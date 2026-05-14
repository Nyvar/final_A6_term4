
<!-- Category Code-->
 <!--  ----------------------------------------------------------------------->
 <?php
require_once 'functions/config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();
$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['category_name'] ?? '');
        $type = $_POST['record_type'] ?? 'expense';
        $color = $_POST['color'] ?? '#6dbf8c';
        
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO Categories (category_name, record_type, color) VALUES (?, ?, ?)");
            $stmt->execute([$name, $type, $color]);
            $message = 'Category added successfully!';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['category_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM Categories WHERE category_id = ? AND record_type != 'income'");
        $stmt->execute([$id]);
        $message = 'Category deleted successfully!';
    }
}

$stmt = $pdo->prepare("SELECT * FROM Categories ORDER BY record_type, display_order");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Monefy</title>
    <link rel="stylesheet" href="css/styleAfterLogin.css">
    <style>
        .categories-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .categories-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .add-category-btn {
            background: var(--green);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .category-section {
            background: white;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
        }
        .category-section h2 {
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        .category-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--gray-bg);
            border-radius: 12px;
            border-left: 4px solid;
        }
        .category-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .category-color {
            width: 24px;
            height: 24px;
            border-radius: 50%;
        }
        .delete-cat-btn {
            background: none;
            border: none;
            color: var(--red);
            font-size: 20px;
            cursor: pointer;
            padding: 0 8px;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: var(--gray-bg);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="categories-container">
        <div class="categories-header">
            <h1>Manage Categories</h1>
            <button class="add-category-btn" onclick="openAddModal()">+ Add Category</button>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php
        $expense_cats = array_filter($categories, fn($c) => $c['record_type'] === 'expense');
        $income_cats = array_filter($categories, fn($c) => $c['record_type'] === 'income');
        ?>
        
        <div class="category-section">
            <h2>Expense Categories</h2>
            <div class="category-grid">
                <?php foreach ($expense_cats as $cat): ?>
                    <div class="category-card" style="border-left-color: <?php echo $cat['color']; ?>">
                        <div class="category-info">
                            <div class="category-color" style="background: <?php echo $cat['color']; ?>"></div>
                            <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                        </div>
                        <?php if ($cat['category_name'] !== 'Groceries' && $cat['category_name'] !== 'Housing'): ?>
                            <form method="POST" onsubmit="return confirm('Delete this category?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                <button type="submit" class="delete-cat-btn">&times;</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="category-section">
            <h2>Income Categories</h2>
            <div class="category-grid">
                <?php foreach ($income_cats as $cat): ?>
                    <div class="category-card" style="border-left-color: <?php echo $cat['color']; ?>">
                        <div class="category-info">
                            <div class="category-color" style="background: <?php echo $cat['color']; ?>"></div>
                            <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <a href="index.php" class="back-btn">Back</a>
    </div>
    
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <h3>Add Category</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <input type="text" name="category_name" class="modal-input" placeholder="Category Name" required>
                <select name="record_type" class="modal-select">
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                </select>
                <input type="color" name="color" class="modal-input" value="#6dbf8c">
                <div class="modal-btns">
                    <button type="button" class="modal-btn modal-cancel" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="modal-btn modal-add">Add Category</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('open');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }
        setTimeout(() => {
            const msg = document.querySelector('.message');
            if (msg) msg.remove();
        }, 3000);
    </script>
</body>
</html>

 <!--  ----------------------------------------------------------------------->

<!-- Currencies--------------------------------------------------------------->


 <?php
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_balance') {
        $currency_code = $_POST['currency_code'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE Currency SET wallet = ? WHERE user_id = ? AND currency_code = ?");
        $stmt->execute([$amount, $user_id, $currency_code]);
        $message = 'Balance updated successfully!';
    }
}

$stmt = $pdo->prepare("SELECT * FROM Currency WHERE user_id = ? ORDER BY FIELD(currency_code, 'USD', 'KHR', 'EUR', 'GBP')");
$stmt->execute([$user_id]);
$currencies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currencies - Monefy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .currencies-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .currency-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s;
        }
        .currency-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .currency-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .currency-symbol {
            width: 60px;
            height: 60px;
            background: var(--green-light);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            color: var(--green);
        }
        .currency-details h3 {
            font-size: 20px;
            margin-bottom: 4px;
        }
        .currency-details p {
            color: var(--text-mid);
            font-size: 13px;
        }
        .currency-balance {
            text-align: right;
        }
        .balance-label {
            font-size: 12px;
            color: var(--text-mid);
        }
        .balance-amount {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
        }
        .edit-form {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .edit-form input {
            width: 150px;
            padding: 10px 12px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 14px;
        }
        .edit-form button {
            background: var(--green);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: var(--gray-bg);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
        }
        .total-balance {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .total-balance h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        .total-balance .amount {
            font-size: 36px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="currencies-container">
        <h1>Currencies & Wallets</h1>
        
        <?php 
        $total_usd = 0;
        foreach ($currencies as $c) {
            $rate = $exchange_rates[$c['currency_code']] ?? 1;
            $total_usd += $c['wallet'] / $rate;
        }
        ?>
        
        <div class="total-balance">
            <h3>Total Net Worth (USD)</h3>
            <div class="amount">$<?php echo number_format($total_usd, 2); ?></div>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php foreach ($currencies as $currency): ?>
            <div class="currency-card">
                <div class="currency-info">
                    <div class="currency-symbol"><?php echo $currency['symbol']; ?></div>
                    <div class="currency-details">
                        <h3><?php echo $currency['currency_name']; ?></h3>
                        <p><?php echo $currency['currency_code']; ?></p>
                    </div>
                </div>
                <div class="currency-balance">
                    <div class="balance-label">Current Balance</div>
                    <div class="balance-amount">
                        <?php echo formatCurrency($currency['wallet'], $currency['currency_code']); ?>
                    </div>
                </div>
            </div>
            
            <div class="currency-card">
                <form method="POST" class="edit-form">
                    <input type="hidden" name="action" value="update_balance">
                    <input type="hidden" name="currency_code" value="<?php echo $currency['currency_code']; ?>">
                    <input type="number" name="amount" step="0.01" value="<?php echo $currency['wallet']; ?>" required>
                    <button type="submit">Update Balance</button>
                </form>
            </div>
        <?php endforeach; ?>
        
        <a href="index.php" class="back-btn">Back</a>
    </div>
    
    <!-- Include Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <script>
        setTimeout(() => {
            const msg = document.querySelector('.message');
            if (msg) msg.remove();
        }, 3000);
    </script>
    <script src="script.js"></script>
</body>
</html>
<!-- ------------------------------------------------------------------- -->

<!-- Guide --------------------------------------------------------------->

<?php
// guides.php - Help and documentation
require_once 'config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guides & Help - Monefy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .guides-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .guide-section {
            background: white;
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .guide-section h2 {
            color: var(--green);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .guide-step {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            padding: 16px;
            background: var(--gray-bg);
            border-radius: 16px;
        }
        .step-number {
            width: 36px;
            height: 36px;
            background: var(--green);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        .step-content h3 {
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        .step-content p {
            color: var(--text-mid);
            line-height: 1.5;
        }
        .tip-box {
            background: var(--green-light);
            padding: 16px;
            border-radius: 16px;
            margin-top: 20px;
            border-left: 4px solid var(--green);
        }
        .faq-item {
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 16px;
        }
        .faq-question {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
        }
        .faq-answer {
            color: var(--text-mid);
            display: none;
            padding-top: 8px;
        }
        .faq-answer.show {
            display: block;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--gray-bg);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
        }
        .shortcut-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        .shortcut-item {
            background: var(--gray-bg);
            padding: 10px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
        }
        .shortcut-key {
            background: white;
            padding: 4px 10px;
            border-radius: 8px;
            font-family: monospace;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="guides-container">
        <h1>User Guide & Help</h1>
        
        <div class="guide-section">
            <h2> Getting Started</h2>
            <div class="guide-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Add Income</h3>
                    <p>Tap the green <strong>+ button</strong> at the bottom to add your income. Enter the amount using the calculator and add a note if needed.</p>
                </div>
            </div>
            <div class="guide-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Add Expenses</h3>
                    <p>Tap the red <strong>- button</strong> to record expenses. Select a category, enter the amount, and choose payment method.</p>
                </div>
            </div>
            <div class="guide-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Transfer Money</h3>
                    <p>Use the transfer icon (↻) in the top bar to move money between different currencies with exchange rates.</p>
                </div>
            </div>
            <div class="guide-step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>View Reports</h3>
                    <p>Use the date filters (Day, Week, Month, Year) to see your financial summary for any period.</p>
                </div>
            </div>
        </div>
        
        <div class="guide-section">
            <h2> Pro Tips</h2>
            <div class="tip-box">
                <strong>- Tip 1:</strong> Use the calculator before adding an expense - the amount will auto-fill!
            </div>
            <div class="tip-box">
                <strong>- Tip 2:</strong> Add notes to transactions to remember what each purchase was for.
            </div>
            <div class="tip-box">
                <strong>- Tip 3:</strong> Check your spending by category to identify where you can save money.
            </div>
            <div class="tip-box">
                <strong>- Tip 4:</strong> Use the Interval filter to view custom date ranges.
            </div>
        </div>
        
        <div class="guide-section">
            <h2> Keyboard Shortcuts</h2>
            <div class="shortcut-grid">
                <div class="shortcut-item"><span>Numbers 0-9</span><span class="shortcut-key">Enter digits</span></div>
                <div class="shortcut-item"><span>+ - * /</span><span class="shortcut-key">Operators</span></div>
                <div class="shortcut-item"><span>Enter / =</span><span class="shortcut-key">Calculate & Add</span></div>
                <div class="shortcut-item"><span>C</span><span class="shortcut-key">Clear calculator</span></div>
                <div class="shortcut-item"><span>Escape</span><span class="shortcut-key">Close modals</span></div>
            </div>
        </div>
        
        <div class="guide-section">
            <h2> Frequently Asked Questions</h2>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>How do I change the currency?</span>
                    <span>▼</span>
                </div>
                <div class="faq-answer">Click on "Monefy" in the top bar or go to Menu → Currencies to select your preferred display currency.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Can I export my data?</span>
                    <span>▼</span>
                </div>
                <div class="faq-answer">Yes! Go to Settings → Export Data to download all your transactions as a CSV file.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>How do exchange rates work for transfers?</span>
                    <span>▼</span>
                </div>
                <div class="faq-answer">Rates are pre-configured but you can manually adjust them during transfer. USD to KHR default is 4000.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Can I delete a transaction?</span>
                    <span>▼</span>
                </div>
                <div class="faq-answer">Yes, open Transaction History from the menu and click the × button next to any transaction.</div>
            </div>
        </div>
        
        <a href="index.php" class="back-btn">Back</a>
    </div>
    
    <!-- Include Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            answer.classList.toggle('show');
            const arrow = element.querySelector('span:last-child');
            arrow.textContent = answer.classList.contains('show') ? '▲' : '▼';
        }
    </script>
    <script src="script.js"></script>
</body>
</html>
<!-- --------------------------------------------------------------------- -->

<!-- Setting ---------------------------------------------------------------->

 <?php
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();
$message = '';

// Handle settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'export_data') {
        
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
    <link rel="stylesheet" href="style.css">
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
        
        <a href="index.php" class="back-btn">Back</a>
    </div>
    
    <!-- Include Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
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
    <script src="script.js"></script>
</body>
</html>

<!-- ----------------------------------------------------------------------- -->

<!-- Menu --------------------------------------------------------------------->

<?php
// menu.php - Simple menu page (no sidebar)
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();
$stmt = $pdo->prepare("SELECT username FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = $user['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Monefy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: linear-gradient(135deg, var(--green-light) 0%, #d4f5e0 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        .menu-wrapper { max-width: 1100px; margin: 0 auto; }
        .page-header { text-align: center; margin-bottom: 40px; }
        .page-header h1 { font-size: 32px; color: var(--text-dark); display: flex; align-items: center; justify-content: center; gap: 12px; }
        .greeting { background: rgba(255,255,255,0.6); display: inline-block; padding: 6px 20px; border-radius: 40px; font-size: 15px; color: var(--text-mid); backdrop-filter: blur(4px); }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; margin-bottom: 35px; }
        .menu-card { background: white; border-radius: 28px; padding: 28px 20px; text-decoration: none; transition: all 0.25s; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); text-align: center; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .card-icon { width: 80px; height: 80px; background: var(--green-light); border-radius: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; transition: background 0.2s; }
        .menu-card:hover .card-icon { background: var(--green); }
        .card-icon svg { width: 44px; height: 44px; stroke: var(--green); }
        .menu-card:hover .card-icon svg { stroke: white; }
        .card-title { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
        .card-desc { font-size: 13px; color: var(--text-mid); }
        .bottom-actions { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .btn { background: white; padding: 12px 28px; border-radius: 50px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: var(--shadow-sm); color: var(--text-dark); }
        .btn-primary { background: var(--green); color: white; }
        .btn-primary svg { stroke: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        @media (max-width: 640px) { .card-icon { width: 64px; height: 64px; } .card-title { font-size: 18px; } }
    </style>
</head>
<body>
<div class="menu-wrapper">
    <div class="page-header">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
                <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
            </svg>
            Menu
        </h1>
        <div class="greeting">Hello, <?php echo htmlspecialchars($username); ?> 👋</div>
    </div>
    <div class="menu-grid">
        <a href="account.php" class="menu-card">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="7" width="20" height="14" rx="2"/><line x1="16" y1="21" x2="16" y2="17"/><line x1="8" y1="21" x2="8" y2="17"/><circle cx="12" cy="11" r="2"/><path d="M12 13c-2.5 0-4 1.5-4 3h8c0-1.5-1.5-3-4-3z"/></svg></div>
            <div class="card-title">Account</div><div class="card-desc">Profile & password</div>
        </a>
        <a href="categories.php" class="menu-card">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></div>
            <div class="card-title">Categories</div><div class="card-desc">Manage income/expense types</div>
        </a>
        <a href="currencies.php" class="menu-card">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M4 4l16 16"/><line x1="8" y1="4" x2="8" y2="8"/><line x1="16" y1="20" x2="16" y2="16"/></svg></div>
            <div class="card-title">Currencies</div><div class="card-desc">Wallet balances & exchange rates</div>
        </a>
        <a href="settings.php" class="menu-card">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></div>
            <div class="card-title">Settings</div><div class="card-desc">Export, theme, clear data</div>
        </a>
        <a href="guides.php" class="menu-card">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></div>
            <div class="card-title">Guides</div><div class="card-desc">Help, tips & FAQ</div>
        </a>
    </div>
    <div class="bottom-actions">
        <a href="index.php" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-7H9v7H5a2 2 0 0 1-2-2z"/></svg>
            Dashboard
        </a>
        <a href="logout.php" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</div>
</body>
</html>

