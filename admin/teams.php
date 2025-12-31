<?php
// admin/teams.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$message = '';
$error = '';

/**
 * LOGIC HANDLERS
 */

// 1. Create Team
if (isset($_POST['create_team'])) {
    $name = clean_input($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO teams (name, created_by_admin) VALUES (?, ?)");
        if ($stmt->execute([$name, $_SESSION['admin_id'] ?? 1])) {
            $message = "Team '$name' created successfully.";
        } else {
            $error = "Failed to create team.";
        }
    }
}

// 2. Add Member to Team (Admin)
if (isset($_POST['add_member'])) {
    $team_id = $_POST['team_id'];
    $emp_id = $_POST['emp_id'];

    // Check if already in team
    $check = $pdo->prepare("SELECT id FROM team_members WHERE team_id = ? AND emp_id = ?");
    $check->execute([$team_id, $emp_id]);
    if ($check->rowCount() > 0) {
        $error = "Employee is already in this team.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO team_members (team_id, emp_id, role, added_by) VALUES (?, ?, 'member', 0)"); // 0 = Admin
        if ($stmt->execute([$team_id, $emp_id])) {
            $message = "Member added successfully.";
        } else {
            $error = "Failed to add member.";
        }
    }
}

// 3. Remove Member
if (isset($_POST['remove_member'])) {
    $mem_id = $_POST['mem_id']; // ID from team_members table
    $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
    if ($stmt->execute([$mem_id])) {
        $message = "Member removed.";
    } else {
        $error = "Failed to remove member.";
    }
}

// 4. Toggle Sub-Admin Role
if (isset($_POST['toggle_role'])) {
    $mem_id = $_POST['mem_id'];
    $current_role = $_POST['current_role'];
    $new_role = ($current_role == 'member') ? 'sub_admin' : 'member';

    $stmt = $pdo->prepare("UPDATE team_members SET role = ? WHERE id = ?");
    if ($stmt->execute([$new_role, $mem_id])) {
        $message = "Role updated to " . ucfirst($new_role) . ".";
    } else {
        $error = "Failed to update role.";
    }
}

// 5. Delete Team
if (isset($_POST['delete_team'])) {
    $tid = $_POST['team_id'];
    $pdo->prepare("DELETE FROM teams WHERE id = ?")->execute([$tid]);
    $message = "Team deleted.";
}

/**
 * FETCH DATA
 */
// Get all teams
$teams = $pdo->query("SELECT * FROM teams ORDER BY name ASC")->fetchAll();

// Get all employees for "Add Member" dropdown (Available employees)
// We fetch ALL employees, then in UI filter those not in selected team, or simpler: select all and handle duplicates on insert (we did that).
$emps = $pdo->query("SELECT id, first_name, last_name, employee_id FROM employees WHERE status='active' ORDER BY first_name ASC")->fetchAll();

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Team Management</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- CREATE TEAM START -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Create New Team</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Team Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Marketing" required>
                    </div>
                    <button type="submit" name="create_team" class="btn btn-primary w-100">Create Team</button>
                </form>
            </div>
        </div>
    </div>
    <!-- CREATE TEAM END -->

    <!-- TEAM LIST START -->
    <div class="col-md-8">
        <?php foreach ($teams as $team): ?>
            <?php
            // Fetch Members for this team
            $stmt = $pdo->prepare("
                SELECT tm.*, e.first_name, e.last_name, e.employee_id, ep.profile_photo 
                FROM team_members tm 
                JOIN employees e ON tm.emp_id = e.id 
                LEFT JOIN employee_profiles ep ON e.id = ep.emp_id
                WHERE tm.team_id = ? 
                ORDER BY tm.role DESC, e.first_name ASC
            "); // Role DESC sorts sub_admin ('s') before member ('m')
            $stmt->execute([$team['id']]);
            $members = $stmt->fetchAll();
            ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="mb-0 text-primary"><?php echo htmlspecialchars($team['name']); ?></h5>
                    <div>
                        <span class="badge bg-secondary"><?php echo count($members); ?> Members</span>
                        <form method="post" class="d-inline ms-2"
                            onsubmit="return confirm('Delete this team? All memberships will be removed.');">
                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                            <button type="submit" name="delete_team" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- ADD MEMBER -->
                    <div class="p-3 border-bottom bg-white">
                        <form method="post" class="row g-2">
                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                            <div class="col-auto flex-grow-1">
                                <select name="emp_id" class="form-select form-select-sm" required>
                                    <option value="">Select Employee to Add...</option>
                                    <?php foreach ($emps as $e): ?>
                                        <option value="<?php echo $e['id']; ?>">
                                            <?php echo $e['first_name'] . ' ' . $e['last_name']; ?>
                                            (<?php echo $e['employee_id']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="add_member" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-lg"></i> Add
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- MEMBERS LIST -->
                    <ul class="list-group list-group-flush">
                        <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $m): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <!-- Photo -->
                                        <?php if (!empty($m['profile_photo'])): ?>
                                            <img src="../uploads/profile_photos/<?php echo $m['profile_photo']; ?>"
                                                class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2 text-muted fw-bold"
                                                style="width: 32px; height: 32px; font-size: 12px;">
                                                <?php echo substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <span
                                                class="fw-bold"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></span>
                                            <?php if ($m['role'] == 'sub_admin'): ?>
                                                <span class="badge bg-warning text-dark ms-1">Admin</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Role Toggle -->
                                        <form method="post">
                                            <input type="hidden" name="mem_id" value="<?php echo $m['id']; ?>">
                                            <input type="hidden" name="current_role" value="<?php echo $m['role']; ?>">
                                            <?php if ($m['role'] == 'member'): ?>
                                                <button type="submit" name="toggle_role" class="btn btn-sm btn-outline-secondary"
                                                    title="Promote to Sub-Admin">
                                                    <i class="bi bi-arrow-up-circle"></i> Promote
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="toggle_role" class="btn btn-sm btn-outline-warning"
                                                    title="Demote to Member">
                                                    <i class="bi bi-arrow-down-circle"></i> Demote
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Remove -->
                                        <form method="post" onsubmit="return confirm('Remove from team?');">
                                            <input type="hidden" name="mem_id" value="<?php echo $m['id']; ?>">
                                            <button type="submit" name="remove_member" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted text-center py-3">No members in this team.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- TEAM LIST END -->
</div>

<?php require_once '../includes/admin_footer.php'; ?>