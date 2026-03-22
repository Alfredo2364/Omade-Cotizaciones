<?php require_once '../includes/admin_header.php'; ?>
<?php if (!hasPermission($pdo, $_SESSION['user_id'], 'quotes')) die("Acceso Denegado"); ?>

<!-- Toggle Button -->
<div class="page-header">
    <h1><i class="fas fa-file-invoice-dollar" style="color: #6366f1;"></i> Cotizaciones</h1>
    <button onclick="toggleQuoteForm()" class="btn-new-quote">
        <i class="fas fa-plus"></i> Nueva Cotización
    </button>
</div>

<?php
// Handle Create Quote Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quote'])) {
    if (!isset($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Error de validación CSRF");
    }
    $client_name = $_POST['client_name'];
    $client_email = $_POST['client_email'] ?? '';
    $client_phone = $_POST['client_phone'] ?? '';
    $client_address = $_POST['client_address'] ?? '';
    $service_type = $_POST['service_type'];
    $description = $_POST['description'];

    if (!empty($client_name) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO quotes (client_name, client_email, client_phone, client_address, service_type, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        if ($stmt->execute([$client_name, $client_email, $client_phone, $client_address, $service_type, $description])) {
            logActivity($pdo, $_SESSION['user_id'], 'CREATE_QUOTE', "Creó cotización manual para: $client_name");
            $_SESSION['flash'] = ['message' => 'Cotización creada correctamente', 'type' => 'success'];
            echo "<script>window.location.href='quotes.php';</script>";
        } else {
            echo "<script>showToast('Error al crear cotización', 'error');</script>";
        }
    } else {
        echo "<script>showToast('Nombre y Descripción son requeridos', 'error');</script>";
    }
}
?>

<!-- Animated Form Container (New Quote) -->
<div id="quoteFormContainer" class="quote-form-overlay" style="display: none;">
    <div class="quote-form-card">
        <div class="form-header">
            <div class="header-title">
                <div class="icon-box"><i class="fas fa-file-invoice"></i></div>
                <h3>Nueva Solicitud</h3>
            </div>
            <button onclick="toggleQuoteForm()" class="close-btn"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="form-body">
                <!-- Client Info Section -->
                <div class="section-title">Información del Cliente</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre Completo <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="client_name" required placeholder="Ej: Juan Pérez">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="client_email" placeholder="cliente@ejemplo.com">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone"></i>
                            <input type="text" name="client_phone" placeholder="55 1234 5678">
                        </div>
                    </div>
                    <div class="form-group">
                         <label>Tipo de Servicio</label>
                         <div class="input-wrapper">
                            <i class="fas fa-tools"></i>
                            <select name="service_type">
                                <option value="Refacciones">Refacciones</option>
                                <option value="Logistica">Logística</option>
                                <option value="Otro">Otro</option>
                            </select>
                            <i class="fas fa-chevron-down arrow-icon"></i>
                         </div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Dirección (Opcional)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="client_address" placeholder="Calle, Número, Colonia...">
                    </div>
                </div>

                <!-- Request Details Section -->
                <div class="section-title" style="margin-top: 20px;">Detalles de la Solicitud</div>
                <div class="form-group full-width">
                    <label>Descripción <span class="required">*</span></label>
                    <textarea name="description" rows="4" required placeholder="Describe lo que necesita el cliente..."></textarea>
                </div>
            </div>

            <div class="form-footer">
                <button type="button" onclick="toggleQuoteForm()" class="btn-cancel">Cancelar</button>
                <button type="submit" name="create_quote" class="btn-submit">
                    <span>Crear Cotización</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Premium Modal Styles */
    .quote-form-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
        z-index: 30000; display: flex; align-items: flex-start; justify-content: center;
        padding: 20px; overflow-y: auto;
        opacity: 0; visibility: hidden; transition: all 0.3s ease;
    }
    .quote-form-overlay.open { opacity: 1; visibility: visible; }

    .quote-form-card {
        background: white; width: 100%; max-width: 650px;
        border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        transform: translateY(20px); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden; display: flex; flex-direction: column;
        margin: auto; /* Centrado vertical */
    }
    .quote-form-overlay.open .quote-form-card { transform: translateY(0); }

    /* Header */
    .form-header {
        padding: 20px 30px; background: white; border-bottom: 1px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center;
    }
    .header-title { display: flex; align-items: center; gap: 15px; }
    .icon-box {
        width: 40px; height: 40px; background: #eff6ff; color: #3b82f6;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    .header-title h3 { margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 700; }
    
    .close-btn {
        background: transparent; border: none; font-size: 1.2rem; color: #94a3b8;
        cursor: pointer; padding: 5px; transition: color 0.2s;
    }
    .close-btn:hover { color: #ef4444; }

    /* Body */
    .form-body { padding: 30px; overflow-y: visible; }
    
    .section-title {
        font-size: 0.85rem; font-weight: 700; text-transform: uppercase;
        color: #94a3b8; letter-spacing: 0.5px; margin-bottom: 15px;
    }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }

    .form-group { margin-bottom: 20px; }
    .form-group:last-child { margin-bottom: 0; }
    .full-width { grid-column: span 2; margin-bottom: 0; }
    
    .form-group label {
        display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 0.9rem;
    }
    .required { color: #ef4444; margin-left: 2px; }

    .input-wrapper { position: relative; }
    .input-wrapper i {
        position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
        color: #94a3b8; font-size: 1rem; pointer-events: none;
    }
    .input-wrapper .arrow-icon { left: auto; right: 15px; font-size: 0.8rem; }
    
    .input-wrapper input, .input-wrapper select, textarea {
        width: 100%; padding: 12px 15px 12px 45px;
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px; font-size: 0.95rem; color: #1e293b;
        transition: all 0.2s; outline: none; font-family: inherit;
        appearance: none; -webkit-appearance: none;
    }
    textarea { padding: 15px; height: 100px; resize: vertical; }

    .input-wrapper input:focus, .input-wrapper select:focus, textarea:focus {
        background: white; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    /* Footer */
    .form-footer {
        padding: 20px 30px; background: #f8fafc; border-top: 1px solid #f1f5f9;
        display: flex; justify-content: flex-end; gap: 15px;
    }
    
    .btn-cancel {
        padding: 12px 24px; background: white; border: 1px solid #e2e8f0;
        color: #64748b; font-weight: 600; border-radius: 10px; cursor: pointer;
        transition: 0.2s;
    }
    .btn-cancel:hover { background: #f1f5f9; color: #475569; }

    .btn-submit {
        padding: 12px 24px; background: #3b82f6; border: none;
        color: white; font-weight: 600; border-radius: 10px; cursor: pointer;
        display: flex; align-items: center; gap: 8px;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
        transition: 0.2s;
    }
    .btn-submit:hover { background: #2563eb; transform: translateY(-1px); }

    /* New Quote Button */
    .btn-new-quote {
        background: #0f172a; color: white; border: none; padding: 10px 20px;
        border-radius: 30px; cursor: pointer; font-weight: 600;
        box-shadow: 0 4px 6px rgba(15, 23, 42, 0.2);
        transition: transform 0.2s; display: flex; align-items: center; gap: 8px;
    }
    .btn-new-quote:hover { transform: translateY(-2px); }

    /* ---- Mobile Responsive ---- */
    @media (max-width: 768px) {
        .page-header { flex-wrap: wrap; gap: 10px; }
        .page-header h1 { font-size: 1.2rem; }
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    }
    @media (max-width: 480px) {
        .form-footer { flex-direction: column; gap: 10px; }
        .btn-cancel, .btn-submit { width: 100%; justify-content: center; }
        .btn-new-quote { padding: 8px 14px; font-size: 0.9rem; }
    }
</style>

<script>
    function toggleQuoteForm() {
        const container = document.getElementById('quoteFormContainer');
        if (container.classList.contains('open')) {
            container.classList.remove('open');
            setTimeout(() => container.style.display = 'none', 300);
        } else {
            container.style.display = 'flex';
            setTimeout(() => container.classList.add('open'), 10);
        }
    }
</script>

<div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
     <div class="card-header-styled" style="background: white; padding: 15px 20px; border-bottom: 1px solid #f1f5f9;">
        <h3 style="margin: 0; color: #1e293b;">Listado de Solicitudes</h3>
    </div>
    <div class="table-container">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $quotes = $pdo->query("SELECT * FROM quotes ORDER BY created_at DESC")->fetchAll();
                
                if (empty($quotes)) {
                    echo "<tr><td colspan='7' style='text-align:center; padding: 30px; color: #64748b;'>No hay cotizaciones pendientes.</td></tr>";
                }

                foreach ($quotes as $q): ?>
                <tr>
                    <td style="font-weight: 600; color: #64748b;">#<?= $q['id'] ?></td>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($q['client_name']) ?></div>
                    </td>
                    <td style="color: #64748b; font-size: 0.9rem;"><?= htmlspecialchars($q['client_email']) ?></td>
                    <td style="font-size: 0.85rem; color: #94a3b8;"><?= date('M d, Y', strtotime($q['created_at'])) ?></td>
                    <td>
                        <?php if($q['status'] == 'pending'): ?>
                            <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;"><i class="far fa-clock"></i> Pendiente</span>
                        <?php elseif($q['status'] == 'approved'): ?>
                            <span class="badge" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;"><i class="fas fa-check"></i> Aprobado</span>
                        <?php else: ?>
                            <span class="badge" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;"><i class="fas fa-times"></i> Rechazado</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 700; color: #0f172a;">
                        $<?= number_format($q['total'], 2) ?>
                    </td>
                    <td style="text-align: center;">
                        <a href="?view=<?= $q['id'] ?>#details-card" class="btn-icon" title="Ver Detalles"><i class="fas fa-eye"></i></a>
                        <a href="print_ticket.php?type=quote&id=<?= $q['id'] ?>" target="_blank" class="btn-icon" title="Imprimir"><i class="fas fa-print"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
        .table-premium { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-premium thead th { 
            background: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; 
            font-size: 0.75rem; letter-spacing: 0.5px; padding: 12px 15px; 
            text-align: left; border-bottom: 2px solid #e2e8f0; 
        }
        .table-premium tbody tr { transition: background 0.2s; }
        .table-premium tbody tr:hover { background: #f8fafc; }
        .table-premium td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
        .table-premium td:first-child { border-left: 3px solid transparent; }
        .table-premium tr:hover td:first-child { border-left-color: #3b82f6; }
        
        .btn-icon { 
            display: inline-flex; align-items: center; justify-content: center; 
            width: 32px; height: 32px; border-radius: 8px; 
            color: #64748b; background: transparent; transition: all 0.2s;
            text-decoration: none;
        }
        .btn-icon:hover { background: #eff6ff; color: #3b82f6; transform: translateY(-2px); }
    </style>
</div>

<?php
// Logic for Details Section and Updates
$selected_quote = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id = ?");
    $stmt->execute([$_GET['view']]);
    $selected_quote = $stmt->fetch();
}

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Error de validación CSRF");
    }
    $new_status = $_POST['status']; // 'approved' or 'rejected'
    $qid = $_POST['quote_id'];
    
    $stmt = $pdo->prepare("UPDATE quotes SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $qid]);
    
    // Logic to create Order if Approved
    if ($new_status == 'approved') {
        $price = $_POST['quote_price'] ?? 0;
        
        // Try to find user
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $uQuoteStmt = $pdo->prepare("SELECT client_email, client_name FROM quotes WHERE id = ?");
        $uQuoteStmt->execute([$qid]);
        $qData = $uQuoteStmt->fetch();
        
        if ($qData) {
            $uStmt->execute([$qData['client_email']]);
            $uData = $uStmt->fetch();
            $userId = $uData ? $uData['id'] : null;
            
            // Insert Order
            $insOrder = $pdo->prepare("INSERT INTO orders (user_id, client_name, total, status, created_at) VALUES (?, ?, ?, 'completed', NOW())");
            $insOrder->execute([$userId, $qData['client_name'], $price]);
            $newOrderId = $pdo->lastInsertId();
            
            // Process Items
            $attachedJson = $_POST['attached_products'] ?? '[]';
            $attachedItems = json_decode($attachedJson, true);

            if (is_array($attachedItems) && !empty($attachedItems)) {
                $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $updateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

                foreach ($attachedItems as $item) {
                     // Check if item has ID (might be manual)
                    if(isset($item['id']) && strpos($item['id'], 'manual_') === false) {
                        $updateStock->execute([$item['qty'], $item['id']]);
                        $stmtItem->execute([$newOrderId, $item['id'], $item['qty'], $item['price']]);
                    } else {
                        // Manual item, just record in order items with null product_id if schema allows, or skip stock
                        // For now, if schema requires product_id, we might need a workaround or just skip stock update
                         // Assuming order_items nullable product_id or handled.
                         // Only logging stock update if real product
                    }
                }
            }
            
            logActivity($pdo, $_SESSION['user_id'], 'CREATE_ORDER', "Pedido #$newOrderId creado desde Cotización #$qid");
            $successMsg = "Cotización aprobada. Pedido #$newOrderId generado.";
        }
    } else {
        $successMsg = "Cotización rechazada correctamente.";
    }

    $action_name = ($new_status == 'approved') ? 'APROBADA' : 'RECHAZADA';
    logActivity($pdo, $_SESSION['user_id'], 'UPDATE_QUOTE', "Cotización #$qid marcada como $action_name");
    
    $_SESSION['flash'] = ['message' => $successMsg, 'type' => 'success'];
    echo "<script>window.location.href='quotes.php';</script>";
}

// Handle Save Quote Details (without status change)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quote_details'])) {
    if (!isset($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Error de validación CSRF");
    }
    $qid = $_POST['quote_id'];
    $discount = $_POST['quote_discount'] ?? 0;
    $total = $_POST['quote_price'] ?? 0;
    $itemsJson = $_POST['attached_products'] ?? '[]';
    
    $stmt = $pdo->prepare("UPDATE quotes SET discount = ?, total = ?, items_json = ? WHERE id = ?");
    if($stmt->execute([$discount, $total, $itemsJson, $qid])) {
        $_SESSION['flash'] = ['message' => 'Detalles guardados correctamente', 'type' => 'success'];
        echo "<script>window.location.href='quotes.php?view=$qid#details-card';</script>";
    } else {
         echo "<script>showToast('Error al guardar detalles', 'error');</script>";
    }
}
?>

<!-- Details Section (Dynamic) -->
<?php if ($selected_quote): ?>
<div class="card" id="details-card" style="margin-top: 30px; border-top: 4px solid #3b82f6;">
    <div class="form-header">
        <h3>Detalles de Cotización #<?= $selected_quote['id'] ?></h3>
    </div>
    
    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin: 15px 0; border: 1px solid #e2e8f0;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($selected_quote['client_name']) ?> <br><span style="color:#64748b; font-size:0.9em;"><?= htmlspecialchars($selected_quote['client_email']) ?></span></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($selected_quote['client_phone'] ?? 'No registrado') ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($selected_quote['client_address'] ?? 'No aplica') ?></p>
            <p><strong>Servicio:</strong> <span class="badge" style="background:#e0f2fe; color:#0369a1; padding:4px 8px; border-radius:4px;"><?= htmlspecialchars($selected_quote['service_type']) ?></span></p>
        </div>
        <div style="margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 10px;">
            <p><strong>Descripción:</strong></p>
            <p style="color: #334155; line-height: 1.5;"><?= nl2br(htmlspecialchars($selected_quote['description'])) ?></p>
        </div>
    </div>
    
    <form method="POST">
        <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="quote_id" value="<?= $selected_quote['id'] ?>">
        
        <div style="margin-top: 25px;">
            <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 1.1rem;">Asignar Productos (Cotización)</h4>
            
            <!-- POS-Style Search Bar -->
            <div style="position: relative; margin-bottom: 20px; z-index: 100;">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <div style="position: relative; flex: 2; min-width: 300px;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="prod-search" placeholder="Buscar producto ..." style="width: 100%; padding: 12px 12px 12px 40px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.2s;">
                        <!-- Dropdown -->
                        <div id="search-results" class="search-results-dropdown"></div>
                    </div>
                    
                    <div style="flex: 1; display: flex; gap: 8px; min-width: 280px; align-items: center;">
                        <input type="text" id="custom-name" placeholder="Item Manual" style="flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; min-width: 0;">
                        <input type="number" id="custom-price" placeholder="$" style="width: 90px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; flex-shrink: 0;">
                        <button type="button" onclick="addCustomItem()" style="background: #10b981; color: white; border: none; padding: 0 15px; height: 38px; border-radius: 8px; cursor: pointer; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Attached Items Table -->
            <table class="table" style="font-size: 0.95rem;">
                <thead style="background: #f8fafc; color: #475569;">
                    <tr>
                        <th style="padding: 10px;">Producto / Servicio</th>
                        <th style="padding: 10px; width: 100px; text-align: center;">Cant.</th>
                        <th style="padding: 10px; width: 120px; text-align: right;">Unitario</th>
                        <th style="padding: 10px; width: 120px; text-align: right;">Total</th>
                        <th style="padding: 10px; width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="quote-items-body"></tbody>
            </table>
            <input type="hidden" name="attached_products" id="attached_products_json">
        </div>

        <!-- POS Search Styles (Scoped) -->
        <style>
            #prod-search:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
            
            .search-results-dropdown {
                position: absolute; top: 100%; left: 0; right: 0; background: white;
                border: 2px solid #3b82f6; border-top: none; border-radius: 0 0 8px 8px;
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 1000;
                display: none; max-height: 300px; overflow-y: auto;
            }
            .search-result-item {
                padding: 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer;
                display: flex; justify-content: space-between; align-items: center;
            }
            .search-result-item:hover { background: #eff6ff; }
            .search-loading { padding: 15px; text-align: center; color: #64748b; }
        </style>

        <script>
            let attachedItems = <?= $selected_quote['items_json'] ? $selected_quote['items_json'] : '[]' ?>;
            
            (function() {
                const searchInput = document.getElementById('prod-search');
                const resultsBox = document.getElementById('search-results');
                let searchTimeout = null;

                async function performSearch(query) {
                    if (!query) { resultsBox.style.display = 'none'; return; }
                    
                    resultsBox.style.display = 'block';
                    resultsBox.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
                    
                    try {
                        const res = await fetch(`../api/pos_search.php?q=${encodeURIComponent(query)}`);
                        const products = await res.json();
                        
                        resultsBox.innerHTML = '';
                        if (products.length > 0) {
                            products.slice(0, 10).forEach(p => {
                                const div = document.createElement('div');
                                div.className = 'search-result-item';
                                div.innerHTML = `
                                    <div>
                                        <div style="font-weight: 600; color: #0f172a;">${p.name}</div>
                                        <small style="color: #64748b;">${p.code || p.product_code || 'S/C'}</small>
                                    </div>
                                    <div style="font-weight: 700; color: #16a34a;">$${parseFloat(p.price).toFixed(2)}</div>
                                `;
                                div.onclick = () => {
                                    addItemToQuote(p);
                                    searchInput.value = '';
                                    resultsBox.style.display = 'none';
                                };
                                resultsBox.appendChild(div);
                            });
                        } else {
                            resultsBox.innerHTML = '<div class="search-loading">No encontrado</div>';
                        }
                    } catch (err) {
                        resultsBox.innerHTML = '<div class="search-loading" style="color:red">Error de conexión</div>';
                    }
                }

                searchInput.addEventListener('input', (e) => {
                    clearTimeout(searchTimeout);
                    if(e.target.value.length > 1) {
                        searchTimeout = setTimeout(() => performSearch(e.target.value), 200);
                    } else {
                        resultsBox.style.display = 'none';
                    }
                });
                
                document.addEventListener('click', (e) => {
                    if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                        resultsBox.style.display = 'none';
                    }
                });
            })();

            function addItemToQuote(product) {
                const exists = attachedItems.find(i => i.id === product.id);
                if(exists) { attachedItems.forEach(i => { if(i.id === product.id) i.qty++; }); } 
                else {
                    attachedItems.push({ 
                        id: product.id, name: product.name, price: parseFloat(product.price), 
                        base_price: parseFloat(product.price), qty: 1,
                        min_qty: parseInt(product.discount_min_qty || 0), disc_pct: parseFloat(product.discount_percent || 0), is_manual: false
                    });
                }
                renderQuoteItems();
            }

            function renderQuoteItems() {
                const tbody = document.getElementById('quote-items-body');
                tbody.innerHTML = '';
                let itemsTotal = 0;

                attachedItems.forEach((item, index) => {
                    if(typeof item.base_price === 'undefined') item.base_price = item.price; 
                    let effectivePrice = item.base_price;
                    let hasDiscount = false;
                    
                    if (!item.is_manual && item.min_qty > 0 && item.qty >= item.min_qty && item.disc_pct > 0) {
                        effectivePrice = item.base_price * (1 - (item.disc_pct / 100));
                        hasDiscount = true;
                    }
                    item.price = effectivePrice;
                    const rowTotal = effectivePrice * item.qty;
                    itemsTotal += rowTotal;

                    const tr = document.createElement('tr');
                    let priceHtml = `$${item.base_price.toFixed(2)}`;
                    if(hasDiscount) priceHtml += `<br><small style="color:green;">${item.disc_pct}% OFF</small>`;

                    tr.innerHTML = `
                        <td style="padding: 10px;">${item.name}</td>
                        <td style="padding: 10px; text-align: center;">
                            <input type="number" min="1" value="${item.qty}" onchange="updateQty(${index}, this.value)" style="width: 60px; padding: 5px; text-align: center; border: 1px solid #ddd; border-radius: 4px;">
                        </td>
                        <td style="padding: 10px; text-align: right;">${priceHtml}</td>
                        <td style="padding: 10px; text-align: right; font-weight: bold;">$${rowTotal.toFixed(2)}</td>
                        <td style="padding: 10px; text-align: center;">
                            <button type="button" onclick="removeItem(${index})" style="color: #ef4444; border: none; background: #fee2e2; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;">&times;</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('attached_products_json').value = JSON.stringify(attachedItems);
                
                const discount = parseFloat(document.getElementById('quote_discount').value || 0);
                const finalTotal = Math.max(0, itemsTotal - discount);
                document.getElementById('quote_price').value = finalTotal.toFixed(2);
            }

            function updateQty(index, val) {
                if(val < 1) val = 1;
                attachedItems[index].qty = parseInt(val);
                renderQuoteItems();
            }
            function removeItem(index) { attachedItems.splice(index, 1); renderQuoteItems(); }
            function addCustomItem() {
                const name = document.getElementById('custom-name').value;
                const price = parseFloat(document.getElementById('custom-price').value);
                if(!name || isNaN(price)) return;
                attachedItems.push({ id: 'manual_' + Date.now(), name, price, base_price: price, qty: 1, is_manual: true });
                document.getElementById('custom-name').value = ''; document.getElementById('custom-price').value = ''; renderQuoteItems();
            }
            document.addEventListener('DOMContentLoaded', renderQuoteItems);
        </script>

        <!-- Totals -->
        <div style="margin-top: 20px; display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Descuento Extra ($)</label>
                <input type="number" step="0.01" name="quote_discount" id="quote_discount" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="0.00" oninput="renderQuoteItems()" value="<?= $selected_quote['discount'] ?? 0 ?>">
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">TOTAL FINAL ($)</label>
                <input type="number" step="0.01" name="quote_price" id="quote_price" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f1f5f9; font-weight: bold; color: #0f172a; font-size: 1.1rem;" readonly value="<?= $selected_quote['total'] ?? 0 ?>">
            </div>
        </div>

        <!-- Action Buttons (Improved) -->
        <div style="margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
             <button type="submit" name="save_quote_details" class="btn-action" style="background: #3b82f6; color: white; padding: 12px 25px; font-size: 1rem; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s;">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <button type="submit" name="update_status" value="1" onclick="document.getElementById('statusField').value='approved'" class="btn-action" style="background: #10b981; color: white; padding: 12px 25px; font-size: 1rem; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s;">
                <i class="fas fa-check-circle"></i> Aprobar y Crear Pedido
            </button>
            <button type="submit" name="update_status" value="1" onclick="document.getElementById('statusField').value='rejected'" class="btn-action" style="background: #ef4444; color: white; padding: 12px 25px; font-size: 1rem; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s;">
                <i class="fas fa-ban"></i> Rechazar
            </button>
            <input type="hidden" name="status" id="statusField">
        </div>
    </form>
</div>
<?php endif; ?>

</div>
</body>
</html>
