<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Access Control Check
if (!isset($_SESSION['employee_logged_in']) || $_SESSION['employee_logged_in'] !== true) {
    header("Location: ../employee/login.php");
    exit;
}
// Notification Count
$notif_count = 0;
if (isset($pdo) && isset($_SESSION['employee_db_id'])) {
    // 1. General Notifications
    $n_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $n_stmt->execute([$_SESSION['employee_db_id']]);
    $notif_count += $n_stmt->fetchColumn();

    // 2. Unread Messages (DISABLED)
    // $m_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_role = 'admin' AND is_read = 0");
    // $m_stmt->execute([$_SESSION['employee_db_id']]);
    // $notif_count += $m_stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - Attendance System</title>

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
                <i class="bi bi-person-workspace text-success me-2"></i> Employee Portal
            </div>

            <nav class="sidebar-nav">
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="dashboard.php">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>

                <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-3" style="font-size: 0.75rem;">My
                    Workspace</div>

                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"
                    href="profile.php">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'education.php' ? 'active' : ''; ?>"
                    href="education.php">
                    <i class="bi bi-mortarboard-fill"></i> Education
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'attendance_history.php' ? 'active' : ''; ?>"
                    href="attendance_history.php">
                    <i class="bi bi-calendar2-check-fill"></i> Attendance
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'leave_request.php' ? 'active' : ''; ?>"
                    href="leave_request.php">
                    <i class="bi bi-send-fill"></i> Leave Requests
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'holidays.php' ? 'active' : ''; ?>"
                    href="holidays.php">
                    <i class="bi bi-calendar3"></i> Calendar
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>"
                    href="tasks.php">
                    <i class="bi bi-list-task"></i> My Tasks
                </a>

                <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-3" style="font-size: 0.75rem;">
                    Collaboration</div>

                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' ? 'active' : ''; ?>"
                    href="team.php">
                    <i class="bi bi-people-fill"></i> My Team
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>"
                    href="chat.php">
                    <i class="bi bi-chat-left-dots-fill"></i> Messages
                </a>
                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'email.php' ? 'active' : ''; ?>"
                    href="email.php">
                    <i class="bi bi-envelope-at-fill"></i> Email
                </a>

                <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-3" style="font-size: 0.75rem;">System
                </div>

                <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>"
                    href="notifications.php">
                    <i class="bi bi-bell-fill"></i> Notifications
                    <span id="notif-badge" class="badge bg-danger rounded-pill ms-auto"
                        style="<?php echo ($notif_count > 0) ? '' : 'display:none;'; ?>"><?php echo $notif_count; ?></span>
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
                        echo ($page == 'dashboard.php') ? 'Dashboard' : 'Employee Portal';
                        ?>
                    </h5>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold"
                            style="width: 32px; height: 32px; font-size: 0.8rem;">
                            EM
                        </div>
                        <span class="small fw-medium d-none d-md-block">Employee</span>
                    </div>
                </div>
            </header>

            <!-- Page Content Padding -->
            <div class="p-4">