<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../login.php");
    exit;
}
require_once '../includes/db.php';

// Logic to find admin remains same
$admin_id = 1; 
$admin_name = 'Soporte';
$last_admin = $pdo->prepare("SELECT u.id, u.name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? AND u.role = 'super_admin' ORDER BY m.created_at DESC LIMIT 1");
$last_admin->execute([$_SESSION['user_id']]);
$found = $last_admin->fetch();
if ($found) { $admin_id = $found['id']; $admin_name = $found['name']; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - Distribuciones Omade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; padding: 0; height: 100vh; display: flex; flex-direction: column; }
        
        /* Navbar (Simplified) */
        .navbar { background: #0056b3; padding: 10px 20px; display: flex; align-items: center; color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); justify-content: space-between; }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.1rem; }
        .nav-brand img { height: 35px; }
        .nav-back { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.9rem; transition: 0.2s; }
        .nav-back:hover { background: rgba(255,255,255,0.3); }

        /* Chat Layout */
        .chat-layout {
            flex: 1; max-width: 900px; width: 100%; margin: 20px auto;
            background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex; flex-direction: column; overflow: hidden; height: calc(100% - 40px);
        }
        
        .chat-header {
            padding: 15px 25px; border-bottom: 1px solid #e2e8f0; background: white;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header-info { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 45px; height: 45px; background: #e0f2fe; color: #0369a1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .status-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block; margin-right: 5px; }

        .messages-area {
            flex: 1; background: #f8fafc; padding: 25px; overflow-y: auto;
            display: flex; flex-direction: column; gap: 15px;
        }
        
        .bubble {
            max-width: 75%; padding: 12px 18px; border-radius: 18px; position: relative;
            font-size: 0.95rem; line-height: 1.5; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .sent {
            align-self: flex-end; background: #3b82f6; color: white;
            border-bottom-right-radius: 4px;
        }
        .received {
            align-self: flex-start; background: white; color: #334155;
            border-bottom-left-radius: 4px; border: 1px solid #e2e8f0;
        }
        .time { font-size: 0.7rem; margin-top: 4px; opacity: 0.8; text-align: right; }

        .input-bar {
            padding: 20px; background: white; border-top: 1px solid #e2e8f0;
            display: flex; gap: 15px; align-items: center;
        }
        .msg-input {
            flex: 1; padding: 15px 20px; background: #f1f5f9; border: 1px solid transparent;
            border-radius: 30px; outline: none; transition: 0.2s; font-size: 1rem;
        }
        .msg-input:focus { background: white; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .send-btn {
            width: 50px; height: 50px; background: #0056b3; color: white; border: none;
            border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; transition: transform 0.2s; box-shadow: 0 4px 10px rgba(0,86,179,0.3);
        }
        .send-btn:hover { transform: scale(1.05); background: #004494; }

        @media (max-width: 768px) {
            .chat-layout { margin: 0; border-radius: 0; height: 100%; width: 100%; box-shadow: none; }
            .navbar { padding: 10px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">
        <a href="dashboard.php" class="nav-back"><i class="fas fa-arrow-left"></i></a>
        <span>Soporte Omade</span>
    </div>
    <!-- Simple Trash Icon for Reset -->
    <i class="fas fa-trash-alt" onclick="closeChat()" style="cursor: pointer; opacity: 0.8;" title="Reiniciar Chat"></i>
</nav>

<div class="chat-layout">
    <div class="chat-header">
        <div class="header-info">
            <div class="avatar"><i class="fas fa-headset"></i></div>
            <div>
                <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($admin_name) ?></div>
                <div style="font-size: 0.85rem; color: #64748b;"><span class="status-dot"></span>Disponible</div>
            </div>
        </div>
    </div>

    <div class="messages-area" id="messagesList">
        <div style="text-align: center; color: #94a3b8; margin-top: 50px;">
            <i class="fas fa-spinner fa-spin"></i> Conectando...
        </div>
    </div>

    <div class="input-bar">
        <input type="text" id="msgInput" class="msg-input" placeholder="Escribe un mensaje..." autocomplete="off">
        <button id="btnSend" class="send-btn"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
    const otherId = <?= $admin_id ?>;
    const myId = <?= $_SESSION['user_id'] ?>;
    const list = document.getElementById('messagesList');
    const input = document.getElementById('msgInput');
    let followScroll = true;

    // Auto Scroll Logic
    list.addEventListener('scroll', () => {
        followScroll = (list.scrollTop + list.offsetHeight) > (list.scrollHeight - 50);
    });

    async function fetchMsgs() {
        try {
            const res = await fetch(`../api/ajax_handler.php?action=fetch_messages&user_id=${otherId}&t=${Date.now()}`);
            const data = await res.json();
            if(data.success) render(data.messages);
        } catch(e) {}
    }

    function render(msgs) {
        if(msgs.length === 0) {
            list.innerHTML = '<div style="text-align: center; color: #cbd5e1; margin-top: 50px;"><p>No hay mensajes recientes.</p></div>';
            return;
        }

        let html = '';
        msgs.forEach(m => {
            const isMe = m.sender_id == myId;
            const cls = isMe ? 'sent' : 'received';
            html += `<div class="bubble ${cls}">
                        ${m.message}
                        <div class="time" style="color:${isMe?'rgba(255,255,255,0.7)':'#94a3b8'}">${m.created_at || ''}</div>
                     </div>`;
        });

        if(list.innerHTML !== html) {
            const old = list.scrollTop;
            list.innerHTML = html;
            if(followScroll) list.scrollTop = list.scrollHeight;
            else list.scrollTop = old;
        }
    }

    async function send() {
        const txt = input.value.trim();
        if(!txt) return;
        input.value = '';
        
        await fetch('../api/ajax_handler.php?action=send_message', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ receiver_id: otherId, message: txt })
        });
        fetchMsgs();
        followScroll = true;
    }

    async function closeChat() {
        if(!confirm('¿Borrar historial?')) return;
        await fetch('../api/ajax_handler.php?action=close_chat', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ other_user_id: otherId })
        });
        window.location.reload();
    }

    input.addEventListener('keypress', e => { if(e.key === 'Enter') send(); });
    document.getElementById('btnSend').onclick = send;

    setInterval(fetchMsgs, 3000);
    fetchMsgs();
</script>

</body>
</html>
