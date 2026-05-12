<?php
// Database configuration file
// config.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'monefy_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'Monefy');
define('SITE_URL', 'http://localhost/monefy/');

// Currency settings
$available_currencies = [
    'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'rate_to_usd' => 1],
    'KHR' => ['name' => 'Cambodian Riel', 'symbol' => '៛', 'rate_to_usd' => 4000],
    'EUR' => ['name' => 'Euro', 'symbol' => '€', 'rate_to_usd' => 0.92],
    'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'rate_to_usd' => 0.79]
];

// Category colors
$category_colors = [
    'Groceries' => '#e07090',
    'Housing' => '#5b8dd9',
    'Car' => '#888888',
    'Dining' => '#d4a017',
    'Transit' => '#5aaa78',
    'Hygiene' => '#9b59b6',
    'Entertainment' => '#c0a0c8',
    'Sports' => '#5aaa78',
    'Taxi' => '#e05555',
    'Health' => '#6dbf8c',
    'Clothing' => '#9b59b6',
    'Phone' => '#b784a7',
    'Gifts' => '#c0a0c8',
    'Pets' => '#5aaa78',
    'Salary' => '#6dbf8c',
    'Bonus' => '#6dbf8c',
    'Investment' => '#6dbf8c'
];

// Exchange rates (base: USD)
$exchange_rates = [
    'USD' => 1,
    'KHR' => 4000,
    'EUR' => 0.92,
    'GBP' => 0.79
];

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (for protected pages)
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Database connection function
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}

// Helper function to format currency
function formatCurrency($amount, $currency = 'USD') {
    global $available_currencies;
    $symbol = $available_currencies[$currency]['symbol'] ?? '$';
    return $symbol . number_format($amount, 2);
}

// Helper function to get user ID - FIXED: Only returns session user_id, doesn't create new user
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}