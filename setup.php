<?php
// setup.php - Script para inicializar la base de datos automáticamente
// Coloca este archivo en C:\xampp\htdocs\Pagina web\ (o tu carpeta de proyecto)

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'omade_db';

try {
    // 1. Conectar al servidor MySQL sin especificar base de datos
    echo "<h1>📢 Inicializando Distribuciones Omade...</h1>";
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Conexión al servidor MySQL exitosa.</p>";

    // 2. Leer el archivo SQL
    $sqlFile = 'db.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>❌ Error: No se encuentra el archivo 'db.sql'. Asegúrate de que está en la misma carpeta.</p>");
    }
    $sql = file_get_contents($sqlFile);

    // 3. Ejecutar las consultas del archivo SQL
    // Nota: El archivo db.sql ya contiene 'CREATE DATABASE' y 'USE omade_db'
    $pdo->exec($sql);
    echo "<p>✅ Base de datos <strong>$dbname</strong> creada e importada correctamente.</p>";

    // 4. Verificar usuario administrador
    // Nos conectamos específicamente a la base de datos ahora para verificar
    $pdo->exec("USE `$dbname`");
    
    // Asegurar que existe el super admin con contraseña 'admin123'
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    // Intentar actualizar si existe, o insertar si no (aunque el SQL ya lo hace, esto es doble seguridad)
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@company.com'");
    $stmt->execute([$adminPass]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Contraseña de Super Admin restablecida/verificada a: <strong>admin123</strong></p>";
    } else {
        // Si no se actualizó nada, quizás el SQL no insertó al usuario (raro), pero informamos
        echo "<p>ℹ️ Verificando usuario administrador...</p>";
    }

    echo "<hr>";
    echo "<h2 style='color:green'>🎉 ¡Instalación Completada con Éxito!</h2>";
    echo "<p>Ahora puedes acceder al sistema:</p>";
    echo "<ul>";
    echo "<li><strong>Sitio Público:</strong> <a href='index.html'>Ir al Inicio</a></li>";
    echo "<li><strong>Panel Admin:</strong> <a href='login.php'>Iniciar Sesión</a> (Usuario: admin@company.com / Pass: admin123)</li>";
    echo "</ul>";
    echo "<p style='color:orange'>⚠️ Por seguridad, borra este archivo 'setup.php' después de usarlo.</p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ Error Fatal</h2>";
    echo "<p>No se pudo completar la instalación. Detalles:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p>Asegúrate de que XAMPP (MySQL) esté encendido.</p>";
}
?>
