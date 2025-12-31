<?php
// admin/tasks.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$message = '';

// Create Task
if (isset($_POST['create_task'])) {
    $title = clean_input($_POST['title']);
    $desc = clean_input($_POST['description']);
    $emp_id = $_POST['assigned_to'];
    $cat = $_POST['category'];
    $due = $_POST['due_date'];
    $by = $_SESSION['admin_db_id']; // Assuming admin_db_id is set in login

    if ($due < date('Y-m-d')) {
        $message = "Error: Due date cannot be in the past.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, category, due_date) VALUES (:t, :d, :at, :by, :c, :due)");
        if ($stmt->execute(['t' => $title, 'd' => $desc, 'at' => $emp_id, 'by' => $by, 'c' => $cat, 'due' => $due])) {

            // Notify
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                ->execute([$emp_id, "New Task Assigned: $title"]);

            $message = "Task assigned successfully.";
        }
    }
}

// Fetch Tasks
$sql = "SELECT t.*, e.first_name, e.last_name FROM tasks t JOIN employees e ON t.assigned_to = e.id ORDER BY t.status ASC, t.due_date ASC";
$tasks = $pdo->query($sql)->fetchAll();

// Employees for Dropdown
$emps = $pdo->query("SELECT id, first_name, last_name FROM employees WHERE status='active'")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Task Management</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Assign Task</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="form-select" required>
                            <?php foreach ($emps as $e): ?>
                                <option value="<?php echo $e['id']; ?>">
                                    <?php echo $e['first_name'] . ' ' . $e['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="development">Development</option>
                            <option value="testing">Testing</option>
                            <option value="design">Design</option>
                            <option value="meeting">Meeting</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" min="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" name="create_task" class="btn btn-primary w-100">Assign Task</button>
                </form>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $t): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($t['title']); ?></div>
                                        <span
                                            class="badge bg-light text-dark border"><?php echo ucfirst($t['category']); ?></span>
                                    </td>
                                    <td><?php echo $t['first_name'] . ' ' . $t['last_name']; ?></td>
                                    <td>
                                        <?php
                                        $d = new DateTime($t['due_date']);
                                        echo $d->format('M d');
                                        if ($d < new DateTime() && $t['status'] != 'completed') {
                                            echo ' <span class="text-danger small">(Overdue)</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $cls = match ($t['status']) {
                                            'completed' => 'bg-success',
                                            'in_progress' => 'bg-info text-dark',
                                            default => 'bg-warning text-dark'
                                        };
                                        ?>
                                        <span
                                            class="badge <?php echo $cls; ?>"><?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>