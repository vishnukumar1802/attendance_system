<?php
// admin/manage_admins.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

// Access Control: Only Super Admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'super_admin') {
    // Fallback check if session role isn't set yet (first login after update)
    // We can query DB to be sure or just redirect. 
    // Ideally login.php sets the session variable.
    // For safety, let's fetch current user role.
    $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $currentUser = $stmt->fetch();

    if (!$currentUser || $currentUser['role'] !== 'super_admin') {
        echo "<div class='alert alert-danger m-4'>Access Denied. Only Super Admins can manage other admins.</div>";
        require_once '../includes/admin_footer.php';
        exit;
    }
}

$success = '';
$error = '';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $username = clean_input($_POST['username']);
            $password = $_POST['password'];
            $role = $_POST['role'];

            if (empty($username) || empty($password)) {
                $error = "Username and Password are required.";
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (:u, :p, :r)");
                    $stmt->execute(['u' => $username, 'p' => $hash, 'r' => $role]);
                    $success = "New admin created successfully.";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            if ($id != $_SESSION['admin_id']) { // Check self-delete
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $success = "Admin removed.";
            } else {
                $error = "You cannot delete your own account.";
            }
        } elseif ($_POST['action'] == 'update_password') {
            $id = $_POST['id'];
            $new_pass = $_POST['password'];
            $username = clean_input($_POST['username']); // Allow username change

            if (!empty($new_pass)) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET username = :u, password = :p WHERE id = :id");
                $stmt->execute(['u' => $username, 'p' => $hash, 'id' => $id]);
                $success = "Credentials updated.";
            } else {
                // Update username only
                $stmt = $pdo->prepare("UPDATE admins SET username = :u WHERE id = :id");
                $stmt->execute(['u' => $username, 'id' => $id]);
                $success = "Username updated.";
            }
        }
    }
}

$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Admins</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Create New Admin -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Create New Admin</h5>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="create">
            <div class="col-md-4">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="col-md-4">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="sub_admin">Sub Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- List Admins -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo $admin['id']; ?></td>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td>
                            <span class="badge <?php echo $admin['role'] == 'super_admin' ? 'bg-danger' : 'bg-info'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                            </span>
                        </td>
                        <td><?php echo $admin['created_at']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary"
                                onclick="editAdmin(<?php echo $admin['id']; ?>, '<?php echo $admin['username']; ?>')">
                                Edit
                            </button>
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin Credentials</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editAdmin(id, username) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_username').value = username;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
</script>

<?php require_once '../includes/admin_footer.php'; ?>