<?php
// employee/login.php
require_once '../config/db.php';

if (isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emp_id = clean_input($_POST['employee_id']);
    $password = clean_input($_POST['password']);

    if (empty($emp_id) || empty($password)) {
        $error = "Please enter both Employee ID and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, employee_id, password, first_name, status FROM employees WHERE employee_id = :eid");
        $stmt->bindParam(':eid', $emp_id);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch();
            if ($row['status'] == 'inactive') {
                $error = "Your account is inactive. Contact Admin.";
            } elseif (password_verify($password, $row['password'])) {
                $_SESSION['employee_logged_in'] = true;
                $_SESSION['employee_db_id'] = $row['id'];
                $_SESSION['employee_id'] = $row['employee_id'];
                $_SESSION['employee_name'] = $row['first_name'];
                $_SESSION['user_role'] = 'employee'; // For unified features
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Invalid Employee ID.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="text-center mb-4">
                <h3>Employee Portal</h3>
                <p>Login to mark attendance</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label class="form-label">Employee ID</label>
                    <input type="text" name="employee_id" class="form-control" placeholder="EMP001" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login to Dashboard</button>
            </form>
            <div class="mt-3 text-center">
                <a href="../index.php" class="text-decoration-none text-muted">Back to Home</a>
            </div>
        </div>
    </div>
</body>

</html>