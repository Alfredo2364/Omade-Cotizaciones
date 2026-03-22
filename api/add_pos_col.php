<?php
require_once '../includes/db.php';
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN pos_favorite TINYINT(1) DEFAULT 0");
    echo "Column pos_favorite added successfully.";
} catch (PDOException $e) {
    echo "Column likely already exists or error: " . $e->getMessage();
}
?>
