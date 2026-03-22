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
    <div class="table-container" style="box-shadow: none; border-radius: 0;">
        <table>
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
                            <div style="width: 35px; height: 35px; background: #e0e7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #4338ca; font-weight: bold;">
                                <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                            </div>
                            <span style="font-weight: 500; color: #1f2937;"><?= htmlspecialchars($admin['name']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($admin['email']) ?></td>
                    <td>
                        <?php if($admin['role'] === 'super_admin'): ?>
                            <span class="badge badge-purple">Super Admin</span>
                        <?php else: ?>
                            <span class="badge badge-blue">Admin</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-green">Activo</span></td>
                    <td style="color: #6b7280; font-size: 0.9rem;"><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
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
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05); /* Softer shadow */
        padding: 30px;
        border: 1px solid #e5e7eb;
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
        border-bottom: 1px solid #f3f4f6;
        padding-bottom: 15px;
    }
    .form-header h3 { margin: 0; color: #111827; display: flex; align-items: center; gap: 10px; }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group label {
        display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 0.9rem;
    }
    .input-icon { position: relative; }
    .input-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .input-icon input, .input-icon select {
        width: 100%; padding: 10px 10px 10px 35px;
        border: 1px solid #d1d5db; border-radius: 8px;
        outline: none; transition: border 0.2s, box-shadow 0.2s;
    }
    .input-icon input:focus, .input-icon select:focus {
        border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* Permissions Styling */
    .permissions-section {
        background: #f9fafb;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        border: 1px dashed #d1d5db;
    }
    .perm-title {
        display: block; font-weight: 600; color: #374151; margin-bottom: 15px;
    }
    .permissions-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;
    }
    .perm-card {
        background: white; border: 1px solid #e5e7eb; border-radius: 8px;
        padding: 10px 15px; cursor: pointer; transition: all 0.2s;
        display: flex; align-items: center; gap: 10px;
    }
    .perm-card:hover { border-color: #6366f1; transform: translateY(-1px); }
    .perm-card input:checked + .perm-content { color: #4f46e5; font-weight: 600; }
    .perm-content { color: #4b5563; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }

    .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; }
    .btn-cancel { background: white; border: 1px solid #d1d5db; color: #374151; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
    .btn-submit { background: #111827; color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .btn-submit:hover { background: #000; }

    /* Table Styles */
    .card-header-styled {
        background: #f9fafb; padding: 15px 20px; border-bottom: 1px solid #e5e7eb;
    }
    .card-header-styled h3 { margin: 0; color: #374151; font-size: 1.1rem; }
    
    table thead th { background: #f3f4f6 !important; color: #374151 !important; font-weight: 600 !important; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; }
    table tbody tr:hover { background: #f9fafb; }
    
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-purple { background: #e0e7ff; color: #4338ca; }
    .badge-blue { background: #e0f2fe; color: #0284c7; }
    .badge-green { background: #dcfce7; color: #166534; }

    .btn-icon { background: none; border: none; cursor: pointer; font-size: 1rem; padding: 5px; border-radius: 4px; transition: background 0.2s; }
    .btn-icon.delete { color: #ef4444; }
    .btn-icon:hover { background: #f3f4f6; }
    
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

</body>
</html>
