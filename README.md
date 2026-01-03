# Office Attendance & Salary Management System

## Installation Guide (GitHub Clone)

### 1. Requirements
- **XAMPP/WAMP** (PHP 8.0+, MySQL/MariaDB)
- **Git** (optional, to clone)

### 2. Setup Project
1. Open your terminal in `htdocs` folder:
   ```sh
   cd C:\xampp\htdocs
   git clone https://github.com/vishnukumar1802/attendance_system.git
   cd attendance_system
   ```

### 3. Setup Database
1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a new database named **`attendance_db`**.
3. Import the `database/schema.sql` file into this database.
   - This creates tables and adds a default admin user.

### 4. Configure Connection
1. Go to `config/` folder.
2. Rename `db.example.php` to `db.php`.
3. Edit `db.php` if your MySQL root password is not empty.

### 5. Run the App
- **Admin Panel:** [http://localhost/attendance_system/admin/login.php](http://localhost/attendance_system/admin/login.php)
- **Employee Panel:** [http://localhost/attendance_system/employee/login.php](http://localhost/attendance_system/employee/login.php)

#### Default Credentials
- **Admin**: `admin` / `admin123`
- **Employee**: Create a new employee from Admin panel first.
