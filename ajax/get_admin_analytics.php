<?php
// ajax/get_admin_analytics.php
// Returns JSON data for the Admin Analytics Dashboard
// Strict Admin Only Access

error_reporting(E_ALL);
ini_set('display_errors', 0); // Suppress HTML errors in JSON response

header('Content-Type: application/json');

ob_start();
session_start();
require_once '../config/db.php';
ob_clean();

// 1. Security Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized Access']);
    exit;
}

$response = [];

try {
    // A. SUMMARY CARDS (Today)
    $today = date('Y-m-d');

    // Total Active Employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
    $response['kpi']['total_employees'] = (int) $stmt->fetchColumn();

    // Present Today
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) FROM attendance WHERE date = ? AND status = 'present'");
    $stmt->execute([$today]);
    $response['kpi']['present_today'] = (int) $stmt->fetchColumn();

    // Absent Today (Explicitly marked 'absent')
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) FROM attendance WHERE date = ? AND status = 'absent'");
    $stmt->execute([$today]);
    $response['kpi']['absent_today'] = (int) $stmt->fetchColumn();

    // WFH Today (Approved 'wfh' leaves intersecting today)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) FROM leaves 
                           WHERE status = 'approved' AND type = 'wfh' 
                           AND ? BETWEEN start_date AND end_date");
    $stmt->execute([$today]);
    $response['kpi']['wfh_today'] = (int) $stmt->fetchColumn();

    // B. WORK TYPE DISTRIBUTION (All time or visible range)
    // Categorize tasks
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM tasks WHERE category IS NOT NULL GROUP BY category");
    $response['work_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // C. PRODUCTIVITY vs PRESENCE (Top 10 Employees - Last 30 Days)
    // Complexity: Calculate Avg Hours worked and Count of Completed Tasks per employee
    $sql_prod = "SELECT 
                    CONCAT(e.first_name, ' ', e.last_name) as u_name,
                    COUNT(t.id) as tasks_completed,
                    SUM(TIMESTAMPDIFF(HOUR, a.check_in_time, a.check_out_time)) as total_hours
                 FROM employees e
                 LEFT JOIN tasks t ON e.id = t.assigned_to 
                    AND t.status = 'completed' 
                    AND t.due_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 LEFT JOIN attendance a ON e.id = a.employee_id 
                    AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 WHERE e.status = 'active'
                 GROUP BY e.id
                 ORDER BY tasks_completed DESC
                 LIMIT 10";
    $response['productivity'] = $pdo->query($sql_prod)->fetchAll(PDO::FETCH_ASSOC);

    // Calc Productivity Score on PHP side: (tasks / hours) * 10
    foreach ($response['productivity'] as &$row) {
        $h = (float) $row['total_hours'];
        $t = (int) $row['tasks_completed'];
        $score = ($h > 0) ? round(($t / $h) * 10, 1) : 0; // *10 multiplier for scale
        $row['score'] = $score;
        $row['total_hours'] = $h; // Ensure float
    }

    // D. ATTENDANCE RISK SCORE CALCULATION
    // Check last 30 days behavior
    // Rules: Late (+5), Early (+5), Missed Checkout (+15), Absent (+10)
    // We fetch raw data and aggregate in PHP for flexibility
    // Optimization: Do aggregation in SQL
    $sql_risk = "SELECT 
                    e.id, 
                    CONCAT(e.first_name, ' ', e.last_name) as name,
                    SUM(CASE WHEN TIME(a.check_in_time) > '09:45:00' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN TIME(a.check_out_time) < '17:00:00' AND a.check_out_time IS NOT NULL THEN 1 ELSE 0 END) as early_count,
                    SUM(CASE WHEN a.check_in_time IS NOT NULL AND a.check_out_time IS NULL AND a.date < CURDATE() THEN 1 ELSE 0 END) as missed_count,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                 FROM employees e
                 JOIN attendance a ON e.id = a.employee_id
                 WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 AND e.status = 'active'
                 GROUP BY e.id";

    $risk_data = $pdo->query($sql_risk)->fetchAll(PDO::FETCH_ASSOC);
    $final_risks = [];

    foreach ($risk_data as $r) {
        $score = 0;
        $score += ($r['late_count'] * 5);
        $score += ($r['early_count'] * 5);
        $score += ($r['missed_count'] * 15); // High penalty
        $score += ($r['absent_count'] * 10);

        $level = 'Low';
        if ($score > 70)
            $level = 'High';
        elseif ($score > 30)
            $level = 'Medium';

        // Add to list if score > 0
        if ($score > 0) {
            $final_risks[] = [
                'name' => $r['name'],
                'score' => min($score, 100), // Cap at 100
                'level' => $level,
                'details' => "Late: {$r['late_count']}, Missed: {$r['missed_count']}"
            ];
        }
    }

    // Sort by Risk Score DESC
    usort($final_risks, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    $response['risk_monitor'] = array_slice($final_risks, 0, 10); // Top 10

    // E. BEHAVIORAL ALERTS (Derived from Risk Data)
    $alerts = [];
    foreach ($risk_data as $r) {
        if ($r['missed_count'] >= 2) {
            $alerts[] = "⚠️ <strong>{$r['name']}</strong> missed checkout {$r['missed_count']} times recently.";
        }
        if ($r['late_count'] >= 4) {
            $alerts[] = "⏰ <strong>{$r['name']}</strong> was late {$r['late_count']} times this month.";
        }
    }
    $response['alerts'] = array_slice($alerts, 0, 5); // Top 5 alerts

    // F. TEAM PERFORMANCE
    // Avg Attendance % per Team
    // (SUM(Present) / COUNT(Total Members * Days)) * 100
    // Simplified: Just Avg present days
    $sql_team = "SELECT 
                    t.name, 
                    COUNT(tm.emp_id) as member_count,
                    (SELECT COUNT(*) FROM attendance a 
                     JOIN team_members tm2 ON a.employee_id = tm2.emp_id 
                     WHERE tm2.team_id = t.id 
                     AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                     AND a.status = 'present') as total_presents
                 FROM teams t
                 LEFT JOIN team_members tm ON t.id = tm.team_id
                 GROUP BY t.id";
    $teams_raw = $pdo->query($sql_team)->fetchAll(PDO::FETCH_ASSOC);
    $teams_perf = [];
    foreach ($teams_raw as $tr) {
        // Assume 22 working days in last 30 days approx
        $possible_days = $tr['member_count'] * 22;
        $perc = ($possible_days > 0) ? round(($tr['total_presents'] / $possible_days) * 100) : 0;
        $teams_perf[] = [
            'name' => $tr['name'],
            'attendance_perc' => min($perc, 100),
            'member_count' => $tr['member_count']
        ];
    }
    $response['team_performance'] = $teams_perf;


} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>