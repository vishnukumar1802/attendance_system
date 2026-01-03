<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Access Control Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Bootstrap (Grid/Utils) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom SaaS Theme -->
    <link rel="stylesheet" href="../assets/css/saas-theme.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="app-container">
        <!-- SaaS Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <i class="bi bi-stars text-primary me-2"></i> HR System
            </div>

            <nav class="sidebar-nav">
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="dashboard.php">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>

                <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-3" style="font-size: 0.75rem;">People
                </div>

                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>"
                    href="employees.php">
                    <i class="bi bi-people-fill"></i> Employees
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'teams.php' ? 'active' : ''; ?>"
                    href="teams.php">
                    <i class="bi bi-microsoft-teams"></i> Teams
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'temp_access.php' ? 'active' : ''; ?>"
                    href="temp_access.php">
                    <i class="bi bi-unlock-fill"></i> Access Requests
                </a>

                <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-3" style="font-size: 0.75rem;">
                    Management</div>

                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>"
                    href="attendance.php">
                    <i class="bi bi-calendar-check-fill"></i> Attendance
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'leaves.php' ? 'active' : ''; ?>"
                    href="leaves.php">
                    <i class="bi bi-envelope-paper-fill"></i> Leaves
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>"
                    href="tasks.php">
                    <i class="bi bi-kanban-fill"></i> Tasks
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'salary.php' ? 'active' : ''; ?>"
                    href="salary.php">
                    <i class="bi bi-currency-dollar"></i> Payroll
                </a>

                <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-3" style="font-size: 0.75rem;">
                    Communication</div>

                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>"
                    href="chat.php">
                    <i class="bi bi-chat-text-fill"></i> Chat
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'email.php' ? 'active' : ''; ?>"
                    href="email.php">
                    <i class="bi bi-envelope-at-fill"></i> Email
                </a>

                <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-3" style="font-size: 0.75rem;">System
                </div>

                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_holidays.php' ? 'active' : ''; ?>"
                    href="manage_holidays.php">
                    <i class="bi bi-calendar3"></i> Holidays
                </a>
                <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'super_admin'): ?>
                    <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_admins.php' ? 'active' : ''; ?>"
                        href="manage_admins.php">
                        <i class="bi bi-shield-fill-check"></i> Admins
                    </a>
                <?php endif; ?>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"
                    href="settings.php">
                    <i class="bi bi-gear-fill"></i> Settings
                </a>

                <a class="nav-item text-danger mt-3" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <?php
                        $page = basename($_SERVER['PHP_SELF']);
                        $title_map = [
                            'dashboard.php' => 'Dashboard',
                            'employees.php' => 'Employee Management',
                            'attendance.php' => 'Attendance Overview',
                            'chat.php' => 'Messages'
                        ];
                        echo isset($title_map[$page]) ? $title_map[$page] : 'Admin Portal';
                        ?>
                    </h5>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative cursor-pointer" id="adminNotifLink">
                        <i class="bi bi-bell fs-5 text-muted"></i>
                        <span id="admin-notif-badge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="display:none; font-size: 0.6rem;">0</span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold"
                            style="width: 32px; height: 32px; font-size: 0.8rem;">
                            AD
                        </div>
                        <span class="small fw-medium d-none d-md-block">Admin User</span>
                    </div>
                </div>
            </header>

            <!-- Notification Logic Kept Intact -->
            <?php
            $admin_notif_count = 0;
            if (isset($pdo) && isset($_SESSION['admin_id'])) {
                $n_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = 0 AND is_read = 0");
                $n_stmt->execute();
                $admin_notif_count += $n_stmt->fetchColumn();
            }
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const badge = document.getElementById('admin-notif-badge');
                    const count = <?php echo $admin_notif_count; ?>;
                    if (count > 0) {
                        badge.innerText = count;
                        badge.style.display = 'inline-block';
                    }
                });
            </script>

            <!-- Page Content Padding -->
            <div class="p-4">