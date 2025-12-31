<?php
// admin/manage_holidays.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$message = '';
$error = '';

// Add Holiday
if (isset($_POST['add_holiday'])) {
    $name = clean_input($_POST['name']);
    $date = $_POST['date'];

    try {
        $stmt = $pdo->prepare("INSERT INTO holidays (name, date) VALUES (:name, :date)");
        $stmt->execute(['name' => $name, 'date' => $date]);
        $message = "Holiday added successfully.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage(); // duplicate likely
    }
}

// Delete Holiday
if (isset($_POST['delete_holiday'])) {
    $id = $_POST['id'];
    $pdo->prepare("DELETE FROM holidays WHERE id = ?")->execute([$id]);
    $message = "Holiday deleted.";
}

// Fetch Holidays
$holidays = $pdo->query("SELECT * FROM holidays ORDER BY date DESC")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Holiday Management</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Add Holiday</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Holiday Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <button type="submit" name="add_holiday" class="btn btn-primary w-100">Add Holiday</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($holidays as $h): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($h['date'])); ?></td>
                                <td><?php echo htmlspecialchars($h['name']); ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Delete this holiday?');">
                                        <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                                        <button type="submit" name="delete_holiday" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>