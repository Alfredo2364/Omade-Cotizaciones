            <!-- ORDERS TAB -->
            <div class="page-header" style="margin-bottom: 30px;">
                <h1 style="font-size: 1.8rem; color: var(--primary);">Mis Pedidos</h1>
                <p style="color: var(--text-light);">Consulta el estado y detalle de tus compras recientes.</p>
            </div>
            
            <div class="premium-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="margin: 0; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; color: var(--primary);">
                        <i class="fas fa-shopping-bag" style="color: var(--secondary);"></i> Historial de Compras
                    </h3>
                </div>
                
                <div class="premium-table-container">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>ID Pedido</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $my_orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
                            $my_orders->execute([$_SESSION['user_id']]);
                            $orders_list = $my_orders->fetchAll();

                            if(empty($orders_list)) {
                                echo "<tr><td colspan='5'><div class='empty-state'><i class='fas fa-box-open'></i><p>No tienes pedidos registrados aún.</p></div></td></tr>";
                            }
                            foreach ($orders_list as $o):
                            ?>
                            <tr>
                                <td style="font-weight: 600;">#<?= $o['id'] ?></td>
                                <td><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                                <td style="font-weight: 700; color: var(--text-main);">$<?= number_format($o['total'], 2) ?></td>
                                <td><span class="status-badge status-approved">Completado</span></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn-action-primary" onclick="openTrackerModal('<?= $o['id'] ?>', 'completed', 'order')" style="padding: 6px 12px; font-size: 0.85rem; border: none; cursor: pointer; background: #10b981;">
                                            <i class="fas fa-map-marker-alt"></i> Rastreo
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
