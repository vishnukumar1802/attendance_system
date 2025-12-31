<?php
// employee/messages.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

// Employee chats with Admin (ID 1 default)
$admin_id = 1;

?>
<style>
    .chat-box {
        height: 400px;
        overflow-y: auto;
        background: #f8f9fa;
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 5px;
    }

    .message {
        margin-bottom: 10px;
        max-width: 70%;
        padding: 10px;
        border-radius: 10px;
        position: relative;
    }

    .message.sent {
        background: #d1e7dd;
        margin-left: auto;
        border-bottom-right-radius: 0;
    }

    .message.received {
        background: #fff;
        border: 1px solid #ddd;
        border-bottom-left-radius: 0;
    }

    .msg-time {
        font-size: 0.75rem;
        color: #666;
        display: block;
        text-align: right;
        margin-top: 5px;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Support</h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <i class="bi bi-chat-dots text-primary me-2"></i> Chat with Admin
            </div>
            <div class="card-body">
                <div id="chatBox" class="chat-box mb-3">
                    <div class="text-center text-muted small mt-5">Loading messages...</div>
                </div>

                <form id="chatForm" onsubmit="sendMessage(event)">
                    <div class="input-group">
                        <input type="text" id="msgInput" class="form-control" placeholder="Type a message..." required
                            autocomplete="off">
                        <input type="hidden" id="receiverId" value="<?php echo $admin_id; ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-send"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let lastMsgId = 0;
    const chatBox = document.getElementById('chatBox');
    const myRole = 'employee'; // Needed to distinguish sent/received

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function fetchMessages() {
        $.ajax({
            url: '../ajax/fetch_messages.php',
            method: 'GET',
            data: { last_id: lastMsgId, partner_id: <?php echo $admin_id; ?> },
            dataType: 'json',
            success: function (response) {
                if (lastMsgId === 0) chatBox.innerHTML = ''; // Clear loading text on first load

                if (response.messages && response.messages.length > 0) {
                    response.messages.forEach(msg => {
                        lastMsgId = msg.id;
                        const isMe = (msg.sender_role === myRole);
                        const cls = isMe ? 'sent' : 'received';
                        const html = `
                            <div class="message ${cls}">
                                <div>${msg.message}</div>
                                <span class="msg-time">${msg.created_at}</span>
                            </div>
                        `;
                        chatBox.innerHTML += html;
                    });
                    scrollToBottom();
                }
            }
        });
    }

    function sendMessage(e) {
        e.preventDefault();
        const input = document.getElementById('msgInput');
        const text = input.value.trim();
        const recv = document.getElementById('receiverId').value;

        if (!text) return;

        // Optimistic UI: Append immediately? No, user wants robust. Wait for send.
        // Actually prompt says "Result should feel real-time". 
        // Best approach: Send AJAX, then let Polling fetch it (reliable) 
        // OR Append and Sync.
        // Let's just Send and clear input. Polling (3s) might be slow for "My" message.
        // I will append "My" message immediately manually for instant feel.

        $.ajax({
            url: '../ajax/send_message.php',
            method: 'POST',
            data: { message: text, receiver_id: recv },
            success: function (res) {
                input.value = '';
                fetchMessages(); // Force fetch immediately
            }
        });
    }

    // Poll every 3 seconds
    setInterval(fetchMessages, 3000);
    fetchMessages(); // Initial load
</script>

<?php require_once '../includes/emp_footer.php'; ?>