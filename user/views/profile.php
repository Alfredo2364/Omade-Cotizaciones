            <!-- PROFILE TAB -->
            <div class="page-header" style="margin-bottom: 30px;">
                <h1 style="font-size: 1.8rem; color: var(--primary);">Mi Perfil</h1>
                <p style="color: var(--text-light);">Administra tu información personal y de contacto.</p>
            </div>
            
             <div class="premium-card" style="max-width: 800px; margin: 0 auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="margin: 0; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; color: var(--primary);">
                        <i class="fas fa-user-cog" style="color: var(--secondary);"></i> Datos Personales
                    </h3>
                </div>

                <form id="clientProfileForm">
                    <div class="premium-form-group">
                        <label class="premium-label">Nombre(s)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="cp_name" value="<?= htmlspecialchars($_SESSION['name']) ?>" class="premium-input">
                        </div>
                    </div>

                    <div class="name-row">
                        <div style="flex: 1;">
                            <label class="premium-label">Apellido Paterno</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user-tag"></i>
                                <input type="text" id="cp_paternal" value="<?= htmlspecialchars($_SESSION['paternal_surname'] ?? '') ?>" class="premium-input">
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <label class="premium-label">Apellido Materno</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user-tag"></i>
                                <input type="text" id="cp_maternal" value="<?= htmlspecialchars($_SESSION['maternal_surname'] ?? '') ?>" class="premium-input">
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                        <div>
                            <label class="premium-label">Correo Electrónico</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="cp_email" value="<?= htmlspecialchars($_SESSION['email']) ?>" class="premium-input">
                            </div>
                        </div>
                        <div>
                            <label class="premium-label">Teléfono</label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="cp_phone" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>" class="premium-input" placeholder="55 1234 5678">
                            </div>
                        </div>
                    </div>
                    
                    <div class="premium-form-group">
                        <label class="premium-label">Dirección</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" id="cp_address" value="<?= htmlspecialchars($_SESSION['address'] ?? '') ?>" class="premium-input" placeholder="Ej: Av. Principal 123, Col. Centro">
                        </div>
                    </div>

                    <div class="premium-form-group">
                        <label class="premium-label">Contraseña Nueva (Opcional)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="cp_password" placeholder="Solo si deseas cambiarla..." class="premium-input">
                        </div>
                    </div>

                    <div style="margin-top: 30px; display: flex; align-items: center; justify-content: flex-end; gap: 15px;">
                        <div id="profileMsg" style="font-weight: 600; font-size: 0.9rem;"></div>
                        <button type="submit" class="premium-btn" style="width: auto; padding-left: 30px; padding-right: 30px;">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
                
                <style>
                    .name-row { display: flex; gap: 20px; margin-bottom: 24px; }
                    @media (max-width: 600px) {
                        .name-row { flex-direction: column; gap: 24px; }
                        .premium-card { padding: 20px; }
                    }
                </style>
             </div>

                <script>
                document.getElementById('clientProfileForm').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = e.target.querySelector('button');
                    const msgDiv = document.getElementById('profileMsg');
                    const originalContent = btn.innerHTML;
                    
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                    msgDiv.innerHTML = "";

                    const data = {
                        name: document.getElementById('cp_name').value,
                        paternal_surname: document.getElementById('cp_paternal').value,
                        maternal_surname: document.getElementById('cp_maternal').value,
                        email: document.getElementById('cp_email').value,
                        phone: document.getElementById('cp_phone').value,
                        address: document.getElementById('cp_address').value,
                        password: document.getElementById('cp_password').value
                    };

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
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                        }
                    } catch(err) {
                        console.error(err);
                        showToast("Error de conexión", 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                });
                </script>
             </div>
