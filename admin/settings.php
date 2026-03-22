<?php require_once '../includes/admin_header.php'; ?>

<div class="page-header">
    <h1>Configuraciones de Perfil</h1>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 20px;">
        <div style="width: 80px; height: 80px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto;">
            <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
        </div>
        <h2 style="margin-top: 10px;"><?= htmlspecialchars($_SESSION['name']) ?></h2>
        <span style="background: #e2e8f0; padding: 2px 10px; border-radius: 10px; font-size: 0.8rem; color: #64748b;">
            <?= ucfirst(str_replace('_', ' ', $_SESSION['role'])) ?>
        </span>
    </div>

    <form id="adminProfileForm">
        <div class="form-group">
            <label>Nombre Completo</label>
            <input type="text" id="p_name" class="form-control" value="<?= htmlspecialchars($_SESSION['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Correo Electrónico</label>
            <!-- Disabled for admins as per rule -->
            <input type="email" id="p_email" class="form-control" value="<?= htmlspecialchars($_SESSION['email']) ?>" disabled title="Los trabajadores no pueden editar su correo.">
            <small style="color: #94a3b8;"><i class="fas fa-lock"></i> No editable por política de seguridad.</small>
        </div>

        <div class="form-group">
            <label>Nueva Contraseña (Opcional)</label>
            <input type="password" id="p_password" class="form-control" placeholder="Dejar en blanco para mantener la actual">
        </div>

        <button type="submit" class="btn-action btn-create" style="width: 100%; margin-top: 10px;">Guardar Cambios</button>
    </form>
    </form>
</div>

<!-- Danger Zone (ONLY MASTER ADMIN ID 1) -->
<?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1): ?>
<div class="card" style="max-width: 600px; margin: 30px auto; border: 1px solid #fab1a0;">
    <h3 style="color: #e74c3c; margin-top: 0;"><i class="fas fa-exclamation-triangle"></i> Zona de Peligro</h3>
    <p style="color: #666; font-size: 0.9rem;">
        Estas acciones son irreversibles y afectan a todo el sistema. Procede con precaución.
    </p>
    <a href="reset_system.php" class="btn-action" style="background: #e74c3c; color: white; display: inline-block; text-decoration: none; width: 100%; text-align: center;">
        Reiniciar Sistema
    </a>
</div>
<?php endif; ?>

<script>
document.getElementById('adminProfileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = "Guardando...";

    const data = {
        name: document.getElementById('p_name').value,
        email: document.getElementById('p_email').value, // Will send value even if disabled? No, disabled inputs aren't sent usually.
        password: document.getElementById('p_password').value
    };
    
    // Manually add email because disabled inputs don't submit, but API expects it
    if(document.getElementById('p_email').disabled) {
        data.email = document.getElementById('p_email').value;
    }

    try {
        const res = await fetch('../api/update_profile.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if(result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch(err) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = originalText;
    }
});
</script>

</body>
</html>
