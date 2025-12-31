<?php
// employee/education.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';
require_once '../includes/profile_helper.php';

$emp_id = $_SESSION['employee_db_id'];
$message = '';
$error = '';

// Add Education
if (isset($_POST['add_education'])) {
    $qual = $_POST['qualification'];
    $degree = clean_input($_POST['degree']);
    $spec = clean_input($_POST['specialization']);
    $inst = clean_input($_POST['institution']);
    $uni = clean_input($_POST['university_or_board']);
    $year = $_POST['year_of_passing'];
    $score = $_POST['percentage_or_cgpa'];

    // Certificate
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $filename = $_FILES['certificate']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['certificate']['size'] <= 5242880) { // 5MB
            $new_name = "cert_" . $emp_id . "_" . time() . "." . $ext;
            $dest = "../uploads/certificates/" . $new_name;

            if (move_uploaded_file($_FILES['certificate']['tmp_name'], $dest)) {

                $pdo->beginTransaction();
                try {
                    // 1. Insert Education
                    $stmt = $pdo->prepare("INSERT INTO employee_education (emp_id, qualification, degree, specialization, institution, university_or_board, year_of_passing, percentage_or_cgpa) 
                        VALUES (:eid, :q, :d, :s, :i, :u, :y, :p)");
                    $stmt->execute(['eid' => $emp_id, 'q' => $qual, 'd' => $degree, 's' => $spec, 'i' => $inst, 'u' => $uni, 'y' => $year, 'p' => $score]);
                    $edu_id = $pdo->lastInsertId();

                    // 2. Insert Certificate
                    $stmt = $pdo->prepare("INSERT INTO education_certificates (education_id, certificate_file) VALUES (?, ?)");
                    $stmt->execute([$edu_id, $new_name]);

                    $pdo->commit();
                    $message = "Education record added.";
                    update_profile_status($pdo, $emp_id);

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "DB Error: " . $e->getMessage();
                }

            } else {
                $error = "Failed to upload certificate.";
            }
        } else {
            $error = "Invalid file type or size (Max 5MB).";
        }
    } else {
        $error = "Certificate file is mandatory.";
    }
}

// Delete Education
if (isset($_POST['delete_edu'])) {
    $del_id = $_POST['del_id'];

    // Verify Ownership
    $stmt = $pdo->prepare("SELECT id FROM employee_education WHERE id = ? AND emp_id = ?");
    $stmt->execute([$del_id, $emp_id]);
    if ($stmt->fetch()) {

        // Get File to delete
        $f_stmt = $pdo->prepare("SELECT certificate_file FROM education_certificates WHERE education_id = ?");
        $f_stmt->execute([$del_id]);
        $file = $f_stmt->fetchColumn();
        if ($file && file_exists("../uploads/certificates/" . $file)) {
            unlink("../uploads/certificates/" . $file);
        }

        $pdo->prepare("DELETE FROM employee_education WHERE id = ?")->execute([$del_id]);
        $message = "Record deleted.";
        update_profile_status($pdo, $emp_id);
    } else {
        $error = "Invalid request.";
    }
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
    <h1 class="h2">Education Details</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Form -->
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Add Education</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Qualification</label>
                        <select name="qualification" class="form-select" required>
                            <option value="10th">10th</option>
                            <option value="12th">12th</option>
                            <option value="Diploma">Diploma</option>
                            <option value="UG">Under Graduate (UG)</option>
                            <option value="PG">Post Graduate (PG)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Degree/Stream</label>
                            <input type="text" name="degree" class="form-control" placeholder="e.g. B.Tech" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-control" placeholder="e.g. CSE"
                                required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Institution</label>
                        <input type="text" name="institution" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">University / Board</label>
                        <input type="text" name="university_or_board" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Year</label>
                            <input type="number" name="year_of_passing" class="form-control" min="1950"
                                max="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">% or CGPA</label>
                            <input type="number" name="percentage_or_cgpa" class="form-control" step="0.01" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Certificate (PDF, Doc, Img) <span class="text-danger">*</span></label>
                        <input type="file" name="certificate" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png"
                            required>
                    </div>

                    <button type="submit" name="add_education" class="btn btn-primary w-100">Add Record</button>
                    <div class="form-text mt-2">Uploading certificate is mandatory.</div>
                </form>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="col-md-7">
        <?php if (count($eds) > 0): ?>
            <?php foreach ($eds as $ed): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title text-primary"><?php echo $ed['qualification']; ?> -
                                <?php echo htmlspecialchars($ed['degree']); ?>
                            </h5>
                            <div>
                                <span class="badge bg-light text-dark border me-2"><?php echo $ed['year_of_passing']; ?></span>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="del_id" value="<?php echo $ed['id']; ?>">
                                    <button type="submit" name="delete_edu" class="btn btn-sm btn-outline-danger"
                                        title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <h6 class="text-muted"><?php echo htmlspecialchars($ed['institution']); ?></h6>
                        <p class="small text-muted mb-2"><?php echo htmlspecialchars($ed['university_or_board']); ?> | Score:
                            <?php echo $ed['percentage_or_cgpa']; ?>
                        </p>

                        <?php if ($ed['certificate_file']): ?>
                            <a href="../uploads/certificates/<?php echo $ed['certificate_file']; ?>" target="_blank"
                                class="btn btn-sm btn-outline-info">
                                <i class="bi bi-file-earmark-text"></i> View Certificate
                            </a>
                        <?php else: ?>
                            <span class="text-danger small">Missing Certificate</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning">No education records found. Please add at least one.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/emp_footer.php'; ?>