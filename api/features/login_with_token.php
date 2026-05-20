<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$token = $data['token'] ?? '';
if ($token === '') {
    echo json_encode(['status' => 'error', 'message' => 'Token is required']);
    exit;
}
$user_id = getUserIdFromToken($pdo, $token);
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
    exit;
}
$stmt = $pdo->prepare("SELECT user_id, username, email FROM Users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Token is valid',
        'user_id' => (int) $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email'],
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
}
