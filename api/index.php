<?php
header("Content-Type: application/json");
require_once "../functions/config.php";
require_once "../functions/api_auth.php";
$pdo = getDBConnection();
$data = json_decode(file_get_contents("php://input"), true) ?? [];
$data['action'] = $_GET['action'] ?? '';
if(file_exists("features/{$data['action']}.php")) {
    include "features/{$data['action']}.php";
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}