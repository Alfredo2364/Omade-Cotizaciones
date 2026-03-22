<?php
// Disable error displaying to prevent breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    session_start();
    require_once '../includes/db.php';
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $action = $_GET['action'] ?? '';
    $currentUser = $_SESSION['user_id'];
    
    // IMPORTANT: Close session writing to prevent locking
    session_write_close();

    // ==========================================
    // ACTION: FETCH MESSAGES
    // ==========================================
    if ($action === 'fetch_messages') {
        $targetUser = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        $role = $_SESSION['role'] ?? 'client';

        if ($role === 'client') {
            // Client: See ALL my messages (sent by me OR received by me)
            $stmt = $pdo->prepare("
                SELECT m.*, u.name as sender_name 
                FROM messages m 
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.sender_id = ? OR m.receiver_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$currentUser, $currentUser]);
        } else {
            // Admin: See conversation with specific client
            if (!$targetUser) {
                echo json_encode(['success' => false, 'message' => 'Missing Client ID']);
                exit;
            }
            $stmt = $pdo->prepare("
                SELECT m.*, u.name as sender_name 
                FROM messages m 
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.sender_id = ? OR m.receiver_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$targetUser, $targetUser]);
        }
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }

    // ==========================================
    // ACTION: SEND MESSAGE
    // ==========================================
    if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $receiverId = $input['receiver_id'] ?? null;
        $message = $input['message'] ?? '';

        if (!$receiverId || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$currentUser, $receiverId, $message])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit;
    }

    // ==========================================
    // ACTION: CLOSE CHAT
    // ==========================================
    if ($action === 'close_chat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $otherUser = $input['other_user_id'] ?? null;
        
        if (!$otherUser) {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        if ($stmt->execute([$currentUser, $otherUser, $otherUser, $currentUser])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit;
    }

    // Default: Invalid Action
    echo json_encode(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
