<?php
require_once 'config/db.php';

try {
    // New hash for 'admin123'
    $new_hash = '$2y$10$AYI7qNExxC3nVt2DB7Nlm.41.bD4xKPGkz10j5EyNxgyG5DOze0PS';

    $stmt = $pdo->prepare("UPDATE admins SET password = :password WHERE username = 'admin'");
    $stmt->bindParam(':password', $new_hash);
    $stmt->execute();

    echo "<h1>Success!</h1>";
    echo "<p>Admin password has been reset to: <strong>admin123</strong></p>";
    echo "<p><a href='admin/login.php'>Click here to Login</a></p>";

} catch (PDOException $e) {
    echo "Error updating record: " . $e->getMessage();
}
?>