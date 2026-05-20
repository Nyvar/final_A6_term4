<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$current = $data['current_password'] ?? '';
$new = $data['new_password'] ?? '';
$confirm = $data['confirm_password'] ?? $new;

if ($current === '' || $new === '') {
    echo json_encode(['status' => 'error', 'message' => 'Current and new password are required']);
    exit;
}

$stmt = $pdo->prepare('SELECT password FROM Users WHERE user_id = ?');
$stmt->execute([$user_id]);
$db_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$db_user || !password_verify($current, $db_user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
    exit;
}
if (strlen($new) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'New password must be at least 6 characters']);
    exit;
}
if ($new !== $confirm) {
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match']);
    exit;
}

$hashed = password_hash($new, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE Users SET password = ? WHERE user_id = ?');
$stmt->execute([$hashed, $user_id]);

echo json_encode(['status' => 'success', 'message' => 'Password changed successfully']);
