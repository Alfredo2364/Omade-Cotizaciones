<?php require_once '../includes/admin_header.php'; ?>

<?php
// Fetch Stats
$stats = [];
$stats['clients'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$stats['quotes'] = $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn();
$stats['products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$stats['orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// --- IA Suggestions Logic ---
// 1. Sugerencia de compras (High velocity, low stock ratio)
$sugCompras = $pdo->query("SELECT p.id, p.product_code, p.name, p.stock, SUM(oi.quantity) as sold_7d
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY p.id
    HAVING sold_7d >= 2 AND (p.stock < (sold_7d * 1.5))
    ORDER BY sold_7d DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

// 2. Productos escasos (Stock <= 2)
$prodEscasos = $pdo->query("SELECT id, product_code, name, stock, price FROM products WHERE stock <= 2 ORDER BY stock ASC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

// 3. Productos Estancados (No sales in 30 days)
$prodEstancados = $pdo->query("SELECT p.id, p.product_code, p.name, p.stock, p.price 
    FROM products p 
    WHERE p.stock > 0 
    AND p.id NOT IN (
        SELECT oi.product_id 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    )
    ORDER BY p.stock ASC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1>Panel de Administración</h1>
</div>

<!-- Stats row with improved design -->
<div class="stats-grid">
    <a href="products.php" class="stat-card card-blue" style="text-decoration: none; color: inherit;">
        <div class="stat-icon"><i class="fas fa-boxes"></i></div>
        <div class="stat-info">
            <span class="stat-label">Productos</span>
            <span class="stat-number"><?= $stats['products'] ?></span>
        </div>
    </a>
    
    <a href="clients.php" class="stat-card card-green" style="text-decoration: none; color: inherit;">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <span class="stat-label">Clientes</span>
            <span class="stat-number"><?= $stats['clients'] ?></span>
        </div>
    </a>
    
    <a href="quotes.php" class="stat-card card-purple" style="text-decoration: none; color: inherit;">
        <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="stat-info">
            <span class="stat-label">Cotizaciones</span>
            <span class="stat-number"><?= $stats['quotes'] ?></span>
        </div>
    </a>
    
    <a href="orders.php" class="stat-card card-orange" style="text-decoration: none; color: inherit;">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-info">
            <span class="stat-label">Pedidos</span>
            <span class="stat-number"><?= $stats['orders'] ?></span>
        </div>
    </a>
</div>

<!-- "Asistente de Inventario" Section -->
<div style="display: flex; align-items: center; gap: 10px; margin-top: 30px; margin-bottom: 15px;">
    <div style="width: 32px; height: 32px; background: rgba(139, 92, 246, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #8b5cf6;">
        <i class="fas fa-brain"></i>
    </div>
    <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-color);">Asistente Inteligente</h3>
</div>

<div class="ai-buttons-grid">
    <button class="ai-btn ai-disabled" disabled>
        <i class="fas fa-shopping-cart"></i> 
        <span>Próximamente...</span>
    </button>
    
    <button class="ai-btn ai-disabled" disabled>
        <i class="fas fa-exclamation-triangle"></i> 
        <span>Próximamente...</span>
    </button>
    
    <button class="ai-btn ai-disabled" disabled>
        <i class="fas fa-box-open"></i> 
        <span>Próximamente...</span>
    </button>
    
    <button class="ai-btn ai-disabled" disabled>
        <i class="fas fa-rocket"></i> 
        <span>Próximamente...</span>
    </button>
</div>

<!-- AI MODALS CONTAINER -->
<div id="ai-modal-overlay" class="ai-modal-overlay" onclick="closeAIModal()">
    <div class="ai-modal-content" onclick="event.stopPropagation()">
        <button class="close-ai-modal" onclick="closeAIModal()"><i class="fas fa-times"></i></button>
        
        <!-- Modal: Compras -->
        <div id="modal-compras" class="ai-view" style="display:none;">
            <h2><i class="fas fa-shopping-cart" style="color:#3b82f6;"></i> Sugerencia de Compras</h2>
            <p style="color:var(--text-muted); margin-bottom:20px;">Basado en las ventas de los últimos 7 días, sugerimos reabastecer estos productos porque su inventario no es suficiente para cubrir la tendencia actual.</p>
            <?php if(count($sugCompras) > 0): ?>
                <div class="table-container" style="border: 1px solid var(--border); box-shadow: none; width: 100%; overflow-x: auto; border-radius: 10px;">
                    <table style="min-width: 600px; width: 100%;">
                        <thead><tr><th>Código</th><th>Producto</th><th style="text-align:center;">7d</th><th style="text-align:center;">Stock</th></tr></thead>
                        <tbody>
                            <?php foreach($sugCompras as $row): ?>
                            <tr>
                                <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><span class="badge-action" style="display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis;"><?= !empty($row['product_code']) ? htmlspecialchars($row['product_code']) : 'N/A' ?></span></td>
                                <td style="font-size: 0.9rem;" title="<?= htmlspecialchars($row['name']) ?>">
                                    <div style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; word-break: break-word;">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </div>
                                </td>
                                <td style="color:#10b981; font-weight:bold; text-align:center; font-size: 0.9rem;"><i class="fas fa-arrow-up" style="font-size:0.75rem;"></i> <?= $row['sold_7d'] ?></td>
                                <td style="color:#ef4444; font-weight:bold; text-align:center; font-size: 0.9rem;"><?= $row['stock'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding:30px; text-align:center; color:var(--text-muted);"><i class="fas fa-check-circle" style="font-size:3rem; color:#10b981; opacity:0.5; margin-bottom:10px; display:block;"></i>No hay sugerencias en este momento. ¡El inventario está estable!</div>
            <?php endif; ?>
        </div>

        <!-- Modal: Escasos -->
        <div id="modal-escasos" class="ai-view" style="display:none;">
            <h2><i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> Productos Escasos</h2>
            <p style="color:var(--text-muted); margin-bottom:20px;">Atención: Estos productos tienen 2 unidades o menos en inventario.</p>
            <?php if(count($prodEscasos) > 0): ?>
                <div class="table-container" style="border: 1px solid var(--border); box-shadow: none; width: 100%; overflow-x: auto; border-radius: 10px;">
                    <table style="min-width: 500px; width: 100%;">
                        <thead><tr><th>Código</th><th>Producto</th><th style="text-align:center;">Stock</th></tr></thead>
                        <tbody>
                            <?php foreach($prodEscasos as $row): ?>
                            <tr>
                                <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><span class="badge-action" style="display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis;"><?= !empty($row['product_code']) ? htmlspecialchars($row['product_code']) : 'N/A' ?></span></td>
                                <td style="font-size: 0.9rem;" title="<?= htmlspecialchars($row['name']) ?>">
                                    <div style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; word-break: break-word;">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </div>
                                </td>
                                <td style="text-align:center;"><span style="background:#fef2f2; color:#ef4444; padding:2px 6px; border-radius:6px; font-weight:bold; border:1px solid #fca5a5; font-size: 0.85rem;"><?= $row['stock'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding:30px; text-align:center; color:var(--text-muted);"><i class="fas fa-boxes" style="font-size:3rem; color:#10b981; opacity:0.5; margin-bottom:10px; display:block;"></i>No hay productos críticos. Todos tienen buen stock.</div>
            <?php endif; ?>
        </div>

        <!-- Modal: Estancados -->
        <div id="modal-estancados" class="ai-view" style="display:none;">
            <h2><i class="fas fa-box-open" style="color:#8b5cf6;"></i> Productos Estancados</h2>
            <p style="color:var(--text-muted); margin-bottom:20px;">Estos productos tienen stock en almacén, pero no han registrado ninguna venta en los últimos 30 días.</p>
            <?php if(count($prodEstancados) > 0): ?>
                <div class="table-container" style="border: 1px solid var(--border); box-shadow: none; width: 100%; overflow-x: auto; border-radius: 10px;">
                    <table style="min-width: 500px; width: 100%;">
                        <thead><tr><th>Código</th><th>Producto</th><th style="text-align:center;">Stock</th></tr></thead>
                        <tbody>
                            <?php foreach($prodEstancados as $row): ?>
                            <tr>
                                <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><span class="badge-action" style="display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis;"><?= !empty($row['product_code']) ? htmlspecialchars($row['product_code']) : 'N/A' ?></span></td>
                                <td style="font-size: 0.9rem;" title="<?= htmlspecialchars($row['name']) ?>">
                                    <div style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; word-break: break-word;">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </div>
                                </td>
                                <td style="font-weight:bold; color:var(--text-color); text-align:center; font-size: 0.9rem;"><?= $row['stock'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding:30px; text-align:center; color:var(--text-muted);"><i class="fas fa-star" style="font-size:3rem; color:#f59e0b; opacity:0.5; margin-bottom:10px; display:block;"></i>¡Excelente! Tu inventario se está moviendo bien, ningún producto se está rezagando.</div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- "Latest Movements" Section -->
<div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-top: 30px;">
    <div class="card-header-styled">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                <i class="fas fa-history"></i>
            </div>
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-color);">Últimos movimientos registrados</h3>
        </div>
    </div>
    
    <div class="table-container" style="box-shadow: none; border-radius: 0;">
        <table style="margin: 0;">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $logs = $pdo->query("SELECT al.*, u.name FROM activity_logs al JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10")->fetchAll();
                if (count($logs) > 0) {
                    foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 24px; height: 24px; background: rgba(59, 130, 246, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-size: 0.75rem; font-weight: 700; border: 1px solid var(--border);">
                                    <?= strtoupper(substr($log['name'], 0, 1)) ?>
                                </div>
                                <span style="font-weight: 600; color: var(--text-color);"><?= htmlspecialchars($log['name']) ?></span>
                            </div>
                        </td>
                        <td><span class="badge-action"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td style="color: var(--text-muted);"><?= htmlspecialchars($log['details']) ?></td>
                        <td style="color: var(--text-muted); opacity: 0.7; font-size: 0.85rem;"><?= date('d/m H:i', strtotime($log['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; 
                } else {
                    echo "<tr><td colspan='4' style='text-align: center; padding: 40px; color: #9ca3af;'>No hay actividad reciente.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--surface-2);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: var(--card-shadow);
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid var(--border);
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-info {
        display: flex;
        flex-direction: column;
    }
    .stat-label {
        font-size: 0.9rem;
        color: var(--text-muted);
        font-weight: 500;
    }
    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--text-color);
    }

    /* Card Themes - Using translucent RGBA for perfect theme integration */
    .card-blue .stat-icon { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
    .card-green .stat-icon { background: rgba(34, 197, 94, 0.12); color: #22c55e; }
    .card-purple .stat-icon { background: rgba(168, 85, 247, 0.12); color: #a855f7; }
    .card-orange .stat-icon { background: rgba(249, 115, 22, 0.12); color: #f97316; }

    /* Table Styles */
    .card-header-styled {
        background: var(--surface-3);
        padding: 15px 25px;
        border-bottom: 1px solid var(--border);
    }
    
    .table-container table thead th {
        background: var(--surface-3);
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--border);
    }
    .table-container table tbody tr {
        border-bottom: 1px solid var(--border);
    }
    .table-container table tbody tr:last-child {
        border-bottom: none;
    }
    .table-container table tbody tr:hover {
        background: var(--surface-3);
    }

    .badge-action {
        background: var(--surface-3);
        color: var(--text-color);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid var(--border);
    }

    /* Mobile - Tablet */
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .stat-card { padding: 16px; }
        .stat-number { font-size: 1.4rem; }
    }
    /* Mobile - Phone */
    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
        .card-header-styled { padding: 12px 16px; }
        .card-header-styled h3 { font-size: 0.95rem; }
    }
    
    /* AI Buttons System */
    .ai-buttons-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .ai-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border: none;
        border-radius: 12px;
        font-family: inherit;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        text-align: left;
    }
    
    .ai-btn i { font-size: 1.4rem; }
    
    /* Cool Gradients and Colors */
    .ai-compras { background: linear-gradient(135deg, #1d4ed8, #3b82f6); color: white; }
    .ai-compras:hover { box-shadow: 0 10px 15px -3px rgba(59,130,246,0.4); transform: translateY(-3px); }
    
    .ai-escasos { background: linear-gradient(135deg, #b91c1c, #ef4444); color: white; }
    .ai-escasos:hover { box-shadow: 0 10px 15px -3px rgba(239,68,68,0.4); transform: translateY(-3px); }
    
    .ai-estancados { background: linear-gradient(135deg, #6d28d9, #8b5cf6); color: white; }
    .ai-estancados:hover { box-shadow: 0 10px 15px -3px rgba(139,92,246,0.4); transform: translateY(-3px); }
    
    .ai-disabled { background: var(--surface-3); color: var(--text-muted); cursor: not-allowed; opacity: 0.6; box-shadow: none; border: 1px dashed var(--border); }
    .ai-disabled:hover { transform: none; box-shadow: none; }

    /* Alert Badge styling resembling user's requested icon */
    .alert-badge {
        margin-left: auto;
        background: #ef4444; 
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        border: 2px solid white;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.6);
        animation: pulseAlert 2s infinite;
    }
    
    @keyframes pulseAlert {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.8); }
        70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    /* AI Modals */
    .ai-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px);
        display: none; align-items: center; justify-content: center;
        z-index: 999999;
        opacity: 0; transition: opacity 0.3s;
    }
    
    .ai-modal-overlay.active { display: flex; opacity: 1; }
    
    .ai-modal-content {
        background: var(--surface-1);
        width: 90%; max-width: 800px;
        border-radius: 16px; padding: 30px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
        max-height: 85vh; overflow-y: auto;
        position: relative;
        transform: translateY(20px); transition: transform 0.3s;
    }
    
    .ai-modal-overlay.active .ai-modal-content { transform: translateY(0); }
    
    .close-ai-modal {
        position: absolute; top: 20px; right: 20px;
        background: var(--surface-3); border: none;
        width: 32px; height: 32px; border-radius: 50%;
        color: var(--text-color); font-size: 1.1rem;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    
    .close-ai-modal:hover { background: #ef4444; color: white; }
    
    .ai-view h2 { margin-top: 0; font-size: 1.4rem; color: var(--text-color); display: flex; align-items: center; gap: 10px; }

</style>

<script>
    function openAIModal(modalId) {
        document.querySelectorAll('.ai-view').forEach(view => view.style.display = 'none');
        document.getElementById(modalId).style.display = 'block';
        
        // Disable background scroll
        document.body.style.overflow = 'hidden';
        
        const overlay = document.getElementById('ai-modal-overlay');
        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('active'), 10);
    }
    
    function closeAIModal() {
        const overlay = document.getElementById('ai-modal-overlay');
        overlay.classList.remove('active');
        
        // Re-enable background scroll after transition
        setTimeout(() => {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }
</script>

</body>
</html>
