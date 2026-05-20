<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$amount = floatval($data['amount'] ?? 0);
$category_id = intval($data['category_id'] ?? $data['categoryId'] ?? 0);
$category_name = trim($data['category_name'] ?? $data['categoryName'] ?? '');
$payment_method_id = intval($data['payment_method_id'] ?? $data['paymentMethodId'] ?? 1);
$note = trim($data['note'] ?? '');
$currency_code = $data['currency_code'] ?? 'USD';

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid expense amount']);
    exit;
}

if ($category_id <= 0 && $category_name !== '') {
    $stmt = $pdo->prepare('SELECT category_id FROM Categories WHERE record_type = ? AND category_name = ? LIMIT 1');
    $stmt->execute(['expense', $category_name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $category_id = (int) $row['category_id'];
    }
}

if ($category_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'A valid expense category is required. Call get_categories with record_type "expense" and send category_id (or category_name).',
    ]);
    exit;
}

$stmt = $pdo->prepare('SELECT category_id FROM Categories WHERE category_id = ? AND record_type = ?');
$stmt->execute([$category_id, 'expense']);
if (!$stmt->fetch()) {
    $stmt = $pdo->query("SELECT category_id, category_name FROM Categories WHERE record_type = 'expense' ORDER BY display_order LIMIT 30");
    $expense_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid category_id for an expense. Ids are not always 1 — call get_categories with record_type "expense" and use a category_id from the response (or send category_name).',
        'received_category_id' => $category_id,
        'expense_categories' => $expense_categories,
    ]);
    exit;
}

$stmt = $pdo->prepare('SELECT method_id FROM Payment_methods WHERE method_id = ? AND (user_id IS NULL OR user_id = ?)');
$stmt->execute([$payment_method_id, $user_id]);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare('SELECT method_id FROM Payment_methods WHERE user_id IS NULL OR user_id = ? ORDER BY method_id ASC LIMIT 1');
    $stmt->execute([$user_id]);
    $pm = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pm) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No payment methods available. Seed Payment_methods or call get_payment_methods.',
        ]);
        exit;
    }
    $payment_method_id = (int) $pm['method_id'];
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO Expenses (user_id, amount, date, currency_code, category_id, payment_method_id, note) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $amount, date('Y-m-d H:i:s'), $currency_code, $category_id, $payment_method_id, $note]);
    $expense_id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?');
    $stmt->execute([$amount, $user_id, $currency_code]);

    $pdo->commit();
    echo json_encode([
        'status' => 'success',
        'message' => 'Expense added successfully',
        'expense_id' => $expense_id,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Unable to add expense: ' . $e->getMessage()]);
}
