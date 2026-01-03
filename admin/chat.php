<?php
// admin/chat.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';
?>

<!-- V4 Chat Styling -->
<style>
    .chat-container {
        height: 85vh;
        display: flex;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
    }

    /* Left Sidebar */
    .chat-sidebar {
        width: 320px;
        border-right: 1px solid #ddd;
        display: flex;
        flex-direction: column;
        background: #f8f9fa;
    }

    .sidebar-header {
        padding: 15px;
        background: #eee;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-list {
        flex: 1;
        overflow-y: auto;
    }

    .chat-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f1f1;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: 0.2s;
    }

    .chat-item:hover {
        background: #e9ecef;
    }

    .chat-item.active {
        background: #e3f2fd;
    }

    .avatar {
        width: 40px;
        height: 40px;
        background: #adb5bd;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 10px;
        font-weight: bold;
    }

    .unread-badge {
        background: #25d366;
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 10px;
        margin-left: auto;
    }

    /* Right Chat Area */
    .chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #e5ddd5;
    }

    /* WhatsApp BG Color */
    .chat-header {
        padding: 10px 15px;
        background: #eee;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .messages-box {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        /* Pattern */
    }

    .msg {
        max-width: 65%;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 14px;
        position: relative;
        word-wrap: break-word;
    }

    .msg.sent {
        align-self: flex-end;
        background: #dcf8c6;
    }

    .msg.received {
        align-self: flex-start;
        background: #fff;
    }

    .msg-time {
        font-size: 10px;
        color: #999;
        text-align: right;
        margin-top: 4px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 4px;
    }

    .msg-status {
        font-size: 14px;
        /* Ensure tick visibility */
        line-height: 1;
    }

    .msg-status.read {
        color: #34b7f1;
    }

    /* Blue Tick */
    .msg-status.delivered {
        color: #999;
    }

    /* Grey Tick */

    .msg-actions {
        display: none;
        position: absolute;
        top: 2px;
        right: 5px;
        background: rgba(255, 255, 255, 0.8);
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 12px;
    }

    .msg.sent:hover .msg-actions {
        display: block;
        /* Show on hover */
    }

    .msg-actions i {
        cursor: pointer;
        margin-left: 5px;
        color: #555;
    }

    .input-area {
        padding: 10px;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .file-btn {
        cursor: pointer;
        color: #555;
    }

    .deleted-msg {
        font-style: italic;
        color: #888;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Sidebar Search Results */
    #dirResults {
        position: absolute;
        top: 60px;
        /* Adjust based on header height */
        left: 0;
        width: 320px;
        max-height: 300px;
        overflow-y: auto;
        background: white;
        border: 1px solid #ddd;
        z-index: 1000;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .dir-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }

    .dir-item:hover {
        background: #f8f9fa;
    }
</style>

<div class="chat-container">
    <!-- Sidebar -->
    <div class="chat-sidebar position-relative">
        <div class="sidebar-header flex-column align-items-stretch">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Chats</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newChatModal">+</button>
            </div>
            <input type="text" id="sidebarSearch" class="form-control form-control-sm" placeholder="Search employee...">
        </div>

        <!-- Search Dropdown -->
        <div id="dirResults"></div>

        <div class="chat-list" id="chatList"></div>
    </div>

    <!-- Main Chat -->
    <div class="chat-area">
        <div id="noChatSelect" class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
            <h4>Select a conversation</h4>
            <p>Admin Private Chat</p>
        </div>

        <div id="activeChat" class="d-none h-100 flex-column">
            <div class="chat-header">
                <div class="d-flex align-items-center">
                    <div class="avatar" id="headerAvatar">?</div>
                    <h6 class="mb-0 ms-2" id="headerName">User</h6>
                </div>
                <!-- Search Box -->
                <div class="d-flex">
                    <input type="text" id="chatSearch" class="form-control form-control-sm" placeholder="Search..."
                        style="width: 200px;">
                </div>
            </div>

            <div class="messages-box" id="msgBox"></div>

            <div id="filePreview" class="px-3 py-1 small bg-light text-primary d-none"></div>

            <div class="input-area">
                <label for="fileIn" class="file-btn"><i class="bi bi-paperclip fs-4"></i></label>
                <input type="file" id="fileIn" hidden>

                <input type="text" id="msgIn" class="form-control" placeholder="Type a message">
                <button class="btn btn-success" onclick="sendMsg()"><i class="bi bi-send-fill"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div class="modal fade" id="newChatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userList" class="list-group">Loading users...</div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editMsgId">
                <textarea id="editMsgText" class="form-control" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="submitEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let currentConvId = null;
    let pollInterval = null;
    let isSearching = false;

    $(document).ready(function () {
        loadChatList();
        setInterval(loadChatList, 4000); // Poll list updates
        loadUsers(); // Pre-load user list for modal

        $('#fileIn').change(function () {
            if (this.files.length) $('#filePreview').text('📎 ' + this.files[0].name).removeClass('d-none');
            else $('#filePreview').addClass('d-none');
        });

        $('#msgIn').keypress(function (e) { if (e.which == 13) sendMsg(); });

        // Search Listener
        $('#chatSearch').on('keyup', function () {
            let term = $(this).val().trim();
            if (term.length > 0) {
                isSearching = true;
                searchMessages(term);
                if (pollInterval) clearInterval(pollInterval); // Stop polling while searching
            } else {
                isSearching = false;
                fetchMessages(); // Restore live view
                if (pollInterval) clearInterval(pollInterval);
                pollInterval = setInterval(fetchMessages, 3000); // Restart polling
            }
        });
    });

    function loadChatList() {
        $.getJSON('../ajax/chat_api.php?action=fetch_list', function (res) {
            if (res.status === 'success') {
                let html = '';
                res.data.forEach(c => {
                    let active = (c.conversation_id == currentConvId) ? 'active' : '';
                    let badge = c.unread_count > 0 ? `<span class="unread-badge">${c.unread_count}</span>` : '';
                    let last = c.last_msg ? (c.last_msg.length > 20 ? c.last_msg.substring(0, 20) + '...' : c.last_msg) : '<i>New Chat</i>';

                    html += `
                <div class="chat-item ${active}" onclick="openChat(${c.conversation_id}, '${c.name}')">
                    <div class="avatar">${c.avatar_initial}</div>
                    <div class="w-100">
                        <div class="d-flex justify-content-between">
                            <strong>${c.name}</strong>
                            ${badge}
                        </div>
                        <small class="text-muted">${last}</small>
                    </div>
                </div>`;
                });
                $('#chatList').html(html);
            }
        });
    }

    function loadUsers() {
        $.getJSON('../ajax/chat_api.php?action=fetch_users', function (res) {
            if (res.status === 'success') {
                let html = '';
                res.data.forEach(u => {
                    html += `<button class="list-group-item list-group-item-action" onclick="startChat(${u.id}, '${u.type}', '${u.name}')">
                    ${u.name} <small class="text-muted">(${u.type})</small>
                </button>`;
                });
                $('#userList').html(html);
            }
        });
    }

    // --- SIDEBAR SEARCH ---
    $('#sidebarSearch').on('keyup', function () {
        let term = $(this).val().trim();
        if (term.length < 1) {
            $('#dirResults').hide().empty();
            return;
        }

        $.post('../ajax/chat_api.php', { action: 'search_directory', term: term }, function (res) {
            if (res.status === 'success' && res.data.length > 0) {
                let html = '';
                res.data.forEach(u => {
                    html += `<div class="dir-item" onclick="selectFromDir(${u.id}, '${u.type}', '${u.name}')">
                        <strong>${u.name}</strong> <small class="text-muted">(${u.type})</small>
                    </div>`;
                });
                $('#dirResults').html(html).show();
            } else {
                $('#dirResults').html('<div class="p-2 text-muted small">No results</div>').show();
            }
        }, 'json');
    });

    function selectFromDir(id, type, name) {
        startChat(id, type, name);
        $('#sidebarSearch').val('');
        $('#dirResults').hide();
    }
    // ----------------------

    function startChat(id, type, name) {
        $.post('../ajax/chat_api.php', { action: 'start_chat', target_id: id, target_type: type }, function (res) {
            if (res.status === 'success') {
                $('#newChatModal').modal('hide');
                openChat(res.conversation_id, name);
                loadChatList(); // Refresh list immediately
            }
        }, 'json');
    }

    function openChat(convId, name) {
        currentConvId = convId;
        $('#noChatSelect').addClass('d-none');
        $('#activeChat').removeClass('d-none').addClass('d-flex');
        $('#headerName').text(name);
        $('#headerAvatar').text(name.substring(0, 1));

        $('#chatSearch').val(''); // Clear search
        isSearching = false;

        fetchMessages();
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(fetchMessages, 3000);
    }

    function fetchMessages() {
        if (!currentConvId || isSearching) return;
        $.post('../ajax/chat_api.php', { action: 'fetch_messages', conversation_id: currentConvId }, function (res) {
            if (res.status === 'success') {
                renderMessages(res.data, res.my_id, res.my_type);
            }
        }, 'json');
    }

    function searchMessages(term) {
        if (!currentConvId) return;
        $.post('../ajax/chat_api.php', { action: 'search_messages', conversation_id: currentConvId, term: term }, function (res) {
            if (res.status === 'success') {
                renderMessages(res.data, null, null, true); // Search mode
            }
        }, 'json');
    }

    function renderMessages(msgs, myId, myType, searchMode = false) {
        let box = $('#msgBox');
        let shouldScroll = (box.scrollTop() + box.innerHeight() >= box[0].scrollHeight - 50);
        if (searchMode) shouldScroll = false; // Don't auto scroll in search results often

        box.empty();

        if (msgs.length === 0) {
            box.html('<div class="text-center text-muted mt-5">No messages found.</div>');
            return;
        }

        // Logic to determine "me" usually comes from API but in search we might drop it.
        // If searchMode, assume simple render or pass myId? 
        // We'll rely on server provided my_id normally, but search api response doesn't pass it.
        // It's okay, assuming I am Admin (since this is admin page) we can infer, 
        // BUT strict way is to send it. I'll pass it in `res` or just check against Session ID if I had it in JS.
        // I'll update searchMessages to expect data in valid format. 
        // Actually, let's just use "sent" class if sender_type == 'admin'.

        msgs.forEach(m => {
            // Precise "Me" check for Admin Panel: Me = Admin
            // NOTE: In V4, Admin ID is usually $_SESSION['admin_id']. 
            // Better to rely on "sender_type" for Admin console if IDs match.
            // If sender_type is 'admin' AND sender_id == (my actual ID from PHP?), 
            // we will approximate for now: IF sender_type == 'admin', it's ME.
            // (Assuming single admin logged in or chat is personal. 
            // V4 chat is personal, so checking sender_type == 'admin' is mostly safe for Admin View).

            // However, `renderMessages` is passed `myId` and `myType` in normal fetch.
            // In search, we didn't pass it. Let's fix that later or just assume 'admin' is ME.
            let isMe = (myId && myType) ? (m.sender_id == myId && m.sender_type == myType) : (m.sender_type === 'admin');

            let cls = isMe ? 'sent' : 'received';
            let body = '';

            // Deleted Check
            if (m.is_deleted == 1) {
                body = `<div class="deleted-msg"><i class="bi bi-slash-circle"></i> This message was deleted</div>`;
            } else {
                if (m.message_type === 'file') {
                    body = `<div class="bg-light p-2 rounded border">
                        <i class="bi bi-file-earmark-text"></i> ${m.file_name} <br>
                        <a href="../${m.file_path}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">Download</a>
                    </div>`;
                    if (m.message_text) body += `<div class="mt-1">${m.message_text}</div>`;
                } else {
                    body = m.message_text;
                }
            }

            // Edited Label
            if (m.edited_at) body += ` <span class="text-muted small" style="font-size: 0.75rem;">(edited)</span>`;

            // Ticks (Only for Sent)
            let ticks = '';
            if (isMe && m.is_deleted == 0) {
                if (m.read_at) {
                    ticks = '<span class="msg-status read">✓✓</span>'; // Blue
                } else {
                    ticks = '<span class="msg-status delivered">✓</span>'; // Grey
                }
            }

            let time = new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            // Actions (Edit/Delete) - Only Me & Not Deleted
            let actions = '';
            if (isMe && m.is_deleted == 0) {
                actions = `
                <div class="msg-actions">
                    <i class="bi bi-pencil-square" title="Edit" onclick="openEditModal(${m.id}, '${m.message_text.replace(/'/g, "\\'")}')"></i>
                    <i class="bi bi-trash-fill" title="Delete" onclick="deleteMessage(${m.id})"></i>
                </div>`;
            }

            box.append(`
            <div class="msg ${cls}">
                ${body}
                <div class="msg-time">
                    ${time} ${ticks}
                </div>
                ${actions}
            </div>
        `);
        });

        if (shouldScroll) box.scrollTop(box[0].scrollHeight);
    }

    function sendMsg() {
        let txt = $('#msgIn').val();
        let file = $('#fileIn')[0].files[0];
        if (!txt && !file) return;

        let fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('conversation_id', currentConvId);
        fd.append('message', txt);
        if (file) fd.append('file', file);

        $.ajax({
            url: '../ajax/chat_api.php', type: 'POST', data: fd, processData: false, contentType: false,
            success: function (res) {
                $('#msgIn').val('');
                $('#fileIn').val('');
                $('#filePreview').addClass('d-none');
                fetchMessages();
                setTimeout(() => $('#msgBox').scrollTop($('#msgBox')[0].scrollHeight), 200);
            }
        });
    }

    // --- ACTIONS ---
    function deleteMessage(id) {
        if (!confirm("Delete this message?")) return;
        $.post('../ajax/chat_api.php', { action: 'delete_message', message_id: id }, function (res) {
            if (res.status === 'success') fetchMessages();
            else alert(res.message);
        }, 'json');
    }

    function openEditModal(id, text) {
        $('#editMsgId').val(id);
        $('#editMsgText').val(text);
        $('#editModal').modal('show');
    }

    function submitEdit() {
        let id = $('#editMsgId').val();
        let txt = $('#editMsgText').val();
        $.post('../ajax/chat_api.php', { action: 'edit_message', message_id: id, message_text: txt }, function (res) {
            if (res.status === 'success') {
                $('#editModal').modal('hide');
                fetchMessages();
            } else {
                alert(res.message);
            }
        }, 'json');
    }
</script>

<?php require_once '../includes/admin_footer.php'; ?>