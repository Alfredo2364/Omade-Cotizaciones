<?php require_once '../includes/admin_header.php'; ?>

<?php
if ($_SESSION['role'] !== 'super_admin') {
    die("Acceso Denegado.");
}
?>

<div class="page-header">
    <h1><i class="fas fa-history" style="color: #6366f1;"></i> Registro de Actividad</h1>
</div>

<div class="card" style="padding: 0; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
    <div class="table-container" style="box-shadow: var(--card-shadow); border-radius: 12px; overflow-x: auto; border: 1px solid var(--border); -webkit-overflow-scrolling: touch;">
        <table style="width: 100%; min-width: 800px; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--surface-3); border-bottom: 2px solid var(--border);">
                    <th style="padding: 15px; text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Usuario</th>
                    <th style="padding: 15px; text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Acción</th>
                    <th style="padding: 15px; text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Detalles</th>
                    <th style="padding: 15px; text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Fecha y Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Only show admin activity
                $sql = "SELECT al.*, u.name 
                        FROM activity_logs al 
                        JOIN users u ON al.user_id = u.id 
                        WHERE u.role != 'client' 
                        ORDER BY al.created_at DESC LIMIT 50";
                $logs = $pdo->query($sql)->fetchAll();
                
                if(empty($logs)) {
                    echo "<tr><td colspan='4' style='text-align:center; padding: 20px; color: #64748b;'>No hay actividad registrada reciente.</td></tr>";
                }

                foreach ($logs as $log): 
                    // Friendly translations
                    $friendlyAction = $log['action'];
                    $badgeClass = 'badge-blue';
                    $icon = 'fa-info-circle';

                    switch($log['action']) {
                        case 'LOGIN':
                            $friendlyAction = 'Inicio de Sesión';
                            $badgeClass = 'badge-green';
                            $icon = 'fa-sign-in-alt';
                            break;
                        case 'CREATE_ADMIN':
                            $friendlyAction = 'Nuevo Admin';
                            $badgeClass = 'badge-purple';
                            $icon = 'fa-user-plus';
                            break;
                        case 'CREATE_CLIENT':
                            $friendlyAction = 'Nuevo Cliente';
                            $badgeClass = 'badge-indigo';
                            $icon = 'fa-user-tag';
                            break;
                         case 'LOGOUT':
                            $friendlyAction = 'Cierre de Sesión';
                            $badgeClass = 'badge-gray';
                            $icon = 'fa-sign-out-alt';
                            break;
                        default:
                            $friendlyAction = str_replace('_', ' ', ucfirst(strtolower($log['action'])));
                            $badgeClass = 'badge-blue';
                    }
                ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; background: rgba(59, 130, 246, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-weight: 700; font-size: 0.8rem; border: 1px solid var(--border);">
                                <?= strtoupper(substr($log['name'], 0, 1)) ?>
                            </div>
                            <span style="font-weight: 600; color: var(--text-color);"><?= htmlspecialchars($log['name']) ?></span>
                        </div>
                    </td>
                    <td style="padding: 15px;">
                        <span class="badge <?= $badgeClass ?>">
                            <i class="fas <?= $icon ?>"></i> <?= $friendlyAction ?>
                        </span>
                    </td>
                    <td style="padding: 15px; color: var(--text-color); font-size: 0.95rem;"><?= htmlspecialchars($log['details']) ?></td>
                    <td style="padding: 15px; color: var(--text-muted); font-size: 0.85rem;">
                        <i class="far fa-clock" style="margin-right: 5px;"></i>
                        <?= date('d/m/Y h:i A', strtotime($log['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Custom Badges for Logs */
    .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }
    
    .badge-blue { background: rgba(37, 99, 235, 0.1); color: #60a5fa; }
    .badge-green { background: rgba(22, 163, 74, 0.1); color: #4ade80; }
    .badge-purple { background: rgba(147, 51, 234, 0.1); color: #c084fc; }
    .badge-indigo { background: rgba(79, 70, 229, 0.1); color: #818cf8; }
    .badge-gray { background: rgba(148, 163, 184, 0.1); color: #94a3b8; }

    /* Enhance table row hover */
    tbody tr:hover { background-color: var(--surface-3); transition: background-color 0.2s; }
</style>

</body>
</html>
