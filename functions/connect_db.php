<?php
function connect_db(): PDO {
    $host = 'localhost';
    $db = 'expense_tracker_2026';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $port = 3306;
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    try {
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit;
    }
}
function create_tables(PDO $pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    );
    CREATE TABLE IF NOT EXISTS user_access_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        amount DECIMAL(10, 2) NOT NULL,
        category VARCHAR(255) NOT NULL,
        date DATE NOT NULL,
        description TEXT,
        user_id INT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";
    $pdo->exec($sql);
}
function seed_data(PDO $pdo) {
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() > 0) {
        return; // Data already seeded
    }
    $sql = "
    INSERT INTO users (username, password) VALUES ('testuser', 'password123');
    INSERT INTO expenses (amount, category, date, description, user_id) VALUES (50.00, 'Food', '2026-04-30', 'Groceries', 1);
    ";
    $pdo->exec($sql);
}