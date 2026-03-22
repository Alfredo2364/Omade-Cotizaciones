<?php require_once '../includes/admin_header.php'; ?>
<?php if (!hasPermission($pdo, $_SESSION['user_id'], 'clients')) die("Acceso Denegado"); ?>

<?php
// Handle Ban/Unban via POST protected by CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_status']) && isset($_POST['client_id'])) {
    if (!isset($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Error de validación CSRF");
    }
    $ban_status = (int)$_POST['ban_status'];
    $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
    $stmt->execute([$ban_status, $_POST['client_id']]);
    
    $msg = $ban_status ? 'Cliente bloqueado correctamente' : 'Cliente desbloqueado correctamente';
    $_SESSION['flash'] = ['message' => $msg, 'type' => 'success'];
    echo "<script>window.location.href='clients.php';</script>";
    exit;
}

// Handle Create Client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_client'])) {
    if (!isset($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Error de validación CSRF");
    }
    $name = trim($_POST['name']);
    $paternal = trim($_POST['paternal']);
    $maternal = trim($_POST['maternal']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate Email
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo "<script>showToast('El correo ya existe', 'error');</script>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, paternal_surname, maternal_surname, email, password, role) VALUES (?, ?, ?, ?, ?, 'client')");
        
        if($stmt->execute([$name, $paternal, $maternal, $email, $hashed_password])) {
             logActivity($pdo, $_SESSION['user_id'], 'CREATE_CLIENT', "Registró cliente: $email");
             $_SESSION['flash'] = ['message' => 'Cliente registrado con éxito', 'type' => 'success'];
             echo "<script>window.location.href='clients.php';</script>";
        } else {
            echo "<script>showToast('Error al registrar cliente: " . implode(" ", $stmt->errorInfo()) . "', 'error');</script>";
        }
    }
}
?>

<!-- Search Bar & Actions -->
<div class="card" style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 25px; border: 1px solid #e2e8f0;">
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
        
        <!-- Search Input with POS Style -->
        <div style="position: relative; flex: 1; min-width: 300px;">
            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;"></i>
            <input type="text" id="clientSearch" placeholder="Buscar cliente por nombre o correo..." 
                   style="width: 100%; padding: 14px 14px 14px 45px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem; outline: none; transition: all 0.2s;">
            <div id="search-spinner" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #3b82f6; display: none;">
                <i class="fas fa-circle-notch fa-spin"></i>
            </div>
        </div>

        <!-- Add Button -->
        <button onclick="toggleClientForm()" class="btn-new-client">
            <i class="fas fa-plus-circle"></i> <span>Nuevo Cliente</span>
        </button>
    </div>
</div>

<!-- Animated Form Container -->
<div id="clientFormContainer" class="client-form-container <?= isset($_POST['create_client']) ? 'open' : '' ?>">
    <div class="client-form-card">
        <div class="form-header">
            <h3><i class="fas fa-user-plus"></i> Registrar Nuevo Cliente</h3>
            <button onclick="toggleClientForm()" class="btn-icon"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre(s)</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" required placeholder="Ej: Juan">
                    </div>
                </div>
                <div class="form-group">
                    <label>Apellido Paterno</label>
                    <div class="input-icon">
                        <i class="fas fa-user-tag"></i>
                        <input type="text" name="paternal" required placeholder="Ej: Pérez">
                    </div>
                </div>
                <div class="form-group">
                    <label>Apellido Materno</label>
                    <div class="input-icon">
                        <i class="fas fa-user-tag"></i>
                        <input type="text" name="maternal" placeholder="Ej: López">
                    </div>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" required placeholder="juan@ejemplo.com">
                    </div>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" onclick="toggleClientForm()" class="btn-cancel">Cancelar</button>
                <button type="submit" name="create_client" class="btn-submit">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- Clients List Container -->
<div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
     <div class="card-header-styled" style="background: white; padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px; color: #1e293b;">
            <i class="fas fa-users" style="color: #3b82f6; background: #eff6ff; padding: 8px; border-radius: 8px;"></i> 
            Directorio de Clientes
        </h3>
        <span id="result-count" style="font-size: 0.85rem; color: #64748b; background: #f8fafc; padding: 4px 10px; border-radius: 20px; border: 1px solid #e2e8f0;">
            Mostrando recientes
        </span>
    </div>
    
    <div class="table-container" style="box-shadow: none; border-radius: 0;">
        <table id="clientsTable">
            <thead>
                <tr>
                    <th style="padding-left: 25px;">Cliente</th>
                    <th>Contacto</th>
                    <th>Registro</th>
                    <th>Estado</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody id="clientsTableBody">
                <!-- Content loaded via search/initial -->
            </tbody>
        </table>
        
        <!-- Loading / Empty States -->
        <div id="table-loader" style="display: none; padding: 40px; text-align: center; color: #64748b;">
            <i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Cargando clientes...
        </div>
        <div id="noResults" style="display: none; padding: 40px; text-align: center; color: #94a3b8;">
            <i class="far fa-folder-open fa-3x" style="margin-bottom: 15px; opacity: 0.5;"></i>
            <p>No se encontraron clientes que coincidan.</p>
        </div>
    </div>
</div>

<script>
let searchTimeout = null;

function toggleClientForm() {
    const container = document.getElementById('clientFormContainer');
    container.classList.toggle('open');
    if(container.classList.contains('open')) {
        setTimeout(() => document.querySelector('input[name="name"]').focus(), 300);
    }
}

// Fetch Clients (Search or Initial)
async function fetchClients(query = '') {
    const tbody = document.getElementById('clientsTableBody');
    const loader = document.getElementById('table-loader');
    const noResults = document.getElementById('noResults');
    const spinner = document.getElementById('search-spinner');
    const countBadge = document.getElementById('result-count');
    
    if(query) spinner.style.display = 'block';
    
    try {
        const res = await fetch(`../api/clients_search.php?q=${encodeURIComponent(query)}`);
        const clients = await res.json();
        
        tbody.innerHTML = ''; // Clear current
        
        if (clients.length > 0) {
            noResults.style.display = 'none';
            
            clients.forEach(client => {
                const tr = document.createElement('tr');
                tr.className = 'client-row-anim';
                
                const statusBadge = client.is_banned == 1 
                    ? '<span class="badge badge-red"><i class="fas fa-ban"></i> Bloqueado</span>'
                    : '<span class="badge badge-green"><i class="fas fa-check"></i> Activo</span>';
                
                const csrfToken = "<?= $_SESSION['csrf_token'] ?? '' ?>";
                const actionBtn = client.is_banned == 1
                    ? `<form method="POST" style="display:inline;margin:0;"><input type="hidden" name="_csrf" value="${csrfToken}"><input type="hidden" name="ban_status" value="0"><input type="hidden" name="client_id" value="${client.id}"><button type="submit" class="btn-icon btn-unlock" title="Desbloquear acceso" style="border:none;cursor:pointer;"><i class="fas fa-unlock"></i></button></form>`
                    : `<form method="POST" style="display:inline;margin:0;"><input type="hidden" name="_csrf" value="${csrfToken}"><input type="hidden" name="ban_status" value="1"><input type="hidden" name="client_id" value="${client.id}"><button type="submit" class="btn-icon btn-lock" title="Bloquear acceso" style="border:none;cursor:pointer;"><i class="fas fa-lock"></i></button></form>`;

                tr.innerHTML = `
                    <td style="padding-left: 25px;">
                         <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="avatar-circle">
                                ${client.initial}
                            </div>
                            <div>
                                <div class="client-name-text">${client.full_name}</div>
                                <div class="client-id-text">ID: #${client.id}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px; color: #475569;">
                            <i class="far fa-envelope" style="color: #94a3b8;"></i> ${client.email}
                        </div>
                    </td>
                    <td style="color: #64748b;">${client.formatted_date}</td>
                    <td>${statusBadge}</td>
                    <td style="text-align: center;">
                        <div class="action-buttons">
                            ${actionBtn}
                            <!-- Future: Edit button -->
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            countBadge.innerText = query ? `Resultados: ${clients.length}` : 'Mostrando recientes';
        } else {
            noResults.style.display = 'block';
            countBadge.innerText = '0 Resultados';
        }
    } catch (err) {
        console.error(err);
    } finally {
        spinner.style.display = 'none';
        loader.style.display = 'none';
    }
}

// Event Listeners
const searchInput = document.getElementById('clientSearch');
searchInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchClients(e.target.value.trim());
    }, 300);
});

searchInput.addEventListener('focus', () => {
    searchInput.parentElement.style.transform = 'scale(1.01)';
});
searchInput.addEventListener('blur', () => {
    searchInput.parentElement.style.transform = 'scale(1)';
});

// Initial Load
document.addEventListener('DOMContentLoaded', () => fetchClients());
</script>

<style>
    /* New Premium Styles */
    .btn-new-client {
        background: #1e293b; 
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.3);
        transition: all 0.2s;
        display: flex; align-items: center; gap: 10px;
    }
    .btn-new-client:hover { 
        background: #334155; 
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.3);
    }

    #clientSearch:focus { 
        border-color: #3b82f6 !important; 
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); 
    }

    /* Table Styles Override */
    #clientsTable { border-collapse: separate; border-spacing: 0; }
    #clientsTable th { 
        border-bottom: 1px solid #e2e8f0; 
        text-transform: uppercase; 
        font-size: 0.75rem; 
        letter-spacing: 0.05em;
        color: #64748b;
        background: #f8fafc;
        padding: 15px 10px;
    }
    #clientsTable td {
        padding: 15px 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .client-row-anim { animation: fadeIn 0.3s ease-out; }
    .client-row-anim:hover { background: #f8fafc; }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    .avatar-circle {
        width: 40px; height: 40px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
    }
    
    .client-name-text { font-weight: 600; color: #1e293b; font-size: 0.95rem; }
    .client-id-text { font-size: 0.75rem; color: #94a3b8; }

    .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
    .badge-green { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-red { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .btn-icon { 
        width: 32px; height: 32px; 
        border-radius: 6px; 
        display: inline-flex; align-items: center; justify-content: center;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-unlock { background: #f0fdf4; color: #16a34a; }
    .btn-unlock:hover { background: #dcfce7; }
    .btn-lock { background: #fef2f2; color: #ef4444; }
    .btn-lock:hover { background: #fee2e2; }

    /* Form Styles from text */
    .client-form-container {
        max-height: 0; opacity: 0; overflow: hidden;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 20px;
    }
    .client-form-container.open { max-height: 1000px; opacity: 1; margin-bottom: 30px; }
    
    .client-form-card {
        background: white; border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        padding: 30px; border: 1px solid #e2e8f0;
        position: relative; overflow: hidden;
    }
    .client-form-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #3b82f6, #6366f1);
    }
    
    .form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
    .form-header h3 { margin: 0; color: #0f172a; font-size: 1.2rem; }
    
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 0.9rem; }
    
    .input-icon { position: relative; }
    .input-icon i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .input-icon input { width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; transition: 0.2s; }
    .input-icon input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    
    .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; }
    .btn-submit { background: #0f172a; color: white; padding: 12px 25px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-submit:hover { background: #1e293b; transform: translateY(-1px); }
    .btn-cancel { background: white; border: 1px solid #cbd5e1; color: #64748b; padding: 12px 20px; border-radius: 8px; cursor: pointer; }
    .btn-cancel:hover { background: #f8fafc; color: #475569; }

    /* ---- Mobile Responsive ---- */
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    }
    @media (max-width: 480px) {
        .form-actions { flex-direction: column; }
        .btn-submit, .btn-cancel { width: 100%; justify-content: center; text-align: center; }
        .client-form-card { padding: 20px; }
    }
</style>
</body>
</html>
