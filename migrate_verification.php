<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Add columns if they don't exist
    $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL");

    // Existing users should be auto-verified so we don't lock out admins and past clients
    $pdo->exec("UPDATE users SET is_verified = 1");

    echo "Migración completada con éxito.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Las columnas ya existen.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
