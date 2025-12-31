<?php
// admin/settings.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$message = '';

// Save Settings
if (isset($_POST['save_settings'])) {
    $lock_date = $_POST['attendance_lock_date'];

    // Using simple key-value update
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('attendance_lock_date', :val) ON DUPLICATE KEY UPDATE setting_value = :val");
    $stmt->execute(['val' => $lock_date]);

    $message = "Settings saved.";
}

// Fetch Current Settings
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'attendance_lock_date'");
$current_lock_date = $stmt->fetchColumn();
if (!$current_lock_date)
    $current_lock_date = '2000-01-01';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">System Settings</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width: 600px;">
    <div class="card-body">
        <form method="post">
            <h5 class="card-title mb-3">Attendance Lock</h5>
            <div class="mb-3">
                <label class="form-label">Lock Date</label>
                <input type="date" name="attendance_lock_date" class="form-control"
                    value="<?php echo $current_lock_date; ?>">
                <div class="form-text">Employees cannot mark attendance for this date or earlier.</div>
            </div>
            <button type="submit" name="save_settings" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>