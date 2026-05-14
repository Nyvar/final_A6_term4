<?php
// login.php - User authentication page
require_once __DIR__ . '/../functions/config.php';

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=home');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $pdo = getDBConnection();
        
        // Check if user exists by username or email
        $stmt = $pdo->prepare("SELECT user_id, username, email, password FROM Users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            header('Location: ../index.php?page=home');
            exit;
        } else {
            $error = 'Invalid username/email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Monefy Finance Tracker</title>
    <link rel="stylesheet" href="../css/styleAfterLogin.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--green-light) 0%, #d4f5e0 100%);
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 32px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-lg);
            animation: fadeInUp 0.5s ease;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        /* New Brand Logo Styles */
        .brand-logo-text {
            font-size: 36px;
            font-weight: 800;
            color: var(--green-dark);
            letter-spacing: -0.02em;
            line-height: 1.1;
            margin-bottom: 4px;
        }
        .login-logo p {
            color: var(--text-mid);
            font-size: 14px;
            margin-top: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            font-size: 16px;
            transition: all 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--green);
        }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: var(--green);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .login-btn:hover {
            background: var(--green-dark);
            transform: translateY(-2px);
        }
        .error-message {
            background: var(--red-light);
            color: var(--red);
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 24px;
            color: var(--text-mid);
            font-size: 14px;
        }
        .register-link a {
            color: var(--green);
            text-decoration: none;
            font-weight: 600;
        }
        .demo-info {
            background: var(--gray-bg);
            padding: 16px;
            border-radius: 16px;
            margin-top: 24px;
            font-size: 13px;
            color: var(--text-mid);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="brand-logo brand-logo-text" aria-hidden="true">Monefy</div>
                <p>Personal Finance Tracker</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Register</a>
            </div>
        </div>
    </div>
</body>
</html>