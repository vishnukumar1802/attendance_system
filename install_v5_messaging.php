<?php
require_once 'config/db.php';

try {
    echo "Setting up Live Messaging & Notifications...\n";

    // 1. Create Messages Table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        sender_role ENUM('admin', 'employee') NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Created messages table.\n";

    // 2. Indexing for performance (Optimized for polling)
    // We query by (receiver_id, is_read) and (created_at)
    try {
        $pdo->exec("CREATE INDEX idx_msg_receiver ON messages(receiver_id, is_read)");
        $pdo->exec("CREATE INDEX idx_msg_created ON messages(created_at)");
        $pdo->exec("CREATE INDEX idx_notif_user ON notifications(user_id, is_read)");
    } catch (Exception $e) {
        // Indexes might already exist
        echo "Indexes update (or already existed).\n";
    }

    echo "Messaging Setup Complete!\n";

} catch (PDOException $e) {
    die("Setup Failed: " . $e->getMessage());
}
?>