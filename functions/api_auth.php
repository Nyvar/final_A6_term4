<?php

function getUserIdFromToken(PDO $pdo, string $token): ?int
{
    if ($token === '') {
        return null;
    }
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT user_id FROM user_access_tokens WHERE token = :token AND expires_at > :now");
    $stmt->execute(['token' => $token, 'now' => $now]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['user_id'] : null;
}

function requireApiToken(PDO $pdo, array $data): int
{
    $user_id = getUserIdFromToken($pdo, $data['token'] ?? '');
    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
        exit;
    }
    return $user_id;
}
