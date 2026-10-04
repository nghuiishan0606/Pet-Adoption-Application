<?php
require_once 'includes/auth.php';
if (is_admin()) {
    header("Location: admin/dashboard.php");
    exit();
} elseif (is_logged_in()) {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Petopia - Pet Adoption Portal</title>
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
        body, html {
            height: 100%;
            overflow: hidden;
            background-color: #fcfcfc;
        }
        .gateway-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            position: relative;
        }
        .split {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            transition: all 0.6s cubic-bezier(0.25, 1, 0.5, 1);
            position: relative;
            z-index: 1;
            text-decoration: none;
            overflow: hidden;
        }
        /* User Split Styling */
        .user-split {
            background: linear-gradient(135deg, #fff0f5 0%, #ffe4e1 100%);
            color: #d15c7a;
        }
        /* Admin Split Styling */
        .admin-split {
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f2ff 100%);
            color: #3b82f6;
        }
        .split::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            transition: width 0.6s ease, height 0.6s ease;
            z-index: -1;
            transform: translate(-50%, -50%);
        }
        .user-split::before {
            background-color: rgba(255, 182, 193, 0.3);
        }
        .admin-split::before {
            background-color: rgba(173, 216, 230, 0.4);
        }
        .split:hover::before {
            width: 250%;
            height: 250%;
        }
        .split:hover {
            flex: 1.15;
        }
        .logo-mark {
            font-size: 5rem;
            margin-bottom: 20px;
            display: inline-block;
            transition: transform 0.4s ease;
        }
        .split:hover .logo-mark {
            transform: scale(1.15) rotate(5deg);
        }
        .title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }
        .desc {
            font-size: 1.1rem;
            max-width: 380px;
            line-height: 1.6;
            margin-bottom: 35px;
            opacity: 0.85;
            color: #555;
        }
        .cta-btn {
            padding: 16px 36px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .user-btn {
            background-color: #d15c7a;
            color: #ffffff;
        }
        .user-btn:hover {
            background-color: #be4d69;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(209, 92, 122, 0.3);
        }
        .admin-btn {
            background-color: #3b82f6;
            color: #ffffff;
        }
        .admin-btn:hover {
            background-color: #2563eb;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
        }
     
        /* Mobile Layout */
        @media (max-width: 768px) {
            .gateway-container {
                flex-direction: column;
            }
            .split {
                padding: 30px;
            }
    
            .logo-mark {
                font-size: 3.5rem;
                margin-bottom: 10px;
            }
            .title {
                font-size: 2rem;
                margin-bottom: 10px;
            }
            .desc {
                font-size: 0.95rem;
                margin-bottom: 20px;
                max-width: 300px;
            }
            .cta-btn {
                padding: 12px 28px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="gateway-container">
        <a href="login.php" class="split user-split">
            <span class="logo-mark">
			<svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="currentColor" viewBox="0 0 24 24">
			  <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
			</svg>
			</span>
            <h1 class="title">User Portal</h1>
            <p class="desc">Adopt a pet, browse cute pet listings, check your application status.</p>
            <button class="cta-btn user-btn">Enter User Portal</button>
        </a>
        

        
        <a href="admin_login.php" class="split admin-split">
            <span class="logo-mark">
			<svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="none" viewBox="0 0 24 24">
				<path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4.5 17H4a1 1 0 0 1-1-1 3 3 0 0 1 3-3h1m0-3.05A2.5 2.5 0 1 1 9 5.5M19.5 17h.5a1 1 0 0 0 1-1 3 3 0 0 0-3-3h-1m0-3.05a2.5 2.5 0 1 0-2-4.45m.5 13.5h-7a1 1 0 0 1-1-1 3 3 0 0 1 3-3h3a3 3 0 0 1 3 3 1 1 0 0 1-1 1Zm-1-9.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
			</svg>
			</span>
            <h1 class="title">Admin Portal</h1>
            <p class="desc">Manage pet listings, review adoption requests, reply to feedback, and view dashboard analytics.</p>
            <button class="cta-btn admin-btn">Enter Admin Portal</button>
        </a>
    </div>
</body>
</html>
