<?php
header("Content-Type: application/json");
require_once "../functions/connect_db.php";
$pdo = connect_db();
$data = json_decode(file_get_contents("php://input"), true);
$data['action'] = $_GET['action'] ?? '';
if(file_exists("features/{$data['action']}.php")) {
    include "features/{$data['action']}.php";
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}