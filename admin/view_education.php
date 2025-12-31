<?php
// admin/view_education.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

if (!isset($_GET['emp_id'])) {
    header("Location: employee_profiles.php");
    exit;
}

$emp_id = $_GET['emp_id'];

// Fetch Employee Name
$stmt = $pdo->prepare("SELECT first_name, last_name, profile_photo, employee_id FROM employees WHERE id = ?");
$stmt->execute([$emp_id]);
$emp = $stmt->fetch();

if (!$emp) {
    die("Employee not found.");
}

// Fetch Education
$stmt = $pdo->prepare("
    SELECT e.*, c.certificate_file 
    FROM employee_education e 
    LEFT JOIN education_certificates c ON e.id = c.education_id 
    WHERE e.emp_id = ? 
    ORDER BY e.year_of_passing DESC
");
$stmt->execute([$emp_id]);
$eds = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Education Details: <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h1>
    <a href="employee_profiles.php" class="btn btn-outline-secondary">Back to Profiles</a>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm text-center p-3">
            <div class="mb-3 mx-auto">
                <?php if (!empty($emp['profile_photo']) && file_exists("../uploads/profile_photos/" . $emp['profile_photo'])): ?>
                    <img src="../uploads/profile_photos/<?php echo $emp['profile_photo']; ?>"
                        class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto"
                        style="width: 150px; height: 150px;">
                        <span class="text-muted">No Photo</span>
                    </div>
                <?php endif; ?>
            </div>
            <h4><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h4>
            <p class="text-muted"><?php echo $emp['employee_id']; ?></p>
        </div>
    </div>

    <div class="col-md-8">
        <h5 class="mb-3">Education Records</h5>
        <?php if (count($eds) > 0): ?>
            <?php foreach ($eds as $ed): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title text-primary"><?php echo $ed['qualification']; ?> -
                                <?php echo htmlspecialchars($ed['degree']); ?></h5>
                            <span class="badge bg-light text-dark border"><?php echo $ed['year_of_passing']; ?></span>
                        </div>
                        <h6 class="text-muted"><?php echo htmlspecialchars($ed['institution']); ?></h6>
                        <p class="small text-muted mb-2">
                            <?php echo htmlspecialchars($ed['university_or_board']); ?> |
                            Specialization: <?php echo htmlspecialchars($ed['specialization']); ?> |
                            Score: <?php echo $ed['percentage_or_cgpa']; ?>
                        </p>

                        <?php if ($ed['certificate_file']): ?>
                            <a href="../uploads/certificates/<?php echo $ed['certificate_file']; ?>" target="_blank"
                                class="btn btn-sm btn-info text-white">
                                <i class="bi bi-download me-1"></i> View Certificate
                            </a>
                        <?php else: ?>
                            <span class="badge bg-danger">No Certificate</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning">No education records found for this employee.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>