<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
$user_id = requireApiToken($pdo, $data);

$stmt = $pdo->prepare("SELECT * FROM Currency WHERE user_id = ? ORDER BY FIELD(currency_code, 'USD', 'KHR', 'EUR', 'GBP')");
$stmt->execute([$user_id]);
$currencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

global $exchange_rates;
$totalUsd = 0;
foreach ($currencies as $c) {
    $rate = $exchange_rates[$c['currency_code']] ?? 1;
    $totalUsd += $c['wallet'] / $rate;
}

echo json_encode([
    'status' => 'success',
    'currencies' => $currencies,
    'total_net_worth_usd' => round($totalUsd, 2),
]);
