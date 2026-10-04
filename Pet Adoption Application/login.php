<?php
// Secure initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Clear only the user side if necessary, but don't blow up the admin session tab!
if (isset($_SESSION['user_session']['logged_in']) && $_SESSION['user_session']['logged_in'] === true) {
    header("Location: home.php");
    exit();
}

$login_error = '';
$register_error = '';
$register_success = '';
$form_mode = 'login';

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $form_mode = 'login';
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $login_error = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'blocked') {
                    $login_error = 'Your account has been blocked by the Administrator.';
                } elseif ($user['role'] === 'admin') {
                    // Redirect admin accounts to dedicated admin portal page
                    header("Location: admin_login.php?notice=use_admin_portal");
                    exit();
                } else {
                    // CRITICAL FIX: Fully isolated nested key mapping for User session
                    $_SESSION['user_session'] = [
                        'logged_in' => true,
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                    
                    header("Location: home.php");
                    exit();
                }
            } else {
                $login_error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $login_error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Register POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $form_mode = 'register';
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($name) || empty($phone) || empty($email) || empty($address) || empty($password) || empty($confirm_password)) {
        $register_error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $register_error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $register_error = 'Password must be at least 6 characters long.';
    } else {
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->fetch()) {
                $register_error = 'Email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $pdo->prepare("INSERT INTO users (name, email, phone, address, password, role, status) VALUES (?, ?, ?, ?, ?, 'user', 'active')");
                $stmt_insert->execute([$name, $email, $phone, $address, $hash]);
                
                $register_success = 'Registration successful! You can now log in.';
                $form_mode = 'login';
            }
        } catch (PDOException $e) {
            $register_error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petopia - User Authentication</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #fff0f2 0%, #ffeef2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 30px 20px;
            color: #4a3e40;
        }
        .auth-card {
            background-color: #ffffff;
            border-radius: 24px;
            border: 1px solid #ffdbe2;
            box-shadow: 0 12px 36px rgba(209, 92, 122, 0.08);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .auth-header {
            padding: 35px 35px 20px;
            text-align: center;
        }
        .auth-logo {
            font-size: 2.8rem;
            margin-bottom: 8px;
            display: inline-block;
        }
        .auth-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #d15c7a;
        }
        .auth-subtitle {
            font-size: 0.95rem;
            color: #8c7e80;
            margin-top: 5px;
        }
        .auth-tabs {
            display: flex;
            border-bottom: 1px solid #ffdbe2;
            background-color: #fffafb;
        }
        .auth-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            background: none;
            color: #8c7e80;
            font-size: 1rem;
        }
        .auth-tab.active {
            color: #d15c7a;
            background-color: #ffffff;
            border-bottom: 3px solid #d15c7a;
        }
        .auth-body {
            padding: 35px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 12px 18px;
            border-radius: 12px;
            border: 1px solid #ffdbe2;
            background-color: #fffafb;
            color: #4a3e40;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        .form-control:focus {
            outline: none;
            border-color: #ffb8c6;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(209, 92, 122, 0.12);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #d15c7a;
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(209, 92, 122, 0.15);
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #b54661;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(209, 92, 122, 0.25);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .alert-danger {
            background-color: #ffeeed;
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.15);
        }
        .alert-success {
            background-color: #eafaf1;
            color: #4cd964;
            border: 1px solid rgba(76, 217, 100, 0.15);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #d15c7a;
            font-weight: 600;
            text-decoration: underline;
        }
        .back-link:hover {
            color: #b54661;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <span class="auth-logo">
			<svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="70" height="70" fill="pink" viewBox="0 0 24 24">
			  <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
			</svg>
			</span>
            <h1 class="auth-title">Petopia</h1>
            <p class="auth-subtitle">Join us in making a difference in shelter animal lives.</p>
        </div>
        
        <div class="auth-tabs">
            <button type="button" class="auth-tab <?= $form_mode === 'login' ? 'active' : '' ?>" onclick="switchForm('login')">Sign In</button>
            <button type="button" class="auth-tab <?= $form_mode === 'register' ? 'active' : '' ?>" onclick="switchForm('register')">Register</button>
        </div>
        
        <div class="auth-body">
            
            <!-- Login Form -->
            <div id="login-form-container" style="display: <?= $form_mode === 'login' ? 'block' : 'none' ?>;">
                <?php if ($login_error): ?>
                    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($login_error) ?></div>
                <?php endif; ?>
                <?php if ($register_success): ?>
                    <div class="alert alert-success">✅ <?= htmlspecialchars($register_success) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" name="email" id="login-email" class="form-control" placeholder="enter your registered email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" name="password" id="login-password" class="form-control" placeholder="enter password" required>
                    </div>
                    
                    <button type="submit" class="btn-submit">Sign In</button>
                </form>
            </div>
            
            <!-- Registration Form -->
            <div id="register-form-container" style="display: <?= $form_mode === 'register' ? 'block' : 'none' ?>;">
                <?php if ($register_error): ?>
                    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($register_error) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="reg-name">Full Name *</label>
                        <input type="text" name="name" id="reg-name" class="form-control" placeholder="name" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg-phone">Phone Number *</label>
                            <input type="text" name="phone" id="reg-phone" class="form-control" placeholder="phone number" required>
                        </div>
                        <div class="form-group">
                            <label for="reg-email">Email Address *</label>
                            <input type="email" name="email" id="reg-email" class="form-control" placeholder="email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reg-address">Home Address *</label>
                        <textarea name="address" id="reg-address" class="form-control" rows="2" placeholder="address" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg-password">Password *</label>
                            <input type="password" name="password" id="reg-password" class="form-control" placeholder="min 6 characters" required>
                        </div>
                        <div class="form-group">
                            <label for="reg-confirm">Confirm Password *</label>
                            <input type="password" name="confirm_password" id="reg-confirm" class="form-control" placeholder="re-enter password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Create Account</button>
                </form>
            </div>
            
            <a href="index.php" class="back-link">&larr; Back to Portal Gateway</a>
        </div>
    </div>
    <script src="js/validation.js"></script>
    <script>
        function switchForm(mode) {
            const loginBtn = document.querySelectorAll('.auth-tab')[0];
            const registerBtn = document.querySelectorAll('.auth-tab')[1];
            const loginForm = document.getElementById('login-form-container');
            const registerForm = document.getElementById('register-form-container');
            
            if (mode === 'login') {
                loginBtn.classList.add('active');
                registerBtn.classList.remove('active');
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
            } else {
                loginBtn.classList.remove('active');
                registerBtn.classList.add('active');
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Login form — only has email
            attachEmailValidation('#login-form-container form', 'login-email');

            // Register form — has email, phone, and address
            attachEmailValidation('#register-form-container form', 'reg-email');
            attachPhoneValidation('#register-form-container form', 'reg-phone');
            attachAddressValidation('#register-form-container form', 'reg-address');
        });
        
        // Auto toggles tab if error occurred in register tab
        <?php if ($form_mode === 'register'): ?>
            switchForm('register');
            
        <?php endif; ?>
    </script>
</body>
</html>
