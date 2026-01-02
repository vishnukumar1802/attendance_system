<?php
// employee/email.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];

// Get Employee Details for "From" field
$stmt = $pdo->prepare("SELECT first_name, last_name, employee_id FROM employees WHERE id = ?");
$stmt->execute([$emp_id]);
$me = $stmt->fetch();
$from_display = $me['first_name'] . ' ' . $me['last_name'] . ' (' . $me['employee_id'] . ')';

// Fetch potential recipients (Admins + Other Employees)
$admins = $pdo->query("SELECT id, username FROM admins")->fetchAll();
$employees = $pdo->query("SELECT id, first_name, last_name, employee_id FROM employees WHERE status='active' AND id != $emp_id ORDER BY first_name ASC")->fetchAll();

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Internal Email System</h1>
</div>

<div class="row">
    <!-- Sidebar / Folder List -->
    <div class="col-md-3 mb-3">
        <div class="list-group shadow-sm">
            <a href="#" class="list-group-item list-group-item-action active" id="btn-compose"
                onclick="showSection('compose')">
                <i class="bi bi-pencil-square me-2"></i> Compose
            </a>
            <a href="#" class="list-group-item list-group-item-action" id="btn-inbox" onclick="loadEmails('inbox')">
                <i class="bi bi-inbox me-2"></i> Inbox
            </a>
            <a href="#" class="list-group-item list-group-item-action" id="btn-sent" onclick="loadEmails('sent')">
                <i class="bi bi-send me-2"></i> Sent
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="col-md-9">

        <!-- COMPOSE SECTION -->
        <div id="section-compose" class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-fill me-2"></i>New Message</h5>
            </div>
            <div class="card-body">
                <form id="composeForm" onsubmit="sendEmail(event)">
                    <div class="mb-3">
                        <label class="form-label fw-bold">From:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($from_display); ?>"
                            readonly disabled>
                    </div>

                    <!-- Hidden Data for JS -->
                    <script>
                        <?php
                        $js_users = [];
                        foreach ($admins as $a) {
                            $js_users[] = [
                                'id' => 'admin_' . $a['id'],
                                'label' => 'Admin: ' . $a['username'],
                                'search' => strtolower('admin ' . $a['username'])
                            ];
                        }
                        foreach ($employees as $e) {
                            $js_users[] = [
                                'id' => 'employee_' . $e['id'],
                                'label' => $e['first_name'] . ' ' . $e['last_name'] . ' (' . $e['employee_id'] . ')',
                                'search' => strtolower($e['first_name'] . ' ' . $e['last_name'] . ' ' . $e['employee_id'])
                            ];
                        }
                        ?>
                        const allUsers = <?php echo json_encode($js_users); ?>;
                    </script>

                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold">To: <small class="text-muted fw-normal">(Enter Name or
                                ID)</small></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="to-input" class="form-control" placeholder="Type to search..."
                                autocomplete="off">
                            <input type="hidden" name="to[]" id="to-hidden" required>
                        </div>
                        <div id="to-suggestions" class="list-group position-absolute w-100 shadow"
                            style="display:none; z-index: 1000; max-height: 200px; overflow-y: auto;"></div>

                        <!-- Selected To Badge -->
                        <div id="to-badge" class="mt-2" style="display:none;">
                            <span class="badge bg-primary p-2 fs-6">
                                <span id="to-badge-text"></span>
                                <i class="bi bi-x-circle ms-2" style="cursor:pointer;" onclick="clearTo()"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3 text-end">
                        <span class="text-primary" style="cursor:pointer;"
                            onclick="$('#cc-container').toggle()">CC</span>
                        <span class="mx-2">|</span>
                        <span class="text-primary" style="cursor:pointer;"
                            onclick="$('#bcc-container').toggle()">BCC</span>
                    </div>

                    <!-- CC Container -->
                    <div id="cc-container" class="mb-3" style="display:none;">
                        <label class="form-label fw-bold">CC:</label>
                        <div class="border rounded p-2 bg-light d-flex flex-wrap gap-2" id="cc-chips">
                            <input type="text" id="cc-input" class="border-0 bg-transparent"
                                style="outline:none; min-width:150px;" placeholder="Add recipient..."
                                autocomplete="off">
                        </div>
                        <div id="cc-suggestions" class="list-group position-absolute shadow"
                            style="display:none; z-index: 1000; width: 300px; max-height: 200px; overflow-y: auto;">
                        </div>
                    </div>

                    <!-- BCC Container -->
                    <div id="bcc-container" class="mb-3" style="display:none;">
                        <label class="form-label fw-bold">BCC:</label>
                        <div class="border rounded p-2 bg-light d-flex flex-wrap gap-2" id="bcc-chips">
                            <input type="text" id="bcc-input" class="border-0 bg-transparent"
                                style="outline:none; min-width:150px;" placeholder="Add recipient..."
                                autocomplete="off">
                        </div>
                        <div id="bcc-suggestions" class="list-group position-absolute shadow"
                            style="display:none; z-index: 1000; width: 300px; max-height: 200px; overflow-y: auto;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="text" name="subject" class="form-control" required placeholder="Subject">
                    </div>

                    <div class="mb-3">
                        <textarea name="body" class="form-control" rows="8" required
                            placeholder="Message body..."></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="input-group">
                            <input type="file" name="attachment" class="form-control" id="attachFile">
                            <label class="input-group-text" for="attachFile"><i class="bi bi-paperclip"></i></label>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4"><i
                                class="bi bi-send-fill me-2"></i>Send</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- LIST SECTION (Inbox/Sent) -->
        <div id="section-list" class="card shadow-sm" style="display:none;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="list-title">Inbox</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="loadEmails(currentFolder)"><i
                        class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="email-list"></div>
            </div>
        </div>

        <!-- READ SECTION -->
        <div id="section-read" class="card shadow-sm" style="display:none;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Read Message</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="showSection('list')"><i
                        class="bi bi-arrow-left"></i> Back</button>
            </div>
            <div class="card-body" id="email-content"></div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let currentFolder = 'inbox';

    // --- Autocomplete Logic ---
    function setupAutocomplete(inputId, suggestionId, onSelect) {
        const input = document.getElementById(inputId);
        const box = document.getElementById(suggestionId);

        input.addEventListener('input', function () {
            const val = this.value.toLowerCase();
            box.innerHTML = '';
            if (val.length < 1) {
                box.style.display = 'none';
                return;
            }

            const matches = allUsers.filter(u => u.search.includes(val));
            if (matches.length === 0) {
                box.style.display = 'none';
                return;
            }

            matches.forEach(u => {
                const item = document.createElement('a');
                item.className = "list-group-item list-group-item-action";
                item.href = "#";
                item.textContent = u.label;
                item.onclick = (e) => {
                    e.preventDefault();
                    onSelect(u);
                    box.style.display = 'none';
                    input.value = '';
                };
                box.appendChild(item);
            });
            box.style.display = 'block';
        });

        // Hide on click outside
        document.addEventListener('click', (e) => {
            if (e.target !== input && e.target !== box) {
                box.style.display = 'none';
            }
        });
    }

    // TO Field (Single)
    setupAutocomplete('to-input', 'to-suggestions', (user) => {
        document.getElementById('to-hidden').value = user.id;
        document.getElementById('to-badge-text').textContent = user.label;
        document.getElementById('to-badge').style.display = 'block';
        document.getElementById('to-input').style.display = 'none'; // Hide input
    });

    function clearTo() {
        document.getElementById('to-hidden').value = '';
        document.getElementById('to-badge').style.display = 'none';
        document.getElementById('to-input').style.display = 'block';
        document.getElementById('to-input').focus();
    }

    // CC/BCC (Multiple Chips)
    function addChip(containerId, inputName, user) {
        const chip = document.createElement('div');
        chip.className = "badge bg-secondary d-flex align-items-center p-2";
        chip.innerHTML = `
            ${user.label} 
            <input type="hidden" name="${inputName}[]" value="${user.id}">
            <i class="bi bi-x ms-2" style="cursor:pointer;" onclick="this.parentElement.remove()"></i>
        `;
        // Insert before the input
        const container = document.getElementById(containerId);
        const input = container.querySelector('input[type="text"]');
        container.insertBefore(chip, input);
    }

    setupAutocomplete('cc-input', 'cc-suggestions', (user) => {
        // Prevent duplicates
        const existing = document.querySelectorAll(`input[name="cc[]"][value="${user.id}"]`);
        if (existing.length === 0) addChip('cc-chips', 'cc', user);
    });

    setupAutocomplete('bcc-input', 'bcc-suggestions', (user) => {
        const existing = document.querySelectorAll(`input[name="bcc[]"][value="${user.id}"]`);
        if (existing.length === 0) addChip('bcc-chips', 'bcc', user);
    });

    // --- Standard Nav ---

    function showSection(sec) {
        $('#section-compose').hide();
        $('#section-list').hide();
        $('#section-read').hide();

        $('#btn-compose').removeClass('active');
        $('#btn-inbox').removeClass('active');
        $('#btn-sent').removeClass('active');

        if (sec === 'compose') {
            $('#section-compose').show();
            $('#btn-compose').addClass('active');
        } else if (sec === 'list') {
            $('#section-list').show();
            if (currentFolder === 'inbox') $('#btn-inbox').addClass('active');
            else $('#btn-sent').addClass('active');
        } else if (sec === 'read') {
            $('#section-read').show();
        }
    }

    function loadEmails(folder) {
        currentFolder = folder;
        $('#list-title').text(folder.charAt(0).toUpperCase() + folder.slice(1));
        showSection('list');

        $('#email-list').html('<div class="p-4 text-center text-muted">Loading...</div>');

        $.get('../ajax/fetch_emails.php', { folder: folder }, function (res) {
            const data = (typeof res === 'string') ? JSON.parse(res) : res;
            const list = $('#email-list');
            list.empty();

            if (data.length === 0) {
                list.html('<div class="p-4 text-center text-muted">No emails found.</div>');
                return;
            }

            data.forEach(email => {
                let badge = '';
                if (folder === 'inbox' && email.is_read == 0) {
                    badge = '<span class="badge bg-primary ms-2">New</span>';
                }

                let otherParty = (folder === 'inbox') ? email.sender_name : 'To: ' + email.recipient_summary;

                const html = `
                    <a href="#" class="list-group-item list-group-item-action" onclick="readEmail(${email.id})">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold text-dark">${otherParty} ${badge}</h6>
                            <small class="text-muted">${email.created_at}</small>
                        </div>
                        <p class="mb-1 text-truncate fw-medium" style="max-width: 80%;">${email.subject}</p>
                        <small class="text-muted text-truncate" style="display:block; max-width:80%;">${email.snippet}</small>
                    </a>
                `;
                list.append(html);
            });
        });
    }

    function readEmail(id) {
        showSection('read');
        $('#email-content').html('<div class="p-4 text-center">Loading content...</div>');
        $.get('../ajax/read_email.php', { id: id }, function (res) {
            const data = (typeof res === 'string') ? JSON.parse(res) : res;

            let attachmentHtml = '';
            if (data.attachment) {
                attachmentHtml = `
                    <div class="mt-3 p-3 bg-light border rounded">
                        <i class="bi bi-paperclip me-2"></i> <strong>Attachment:</strong> 
                        <a href="../${data.attachment}" download="${data.attachment_name}">${data.attachment_name}</a>
                    </div>
                 `;
            }

            const html = `
                <div class="mb-3 border-bottom pb-3">
                    <h4 class="mb-2">${data.subject}</h4>
                    <div class="d-flex justify-content-between text-muted small">
                        <div>
                            <strong>From:</strong> ${data.sender_name}<br>
                            <strong>To:</strong> ${data.to_recipients}<br>
                            ${data.cc_recipients ? '<strong>CC:</strong> ' + data.cc_recipients : ''}
                        </div>
                        <div class="text-end">${data.created_at}</div>
                    </div>
                </div>
                <div class="email-body mb-4" style="white-space: pre-wrap;">${data.body}</div>
                ${attachmentHtml}
                <hr>
                <div class="mt-3">
                    <button class="btn btn-outline-primary" onclick="replyEmail(${data.sender_id}, '${data.sender_type}', '${data.subject}')">
                        <i class="bi bi-reply"></i> Reply
                    </button>
                </div>
             `;
            $('#email-content').html(html);
        });
    }

    function sendEmail(e) {
        e.preventDefault();
        const form = document.getElementById('composeForm');
        // Validate To
        if (!document.getElementById('to-hidden').value) {
            alert('Please select a "To" recipient from the list.');
            return;
        }

        const formData = new FormData(form);
        $.ajax({
            url: '../ajax/send_email.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                const data = (typeof res === 'string') ? JSON.parse(res) : res;
                if (data.status === 'success') {
                    alert('Email sent successfully!');
                    form.reset();
                    // Clear custom inputs
                    clearTo();
                    document.getElementById('cc-chips').innerHTML = '<input type="text" id="cc-input" class="border-0 bg-transparent" style="outline:none; min-width:150px;" placeholder="Add recipient..." autocomplete="off">';
                    document.getElementById('bcc-chips').innerHTML = '<input type="text" id="bcc-input" class="border-0 bg-transparent" style="outline:none; min-width:150px;" placeholder="Add recipient..." autocomplete="off">';
                    // Re-bind listeners for new inputs
                    setupAutocomplete('cc-input', 'cc-suggestions', (u) => {
                        const existing = document.querySelectorAll(`input[name="cc[]"][value="${u.id}"]`);
                        if (existing.length === 0) addChip('cc-chips', 'cc', u);
                    });
                    setupAutocomplete('bcc-input', 'bcc-suggestions', (u) => {
                        const existing = document.querySelectorAll(`input[name="bcc[]"][value="${u.id}"]`);
                        if (existing.length === 0) addChip('bcc-chips', 'bcc', u);
                    });

                    loadEmails('sent');
                } else {
                    alert('Error: ' + data.message);
                }
            },
            error: function () {
                alert('Failed to send email.');
            }
        });
    }

    function replyEmail(senderId, senderType, subject) {
        showSection('compose');
        const searchId = senderType + '_' + senderId;
        const user = allUsers.find(u => u.id === searchId);

        if (user) {
            // Set To
            document.getElementById('to-hidden').value = user.id;
            document.getElementById('to-badge-text').textContent = user.label;
            document.getElementById('to-badge').style.display = 'block';
            document.getElementById('to-input').style.display = 'none';
        }

        const subjInput = document.querySelector('input[name="subject"]');
        if (!subject.startsWith('Re:')) {
            subjInput.value = 'Re: ' + subject;
        } else {
            subjInput.value = subject;
        }
        document.querySelector('textarea[name="body"]').focus();
    }

    // Init
    loadEmails('inbox');
</script>

<?php require_once '../includes/emp_footer.php'; ?>