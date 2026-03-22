<?php
require_once '../includes/db.php';

try {
    echo "Migrating surnames...<br>";
    
    // Rename lastname to paternal_surname
    // Note: If lastname doesn't exist (if v2 failed), this might error, but assuming v2 ran.
    // We strive for idempotency or at least clear error reporting.
    
    // Check if paternal_surname already exists to avoid error
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'paternal_surname'");
    if($check->rowCount() == 0) {
        // Assume lastname exists from previous step
        $pdo->exec("ALTER TABLE users CHANGE lastname paternal_surname VARCHAR(100)");
        echo "Changed lastname to paternal_surname.<br>";
    } else {
        echo "paternal_surname already exists.<br>";
    }

    // Check maternal_surname
    $check2 = $pdo->query("SHOW COLUMNS FROM users LIKE 'maternal_surname'");
    if($check2->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN maternal_surname VARCHAR(100) AFTER paternal_surname");
        echo "Added maternal_surname.<br>";
    } else {
        echo "maternal_surname already exists.<br>";
    }
    
    echo "Migration v3 completed successfully.";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
