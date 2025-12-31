<?php
// admin/messages.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

// Admin needs to select WHICH employee to chat with.
// We need a list of employees.
$emps = $pdo->query("SELECT id, first_name, last_name, employee_id, profile_photo FROM employees ORDER BY first_name ASC")->fetchAll();

$current_chat_id = isset($_GET['emp_id']) ? (int) $_GET['emp_id'] : 0;
?>
<style>
    .chat-container {
        height: 75vh;
    }

    .user-list {
        height: 100%;
        overflow-y: auto;
        border-right: 1px solid #dee2e6;
    }

    .chat-area {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .chat-box {
        flex-grow: 1;
        overflow-y: auto;
        background: #f8f9fa;
        padding: 20px;
    }

    .message {
        margin-bottom: 15px;
        padding: 10px 15px;
        border-radius: 15px;
        max-width: 75%;
        position: relative;
    }

    .message.sent {
        background: #0d6efd;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 2px;
    }

    .message.received {
        background: #e9ecef;
        margin-right: auto;
        border-bottom-left-radius: 2px;
    }

    .user-item {
        cursor: pointer;
        transition: 0.2s;
    }

    .user-item:hover,
    .user-item.active {
        background-color: #f8f9fa;
    }
</style>

<div class="container-fluid p-0">
    <div class="d-flex chat-container bg-white shadow-sm rounded border mt-3">

        <!-- User List -->
        <div class="col-md-3 user-list p-0">
            <div class="p-3 border-bottom bg-light fw-bold">Employees</div>
            <div class="list-group list-group-flush">
                <?php foreach ($emps as $e): ?>
                    <a href="?emp_id=<?php echo $e['id']; ?>"
                        class="list-group-item list-group-item-action user-item <?php echo ($current_chat_id == $e['id']) ? 'active' : ''; ?>">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white me-2"
                                style="width: 35px; height: 35px; font-size: 14px;">
                                <?php echo substr($e['first_name'], 0, 1) . substr($e['last_name'], 0, 1); ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo $e['first_name'] . ' ' . $e['last_name']; ?></h6>
                                <small class="text-muted"><?php echo $e['employee_id']; ?></small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="col-md-9 chat-area">
            <?php if ($current_chat_id > 0): ?>
                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Chatting with Employee ID: <?php echo $current_chat_id; ?></h5>
                </div>

                <div id="chatBox" class="chat-box">
                    <div class="text-center text-muted mt-5">Loading conversation...</div>
                </div>

                <div class="p-3 border-top bg-light">
                    <form id="chatForm" onsubmit="sendMessage(event)">
                        <div class="input-group">
                            <input type="text" id="msgInput" class="form-control" placeholder="Type a message..." required
                                autocomplete="off">
                            <input type="hidden" id="receiverId" value="<?php echo $current_chat_id; ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-send-fill"></i></button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                    <h5>Select an employee to start chatting</h5>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php if ($current_chat_id > 0): ?>
    <script>
        let lastMsgId = 0;
        const chatBox = document.getElementById('chatBox');
        const myRole = 'admin';

        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function fetchMessages() {
            $.ajax({
                url: '../ajax/fetch_messages.php',
                method: 'GET',
                data: { last_id: lastMsgId, partner_id: <?php echo $current_chat_id; ?> },
                dataType: 'json',
                success: function (response) {
                    if (lastMsgId === 0) chatBox.innerHTML = '';

                    if (response.messages && response.messages.length > 0) {
                        response.messages.forEach(msg => {
                            lastMsgId = msg.id;
                            const isMe = (msg.sender_role === myRole);
                            const cls = isMe ? 'sent' : 'received';
                            const html = `
                            <div class="message ${cls}">
                                <div>${msg.message}</div>
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

            $.ajax({
                url: '../ajax/send_message.php',
                method: 'POST',
                data: { message: text, receiver_id: recv },
                success: function (res) {
                    input.value = '';
                    fetchMessages();
                }
            });
        }

        setInterval(fetchMessages, 3000); // 3s polling
        fetchMessages();
    </script>
<?php endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>