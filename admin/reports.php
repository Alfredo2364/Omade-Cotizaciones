<?php require_once '../includes/admin_header.php'; ?>
<?php if (!hasPermission($pdo, $_SESSION['user_id'], 'reports')) die("Acceso Denegado"); ?>

<div class="page-header">
    <h1><i class="fas fa-chart-line" style="color: #6366f1;"></i> Reportes del Sistema</h1>
</div>

<div class="card">
    <form method="GET" class="report-filter-form">
        <div class="filter-group">
            <label>Desde:</label>
            <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? date('Y-m-01')) ?>" onclick="this.showPicker()" style="cursor: pointer;">
        </div>
        <div class="filter-group">
            <label>Hasta:</label>
            <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? date('Y-m-d')) ?>" onclick="this.showPicker()" style="cursor: pointer;">
        </div>
        <div class="filter-btn-container">
            <button type="submit" class="btn-action btn-generate"><i class="fas fa-calendar-alt"></i> Generar Reporte</button>
        </div>
    </form>

    <style>
        .report-filter-form {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-generate {
            background: #0f172a;
            color: white;
            padding: 12px 25px;
            height: 42px;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        
        @media (max-width: 600px) {
            .report-filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-generate {
                width: 100%;
            }
        }
    </style>

    <?php
    // Filter Dates
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-d');

    // 1. Total Sales & Orders
    $sql = "SELECT SUM(total) as total_sales, COUNT(*) as total_orders FROM orders WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$from, $to]);
    $stats = $stmt->fetch();
    $totalSales = $stats['total_sales'] ?? 0;
    $totalOrders = $stats['total_orders'] ?? 0;

    // 2. Active Clients (Overall)
    $clientStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'");
    $activeClients = $clientStmt->fetchColumn();

    // 3. Top 5 Best Selling Products
    $topProdSql = "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue 
                   FROM order_items oi 
                   JOIN products p ON oi.product_id = p.id 
                   JOIN orders o ON oi.order_id = o.id 
                   WHERE DATE(o.created_at) BETWEEN ? AND ? 
                   GROUP BY p.id 
                   ORDER BY total_sold DESC 
                   LIMIT 5";
    $topProdStmt = $pdo->prepare($topProdSql);
    $topProdStmt->execute([$from, $to]);
    $topProducts = $topProdStmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for Chart.js
    $prodLabels = [];
    $prodData = [];
    foreach($topProducts as $p) {
        $prodLabels[] = $p['name'];
        $prodData[] = $p['total_sold'];
    }

    // 4. Daily Sales History
    $salesHistSql = "SELECT DATE(created_at) as sale_date, SUM(total) as daily_total 
                     FROM orders 
                     WHERE DATE(created_at) BETWEEN ? AND ? 
                     GROUP BY DATE(created_at) 
                     ORDER BY sale_date ASC";
    $salesHistStmt = $pdo->prepare($salesHistSql);
    $salesHistStmt->execute([$from, $to]);
    $salesHistory = $salesHistStmt->fetchAll(PDO::FETCH_ASSOC);

    $dateLabels = [];
    $salesData = [];
    foreach($salesHistory as $day) {
        $dateLabels[] = date('d M', strtotime($day['sale_date']));
        $salesData[] = $day['daily_total'];
    }
    ?>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <div class="report-card card-sales">
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="label">Ventas Totales</div>
            <div class="value">$<?= number_format($totalSales, 2) ?></div>
        </div>
        <div class="report-card card-orders">
            <div class="icon"><i class="fas fa-shopping-bag"></i></div>
            <div class="label">Pedidos</div>
            <div class="value"><?= $totalOrders ?></div>
        </div>
        <div class="report-card card-clients">
            <div class="icon"><i class="fas fa-users"></i></div>
            <div class="label">Clientes Activos</div>
            <div class="value"><?= $activeClients ?></div>
        </div>
    </div>
    
    <!-- Charts Container -->
    <div class="charts-container">
        <!-- Top Products -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-trophy" style="color: #f59e0b;"></i> Top 5 Productos Más Vendidos</h3>
            </div>
            <div class="chart-body">
                <?php if(!empty($topProducts)): ?>
                    <canvas id="topProductsChart"></canvas>
                <?php else: ?>
                    <div class="no-data">No hay datos de ventas en este periodo.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sales Trend -->
         <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-area" style="color: #3b82f6;"></i> Historial de Ventas</h3>
            </div>
            <div class="chart-body">
                 <?php if(!empty($salesHistory)): ?>
                    <canvas id="salesTrendChart"></canvas>
                <?php else: ?>
                    <div class="no-data">No hay movimientos en este periodo.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Details Section -->
    <div class="details-section">
        <div class="detail-card">
            <h3>Detalle de Mejores Productos</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Unidades</th>
                        <th>Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($topProducts as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td class="text-center"><?= $p['total_sold'] ?></td>
                        <td class="text-right">$<?= number_format($p['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($topProducts)) echo "<tr><td colspan='3' class='text-center'>Sin datos</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const colors = {
        primary: '#3b82f6',
        secondary: '#6366f1',
        accent: '#f59e0b',
        success: '#10b981',
        grid: '#f1f5f9',
        text: '#64748b'
    };

    // Top Products Chart
    <?php if(!empty($topProducts)): ?>
    const ctxProd = document.getElementById('topProductsChart').getContext('2d');
    new Chart(ctxProd, {
        type: 'bar',
        data: {
            labels: <?= json_encode($prodLabels) ?>,
            datasets: [{
                label: 'Unidades',
                data: <?= json_encode($prodData) ?>,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(99, 102, 241, 0.7)',
                    'rgba(236, 72, 153, 0.7)'
                ],
                borderColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#6366f1', '#ec4899'
                ],
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid },
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
    <?php endif; ?>

    // Sales Trend Chart
    <?php if(!empty($salesHistory)): ?>
    const ctxTrend = document.getElementById('salesTrendChart').getContext('2d');
    
    let gradient = ctxTrend.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?= json_encode($dateLabels) ?>,
            datasets: [{
                label: 'Ventas ($)',
                data: <?= json_encode($salesData) ?>,
                borderColor: colors.primary,
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: colors.primary,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
    <?php endif; ?>
</script>

<style>
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .charts-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .report-card, .chart-card, .detail-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9;
        height: 100%;
    }

    .card-sales .value { color: #10b981; }
    .card-orders .value { color: #3b82f6; }
    .card-clients .value { color: #8b5cf6; }
    
    .report-card .icon { font-size: 1.5rem; color: #94a3b8; margin-bottom: 10px; }
    .report-card .label { font-size: 0.9rem; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
    .report-card .value { font-size: 1.8rem; font-weight: 800; }

    .chart-header {
        margin-bottom: 15px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
    }
    .chart-header h3 { margin: 0; font-size: 1.1rem; color: #1e293b; }
    .chart-body {
        position: relative;
        height: 300px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .no-data {
        color: #94a3b8;
        font-style: italic;
    }

    .report-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .report-table th { text-align: left; padding: 10px; background: #f8fafc; color: #64748b; font-size: 0.8rem; text-transform: uppercase; }
    .report-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }

    @media (max-width: 768px) {
        .charts-container { grid-template-columns: 1fr; }
    }
</style>

</body>
</html>
