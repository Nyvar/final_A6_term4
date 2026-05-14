<?php
require_once __DIR__ . '/../functions/config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();

$activeCurrency = $_GET['currency'] ?? $_SESSION['active_currency'] ?? 'USD';
$_SESSION['active_currency'] = $activeCurrency;

$error = '';
$formAmount = '';
$formNote = trim($_GET['category'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_income') {
    $formAmount = trim($_POST['amount'] ?? '');
    $formNote = trim($_POST['note'] ?? '');
    $amount = floatval($formAmount);

    if ($amount > 0) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO Income (user_id, amount, date, currency_code, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $amount, date('Y-m-d H:i:s'), $activeCurrency, $formNote]);

            $stmt = $pdo->prepare("UPDATE Currency SET wallet = wallet + ? WHERE user_id = ? AND currency_code = ?");
            $stmt->execute([$amount, $user_id, $activeCurrency]);

            $pdo->commit();
            header('Location: homepage_after_login.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Unable to add income: ' . $e->getMessage();
        }
    } else {
        $error = 'Please enter a valid income amount.';
    }
}

$currencySymbol = $available_currencies[$activeCurrency]['symbol'] ?? '$';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Income - Monefy</title>
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
    <style>
        body {
            background: var(--page-bg, #f2f6fb);
        }
        .page-container {
            max-width: 760px;
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

            <form method="POST" novalidate>
                <input type="hidden" name="action" value="add_income">
                <input type="number" id="incomeAmount" name="amount" class="modal-input" placeholder="Amount" step="0.01" value="<?php echo htmlspecialchars($formAmount); ?>">
                <input type="text" id="incomeNote" name="note" class="modal-input" placeholder="Note or source" value="<?php echo htmlspecialchars($formNote); ?>">
                <div class="modal-btns">
                    <button type="button" class="modal-btn modal-cancel" onclick="window.location.href='homepage_after_login.php'">Cancel</button>
                    <button type="button" class="modal-btn modal-add" onclick="ensureModalAmountCommitted('income'); addIncome()">Add Income</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../js/responsive.js"></script>
    <script>
        initModalCalcFromInput('income');
    </script>
</body>
</html>
