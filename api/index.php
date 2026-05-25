<?php
// Enable error reporting temporarily to aid debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header("Content-Type: application/json");
require_once "../functions/config.php";
require_once "../functions/api_auth.php";
$pdo = getDBConnection();
$rawInput = file_get_contents("php://input");
$data = [];
if ($rawInput) {
    $json = json_decode($rawInput, true);
    if (is_array($json)) {
        $data = $json;
    }
}
// Merge query string values so browser GET requests work with ?action=...
foreach ($_GET as $key => $value) {
    if (!array_key_exists($key, $data)) {
        $data[$key] = $value;
    }
}
$action = $data['action'] ?? '';
$data['action'] = $action;
// Support token passing via `Authorization: Bearer <token>` header for clients like Postman
$allHeaders = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $allHeaders['Authorization'] ?? $allHeaders['authorization'] ?? '';
if (empty($data['token']) && $authHeader) {
    if (preg_match('/Bearer\s+(\S+)/', $authHeader, $m)) {
        $data['token'] = $m[1];
    }
}
if ($action && file_exists("features/{$action}.php")) {
    include "features/{$action}.php";
} elseif (!$action) {
    echo json_encode(["status" => "error", "message" => "No action specified. Use ?action=register or ?action=login or ?action=get_profile."]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}