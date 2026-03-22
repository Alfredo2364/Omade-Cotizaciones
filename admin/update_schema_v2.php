<?php
require_once '../includes/db.php';

try {
    echo "Updating users table...<br>";
    
    // Add columns if they don't exist
    $cols = [
        "lastname" => "VARCHAR(100) AFTER name",
        "phone" => "VARCHAR(20) AFTER email",
        "address" => "TEXT AFTER phone"
    ];

    foreach ($cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
            echo "Added column: $col<br>";
        } catch (PDOException $e) {
            // Ignore if column exists (Code 42S21 usually, but generic catch is safer for simple migration)
            echo "Column $col might already exist or error: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "Migration completed successfully.";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
