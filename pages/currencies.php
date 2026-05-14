<?php
// currencies.php - Manage currencies and wallet balances
require_once __DIR__ . '/../functions/config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();
$message = '';

// Handle currency operations
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

// Get user's currencies
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
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
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
        
        <a href="homepage_after_login.php" class="back-btn">Back</a>
    </div>
    
    <!-- Include Sidebar -->
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <script>
        setTimeout(() => {
            const msg = document.querySelector('.message');
            if (msg) msg.remove();
        }, 3000);
    </script>
    <script src="../js/responsive.js"></script>
</body>
</html>