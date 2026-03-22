<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$headers = getallheaders();
$csrf_header = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_header)) {
    echo json_encode(['success' => false, 'message' => 'Error de validación CSRF']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$name = $data['name'];
$email = $data['email'];
$phone = $data['phone'] ?? '';
$address = $data['address'] ?? '';
$service = $data['service'];
$description = $data['description'];

$sql = "INSERT INTO quotes (client_name, client_email, client_phone, client_address, service_type, description) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$name, $email, $phone, $address, $service, $description])) {

    // Generate Ticket Number (using the ID)
    $ticketId = $pdo->lastInsertId();
    $ticketCode = 'COT-' . str_pad($ticketId, 6, '0', STR_PAD_LEFT);
    $dateStr = date('d/m/Y H:i');

    // Email Logic
    $to = 'admin@omade.com.mx'; // Replace with actual admin email
    $subject = "Nueva Cotización Recibida - $ticketCode";
    
    // Barcode URL (Static API for Email)
    $barcodeUrl = "https://bwipjs-api.metafloor.org/?bcid=code128&text=$ticketCode&scale=2&height=10&incltext=false";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { background-color: #e2e8f0; font-family: 'Arial', sans-serif; padding: 20px; }
            .email-container {
                background: white;
                max-width: 800px;
                margin: 0 auto;
                padding: 40px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .header-table { width: 100%; margin-bottom: 20px; }
            .company-name { font-size: 18px; font-weight: bold; color: #333; }
            .doc-title { 
                background: #e2e8f0;
                padding: 5px 10px;
                font-weight: bold;
                font-size: 16px;
                font-family: sans-serif;
                text-align: right;
                border-bottom: 2px solid #333;
            }
            .meta-table { width: 100%; font-size: 11px; margin-top: 5px; }
            .client-box {
                border-top: 2px solid #333;
                border-bottom: 2px solid #333;
                padding: 10px 0;
                margin-bottom: 10px;
                font-size: 12px;
                width: 100%;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 12px;
            }
            .items-table th {
                background: #b0c4de;
                color: #000;
                padding: 8px;
                text-align: left;
            }
            .items-table td {
                padding: 8px;
                border-bottom: 1px solid #eee;
            }
            .footer-table { width: 100%; margin-top: 20px; }
            .total-row { background: #b0c4de; font-weight: bold; padding: 5px; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            
            <!-- Header -->
            <table class='header-table'>
                <tr>
                    <td width='60%' valign='top'>
                        <div class='company-name' style='margin-bottom: 5px;'>DISTRIBUCIONES OMADE S.A. DE C.V.</div>
                        <div style='font-size: 11px; color: #555;'>
                            Calle Ficticia 123, Col. Centro, CDMX<br>
                            RFC: OMA230101XYZ &bull; Tel: 55 1234 5678
                        </div>
                    </td>
                    <td width='40%' valign='top'>
                        <div class='doc-title'>COTIZACIÓN</div>
                        <table class='meta-table'>
                            <tr><td><strong>No. Folio:</strong></td><td align='right'>$ticketCode</td></tr>
                            <tr><td><strong>Fecha:</strong></td><td align='right'>$dateStr</td></tr>
                            <tr><td><strong>Lugar:</strong></td><td align='right'>CDMX, México</td></tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Client Info -->
            <div class='client-box'>
                <table width='100%'>
                    <tr>
                        <td><strong>Cliente:</strong> $name</td>
                        <td><strong>Correo:</strong> $email</td>
                    </tr>
                    <tr>
                        <td><strong>Teléfono:</strong> $phone</td>
                        <td><strong>Dirección:</strong> $address</td>
                    </tr>
                </table>
            </div>

            <!-- Items -->
            <table class='items-table'>
                <thead>
                    <tr>
                        <th width='10%'>Cant.</th>
                        <th width='10%'>Unidad</th>
                        <th>Descripción</th>
                        <th width='15%' align='right'>Precio</th>
                        <th width='15%' align='right'>Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1.00</td>
                        <td>SERV</td>
                        <td>
                            <strong>Solicitud: $service</strong><br>
                            ".nl2br(htmlspecialchars($description))."
                        </td>
                        <td align='right'>$0.00</td>
                        <td align='right'>$0.00</td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <table class='footer-table'>
                <tr>
                    <td width='65%' valign='top' style='font-size: 11px; color: #666;'>
                        <strong>Notas:</strong><br>
                        Esta solicitud de cotización será revisada por un agente.<br>
                        Los precios finales serán confirmados a la brevedad.
                        <br><br>
                        <strong>COCHE:</strong> ".htmlspecialchars($service)."
                    </td>
                    <td width='35%' valign='top'>
                        <table width='100%' style='font-size: 12px;'>
                            <tr>
                                <td>Subtotal:</td>
                                <td align='right'>$0.00</td>
                            </tr>
                            <tr>
                                <td>IVA (16%):</td>
                                <td align='right'>$0.00</td>
                            </tr>
                            <tr style='font-weight: bold; background: #b0c4de;'>
                                <td style='padding: 5px;'>Total:</td>
                                <td align='right' style='padding: 5px;'>$0.00</td>
                            </tr>
                        </table>
                        <div style='text-align: right; font-size: 10px; margin-top: 5px; font-weight: bold;'>
                            (CERO PESOS 00/100 M.N.)
                        </div>
                    </td>
                </tr>
            </table>

            <div style='text-align: center; margin-top: 30px; font-size: 10px; color: #aaa;'>
                Este correo fue generado automáticamente por el sistema OMADE.
            </div>

        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: sistema@omade.com.mx" . "\r\n";

    // Send to Admin
    mail($to, $subject, $message, $headers);
    // Optionally Send to Client
    mail($email, "Hemos recibido tu cotización - $ticketCode", $message, $headers);

    echo json_encode(['success' => true, 'message' => '¡Cotización enviada con éxito! Se ha generado el ticket de seguimiento: ' . $ticketCode]);

} else {
    echo json_encode(['success' => false, 'message' => 'Error al enviar la solicitud.']);
}
?>
