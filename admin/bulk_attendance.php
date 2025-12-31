<?php
// admin/bulk_attendance.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$message = '';
$date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');

// Handle Bulk Update
if (isset($_POST['bulk_update'])) {
    $status = $_POST['status'];
    $selected_emps = isset($_POST['emp_ids']) ? $_POST['emp_ids'] : [];

    if (count($selected_emps) > 0) {
        $count = 0;
        foreach ($selected_emps as $eid) {
            // Check if exists
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status, check_in_time) VALUES (:eid, :date, :status, NOW()) 
                                   ON DUPLICATE KEY UPDATE status = :status");
            // Note: If using ON DUPLICATE, check_in_time isn't updated if validation fails?
            // Actually if we insert, check_in_time is NOW(). If we update, we only update status.
            $stmt->execute(['eid' => $eid, 'date' => $date, 'status' => $status]);
            $count++;
        }
        $message = "Updated $count employees to '$status' for $date.";
    } else {
        $message = "No employees selected.";
    }
}

// Fetch Employees who DO NOT have attendance for this date? 
// Or just List All Active Employees and show their current status if any.
$sql = "SELECT e.id, e.first_name, e.last_name, e.employee_id, a.status as current_status 
        FROM employees e 
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = :date 
        WHERE e.status = 'active' 
        ORDER BY e.first_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['date' => $date]);
$employees = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Bulk Attendance Update</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<form method="post">
    <!-- Filters -->
    <div class="row mb-4 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="<?php echo $date; ?>"
                onchange="this.form.submit()">
        </div>
        <div class="col-md-3">
            <label class="form-label">Mark As</label>
            <select name="status" class="form-select">
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="half_day">Half Day</option>
                <option value="holiday">Holiday</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" name="bulk_update" class="btn btn-primary w-100">Update Selected</button>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" class="form-check-input" onclick="toggleAll(this)">
                            </th>
                            <th>Employee</th>
                            <th>Current Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="emp_ids[]" value="<?php echo $emp['id']; ?>"
                                        class="form-check-input emp-checkbox">
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                    <small class="text-muted d-block"><?php echo $emp['employee_id']; ?></small>
                                </td>
                                <td>
                                    <?php if ($emp['current_status']): ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($emp['current_status']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<script>
    function toggleAll(source) {
        checkboxes = document.getElementsByClassName('emp-checkbox');
        for (var i = 0, n = checkboxes.length; i < n; i++) {
            checkboxes[i].checked = source.checked;
        }
    }
</script>

<?php require_once '../includes/admin_footer.php'; ?>