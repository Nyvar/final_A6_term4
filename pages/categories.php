<?php
// categories.php - Manage categories
require_once __DIR__ . '/../functions/config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();
$message = '';

// Handle category operations
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

// Get all categories
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
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
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
        
        <a href="homepage_after_login.php" class="back-btn">Back</a>
    </div>
    
    <!-- Add Category Modal -->
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