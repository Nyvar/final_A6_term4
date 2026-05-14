<?php
// account.php - View and update user account
require_once __DIR__ . '/../functions/config.php';
requireLogin();

$pdo = getDBConnection();
$user_id = getUserId();
$message = '';
$error = '';

// Get user info
$stmt = $pdo->prepare("SELECT username, email, created_at FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($username) || empty($email)) {
            $error = 'Please fill in all fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email';
        } else {
            $stmt = $pdo->prepare("UPDATE Users SET username = ?, email = ? WHERE user_id = ?");
            if ($stmt->execute([$username, $email, $user_id])) {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $message = 'Profile updated successfully!';
                $user['username'] = $username;
                $user['email'] = $email;
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT password FROM Users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $db_user = $stmt->fetch();
        
        if (!password_verify($current, $db_user['password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                $message = 'Password changed successfully!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Monefy</title>
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
    <style>
        .account-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .account-card {
            background: white;
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .account-card h2 {
            margin-bottom: 24px;
            color: var(--text-dark);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 12px;
        }
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .info-label {
            width: 120px;
            font-weight: 600;
            color: var(--text-mid);
        }
        .info-value {
            flex: 1;
            color: var(--text-dark);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--green);
        }
        .save-btn {
            background: var(--green);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--gray-bg);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
        }
        .message {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #e8f5ee;
            color: var(--green);
        }
        .message.error {
            background: var(--red-light);
            color: var(--red);
        }
    </style>
</head>
<body>
    <div class="account-container">
        <h1>Account Settings</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="account-card">
            <h2>Profile Information</h2>
            <div class="info-row">
                <div class="info-label">Member since:</div>
                <div class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <button type="submit" class="save-btn">Update Profile</button>
            </form>
        </div>
        
        <div class="account-card">
            <h2>Change Password</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password (min 6 characters)</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" class="save-btn">Change Password</button>
            </form>
        </div>
        
        <a href="homepage_after_login.php" class="back-btn">Back</a>
    </div>
    
    <script>
        setTimeout(() => {
            const msg = document.querySelector('.message');
            if (msg) msg.remove();
        }, 3000);
    </script>
</body>
</html>