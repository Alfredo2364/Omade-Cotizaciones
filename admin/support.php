<?php require_once '../includes/admin_header.php'; ?>
<?php if (!hasPermission($pdo, $_SESSION['user_id'], 'support')) die("Acceso Denegado"); ?>

<div class="page-header">
    <h1><i class="fas fa-headset" style="color: #6366f1;"></i> Centro de Soporte</h1>
</div>

<style>
    /* Support Layout */
    .support-wrapper {
        display: flex; height: calc(100vh - 140px); background: var(--surface-2); 
        border-radius: 12px; overflow: hidden; box-shadow: var(--card-shadow);
        border: 1px solid var(--border);
    }
    
    /* LEFT SIDEBAR (Client List) */
    .support-sidebar {
        width: 320px; background: var(--surface-2); border-right: 1px solid var(--border); display: flex; flex-direction: column;
    }
    .sidebar-header {
        padding: 20px; border-bottom: 1px solid var(--border); background: var(--surface-3);
    }
    .search-box {
        position: relative;
    }
    .search-box i {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;
    }
    .search-box input {
        width: 100%; padding: 10px 10px 10px 35px; border: 1px solid var(--border);
        border-radius: 8px; outline: none; transition: 0.2s; background: var(--surface-1);
        color: var(--text-color);
    }
    .search-box input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    
    .client-list {
        flex: 1; overflow-y: auto;
    }
    .client-item {
        display: flex; align-items: center; gap: 12px; padding: 15px 20px;
        border-bottom: 1px solid var(--border); cursor: pointer; transition: 0.2s;
        text-decoration: none; color: inherit;
    }
    .client-item:hover { background: var(--surface-3); }
    .client-item.active { background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; }
    
    .client-avatar {
        width: 45px; height: 45px; border-radius: 50%; background: var(--surface-3);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: var(--text-muted); font-size: 1.1rem; flex-shrink: 0;
    }
    .client-item.active .client-avatar { background: #3b82f6; color: white; }
    
    .client-info { flex: 1; min-width: 0; }
    .client-name { font-weight: 600; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .client-meta { font-size: 0.8rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .unread-badge {
        background: #ef4444; color: white; font-size: 0.75rem; font-weight: bold;
        padding: 2px 8px; border-radius: 10px; margin-left: auto;
    }

    /* RIGHT AREA (Chat) */
    .chat-area {
        flex: 1; display: flex; flex-direction: column; background: var(--surface-1);
    }
    .chat-header-bar {
        padding: 15px 25px; border-bottom: 1px solid var(--border); background: var(--surface-2);
        display: flex; justify-content: space-between; align-items: center; z-index: 10;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .chat-user-profile { display: flex; align-items: center; gap: 12px; }
    .chat-user-name { font-weight: 700; color: var(--text-color); font-size: 1.1rem; }
    .chat-status { font-size: 0.8rem; color: #10b981; display: flex; align-items: center; gap: 5px; }
    .chat-status::before { content: ''; width: 8px; height: 8px; background: #10b981; border-radius: 50%; }

    .messages-container {
        flex: 1; padding: 25px; overflow-y: auto; background: var(--surface-1);
        display: flex; flex-direction: column; gap: 15px; scroll-behavior: smooth;
    }
    /* Custom Scrollbar for messages */
    .messages-container::-webkit-scrollbar { width: 6px; }
    .messages-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

    .msg-bubble {
        max-width: 75%; padding: 12px 18px; border-radius: 18px; position: relative;
        font-size: 0.95rem; line-height: 1.5; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .msg-sent {
        align-self: flex-end; background: #3b82f6; color: white;
        border-bottom-right-radius: 4px;
    }
    .msg-received {
        align-self: flex-start; background: var(--surface-3); color: var(--text-color);
        border-bottom-left-radius: 4px; border: 1px solid var(--border);
    }
    .msg-time {
        font-size: 0.7rem; margin-top: 4px; opacity: 0.8; text-align: right;
    }

    .input-area {
        padding: 20px; background: var(--surface-2); border-top: 1px solid var(--border);
        display: flex; gap: 15px; align-items: center;
    }
    .chat-input {
        flex: 1; padding: 14px 20px; border: 2px solid var(--border); border-radius: 25px;
        outline: none; font-size: 0.95rem; transition: 0.2s; background: var(--surface-3);
        color: var(--text-color);
    }
    .chat-input:focus { border-color: #3b82f6; background: var(--surface-1); }
    
    .btn-send {
        width: 50px; height: 50px; border-radius: 50%; background: #3b82f6;
        color: white; border: none; font-size: 1.2rem; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
    }
    .btn-send:hover { transform: scale(1.05); background: #2563eb; }

    .empty-state {
        flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
        color: var(--text-muted); background: var(--surface-1);
    }
    .empty-state i { font-size: 4rem; opacity: 0.2; margin-bottom: 20px; }
    
    /* Responsive */
    @media (max-width: 992px) {
        .support-wrapper { height: auto; border-radius: 0; flex-direction: column; min-height: calc(100vh - 100px); }
        .support-sidebar { width: 100%; display: <?= isset($_GET['user_id']) ? 'none' : 'flex' ?>; flex-shrink: 0; }
        .chat-area { display: <?= isset($_GET['user_id']) ? 'flex' : 'none' ?>; flex: 1; min-height: 60vh; }
        .back-btn { display: block !important; }
    }
    @media (max-width: 480px) {
        .messages-container { padding: 12px; gap: 10px; }
        .message-input-area { padding: 10px; }
        .chat-header-bar { padding: 10px 15px; }
        .chat-user-name { font-size: 0.95rem; }
    }
    .back-btn { display: none; margin-right: 15px; font-size: 1.2rem; color: #64748b; cursor: pointer; }
</style>

<div class="support-wrapper">
    <!-- SIDEBAR -->
    <div class="support-sidebar">
        <div class="sidebar-header">
            <h3 style="margin: 0 0 15px 0; color: var(--text-color);">Mensajes</h3>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="clientSearch" placeholder="Buscar cliente..." onkeyup="filterClients()">
            </div>
        </div>
        <div class="client-list" id="clientList">
            <?php
            // Get clients with unread count
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.paternal_surname, u.email,
                (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id AND m.receiver_id = ? AND m.is_read = 0) as unread
                FROM users u WHERE u.role = 'client' ORDER BY unread DESC, u.name ASC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $users = $stmt->fetchAll();

            foreach ($users as $u): 
                $active = (isset($_GET['user_id']) && $_GET['user_id'] == $u['id']) ? 'active' : '';
                $initials = strtoupper(substr($u['name'], 0, 1));
            ?>
            <a href="?user_id=<?= $u['id'] ?>" class="client-item <?= $active ?>" data-name="<?= strtolower($u['name'] . ' ' . $u['email']) ?>">
                <div class="client-avatar"><?= $initials ?></div>
                <div class="client-info">
                    <div class="client-name"><?= htmlspecialchars($u['name'] . ' ' . $u['paternal_surname']) ?></div>
                    <div class="client-meta"><?= htmlspecialchars($u['email']) ?></div>
                </div>
                <?php if($u['unread'] > 0): ?>
                    <div class="unread-badge"><?= $u['unread'] ?></div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CHAT AREA -->
    <div class="chat-area">
        <?php if (isset($_GET['user_id'])): 
            $uid = $_GET['user_id'];
            $userMeta = $pdo->prepare("SELECT name, paternal_surname FROM users WHERE id = ?");
            $userMeta->execute([$uid]);
            $clientData = $userMeta->fetch();
            $clientName = $clientData ? $clientData['name'] . ' ' . $clientData['paternal_surname'] : 'Usuario';
        ?>
            <div class="chat-header-bar">
                <div class="chat-user-profile">
                    <a href="support.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                    <div class="client-avatar" style="width: 40px; height: 40px; font-size: 1rem; background: #3b82f6; color: white;">
                        <?= strtoupper(substr($clientName, 0, 1)) ?>
                    </div>
                    <div>
                        <div class="chat-user-name"><?= htmlspecialchars($clientName) ?></div>
                        <div class="chat-status">Cliente Activo</div>
                    </div>
                </div>
                <button onclick="closeChat(<?= $uid ?>)" class="btn-icon" style="color: #ef4444; background: #fee2e2; border-radius: 8px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none;" title="Eliminar Chat">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>

            <div class="messages-container" id="adminMessagesList">
                <div style="text-align: center; color: #94a3b8; padding-top: 20px;">Cargando conversación...</div>
            </div>

            <div class="input-area">
                <input type="text" id="adminMsgInput" class="chat-input" placeholder="Escribe tu respuesta..." autocomplete="off">
                <button id="adminSendBtn" class="btn-send"><i class="fas fa-paper-plane"></i></button>
            </div>

            <script>
                const chatId = <?= $uid ?>;
                const myId = <?= $_SESSION['user_id'] ?>;
                const msgList = document.getElementById('adminMessagesList');
                const inp = document.getElementById('adminMsgInput');
                let autoScroll = true;

                msgList.addEventListener('scroll', () => {
                    autoScroll = (msgList.scrollTop + msgList.offsetHeight) > (msgList.scrollHeight - 50);
                });

                async function fetchChat() {
                    try {
                        const res = await fetch(`../api/ajax_handler.php?action=fetch_messages&user_id=${chatId}&t=${Date.now()}`);
                        const data = await res.json();
                        if(data.success) renderMessages(data.messages);
                    } catch(e) {}
                }

                function renderMessages(msgs) {
                    if(msgs.length === 0) {
                        msgList.innerHTML = '<div style="text-align: center; color: #cbd5e1; margin-top: 50px;"><i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 10px;"></i><p>Sin mensajes previos</p></div>';
                        return;
                    }

                    let html = '';
                    msgs.forEach(m => {
                        const isMe = m.sender_id == myId;
                        const cls = isMe ? 'msg-sent' : 'msg-received';
                        html += `
                        <div class="msg-bubble ${cls}">
                            ${m.message}
                            <div class="msg-time" style="color: ${isMe ? 'rgba(255,255,255,0.8)' : '#94a3b8'}">
                                ${m.created_at || ''}
                            </div>
                        </div>`;
                    });

                    if(msgList.innerHTML !== html) {
                        const oldPos = msgList.scrollTop;
                        msgList.innerHTML = html;
                        if(autoScroll) msgList.scrollTop = msgList.scrollHeight;
                        else msgList.scrollTop = oldPos;
                    }
                }

                async function send() {
                    const txt = inp.value.trim();
                    if(!txt) return;
                    inp.value = '';
                    
                    await fetch('../api/ajax_handler.php?action=send_message', {
                        method: 'POST', headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ receiver_id: chatId, message: txt })
                    });
                    
                    fetchChat();
                    autoScroll = true;
                }

                inp.addEventListener('keypress', e => { if(e.key === 'Enter') send(); });
                document.getElementById('adminSendBtn').onclick = send;
                
                async function closeChat(id) {
                    if(!confirm('¿Borrar historial de chat?')) return;
                    await fetch('../api/ajax_handler.php?action=close_chat', {
                        method: 'POST', headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ other_user_id: id })
                    });
                    window.location.href = 'support.php';
                }

                setInterval(fetchChat, 3000);
                fetchChat();
            </script>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>Selecciona un chat</h3>
                <p>Elige un cliente de la lista para comenzar a conversar.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterClients() {
    const term = document.getElementById('clientSearch').value.toLowerCase();
    document.querySelectorAll('.client-item').forEach(el => {
        el.style.display = el.dataset.name.includes(term) ? 'flex' : 'none';
    });
}

</script>

</body>
</html>
