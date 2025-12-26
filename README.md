# Office Attendance & Salary Management System

## Prerequisites
- **XAMPP** (or any AMP stack like WAMP/MAMP) installed on your computer.
- Basic knowledge of starting Apache and MySQL services.

## Step-by-Step Installation Guide

### 1. Setup Project Folder
1. Locate your XAMPP installation folder (usually `C:\xampp`).
2. Open the `htdocs` folder inside it (`C:\xampp\htdocs`).
3. Create a new folder named `attendance-system`.
4. Copy ALL files from this project into that folder.
   
   **Your path should look like:** `C:\xampp\htdocs\attendance-system\`

### 2. Setup Database
1. Open the **XAMPP Control Panel**.
2. Click **Start** for both **Apache** and **MySQL**.
3. Open your browser and go to: `http://localhost/phpmyadmin`
4. Click **New** in the sidebar.
5. Database Name: `attendance_db`
6. Click **Create**.
7. Click on the `attendance_db` database you just created.
8. Click the **Import** tab.
9. Click **Choose File** and select the file `database/schema.sql` from your project folder.
10. Click **Import** (or Go) at the bottom.

### 3. Verify Configuration
The default XAMPP credentials are pre-configured in `config/db.php`:
- **Host:** localhost
- **User:** root
- **Password:** (empty)
- **Database:** attendance_db

If you have a password set for root, update `config/db.php`.

## How to use

### Admin Portal
1. **URL:** [http://localhost/attendance-system/admin/login.php](http://localhost/attendance-system/admin/login.php)
2. **Default Credentials:**
   - **Username:** `admin`
   - **Password:** `admin123`
3. **Tasks:**
   - Go to "Employees" to create a new employee account (e.g., EMP001).
   - Set their salary and password.

### Employee Portal
1. **URL:** [http://localhost/attendance-system/employee/login.php](http://localhost/attendance-system/employee/login.php)
2. **Credentials:** Use the Employee ID and Password you created in the Admin panel.
3. **Tasks:**
   - **Check In** when you start work.
   - **Check Out** requires a work summary/link.

## Troubleshooting
- **"Object not found"**: Make sure the folder name in `htdocs` matches the URL (attendance-system).
- **Database Connection Error**: Check if MySQL is running in XAMPP and `config/db.php` credentials are correct.
