<?php
// employee/team.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];
$message = '';
$error = '';

/**
 * 🔐 PERMISSION CHECK HELPER
 * Returns true if the logged-in user is a sub-admin of the specified team
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

/**
 * 🛠️ HANDLE ACTIONS (Add/Remove Member)
 */
// 1. Add Member (Sub-Admin Only)
if (isset($_POST['add_member'])) {
    $target_team = $_POST['team_id'];
    $target_emp = $_POST['new_emp_id'];

    if (is_sub_admin($pdo, $target_team, $emp_id)) {
        // Validation: Is user already in team?
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
        $error = "Unauthorized: You are not an admin of this team.";
    }
}

// 2. Remove Member (Sub-Admin Only)
if (isset($_POST['remove_member'])) {
    $target_mem_id = $_POST['mem_id'];
    $target_team = $_POST['team_id'];

    if (is_sub_admin($pdo, $target_team, $emp_id)) {
        // Prevent removing self (optional, but good UX)
        // Prevent removing other Sub-Admins? (Prompt says "Sub-admin can remove employees". 
        // It doesn't explicitly forbid removing other sub-admins, but usually that's safer.
        // Let's Check target role.
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

// Prepare list of ALL employees for the "Add Member" dropdown (Available to sub-admins)
// We fetch this only if user is sub_admin of at least one team to save query, 
// but simple enough to just fetch.
$all_emps = $pdo->query("SELECT id, first_name, last_name, employee_id FROM employees WHERE status='active' ORDER BY first_name ASC")->fetchAll();

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Teams</h1>
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

        // Fetch Members
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

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0 text-primary">
                    <i class="bi bi-people-fill me-2"></i><?php echo htmlspecialchars($team['name']); ?>
                </h4>
                <span class="badge <?php echo $is_sub ? 'bg-warning text-dark' : 'bg-secondary'; ?> fs-6">
                    <?php echo ucfirst($team['my_role'] == 'sub_admin' ? 'Team Admin' : 'Member'); ?>
                </span>
            </div>

            <!-- SUB-ADMIN CONTROLS: ADD MEMBER -->
            <?php if ($is_sub): ?>
                <div class="card-body border-bottom bg-light">
                    <h6 class="fw-bold mb-3"><i class="bi bi-person-plus-fill me-2"></i>Add Member</h6>
                    <form method="post" class="row g-2 align-items-center">
                        <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                        <div class="col-8 col-md-4">
                            <select name="new_emp_id" class="form-select" required>
                                <option value="">Select Employee...</option>
                                <?php foreach ($all_emps as $e): ?>
                                    <option value="<?php echo $e['id']; ?>">
                                        <?php echo $e['first_name'] . ' ' . $e['last_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <button type="submit" name="add_member" class="btn btn-primary">Add</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($members as $m): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <!-- Avatar -->
                                <div class="avatar-placeholder rounded-circle bg-light d-flex align-items-center justify-content-center me-3 text-secondary"
                                    style="width: 45px; height: 45px; font-weight: bold;">
                                    <?php
                                    if (!empty($m['profile_photo'])) {
                                        echo "<img src='../uploads/profile_photos/{$m['profile_photo']}' class='rounded-circle w-100 h-100' style='object-fit:cover'>";
                                    } else {
                                        echo substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1);
                                    }
                                    ?>
                                </div>

                                <div>
                                    <h6 class="mb-0 fw-bold">
                                        <?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?>
                                        <?php if ($m['emp_id'] == $emp_id)
                                            echo " (You)"; ?>
                                    </h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($m['designation']); ?></small>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <?php if ($m['role'] == 'sub_admin'): ?>
                                    <span class="badge bg-warning text-dark me-3">Admin</span>
                                <?php endif; ?>

                                <!-- SUB-ADMIN ACTIONS: REMOVE -->
                                <?php if ($is_sub && $m['emp_id'] != $emp_id && $m['role'] != 'sub_admin'): ?>
                                    <form method="post"
                                        onsubmit="return confirm('Remove <?php echo $m['first_name']; ?> from Group?');">
                                        <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                        <input type="hidden" name="mem_id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" name="remove_member" class="btn btn-sm btn-outline-danger"
                                            title="Remove Member">
                                            <i class="bi bi-person-dash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php require_once '../includes/emp_footer.php'; ?>