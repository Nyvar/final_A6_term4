<?php
require_once __DIR__ . '/../functions/config.php';

$fromAppRoot = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/pages/') === false;
$pagesPrefix = $fromAppRoot ? 'pages/' : '';
$cssBase = $fromAppRoot ? '' : '../';
$imagesBase = $fromAppRoot ? 'images/' : '../images/';

if (!function_exists('monefy_home_reload')) {
    function monefy_home_reload(array $override = []) {
        global $fromAppRoot, $activeCurrency, $dateFilter, $startDateParam, $endDateParam, $searchQuery;
        $currency = $override['currency'] ?? $activeCurrency;
        $df = $override['date_filter'] ?? $dateFilter;
        $q = ['currency' => $currency, 'date_filter' => $df];
        $sd = array_key_exists('start_date', $override) ? $override['start_date'] : $startDateParam;
        $ed = array_key_exists('end_date', $override) ? $override['end_date'] : $endDateParam;
        if (!empty($sd) && !empty($ed)) {
            $q['start_date'] = $sd;
            $q['end_date'] = $ed;
        }
        $sq = array_key_exists('search', $override) ? $override['search'] : $searchQuery;
        if ($sq !== '' && $sq !== null) {
            $q['search'] = $sq;
        }
        $qs = http_build_query($q);
        return $fromAppRoot ? ('index.php?page=home&' . $qs) : ('homepage_after_login.php?' . $qs);
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . ($fromAppRoot ? 'pages/login.php' : 'login.php'));
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
        header('Location: ' . monefy_home_reload());
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
        header('Location: ' . monefy_home_reload());
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
        header('Location: ' . monefy_home_reload());
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
        
        header('Location: ' . monefy_home_reload());
        exit;
    }
    
    if ($action === 'change_currency') {
        $newCurrency = $_POST['currency_code'] ?? 'USD';
        $_SESSION['active_currency'] = $newCurrency;
        header('Location: ' . monefy_home_reload(['currency' => $newCurrency]));
        exit;
    }
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
$monthName = date('F Y');
$displayUsername = htmlspecialchars($_SESSION['username'] ?? 'user_name', ENT_QUOTES, 'UTF-8');
$headerDateLine = date('M') . ', ' . date('d') . ', ' . date('Y');
/* Orbit: up to 12 unique categories, alternating expense / income for balance */
$orbitMax = 12;
$orbitItems = [];
$orbitSeenIds = [];
$exi = 0;
$ini = 0;
while (count($orbitItems) < $orbitMax) {
    $orbitCountBefore = count($orbitItems);
    while ($exi < count($expenseCategories)) {
        $c = $expenseCategories[$exi++];
        $cid = (int) ($c['category_id'] ?? 0);
        if ($cid && !isset($orbitSeenIds[$cid])) {
            $orbitSeenIds[$cid] = true;
            $orbitItems[] = ['kind' => 'category', 'record_type' => 'expense', 'cat' => $c];
            break;
        }
    }
    if (count($orbitItems) >= $orbitMax) {
        break;
    }
    while ($ini < count($incomeCategories)) {
        $c = $incomeCategories[$ini++];
        $cid = (int) ($c['category_id'] ?? 0);
        if ($cid && !isset($orbitSeenIds[$cid])) {
            $orbitSeenIds[$cid] = true;
            $orbitItems[] = ['kind' => 'category', 'record_type' => 'income', 'cat' => $c];
            break;
        }
    }
    if (count($orbitItems) === $orbitCountBefore) {
        break;
    }
}
$orbitCount = count($orbitItems);
$orbitStepDeg = $orbitCount > 0 ? (360 / $orbitCount) : 30;
for ($oi = 0; $oi < $orbitCount; $oi++) {
    $orbitItems[$oi]['angle'] = $oi * $orbitStepDeg;
}
$orbitDenseClass = ($orbitCount >= 8) ? ' orbit-stage--dense' : '';
$categoryIconPaths = [
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
    'Pets' => '<circle cx="12" cy="12" r="10"/><circle cx="9" cy="10" r="1"/><circle cx="15" cy="10" r="1"/>',
    'Salary' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    'Bonus' => '<circle cx="12" cy="12" r="10"/><path d="M8 12h8M12 8v8"/>',
    'Investment' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
    'Freelance' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3v4"/>',
];
?>
<!DOCTYPE html>
<html lang="en" class="wallet-page-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Monefy - Personal Finance Tracker</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssBase); ?>css/styleAfterLogin.css">
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
<body class="wallet-page">
    <div class="app-container app-container--wallet">
        <header class="topbar topbar--wallet">
            <div class="topbar-row topbar-row--main">
                <div class="topbar-left-block">
                    <button type="button" class="icon-btn topbar-left-menu-trigger" aria-label="Accounts" onclick="event.stopPropagation(); toggleLeftDrawer();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div class="topbar-user" onclick="toggleLeftDrawer()" role="button" tabindex="0">
                        <div class="topbar-user-name"><?php echo $displayUsername; ?></div>
                        <div class="topbar-user-currency"><?php echo htmlspecialchars($currencySymbol . ' · ' . $activeCurrency); ?></div>
                    </div>
                </div>
                <div class="topbar-right-block">
                    <div class="topbar-icons-row">
                        <button type="button" class="icon-btn topbar-search-trigger" aria-label="Search" onclick="event.stopPropagation(); openSearchModal();">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </button>
                        <button type="button" class="icon-btn topbar-transfer-trigger" aria-label="Transfer" onclick="event.stopPropagation(); openTransferModal();">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                        </button>
                        <button type="button" class="icon-btn topbar-right-menu-trigger" aria-label="Menu" onclick="event.stopPropagation(); toggleRightNavDropdown();">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="topbar-date-row" onclick="openDateRangeModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span><?php echo htmlspecialchars($headerDateLine); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="left-drawer-backdrop" id="leftDrawerBackdrop" onclick="closeLeftDrawer()"></div>
        <aside class="left-drawer" id="leftDrawer" aria-hidden="true">
            <div class="left-drawer-header">
                <span>Accounts</span>
                <button type="button" class="left-drawer-close icon-btn" aria-label="Close" onclick="closeLeftDrawer()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="left-drawer-section-title">Currency</div>
            <div class="left-drawer-item" onclick="changeCurrency('USD'); closeLeftDrawer();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4" y1="4" x2="20" y2="20"/></svg><span>USD ($)</span></div>
            <div class="left-drawer-item" onclick="changeCurrency('KHR'); closeLeftDrawer();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4" y1="4" x2="20" y2="20"/></svg><span>KHR (៛)</span></div>
            <div class="left-drawer-item" onclick="changeCurrency('EUR'); closeLeftDrawer();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4" y1="4" x2="20" y2="20"/></svg><span>EUR (€)</span></div>
            <div class="left-drawer-item" onclick="changeCurrency('GBP'); closeLeftDrawer();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4" y1="4" x2="20" y2="20"/></svg><span>GBP (£)</span></div>
            <div class="left-drawer-section-title">Actions</div>
            <div class="left-drawer-item" onclick="openTransferModal(); closeLeftDrawer();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg><span>Transfer money</span></div>
            <div class="left-drawer-item" onclick="openHistory(); closeLeftDrawer();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>Transaction history</span></div>
            <div class="left-drawer-item" onclick="showTransit();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18M8 6v12M16 6v12"/><rect x="6" y="4" width="12" height="16" rx="1"/></svg><span>Transit</span></div>
        </aside>

        <div class="right-nav-backdrop" id="rightNavBackdrop" onclick="closeRightNavDropdown()"></div>
        <nav class="right-nav-dropdown" id="rightNavDropdown" aria-hidden="true">
            <div class="right-nav-dropdown-arrow" aria-hidden="true"></div>
            <div class="right-nav-dropdown-header">
                <span>Menu</span>
                <button type="button" class="icon-btn right-nav-close" aria-label="Close menu" onclick="closeRightNavDropdown()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="right-nav-dropdown-body">
                <div class="right-nav-item" onclick="location.href='<?php echo $pagesPrefix; ?>add_expense.php'; closeRightNavDropdown();">
                    <img src="<?php echo htmlspecialchars($imagesBase); ?>icon-add-expense.svg" alt="" class="right-nav-item-img" width="28" height="28"/>
                    <span>Add expense</span>
                </div>
                <div class="right-nav-item" onclick="location.href='<?php echo $pagesPrefix; ?>add_income.php'; closeRightNavDropdown();">
                    <img src="<?php echo htmlspecialchars($imagesBase); ?>icon-add-income.svg" alt="" class="right-nav-item-img" width="28" height="28"/>
                    <span>Add income</span>
                </div>
                <div class="right-nav-item" onclick="location.href='<?php echo $pagesPrefix; ?>categories.php'; closeRightNavDropdown();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Categories</span>
                </div>
                <div class="right-nav-item" onclick="location.href='<?php echo $pagesPrefix; ?>account.php'; closeRightNavDropdown();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><line x1="16" y1="21" x2="16" y2="17"/><line x1="8" y1="21" x2="8" y2="17"/></svg>
                    <span>Account</span>
                </div>
                <div class="right-nav-item" onclick="location.href='<?php echo $pagesPrefix; ?>currencies.php'; closeRightNavDropdown();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M4 4l16 16"/></svg>
                    <span>Currencies</span>
                </div>
                <div class="right-nav-item" onclick="location.href='<?php echo $pagesPrefix; ?>settings.php'; closeRightNavDropdown();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Settings</span>
                </div>
                <div class="right-nav-item" onclick="location.href='<?php echo $pagesPrefix; ?>guides.php'; closeRightNavDropdown();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Guides &amp; Help</span>
                </div>
                <div class="right-nav-item right-nav-item--danger" onclick="location.href='<?php echo $pagesPrefix; ?>logout.php'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Logout</span>
                </div>
            </div>
        </nav>

        <div class="date-filter-bar date-filter-bar--compact period-toolbar">
            <button type="button" class="toolbar-search-chip" onclick="openSearchModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <span>Search</span>
            </button>
            <div class="filter-buttons">
                <button class="filter-btn <?php echo ($dateFilter == 'day' ? 'active' : ''); ?>" onclick="setDateFilter('day')">Day</button>
                <button class="filter-btn <?php echo ($dateFilter == 'week' ? 'active' : ''); ?>" onclick="setDateFilter('week')">Week</button>
                <button class="filter-btn <?php echo ($dateFilter == 'month' ? 'active' : ''); ?>" onclick="setDateFilter('month')">Month</button>
                <button class="filter-btn <?php echo ($dateFilter == 'year' ? 'active' : ''); ?>" onclick="setDateFilter('year')">Year</button>
                <button class="filter-btn <?php echo ($dateFilter == 'all' ? 'active' : ''); ?>" onclick="setDateFilter('all')">All</button>
                <button class="filter-btn <?php echo ($dateFilter == 'interval' ? 'active' : ''); ?>" onclick="openDateRangeModal()">Interval</button>
            </div>
            <div class="date-picker-wrapper" onclick="openDateRangeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span id="selectedDateRange"><?php echo htmlspecialchars($dateRangeText); ?></span>
            </div>
            <?php if (!empty($searchQuery)): ?>
            <div class="search-active-badge" onclick="clearSearch()">🔍 "<?php echo htmlspecialchars($searchQuery); ?>" <span style="cursor:pointer;">&times;</span></div>
            <?php endif; ?>
        </div>

        <main class="wallet-main">
            <h1 class="wallet-main-title">Your current wallet</h1>
            <div class="orbit-stage orbit-stage--radial<?php echo $orbitDenseClass; ?>">
                <?php foreach ($orbitItems as $idx => $item):
                    $oc = $item['cat'] ?? null;
                    if (!$oc) {
                        continue;
                    }
                    $ang = (float) $item['angle'];
                    $rt = $item['record_type'] ?? 'expense';
                    echo '<div class="orbit-node" style="--orbit-angle: ' . $ang . 'deg;"><div class="orbit-node-inner">';
                        $cid = (int) $oc['category_id'];
                        $cnameJson = json_encode($oc['category_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                        $strokeCol = htmlspecialchars($oc['color'] ?? '#1a1a1a', ENT_QUOTES, 'UTF-8');
                        $iconSvg = $categoryIconPaths[$oc['category_name']] ?? '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
                        if ($rt === 'income') {
                            echo '<a class="orbit-node-btn orbit-node-btn--action orbit-node-btn--category orbit-node-btn--income-cat" href="' . $pagesPrefix . 'add_income.php?category=' . rawurlencode($oc['category_name']) . '" title="Add income: ' . htmlspecialchars($oc['category_name'], ENT_QUOTES, 'UTF-8') . '">';
                        } else {
                            echo '<a class="orbit-node-btn orbit-node-btn--action orbit-node-btn--category orbit-node-btn--expense-cat" href="' . $pagesPrefix . 'add_expense.php?category_id=' . $cid . '" title="Add expense: ' . htmlspecialchars($oc['category_name'], ENT_QUOTES, 'UTF-8') . '">';
                        }
                        echo '<svg viewBox="0 0 24 24" fill="none" stroke="' . $strokeCol . '" stroke-width="2">' . $iconSvg . '</svg>';
                        echo '<span class="orbit-node-label">' . htmlspecialchars($oc['category_name']) . '</span></a>';
                    echo '</div></div>';
                endforeach; ?>
                <div class="orbit-center-ring">
                    <svg viewBox="0 0 240 240" aria-hidden="true">
                        <circle cx="120" cy="120" r="103" fill="none" stroke="#1a1a1a" stroke-width="2.5"/>
                        <circle cx="120" cy="120" r="100" fill="none" stroke="#ffffff" stroke-width="34"/>
                    </svg>
                    <div class="orbit-center-label"><?php echo htmlspecialchars(formatCurrency($balance, $activeCurrency)); ?></div>
                </div>
            </div>
            <?php if (!empty($expenseByCategory)): ?>
            <div class="category-breakdown">
                <div class="breakdown-title">Top expenses (period)</div>
                <?php foreach ($expenseByCategory as $cat): ?>
                <div class="breakdown-item">
                    <span class="breakdown-label"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                    <div class="breakdown-bar-container">
                        <div class="breakdown-bar" style="width: <?php echo ($cat['total'] / max($totalExpense, 1)) * 100; ?>%; background-color: <?php echo htmlspecialchars($cat['color']); ?>;"></div>
                    </div>
                    <span class="breakdown-amount"><?php echo htmlspecialchars(formatCurrency($cat['total'], $activeCurrency)); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>

        <div class="bottom-bar" onclick="window.location.href='<?php echo $pagesPrefix; ?>add_expense.php'">
            <div class="balance-area">
                <span class="balance-label">Balance</span>
                <span class="balance-amount"><?php echo formatCurrency($balance, $activeCurrency); ?></span>
            </div>
            <div class="menu-icon" onclick="event.stopPropagation(); toggleRightNavDropdown();"><span></span><span></span><span></span></div>
        </div>
        <div class="bottom-btns">
            <div class="btn-circle btn-expense" onclick="window.location.href='<?php echo $pagesPrefix; ?>add_expense.php'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="5" y1="12" x2="19" y2="12"/></svg></div>
            <div class="btn-circle btn-income" onclick="window.location.href='<?php echo $pagesPrefix; ?>add_income.php'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div>
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
        <div class="modal modal--with-calculator" onclick="event.stopPropagation()">
            <h3>Add Expense</h3>
            <div class="modal-calc-wrap" aria-label="Calculator">
                <div class="modal-calc-display">
                    <span id="expenseModalCalcValue">0</span><small><?php echo htmlspecialchars($activeCurrency); ?></small>
                </div>
                <div class="modal-calc-keypad">
                    <button type="button" class="key" onclick="modalCalcInput('expense','7')">7</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','8')">8</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','9')">9</button>
                    <button type="button" class="key key-operator" onclick="modalCalcInput('expense','+')">+</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','4')">4</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','5')">5</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','6')">6</button>
                    <button type="button" class="key key-operator" onclick="modalCalcInput('expense','-')">-</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','1')">1</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','2')">2</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','3')">3</button>
                    <button type="button" class="key key-operator" onclick="modalCalcInput('expense','*')">×</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','0')">0</button>
                    <button type="button" class="key" onclick="modalCalcInput('expense','.')">.</button>
                    <button type="button" class="key key-clear" onclick="modalCalcClear('expense')">C</button>
                    <button type="button" class="key key-operator" onclick="modalCalcInput('expense','/')">÷</button>
                    <button type="button" class="key key-equals key-equals-wide" onclick="modalCalcEquals('expense')">=</button>
                </div>
            </div>
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
        <div class="modal modal--with-calculator" onclick="event.stopPropagation()">
            <h3>Add Income</h3>
            <div class="modal-calc-wrap" aria-label="Calculator">
                <div class="modal-calc-display">
                    <span id="incomeModalCalcValue">0</span><small><?php echo htmlspecialchars($activeCurrency); ?></small>
                </div>
                <div class="modal-calc-keypad">
                    <button type="button" class="key" onclick="modalCalcInput('income','7')">7</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','8')">8</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','9')">9</button>
                    <button type="button" class="key key-operator" onclick="modalCalcInput('income','+')">+</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','4')">4</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','5')">5</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','6')">6</button>
                    <button type="button" class="key key-operator" onclick="modalCalcInput('income','-')">-</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','1')">1</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','2')">2</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','3')">3</button>
                    <button type="button" class="key key-operator" onclick="modalCalcInput('income','*')">×</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','0')">0</button>
                    <button type="button" class="key" onclick="modalCalcInput('income','.')">.</button>
                    <button type="button" class="key key-clear" onclick="modalCalcClear('income')">C</button>
                    <button type="button" class="key key-operator" onclick="modalCalcInput('income','/')">÷</button>
                    <button type="button" class="key key-equals key-equals-wide" onclick="modalCalcEquals('income')">=</button>
                </div>
            </div>
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

    <script src="<?php echo htmlspecialchars($cssBase); ?>js/responsive.js"></script>
    <script>
        const activeCurrency = '<?php echo $activeCurrency; ?>';
        const exchangeRates = <?php echo json_encode($exchange_rates); ?>;
        const currentDateFilter = '<?php echo $dateFilter; ?>';
        const startDateParam = '<?php echo $startDateParam; ?>';
        const endDateParam = '<?php echo $endDateParam; ?>';
        
        // Search functionality
        function openSearchModal() {
            if (typeof closeRightNavDropdown === 'function') closeRightNavDropdown();
            if (typeof closeLeftDrawer === 'function') closeLeftDrawer();
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