<?php
/**
 * @var PDO $pdo
 * @var array $data
 */
requireApiToken($pdo, $data);

$category_id = intval($data['category_id'] ?? 0);
if ($category_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Category id is required']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM Categories WHERE category_id = ? AND record_type != 'income'");
$stmt->execute([$category_id]);

echo json_encode([
    'status' => 'success',
    'message' => 'Category deleted successfully',
]);
