<?php
// Disable display_errors to prevent HTML affecting JSON
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    // Robust inclusion
    $baseDir = dirname(__DIR__);
    $dbFile = $baseDir . '/includes/db.php';

    if (!file_exists($dbFile)) {
        throw new Exception("Database file not found at: $dbFile");
    }
    require_once $dbFile;

    // Verify PDO
    if (!isset($pdo)) {
        throw new Exception("Database connection failed (PDO not set).");
    }

    $q = $_GET['q'] ?? '';
    
    // Check if columns exist (Optional fallback or just run basic query)
    // For now, we assume the schema is correct as per instructions.
    // If not, we might fallback to just 'name'
    
    // Safer Query Construction
    $sql = "SELECT id, name, paternal_surname, maternal_surname, email, created_at, is_banned 
            FROM users 
            WHERE role = 'client'";
    
    $params = [];
    
    if (!empty($q)) {
        // Concatenate names for easier search logic
        // CONCAT_WS handles NULLs gracefully
        $sql .= " AND (
            CONCAT_WS(' ', name, paternal_surname, maternal_surname) LIKE ? 
            OR email LIKE ?
        )";
        $search = "%$q%";
        $params = [$search, $search];
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process Results
    $results = [];
    foreach ($clients as $client) {
        // Use ?? '' to handle potential NULLs
        $name = $client['name'] ?? '';
        $paternal = $client['paternal_surname'] ?? '';
        $maternal = $client['maternal_surname'] ?? '';
        
        $fullName = trim("$name $paternal $maternal");
        // Fallback for empty name
        if (!$fullName) $fullName = 'Cliente Sin Nombre';
        
        $results[] = [
            'id' => $client['id'],
            'full_name' => htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'),
            'initial' => htmlspecialchars(mb_strtoupper(mb_substr($name, 0, 1)) ?: '?', ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($client['email'] ?? '', ENT_QUOTES, 'UTF-8'),
            'created_at' => $client['created_at'],
            'formatted_date' => date('d/m/Y', strtotime($client['created_at'])),
            'is_banned' => $client['is_banned']
        ];
    }
    
    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
