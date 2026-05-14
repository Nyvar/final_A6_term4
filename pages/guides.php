<?php
// guides.php - Help and documentation
require_once __DIR__ . '/../functions/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guides & Help - Monefy</title>
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
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
        
        <a href="homepage_after_login.php" class="back-btn">Back</a>
    </div>
    
    <!-- Include Sidebar -->
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            answer.classList.toggle('show');
            const arrow = element.querySelector('span:last-child');
            arrow.textContent = answer.classList.contains('show') ? '▲' : '▼';
        }
    </script>
    <script src="../js/responsive.js"></script>
</body>
</html>