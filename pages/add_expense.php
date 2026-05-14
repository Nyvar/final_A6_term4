<?php
require_once __DIR__ . '/../functions/config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();

$activeCurrency = $_GET['currency'] ?? $_SESSION['active_currency'] ?? 'USD';
$_SESSION['active_currency'] = $activeCurrency;

$error = '';
$formAmount = '';
$formNote = '';

$stmt = $pdo->prepare("SELECT * FROM Categories WHERE record_type = 'expense' ORDER BY display_order");
$stmt->execute();
$expenseCategories = $stmt->fetchAll();

$selectedCategoryId = intval($_GET['category_id'] ?? 0);
if ($selectedCategoryId === 0 && !empty($expenseCategories)) {
    $selectedCategoryId = $expenseCategories[0]['category_id'];
}
$categoryIds = array_column($expenseCategories, 'category_id');
if (!in_array($selectedCategoryId, $categoryIds, true)) {
    $selectedCategoryId = $expenseCategories[0]['category_id'] ?? 0;
}

$stmt = $pdo->prepare("SELECT * FROM Payment_methods WHERE user_id IS NULL OR user_id = ?");
$stmt->execute([$user_id]);
$paymentMethods = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_expense') {
    $formAmount = trim($_POST['amount'] ?? '');
    $selectedCategoryId = intval($_POST['category_id'] ?? $selectedCategoryId);
    $paymentMethodId = intval($_POST['payment_method_id'] ?? ($paymentMethods[0]['method_id'] ?? 1));
    $formNote = trim($_POST['note'] ?? '');
    $amount = floatval($formAmount);

    if ($amount > 0 && $selectedCategoryId > 0) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO Expenses (user_id, amount, date, currency_code, category_id, payment_method_id, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $amount, date('Y-m-d H:i:s'), $activeCurrency, $selectedCategoryId, $paymentMethodId, $formNote]);

            $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet - ? WHERE user_id = ? AND currency_code = ?");
            $stmt->execute([$amount, $user_id, $activeCurrency]);

            $pdo->commit();
            header('Location: homepage_after_login.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Unable to add expense: ' . $e->getMessage();
        }
    } else {
        $error = 'Please enter a valid expense amount and category.';
    }
}

$currencySymbol = $available_currencies[$activeCurrency]['symbol'] ?? '$';

function getCategoryIconPath($categoryName) {
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
    return $icons[$categoryName] ?? '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Monefy</title>
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
    <style>
        body {
            background: var(--page-bg, #f2f6fb);
        }
        .page-container {
            max-width: 960px;
            margin: 0 auto;
            padding: 24px 20px 40px;
        }
        .entry-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .entry-header h1 {
            margin: 0;
            font-size: clamp(28px, 4vw, 36px);
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            background: var(--white);
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
        }
        .entry-card {
            background: var(--white);
            border-radius: 28px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }
        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 20px;
            font-weight: 700;
        }
        .message-inline {
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="page-container">
       

        <?php if ($error): ?>
            <div class="message message-error message-inline"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="entry-card">
            <div class="section-heading">
                <span>Enter amount</span>
                <span class="text-muted"><?php echo htmlspecialchars($activeCurrency); ?></span>
            </div>

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

            <form method="POST" novalidate>
                <input type="hidden" name="action" value="add_expense">
                <div class="section-heading">
                    <span>Category</span>
                </div>
                <div class="category-grid" id="expenseCategoryGrid">
                    <?php foreach ($expenseCategories as $cat): ?>
                        <div class="category-option<?php echo ($cat['category_id'] === $selectedCategoryId ? ' selected' : ''); ?>" data-cat-id="<?php echo $cat['category_id']; ?>" onclick="selectCategory(this)">
                            <div class="category-icon" style="background-color: <?php echo htmlspecialchars($cat['color']); ?>20;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="<?php echo htmlspecialchars($cat['color']); ?>" stroke-width="2"><?php echo getCategoryIconPath($cat['category_name']); ?></svg>
                            </div>
                            <div class="label"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" id="expenseCategoryId" name="category_id" value="<?php echo htmlspecialchars($selectedCategoryId); ?>">
                <input type="number" id="expenseAmount" name="amount" class="modal-input" placeholder="Amount" step="0.01" value="<?php echo htmlspecialchars($formAmount); ?>">
                <select id="paymentMethodId" name="payment_method_id" class="modal-select">
                    <?php foreach ($paymentMethods as $pm): ?>
                        <option value="<?php echo $pm['method_id']; ?>"><?php echo htmlspecialchars($pm['method_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="expenseNote" name="note" class="modal-input" placeholder="Note (optional)" value="<?php echo htmlspecialchars($formNote); ?>">
                <div class="modal-btns">
                    <button type="button" class="modal-btn modal-cancel" onclick="window.location.href='homepage_after_login.php'">Cancel</button>
                    <button type="button" class="modal-btn modal-add expense" onclick="ensureModalAmountCommitted('expense'); addExpense()">Add Expense</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../js/responsive.js"></script>
    <script>
        initModalCalcFromInput('expense');
        document.querySelectorAll('#expenseCategoryGrid .category-option').forEach(opt => {
            if (opt.dataset.catId === String(<?php echo json_encode($selectedCategoryId); ?>)) {
                opt.classList.add('selected');
            }
        });
    </script>
</body>
</html>