<?php require_once '../includes/admin_header.php'; ?>
<?php if (!hasPermission($pdo, $_SESSION['user_id'], 'orders')) die("Acceso Denegado"); ?>

<div class="page-header">
    <h1><i class="fas fa-history" style="color: #6366f1;"></i> Historial de Ventas</h1>
</div>

<!-- Filters Card -->
<div class="card" style="margin-bottom: 25px; padding: 25px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
    <form method="GET" class="filters-form">
        <div class="filter-group grow">
            <label>Buscar Pedido</label>
            <div class="input-icon">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Folio (#00123) o Cliente..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
        </div>
        
        <div class="filter-group">
            <label>Fecha</label>
            <div class="input-icon">
                <i class="fas fa-calendar-alt"></i>
                <input type="date" name="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
            </div>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <?php if(isset($_GET['search']) || isset($_GET['date'])): ?>
                <a href="orders.php" class="btn-clear"><i class="fas fa-undo"></i> Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Orders Table Card -->
<div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
    <div class="table-container">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Cotización</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // --- Logic: Search, Filter, Pagination ---
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $perPage = 15;
                $offset = ($page - 1) * $perPage;

                $search = $_GET['search'] ?? '';
                $date = $_GET['date'] ?? '';

                $whereClauses = [];
                $params = [];

                if (!empty($search)) {
                    $searchClean = str_replace('#', '', $search);
                    $whereClauses[] = "(o.id LIKE ? OR o.client_name LIKE ?)";
                    $params[] = "%$searchClean%";
                    $params[] = "%$search%";
                }

                if (!empty($date)) {
                    $whereClauses[] = "DATE(o.created_at) = ?";
                    $params[] = $date;
                }

                $whereSQL = "";
                if (count($whereClauses) > 0) {
                    $whereSQL = "WHERE " . implode(' AND ', $whereClauses);
                }

                // Count Total
                $countSql = "SELECT COUNT(*) FROM orders o $whereSQL";
                $countStmt = $pdo->prepare($countSql);
                $countStmt->execute($params);
                $totalOrders = $countStmt->fetchColumn();
                $totalPages = ceil($totalOrders / $perPage);

                // Fetch Data
                $sql = "SELECT o.*, u.name as seller_name 
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id 
                        $whereSQL 
                        ORDER BY o.created_at DESC 
                        LIMIT $perPage OFFSET $offset";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $orders = $stmt->fetchAll();
                
                if (empty($orders)) {
                    echo "<tr><td colspan='8' class='empty-state'><i class='fas fa-search'></i><p>No se encontraron pedidos</p></td></tr>";
                }

                foreach ($orders as $o): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 600; color: #64748b;">
                        #<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?>
                    </td>
                    <td>
                        <?php if(!empty($o['quote_id'])): ?>
                            <a href="quotes.php?view=<?= $o['quote_id'] ?>#details-card" class="link-quote">
                                #QT-<?= $o['quote_id'] ?>
                            </a>
                        <?php else: ?>
                            <span style="color: #cbd5e1;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($o['client_name']) ?></td>
                    <td style="color: #64748b; font-size: 0.9rem;"><?= htmlspecialchars($o['seller_name'] ?? 'Sistema') ?></td>
                    <td style="color: #64748b; font-size: 0.85rem;">
                        <i class="far fa-clock" style="margin-right: 4px; opacity: 0.7;"></i>
                        <?= date('d M, Y', strtotime($o['created_at'])) ?>
                        <span style="color: #94a3b8; font-size: 0.75rem; margin-left: 3px;"><?= date('H:i', strtotime($o['created_at'])) ?></span>
                    </td>
                    <td>
                        <span class="badge-success"><i class="fas fa-check"></i> Completado</span>
                    </td>
                    <td style="font-weight: 700; color: #059669; font-size: 1rem;">$<?= number_format($o['total'], 2) ?></td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 8px;">
                            <a href="print_ticket.php?type=order&id=<?= $o['id'] ?>" target="_blank" class="btn-icon" title="Imprimir Factura">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                            <a href="print_receipt.php?id=<?= $o['id'] ?>" target="_blank" class="btn-icon" title="Imprimir Ticket">
                                <i class="fas fa-receipt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-container">
        <span class="pagination-info">Mostrando <?=count($orders)?> de <?=$totalOrders?></span>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&date=<?= $date ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date=<?= $date ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&date=<?= $date ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    /* Premium Design Styles */
    .filters-form { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; }
    .filter-group { display: flex; flex-direction: column; gap: 8px; }
    .filter-group label { font-weight: 600; color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .grow { flex: 1; min-width: 250px; }
    
    .input-icon { position: relative; }
    .input-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .input-icon input { 
        width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #e2e8f0; 
        border-radius: 10px; outline: none; transition: 0.2s; background: #f8fafc; color: #1e293b;
    }
    .input-icon input:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

    .filter-actions { display: flex; gap: 10px; margin-left: auto; }
    .btn-filter { 
        background: #0f172a; color: white; border: none; padding: 0 25px; 
        height: 45px; border-radius: 10px; font-weight: 600; cursor: pointer; 
        display: flex; align-items: center; gap: 8px; transition: 0.2s;
    }
    .btn-filter:hover { background: #1e293b; transform: translateY(-1px); }
    
    .btn-clear {
        background: white; border: 1px solid #e2e8f0; color: #64748b; padding: 0 20px;
        height: 45px; border-radius: 10px; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s;
    }
    .btn-clear:hover { background: #f1f5f9; color: #ef4444; border-color: #fee2e2; }

    /* Table Styles */
    .table-premium { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-premium thead th { 
        background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; 
        font-size: 0.75rem; letter-spacing: 0.5px; padding: 15px 20px; 
        text-align: left; border-bottom: 1px solid #e2e8f0; 
    }
    .table-premium tbody tr { transition: background 0.15s; }
    .table-premium tbody tr:hover { background: #f8fafc; }
    .table-premium td { 
        padding: 15px 20px; border-bottom: 1px solid #f1f5f9; 
        vertical-align: middle; color: #334155; 
    }
    
    .link-quote { 
        color: #3b82f6; text-decoration: none; font-weight: 500; 
        padding: 2px 8px; background: #eff6ff; border-radius: 4px; 
        transition: 0.2s; font-size: 0.85rem;
    }
    .link-quote:hover { background: #dbeafe; text-decoration: underline; }

    .badge-success { 
        background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; 
        padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; 
        font-weight: 600; display: inline-flex; align-items: center; gap: 5px; 
    }

    .btn-icon { 
        width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; 
        border-radius: 8px; text-decoration: none; transition: all 0.2s; 
        color: #64748b; background: transparent;
    }
    .btn-icon:hover { background: #eff6ff; color: #3b82f6; transform: translateY(-2px); }

    .empty-state { text-align: center; padding: 50px; color: #94a3b8; }
    .empty-state i { font-size: 2.5rem; margin-bottom: 15px; opacity: 0.3; display: block; }

    /* Pagination */
    .pagination-container { 
        padding: 20px 25px; border-top: 1px solid #f1f5f9; background: #fff; 
        display: flex; justify-content: space-between; align-items: center; 
    }
    .pagination-info { font-size: 0.85rem; color: #94a3b8; font-weight: 500; }
    .pagination { display: flex; gap: 5px; }
    .pagination a { 
        width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; 
        border-radius: 8px; border: 1px solid #e2e8f0; color: #64748b; 
        text-decoration: none; transition: 0.2s; font-size: 0.9rem; 
    }
    .pagination a:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
    .pagination a.active { background: #0f172a; color: white; border-color: #0f172a; }

    @media (max-width: 1024px) {
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    }
    @media (max-width: 768px) {
        .filters-form { flex-direction: column; align-items: stretch; }
        .filter-actions { margin-left: 0; }
        .pagination-container { flex-direction: column; gap: 15px; }
    }
    @media (max-width: 480px) {
        table th, table td { font-size: 0.8rem; padding: 8px 10px; }
        .pagination { flex-wrap: wrap; justify-content: center; }
    }
</style>

</body>
</html>
