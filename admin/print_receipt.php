<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Acceso Denegado");
}

$id = $_GET['id'] ?? null;
if (!$id) die("ID Inválido");

// Fetch Order
$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name, u.paternal_surname 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) die("Pedido no encontrado");

$clientName = $data['client_name'] ?? 'Cliente General';
$sellerName = strtoupper($data['user_name'] . ' ' . $data['paternal_surname']);
$date = date('d/m/Y', strtotime($data['created_at']));
$time = date('h:i A', strtotime($data['created_at']));
$total = $data['total'];

// Fetch Items
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

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
    <title>Ticket #<?= $id ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <style>
        body { margin: 0; padding: 20px 0; background: #e0e0e0; font-family: 'Arial', sans-serif; display: flex; justify-content: center; }
        .receipt { background: white; width: 70mm; padding: 5mm 2mm; box-shadow: 0 0 10px rgba(0,0,0,0.1); font-size: 11px; color: #000; line-height: 1.3; }
        .centered { text-align: center; }
        .bold { font-weight: bold; }
        .logo-circle { width: 60px; height: 60px; border: 2px solid #000; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 10px; margin: 0 auto; }
        .header-text { font-size: 10px; margin-top: 5px; text-align: center; }
        .receipt-title { margin-top: 10px; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .meta-block { margin-top: 10px; text-align: left; font-size: 10px; }
        .items-block { margin-top: 10px; font-family: 'Courier New', Courier, monospace; font-size: 10px; }
        .item-row { display: flex; justify-content: space-between; }
        .totals-block { margin-top: 15px; text-align: right; font-size: 11px; font-weight: bold; border-top: 1px dashed #999; padding-top: 5px; }
        .amount-text { text-align: center; font-size: 9px; margin: 5px 0; text-transform: uppercase; }
        .footer-logo { margin-top: 15px; text-align: center; font-weight: bold; font-size: 14px; border: 2px solid #000; padding: 5px; display: inline-block; }
        .paid-stamp { font-size: 24px; font-weight: 900; text-align: center; margin: 10px 0; letter-spacing: 2px; }
        @media print {
            body { background: white; padding: 0; }
            .receipt { box-shadow: none; width: 100%; }
            .no-print, #print-sensor { display: none !important; }
        }
    </style>
</head>
<body onload="generateBarcode()">

<div class="receipt">
    <div class="logo-container centered">
        <img src="../assets/images/logo/Logo.png" style="width: 80px; height: auto; margin-bottom: 5px;">
        <div class="header-text">
            DISTRIBUCIONES OMADE<br>
            IRMA DOLORES TEC CANCHE<br>
            RFC: TECI900407CG7<br>
            CALLE 18 POR 25 Y 27 S/N, LOC. IXIL 97343<br>
            IXIL, YUCATÁN<br>
            Tel: 999 232 3981<br>
            Email: omaderefaccciones@gmail.com<br>
            <b>SUC. MATRIZ</b>
        </div>
    </div>

    <div class="centered receipt-title">
        NOTA DE VENTA<br>
        FOLIO: OMD-<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?>
    </div>

    <div class="meta-block">
        <?= $date ?> &nbsp;&nbsp;&nbsp;&nbsp; <?= $time ?><br>
        MO/<?= strtoupper($sellerName) ?><br>
        CLIENTE: <?= strtoupper($clientName) ?><br>
        RFC: TECI900407CG7<br>
        EXPEDIDO EN: IXIL, YUCATÁN, MÉXICO
    </div>

    <!-- Items -->
    <div class="items-block" style="border-bottom: 1px dashed #999; margin-bottom: 5px; padding-bottom: 5px;">
        <div class="item-row" style="font-weight: bold;">
            <span style="width: 15%;">CANT</span>
            <span style="width: 55%;">DESCRIPCION</span>
            <span style="width: 30%; text-align: right;">IMPORTE</span>
        </div>
    </div>

    <div class="items-block">
        <?php foreach($items as $item): ?>
            <div style="margin-bottom: 8px;">
                 <div style="font-weight: bold;"><?= strtoupper($item['name']) ?></div>
                 <div class="item-row">
                     <span style="width: 20%; text-align: center;"><?= $item['quantity'] ?></span>
                     <span style="width: 50%;">PZA &nbsp; $<?= number_format($item['price'], 2) ?></span>
                     <span style="width: 30%; text-align: right;">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                 </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="totals-block">
        TOTAL: &nbsp; $ <?= number_format($total, 2) ?>
    </div>

    <div class="amount-text">
        SON: <?= numeroALetras($total) ?>
    </div>
    
    <div class="centered">
        <div class="footer-logo">OMADE<br><span style="font-size: 10px; font-weight: normal;">AUTOPARTES</span></div>
        <div class="paid-stamp">PAGADO</div>
        <div style="font-size: 9px;">
            GRACIAS POR SU COMPRA<br>
            GARANTIA CON EMPAQUE ORIGINAL
        </div>
        
        <svg id="barcode" style="width: 100%; height: 50px; margin-top: 10px;"></svg>
        <div style="text-align: center; font-size: 10px;">
            <?= $date ?> <?= $time ?>
        </div>
    </div>
</div>

<?php
// Build WhatsApp message from order data
$waItems = '';
foreach($items as $it) {
    $waItems .= "\n  • " . strtoupper($it['name']) . " x" . $it['quantity'] . " — $" . number_format($it['price'] * $it['quantity'], 2);
}
$waMsg = "🧾 *NOTA DE VENTA — OMADE*\n"
       . "Folio: OMD-" . str_pad($id, 6, '0', STR_PAD_LEFT) . "\n"
       . "Fecha: $date $time\n"
       . "Cliente: " . strtoupper($clientName) . "\n"
       . "Atendió: " . strtoupper($sellerName) . "\n"
       . "----------------------------"
       . $waItems . "\n"
       . "----------------------------\n"
       . "*TOTAL: $" . number_format($total, 2) . "*\n"
       . "¡Gracias por su compra! 🙏";
$waEncoded = rawurlencode($waMsg);
?>

<div class="no-print" style="position: fixed; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
    <button onclick="window.print()" style="background: #1e293b; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 0.95rem; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">🖨️ Imprimir</button>
    <button id="btn-wa" onclick="compartirWA()" style="background: #25d366; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 0.95rem; box-shadow: 0 2px 8px rgba(0,0,0,0.2); display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 6px;"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
        WhatsApp
    </button>
</div>

<script>
// Show WhatsApp button only on mobile/tablet
(function() {
    const isMobile = /Android|iPhone|iPad|iPod|HarmonyOS/i.test(navigator.userAgent)
                  || (navigator.maxTouchPoints > 1);
    if (isMobile) document.getElementById('btn-wa').style.display = 'block';
})();

async function compartirWA() {
    const btn = document.getElementById('btn-wa');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '⏳ Capturando...';
    btn.disabled = true;

    try {
        // Capture the receipt div as image
        const canvas = await html2canvas(document.querySelector('.receipt'), {
            scale: 3,           // High resolution
            useCORS: true,
            backgroundColor: '#ffffff'
        });

        canvas.toBlob(async function(blob) {
            if (!blob) {
                // Some browsers return null — fallback to text
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                window.open('https://wa.me/?text=<?= $waEncoded ?>', '_blank');
                return;
            }
            const fileName = 'Ticket-OMD-<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?>.png';
            const file = new File([blob], fileName, { type: 'image/png' });

            // Try Web Share API with file (Android/iOS opens native share sheet → WhatsApp as image)
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                try {
                    await navigator.share({
                        files: [file],
                        title: 'Ticket OMADE',
                        text: 'Ticket de venta - Distribuciones Omade'
                    });
                } catch(e) { /* cancelled */ }
            } else if (navigator.share) {
                // Share URL fallback if files not supported
                await navigator.share({
                    title: 'Ticket OMADE',
                    url: window.location.href
                }).catch(() => {});
            } else {
                // Last fallback: wa.me with text
                window.open('https://wa.me/?text=<?= $waEncoded ?>', '_blank');
            }

            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }, 'image/png');

    } catch(err) {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        // Fallback to text on error
        window.open('https://wa.me/?text=<?= $waEncoded ?>', '_blank');
    }
}
</script>

<script>
    function generateBarcode() {
        try {
            JsBarcode("#barcode", "OMD-<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?>", {
                format: "CODE128", width: 2, height: 40, displayValue: false, margin: 0
            });
        } catch(e){}
        
        
        
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        
        window.onafterprint = function() {
            if (isMobile) {
                // Show "Sensor" Menu instead of closing, with delay
                setTimeout(function(){
                    let overlay = document.createElement('div');
                    overlay.id = 'print-sensor';
                    overlay.innerHTML = `
                        <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; display:flex; align-items:center; justify-content:center;">
                            <div style="background:white; padding:20px; border-radius:10px; text-align:center; max-width:80%;">
                                <h3 style="margin-top:0;">¿Se imprimió correctamente?</h3>
                                <button onclick="window.close()" style="background:#22c55e; color:white; padding:10px 20px; border:none; border-radius:5px; margin:5px; font-weight:bold; width:100%;">SI - CERRAR</button><br>
                                <button onclick="document.getElementById('print-sensor').remove()" style="background:#64748b; color:white; padding:10px 20px; border:none; border-radius:5px; margin:5px; width:100%;">NO - REVISAR TICKET</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                }, 1500);
            } else {
                window.close();
            }
        };

        // Auto print trigger (non-blocking if possible)
        setTimeout(function(){
            window.print();
        }, 800);
    }
</script>
</body>
</html>
