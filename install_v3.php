<?php
require_once 'config/db.php';

try {
    echo "Updating database schema for V3 (Profiles & Education)...\n";

    // 1. Employee Profiles Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS employee_profiles (
        emp_id INT PRIMARY KEY,
        dob DATE,
        gender ENUM('Male', 'Female', 'Other'),
        phone VARCHAR(20),
        address TEXT,
        emergency_contact VARCHAR(20),
        designation VARCHAR(100),
        department VARCHAR(100),
        joining_date DATE,
        profile_photo VARCHAR(255),
        profile_completed TINYINT DEFAULT 0,
        temp_access_expiry DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE
    )");
    echo "Created employee_profiles table.\n";

    // 2. Employee Education Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS employee_education (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emp_id INT,
        qualification ENUM('10th', '12th', 'Diploma', 'UG', 'PG') NOT NULL,
        degree VARCHAR(100) NOT NULL,
        specialization VARCHAR(100) NOT NULL,
        institution VARCHAR(100) NOT NULL,
        university_or_board VARCHAR(100) NOT NULL,
        year_of_passing INT NOT NULL,
        percentage_or_cgpa DECIMAL(5,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE
    )");
    echo "Created employee_education table.\n";

    // 3. Education Certificates Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS education_certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        education_id INT UNIQUE, -- One certificate per education
        certificate_file VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (education_id) REFERENCES employee_education(id) ON DELETE CASCADE
    )");
    echo "Created education_certificates table.\n";

    // 4. Temporary Access Requests Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS temp_access_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emp_id INT,
        reason TEXT NOT NULL,
        requested_till DATE NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE
    )");
    echo "Created temp_access_requests table.\n";

    // Create directories
    if (!file_exists('uploads'))
        mkdir('uploads');
    if (!file_exists('uploads/profile_photos'))
        mkdir('uploads/profile_photos');
    if (!file_exists('uploads/certificates'))
        mkdir('uploads/certificates');

    echo "Database V3 update completed successfully!";

} catch (PDOException $e) {
    die("DB Update Failed: " . $e->getMessage());
}
?>