<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$currency_code = $data['currency_code'] ?? '';
$amount = floatval($data['amount'] ?? 0);

if ($currency_code === '') {
    echo json_encode(['status' => 'error', 'message' => 'Currency code is required']);
    exit;
}

$stmt = $pdo->prepare('UPDATE Currency SET wallet = ? WHERE user_id = ? AND currency_code = ?');
$stmt->execute([$amount, $user_id, $currency_code]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Currency not found']);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Balance updated successfully',
    'currency_code' => $currency_code,
    'wallet' => $amount,
]);
