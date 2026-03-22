// Global Toast Function
function showToast(message, type = 'success', duration = 2000) {
    const overlay = document.getElementById('toast-overlay');
    if (!overlay) return; // Guard if element missing

    const box = document.getElementById('toast-box');
    const icon = document.getElementById('toast-icon');
    const msg = document.getElementById('toast-message');

    // Configure content
    msg.textContent = message;

    if (type === 'success') {
        icon.innerHTML = '<span style="color: #10b981;">&#10004;</span>'; // Checkmark
        box.style.borderTop = '5px solid #10b981';
    } else if (type === 'error') {
        icon.innerHTML = '<span style="color: #ef4444;">&#10006;</span>'; // Cross
        box.style.borderTop = '5px solid #ef4444';
    } else {
        icon.innerHTML = '<span style="color: #3b82f6;">&#8505;</span>'; // Info
        box.style.borderTop = '5px solid #3b82f6';
    }

    // Show
    overlay.style.display = 'flex';
    setTimeout(() => { box.classList.add('active'); }, 10);

    // Hide
    setTimeout(() => {
        box.classList.remove('active');
        setTimeout(() => { overlay.style.display = 'none'; }, 300);
    }, duration);
}

document.addEventListener('DOMContentLoaded', () => {
    // Quote Form Handling
    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        quoteForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Check restriction
            if (localStorage.getItem('quote_submitted') === 'true') {
                showToast('Ya has realizado una cotización gratuita. Inicia sesión para más.', 'info');
                setTimeout(() => window.location.href = 'login.php', 3000);
                return;
            }

            const submitBtn = quoteForm.querySelector('button');
            const originalText = submitBtn.innerText;
            submitBtn.innerText = 'Enviando...';
            submitBtn.disabled = true;

            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                address: document.getElementById('address').value,
                service: document.getElementById('service').value,
                description: document.getElementById('description').value
            };

            try {
                const response = await fetch('api/submit_quote.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    quoteForm.reset();
                    // Set flag to restrict further quotes
                    localStorage.setItem('quote_submitted', 'true');
                } else {
                    showToast(result.message || 'Ocurrió un error.', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error de conexión.', 'error');
            } finally {
                submitBtn.innerText = originalText;
                submitBtn.disabled = false;
            }
        });
    }

    // Login Form Handling
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error de conexión.', 'error');
            }
        });
    }

    // Register Form Handling
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = registerForm.querySelector('button');
            const originalText = submitBtn.innerText;
            submitBtn.innerText = 'Registrando...';
            submitBtn.disabled = true;

            const formData = {
                name: document.getElementById('name').value.trim(),
                paternal_surname: document.getElementById('paternal_surname').value.trim(),
                maternal_surname: document.getElementById('maternal_surname').value.trim(),
                email: document.getElementById('email').value.trim(),
                password: document.getElementById('password').value
            };

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.href = 'login.php', 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error de conexión.', 'error');
            } finally {
                submitBtn.innerText = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    // Hamburger Menu Logic
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // Close menu when a link is clicked
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });
    }

    // Auto-resize Textarea
    const textarea = document.getElementById('description');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});
