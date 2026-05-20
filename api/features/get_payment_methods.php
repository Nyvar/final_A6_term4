<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$stmt = $pdo->prepare('SELECT * FROM Payment_methods WHERE user_id IS NULL OR user_id = ?');
$stmt->execute([$user_id]);
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'payment_methods' => $methods,
]);
