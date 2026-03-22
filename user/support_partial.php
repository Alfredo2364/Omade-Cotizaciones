<?php
// Extracted chat logic to keep dashboard clean
// Fetch my messages
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE sender_id = ? OR receiver_id = ? ORDER BY created_at ASC");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $messages = $stmt->fetchAll();
} else {
    $messages = [];
}
?>
<div style="height: 250px; overflow-y: auto; background: #fafafa; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #eee;">
        <?php foreach ($messages as $msg): 
        $is_me = $msg['sender_id'] == $_SESSION['user_id'];
    ?>
    <div style="margin-bottom: 10px; text-align: <?= $is_me ? 'right' : 'left' ?>;">
        <div style="
            display: inline-block; 
            padding: 8px 12px; 
            border-radius: 10px; 
            background: <?= $is_me ? '#0056b3' : '#e0e0e0' ?>; 
            color: <?= $is_me ? '#fff' : '#333' ?>;
        ">
            <?= htmlspecialchars($msg['message']) ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<form method="POST">
    <input type="hidden" name="receiver_id" value="1"> <!-- Default to Super Admin -->
    <div style="display: flex; gap: 10px;">
        <input type="text" name="message" placeholder="Escribe un mensaje..." required style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
        <button type="submit" name="send_message" class="btn-action-primary" style="border:none; cursor:pointer;">Enviar</button>
    </div>
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $msg = $_POST['message'];
    if (!empty($msg)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], 1, $msg]); 
        echo "<meta http-equiv='refresh' content='0'>"; 
    }
}
?>
