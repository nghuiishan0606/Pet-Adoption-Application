<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// If already logged in, redirect
redirect_if_logged_in();

$error = '';
$notice = '';

if (isset($_GET['notice'])) {
    if ($_GET['notice'] === 'use_admin_portal') {
        $notice = 'Admin accounts must log in through this secure portal.';
    }
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $error = 'You must log in as an Administrator to access that area.';
    }
}

// Handle Admin Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] !== 'admin') {
                    $error = 'Access denied. Only Administrator accounts can log in here.';
                } else {
                    // Initialize Admin Session
                    $_SESSION['admin_session'] = [
                        'logged_in' => true,
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                    
                    header("Location: admin/dashboard.php");
                    exit();
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petopia - Secure Admin Access</title>
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
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f2ff 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            color: #334155;
        }
        .admin-card {
            background-color: #ffffff;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.08);
            width: 100%;
            max-width: 440px;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 3rem;
            margin-bottom: 10px;
            display: inline-block;
        }
        .title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #3b82f6;
        }
        .subtitle {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 5px;
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
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            color: #334155;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #3b82f6;
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(59, 130, 246, 0.25);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .alert-danger {
            background-color: #fef2f2;
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.15);
        }
        .alert-info {
            background-color: #eff6ff;
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.15);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #3b82f6;
            font-weight: 600;
            text-decoration: underline;
        }
        .back-link:hover {
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="admin-card">
        <div class="header">
            <span class="logo">
			<svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="70" height="70" fill="none" viewBox="0 0 24 24">
				<path stroke="#BAE6FD" stroke-linecap="round" stroke-width="2" d="M4.5 17H4a1 1 0 0 1-1-1 3 3 0 0 1 3-3h1m0-3.05A2.5 2.5 0 1 1 9 5.5M19.5 17h.5a1 1 0 0 0 1-1 3 3 0 0 0-3-3h-1m0-3.05a2.5 2.5 0 1 0-2-4.45m.5 13.5h-7a1 1 0 0 1-1-1 3 3 0 0 1 3-3h3a3 3 0 0 1 3 3 1 1 0 0 1-1 1Zm-1-9.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
			</svg>
			</span>
            <h1 class="title">Admin Portal</h1>
            <p class="subtitle">Secure administrative authentication console.</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($notice): ?>
            <div class="alert alert-info">ℹ️ <?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="admin_login.php">
            <div class="form-group">
                <label for="admin-email">Admin Email</label>
                <input type="email" name="email" id="admin-email" class="form-control" placeholder="enter your registered email" required>
            </div>
            
            <div class="form-group">
                <label for="admin-password">Password</label>
                <input type="password" name="password" id="admin-password" class="form-control" placeholder="enter your password" required>
            </div>
            
            <button type="submit" class="btn-submit">Sign in</button>
        </form>
        
        <a href="index.php" class="back-link">&larr; Back to Portal Gateway</a>
    </div>
</body>
</html>
