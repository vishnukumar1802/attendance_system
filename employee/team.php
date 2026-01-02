<?php
// employee/team.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];
$message = '';
$error = '';

/**
 * 🔐 PERMISSION CHECK HELPER
 */
function is_sub_admin($pdo, $team_id, $emp_id)
{
    if (!$team_id)
        return false;
    $stmt = $pdo->prepare("SELECT role FROM team_members WHERE team_id = ? AND emp_id = ?");
    $stmt->execute([$team_id, $emp_id]);
    $role = $stmt->fetchColumn();
    return ($role === 'sub_admin');
}

// ... (Existing Post Handlers for Add/Remove Members - KEEP AS IS)

// 1. Add Member (Sub-Admin Only)
if (isset($_POST['add_member'])) {
    $target_team = $_POST['team_id'];
    $target_emp = $_POST['new_emp_id'];

    if (is_sub_admin($pdo, $target_team, $emp_id)) {
        $check = $pdo->prepare("SELECT id FROM team_members WHERE team_id = ? AND emp_id = ?");
        $check->execute([$target_team, $target_emp]);

        if ($check->rowCount() == 0) {
            $ins = $pdo->prepare("INSERT INTO team_members (team_id, emp_id, role, added_by) VALUES (?, ?, 'member', ?)");
            if ($ins->execute([$target_team, $target_emp, $emp_id])) {
                $message = "Member added successfully.";
            } else {
                $error = "Failed to add member.";
            }
        } else {
            $error = "User already in team.";
        }
    } else {
        $error = "Unauthorized.";
    }
}

// 2. Remove Member (Sub-Admin Only)
if (isset($_POST['remove_member'])) {
    $target_mem_id = $_POST['mem_id'];
    $target_team = $_POST['team_id'];

    if (is_sub_admin($pdo, $target_team, $emp_id)) {
        $t_chk = $pdo->prepare("SELECT role, emp_id FROM team_members WHERE id = ?");
        $t_chk->execute([$target_mem_id]);
        $target = $t_chk->fetch();

        if ($target) {
            if ($target['emp_id'] == $emp_id) {
                $error = "You cannot remove yourself.";
            } elseif ($target['role'] == 'sub_admin') {
                $error = "You cannot remove another Sub-Admin.";
            } else {
                $del = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
                $del->execute([$target_mem_id]);
                $message = "Member removed.";
            }
        }
    } else {
        $error = "Unauthorized.";
    }
}


/**
 * 📥 FETCH DATA
 */
// Get All Teams Current User Belongs To
$stmt = $pdo->prepare("
    SELECT t.id, t.name, tm.role as my_role 
    FROM teams t 
    JOIN team_members tm ON t.id = tm.team_id 
    WHERE tm.emp_id = ?
");
$stmt->execute([$emp_id]);
$my_teams = $stmt->fetchAll();

$all_emps = $pdo->query("SELECT id, first_name, last_name, employee_id FROM employees WHERE status='active' ORDER BY first_name ASC")->fetchAll();
?>

<style>
    .chat-window {
        height: 300px;
        overflow-y: auto;
        background: #f1f2f6;
        border: 1px solid #ced4da;
    }

    .chat-msg {
        font-size: 0.9rem;
        margin-bottom: 8px;
        padding: 5px 10px;
        border-radius: 8px;
        max-width: 80%;
    }

    .chat-msg.mine {
        background: #d1e7dd;
        margin-left: auto;
    }

    .chat-msg.others {
        background: #fff;
    }

    .chat-sender {
        font-size: 0.7rem;
        font-weight: bold;
        color: #6c757d;
        display: block;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Teams & Chat</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (count($my_teams) == 0): ?>
    <div class="alert alert-info">You are not part of any team.</div>
<?php else: ?>

    <?php foreach ($my_teams as $team): ?>
        <?php
        $is_sub = ($team['my_role'] == 'sub_admin');

        // Members list logic...
        $mstmt = $pdo->prepare("
                SELECT tm.*, e.first_name, e.last_name, e.employee_id, ep.designation, ep.profile_photo 
                FROM team_members tm 
                JOIN employees e ON tm.emp_id = e.id 
                LEFT JOIN employee_profiles ep ON e.id = ep.emp_id
                WHERE tm.team_id = ? 
                ORDER BY tm.role DESC, e.first_name ASC
            ");
        $mstmt->execute([$team['id']]);
        $members = $mstmt->fetchAll();
        ?>

        <div class="row mb-5">
            <!-- Left: Members & Controls -->
            <div class="col-lg-7 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="bi bi-people-fill me-2"></i><?php echo htmlspecialchars($team['name']); ?>
                        </h5>
                        <span class="badge <?php echo $is_sub ? 'bg-warning text-dark' : 'bg-secondary'; ?>">
                            <?php echo ucfirst($team['my_role'] == 'sub_admin' ? 'Team Admin' : 'Member'); ?>
                        </span>
                    </div>

                    <!-- SUB-ADMIN: ADD MEMBER -->
                    <?php if ($is_sub): ?>
                        <div class="p-3 bg-light border-bottom">
                            <form method="post" class="row g-2">
                                <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                <div class="col-8">
                                    <select name="new_emp_id" class="form-select form-select-sm" required>
                                        <option value="">Select Employee to Add...</option>
                                        <?php foreach ($all_emps as $e): ?>
                                            <option value="<?php echo $e['id']; ?>">
                                                <?php echo $e['first_name'] . ' ' . $e['last_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <button type="submit" name="add_member" class="btn btn-sm btn-primary w-100">Add</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($members as $m): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2"
                                            style="width: 32px; height: 32px; font-weight:bold; font-size:12px;">
                                            <?php echo substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <span
                                                class="fw-bold small"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></span>
                                            <?php if ($m['role'] == 'sub_admin'): ?>
                                                <span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">Admin</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($is_sub && $m['emp_id'] != $emp_id && $m['role'] != 'sub_admin'): ?>
                                        <form method="post" onsubmit="return confirm('Remove?');">
                                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                            <input type="hidden" name="mem_id" value="<?php echo $m['id']; ?>">
                                            <button type="submit" name="remove_member"
                                                class="btn btn-sm btn-outline-danger py-0 px-2">x</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right: Team Chat -->
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <i class="bi bi-chat-quote-fill me-2 text-success"></i> Team Chat
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div id="chat-box-<?php echo $team['id']; ?>" class="chat-window mb-3 p-2 rounded">
                            <div class="text-center text-muted small mt-4">Loading messages...</div>
                        </div>
                        <form onsubmit="sendTeamMessage(event, <?php echo $team['id']; ?>)">
                            <div class="input-group">
                                <label class="btn btn-outline-secondary" for="file-input-<?php echo $team['id']; ?>">
                                    <i class="bi bi-paperclip"></i>
                                </label>
                                <input type="file" id="file-input-<?php echo $team['id']; ?>" class="d-none"
                                    onchange="updateFileLabel(<?php echo $team['id']; ?>)">
                                <input type="text" id="msg-input-<?php echo $team['id']; ?>" class="form-control"
                                    placeholder="Type..." autocomplete="off">
                                <button class="btn btn-success" type="submit"><i class="bi bi-send"></i></button>
                            </div>
                            <div id="file-label-<?php echo $team['id']; ?>" class="small text-muted mt-1" style="display:none;">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const myId = <?php echo $emp_id; ?>;
    const teamLastIds = {}; // Track last msg ID per team

    function updateFileLabel(teamId) {
        const fileInput = document.getElementById(`file-input-${teamId}`);
        const label = document.getElementById(`file-label-${teamId}`);
        if (fileInput.files.length > 0) {
            label.textContent = "Attached: " + fileInput.files[0].name;
            label.style.display = 'block';
        } else {
            label.style.display = 'none';
        }
    }

    function escapeHtml(text) {
        if (!text) return "";
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function linkify(text) {
        var urlRegex = /(https?:\/\/[^\s]+)/g;
        return text.replace(urlRegex, function(url) {
            return '<a href="' + url + '" target="_blank">' + url + '</a>';
        });
    }

    function sendTeamMessage(e, teamId) {
        e.preventDefault();
        const input = document.getElementById(`msg-input-${teamId}`);
        const fileInput = document.getElementById(`file-input-${teamId}`);
        const text = input.value.trim();
        
        if (!text && fileInput.files.length === 0) return;

        const formData = new FormData();
        formData.append('team_id', teamId);
        formData.append('message', text);
        if (fileInput.files.length > 0) {
            formData.append('attachment', fileInput.files[0]);
        }

        $.ajax({
            url: '../ajax/send_team_message.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                input.value = '';
                fileInput.value = ''; 
                updateFileLabel(teamId);
                fetchTeamMessages(teamId); 
            },
            error: function(err) {
                console.error("Send failed", err);
            }
        });
    }

    function fetchTeamMessages(teamId) {
        if (!teamLastIds[teamId]) teamLastIds[teamId] = 0;

        $.get('../ajax/fetch_team_messages.php', { team_id: teamId, last_id: teamLastIds[teamId] }, function (res) {
            const data = (typeof res === 'string') ? JSON.parse(res) : res;
            const box = document.getElementById(`chat-box-${teamId}`);

            if (teamLastIds[teamId] === 0) box.innerHTML = ''; // Clear loading

            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    teamLastIds[teamId] = msg.id;
                    const isMine = (msg.sender_id == myId);
                    const cls = isMine ? 'mine' : 'others'; 
                    
                    let msgContent = linkify(escapeHtml(msg.message));
                    if (msg.attachment) {
                        if (msgContent) msgContent += '<br>';
                        msgContent += `<small><a href="../${msg.attachment}" class="text-decoration-none" download="${msg.attachment_name}" target="_blank">
                            <i class="bi bi-paperclip"></i> ${msg.attachment_name}
                        </a></small>`;
                    }

                    const html = `
                        <div class="chat-msg ${cls}">
                            <span class="chat-sender">${isMine ? 'You' : msg.first_name}</span>
                            ${msgContent}
                        </div>
                    `;
                    box.innerHTML += html;
                });
                box.scrollTop = box.scrollHeight;
            }
        });
    }

    // Initialize & Poll
    <?php foreach ($my_teams as $t): ?>
        setInterval(() => fetchTeamMessages(<?php echo $t['id']; ?>), 3000);
        fetchTeamMessages(<?php echo $t['id']; ?>);
    <?php endforeach; ?>
</script>

<?php require_once '../includes/emp_footer.php'; ?>