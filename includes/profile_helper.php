<?php
// includes/profile_helper.php

function check_profile_completion($pdo, $emp_id)
{
    // 1. Check Profile Fields
    $stmt = $pdo->prepare("SELECT * FROM employee_profiles WHERE emp_id = ?");
    $stmt->execute([$emp_id]);
    $profile = $stmt->fetch();

    if (!$profile)
        return false;

    // List of mandatory fields
    // Assuming designation, department, joining_date must be filled by Admin first?
    // The prompt says "All mandatory profile fields are filled".
    // Let's assume all fields in the table are mandatory except temp_access_expiry.
    $mandatory = ['dob', 'gender', 'phone', 'address', 'emergency_contact', 'designation', 'department', 'joining_date', 'profile_photo'];
    foreach ($mandatory as $field) {
        if (empty($profile[$field]))
            return false;
    }

    // 2. Check Education (At least one)
    $stmt = $pdo->prepare("SELECT id FROM employee_education WHERE emp_id = ?");
    $stmt->execute([$emp_id]);
    $edus = $stmt->fetchAll(PDO::FETCH_COLUMN); // Get IDs

    if (count($edus) == 0)
        return false;

    // 3. Check Certificate (For THAT education - logic: "Certificate uploaded for that education")
    // Does it mean ALL education must have certificates? "At least one education record exists ... Certificate uploaded for that education"
    // implies if multiple exist, they might all need it, OR just the one that satisfies the condition.
    // Let's be strict: All added education records must have a certificate.
    // OR Let's be lenient: At least one Education Record WITH a Certificate is required.
    // Prompt: "At least one education record exists... Certificate uploaded for that education" -> singular.
    // I will enforce: At least ONE fully documented education exists.

    $has_valid_edu = false;
    foreach ($edus as $edu_id) {
        $c_stmt = $pdo->prepare("SELECT COUNT(*) FROM education_certificates WHERE education_id = ?");
        $c_stmt->execute([$edu_id]);
        if ($c_stmt->fetchColumn() > 0) {
            $has_valid_edu = true;
            break;
        }
    }

    if (!$has_valid_edu)
        return false;

    return true;
}

function update_profile_status($pdo, $emp_id)
{
    $is_complete = check_profile_completion($pdo, $emp_id);
    $status = $is_complete ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE employee_profiles SET profile_completed = ? WHERE emp_id = ?");
    $stmt->execute([$status, $emp_id]);
    return $status;
}
?>