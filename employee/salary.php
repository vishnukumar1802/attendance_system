<?php
// employee/salary.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';
?>

<div class="d-flex justify-content-center align-items-center" style="height: 60vh;">
    <div class="text-center">
        <i class="bi bi-shield-lock-fill text-danger" style="font-size: 5rem;"></i>
        <h1 class="mt-3 text-danger">Access Restricted</h1>
        <p class="lead text-muted">You do not have permission to view salary details directly.</p>
        <a href="dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
    </div>
</div>

<?php require_once '../includes/emp_footer.php'; ?>