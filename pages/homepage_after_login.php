<?php
// Main application file - index.php
require_once '../functions/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = getUserId();

// Get search query
$searchQuery = $_GET['search'] ?? '';
$searchCondition = '';
if (!empty($searchQuery)) {
    $searchCondition = "AND (e.note LIKE '%$searchQuery%' OR c.category_name LIKE '%$searchQuery%' OR CAST(e.amount AS CHAR) LIKE '%$searchQuery%')";
}

// Get active currency from session or URL
$activeCurrency = $_GET['currency'] ?? $_SESSION['active_currency'] ?? 'USD';
$_SESSION['active_currency'] = $activeCurrency;

// Get date filter from URL
$dateFilter = $_GET['date_filter'] ?? $_SESSION['date_filter'] ?? 'month';
$startDateParam = $_GET['start_date'] ?? null;
$endDateParam = $_GET['end_date'] ?? null;
$_SESSION['date_filter'] = $dateFilter;

// Calculate date range based on filter
$dateRangeText = '';
$dateCondition = '';

if ($dateFilter === 'interval' && $startDateParam && $endDateParam) {
    $dateCondition = "AND DATE(date) BETWEEN '$startDateParam' AND '$endDateParam'";
    $dateRangeText = date('M d, Y', strtotime($startDateParam)) . ' - ' . date('M d, Y', strtotime($endDateParam));
} else {
    switch ($dateFilter) {
        case 'day':
            $dateCondition = "AND DATE(date) = CURDATE()";
            $dateRangeText = date('F d, Y');
            break;
        case 'week':
            $dateCondition = "AND YEARWEEK(date) = YEARWEEK(CURDATE())";
            $dateRangeText = 'This Week';
            break;
        case 'month':
            $dateCondition = "AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
            $dateRangeText = date('F Y');
            break;
        case 'year':
            $dateCondition = "AND YEAR(date) = YEAR(CURDATE())";
            $dateRangeText = date('Y');
            break;
        case 'all':
        default:
            $dateCondition = "";
            $dateRangeText = 'All Time';
            break;
    }
}

// Get user's currencies and balances
$currencies = [];
$stmt = $pdo->prepare("SELECT * FROM Currency WHERE user_id = ?");
$stmt->execute([$user_id]);
$currencies = $stmt->fetchAll();

$walletBalance = 0;
$currencySymbol = '$';
foreach ($currencies as $c) {
    if ($c['currency_code'] === $activeCurrency) {
        $walletBalance = $c['wallet'];
        $currencySymbol = $c['symbol'];
        break;
    }
}

// Get statistics with date filter
$stmt = $pdo->prepare("
    SELECT 
        COALESCE((SELECT SUM(amount) FROM Expenses WHERE user_id = ? AND currency_code = ? $dateCondition $searchCondition), 0) as total_expense,
        COALESCE((SELECT SUM(amount) FROM Income WHERE user_id = ? AND currency_code = ? $dateCondition), 0) as total_income
");
$stmt->execute([$user_id, $activeCurrency, $user_id, $activeCurrency]);
$stats = $stmt->fetch();
$totalExpense = $stats['total_expense'];
$totalIncome = $stats['total_income'];
$balance = $walletBalance;

// Get expense by category with date filter
$stmt = $pdo->prepare("
    SELECT c.category_name, c.color, SUM(e.amount) as total
    FROM Expenses e
    LEFT JOIN Categories c ON e.category_id = c.category_id
    WHERE e.user_id = ? AND e.currency_code = ? $dateCondition $searchCondition
    GROUP BY e.category_id
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute([$user_id, $activeCurrency]);
$expenseByCategory = $stmt->fetchAll();

// Get recent transactions with date filter and search
$stmt = $pdo->prepare("
    (SELECT 'expense' as type, e.expense_id as id, e.amount, e.date, e.note, 
            c.category_name, pm.method_name as payment_method, e.currency_code
     FROM Expenses e
     LEFT JOIN Categories c ON e.category_id = c.category_id
     LEFT JOIN Payment_methods pm ON e.payment_method_id = pm.method_id
     WHERE e.user_id = ? AND e.currency_code = ? $dateCondition $searchCondition
     ORDER BY e.date DESC LIMIT 50)
    UNION ALL
    (SELECT 'income' as type, i.income_id as id, i.amount, i.date, i.note, 
            'Income' as category_name, NULL as payment_method, i.currency_code
     FROM Income i
     WHERE i.user_id = ? AND i.currency_code = ? $dateCondition
     ORDER BY i.date DESC LIMIT 50)
    UNION ALL
    (SELECT 'transfer' as type, t.transfer_id as id, t.amount, t.date, t.note,
            CONCAT('Transfer: ', t.from_currency, ' → ', t.to_currency) as category_name,
            NULL as payment_method, t.from_currency as currency_code
     FROM Transfer t
     WHERE t.user_id = ? $dateCondition
     ORDER BY t.date DESC LIMIT 50)
    ORDER BY date DESC LIMIT 100
");
$stmt->execute([$user_id, $activeCurrency, $user_id, $activeCurrency, $user_id]);
$transactions = $stmt->fetchAll();

// Get categories for display
$stmt = $pdo->prepare("SELECT * FROM Categories WHERE record_type = 'expense' ORDER BY display_order");
$stmt->execute();
$expenseCategories = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM Categories WHERE record_type = 'income' ORDER BY display_order");
$stmt->execute();
$incomeCategories = $stmt->fetchAll();

// Get payment methods
$stmt = $pdo->prepare("SELECT * FROM Payment_methods WHERE user_id IS NULL OR user_id = ?");
$stmt->execute([$user_id]);
$paymentMethods = $stmt->fetchAll();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_expense') {
        $amount = floatval($_POST['amount'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $payment_method_id = intval($_POST['payment_method_id'] ?? 1);
        $note = trim($_POST['note'] ?? '');
        $date = date('Y-m-d H:i:s');
        
        if ($amount > 0 && $category_id > 0) {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO Expenses (user_id, amount, date, currency_code, category_id, payment_method_id, note) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $amount, $date, $activeCurrency, $category_id, $payment_method_id, $note]);
                
                $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?");
                $stmt->execute([$amount, $user_id, $activeCurrency]);
                
                $pdo->commit();
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Expense added successfully!'];
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
            }
        }
        header('Location: ?currency=' . $activeCurrency . '&date_filter=' . $dateFilter . ($startDateParam ? '&start_date=' . $startDateParam . '&end_date=' . $endDateParam : '') . (!empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''));
        exit;
    }
    
    if ($action === 'add_income') {
        $amount = floatval($_POST['amount'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        $date = date('Y-m-d H:i:s');
        
        if ($amount > 0) {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO Income (user_id, amount, date, currency_code, note) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $amount, $date, $activeCurrency, $note]);
                
                $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?");
                $stmt->execute([$amount, $user_id, $activeCurrency]);
                
                $pdo->commit();
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Income added successfully!'];
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
            }
        }
        header('Location: ?currency=' . $activeCurrency . '&date_filter=' . $dateFilter . ($startDateParam ? '&start_date=' . $startDateParam . '&end_date=' . $endDateParam : '') . (!empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''));
        exit;
    }
    
    if ($action === 'transfer') {
        $amount = floatval($_POST['amount'] ?? 0);
        $from_currency = $_POST['from_currency'] ?? 'USD';
        $to_currency = $_POST['to_currency'] ?? 'KHR';
        $exchange_rate = floatval($_POST['exchange_rate'] ?? 1);
        $note = trim($_POST['note'] ?? '');
        $date = date('Y-m-d H:i:s');
        
        if ($amount > 0) {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("SELECT wallet FROM Currency WHERE user_id = ? AND currency_code = ? FOR UPDATE");
                $stmt->execute([$user_id, $from_currency]);
                $fromBalance = $stmt->fetchColumn();
                
                if ($fromBalance >= $amount) {
                    $convertedAmount = $amount * $exchange_rate;
                    
                    $stmt = $pdo->prepare("INSERT INTO Transfer (user_id, amount, from_currency, to_currency, exchange_rate, date, note) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $amount, $from_currency, $to_currency, $exchange_rate, $date, $note]);
                    
                    $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?");
                    $stmt->execute([$amount, $user_id, $from_currency]);
                    
                    $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?");
                    $stmt->execute([$convertedAmount, $user_id, $to_currency]);
                    
                    $pdo->commit();
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Transfer completed!'];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Insufficient balance'];
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
            }
        }
        header('Location: ?currency=' . $activeCurrency . '&date_filter=' . $dateFilter . ($startDateParam ? '&start_date=' . $startDateParam . '&end_date=' . $endDateParam : '') . (!empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''));
        exit;
    }
    
    if ($action === 'delete') {
        $type = $_POST['record_type'] ?? '';
        $id = intval($_POST['record_id'] ?? 0);
        
        try {
            $pdo->beginTransaction();
            
            if ($type === 'expense') {
                $stmt = $pdo->prepare("SELECT amount, currency_code FROM Expenses WHERE expense_id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $expense = $stmt->fetch();
                
                if ($expense) {
                    $stmt = $pdo->prepare("DELETE FROM Expenses WHERE expense_id = ? AND user_id = ?");
                    $stmt->execute([$id, $user_id]);
                    $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?");
                    $stmt->execute([$expense['amount'], $user_id, $expense['currency_code']]);
                }
            } elseif ($type === 'income') {
                $stmt = $pdo->prepare("SELECT amount, currency_code FROM Income WHERE income_id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $income = $stmt->fetch();
                
                if ($income) {
                    $stmt = $pdo->prepare("DELETE FROM Income WHERE income_id = ? AND user_id = ?");
                    $stmt->execute([$id, $user_id]);
                    $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?");
                    $stmt->execute([$income['amount'], $user_id, $income['currency_code']]);
                }
            } elseif ($type === 'transfer') {
                $stmt = $pdo->prepare("SELECT amount, from_currency, to_currency, exchange_rate FROM Transfer WHERE transfer_id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $transfer = $stmt->fetch();
                
                if ($transfer) {
                    $stmt = $pdo->prepare("DELETE FROM Transfer WHERE transfer_id = ? AND user_id = ?");
                    $stmt->execute([$id, $user_id]);
                    
                    $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?");
                    $stmt->execute([$transfer['amount'], $user_id, $transfer['from_currency']]);
                    
                    $convertedAmount = $transfer['amount'] * $transfer['exchange_rate'];
                    $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?");
                    $stmt->execute([$convertedAmount, $user_id, $transfer['to_currency']]);
                }
            }
            
            $pdo->commit();
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Deleted successfully!'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
        }
        
        header('Location: ?currency=' . $activeCurrency . '&date_filter=' . $dateFilter . ($startDateParam ? '&start_date=' . $startDateParam . '&end_date=' . $endDateParam : '') . (!empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''));
        exit;
    }
    
    if ($action === 'change_currency') {
        $newCurrency = $_POST['currency_code'] ?? 'USD';
        $_SESSION['active_currency'] = $newCurrency;
        header('Location: ?currency=' . $newCurrency . '&date_filter=' . $dateFilter . ($startDateParam ? '&start_date=' . $startDateParam . '&end_date=' . $endDateParam : '') . (!empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''));
        exit;
    }
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
$monthName = date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Monefy - Personal Finance Tracker</title>
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
    <style>
        /* Search Modal Styles */
        .search-modal {
            max-width: 600px;
            width: 90%;
        }
        .search-input-wrapper {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        .search-input-wrapper input {
            flex: 1;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            font-size: 16px;
        }
        .search-input-wrapper input:focus {
            outline: none;
            border-color: var(--green);
        }
        .search-input-wrapper button {
            padding: 14px 24px;
            background: var(--green);
            color: white;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 600;
        }
        .search-results {
            max-height: 400px;
            overflow-y: auto;
        }
        .search-result-item {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-result-item:hover {
            background: var(--gray-bg);
        }
        .search-result-amount {
            font-weight: 700;
            font-size: 16px;
        }
        .search-result-amount.expense {
            color: var(--red);
        }
        .search-result-amount.income {
            color: var(--green);
        }
        .search-result-category {
            font-size: 12px;
            color: var(--text-mid);
            margin-top: 4px;
        }
        .search-result-date {
            font-size: 11px;
            color: var(--text-light);
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: var(--text-mid);
        }
        .clear-search {
            background: var(--gray-bg);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .clear-search:hover {
            background: var(--red-light);
        }
        .search-active-badge {
            background: var(--green);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Top Navigation Bar -->
        <div class="topbar">
            <div class="topbar-left" onclick="toggleDropdown()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
                <div>
                    <div class="topbar-title">Monefy</div>
                    <div class="topbar-sub">All accounts · <?php echo htmlspecialchars($activeCurrency); ?></div>
                </div>
                <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
            <div class="topbar-icons">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" onclick="openSearchModal()">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" onclick="openTransferModal()">
                    <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                </svg>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" onclick="openSidebar()">
                    <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                </svg>
            </div>
        </div>

        <!-- Dropdown Menu -->
        <div class="dropdown-menu" id="dropdownMenu">
            <div class="dropdown-item" onclick="changeCurrency('USD')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4" y1="4" x2="20" y2="20"/>
                </svg>
                <span>USD ($)</span>
                <?php if ($activeCurrency === 'USD'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php endif; ?>
            </div>
            <div class="dropdown-item" onclick="changeCurrency('KHR')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4" y1="4" x2="20" y2="20"/>
                </svg>
                <span>KHR (៛)</span>
                <?php if ($activeCurrency === 'KHR'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php endif; ?>
            </div>
            <div class="dropdown-item" onclick="changeCurrency('EUR')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4" y1="4" x2="20" y2="20"/>
                </svg>
                <span>EUR (€)</span>
                <?php if ($activeCurrency === 'EUR'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php endif; ?>
            </div>
            <div class="dropdown-item" onclick="changeCurrency('GBP')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4" y1="4" x2="20" y2="20"/>
                </svg>
                <span>GBP (£)</span>
                <?php if ($activeCurrency === 'GBP'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php endif; ?>
            </div>
            <div class="dropdown-divider"></div>
            <div class="dropdown-item" onclick="openTransferModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="17 1 21 5 17 9"/>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                </svg>
                <span>Transfer Money</span>
            </div>
            <div class="dropdown-item" onclick="openHistory()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <span>Transaction History</span>
            </div>
            <div class="dropdown-item" onclick="showTransit()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18M8 6v12M16 6v12"/>
                    <rect x="6" y="4" width="12" height="16" rx="1"/>
                </svg>
                <span>Transit</span>
            </div>
        </div>

        <!-- Right Sidebar Navigation -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
        <div class="right-sidebar" id="rightSidebar">
            <div class="sidebar-header">
                <h3>Menu</h3>
                <button class="close-sidebar" onclick="closeSidebar()">&times;</button>
            </div>
            <div class="sidebar-items">
                <div class="sidebar-item" onclick="location.href='categories.php'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    <span>Categories</span>
                </div>
                <div class="sidebar-item" onclick="location.href='account.php'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2"/>
                        <line x1="16" y1="21" x2="16" y2="17"/>
                        <line x1="8" y1="21" x2="8" y2="17"/>
                    </svg>
                    <span>Account</span>
                </div>
                <div class="sidebar-item" onclick="location.href='currencies.php'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M4 4l16 16"/>
                    </svg>
                    <span>Currencies</span>
                </div>
                <div class="sidebar-item" onclick="location.href='settings.php'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>Settings</span>
                </div>
                <div class="sidebar-item" onclick="location.href='guides.php'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>Guides & Help</span>
                </div>
                <div class="sidebar-item" onclick="location.href='logout.php'" style="border-top: 1px solid var(--border-color); margin-top: 10px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Logout</span>
                </div>
            </div>
        </div>

        <!-- Date Filter Bar -->
        <div class="date-filter-bar">
            <div class="filter-buttons">
                <button class="filter-btn <?php echo ($dateFilter == 'day' ? 'active' : ''); ?>" onclick="setDateFilter('day')">Day</button>
                <button class="filter-btn <?php echo ($dateFilter == 'week' ? 'active' : ''); ?>" onclick="setDateFilter('week')">Week</button>
                <button class="filter-btn <?php echo ($dateFilter == 'month' ? 'active' : ''); ?>" onclick="setDateFilter('month')">Month</button>
                <button class="filter-btn <?php echo ($dateFilter == 'year' ? 'active' : ''); ?>" onclick="setDateFilter('year')">Year</button>
                <button class="filter-btn <?php echo ($dateFilter == 'all' ? 'active' : ''); ?>" onclick="setDateFilter('all')">All</button>
                <button class="filter-btn <?php echo ($dateFilter == 'interval' ? 'active' : ''); ?>" onclick="openDateRangeModal()">Interval</button>
            </div>
            <div class="date-picker-wrapper" onclick="openDateRangeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span id="selectedDateRange"><?php echo htmlspecialchars($dateRangeText); ?></span>
            </div>
            <?php if (!empty($searchQuery)): ?>
            <div class="search-active-badge" onclick="clearSearch()">
                🔍 "<?php echo htmlspecialchars($searchQuery); ?>" 
                <span style="cursor:pointer;">&times;</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Dashboard Layout - Two Columns for Web -->
        <div class="dashboard-layout">
            <div class="left-panel">
                <!-- Donut Chart -->
                <div class="donut-container">
                    <div class="donut-wrapper">
                        <svg viewBox="0 0 240 240">
                            <circle cx="120" cy="120" r="100" fill="none" stroke="#e0e0e0" stroke-width="38"/>
                            <circle cx="120" cy="120" r="100" fill="none" stroke="#6dbf8c" stroke-width="38"
                                stroke-dasharray="<?php 
                                    $total = $totalIncome + $totalExpense;
                                    $circ = 2 * M_PI * 100;
                                    $incLen = $total > 0 ? ($totalIncome / $total) * $circ : 0;
                                    echo $incLen . ' ' . ($circ - $incLen);
                                ?>" stroke-linecap="round" 
                                stroke-dashoffset="-<?php echo $circ * 0.25; ?>"
                                transform="rotate(-90 120 120)"/>
                            <circle cx="120" cy="120" r="100" fill="none" stroke="#e07070" stroke-width="38"
                                stroke-dasharray="<?php 
                                    $expLen = $total > 0 ? ($totalExpense / $total) * $circ : 0;
                                    echo $expLen . ' ' . ($circ - $expLen);
                                ?>" stroke-linecap="round"
                                stroke-dashoffset="-<?php echo $circ * 0.25 + $incLen; ?>"
                                transform="rotate(-90 120 120)"/>
                        </svg>
                        <div class="donut-center">
                            <div class="donut-income"><?php echo formatCurrency($totalIncome, $activeCurrency); ?></div>
                            <div class="donut-expense"><?php echo formatCurrency($totalExpense, $activeCurrency); ?></div>
                            
                        </div>
                    </div>
                </div>

                <!-- Expense by Category -->
                <?php if (!empty($expenseByCategory)): ?>
                <div class="category-breakdown">
                    <div class="breakdown-title">Top Expenses</div>
                    <?php foreach ($expenseByCategory as $cat): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-label"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                        <div class="breakdown-bar-container">
                            <div class="breakdown-bar" style="width: <?php echo ($cat['total'] / max($totalExpense, 1)) * 100; ?>%; background-color: <?php echo $cat['color']; ?>;"></div>
                        </div>
                        <span class="breakdown-amount"><?php echo formatCurrency($cat['total'], $activeCurrency); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="right-panel">
                <!-- Calculator Display -->
                <div class="calc-display">
                    <div class="calc-amount">
                        <span id="calcValue">0</span>
                        <small><?php echo htmlspecialchars($activeCurrency); ?></small>
                    </div>
                </div>

          
                <div class="categories">
                    <?php 
                    $displayCategories = array_slice($expenseCategories, 0, 12);
                    foreach ($displayCategories as $cat): 
                    ?>
                    <div class="cat-icon" onclick="openExpenseModal(<?php echo $cat['category_id']; ?>, '<?php echo htmlspecialchars($cat['category_name']); ?>')">
                        <div class="cat-icon-circle" style="background-color: <?php echo $cat['color']; ?>20; border-color: <?php echo $cat['color']; ?>;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="<?php echo $cat['color']; ?>" stroke-width="2">
                                <?php 
                                $icons = [
                                    'Groceries' => '<path d="M3 6h18v12H3z"/><circle cx="8" cy="12" r="2"/><circle cx="16" cy="12" r="2"/>',
                                    'Housing' => '<rect x="4" y="8" width="16" height="12"/><polygon points="2 8 12 2 22 8"/>',
                                    'Car' => '<rect x="4" y="12" width="16" height="8"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/>',
                                    'Dining' => '<path d="M8 3v3a4 4 0 0 0 8 0V3"/><path d="M12 12v8"/><path d="M5 21h14"/>',
                                    'Transit' => '<path d="M3 12h18M3 6h18M3 18h18M8 6v12M16 6v12"/><rect x="6" y="4" width="12" height="16" rx="1"/>',
                                    'Hygiene' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
                                    'Entertainment' => '<circle cx="12" cy="12" r="10"/><path d="M9 12h6"/><path d="M12 9v6"/>',
                                    'Sports' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/>',
                                    'Taxi' => '<rect x="4" y="12" width="16" height="8"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/>',
                                    'Health' => '<path d="M4 8h16"/><path d="M12 2v20"/><rect x="2" y="8" width="20" height="12" rx="2"/>',
                                    'Clothing' => '<rect x="5" y="7" width="14" height="14" rx="2"/><path d="M9 7V4h6v3"/>',
                                    'Phone' => '<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
                                    'Gifts' => '<polygon points="12 2 15 7 21 8 16 13 17 19 12 16 7 19 8 13 3 8 9 7 12 2"/>',
                                    'Pets' => '<circle cx="12" cy="12" r="10"/><circle cx="9" cy="10" r="1"/><circle cx="15" cy="10" r="1"/>'
                                ];
                                $iconPath = $icons[$cat['category_name']] ?? '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
                                echo $iconPath;
                                ?>
                            </svg>
                        </div>
                        <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Calculator Keypad -->
                <div class="calc-keypad">
                    <button class="key" onclick="calculatorInput('7')">7</button>
                    <button class="key" onclick="calculatorInput('8')">8</button>
                    <button class="key" onclick="calculatorInput('9')">9</button>
                    <button class="key key-operator" onclick="calculatorInput('+')">+</button>
                    <button class="key" onclick="calculatorInput('4')">4</button>
                    <button class="key" onclick="calculatorInput('5')">5</button>
                    <button class="key" onclick="calculatorInput('6')">6</button>
                    <button class="key key-operator" onclick="calculatorInput('-')">-</button>
                    <button class="key" onclick="calculatorInput('1')">1</button>
                    <button class="key" onclick="calculatorInput('2')">2</button>
                    <button class="key" onclick="calculatorInput('3')">3</button>
                    <button class="key key-operator" onclick="calculatorInput('*')">×</button>
                    <button class="key" onclick="calculatorInput('0')">0</button>
                    <button class="key" onclick="calculatorInput('.')">.</button>
                    <button class="key key-clear" onclick="calculatorClear()">C</button>
                    <button class="key key-operator" onclick="calculatorInput('/')">÷</button>
                </div>
            </div>
        </div>

        <!-- Bottom Balance Bar -->
        <div class="bottom-bar" onclick="openExpenseModal()">
            <div class="balance-area">
                <span class="balance-label">Balance</span>
                <span class="balance-amount"><?php echo formatCurrency($balance, $activeCurrency); ?></span>
            </div>
            <div class="menu-icon" onclick="event.stopPropagation(); openSidebar()">
                <span></span><span></span><span></span>
            </div>
        </div>

        <!-- Bottom Action Buttons -->
        <div class="bottom-btns">
            <div class="btn-circle btn-expense" onclick="openExpenseModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </div>
            <div class="btn-circle btn-income" onclick="openIncomeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Search Modal -->
    <div class="modal-overlay" id="searchModal" onclick="closeModal('searchModal')">
        <div class="modal search-modal" onclick="event.stopPropagation()">
            <h3>🔍 Search Transactions</h3>
            <div class="search-input-wrapper">
                <input type="text" id="searchInput" placeholder="Search by amount, category, or note..." autofocus>
                <button onclick="performSearch()">Search</button>
            </div>
            <div id="searchResults" class="search-results">
                <div class="no-results">Enter a search term above</div>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="expenseModal" onclick="closeModal('expenseModal')">
        <div class="modal" onclick="event.stopPropagation()">
            <h3>Add Expense</h3>
            <div class="category-grid" id="expenseCategoryGrid">
                <?php foreach ($expenseCategories as $cat): ?>
                <div class="category-option" data-cat-id="<?php echo $cat['category_id']; ?>" data-cat-name="<?php echo htmlspecialchars($cat['category_name']); ?>" onclick="selectCategory(this)">
                    <div class="category-icon" style="background-color: <?php echo $cat['color']; ?>20;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="<?php echo $cat['color']; ?>" stroke-width="2">
                            <?php 
                            $icons = [
                                'Groceries' => '<path d="M3 6h18v12H3z"/><circle cx="8" cy="12" r="2"/><circle cx="16" cy="12" r="2"/>',
                                'Housing' => '<rect x="4" y="8" width="16" height="12"/><polygon points="2 8 12 2 22 8"/>',
                                'Car' => '<rect x="4" y="12" width="16" height="8"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/>',
                                'Dining' => '<path d="M8 3v3a4 4 0 0 0 8 0V3"/><path d="M12 12v8"/><path d="M5 21h14"/>',
                                'Transit' => '<path d="M3 12h18M3 6h18M3 18h18M8 6v12M16 6v12"/><rect x="6" y="4" width="12" height="16" rx="1"/>',
                                'Hygiene' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
                                'Entertainment' => '<circle cx="12" cy="12" r="10"/><path d="M9 12h6"/><path d="M12 9v6"/>',
                                'Sports' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/>',
                                'Taxi' => '<rect x="4" y="12" width="16" height="8"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/>',
                                'Health' => '<path d="M4 8h16"/><path d="M12 2v20"/><rect x="2" y="8" width="20" height="12" rx="2"/>',
                                'Clothing' => '<rect x="5" y="7" width="14" height="14" rx="2"/><path d="M9 7V4h6v3"/>',
                                'Phone' => '<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
                                'Gifts' => '<polygon points="12 2 15 7 21 8 16 13 17 19 12 16 7 19 8 13 3 8 9 7 12 2"/>',
                                'Pets' => '<circle cx="12" cy="12" r="10"/><circle cx="9" cy="10" r="1"/><circle cx="15" cy="10" r="1"/>'
                            ];
                            $iconPath = $icons[$cat['category_name']] ?? '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
                            echo $iconPath;
                            ?>
                        </svg>
                    </div>
                    <div class="label"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="expenseCategoryId" value="<?php echo $expenseCategories[0]['category_id'] ?? ''; ?>">
            <input type="number" id="expenseAmount" class="modal-input" placeholder="Amount" step="0.01" value="0">
            <select id="paymentMethodId" class="modal-select">
                <?php foreach ($paymentMethods as $pm): ?>
                <option value="<?php echo $pm['method_id']; ?>"><?php echo htmlspecialchars($pm['method_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="expenseNote" class="modal-input" placeholder="Note (optional)">
            <div class="modal-btns">
                <button class="modal-btn modal-cancel" onclick="closeModal('expenseModal')">Cancel</button>
                <button class="modal-btn modal-add expense" onclick="addExpense()">Add Expense</button>
            </div>
        </div>
    </div>

    <!-- Income Modal -->
    <div class="modal-overlay" id="incomeModal" onclick="closeModal('incomeModal')">
        <div class="modal" onclick="event.stopPropagation()">
            <h3>Add Income</h3>
            <input type="number" id="incomeAmount" class="modal-input" placeholder="Amount" step="0.01" value="0">
            <input type="text" id="incomeNote" class="modal-input" placeholder="Note (optional)">
            <div class="modal-btns">
                <button class="modal-btn modal-cancel" onclick="closeModal('incomeModal')">Cancel</button>
                <button class="modal-btn modal-add" onclick="addIncome()">Add Income</button>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div class="modal-overlay" id="transferModal" onclick="closeModal('transferModal')">
        <div class="modal" onclick="event.stopPropagation()">
            <h3>Transfer Money</h3>
            <div class="account-selector">
                <?php foreach ($available_currencies as $code => $info): ?>
                <div class="account-option" data-currency="<?php echo $code; ?>" data-account-type="from" onclick="selectTransferAccount('from', '<?php echo $code; ?>')">
                    <div class="account-icon"><?php echo $info['symbol']; ?></div>
                    <div class="label">From: <?php echo $code; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="transfer-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </div>
            <div class="account-selector">
                <?php foreach ($available_currencies as $code => $info): ?>
                <div class="account-option" data-currency="<?php echo $code; ?>" data-account-type="to" onclick="selectTransferAccount('to', '<?php echo $code; ?>')">
                    <div class="account-icon"><?php echo $info['symbol']; ?></div>
                    <div class="label">To: <?php echo $code; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="transferFromCurrency" value="USD">
            <input type="hidden" id="transferToCurrency" value="KHR">
            <input type="number" id="transferAmount" class="modal-input" placeholder="Amount" step="0.01">
            <input type="number" id="exchangeRate" class="modal-input" placeholder="Exchange Rate" step="0.000001" value="4000">
            <input type="text" id="transferNote" class="modal-input" placeholder="Note (optional)">
            <div class="modal-btns">
                <button class="modal-btn modal-cancel" onclick="closeModal('transferModal')">Cancel</button>
                <button class="modal-btn modal-add" onclick="processTransfer()">Transfer</button>
            </div>
        </div>
    </div>

    <!-- History Panel -->
    <div class="history-panel" id="historyPanel">
        <div class="history-header">
            <h4>Transaction History</h4>
            <button class="close-history" onclick="closeHistory()">&times;</button>
        </div>
        <div class="history-stats">
            <div class="stat-box">
                <div class="stat-label">Total Income</div>
                <div class="stat-value income"><?php echo formatCurrency($totalIncome, $activeCurrency); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Expense</div>
                <div class="stat-value expense"><?php echo formatCurrency($totalExpense, $activeCurrency); ?></div>
            </div>
        </div>
        <div class="history-list" id="historyList">
            <?php if (empty($transactions)): ?>
                <div class="empty-history">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
                        <rect x="2" y="7" width="20" height="14" rx="2"/>
                        <line x1="16" y1="21" x2="16" y2="17"/>
                        <line x1="8" y1="21" x2="8" y2="17"/>
                    </svg>
                    <p>No transactions yet</p>
                    <p class="empty-sub">Tap + to add income or expense</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-cat">
                            <?php if ($tx['type'] === 'expense'): ?>
                                <span class="cat-dot" style="background-color: #e07070;"></span>
                            <?php elseif ($tx['type'] === 'income'): ?>
                                <span class="cat-dot" style="background-color: #6dbf8c;"></span>
                            <?php else: ?>
                                <span class="cat-dot" style="background-color: #d4a017;"></span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tx['category_name']); ?>
                            <?php if (!empty($tx['note'])): ?>
                                <span class="transaction-note">📝 <?php echo htmlspecialchars(substr($tx['note'], 0, 30)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="transaction-date"><?php echo date('M d, Y • H:i', strtotime($tx['date'])); ?></div>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <span class="transaction-amount <?php echo $tx['type']; ?>">
                            <?php if ($tx['type'] === 'income'): ?>+<?php elseif ($tx['type'] === 'expense'): ?>-<?php else: ?>⇄<?php endif; ?>
                            <?php echo formatCurrency($tx['amount'], $tx['currency_code'] ?? $activeCurrency); ?>
                        </span>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this transaction?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="record_type" value="<?php echo $tx['type']; ?>">
                            <input type="hidden" name="record_id" value="<?php echo $tx['id']; ?>">
                            <button type="submit" class="delete-tx" title="Delete">&times;</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Date Range Modal -->
    <div class="modal-overlay" id="dateRangeModal" onclick="closeModal('dateRangeModal')">
        <div class="modal" onclick="event.stopPropagation()">
            <h3>Select Date Range</h3>
            <div class="date-inputs">
                <div class="date-input-group">
                    <label>Start Date</label>
                    <input type="date" id="rangeStartDate" class="modal-input">
                </div>
                <div class="date-input-group">
                    <label>End Date</label>
                    <input type="date" id="rangeEndDate" class="modal-input">
                </div>
            </div>
            <div class="modal-btns">
                <button class="modal-btn modal-cancel" onclick="closeModal('dateRangeModal')">Cancel</button>
                <button class="modal-btn modal-add" onclick="applyDateRangeFilter()">Apply</button>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="message <?php echo $message['type']; ?>"><?php echo htmlspecialchars($message['text']); ?></div>
    <?php endif; ?>

    <script src="script.js"></script>
    <script>
        const activeCurrency = '<?php echo $activeCurrency; ?>';
        const exchangeRates = <?php echo json_encode($exchange_rates); ?>;
        const currentDateFilter = '<?php echo $dateFilter; ?>';
        const startDateParam = '<?php echo $startDateParam; ?>';
        const endDateParam = '<?php echo $endDateParam; ?>';
        
        // Search functionality
        function openSearchModal() {
            document.getElementById('searchModal').classList.add('open');
            document.getElementById('searchInput').value = '';
            document.getElementById('searchInput').focus();
            document.getElementById('searchResults').innerHTML = '<div class="no-results">Enter a search term above</div>';
        }
        
        function performSearch() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (searchTerm.length < 2) {
                document.getElementById('searchResults').innerHTML = '<div class="no-results">Please enter at least 2 characters</div>';
                return;
            }
            
            // Redirect to home with search parameter
            const url = new URL(window.location.href);
            url.searchParams.set('search', searchTerm);
            window.location.href = url.toString();
        }
        
        function clearSearch() {
            const url = new URL(window.location.href);
            url.searchParams.delete('search');
            window.location.href = url.toString();
        }
        
        // Allow Enter key to search
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && document.getElementById('searchModal').classList.contains('open')) {
                performSearch();
            }
        });
        
        setTimeout(() => {
            const msg = document.querySelector('.message');
            if (msg) msg.remove();
        }, 3000);
        
        // Global search variable for script.js
        const currentSearchQuery = '<?php echo addslashes($searchQuery); ?>';
    </script>
</body>
</html>