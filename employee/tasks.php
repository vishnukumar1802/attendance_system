<?php
// employee/tasks.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];
$message = '';

// Update Status
if (isset($_POST['update_status'])) {
    $tid = $_POST['task_id'];
    $st = $_POST['status'];
    $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?")->execute([$st, $tid, $emp_id]);
    $message = "Task updated.";

    // Notify Admin? Maybe later.
}

// Fetch My Tasks
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE assigned_to = ? ORDER BY status ASC, due_date ASC");
$stmt->execute([$emp_id]);
$tasks = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Tasks</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <?php if (count($tasks) > 0): ?>
        <?php foreach ($tasks as $t): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-secondary"><?php echo ucfirst($t['category']); ?></span>
                            <?php
                            $status_cls = match ($t['status']) {
                                'completed' => 'text-success',
                                'in_progress' => 'text-info',
                                default => 'text-warning'
                            };
                            ?>
                            <span class="fw-bold <?php echo $status_cls; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?>
                            </span>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($t['title']); ?></h5>
                        <p class="card-text text-muted small mb-3">
                            Due: <?php echo date('M d, Y', strtotime($t['due_date'])); ?>
                        </p>
                        <p class="card-text bg-light p-2 rounded small">
                            <?php echo nl2br(htmlspecialchars($t['description'])); ?>
                        </p>

                        <form method="post" class="mt-3">
                            <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                            <div class="input-group input-group-sm">
                                <select name="status" class="form-select">
                                    <option value="pending" <?php echo $t['status'] == 'pending' ? 'selected' : ''; ?>>Pending
                                    </option>
                                    <option value="in_progress" <?php echo $t['status'] == 'in_progress' ? 'selected' : ''; ?>>In
                                        Progress</option>
                                    <option value="completed" <?php echo $t['status'] == 'completed' ? 'selected' : ''; ?>>Completed
                                    </option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-outline-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5 text-muted">
            <h4>No tasks assigned.</h4>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/emp_footer.php'; ?>