<?php require_once '../includes/admin_header.php'; ?>

<?php
// Fetch Stats
$stats = [];
$stats['clients'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$stats['quotes'] = $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn();
$stats['products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$stats['orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
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

<!-- "Latest Movements" Section -->
<div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-top: 30px;">
    <div class="card-header-styled">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; background: #fff7ed; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ea580c;">
                <i class="fas fa-history"></i>
            </div>
            <h3 style="margin: 0; font-size: 1.1rem; color: #1f2937;">Últimos movimientos registrados</h3>
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
                                <div style="width: 24px; height: 24px; background: #e0e7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #4338ca; font-size: 0.7rem; font-weight: bold;">
                                    <?= strtoupper(substr($log['name'], 0, 1)) ?>
                                </div>
                                <span style="font-weight: 500; color: #374151;"><?= htmlspecialchars($log['name']) ?></span>
                            </div>
                        </td>
                        <td><span class="badge-action"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td style="color: #6b7280;"><?= htmlspecialchars($log['details']) ?></td>
                        <td style="color: #9ca3af; font-size: 0.85rem;"><?= date('d/m H:i', strtotime($log['created_at'])) ?></td>
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
        background: white;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid rgba(0,0,0,0.05);
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
        color: #6b7280;
        font-weight: 500;
    }
    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1.2;
        color: #111827;
    }

    /* Card Themes */
    .card-blue .stat-icon { background: #eff6ff; color: #3b82f6; }
    .card-green .stat-icon { background: #f0fdf4; color: #22c55e; }
    .card-purple .stat-icon { background: #faf5ff; color: #a855f7; }
    .card-orange .stat-icon { background: #fff7ed; color: #f97316; }

    /* Table Styles */
    .card-header-styled {
        background: #f9fafb;
        padding: 15px 25px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .table-container table thead th {
        background: #f3f4f6;
        color: #4b5563;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e5e7eb;
    }
    .table-container table tbody tr {
        border-bottom: 1px solid #f3f4f6;
    }
    .table-container table tbody tr:last-child {
        border-bottom: none;
    }
    .table-container table tbody tr:hover {
        background: #f9fafb;
    }

    .badge-action {
        background: #f3f4f6;
        color: #4b5563;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid #e5e7eb;
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
</style>

</body>
</html>
