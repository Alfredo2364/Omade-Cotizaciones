<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Acceso Denegado");
}

$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'order'; // 'order' or 'quote'

if (!$id) die("ID Inválido");

$data = null;
$items = [];
$title = "TICKET DE VENTA";
$clientName = "Cliente General";
$date = date('d/m/Y H:i');
$total = 0;

if ($type == 'order') {
    // Fetch Order
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    
    if (!$data) die("Pedido no encontrado");
    
    $title = "NOTA DE VENTA";
    $clientName = $data['client_name'] ?? 'Venta en Tienda';
    $date = date('d/m/Y h:i A', strtotime($data['created_at']));
    $total = $data['total'];

    // Fetch Items
    $stmtItems = $pdo->prepare("
        SELECT oi.*, p.name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?");
    $stmtItems->execute([$id]);
    $items = $stmtItems->fetchAll();
    
} elseif ($type == 'quote') {
    // Fetch Quote
    $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    
    if (!$data) die("Cotización no encontrada");
    
    $title = "COTIZACIÓN FORMAL";
    $clientName = $data['client_name'];
    $date = date('d/m/Y', strtotime($data['created_at']));
    $total = $data['total'] ?? 0;
    
    // Parse Items JSON if available
    if (!empty($data['items_json'])) {
        $quoteItems = json_decode($data['items_json'], true);
        if (is_array($quoteItems)) {
            foreach ($quoteItems as $qi) {
                $items[] = [
                    'quantity' => $qi['qty'],
                    'name' => $qi['name'],
                    'price' => $qi['price']
                ];
            }
        }
    }
}

// --- Helper Functions (Legacy PHP Compatible) ---
function numeroALetras($number) {
    if (!is_numeric($number)) return "CERO PESOS";
    $number = number_format($number, 2, '.', '');
    $parts = explode('.', $number);
    $integer = $parts[0];
    $fraction = $parts[1];
    
    return strtoupper(convertGroup($integer)) . " PESOS " . $fraction . "/100 M.N.";
}

function convertGroup($n) {
    if ($n == 0) return "CERO";
    $n = str_pad($n, 9, '0', STR_PAD_LEFT);
    $millions = substr($n, 0, 3);
    $thousands = substr($n, 3, 3);
    $hundreds = substr($n, 6, 3);
    
    $res = "";
    if (intval($millions) > 0) {
        $res .= ($millions == '001') ? "UN MILLON " : convertThreeDigits($millions) . " MILLONES ";
    }
    if (intval($thousands) > 0) {
        $res .= ($thousands == '001') ? "MIL " : convertThreeDigits($thousands) . " MIL ";
    }
    if (intval($hundreds) > 0) {
        $res .= convertThreeDigits($hundreds);
    }
    return trim($res);
}

function convertThreeDigits($n) {
    $n = intval($n);
    if ($n == 0) return "";
    $hundreds = floor($n / 100);
    $remainder = $n % 100;
    
    $str = "";
    if ($hundreds == 1) $str .= ($remainder==0) ? "CIEN " : "CIENTO ";
    elseif ($hundreds == 2) $str .= "DOSCIENTOS ";
    elseif ($hundreds == 3) $str .= "TRESCIENTOS ";
    elseif ($hundreds == 4) $str .= "CUATROCIENTOS ";
    elseif ($hundreds == 5) $str .= "QUINIENTOS ";
    elseif ($hundreds == 6) $str .= "SEISCIENTOS ";
    elseif ($hundreds == 7) $str .= "SETECIENTOS ";
    elseif ($hundreds == 8) $str .= "OCHOCIENTOS ";
    elseif ($hundreds == 9) $str .= "NOVECIENTOS ";
    
    if ($remainder > 0) {
        if ($remainder < 10) {
            $u = array("","UN","DOS","TRES","CUATRO","CINCO","SEIS","SIETE","OCHO","NUEVE");
            $str .= $u[$remainder];
        } elseif ($remainder < 20) {
            $t = array("DIEZ","ONCE","DOCE","TRECE","CATORCE","QUINCE","DIECISEIS","DIECISIETE","DIECIOCHO","DIECINUEVE");
            $str .= $t[$remainder-10];
        } else {
            $tens = array("","","VEINTE","TREINTA","CUARENTA","CINCUENTA","SESENTA","SETENTA","OCHENTA","NOVENTA");
            $ten = floor($remainder / 10);
            $unit = $remainder % 10;
            
            if ($ten==2 && $unit>0) {
                 $u2 = array("","UNO","DOS","TRES","CUATRO","CINCO","SEIS","SIETE","OCHO","NUEVE"); 
                 $str .= "VEINTI" . $u2[$unit];
            } else {
                $str .= $tens[$ten];
                if ($unit>0) {
                     $u = array("","UN","DOS","TRES","CUATRO","CINCO","SEIS","SIETE","OCHO","NUEVE");
                     $str .= " Y " . $u[$unit];
                }
            }
        }
    }
    return trim($str);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir <?= $title ?> #<?= $id ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

        /* PDF Loading Overlay */
        #pdf-loading {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,0.75); z-index: 999999;
            flex-direction: column; align-items: center; justify-content: center;
            color: white; font-family: 'Roboto', sans-serif;
            backdrop-filter: blur(4px);
        }
        #pdf-loading.show { display: flex; }
        #pdf-loading .spinner {
            width: 56px; height: 56px; border: 5px solid rgba(255,255,255,0.2);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.8s linear infinite; margin-bottom: 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        #pdf-loading p { font-size: 1.1rem; font-weight: 500; margin: 0; }
        #pdf-loading small { font-size: 0.85rem; opacity: 0.7; margin-top: 6px; }

        /* Toast feedback */
        #pdf-toast {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-80px);
            background: #22c55e; color: white; padding: 12px 24px; border-radius: 50px;
            font-weight: 600; z-index: 999998; transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
            display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        #pdf-toast.show { transform: translateX(-50%) translateY(0); }

        body {
            background: #e2e8f0;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 40px;
            color: #333;
            font-size: 12px;
        }

        /* Mobile Screen Optimizations */
        @media screen and (max-width: 768px) {
            body { padding: 10px; }
            .document-container { padding: 15px; margin-bottom: 50px; }
            .back-link { position: static; display: block; margin-bottom: 10px; width: fit-content; }
            .header { flex-direction: column; }
            .header-left, .header-right { width: 100%; margin-bottom: 10px; }
            .print-btn { bottom: 10px; right: 10px; padding: 10px 20px; font-size: 14px; }
        }

        .document-container {
            background: white;
            width: 100%;
            max-width: 800px; /* Letter/A4 width approx */
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            width: 60%;
        }

        .logo-box {
            width: 100px;
            height: 100px;
            margin-right: 20px;
        }
        .logo-box img { width: 100%; height: 100%; object-fit: contain; }

        .company-info {
            font-size: 10px;
            line-height: 1.4;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .header-right {
            width: 35%;
            border: 1px solid #333;
        }

        .doc-title-box {
            background: #e2e8f0; /* Light gray/blue */
            padding: 5px 10px;
            font-weight: bold;
            font-size: 16px;
            text-align: right;
            border-bottom: 1px solid #333;
        }

        .doc-meta {
            padding: 10px;
            font-size: 11px;
            line-height: 1.6;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
        }
        .meta-label { font-weight: bold; }

        /* CLIENT INFO STRIP */
        .client-info-box {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            padding: 10px 0;
            margin-bottom: 5px; /* Tiny gap before table */
            font-size: 11px;
            display: flex;
            flex-wrap: wrap;
        }
        
        .client-col { width: 50%; margin-bottom: 5px; }
        .client-label { font-weight: bold; margin-right: 5px; }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        th {
            background: #b0c4de; /* Light Steel Blue */
            color: #000;
            font-weight: bold;
            text-align: left;
            padding: 5px;
            border: 1px solid white; /* Separator look */
        }
        
        td {
            padding: 8px 5px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* FOOTER / TOTALS */
        .footer-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 20px;
        }

        .comments-section {
            width: 60%;
            font-size: 10px;
            color: #555;
            padding-right: 20px;
        }

        .totals-box {
            width: 35%;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px;
            border-bottom: 1px solid #ccc;
        }
        .total-row.final {
            background: #b0c4de;
            font-weight: bold;
            border: 1px solid #333;
            border-top: none;
        }
        .total-row.sub {
            background: #f1f5f9;
        }

        .amount-text {
            text-align: right;
            margin-top: 5px;
            font-size: 10px;
            font-weight: bold;
            font-style: italic;
        }

        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0056b3;
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            text-decoration: none;
            font-weight: 700;
            z-index: 100;
        }
        .back-link {
            position: fixed;
            top: 30px;
            left: 30px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            background: white;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        @page {
            size: 279.4mm 215.9mm; /* Letter Landscape exact dimensions */
            margin: 10mm;
        }

        @media print {
            html, body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .document-container {
                width: 100%;
                max-width: 100% !important;
                margin: 0;
                padding: 10px;
                box-shadow: none;
                border: none;
                font-size: 10px;
            }
            .print-btn, .back-link, #print-sensor, .tablet-banner {
                display: none !important;
            }
            .header, .client-info-box, table, .footer-section {
                width: 100%;
            }
            table { page-break-inside: avoid; }
            .header { page-break-after: avoid; }
        }

        /* Tablet specific screen styles */
        @media screen and (max-width: 1024px) {
            body { padding: 10px; }
            .document-container { padding: 20px; }
            .back-link { position: static; display: block; margin-bottom: 10px; }
            .print-btn { bottom: 10px; right: 10px; font-size: 14px; padding: 12px 20px; }
        }

        /* Phone */
        @media screen and (max-width: 600px) {
            body { padding: 5px; }
            .document-container { padding: 10px; font-size: 10px; }
            .header { flex-direction: column; }
            .header-left, .header-right { width: 100%; margin-bottom: 10px; }
            .logo-box { width: 60px; height: 60px; }
            .form-row { grid-template-columns: 1fr; }
            .footer-section { flex-direction: column; }
            .comments-section, .totals-box { width: 100%; padding-right: 0; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- PDF Loading Overlay -->
    <div id="pdf-loading">
        <div class="spinner"></div>
        <p>Generando PDF...</p>
        <small>Esto puede tardar unos segundos</small>
    </div>

    <!-- Toast Notification -->
    <div id="pdf-toast"><i class="fas fa-check-circle"></i> PDF descargado correctamente</div>

    <a href="javascript:history.back()" class="back-link"><i class="fas fa-arrow-left"></i> Volver</a>
    <a href="javascript:window.print()" class="print-btn"><i class="fas fa-print"></i> IMPRIMIR</a>

    <div class="document-container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <div class="logo-box">
                    <img src="../assets/images/logo/Logo.png" alt="OMADE">
                </div>
                <div class="company-info">
                    <div class="company-name">DISTRIBUCIONES OMADE</div>
                    <strong>IRMA DOLORES TEC CANCHE</strong><br>
                    RFC: TECI900407CG7<br>
                    Calle 18 x 25 y 27 S/N, Loc. Ixil 97343<br>
                    Ixil, Yucatán<br>
                    Tel: 999 232 3981 | Email: omaderefaccciones@gmail.com
                </div>
            </div>
            <div class="header-right">
                <div class="doc-title-box">
                    <?= $type == 'quote' ? 'Cotización' : 'Nota de Venta' ?>
                </div>
                <div class="doc-meta">
                    <div class="meta-row">
                        <span class="meta-label">No. Folio:</span>
                        <span><?= ($type=='quote'?'COT-':'OMD-') . str_pad($id, 6, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Lugar Expedición:</span>
                        <span>Yucatán, México</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Fecha Emisión:</span>
                        <span><?= $date ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CLIENT INFO -->
        <div class="client-info-box">
            <div class="client-col"><span class="client-label">Cliente:</span> <?= htmlspecialchars($clientName) ?></div>
            <?php if ($type != 'quote'): ?>
            <div class="client-col"><span class="client-label">RFC:</span> TECI900407CG7</div>
            <?php endif; ?>
            
            <?php
            // Email Logic: 
            // - If Quote: Show Client Email (who accepted/requested)
            // - If Order (POS): Show Admin Email (omaderefaccciones@gmail.com)
            $displayEmail = ($type == 'quote') ? ($data['client_email'] ?? 'No registrado') : 'omaderefaccciones@gmail.com';
            
            // Address Logic:
            // - If Quote: "Cotizacion Online"
            // - If Order (POS): "Venta en Tienda"
            $displayAddress = ($type == 'quote') ? 'Cotizacion Online' : 'Venta en Tienda';
            ?>
            
            <div class="client-col"><span class="client-label">Correo:</span> <?= htmlspecialchars($displayEmail) ?></div>
            <div class="client-col"><span class="client-label">Teléfono:</span> 999 232 3981</div>
            <div class="client-col" style="width: 100%; margin-top: 5px;">
                <span class="client-label">Dirección:</span> <?= htmlspecialchars($displayAddress) ?>
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Cant.</th>
                    <th style="width: 10%;">Unidad</th>
                    <th>Descripción</th>
                    <th style="width: 15%; text-align: right;">P. Unitario</th>
                    <th style="width: 15%; text-align: right;">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($type == 'quote' && empty($items)): ?>
                    <!-- Quote Content (Description Only if no items) -->
                     <tr>
                        <td>1.00</td>
                        <td>SERVICIO</td>
                        <td>
                            <strong><?= htmlspecialchars($data['service_type']) ?></strong><br>
                            <?= nl2br(htmlspecialchars($data['description'])) ?>
                        </td>
                        <td style="text-align: right;">$<?= number_format($data['total'] ?? 0, 2) ?></td>
                        <td style="text-align: right;">$<?= number_format($data['total'] ?? 0, 2) ?></td>
                     </tr>
                <?php else: ?>
                    <!-- Order/Quote Items -->
                    <?php if(empty($items) && $type != 'quote'): ?>
                         <tr>
                            <td>1.00</td>
                            <td>PZ</td>
                            <td>Venta General</td>
                            <td style="text-align: right;">$<?= number_format($total, 2) ?></td>
                            <td style="text-align: right;">$<?= number_format($total, 2) ?></td>
                         </tr>
                    <?php else: ?>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><?= $item['quantity'] ?></td>
                            <td>mult</td> <!-- Placeholder unit -->
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td style="text-align: right;">$<?= number_format($item['price'], 2) ?></td>
                            <td style="text-align: right;">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- FOOTER & TOTALS -->
        <div class="footer-section">
            <div class="comments-section">
                <?php if($type == 'quote'): ?>
                    Esta cotización tiene una vigencia de 15 días. Sujeta a cambios sin previo aviso.<br>
                    Para proceder, favor de contactar a su asesor.
                <?php else: ?>
                    Gracias por su compra.<br>
                    Garantía sujeta a términos y condiciones del fabricante.
                <?php endif; ?>
                
                <div style="margin-top: 20px; font-weight: bold;">
                    <?= $type == 'quote' ? 'COCHE: '. htmlspecialchars($data['service_type']) : '' ?>
                </div>
            </div>

            <div class="totals-box">
                <?php 
                    // Calculate Sum of Items
                    $itemsSum = 0;
                    foreach($items as $item) {
                        $itemsSum += $item['price'] * $item['quantity'];
                    }
                    
                    // Final Total stored in DB
                    $finalTotal = $type == 'quote' ? ($data['total'] ?? 0) : $total;
                    
                    // If no items (manual quote), assume sum = total
                    if(empty($items)) $itemsSum = $finalTotal;

                    // Derived Discount
                    // For quotes, we have explicit discount column now
                    if ($type == 'quote') {
                        $discountVal = $data['discount'] ?? 0;
                        // If items exist, recalculate sum to be sure
                         if(!empty($items)) {
                             $itemsSum = 0;
                             foreach($items as $item) $itemsSum += $item['price'] * $item['quantity'];
                         } else {
                             // If no items, assume base = total + discount
                             $itemsSum = $finalTotal + $discountVal;
                         }
                    } else {
                        $discountVal = max(0, $itemsSum - $finalTotal);
                    }
                    
                    // Base breakdown (assuming prices are IVA inclusive)
                    // If user wants "IVA added", they might mean Prices entered are Net. 
                    // But standard logic usually implies stored prices are what displayed.
                    // Let's stick to standard "Inclusive" logic for now unless instructed to change base.
                    $subtotal = $finalTotal / 1.16;
                    $iva = $finalTotal - $subtotal;
                ?>
                <div class="total-row sub">
                    <span>Subtotal:</span>
                    <span>$<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="total-row sub">
                    <span>Descuento:</span>
                    <span>$<?= number_format($discountVal, 2) ?></span>
                </div>
                <div class="total-row sub">
                    <span>IVA (16%):</span>
                    <span>$<?= number_format($iva, 2) ?></span>
                </div>
                <div class="total-row final">
                    <span>Total:</span>
                    <span>$<?= number_format($finalTotal, 2) ?></span>
                </div>
                <div class="amount-text">
                    (SON: <?= numeroALetras($finalTotal) ?>)
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <!-- PDF Download Button (Visible on Mobile/Tablet primarily) -->
    <button onclick="downloadPDF()" class="print-btn" style="background: #ef4444; bottom: 80px;" title="Descargar como PDF">
        <i class="fas fa-file-pdf"></i> PDF
    </button>

    <script>
        // ---- PDF Generation with loading overlay + toast ----
        async function downloadPDF() {
            const element = document.querySelector('.document-container');
            const loader = document.getElementById('pdf-loading');
            const toast  = document.getElementById('pdf-toast');
            const isTablet = window.innerWidth <= 1024;
            const isPhone  = window.innerWidth <= 600;
            const orientation = isPhone ? 'portrait' : 'landscape';
            const format      = isPhone ? 'a4' : 'letter';

            loader.classList.add('show');
            try {
                await html2pdf().set({
                    margin:      [8, 8, 8, 8],
                    filename:    'Imprimir <?= strtoupper($title) ?> #<?= $id ?>.pdf',
                    image:       { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: isTablet ? 1.5 : 2, useCORS: true, letterRendering: true },
                    jsPDF:       { unit: 'mm', format: format, orientation: orientation }
                }).from(element).save();

                // Show success toast
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3500);
            } catch(e) {
                alert('Error al generar el PDF. Por favor intenta de nuevo.');
            } finally {
                loader.classList.remove('show');
            }
        }

        // ---- Web Share API (share via WhatsApp, Telegram, Email etc.) ----
        async function shareDocument() {
            if (!navigator.share) {
                // Fallback: copy link to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Enlace copiado al portapapeles');
                });
                return;
            }
            try {
                await navigator.share({
                    title: '<?= $title ?> #<?= str_pad($id,6,'0',STR_PAD_LEFT) ?>',
                    text: 'Documento de <?= htmlspecialchars($clientName) ?>',
                    url: window.location.href
                });
            } catch(e) { /* user cancelled */ }
        }

        // Show tablet banner
        if (window.innerWidth <= 1024) {
            document.getElementById('tablet-banner').style.display = 'block';
        }

        // Device Detection — covers Android, iOS, HarmonyOS (Huawei MatePad, etc.)
        const ua = navigator.userAgent;
        const isAndroid = /Android/i.test(ua);
        const isIOS = /iPhone|iPad|iPod/i.test(ua);
        const isHarmonyOS = /HarmonyOS|HuaweiBrowser/i.test(ua);
        // Fallback: any touch device with tablet-sized screen
        const isTouchTablet = (navigator.maxTouchPoints > 0 && window.innerWidth <= 1340 && window.innerWidth >= 600);
        const isMobileDevice = isAndroid || isIOS || isHarmonyOS || isTouchTablet;
        const isDesktop = !isMobileDevice;

        // ---- DESKTOP: auto-print then close ----
        if (isDesktop) {
            window.onafterprint = function() {
                window.close();
            };
            setTimeout(function() {
                window.print();
            }, 1000);
        }

        // ---- ANDROID / TABLET / HARMONYOS: show action panel ----
        if (isMobileDevice) {
            const hasShare = !!navigator.share;
            const panel = document.createElement('div');
            panel.id = 'mobile-action-panel';
            panel.style.cssText = `
                position: fixed; bottom: 0; left: 0; right: 0;
                background: white; border-radius: 24px 24px 0 0; padding: 12px 20px 30px;
                box-shadow: 0 -8px 30px rgba(0,0,0,0.15); z-index: 99999;
                display: flex; gap: 10px; flex-direction: column; align-items: center;
                animation: slideUp 0.35s cubic-bezier(0.34,1.56,0.64,1);
            `;
            panel.innerHTML = `
                <style>
                    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
                    .mob-btn { width:100%; padding:15px; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; transition:opacity 0.2s; }
                    .mob-btn:active { opacity:0.8; }
                </style>
                <!-- Drag handle -->
                <div style="width:40px;height:4px;background:#e2e8f0;border-radius:4px;margin-bottom:8px;"></div>
                <p style="margin:0 0 8px;font-weight:700;color:#0f172a;font-size:1rem;text-align:center;">
                    <i class="fas fa-print" style="color:#3b82f6;"></i>&nbsp; ¿Qué deseas hacer?
                </p>
                <button class="mob-btn" onclick="
                    if(confirm('💡 Tip: En el diálogo de impresión selecciona \'Horizontal\' para mejor resultado.\n\n¿Continuar?')) window.print();
                " style="background:#0f172a;color:white;">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <button class="mob-btn" onclick="downloadPDF()" style="background:#ef4444;color:white;">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
                ${ hasShare ? `
                <button class="mob-btn" onclick="shareDocument()" style="background:#2563eb;color:white;">
                    <i class="fas fa-share-alt"></i> Compartir (WhatsApp, Email...)
                </button>` : `` }
                <button class="mob-btn" onclick="history.back()" style="background:#f1f5f9;color:#64748b;font-weight:500;">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            `;
            document.body.appendChild(panel);
        }
    </script>
</body>
</html>
