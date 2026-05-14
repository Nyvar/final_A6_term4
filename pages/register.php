<?php
// register.php - User registration page
require_once 'config.php';

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: homepage_after_login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $pdo = getDBConnection();
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already taken';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Users (username, email, password) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // Create default currencies for the user
                    $stmt = $pdo->prepare("INSERT INTO Currency (user_id, currency_code, currency_name, symbol, wallet) VALUES 
                        (?, 'USD', 'US Dollar', '$', 5000),
                        (?, 'KHR', 'Cambodian Riel', '៛', 20000000),
                        (?, 'EUR', 'Euro', '€', 0),
                        (?, 'GBP', 'British Pound', '£', 0)");
                    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
                    
                    // Auto login after registration
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    header('Location: homepage_after_login.php');
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
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
    <title>Register - Monefy Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--green-light) 0%, #d4f5e0 100%);
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 32px;
            padding: 40px;
            width: 100%;
            max-width: 460px;
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
        .register-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        /* New Brand Logo Styles */
        .brand-logo {
            height: 70px; /* Adjust height based on your image proportions */
            width: auto;
            max-width: 100%;
            margin-bottom: 12px;
        }
        .register-logo h1 {
            font-size: 28px;
            color: var(--text-dark);
            margin-top: 12px;
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
        .register-btn {
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
        .register-btn:hover {
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
        .login-link {
            text-align: center;
            margin-top: 24px;
            color: var(--text-mid);
            font-size: 14px;
        }
        .login-link a {
            color: var(--green);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-logo">
                <!-- Updated Image Section -->
                <img src="image/monefy.png" alt="Monefy Logo" class="brand-logo">
                <h1>Create Account</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password (min 6 characters)</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" class="register-btn">Create Account</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
</body>
</html>