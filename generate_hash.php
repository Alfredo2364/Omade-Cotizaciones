<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Hash de Contraseñas</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; font-weight: 600; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        button { width: 100%; padding: 12px; background: #0f172a; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.2s; }
        button:hover { background: #1e293b; }
        .result { margin-top: 20px; padding: 15px; background: #dcfce7; color: #166534; border-radius: 6px; word-break: break-all; border: 1px solid #bbf7d0; }
        .result strong { display: block; margin-bottom: 5px; }
        .copy-hint { font-size: 0.8rem; color: #666; text-align: right; margin-top: 5px; }
        a { display: block; text-align: center; margin-top: 20px; color: #0f172a; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🔐 Generador de Hash</h2>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">
            Ingresa una contraseña para obtener su hash seguro (BCRYPT) compatible con el sistema.
        </p>
        
        <form method="POST">
            <div class="form-group">
                <label>Contraseña:</label>
                <input type="text" name="password" placeholder="Ej: admin123" required autocomplete="off" value="<?= isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '' ?>">
            </div>
            <button type="submit">Generar Hash</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
            $password = $_POST['password'];
            $hash = password_hash($password, PASSWORD_DEFAULT);
            echo "<div class='result'>
                    <strong>Hash Generado:</strong>
                    $hash
                  </div>";
            echo "<div class='copy-hint'>Copia este código y pégalo en la columna <code>password</code> de tu base de datos.</div>";
        }
        ?>
        
        <a href="index.html">← Volver al inicio</a>
    </div>
</body>
</html>
