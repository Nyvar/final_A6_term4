<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');

if ($username === '' || $email === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email']);
    exit;
}

$stmt = $pdo->prepare('UPDATE Users SET username = ?, email = ? WHERE user_id = ?');
if ($stmt->execute([$username, $email, $user_id])) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Profile updated successfully',
        'username' => $username,
        'email' => $email,
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Profile update failed']);
}
