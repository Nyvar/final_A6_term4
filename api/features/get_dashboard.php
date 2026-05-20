<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$activeCurrency = $data['currency'] ?? 'USD';
$dateFilter = $data['date_filter'] ?? 'month';
$startDateParam = $data['start_date'] ?? null;
$endDateParam = $data['end_date'] ?? null;
$searchQuery = trim($data['search'] ?? '');

$dateCondition = '';
$dateRangeText = 'All Time';

if ($dateFilter === 'interval' && $startDateParam && $endDateParam) {
    $start = $pdo->quote($startDateParam);
    $end = $pdo->quote($endDateParam);
    $dateCondition = "AND DATE(date) BETWEEN $start AND $end";
    $dateRangeText = date('M d, Y', strtotime($startDateParam)) . ' - ' . date('M d, Y', strtotime($endDateParam));
} else {
    switch ($dateFilter) {
        case 'day':
            $dateCondition = 'AND DATE(date) = CURDATE()';
            $dateRangeText = date('F d, Y');
            break;
        case 'week':
            $dateCondition = 'AND YEARWEEK(date) = YEARWEEK(CURDATE())';
            $dateRangeText = 'This Week';
            break;
        case 'month':
            $dateCondition = "AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
            $dateRangeText = date('F Y');
            break;
        case 'year':
            $dateCondition = 'AND YEAR(date) = YEAR(CURDATE())';
            $dateRangeText = date('Y');
            break;
        default:
            $dateCondition = '';
            $dateRangeText = 'All Time';
            break;
    }
}

$searchCondition = '';
if ($searchQuery !== '') {
    $q = $pdo->quote('%' . $searchQuery . '%');
    $searchCondition = "AND (e.note LIKE $q OR c.category_name LIKE $q OR CAST(e.amount AS CHAR) LIKE $q)";
}

$stmt = $pdo->prepare('SELECT * FROM Currency WHERE user_id = ?');
$stmt->execute([$user_id]);
$currencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$walletBalance = 0;
$currencySymbol = '$';
foreach ($currencies as $c) {
    if ($c['currency_code'] === $activeCurrency) {
        $walletBalance = (float) $c['wallet'];
        $currencySymbol = $c['symbol'];
        break;
    }
}

$stmt = $pdo->prepare("
    SELECT
        COALESCE((SELECT SUM(amount) FROM Expenses WHERE user_id = ? AND currency_code = ? $dateCondition), 0) as total_expense,
        COALESCE((SELECT SUM(amount) FROM Income WHERE user_id = ? AND currency_code = ? $dateCondition), 0) as total_income
");
$stmt->execute([$user_id, $activeCurrency, $user_id, $activeCurrency]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT c.category_name, c.color, SUM(e.amount) as total
    FROM Expenses e
    LEFT JOIN Categories c ON e.category_id = c.category_id
    WHERE e.user_id = ? AND e.currency_code = ? $dateCondition $searchCondition
    GROUP BY e.category_id
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute([$user_id, $activeCurrency]);
$expenseByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    (SELECT 'expense' as type, e.expense_id as id, e.amount, e.date, e.note,
            c.category_name, pm.method_name as payment_method, e.currency_code
     FROM Expenses e
     LEFT JOIN Categories c ON e.category_id = c.category_id
     LEFT JOIN Payment_methods pm ON e.payment_method_id = pm.method_id
     WHERE e.user_id = ? AND e.currency_code = ? $dateCondition $searchCondition
     ORDER BY e.date DESC LIMIT 50)
    UNION ALL
    (SELECT 'income' as type, i.income_id as id, i.amount, i.date, i.note,
            'Income' as category_name, NULL as payment_method, i.currency_code
     FROM Income i
     WHERE i.user_id = ? AND i.currency_code = ? $dateCondition
     ORDER BY i.date DESC LIMIT 50)
    UNION ALL
    (SELECT 'transfer' as type, t.transfer_id as id, t.amount, t.date, t.note,
            CONCAT('Transfer: ', t.from_currency, ' → ', t.to_currency) as category_name,
            NULL as payment_method, t.from_currency as currency_code
     FROM Transfer t
     WHERE t.user_id = ? $dateCondition
     ORDER BY t.date DESC LIMIT 50)
    ORDER BY date DESC LIMIT 100
");
$stmt->execute([$user_id, $activeCurrency, $user_id, $activeCurrency, $user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

global $exchange_rates;
$totalUsd = 0;
foreach ($currencies as $c) {
    $rate = $exchange_rates[$c['currency_code']] ?? 1;
    $totalUsd += $c['wallet'] / $rate;
}

echo json_encode([
    'status' => 'success',
    'currency' => $activeCurrency,
    'currency_symbol' => $currencySymbol,
    'date_filter' => $dateFilter,
    'date_range_text' => $dateRangeText,
    'wallet_balance' => $walletBalance,
    'total_expense' => (float) $stats['total_expense'],
    'total_income' => (float) $stats['total_income'],
    'total_net_worth_usd' => round($totalUsd, 2),
    'expense_by_category' => $expenseByCategory,
    'currencies' => $currencies,
    'transactions' => $transactions,
]);
