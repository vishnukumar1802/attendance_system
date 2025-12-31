<?php
require_once 'config/db.php';

try {
    echo "Updating database for Education & Certificates...\n";

    // 1. Create Employee Education Table
    $sql1 = "CREATE TABLE IF NOT EXISTS employee_education (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emp_id INT NOT NULL,
        qualification VARCHAR(50) NOT NULL,
        degree VARCHAR(100) NOT NULL,
        specialization VARCHAR(100) NOT NULL,
        institution VARCHAR(150) NOT NULL,
        university_or_board VARCHAR(150) NOT NULL,
        year_of_passing YEAR NOT NULL,
        percentage_or_cgpa DECIMAL(5,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql1);
    echo "Created employee_education table.\n";

    // 2. Create Education Certificates Table
    $sql2 = "CREATE TABLE IF NOT EXISTS education_certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        education_id INT NOT NULL,
        certificate_file VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (education_id) REFERENCES employee_education(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql2);
    echo "Created education_certificates table.\n";

    // 3. Create Temp Access Requests Table
    $sql3 = "CREATE TABLE IF NOT EXISTS temp_access_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emp_id INT NOT NULL,
        reason TEXT NOT NULL,
        requested_till DATE NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql3);
    echo "Created temp_access_requests table.\n";

    // 4. Ensure employee_profiles has necessary columns
    // Check for temp_access_expiry
    $colCheck = $pdo->query("SHOW COLUMNS FROM employee_profiles LIKE 'temp_access_expiry'");
    if ($colCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE employee_profiles ADD COLUMN temp_access_expiry DATE NULL");
        echo "Added temp_access_expiry to employee_profiles.\n";
    }

    // Check for profile_completed
    $colCheck2 = $pdo->query("SHOW COLUMNS FROM employee_profiles LIKE 'profile_completed'");
    if ($colCheck2->rowCount() == 0) {
        $pdo->exec("ALTER TABLE employee_profiles ADD COLUMN profile_completed TINYINT(1) DEFAULT 0");
        echo "Added profile_completed to employee_profiles.\n";
    }

    // 5. Ensure Upload Directories Exist
    $dirs = [
        'uploads/certificates',
        'uploads/profile_photos'
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo "Created directory: $dir\n";
        }
    }

    echo "Database & Folders Setup Complete!\n";

} catch (PDOException $e) {
    die("DB Setup Failed: " . $e->getMessage());
}
?>