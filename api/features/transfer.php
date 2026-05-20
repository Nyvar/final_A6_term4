<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$amount = floatval($data['amount'] ?? 0);
$from_currency = $data['from_currency'] ?? 'USD';
$to_currency = $data['to_currency'] ?? 'KHR';
$exchange_rate = floatval($data['exchange_rate'] ?? 1);
$note = trim($data['note'] ?? '');

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid transfer amount']);
    exit;
}

if ($from_currency === $to_currency) {
    echo json_encode(['status' => 'error', 'message' => 'Source and destination currency must differ']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT wallet FROM Currency WHERE user_id = ? AND currency_code = ? FOR UPDATE');
    $stmt->execute([$user_id, $from_currency]);
    $fromBalance = $stmt->fetchColumn();

    if ($fromBalance === false) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Source currency not found']);
        exit;
    }

    if ((float) $fromBalance < $amount) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Insufficient balance']);
        exit;
    }

    $convertedAmount = $amount * $exchange_rate;

    $stmt = $pdo->prepare('INSERT INTO Transfer (user_id, amount, from_currency, to_currency, exchange_rate, date, note) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $amount, $from_currency, $to_currency, $exchange_rate, date('Y-m-d H:i:s'), $note]);
    $transfer_id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?');
    $stmt->execute([$amount, $user_id, $from_currency]);

    $stmt = $pdo->prepare('UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?');
    $stmt->execute([$convertedAmount, $user_id, $to_currency]);

    $pdo->commit();
    echo json_encode([
        'status' => 'success',
        'message' => 'Transfer completed',
        'transfer_id' => $transfer_id,
        'converted_amount' => $convertedAmount,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Unable to transfer: ' . $e->getMessage()]);
}
