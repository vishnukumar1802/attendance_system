<?php
// admin/employee_form.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$id = '';
$is_edit = false;
$error = '';
$success = '';

// Default values
$employee_id = '';
$first_name = '';
$last_name = '';
$email = '';
$designation = '';
$monthly_salary = '';

// Check if Editing
if (isset($_GET['id'])) {
    $is_edit = true;
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $emp = $stmt->fetch();

    if ($emp) {
        $employee_id = $emp['employee_id'];
        $first_name = $emp['first_name'];
        $last_name = $emp['last_name'];
        $email = $emp['email'];
        $designation = $emp['designation'];
        $monthly_salary = $emp['monthly_salary'];
    } else {
        header("Location: employees.php");
        exit;
    }
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = clean_input($_POST['employee_id']);
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $email = clean_input($_POST['email']);
    $designation = clean_input($_POST['designation']);
    $monthly_salary = clean_input($_POST['monthly_salary']);
    $password = $_POST['password'];

    // Validation
    if (empty($employee_id) || empty($first_name) || empty($monthly_salary)) {
        $error = "Required fields are missing.";
    } else {
        // Auto-calculate Daily Salary (30 days standard)
        $salary_per_day = floatval($monthly_salary) / 30;

        try {
            if ($is_edit) {
                // Update
                $sql = "UPDATE employees SET 
                        employee_id = :eid, 
                        first_name = :fn, 
                        last_name = :ln, 
                        email = :email, 
                        designation = :desig, 
                        salary_per_day = :daily, 
                        monthly_salary = :monthly";

                $params = [
                    ':eid' => $employee_id,
                    ':fn' => $first_name,
                    ':ln' => $last_name,
                    ':email' => $email,
                    ':desig' => $designation,
                    ':daily' => $salary_per_day,
                    ':monthly' => $monthly_salary,
                    ':id' => $id
                ];

                if (!empty($password)) {
                    $sql .= ", password = :pass";
                    $params[':pass'] = password_hash($password, PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $success = "Employee updated successfully.";

            } else {
                // Create
                if (empty($password)) {
                    $error = "Password is required for new employees.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO employees (employee_id, password, first_name, last_name, email, designation, salary_per_day, monthly_salary) VALUES (:eid, :pass, :fn, :ln, :email, :desig, :daily, :monthly)");
                    $stmt->execute([
                        ':eid' => $employee_id,
                        ':pass' => password_hash($password, PASSWORD_DEFAULT),
                        ':fn' => $first_name,
                        ':ln' => $last_name,
                        ':email' => $email,
                        ':desig' => $designation,
                        ':daily' => $salary_per_day,
                        ':monthly' => $monthly_salary
                    ]);
                    // Reset form after success
                    $success = "Employee created successfully.";
                    $employee_id = $first_name = $last_name = $email = $designation = $monthly_salary = '';
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error = "Employee ID already exists.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $is_edit ? 'Edit Employee' : 'Add New Employee'; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="employees.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" name="employee_id" class="form-control"
                                value="<?php echo htmlspecialchars($employee_id); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" class="form-control"
                                value="<?php echo htmlspecialchars($designation); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control"
                                value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control"
                                value="<?php echo htmlspecialchars($last_name); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($email); ?>">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Monthly Salary <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="monthly_salary" class="form-control"
                                    value="<?php echo htmlspecialchars($monthly_salary); ?>" required>
                            </div>
                            <div class="form-text">Daily rate will be calculated as Salary / 30</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password
                                <?php echo $is_edit ? '<small>(Leave blank to keep current)</small>' : '<span class="text-danger">*</span>'; ?></label>
                            <input type="password" name="password" class="form-control" <?php echo $is_edit ? '' : 'required'; ?>>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <?php echo $is_edit ? 'Update Employee' : 'Create Employee'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>