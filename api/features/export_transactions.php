<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$stmt = $pdo->prepare("
    SELECT 'expense' as type, amount, date, note, currency_code
    FROM Expenses WHERE user_id = ?
    UNION ALL
    SELECT 'income' as type, amount, date, note, currency_code
    FROM Income WHERE user_id = ?
    UNION ALL
    SELECT 'transfer' as type, amount, date, note, from_currency as currency_code
    FROM Transfer WHERE user_id = ?
    ORDER BY date DESC
");
$stmt->execute([$user_id, $user_id, $user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'transactions' => $transactions,
]);
