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

<!-- 1. SUMMARY KPI CARDS -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="saas-card h-100 d-flex align-items-center">
            <div class="rounded-circle p-3 bg-indigo-50 text-primary me-3 bg-primary-subtle">
                <i class="bi bi-people-fill fs-4"></i>
            </div>
            <div>
                <p class="text-muted small text-uppercase fw-bold mb-1">Total Employees</p>
                <h2 class="display-6 fw-bold text-dark mb-0" id="kpi-total">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="saas-card h-100 d-flex align-items-center">
            <div class="rounded-circle p-3 bg-green-50 text-success me-3 bg-success-subtle">
                <i class="bi bi-check-circle-fill fs-4"></i>
            </div>
            <div>
                <p class="text-muted small text-uppercase fw-bold mb-1">Present Today</p>
                <h2 class="display-6 fw-bold text-dark mb-0" id="kpi-present">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="saas-card h-100 d-flex align-items-center">
            <div class="rounded-circle p-3 bg-red-50 text-danger me-3 bg-danger-subtle">
                <i class="bi bi-x-circle-fill fs-4"></i>
            </div>
            <div>
                <p class="text-muted small text-uppercase fw-bold mb-1">Absent Today</p>
                <h2 class="display-6 fw-bold text-dark mb-0" id="kpi-absent">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="saas-card h-100 d-flex align-items-center">
            <div class="rounded-circle p-3 bg-blue-50 text-info me-3 bg-info-subtle">
                <i class="bi bi-laptop-fill fs-4"></i>
            </div>
            <div>
                <p class="text-muted small text-uppercase fw-bold mb-1">WFH / On Leave</p>
                <h2 class="display-6 fw-bold text-dark mb-0" id="kpi-wfh">-</h2>
            </div>
        </div>
    </div>
</div>

<!-- 2. CHARTS ROW -->
<div class="row mb-4">
    <!-- Productivity Chart -->
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="saas-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-line text-primary me-2"></i>Productivity
                </h5>
                <span class="badge bg-light text-dark border">Last 30 Days</span>
            </div>
            <div>
                <canvas id="productivityChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <!-- Work Distribution -->
    <div class="col-lg-4">
        <div class="saas-card h-100">
            <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-pie-chart text-primary me-2"></i>Work Type</h5>
            <div class="d-flex align-items-center justify-content-center">
                <div style="width: 100%; max-width: 280px;">
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
        <div class="saas-card h-100 p-0 overflow-hidden">
            <div class="p-4 border-bottom bg-light bg-opacity-50">
                <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Attendance Risk
                    Monitor</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Employee</th>
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
        <div class="saas-card h-100 p-0 overflow-hidden">
            <div class="p-4 border-bottom bg-light bg-opacity-50">
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-bell me-2"></i>Behavioral Alerts</h5>
            </div>
            <div class="p-0">
                <ul class="list-group list-group-flush" id="alerts-list">
                    <li class="list-group-item text-muted text-center py-4 border-0">Scanning patterns...</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 4. TEAM ANALYTICS -->
<div class="saas-card mb-4">
    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-people text-primary me-2"></i>Team Performance</h5>
    <div id="team-perf-container">
        <!-- JS will populate -->
    </div>
</div>

<!-- 5. RECENT ACTIVITY TABLE -->
<div class="saas-card mb-4 p-0 overflow-hidden">
    <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-light bg-opacity-50">
        <h5 class="fw-bold mb-0 text-dark">Recent Activity Log</h5>
        <a href="attendance.php" class="btn btn-sm btn-saas-outline">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4">Employee</th>
                    <th>Date</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recent_activities) > 0): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar bg-light text-primary rounded-circle small fw-bold d-flex align-items-center justify-content-center me-2"
                                        style="width: 32px; height: 32px;">
                                        <?php echo substr($activity['first_name'], 0, 1) . substr($activity['last_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-dark small fw-bold">
                                            <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                        </h6>
                                        <small class="text-muted"
                                            style="font-size: 0.75rem;"><?php echo htmlspecialchars($activity['employee_id']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted small"><?php echo date('M j, Y', strtotime($activity['date'])); ?></td>
                            <td class="text-dark small fw-medium">
                                <?php echo $activity['check_in_time'] ? date('h:i A', strtotime($activity['check_in_time'])) : '-'; ?>
                            </td>
                            <td class="text-dark small fw-medium">
                                <?php echo $activity['check_out_time'] ? date('h:i A', strtotime($activity['check_out_time'])) : '-'; ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = match ($activity['status']) {
                                    'present' => 'badge-present',
                                    'absent' => 'badge-absent',
                                    'pending' => 'badge-half', // using half style for pending
                                    default => 'badge-half'
                                };
                                ?>
                                <span class="badge-saas <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($activity['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No recent activity found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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