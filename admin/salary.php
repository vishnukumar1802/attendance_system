<?php
// admin/salary.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Generate/Recalculate Salaries
if (isset($_POST['generate'])) {
    // 1. Clear existing for this month
    $stmt = $pdo->prepare("DELETE FROM salary_records WHERE month = :m AND year = :y");
    $stmt->execute(['m' => $month, 'y' => $year]);

    // 2. Fetch all active employees
    $emps = $pdo->query("SELECT id, salary_per_day FROM employees WHERE status = 'active'")->fetchAll();

    foreach ($emps as $emp) {
        // Count Present
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = :eid AND MONTH(date) = :m AND YEAR(date) = :y AND status = 'present'");
        $stmt->execute(['eid' => $emp['id'], 'm' => $month, 'y' => $year]);
        $present_days = $stmt->fetchColumn();

        // Count Absent (Explicit)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = :eid AND MONTH(date) = :m AND YEAR(date) = :y AND status = 'absent'");
        $stmt->execute(['eid' => $emp['id'], 'm' => $month, 'y' => $year]);
        $absent_days = $stmt->fetchColumn();

        $total_salary = $present_days * $emp['salary_per_day'];

        // Insert Record
        $ins = $pdo->prepare("INSERT INTO salary_records (employee_id, month, year, present_days, absent_days, total_salary) VALUES (:eid, :m, :y, :p, :a, :t)");
        $ins->execute([
            'eid' => $emp['id'],
            'm' => $month,
            'y' => $year,
            'p' => $present_days,
            'a' => $absent_days,
            't' => $total_salary
        ]);
    }
    $message = "Salaries generated successfully!";
}

// Fetch Records
$sql = "
    SELECT s.*, e.first_name, e.last_name, e.salary_per_day 
    FROM salary_records s 
    JOIN employees e ON s.employee_id = e.id 
    WHERE s.month = :m AND s.year = :y
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['m' => $month, 'y' => $year]);
$salaries = $stmt->fetchAll();

// Export CSV
if (isset($_POST['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="salary_report_' . $month . '_' . $year . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee ID', 'Name', 'Present Days', 'Absent Days', 'Daily Rate', 'Total Salary']);

    foreach ($salaries as $row) {
        fputcsv($output, [
            $row['employee_id'], // Use JOIN if available, currently not in SELECT but e.first_name is
            $row['first_name'] . ' ' . $row['last_name'],
            $row['present_days'],
            $row['absent_days'],
            $row['salary_per_day'],
            $row['total_salary']
        ]);
    }
    fclose($output);
    exit;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Salary Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <form class="d-flex align-items-center gap-2" method="get">
            <select name="month" class="form-select form-select-sm">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-select form-select-sm">
                <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="mb-3">
    <form method="post" class="d-inline">
        <button type="submit" name="generate" class="btn btn-primary">
            <i class="bi bi-calculator"></i> Generate Salaries
        </button>
    </form>
    <?php if (count($salaries) > 0): ?>
        <form method="post" class="d-inline ms-2">
            <button type="submit" name="export" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Present Days</th>
                        <th>Absent Days</th>
                        <th>Daily Rate</th>
                        <th>Total Salary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($salaries) > 0): ?>
                        <?php foreach ($salaries as $row): ?>
                            <tr>
                                <td>
                                    <span
                                        class="fw-bold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                                </td>
                                <td><?php echo $row['present_days']; ?></td>
                                <td><?php echo $row['absent_days']; ?></td>
                                <td><?php echo format_money($row['salary_per_day']); ?></td>
                                <td class="fw-bold text-success"><?php echo format_money($row['total_salary']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No salary records generated for this period.
                                Click 'Generate Salaries'.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>