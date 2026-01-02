<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="auth-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center text-white mb-5">
                    <h1 class="display-4 fw-bold">Office Attendance System</h1>
                    <p class="lead">Select your portal to continue</p>
                </div>
            </div>
            <div class="row justify-content-center gap-4">
                <div class="col-md-5 col-lg-4">
                    <div class="card p-4 h-100 text-center border-0 shadow-lg">
                        <div class="card-body">
                            <h3 class="card-title text-primary mb-3">Admin Portal</h3>
                            <p class="card-text text-muted mb-4">Manage employees, attendance, and salaries.</p>
                            <a href="admin/login.php" class="btn btn-outline-primary w-100">Login as Admin</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="card p-4 h-100 text-center border-0 shadow-lg">
                        <div class="card-body">
                            <h3 class="card-title text-success mb-3">Employee Portal</h3>
                            <p class="card-text text-muted mb-4">Mark attendance and submit work reports.</p>
                            <a href="employee/login.php" class="btn btn-outline-success w-100">Login as Employee</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>