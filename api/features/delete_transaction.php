<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$type = $data['record_type'] ?? '';
$id = intval($data['record_id'] ?? 0);

if ($id <= 0 || !in_array($type, ['expense', 'income', 'transfer'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Valid record type and id are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($type === 'expense') {
        $stmt = $pdo->prepare('SELECT amount, currency_code FROM Expenses WHERE expense_id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $stmt = $pdo->prepare('DELETE FROM Expenses WHERE expense_id = ? AND user_id = ?');
            $stmt->execute([$id, $user_id]);
            $stmt = $pdo->prepare('UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?');
            $stmt->execute([$record['amount'], $user_id, $record['currency_code']]);
        }
    } elseif ($type === 'income') {
        $stmt = $pdo->prepare('SELECT amount, currency_code FROM Income WHERE income_id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $stmt = $pdo->prepare('DELETE FROM Income WHERE income_id = ? AND user_id = ?');
            $stmt->execute([$id, $user_id]);
            $stmt = $pdo->prepare('UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?');
            $stmt->execute([$record['amount'], $user_id, $record['currency_code']]);
        }
    } else {
        $stmt = $pdo->prepare('SELECT amount, from_currency, to_currency, exchange_rate FROM Transfer WHERE transfer_id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $stmt = $pdo->prepare('DELETE FROM Transfer WHERE transfer_id = ? AND user_id = ?');
            $stmt->execute([$id, $user_id]);

            $stmt = $pdo->prepare('UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?');
            $stmt->execute([$record['amount'], $user_id, $record['from_currency']]);

            $convertedAmount = $record['amount'] * $record['exchange_rate'];
            $stmt = $pdo->prepare('UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?');
            $stmt->execute([$convertedAmount, $user_id, $record['to_currency']]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Deleted successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
