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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse px-0">
                <a class="sidebar-brand" href="dashboard.php">
                    <i class="bi bi-shield-lock-fill me-2"></i>Admin Panel
                </a>
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                                href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>"
                                href="employees.php">
                                <i class="bi bi-people me-2"></i>Employees
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'employee_profiles.php' ? 'active' : ''; ?>"
                                href="employee_profiles.php">
                                <i class="bi bi-person-lines-fill me-2"></i>Profiles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'temp_access.php' ? 'active' : ''; ?>"
                                href="temp_access.php">
                                <i class="bi bi-unlock me-2"></i>Access Reqs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>"
                                href="attendance.php">
                                <i class="bi bi-calendar-check me-2"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bulk_attendance.php' ? 'active' : ''; ?>"
                                href="bulk_attendance.php">
                                <i class="bi bi-calendar-range me-2"></i>Bulk Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'missed_checkouts.php' ? 'active' : ''; ?>"
                                href="missed_checkouts.php">
                                <i class="bi bi-exclamation-triangle me-2"></i>Missed Checkout
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'leaves.php' ? 'active' : ''; ?>"
                                href="leaves.php">
                                <i class="bi bi-envelope-open me-2"></i>Leaves
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>"
                                href="tasks.php">
                                <i class="bi bi-list-check me-2"></i>Tasks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'teams.php' ? 'active' : ''; ?>"
                                href="teams.php">
                                <i class="bi bi-people-fill me-2"></i>Teams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'salary.php' ? 'active' : ''; ?>"
                                href="salary.php">
                                <i class="bi bi-cash-stack me-2"></i>Salary & Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_holidays.php' ? 'active' : ''; ?>"
                                href="manage_holidays.php">
                                <i class="bi bi-calendar-event me-2"></i>Holidays
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"
                                href="settings.php">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>"
                                href="messages.php">
                                <i class="bi bi-chat-dots me-2"></i>Messages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'email.php' ? 'active' : ''; ?>"
                                href="email.php">
                                <i class="bi bi-envelope me-2"></i>Email System
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="adminNotifLink">
                                <i class="bi bi-bell me-2"></i>Notifications
                                <span id="admin-notif-badge" class="badge bg-danger rounded-pill ms-2"
                                    style="display:none;">0</span>
                            </a>
                        </li>
                        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'super_admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_admins.php' ? 'active' : ''; ?>"
                                    href="manage_admins.php">
                                    <i class="bi bi-person-badge me-2"></i>Manage Admins
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <?php
            // Admin Notification Logic
            $admin_notif_count = 0;
            if (isset($pdo) && isset($_SESSION['admin_id'])) {
                // 1. General Notifications
                $n_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = 0 AND is_read = 0"); // 0 for Admin
                $n_stmt->execute();
                $admin_notif_count += $n_stmt->fetchColumn();

                // 2. Unread Messages (From Employees)
                $m_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_role = 'employee' AND is_read = 0");
                $m_stmt->execute([$_SESSION['admin_id']]);
                $admin_notif_count += $m_stmt->fetchColumn();
            }
            ?>
            <script>
                // Update Admin Badge
                document.addEventListener('DOMContentLoaded', function () {
                    const badge = document.getElementById('admin-notif-badge');
                    const count = <?php echo $admin_notif_count; ?>;
                    if (count > 0) {
                        badge.innerText = count;
                        badge.style.display = 'inline-block';
                    }
                });
            </script>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">