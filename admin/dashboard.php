<?php
// admin/dashboard.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

// Fetch Statistics
$today = date('Y-m-d');

// 1. Total Employees
$stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
$total_employees = $stmt->fetchColumn();

// 2. Present Today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = :date AND status = 'present'");
$stmt->execute(['date' => $today]);
$present_today = $stmt->fetchColumn();

// 3. Pending Approvals
$stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status = 'pending'");
$pending_approvals = $stmt->fetchColumn();

// 4. Recent Activities (Last 5 attendance records)
$stmt = $pdo->query("
    SELECT a.*, e.first_name, e.last_name, e.employee_id
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recent_activities = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="btn btn-sm btn-outline-secondary disabled"><?php echo date('l, F j, Y'); ?></span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Stat Card 1 -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="stat-card">
            <h6>Total Employees</h6>
            <div class="d-flex align-items-center justify-content-between">
                <h2><?php echo $total_employees; ?></h2>
                <i class="bi bi-people fs-1 text-primary"></i>
            </div>
        </div>
    </div>
    <!-- Stat Card 2 -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="stat-card">
            <h6>Present Today</h6>
            <div class="d-flex align-items-center justify-content-between">
                <h2><?php echo $present_today; ?></h2>
                <i class="bi bi-person-check fs-1 text-success"></i>
            </div>
        </div>
    </div>
    <!-- Stat Card 3 -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="stat-card">
            <h6>Pending Approvals</h6>
            <div class="d-flex align-items-center justify-content-between">
                <h2><?php echo $pending_approvals; ?></h2>
                <i class="bi bi-clock-history fs-1 text-warning"></i>
            </div>
        </div>
    </div>
</div>

<h3 class="h4 mb-3">Recent Activity</h3>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th scope="col">Employee</th>
                        <th scope="col">Date</th>
                        <th scope="col">Check In</th>
                        <th scope="col">Check Out</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_activities) > 0): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-2">
                                            <h6 class="mb-0">
                                                <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                            </h6>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($activity['employee_id']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($activity['date'])); ?></td>
                                <td><?php echo $activity['check_in_time'] ? date('h:i A', strtotime($activity['check_in_time'])) : '-'; ?>
                                </td>
                                <td><?php echo $activity['check_out_time'] ? date('h:i A', strtotime($activity['check_out_time'])) : '-'; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match ($activity['status']) {
                                        'present' => 'status-present',
                                        'absent' => 'status-absent',
                                        'pending' => 'status-pending',
                                        default => 'status-pending'
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($activity['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No recent activity found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>