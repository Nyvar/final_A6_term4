<?php
/**
 * @var PDO $pdo
 */
$token = $data['token'] ?? '';
if(empty($token)) {
    echo json_encode(["status" => "error", "message" => "Token is required"]);
    exit;
}
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT * FROM user_access_tokens WHERE token = :token AND expires_at > :now");
$stmt->execute(['token' => $token, 'now' => $now]);
$token_data = $stmt->fetch(PDO::FETCH_ASSOC);
if($token_data) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $token_data['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if($user) {
        echo json_encode(["status" => "success", "message" => "Token is valid", 
            "user_id" => $user['id'], "username" => $user['username']]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
}