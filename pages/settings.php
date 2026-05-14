<?php
// settings.php - Application settings
require_once __DIR__ . '/../functions/config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();
$message = '';

// Handle settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'export_data') {
        // Export transactions as CSV
        $stmt = $pdo->prepare("
            SELECT 'expense' as type, amount, date, note, currency_code 
            FROM Expenses WHERE user_id = ?
            UNION ALL
            SELECT 'income' as type, amount, date, note, currency_code 
            FROM Income WHERE user_id = ?
            UNION ALL
            SELECT 'transfer' as type, amount, date, note, from_currency 
            FROM Transfer WHERE user_id = ?
            ORDER BY date DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $transactions = $stmt->fetchAll();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="monefy_export_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Type', 'Amount', 'Date', 'Note', 'Currency']);
        foreach ($transactions as $tx) {
            fputcsv($output, [$tx['type'], $tx['amount'], $tx['date'], $tx['note'], $tx['currency_code']]);
        }
        fclose($output);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Monefy</title>
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .settings-card {
            background: white;
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .settings-card h2 {
            margin-bottom: 20px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-info h3 {
            font-size: 16px;
            margin-bottom: 4px;
        }
        .setting-info p {
            font-size: 13px;
            color: var(--text-mid);
        }
        .setting-action button {
            background: var(--green);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
        }
        .setting-action .danger-btn {
            background: var(--red);
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--gray-bg);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
        }
        .theme-selector {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }
        .theme-option {
            padding: 8px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            background: white;
        }
        .theme-option.active {
            border-color: var(--green);
            background: var(--green-light);
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <h1>Settings</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="settings-card">
            <h2> Data Management</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Export Data</h3>
                    <p>Download all your transactions as CSV file</p>
                </div>
                <div class="setting-action">
                    <form method="POST">
                        <input type="hidden" name="action" value="export_data">
                        <button type="submit">Export</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="settings-card">
            <h2> Appearance</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Theme</h3>
                    <p>Choose your preferred color scheme</p>
                </div>
                <div class="theme-selector">
                    <div class="theme-option active" onclick="changeTheme('light')">Light</div>
                    <div class="theme-option" onclick="changeTheme('dark')">Dark</div>
                </div>
            </div>
        </div>
        
        <div class="settings-card">
            <h2> System</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Clear All Data</h3>
                    <p>This will delete all your transactions</p>
                </div>
                <div class="setting-action">
                    <button class="danger-btn" onclick="confirmClearData()">Clear Data</button>
                </div>
            </div>
        </div>
        
        <div class="settings-card">
            <h2>About</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Monefy Finance Tracker</h3>
                    <p>Version 2.0 | Built with PHP & MySQL</p>
                    <p>© 2024 Monefy - Personal Finance Management</p>
                </div>
            </div>
        </div>
        
        <a href="homepage_after_login.php" class="back-btn">Back</a>
    </div>
    
    <!-- Include Sidebar -->
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <script>
        function changeTheme(theme) {
            alert('Theme preference saved! (Full theme implementation coming soon)');
        }
        
        function confirmClearData() {
            if (confirm('WARNING: This will delete ALL your transactions. This action cannot be undone!\n\nAre you absolutely sure?')) {
                if (confirm('Type "DELETE" to confirm:') === 'DELETE') {
                    fetch('clear_data.php', { method: 'POST' })
                        .then(() => alert('All data has been cleared'))
                        .catch(() => alert('Error clearing data'));
                }
            }
        }
        
        setTimeout(() => {
            const msg = document.querySelector('.message');
            if (msg) msg.remove();
        }, 3000);
    </script>
    <script src="../js/responsive.js"></script>
</body>
</html>