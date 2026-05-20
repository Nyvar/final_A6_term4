<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
requireApiToken($pdo, $data);

$name = trim($data['category_name'] ?? '');
$type = $data['record_type'] ?? 'expense';
$color = $data['color'] ?? '#6dbf8c';

if ($name === '') {
    echo json_encode(['status' => 'error', 'message' => 'Category name is required']);
    exit;
}

if (!in_array($type, ['expense', 'income'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid record type']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO Categories (category_name, record_type, color) VALUES (?, ?, ?)');
$stmt->execute([$name, $type, $color]);

echo json_encode([
    'status' => 'success',
    'message' => 'Category added successfully',
    'category_id' => (int) $pdo->lastInsertId(),
]);
