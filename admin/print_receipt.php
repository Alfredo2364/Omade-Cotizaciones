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

<div class="no-print" style="position: fixed; bottom: 20px; right: 20px;">
    <button onclick="window.print()" style="background: #000; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">🖨️ Imprimir</button>
</div>

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
