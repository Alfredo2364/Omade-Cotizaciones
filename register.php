<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - Distribuciones Omade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="assets/favicon_io/site.webmanifest">
    <style>
        .login-body {
            background-color: #0b1e3b;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px; /* Consistent with login */
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #475569; font-weight: 600; font-size: 0.9rem; }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 1rem;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #0b1e3b;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-submit:hover { background: #1e293b; }
    </style>
</head>
<body class="login-body">

    <div class="login-card">
        <!-- Volver Button -->
        <a href="login.php" style="text-decoration: none; color: #0b1e3b; font-weight: 600; display: flex; align-items: center; gap: 5px; margin-bottom: 20px;">
            <span style="font-size: 1.2rem;">&larr;</span> Volver al inicio de sesión
        </a>

        <div class="login-header" style="text-align: center; margin-bottom: 30px;">
            <h2 style="margin-bottom: 10px; color: #1e293b;">Crear Cuenta</h2>
            <p style="color: #64748b; font-size: 0.95rem;">Únete para gestionar tus pedidos fácilmente.</p>
        </div>

        <form id="registerForm">
            <div class="form-group">
                <label for="name">Nombre(s)</label>
                <input type="text" id="name" name="name" required placeholder="Tu nombre">
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="paternal_surname">Apellido Paterno</label>
                    <input type="text" id="paternal_surname" name="paternal_surname" required placeholder="Apellido Paterno">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="maternal_surname">Apellido Materno</label>
                    <input type="text" id="maternal_surname" name="maternal_surname" required placeholder="Apellido Materno">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required placeholder="tu@correo.com">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="Crea una contraseña segura">
            </div>

            <button type="submit" class="btn-submit">Registrarse</button>
        </form>
    </div>

    <!-- Toast Notification -->
    <div id="toast-overlay">
        <div id="toast-box">
            <div id="toast-icon"></div>
            <div id="toast-message"></div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
