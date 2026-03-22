<?php
require_once '../includes/db.php';

try {
    echo "Agregando columna product_code...<br>";
    
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM products LIKE 'product_code'");
    if($check->rowCount() == 0) {
        // Add column after ID
        $pdo->exec("ALTER TABLE products ADD COLUMN product_code VARCHAR(50) DEFAULT NULL AFTER id");
        
        // Add index for search performance
        $pdo->exec("ALTER TABLE products ADD INDEX idx_product_code (product_code)");
        
        echo "Columna product_code agregada exitosamente.<br>";
    } else {
        echo "La columna product_code ya existe.<br>";
    }
    
    echo "Actualización v4 completada.";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
