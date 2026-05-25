<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$confirm_password = $data['confirm_password'] ?? $password;

if ($username === '' || $email === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
    exit;
}
if (strlen($username) < 3) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be at least 3 characters']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters']);
    exit;
}
if ($password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Username already taken']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM Users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO Users (username, email, password) VALUES (?, ?, ?)");
if (!$stmt->execute([$username, $email, $hashed_password])) {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
    exit;
}

$user_id = (int) $pdo->lastInsertId();
$stmt = $pdo->prepare("INSERT INTO Currency (user_id, currency_code, currency_name, symbol, wallet) VALUES 
    (?, ?, ?, ?, ?),
    (?, ?, ?, ?, ?),
    (?, ?, ?, ?, ?),
    (?, ?, ?, ?, ?)");
$stmt->execute([
    $user_id, 'USD', 'US Dollar', '$', 0,
    $user_id, 'KHR', 'Cambodian Riel', '៛', 0,
    $user_id, 'EUR', 'Euro', '€', 0,
    $user_id, 'GBP', 'British Pound', '£', 0
]);

$token = bin2hex(random_bytes(16));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
$stmt = $pdo->prepare("INSERT INTO user_access_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $token, $expires_at]);

echo json_encode([
    'status' => 'success',
    'message' => 'Registration successful',
    'user_id' => $user_id,
    'username' => $username,
    'email' => $email,
    'token' => $token,
    'expires_at' => $expires_at,
]);
