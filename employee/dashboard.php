<?php
// employee/dashboard.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];
$today = date('Y-m-d');
$message = '';
$error = '';

// Check today's status
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :eid AND date = :date");
$stmt->execute(['eid' => $emp_id, 'date' => $today]);
$attendance = $stmt->fetch();

// Check Holiday
$h_stmt = $pdo->prepare("SELECT name FROM holidays WHERE date = ?");
$h_stmt->execute([$today]);
$holiday = $h_stmt->fetch();
$is_holiday = (bool) $holiday;

// --- V3: PROFILE CHECK ---
$p_stmt = $pdo->prepare("SELECT profile_completed, temp_access_expiry FROM employee_profiles WHERE emp_id = ?");
$p_stmt->execute([$emp_id]);
$prof_data = $p_stmt->fetch();

$is_profile_complete = ($prof_data && $prof_data['profile_completed'] == 1);
$temp_access_valid = ($prof_data && $prof_data['temp_access_expiry'] && $prof_data['temp_access_expiry'] >= $today);

$attendance_allowed = ($is_profile_complete || $temp_access_valid);
// -------------------------

// Handle Check In
if (isset($_POST['check_in'])) {

    if (!$attendance_allowed) {
        $error = "Access Denied. Complete your profile.";
    } else {
        // 0. Check Lock
        // (Assuming attendance_lock_date setting exists, fetching safely)
        $lock_res = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'attendance_lock_date'");
        $lock_date = ($lock_res && $lock_res->rowCount() > 0) ? $lock_res->fetchColumn() : '1970-01-01';

        if ($today <= $lock_date) {
            $error = "Attendance for this date is locked by Admin.";
        } elseif ($is_holiday) {
            $error = "Today is a holiday (" . $holiday['name'] . "). No check-in required.";
        } elseif ($attendance && $attendance['status'] == 'leave') {
            $error = "You are on approved leave today.";
        } elseif (!$attendance) {
            // Late Check-in Logic (After 10:30 AM)
            $current_time = time(); // timestamp
            $late_threshold = strtotime(date('Y-m-d 10:30:00'));

            $status = ($current_time > $late_threshold) ? 'half_day' : 'pending';

            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, status) VALUES (:eid, :date, NOW(), :status)");
            if ($stmt->execute(['eid' => $emp_id, 'date' => $today, 'status' => $status])) {
                // Refresh logic
                echo "<script>window.location.href='dashboard.php';</script>";
                exit;
            } else {
                $error = "Failed to check in.";
            }
        }
    }
}

// Handle Check Out (Work Submission)
if (isset($_POST['submit_work'])) {
    $description = htmlspecialchars($_POST['work_description']);
    $link = htmlspecialchars($_POST['work_link']);

    // Validate submission linked to attendance
    if ($attendance && !$attendance['check_out_time']) {
        if (empty($description)) {
            $error = "Work description is required.";
        } else {
            $pdo->beginTransaction();
            try {
                // 1. Calculate Status (Late In OR Early Out)
                $check_out_time = time();
                $early_threshold = strtotime(date('Y-m-d 16:00:00')); // 4:00 PM

                $current_status = $attendance['status']; // could be 'half_day' from checkin
                $final_status = 'present';

                // If already half_day (due to late in) OR early out
                if ($current_status == 'half_day' || $check_out_time < $early_threshold) {
                    $final_status = 'half_day';
                }

                $upd = $pdo->prepare("UPDATE attendance SET check_out_time = NOW(), status = :st WHERE id = :id");
                $upd->execute(['id' => $attendance['id'], 'st' => $final_status]);

                // 2. Insert Work Submission
                $ins = $pdo->prepare("INSERT INTO work_submissions (attendance_id, description, link) VALUES (:aid, :desc, :link)");
                $ins->execute(['aid' => $attendance['id'], 'desc' => $description, 'link' => $link]);

                $pdo->commit();
                $message = "Checked out successfully. Good job!";
                echo "<script>window.location.href='dashboard.php';</script>";
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error submitting work: " . $e->getMessage();
            }
        }
    }
}

// --- NEW VISUAL DATA FETCHING ---
// 1. Month Summary
$current_month = date('m');
$current_year = date('Y');
$stats_sql = "SELECT status, COUNT(*) as count FROM attendance WHERE employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? GROUP BY status";
$s_stmt = $pdo->prepare($stats_sql);
$s_stmt->execute([$emp_id, $current_month, $current_year]);
$month_stats = $s_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['present' => 5, 'absent' => 1]

$present_days = $month_stats['present'] ?? 0;
$absent_days = $month_stats['absent'] ?? 0;
$leave_days = $month_stats['leave'] ?? 0;

// 2. Upcoming Holidays
$hol_sql = "SELECT name, date, type FROM holidays WHERE date >= CURDATE() ORDER BY date ASC LIMIT 3";
$upcoming_holidays = $pdo->query($hol_sql)->fetchAll();

// 3. Task Stats
$task_sql = "SELECT status, COUNT(*) as count FROM tasks WHERE assigned_to = ? GROUP BY status";
$t_stmt = $pdo->prepare($task_sql);
$t_stmt->execute([$emp_id]);
$task_stats = $t_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$tasks_completed = $task_stats['completed'] ?? 0;
$tasks_in_progress = $task_stats['in_progress'] ?? 0;
$tasks_pending = $task_stats['pending'] ?? 0;
$total_tasks = $tasks_completed + $tasks_in_progress + $tasks_pending;

// Weighted Progress: Completed=100%, In Progress=50%
$weighted_score = ($tasks_completed * 1) + ($tasks_in_progress * 0.5);
$task_progress = ($total_tasks > 0) ? round(($weighted_score / $total_tasks) * 100) : 0;

// 4. Calendar Data (Full Month)
$cal_sql = "SELECT date, status FROM attendance WHERE employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";
$c_stmt = $pdo->prepare($cal_sql);
$c_stmt->execute([$emp_id, $current_month, $current_year]);
$attendance_map = $c_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Holiday Map for Calendar
$h_cal_sql = "SELECT date, name FROM holidays WHERE MONTH(date) = ? AND YEAR(date) = ?";
$hc_stmt = $pdo->prepare($h_cal_sql);
$hc_stmt->execute([$current_month, $current_year]);
$holiday_map = $hc_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php if ($message): ?>
    <div class="alert alert-success d-flex align-items-center mb-4 border-0 shadow-sm">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4 border-0 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- DASHBOARD GRID -->
<div class="row g-4 mb-4">
    <!-- ACTION CARD (Left Column) -->
    <div class="col-lg-4">
        <?php if ($attendance && $attendance['status'] == 'leave'): ?>
            <div class="saas-card h-100 text-center p-4 border-warning border-2 d-flex flex-column justify-content-center">
                <div class="fs-1 text-warning mb-2"><i class="bi bi-calendar2-x"></i></div>
                <h3 class="fw-bold">On Leave</h3>
                <p class="text-muted">Enjoy your time off!</p>
            </div>
        <?php elseif ($is_holiday): ?>
            <div class="saas-card h-100 text-center p-4 border-info border-2 d-flex flex-column justify-content-center">
                <div class="fs-1 text-info mb-2"><i class="bi bi-calendar-event"></i></div>
                <h3 class="fw-bold">Holiday</h3>
                <p class="text-muted"><?php echo htmlspecialchars($holiday['name']); ?></p>
            </div>
        <?php elseif (!$attendance_allowed): ?>
            <div class="saas-card h-100 text-center p-4 border-danger border-2 d-flex flex-column justify-content-center">
                <div class="fs-1 text-danger mb-2"><i class="bi bi-shield-lock"></i></div>
                <h3 class="fw-bold">Access Denied</h3>
                <p class="text-muted small">Complete Profile to Check In</p>
                <div class="d-grid gap-2 mt-3">
                    <a href="profile.php" class="btn btn-outline-danger">Complete Profile</a>
                    <a href="request_access.php" class="btn btn-warning text-white">Request Temp Access</a>
                </div>
            </div>
        <?php elseif (!$attendance): ?>
            <div class="saas-card h-100 text-center p-4 d-flex flex-column justify-content-center">
                <h4 class="fw-bold text-dark mb-3">Mark Attendance</h4>
                <div class="mb-4">
                    <span class="fs-5 text-muted"><?php echo date('l, d M Y'); ?></span>
                </div>
                <form method="post">
                    <button type="submit" name="check_in"
                        class="btn btn-primary w-100 py-3 rounded-pill shadow-sm fw-bold hover-scale">
                        <i class="bi bi-fingerprint me-2"></i> CHECK IN
                    </button>
                </form>
            </div>
        <?php elseif ($attendance['check_in_time'] && !$attendance['check_out_time']): ?>
            <div class="saas-card h-100 text-center p-4 border-start border-4 border-primary">
                <div class="badge bg-success-subtle text-success mb-3 px-3 py-2 rounded-pill">Checked In</div>
                <div class="display-6 fw-bold text-dark mb-2" id="clock">00:00:00</div>
                <p class="text-muted small mb-4">Since <?php echo date('h:i A', strtotime($attendance['check_in_time'])); ?>
                </p>
                <button type="button" class="btn btn-danger w-100 py-3 rounded-pill shadow-sm fw-bold"
                    data-bs-toggle="modal" data-bs-target="#checkoutModal">
                    <i class="bi bi-box-arrow-right me-2"></i> CHECK OUT
                </button>
            </div>
        <?php else: ?>
            <div class="saas-card h-100 text-center p-4 d-flex flex-column justify-content-center bg-light">
                <div class="text-success fs-1 mb-2"><i class="bi bi-check-circle-fill"></i></div>
                <h4 class="fw-bold">Done for Today!</h4>
                <div class="d-flex justify-content-between px-4 mt-3 small text-muted">
                    <span>In: <?php echo date('h:i A', strtotime($attendance['check_in_time'])); ?></span>
                    <span>Out: <?php echo date('h:i A', strtotime($attendance['check_out_time'])); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- STATS GRID (Right Column) -->
    <div class="col-lg-8">
        <div class="row g-3">
            <!-- Summary Cards -->
            <div class="col-md-4">
                <div class="saas-card p-3 h-100 d-flex align-items-center">
                    <div class="rounded-circle bg-green-50 text-success p-3 me-3 bg-success-subtle">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Present</div>
                        <h4 class="mb-0 fw-bold"><?php echo $present_days; ?> <small
                                class="text-muted fs-6">Days</small></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="saas-card p-3 h-100 d-flex align-items-center">
                    <div class="rounded-circle bg-red-50 text-danger p-3 me-3 bg-danger-subtle">
                        <i class="bi bi-calendar-x fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Absent</div>
                        <h4 class="mb-0 fw-bold"><?php echo $absent_days; ?> <small class="text-muted fs-6">Days</small>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="saas-card p-3 h-100 d-flex align-items-center">
                    <div class="rounded-circle bg-blue-50 text-primary p-3 me-3 bg-primary-subtle">
                        <i class="bi bi-list-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Tasks Done</div>
                        <h4 class="mb-0 fw-bold"><?php echo $tasks_completed; ?></h4>
                    </div>
                </div>
            </div>

            <!-- Chart & Holidays -->
            <div class="col-md-7">
                <div class="saas-card p-3 h-100">
                    <h6 class="fw-bold text-dark mb-3">Attendance Trends</h6>
                    <div style="height: 150px; position: relative;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="saas-card p-3 h-100">
                    <h6 class="fw-bold text-dark mb-3">Upcoming Holidays</h6>
                    <?php if (count($upcoming_holidays) > 0): ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($upcoming_holidays as $uh): ?>
                                <li class="d-flex align-items-center mb-2 p-2 rounded bg-light">
                                    <div class="badge bg-white text-danger border shadow-sm me-2">
                                        <?php echo date('d', strtotime($uh['date'])); ?>
                                    </div>
                                    <div class="lh-1">
                                        <div class="fw-bold small text-dark"><?php echo htmlspecialchars($uh['name']); ?></div>
                                        <small class="text-muted"
                                            style="font-size:10px;"><?php echo date('M Y', strtotime($uh['date'])); ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small">No upcoming holidays.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 2: CALENDAR & PRODUCTIVITY -->
<div class="row g-4 mb-4">
    <!-- CALENDAR -->
    <div class="col-lg-8">
        <div class="saas-card h-100 p-0 overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light bg-opacity-50">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2 text-primary"></i>My Attendance Calendar
                    (<?php echo date('F Y'); ?>)</h6>
                <div class="d-flex gap-2 small">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Present</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Absent</span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle">Holiday</span>
                </div>
            </div>
            <div class="p-4">
                <div id="dashboard-calendar" class="d-grid gap-2" style="grid-template-columns: repeat(7, 1fr);">
                    <!-- Days Header -->
                    <?php foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $d): ?>
                        <div class="text-center text-muted small fw-bold py-1"><?php echo $d; ?></div>
                    <?php endforeach; ?>

                    <!-- Days Gen (PHP Logic for Grid) -->
                    <?php
                    $days_in_month = date('t');
                    $start_day_offset = date('w', strtotime("$current_year-$current_month-01"));

                    // Empty Slots
                    for ($i = 0; $i < $start_day_offset; $i++)
                        echo "<div></div>";

                    // Days
                    for ($d = 1; $d <= $days_in_month; $d++):
                        $date_str = "$current_year-$current_month-" . str_pad($d, 2, '0', STR_PAD_LEFT);

                        // Check Status
                        $day_class = 'bg-light text-muted'; // Default
                        $tooltip = 'No status';

                        if (isset($holiday_map[$date_str])) {
                            $day_class = 'bg-info-subtle text-info fw-bold';
                            $tooltip = $holiday_map[$date_str];
                        } elseif (isset($attendance_map[$date_str])) {
                            $status = $attendance_map[$date_str];
                            if ($status == 'present')
                                $day_class = 'bg-success text-white fw-bold shadow-sm';
                            elseif ($status == 'absent')
                                $day_class = 'bg-danger-subtle text-danger fw-bold';
                            elseif ($status == 'leave')
                                $day_class = 'bg-warning-subtle text-dark fw-bold';
                            elseif ($status == 'half_day')
                                $day_class = 'bg-warning text-dark fw-bold';
                        } else {
                            // Default for past weekdays usually absent, but let's keep it neutral or light red for visual
                            if ($date_str < date('Y-m-d') && date('N', strtotime($date_str)) < 6) {
                                // $day_class = 'bg-white text-secondary border'; 
                            }
                        }

                        // Highlight Today
                        $border_style = ($date_str == date('Y-m-d')) ? 'border: 2px solid #0d6efd;' : '';
                        ?>
                        <div class="ratio ratio-1x1 mb-1">
                            <div class="d-flex align-items-center justify-content-center rounded-3 small <?php echo $day_class; ?>"
                                style="cursor: default; <?php echo $border_style; ?>" title="<?php echo $tooltip; ?>">
                                <?php echo $d; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- PRODUCTIVITY -->
    <div class="col-lg-4">
        <div class="saas-card h-100">
            <h6 class="fw-bold mb-4">Task Productivity</h6>

            <div class="text-center mb-4">
                <div class="position-relative d-inline-block">
                    <div style="width: 120px; height: 120px;">
                        <canvas id="taskDoughnut"></canvas>
                    </div>
                    <div class="position-absolute top-50 start-50 translate-middle text-center">
                        <h4 class="fw-bold mb-0"><?php echo $task_progress; ?>%</h4>
                        <small class="text-muted" style="font-size: 10px;">COMPLETE</small>
                    </div>
                </div>
            </div>

            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                    <span><i class="bi bi-check-circle-fill text-success me-2"></i>Completed</span>
                    <span class="fw-bold"><?php echo $task_stats['completed'] ?? 0; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                    <span><i class="bi bi-hourglass-split text-primary me-2"></i>In Progress</span>
                    <span class="fw-bold"><?php echo $task_stats['in_progress'] ?? 0; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                    <span><i class="bi bi-circle text-secondary me-2"></i>Pending</span>
                    <span class="fw-bold"><?php echo $task_stats['pending'] ?? 0; ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- CHECKOUT MODAL (Preserved Logic) -->
<div class="modal fade" id="checkoutModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Daily Work Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body pt-4">
                    <p class="text-muted small mb-3">You must submit your work summary to check out.</p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Work Description <span
                                class="text-danger">*</span></label>
                        <textarea name="work_description" class="form-control" rows="4"
                            placeholder="What did you work on today?" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Project Link
                            (Optional)</label>
                        <input type="url" name="work_link" class="form-control"
                            placeholder="https://drive.google.com/...">
                        <div class="form-text">Link to Drive, GitHub, or documents.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_work"
                        class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">Submit & Check Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS FOR CHARTS & CLOCK -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Attendance Bar Chart
    const ctxAtt = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctxAtt, {
        type: 'bar',
        data: {
            labels: ['Present', 'Absent', 'Leave'],
            datasets: [{
                label: 'Days',
                data: [<?php echo "$present_days, $absent_days, $leave_days"; ?>],
                backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, display: false },
                x: { grid: { display: false } }
            }
        }
    });

    // Task Doughnut
    const ctxTask = document.getElementById('taskDoughnut').getContext('2d');
    new Chart(ctxTask, {
        type: 'doughnut',
        data: {
            labels: ['Done', 'Process', 'Pending'],
            datasets: [{
                data: [
                    <?php echo ($task_stats['completed'] ?? 0); ?>,
                    <?php echo ($task_stats['in_progress'] ?? 0); ?>,
                    <?php echo ($task_stats['pending'] ?? 0); ?>
                ],
                backgroundColor: ['#198754', '#0d6efd', '#6c757d'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            cutout: '75%',
            plugins: { legend: { display: false }, tooltip: { enabled: true } }
        }
    });

    // Simple Clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        const clockElement = document.getElementById('clock');
        if (clockElement) {
            clockElement.textContent = timeString;
        }
    }
    if (document.getElementById('clock')) {
        setInterval(updateClock, 1000);
        updateClock();
    }
</script>

<?php require_once '../includes/emp_footer.php'; ?>