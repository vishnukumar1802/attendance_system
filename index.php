<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Attendance System</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Bootstrap Grid Only (Optional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom SaaS Theme -->
    <link rel="stylesheet" href="assets/css/saas-theme.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="landing-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="glass-card mb-4">
                        <div class="row g-0">
                            <!-- Left: Brand / Welcome -->
                            <div class="col-md-6 bg-white p-5 d-flex flex-column justify-content-center">
                                <div class="mb-4">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">
                                        <i class="bi bi-stars me-1"></i> HR Management System
                                    </span>
                                    <h1 class="display-5 fw-bold text-dark mb-3">Manage Your Team Efficiently</h1>
                                    <p class="text-muted lead">Streamline attendance, leave requests, tasks, and
                                        communication in one unified platform.</p>
                                </div>
                                <div class="d-flex gap-3 align-items-center text-muted small">
                                    <span><i class="bi bi-check-circle-fill text-success me-1"></i> Secure</span>
                                    <span><i class="bi bi-check-circle-fill text-success me-1"></i> Fast</span>
                                    <span><i class="bi bi-check-circle-fill text-success me-1"></i> Reliable</span>
                                </div>
                            </div>

                            <!-- Right: Login Options -->
                            <div class="col-md-6 bg-light p-5 border-start border-light">
                                <h4 class="mb-4 fw-bold">Select Portal</h4>

                                <a href="admin/login.php" class="text-decoration-none">
                                    <div class="saas-card mb-3 d-flex align-items-center p-3 hover-scale">
                                        <div
                                            class="rounded-circle bg-indigo-100 p-3 me-3 text-primary bg-primary-subtle">
                                            <i class="bi bi-shield-lock-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-dark fw-bold">Admin Portal</h6>
                                            <p class="mb-0 text-muted small">Access dashboard & manage staff</p>
                                        </div>
                                        <i class="bi bi-arrow-right ms-auto text-muted"></i>
                                    </div>
                                </a>

                                <a href="employee/login.php" class="text-decoration-none">
                                    <div class="saas-card d-flex align-items-center p-3 hover-scale">
                                        <div
                                            class="rounded-circle bg-green-100 p-3 me-3 text-success bg-success-subtle">
                                            <i class="bi bi-person-badge-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-dark fw-bold">Employee Portal</h6>
                                            <p class="mb-0 text-muted small">Mark attendance, chat & tasks</p>
                                        </div>
                                        <i class="bi bi-arrow-right ms-auto text-muted"></i>
                                    </div>
                                </a>

                                <div class="mt-4 text-center">
                                    <small class="text-muted">Office Attendance System &copy;
                                        <?php echo date('Y'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>