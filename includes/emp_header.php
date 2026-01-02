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

    // 2. Unread Messages (From Admin)
    $m_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_role = 'admin' AND is_read = 0");
    $m_stmt->execute([$_SESSION['employee_db_id']]);
    $notif_count += $m_stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - Attendance System</title>
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
                    <i class="bi bi-person-badge-fill me-2"></i>Employee Portal
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
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"
                                href="profile.php">
                                <i class="bi bi-person-circle me-2"></i>My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'education.php' ? 'active' : ''; ?>"
                                href="education.php">
                                <i class="bi bi-mortarboard me-2"></i>Education
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance_history.php' ? 'active' : ''; ?>"
                                href="attendance_history.php">
                                <i class="bi bi-calendar-check me-2"></i>My Attendance
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'leave_request.php' ? 'active' : ''; ?>"
                                href="leave_request.php">
                                <i class="bi bi-calendar-minus me-2"></i>Leaves
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>"
                                href="tasks.php">
                                <i class="bi bi-list-check me-2"></i>My Tasks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' ? 'active' : ''; ?>"
                                href="team.php">
                                <i class="bi bi-people me-2"></i>My Team
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'email.php' ? 'active' : ''; ?>"
                                href="email.php">
                                <i class="bi bi-envelope me-2"></i>Email System
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>"
                                href="messages.php">
                                <i class="bi bi-chat-dots me-2"></i>My Chat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>"
                                href="notifications.php">
                                <i class="bi bi-bell me-2"></i>Notifications
                                <span id="notif-badge" class="badge bg-danger rounded-pill ms-2"
                                    style="<?php echo ($notif_count > 0) ? '' : 'display:none;'; ?>"><?php echo $notif_count; ?></span>
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">