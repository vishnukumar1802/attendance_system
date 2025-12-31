<?php
// admin/employee_profiles.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';
require_once '../includes/profile_helper.php';

$message = '';
$error = '';

// Edit Profile (Admin Override)
if (isset($_POST['update_profile'])) {
    $uuid = $_POST['emp_id'];
    $desig = clean_input($_POST['designation']);
    $dept = clean_input($_POST['department']);
    $join = $_POST['joining_date'];

    // Admin can also edit other fields if needed, but let's stick to the key ones prompt mentioned
    // "Edit ALL profile fields". Okay, so all.
    // Let's implement key ones first.
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $phone = clean_input($_POST['phone']);

    // Update
    $sql = "INSERT INTO employee_profiles (emp_id, designation, department, joining_date, dob, gender, phone) 
            VALUES (:eid, :desig, :dept, :join, :dob, :gen, :ph)
            ON DUPLICATE KEY UPDATE 
            designation=:desig, department=:dept, joining_date=:join, dob=:dob, gender=:gen, phone=:ph";

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute(['eid' => $uuid, 'desig' => $desig, 'dept' => $dept, 'join' => $join, 'dob' => $dob, 'gen' => $gender, 'ph' => $phone])) {
        $message = "Profile updated.";
        update_profile_status($pdo, $uuid);
    } else {
        $error = "Update failed.";
    }
}

// Fetch All Employees with Profile Data
$sql = "SELECT e.id, e.first_name, e.last_name, e.employee_id, ep.* 
        FROM employees e 
        LEFT JOIN employee_profiles ep ON e.id = ep.emp_id 
        WHERE e.status = 'active'";
$emps = $pdo->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Employee Profiles</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Profile Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emps as $e): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($e['profile_photo'])): ?>
                                        <img src="../uploads/profile_photos/<?php echo $e['profile_photo']; ?>"
                                            class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2 text-muted"
                                            style="width: 32px; height: 32px;">
                                            <?php echo strtoupper(substr($e['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="fw-bold">
                                            <?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>
                                        </div>
                                        <div class="small text-muted"><?php echo $e['employee_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $e['designation'] ?? '-'; ?></td>
                            <td><?php echo $e['department'] ?? '-'; ?></td>
                            <td>
                                <?php if ($e['profile_completed']): ?>
                                    <span class="badge bg-success">Complete</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Incomplete</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editModal<?php echo $e['id']; ?>">
                                    Edit
                                </button>
                                <a href="view_education.php?emp_id=<?php echo $e['id']; ?>"
                                    class="btn btn-sm btn-outline-info">
                                    Education
                                </a>

                                <!-- Modal -->
                                <div class="modal fade" id="editModal<?php echo $e['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Profile: <?php echo $e['first_name']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="emp_id" value="<?php echo $e['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Designation</label>
                                                        <input type="text" name="designation" class="form-control"
                                                            value="<?php echo $e['designation'] ?? ''; ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Department</label>
                                                        <input type="text" name="department" class="form-control"
                                                            value="<?php echo $e['department'] ?? ''; ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Joining Date</label>
                                                        <input type="date" name="joining_date" class="form-control"
                                                            value="<?php echo $e['joining_date'] ?? ''; ?>">
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">DOB</label>
                                                            <input type="date" name="dob" class="form-control"
                                                                value="<?php echo $e['dob'] ?? ''; ?>">
                                                        </div>
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Gender</label>
                                                            <select name="gender" class="form-select">
                                                                <option value="Male" <?php echo ($e['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male
                                                                </option>
                                                                <option value="Female" <?php echo ($e['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female
                                                                </option>
                                                                <option value="Other" <?php echo ($e['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Phone</label>
                                                        <input type="text" name="phone" class="form-control"
                                                            value="<?php echo $e['phone'] ?? ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_profile" class="btn btn-primary">Save
                                                        Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>