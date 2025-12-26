<?php
// admin/employees.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

// Handle Action (Deactivate/Activate)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    $status = ($action == 'deactivate') ? 'inactive' : 'active';

    $stmt = $pdo->prepare("UPDATE employees SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $status, 'id' => $id]);

    // Redirect to remove query params
    echo "<script>window.location.href='employees.php';</script>";
}

// Fetch Employees
$stmt = $pdo->query("SELECT * FROM employees ORDER BY created_at DESC");
$employees = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Employees</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="employee_form.php" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Employee
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Salary/Day</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($employees) > 0): ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-placeholder me-2 rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold"
                                            style="width: 32px; height: 32px;">
                                            <?php echo strtoupper(substr($emp['first_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                            </div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($emp['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                                <td><?php echo format_money($emp['salary_per_day']); ?></td>
                                <td>
                                    <?php if ($emp['status'] == 'active'): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="employee_form.php?id=<?php echo $emp['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($emp['status'] == 'active'): ?>
                                        <a href="employees.php?action=deactivate&id=<?php echo $emp['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to deactivate this employee?');">
                                            <i class="bi bi-person-x"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="employees.php?action=activate&id=<?php echo $emp['id']; ?>"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Activate this employee?');">
                                            <i class="bi bi-person-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">No employees found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>