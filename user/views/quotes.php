<?php
// user/views/quotes.php
?>
            <!-- QUOTES TAB -->
            <div class="page-header" style="margin-bottom: 30px;">
                <h1 style="font-size: 1.8rem; color: var(--primary);">Mis Cotizaciones</h1>
                <p style="color: var(--text-light);">Consulta tu historial y solicita nuevos servicios.</p>
            </div>

            <div class="quotes-layout" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; align-items: start;">
                
                <!-- LEFT: History -->
                <div class="premium-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; color: var(--primary);">
                            <i class="fas fa-history" style="color: var(--secondary);"></i> Historial de Solicitudes
                        </h3>
                    </div>

                    <div class="premium-table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Servicio</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $my_quotes = $pdo->prepare("SELECT * FROM quotes WHERE client_email = ? ORDER BY created_at DESC");
                                $my_quotes->execute([$_SESSION['email'] ?? '']); 
                                $quotes_list = $my_quotes->fetchAll();

                                if(empty($quotes_list)) {
                                    echo "<tr><td colspan='5'><div class='empty-state'><i class='fas fa-inbox'></i><p>No tienes cotizaciones registradas aún.</p></div></td></tr>";
                                }
                                foreach ($quotes_list as $q):
                                ?>
                                <tr>
                                    <td style="font-weight: 600;">#<?= $q['id'] ?></td>
                                    <td><?= htmlspecialchars($q['service_type']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($q['created_at'])) ?></td>
                                    <td>
                                        <?php if($q['status'] == 'pending'): ?>
                                            <span class="status-badge status-pending">Pendiente</span>
                                        <?php elseif($q['status'] == 'approved'): ?>
                                            <span class="status-badge status-approved">Aprobada</span>
                                        <?php else: ?>
                                            <span class="status-badge status-rejected">Rechazada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../admin/print_ticket.php?type=quote&id=<?= $q['id'] ?>" target="_blank" style="color: var(--secondary); font-size: 1.1rem; transition: 0.2s;" title="Ver PDF"><i class="fas fa-file-pdf"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT: New Quote Form -->
                <div class="stat-card-premium" style="display: block; border-top: 4px solid var(--secondary);">
                    <h3 style="margin-top: 0; margin-bottom: 25px; color: var(--primary); font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-plus-circle" style="color: var(--secondary);"></i> Nueva Solicitud
                    </h3>
                    <form id="clientQuoteForm">
                        
                        <!-- Client Details -->
                        <div class="premium-form-group">
                            <label class="premium-label">Nombre Completo</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="q_name" class="premium-input" value="<?= htmlspecialchars(trim($_SESSION['name'] . ' ' . ($_SESSION['paternal_surname'] ?? '') . ' ' . ($_SESSION['maternal_surname'] ?? ''))) ?>" readonly style="background: #f8fafc; cursor: default;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="premium-form-group">
                                <label class="premium-label">Correo</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="q_email" class="premium-input" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" readonly style="background: #f8fafc; cursor: default;">
                                </div>
                            </div>
                            <div class="premium-form-group">
                                <label class="premium-label">Teléfono</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" id="q_phone" class="premium-input" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Dirección de Entrega / Servicio</label>
                            <div class="input-wrapper">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" id="q_address" class="premium-input" value="<?= htmlspecialchars($_SESSION['address'] ?? '') ?>" placeholder="Calle, número, colonia...">
                            </div>
                        </div>
                        
                        <div class="premium-form-group">
                            <label class="premium-label">Tipo de Servicio</label>
                            <div class="input-wrapper">
                                <i class="fas fa-tools"></i>
                                <select id="q_service" class="premium-select">
                                    <option value="Maquinaria">Maquinaria Industrial</option>
                                    <option value="Refacciones">Refacciones</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                    <option value="Logistica">Logística</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="premium-form-group">
                            <label class="premium-label">Detalles de la Solicitud</label>
                            <?php
                                $cartText = "";
                                if (!empty($_SESSION['quote_cart'])) {
                                    $cartText = "Hola, me interesa cotizar los siguientes productos:\n";
                                    foreach ($_SESSION['quote_cart'] as $item) {
                                        $cartText .= "- " . htmlspecialchars($item) . "\n";
                                    }
                                    $cartText .= "\nDetalles adicionales:";
                                } elseif (isset($_GET['product'])) {
                                    $cartText = "Hola, me interesa cotizar el producto: " . htmlspecialchars($_GET['product']) . ".\n\nDetalles adicionales:";
                                }
                            ?>
                            <textarea id="q_description" rows="5" class="premium-textarea" placeholder="Describe detalladamente qué necesitas..."><?= $cartText ?></textarea>
                            <?php if(!empty($_SESSION['quote_cart'])): ?>
                                <div style="text-align: right; margin-top: 5px;">
                                    <a href="?clear_quote_cart=1" style="font-size: 0.85rem; color: #ef4444; text-decoration: none;"><i class="fas fa-trash"></i> Limpiar carrito</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="premium-btn">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitud
                        </button>
                    </form>
                </div>
            </div>

            <!-- Responsive Adjustment -->
            <style>
                @media (max-width: 900px) {
                    .quotes-layout { grid-template-columns: 1fr !important; }
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('action') === 'new') {
                        const form = document.getElementById('clientQuoteForm');
                        if (form) {
                            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            // Optional: Highlight the card
                            form.closest('.stat-card-premium').style.boxShadow = '0 0 0 4px rgba(37, 99, 235, 0.2)';
                            setTimeout(() => form.querySelector('textarea')?.focus(), 500);
                        }
                    }
                });

                document.getElementById('clientQuoteForm').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    // ... existing submit logic ...
                    const btn = e.target.querySelector('button');
                    const originalContent = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                    
                    const data = {
                        name: document.getElementById('q_name').value,
                        email: document.getElementById('q_email').value,
                        phone: document.getElementById('q_phone').value,
                        address: document.getElementById('q_address').value,
                        service: document.getElementById('q_service').value,
                        description: document.getElementById('q_description').value
                    };

                    try {
                        // Assuming fetch logic is here or imported
                         // Mock fetch to demonstrate flow if file isn't fully replaced with logic
                        const res = await fetch('../api/submit_quote.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(data)
                        });
                        const result = await res.json();
                        if(result.success) {
                            showToast(result.message, 'success');
                            setTimeout(() => location.href = '?tab=quotes', 2000); // Reload to clear params
                        } else {
                            showToast(result.message, 'error');
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                        }
                    } catch(err) {
                        showToast('Error de conexión o servidor', 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                });
            </script>
