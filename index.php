<?php
/**
 * App entry: /final_A6_term4/?page=home loads the logged-in dashboard.
 */
require_once __DIR__ . '/functions/config.php';

$page = $_GET['page'] ?? '';

if (!isset($_SESSION['user_id'])) {
    header('Location: pages/login.php');
    exit;
}

if ($page === 'home' || $page === '') {
    require __DIR__ . '/pages/homepage_after_login.php';
    exit;
}

header('Location: index.php?page=home');
exit;
