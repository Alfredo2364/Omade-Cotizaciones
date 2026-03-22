<?php require_once '../includes/admin_header.php'; ?>

<div class="page-header">
    <h1>Configuraciones de Perfil</h1>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 20px;">
        <div style="width: 80px; height: 80px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto; box-shadow: var(--card-shadow); border: 2px solid var(--border);">
            <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
        </div>
        <h2 style="margin-top: 10px; color: var(--text-color);"><?= htmlspecialchars($_SESSION['name']) ?></h2>
        <span style="background: var(--surface-3); padding: 2px 10px; border-radius: 10px; font-size: 0.8rem; color: var(--text-muted); border: 1px solid var(--border);">
            <?= ucfirst(str_replace('_', ' ', $_SESSION['role'])) ?>
        </span>
    </div>

    <form id="adminProfileForm">
        <div class="form-group">
            <label style="color: var(--text-muted); font-weight: 600;">Nombre Completo</label>
            <input type="text" id="p_name" class="form-control" value="<?= htmlspecialchars($_SESSION['name']) ?>" required style="background: var(--surface-3); color: var(--text-color); border: 1px solid var(--border);">
        </div>

        <div class="form-group">
            <label style="color: var(--text-muted); font-weight: 600;">Correo Electrónico</label>
            <!-- Disabled for admins as per rule -->
            <input type="email" id="p_email" class="form-control" value="<?= htmlspecialchars($_SESSION['email']) ?>" disabled style="background: var(--surface-1); color: var(--text-muted); border: 1px solid var(--border);">
            <small style="color: var(--muted);"><i class="fas fa-lock"></i> No editable por política de seguridad.</small>
        </div>

        <div class="form-group">
            <label style="color: var(--text-muted); font-weight: 600;">Nueva Contraseña (Opcional)</label>
            <input type="password" id="p_password" class="form-control" placeholder="Dejar en blanco para mantener la actual" style="background: var(--surface-3); color: var(--text-color); border: 1px solid var(--border);">
        </div>

        <button type="submit" class="btn-action btn-create" style="width: 100%; margin-top: 10px;">Guardar Cambios</button>
    </form>
</div>

<div class="card" style="max-width: 600px; margin: 20px auto;">
    <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px; color: var(--text-color);">
        <i class="fas fa-palette" style="color: #6366f1;"></i> Apariencia del Sistema
    </h3>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
        Elige el modo que mejor se adapte a tu vista. El modo oscuro reduce el cansancio visual en entornos de poca luz.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div onclick="setTheme('light')" style="cursor: pointer; padding: 15px; border: 2px solid var(--border); border-radius: 12px; text-align: center; transition: 0.2s; background: var(--surface-3); color: var(--text-color);" id="theme-light-card">
            <i class="fas fa-sun" style="font-size: 1.5rem; color: #f1c40f; margin-bottom: 8px;"></i>
            <div style="font-weight: 600;">Modo Claro</div>
        </div>
        <div onclick="setTheme('dark')" style="cursor: pointer; padding: 15px; border: 2px solid var(--border); border-radius: 12px; text-align: center; transition: 0.2s; background: var(--surface-3); color: var(--text-color);" id="theme-dark-card">
            <i class="fas fa-moon" style="font-size: 1.5rem; color: #6366f1; margin-bottom: 8px;"></i>
            <div style="font-weight: 600;">Modo Oscuro</div>
        </div>
    </div>
</div>

<script>
    // Theme selection visual feedback
    function updateThemeHighlights(e) {
        const current = (e && e.detail && e.detail.theme) || document.documentElement.getAttribute('data-theme') || 'light';
        const lightCard = document.getElementById('theme-light-card');
        const darkCard = document.getElementById('theme-dark-card');
        
        if (!lightCard || !darkCard) return;

        if (current === 'dark') {
            darkCard.style.borderColor = '#6366f1';
            darkCard.style.background = 'rgba(99, 102, 241, 0.1)';
            lightCard.style.borderColor = 'var(--border)';
            lightCard.style.background = 'var(--surface-3)';
        } else {
            lightCard.style.borderColor = '#f1c40f';
            lightCard.style.background = 'rgba(241, 196, 15, 0.1)';
            darkCard.style.borderColor = 'var(--border)';
            darkCard.style.background = 'var(--surface-3)';
        }
    }

    // Listen for global theme changes (from navbar etc.)
    window.addEventListener('themechanged', updateThemeHighlights);

    document.addEventListener('DOMContentLoaded', () => {
        updateThemeHighlights();
    });
</script>
<!-- Danger Zone (ONLY MASTER ADMIN ID 1) -->
<?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1): ?>
<div class="card" style="max-width: 600px; margin: 30px auto; border: 1px solid #ef4444; background: rgba(239, 68, 68, 0.05);">
    <h3 style="color: #ef4444; margin-top: 0;"><i class="fas fa-exclamation-triangle"></i> Zona de Peligro</h3>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
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
