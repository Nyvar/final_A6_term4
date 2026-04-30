<?php
/**
 * @var PDO $pdo
 */
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
if(empty($username) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Username and password are required"]);
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if($user && $password === $user['password']) {
    $token = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("INSERT INTO user_access_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
    $stmt->execute(['user_id' => $user['id'], 'token' => $token, 'expires_at' => $expires_at]);
    echo json_encode(["status" => "success", "message" => "Login successful", 
        "user_id" => $user['id'], "username" => $user['username'], "token" => $token]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
}