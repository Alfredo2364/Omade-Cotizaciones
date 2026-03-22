<?php require_once '../includes/admin_header.php'; ?>
<?php if ($_SESSION['role'] !== 'super_admin') die("Acceso Denegado"); ?>

<div class="page-header">
    <h1>Gestión de Administradores</h1>
    <button onclick="toggleAdminForm()" class="btn-new-admin">
        <i class="fas fa-user-plus"></i> Nuevo Administrador
    </button>
</div>

<?php
// Handle Create Admin (same logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    if (!isset($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Error de validación CSRF");
    }
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // Process permissions
    $permissions = [];
    if($role === 'super_admin') {
        $permissions[] = 'all';
    } else {
        if(isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $permissions = $_POST['permissions'];
        }
    }
    $permissionsJson = json_encode($permissions);

    // Check if email exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo "<script>showToast('El correo ya existe', 'error');</script>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, permissions) VALUES (?, ?, ?, ?, ?)");
        if($stmt->execute([$name, $email, $password, $role, $permissionsJson])) {
             logActivity($pdo, $_SESSION['user_id'], 'CREATE_ADMIN', "Creó administrador: $email");
             $_SESSION['flash'] = ['message' => 'Administrador creado con éxito', 'type' => 'success'];
             echo "<script>window.location.href='admins.php';</script>";
        }
    }
}
?>

<!-- Animated Form Container -->
<div id="adminFormContainer" class="admin-form-container <?= isset($_POST['create_admin']) ? 'open' : '' ?>">
    <div class="admin-form-card">
        <div class="form-header">
            <h3><i class="fas fa-user-shield"></i> Registrar Nuevo Administrador</h3>
            <button onclick="toggleAdminForm()" class="close-btn"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" required placeholder="Ej: Pedro López">
                    </div>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" required placeholder="pedro@empresa.com">
                    </div>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                </div>
                <div class="form-group">
                    <label>Rol de Usuario</label>
                    <div class="input-icon">
                        <i class="fas fa-user-tag"></i>
                        <select name="role" id="roleSelect" onchange="togglePermissions()">
                            <option value="admin">Administrador (Limitado)</option>
                            <option value="super_admin">Super Admin (Acceso Total)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="permissionsContainer" class="permissions-section">
                <label class="perm-title">Permisos de Acceso / Zonas de Trabajo</label>
                <div class="permissions-grid">
                    <label class="perm-card">
                        <input type="checkbox" name="permissions[]" value="pos"> 
                        <span class="perm-content"><i class="fas fa-cash-register"></i> Punto de Venta</span>
                    </label>
                    <label class="perm-card">
                        <input type="checkbox" name="permissions[]" value="products"> 
                        <span class="perm-content"><i class="fas fa-boxes"></i> Inventario</span>
                    </label>
                    <label class="perm-card">
                        <input type="checkbox" name="permissions[]" value="quotes"> 
                        <span class="perm-content"><i class="fas fa-file-invoice-dollar"></i> Cotizaciones</span>
                    </label>
                    <label class="perm-card">
                        <input type="checkbox" name="permissions[]" value="orders"> 
                        <span class="perm-content"><i class="fas fa-shopping-bag"></i> Ventas</span>
                    </label>
                    <label class="perm-card">
                        <input type="checkbox" name="permissions[]" value="clients"> 
                        <span class="perm-content"><i class="fas fa-users"></i> Clientes</span>
                    </label>
                    <label class="perm-card">
                        <input type="checkbox" name="permissions[]" value="reports"> 
                        <span class="perm-content"><i class="fas fa-chart-line"></i> Reportes</span>
                    </label>
                    <label class="perm-card">
                        <input type="checkbox" name="permissions[]" value="support"> 
                        <span class="perm-content"><i class="fas fa-headset"></i> Soporte</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" onclick="toggleAdminForm()" class="btn-cancel">Cancelar</button>
                <button type="submit" name="create_admin" class="btn-submit">Guardar Administrador</button>
            </div>
        </form>
    </div>
</div>

<!-- Administrators List -->
<div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
    <div class="card-header-styled">
        <h3><i class="fas fa-users-cog"></i> Lista de Administradores</h3>
    </div>
    <div class="table-container" style="box-shadow: none; border-radius: 0; overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table style="width: 100%; min-width: 600px;">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $admins = $pdo->query("SELECT * FROM users WHERE role IN ('admin', 'super_admin') ORDER BY created_at DESC")->fetchAll();
                foreach ($admins as $admin): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 35px; height: 35px; background: rgba(59, 130, 246, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-weight: 700; border: 1px solid var(--border);">
                                <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                            </div>
                            <span style="font-weight: 600; color: var(--text-color);"><?= htmlspecialchars($admin['name']) ?></span>
                        </div>
                    </td>
                    <td style="color: var(--text-muted);"><?= htmlspecialchars($admin['email']) ?></td>
                    <td>
                        <?php if($admin['role'] === 'super_admin'): ?>
                            <span class="badge badge-purple" style="white-space: nowrap;">Super Admin</span>
                        <?php else: ?>
                            <span class="badge badge-blue" style="white-space: nowrap;">Admin</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-green" style="white-space: nowrap;">Activo</span></td>
                    <td style="color: var(--text-muted); font-size: 0.9rem;"><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
                    <td>
                        <?php if($admin['id'] != $_SESSION['user_id']): // Prevent self-delete logic placeholder ?> 
                             <button class="btn-icon delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleAdminForm() {
    const container = document.getElementById('adminFormContainer');
    container.classList.toggle('open');
}

function togglePermissions() {
    const role = document.getElementById('roleSelect').value;
    const container = document.getElementById('permissionsContainer');
    if(role === 'super_admin') {
        container.style.opacity = '0.5';
        container.style.pointerEvents = 'none';
    } else {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
    }
}
</script>

<style>
    /* Admin Page Specific Styles */
    .btn-new-admin {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 30px;
        cursor: pointer;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
        transition: transform 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-new-admin:hover { transform: translateY(-2px); }

    .admin-form-container {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 20px;
    }
    .admin-form-container.open {
        max-height: 1000px;
        opacity: 1;
        margin-bottom: 30px;
    }

    .admin-form-card {
        background: var(--surface-2);
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        padding: 30px;
        border: 1px solid var(--border);
        position: relative;
    }
    .admin-form-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, #4f46e5, #ec4899);
    }

    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 15px;
    }
    .form-header h3 { margin: 0; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group label {
        display: block; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; font-size: 0.9rem;
    }
    .input-icon { position: relative; }
    .input-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
    .input-icon input, .input-icon select {
        width: 100%; padding: 10px 10px 10px 35px;
        background: var(--surface-3); color: var(--text-color);
        border: 1px solid var(--border); border-radius: 8px;
        outline: none; transition: border 0.2s, box-shadow 0.2s;
    }
    .input-icon input:focus, .input-icon select:focus {
        border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        background: var(--surface-1);
    }

    /* Permissions Styling */
    .permissions-section {
        background: var(--surface-1);
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        border: 1px dashed var(--border);
    }
    .perm-title {
        display: block; font-weight: 600; color: #374151; margin-bottom: 15px;
    }
    .permissions-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(115px, 1fr)); gap: 8px;
    }
    .perm-card {
        background: var(--surface-3); border: 1px solid var(--border); border-radius: 8px;
        padding: 10px 15px; cursor: pointer; transition: all 0.2s;
        display: flex; align-items: center; gap: 10px;
    }
    .perm-card:hover { border-color: #6366f1; transform: translateY(-1px); background: var(--surface-2); }
    .perm-card input:checked + .perm-content { color: #4f46e5; font-weight: 600; }
    .perm-content { color: #4b5563; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }

    .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; }
    .btn-cancel { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 10px 20px; border-radius: 6px; cursor: pointer; }
    .btn-submit { background: var(--text-color); color: var(--surface-1); border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 0.95rem; white-space: nowrap; transition: opacity 0.2s; }
    .btn-submit:hover { opacity: 0.9; }

    /* Table Styles */
    .card-header-styled {
        background: var(--surface-3); padding: 15px 20px; border-bottom: 1px solid var(--border);
    }
    .card-header-styled h3 { margin: 0; color: var(--text-color); font-size: 1.1rem; }
    
    table thead th { background: var(--surface-3) !important; color: var(--text-muted) !important; font-weight: 700 !important; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; border-bottom: 2px solid var(--border); }
    table tbody tr:hover { background: var(--surface-3); }
    
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border: 1px solid rgba(0,0,0,0.05); }
    .badge-purple { background: rgba(99, 102, 241, 0.15); color: #818cf8; }
    .badge-blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
    .badge-green { background: rgba(16, 185, 129, 0.15); color: #34d399; }

    .btn-icon { background: none; border: none; cursor: pointer; font-size: 1rem; padding: 5px; border-radius: 4px; transition: background 0.2s; }
    .btn-icon.delete { color: #ef4444; }
    .btn-icon:hover { background: #f3f4f6; }
    
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .admin-form-card { padding: 20px 15px; }
        .btn-submit { font-size: 0.85rem; padding: 10px; width: 100%; }
        .form-actions { flex-direction: column-reverse; }
        .btn-cancel { width: 100%; }
    }
</style>

</body>
</html>
