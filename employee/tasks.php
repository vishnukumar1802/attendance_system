<?php
// employee/tasks.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];
$message = '';
$error = '';

// Update Status with Attachment
if (isset($_POST['update_status'])) {
    $tid = $_POST['task_id'];
    $st = $_POST['status'];

    // Check Requirement: If completed, MUST have attachment (existing or new)
    $proceed = true;
    if ($st === 'completed') {
        // Check existing
        $chkst = $pdo->prepare("SELECT attachment_path FROM tasks WHERE id = ?");
        $chkst->execute([$tid]);
        $has_existing = $chkst->fetchColumn();

        // Check new
        $has_new = (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK);

        if (empty($has_existing) && !$has_new) {
            $error = "Task cannot be completed without an attachment proof.";
            $proceed = false;
        }
    }

    if ($proceed) {
        $attachment_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/task_attachments/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            $file_name = time() . '_' . basename($_FILES['attachment']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                $attachment_path = 'uploads/task_attachments/' . $file_name;
            }
        }

        if ($attachment_path) {
            $stmt = $pdo->prepare("UPDATE tasks SET status = ?, attachment_path = ? WHERE id = ? AND assigned_to = ?");
            $stmt->execute([$st, $attachment_path, $tid, $emp_id]);
            $message = "Task completed with attachment.";
        } else {
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
            $stmt->execute([$st, $tid, $emp_id]);
            $message = "Task updated.";
        }
    }
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
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
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
                        <p class="card-text text-muted small mb-2">
                            Due: <?php echo date('M d, Y', strtotime($t['due_date'])); ?>
                        </p>

                        <!-- Progress Bar -->
                        <?php
                        $progress_val = match ($t['status']) {
                            'completed' => 100,
                            'in_progress' => 50,
                            default => 0
                        };
                        $progress_color = match ($t['status']) {
                            'completed' => 'bg-success',
                            'in_progress' => 'bg-info',
                            default => 'bg-secondary'
                        };
                        ?>
                        <div class="progress mb-3" style="height: 20px;">
                            <div class="progress-bar <?php echo $progress_color; ?>" role="progressbar"
                                style="width: <?php echo $progress_val; ?>%" aria-valuenow="<?php echo $progress_val; ?>"
                                aria-valuemin="0" aria-valuemax="100">
                                <?php echo $progress_val; ?>%
                            </div>
                        </div>

                        <p class="card-text bg-light p-2 rounded small">
                            <?php echo nl2br(htmlspecialchars($t['description'])); ?>
                        </p>

                        <?php if (!empty($t['attachment_path'])): ?>
                            <div class="mb-3">
                                <a href="../<?php echo $t['attachment_path']; ?>" class="btn btn-sm btn-outline-info" download>
                                    <i class="bi bi-paperclip"></i> Download Attachment
                                </a>
                            </div>
                        <?php endif; ?>

                        <form method="post" enctype="multipart/form-data" class="mt-3">
                            <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">

                            <div class="mb-2">
                                <label class="form-label small text-muted">Attach Document</label>
                                <input type="file" name="attachment" class="form-control form-control-sm">
                            </div>

                            <div class="input-group input-group-sm">
                                <select name="status" class="form-select">
                                    <option value="pending" <?php echo $t['status'] == 'pending' ? 'selected' : ''; ?>>Pending
                                    </option>
                                    <option value="in_progress" <?php echo $t['status'] == 'in_progress' ? 'selected' : ''; ?>>In
                                        Progress</option>
                                    <option value="completed" <?php echo $t['status'] == 'completed' ? 'selected' : ''; ?>>
                                        Completed
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