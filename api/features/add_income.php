<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$amount = floatval($data['amount'] ?? 0);
$note = trim($data['note'] ?? '');
$currency_code = $data['currency_code'] ?? 'USD';

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid income amount']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO Income (user_id, amount, date, currency_code, note) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $amount, date('Y-m-d H:i:s'), $currency_code, $note]);
    $income_id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?');
    $stmt->execute([$amount, $user_id, $currency_code]);

    $pdo->commit();
    echo json_encode([
        'status' => 'success',
        'message' => 'Income added successfully',
        'income_id' => $income_id,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Unable to add income: ' . $e->getMessage()]);
}
