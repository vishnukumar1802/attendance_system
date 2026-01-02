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

<!-- 1. SUMMARY KPI CARDS -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-primary h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Total Employees</div>
                <h2 class="display-6 fw-bold text-primary mb-0" id="kpi-total">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Present Today</div>
                <h2 class="display-6 fw-bold text-success mb-0" id="kpi-present">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-danger h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Absent Today</div>
                <h2 class="display-6 fw-bold text-danger mb-0" id="kpi-absent">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-info h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">WFH / On Leave</div>
                <h2 class="display-6 fw-bold text-info mb-0" id="kpi-wfh">-</h2>
            </div>
        </div>
    </div>
</div>

<!-- 2. CHARTS ROW -->
<div class="row mb-4">
    <!-- Productivity Chart -->
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart-line me-2"></i>Productivity vs Presence (Top 10 Active)</span>
                <span class="badge bg-light text-dark border">Last 30 Days</span>
            </div>
            <div class="card-body">
                <canvas id="productivityChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <!-- Work Distribution -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-pie-chart me-2"></i>Work Type Distribution
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="width: 100%; max-width: 300px;">
                    <canvas id="workDistChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. RISK & ALERTS ROW -->
<div class="row mb-4">
    <!-- Risk Monitor -->
    <div class="col-lg-7 mb-4 mb-lg-0">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold text-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>Attendance Risk Monitor
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Employee</th>
                            <th>Risk Score</th>
                            <th>Level</th>
                            <th>Key Issues</th>
                        </tr>
                    </thead>
                    <tbody id="risk-table-body">
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Loading risks...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Behavioral Alerts -->
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold text-primary">
                <i class="bi bi-bell me-2"></i>Behavioral Alerts
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="alerts-list">
                    <li class="list-group-item text-muted text-center py-3">Scanning patterns...</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 4. TEAM ANALYTICS -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-people me-2"></i>Team Performance (Attendance Consistency)
    </div>
    <div class="card-body" id="team-perf-container">
        <!-- JS will populate -->
    </div>
</div>

<!-- 5. RECENT ACTIVITY TABLE -->
<h3 class="h4 mb-3">Recent Activity Log</h3>
<div class="card border-0 shadow-sm mb-4">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', loadAnalytics);

    function loadAnalytics() {
        fetch('/attendance-system/ajax/get_admin_analytics.php')
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    console.error("Analytics Error:", data.error);
                    return;
                }
                renderKPI(data.kpi);
                renderCharts(data);
                renderRiskTable(data.risk_monitor);
                renderAlerts(data.alerts);
                renderTeams(data.team_performance);
            })
            .catch(err => console.error(err));
    }

    // 1. Render KPIs
    function renderKPI(kpi) {
        document.getElementById('kpi-total').innerText = kpi.total_employees;
        document.getElementById('kpi-present').innerText = kpi.present_today;
        document.getElementById('kpi-absent').innerText = kpi.absent_today || 0;
        document.getElementById('kpi-wfh').innerText = kpi.wfh_today || 0;
    }

    // 2. Render Charts
    let prodChart, workChart;

    function renderCharts(data) {
        // A. Productivity Chart
        const ctxP = document.getElementById('productivityChart').getContext('2d');
        const labels = data.productivity.map(d => d.u_name.split(' ')[0]);
        const tasks = data.productivity.map(d => d.tasks_completed);
        const hours = data.productivity.map(d => d.total_hours);
        const scores = data.productivity.map(d => d.score);

        if (prodChart) prodChart.destroy();
        prodChart = new Chart(ctxP, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Tasks Completed',
                        data: tasks,
                        backgroundColor: '#2563eb',
                        order: 2
                    },
                    {
                        label: 'Total Hours',
                        data: hours,
                        backgroundColor: '#cbd5e1',
                        order: 3
                    },
                    {
                        label: 'Efficiency Score (0-10)',
                        data: scores,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        type: 'line',
                        order: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Count / Hours' } },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        max: 10,
                        title: { display: true, text: 'Score' }
                    }
                }
            }
        });

        // B. Work Distribution Chart
        const ctxW = document.getElementById('workDistChart').getContext('2d');
        if (data.work_distribution && data.work_distribution.length > 0) {
            const catLabels = data.work_distribution.map(d => d.category.toUpperCase());
            const catCounts = data.work_distribution.map(d => d.count);
            const colors = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

            if (workChart) workChart.destroy();
            workChart = new Chart(ctxW, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catCounts,
                        backgroundColor: colors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    }

    // 3. Render Risk Table
    function renderRiskTable(risks) {
        const tbody = document.getElementById('risk-table-body');
        if (!risks || risks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No specific risks detected.</td></tr>';
            return;
        }

        let html = '';
        risks.forEach(r => {
            let badgeCls = 'bg-success';
            if (r.level === 'High') badgeCls = 'bg-danger';
            else if (r.level === 'Medium') badgeCls = 'bg-warning text-dark';

            let progressColor = 'success';
            if (r.score > 70) progressColor = 'danger';
            else if (r.score > 30) progressColor = 'warning';

            html += `
            <tr>
                <td class="fw-bold">${r.name}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                            <div class="progress-bar bg-${progressColor}" style="width: ${r.score}%"></div>
                        </div>
                        <span class="small fw-bold">${r.score}</span>
                    </div>
                </td>
                <td><span class="badge ${badgeCls}">${r.level}</span></td>
                <td class="small text-muted">${r.details}</td>
            </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    // 4. Render Alerts
    function renderAlerts(alerts) {
        const list = document.getElementById('alerts-list');
        if (!alerts || alerts.length === 0) {
            list.innerHTML = '<li class="list-group-item text-muted text-center">No anomalies detected.</li>';
            return;
        }
        let html = '';
        alerts.forEach(a => {
            html += `<li class="list-group-item border-0 border-bottom">${a}</li>`;
        });
        list.innerHTML = html;
    }

    // 5. Render Teams
    function renderTeams(teams) {
        const container = document.getElementById('team-perf-container');
        if (!teams || teams.length === 0) {
            container.innerHTML = '<p class="text-muted">No teams found.</p>';
            return;
        }
        let html = '<div class="row">';
        teams.forEach(t => {
            let color = 'primary';
            if (t.attendance_perc < 70) color = 'danger';
            else if (t.attendance_perc < 90) color = 'warning';
            else color = 'success';

            html += `
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold">${t.name} <span class="text-muted small fw-normal">(${t.member_count} members)</span></span>
                    <span class="small fw-bold ${t.attendance_perc < 80 ? 'text-danger' : 'text-success'}">${t.attendance_perc}% Att.</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-${color}" style="width: ${t.attendance_perc}%"></div>
                </div>
            </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }
</script>

<?php require_once '../includes/admin_footer.php'; ?>