<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
requireApiToken($pdo, $data);

$record_type = $data['record_type'] ?? null;
if ($record_type) {
    $stmt = $pdo->prepare('SELECT * FROM Categories WHERE record_type = ? ORDER BY display_order');
    $stmt->execute([$record_type]);
} else {
    $stmt = $pdo->query('SELECT * FROM Categories ORDER BY record_type, display_order');
}
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'categories' => $categories,
]);
