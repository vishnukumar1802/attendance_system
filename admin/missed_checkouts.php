<?php
// admin/missed_checkouts.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$message = '';

// Handle Action
if (isset($_POST['update_status'])) {
    $att_id = $_POST['id'];
    $new_status = $_POST['status']; // 'present', 'half_day', 'absent'

    // Update status and maybe set a default checkout time if present/half_day?
    // If half_day is chosen, we might want to ensure checkout time exists or leave it null?
    // Let's set checkout time to 18:00:00 (6 PM) if marked present, just to close the loop, 
    // or keep it NULL but status is final.
    // The prompt says "Handle Missed Check-Out ... mark attendance as PENDING".
    // Admin can approve full/half or deduct.

    $stmt = $pdo->prepare("UPDATE attendance SET status = :st WHERE id = :id");
    if ($stmt->execute(['st' => $new_status, 'id' => $att_id])) {
        $message = "Attendance updated to " . ucfirst($new_status);
    }
}

// Fetch Missed Checkouts (Date < Today AND Check_out IS NULL)
// Exclude 'rejected' or 'absent' if they are somehow there without checkout (unlikely but possible).
$sql = "SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        WHERE a.check_out_time IS NULL 
        AND a.date < CURDATE() 
        AND a.status != 'absent' 
        AND a.status != 'rejected'
        ORDER BY a.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$missed = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Missed Check-Outs</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Check-In Time</th>
                        <th>Current Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($missed) > 0): ?>
                        <?php foreach ($missed as $row): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($row['emp_code']); ?></div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['check_in_time'])); ?></td>
                                <td>
                                    <span class="badge bg-warning text-dark"><?php echo ucfirst($row['status']); ?></span>
                                </td>
                                <td>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="status" value="present" class="btn btn-sm btn-success"
                                            title="Mark Full Day">
                                            <i class="bi bi-check-circle"></i> Full
                                        </button>
                                        <button type="submit" name="status" value="half_day" class="btn btn-sm btn-warning"
                                            title="Mark Half Day">
                                            <i class="bi bi-hourglass-split"></i> Half
                                        </button>
                                        <button type="submit" name="status" value="absent" class="btn btn-sm btn-danger"
                                            title="Mark Absent">
                                            <i class="bi bi-x-circle"></i> Absent
                                        </button>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No missed check-outs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>