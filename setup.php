<?php
// setup.php
// COMPLETE DATABASE INSTALLER FOR ATTENDANCE SYSTEM

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password
// If you changed your root password, update it here temporarily to run this script.

try {
    // 1. Connect to MySQL Server (No Database selected yet)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>Starting Installation...</h3>";

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS attendance_db");
    echo "✅ Database 'attendance_db' created or already exists.<br>";

    // 3. Select Database
    $pdo->exec("USE attendance_db");

    // 4. Create Tables
    $queries = [
        // 1. Admins
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'sub_admin') DEFAULT 'super_admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // 2. Employees
        "CREATE TABLE IF NOT EXISTS employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id VARCHAR(20) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            designation VARCHAR(50),
            salary_per_day DECIMAL(10, 2) DEFAULT 0.00,
            monthly_salary DECIMAL(10, 2) DEFAULT 0.00,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // 3. Employee Profiles (Extended Info)
        "CREATE TABLE IF NOT EXISTS employee_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emp_id INT NOT NULL,
            dob DATE,
            gender ENUM('Male', 'Female', 'Other'),
            phone VARCHAR(20),
            address TEXT,
            profile_photo VARCHAR(255),
            profile_completed TINYINT(1) DEFAULT 0,
            temp_access_expiry DATE DEFAULT NULL,
            FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE
        )",

        // 4. Educational Details
        // 4. Employee Education
        "CREATE TABLE IF NOT EXISTS employee_education (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emp_id INT NOT NULL,
            qualification VARCHAR(50),
            degree VARCHAR(100),
            specialization VARCHAR(100),
            institution VARCHAR(100),
            university_or_board VARCHAR(100),
            year_of_passing INT,
            percentage_or_cgpa VARCHAR(10),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE
        )",

        // 5. Education Certificates
        "CREATE TABLE IF NOT EXISTS education_certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            education_id INT NOT NULL,
            certificate_file VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (education_id) REFERENCES employee_education(id) ON DELETE CASCADE
        )",

        // 5. Attendance
        "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            date DATE NOT NULL,
            check_in_time TIMESTAMP NULL DEFAULT NULL,
            check_out_time TIMESTAMP NULL DEFAULT NULL,
            status ENUM('present', 'absent', 'pending', 'rejected', 'leave') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )",

        // 6. Work Submissions
        "CREATE TABLE IF NOT EXISTS work_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            attendance_id INT NOT NULL,
            description TEXT,
            link VARCHAR(255),
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE
        )",

        // 7. Salary Records
        "CREATE TABLE IF NOT EXISTS salary_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            month INT NOT NULL,
            year INT NOT NULL,
            present_days INT DEFAULT 0,
            absent_days INT DEFAULT 0,
            total_salary DECIMAL(10, 2) NOT NULL,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )",

        // 8. Tasks
        "CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            assigned_to INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            due_date DATE,
            status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
            category VARCHAR(50) DEFAULT 'General',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES employees(id) ON DELETE CASCADE
        )",

        // 9. Notifications
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL COMMENT '0 for Admin, else Employee ID',
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // 10. Leaves
        "CREATE TABLE IF NOT EXISTS leaves (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            type ENUM('sick', 'casual', 'earned', 'wfh') NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT,
            status ENUM('pending', 'approved', 'rejected', 'revoked') DEFAULT 'pending',
            admin_response TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )",

        // 11. Teams
        "CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_by_admin INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // 12. Team Members
        "CREATE TABLE IF NOT EXISTS team_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            emp_id INT NOT NULL,
            role ENUM('member', 'sub_admin') DEFAULT 'member',
            added_by INT DEFAULT 0,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE
        )",

        // 13. Team Chat
        "CREATE TABLE IF NOT EXISTS team_chat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            sender_id INT NOT NULL,
            message TEXT,
            attachment_path VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES employees(id) ON DELETE CASCADE
        )",

        // 14. Messages (Private Chat)
        "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            sender_role ENUM('admin', 'employee') NOT NULL,
            receiver_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // 15. Emails (Internal System)
        "CREATE TABLE IF NOT EXISTS emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            sender_role ENUM('admin', 'employee') NOT NULL,
            subject VARCHAR(255),
            body TEXT,
            attachment_path VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // 16. Email Recipients
        "CREATE TABLE IF NOT EXISTS email_recipients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email_id INT NOT NULL,
            recipient_id INT NOT NULL,
            type ENUM('to', 'cc', 'bcc') DEFAULT 'to',
            is_read TINYINT(1) DEFAULT 0,
            is_deleted TINYINT(1) DEFAULT 0,
            FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
        )",

        // 17. Settings
        "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // 18. Holidays
        "CREATE TABLE IF NOT EXISTS holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            date DATE NOT NULL UNIQUE,
            type ENUM('national', 'restricted', 'optional') DEFAULT 'national',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }
    echo "✅ All Tables Created Successfully.<br>";

    // 5. Create Default Admin
    // Check if admin exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE username='admin'");
    if ($stmt->fetchColumn() == 0) {
        $passHash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admins (username, password, role) VALUES ('admin', ?, 'super_admin')")
            ->execute([$passHash]);
        echo "✅ Default Admin created (User: admin, Pass: admin123).<br>";
    } else {
        echo "ℹ️ Admin account already exists.<br>";
    }

    echo "<h3>System Installed Successfully!</h3>";
    echo "<p><a href='index.php'>Go to Homepage</a></p>";

} catch (PDOException $e) {
    if ($e->getCode() == 1045) {
        die("<h3 style='color:red;'>Access Denied</h3>
             <p>Your MySQL 'root' user has a password. Please edit this <code>setup.php</code> file and set <code>\$pass = 'YOUR_PASSWORD';</code> at the top.</p>");
    }
    die("Setup Error: " . $e->getMessage());
}
?>