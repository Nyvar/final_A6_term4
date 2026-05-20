<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
if ($username === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
    exit;
}
$stmt = $pdo->prepare('SELECT user_id, username, email, password FROM Users WHERE username = ? OR email = ?');
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && password_verify($password, $user['password'])) {
    $token = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("INSERT INTO user_access_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
    $stmt->execute(['user_id' => $user['user_id'], 'token' => $token, 'expires_at' => $expires_at]);
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user_id' => (int) $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'token' => $token,
        'expires_at' => $expires_at,
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
}
