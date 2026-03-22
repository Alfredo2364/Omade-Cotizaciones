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
    <div class="table-container" style="box-shadow: none; border-radius: 12px; overflow: hidden;">
        <table>
            <thead style="background: #0056b3; color: white;">
                <tr>
                    <th style="padding: 15px; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Usuario</th>
                    <th style="padding: 15px; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Acción</th>
                    <th style="padding: 15px; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Detalles</th>
                    <th style="padding: 15px; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Fecha y Hora</th>
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
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: bold; font-size: 0.8rem;">
                                <?= strtoupper(substr($log['name'], 0, 1)) ?>
                            </div>
                            <span style="font-weight: 500; color: #334155;"><?= htmlspecialchars($log['name']) ?></span>
                        </div>
                    </td>
                    <td style="padding: 15px;">
                        <span class="badge <?= $badgeClass ?>">
                            <i class="fas <?= $icon ?>"></i> <?= $friendlyAction ?>
                        </span>
                    </td>
                    <td style="padding: 15px; color: #475569; font-size: 0.95rem;"><?= htmlspecialchars($log['details']) ?></td>
                    <td style="padding: 15px; color: #64748b; font-size: 0.85rem;">
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
    
    .badge-blue { background: #eff6ff; color: #2563eb; }
    .badge-green { background: #f0fdf4; color: #16a34a; }
    .badge-purple { background: #f3e8ff; color: #9333ea; }
    .badge-indigo { background: #e0e7ff; color: #4f46e5; }
    .badge-gray { background: #f8fafc; color: #64748b; }

    /* Enhance table row hover */
    tbody tr:hover { background-color: #f8fafc; transition: background-color 0.2s; }
</style>

</body>
</html>
