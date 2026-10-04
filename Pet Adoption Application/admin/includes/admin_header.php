<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';

// Enforce admin permission check
require_admin();

$admin_id = $_SESSION['admin_session']['id'];
$admin_name = $_SESSION['admin_session']['name'];
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$admin_email = $_SESSION['admin_session']['email'];

// Get counts for notification badge
$pending_apps = 0;
$unread_inqs = 0;
try {
    $pending_apps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")->fetchColumn();
    $unread_inqs = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE admin_reply IS NULL")->fetchColumn();
} catch (PDOException $e) {}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petopia Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Profile Dropdown Styling */
        .admin-dropdown-menu {
            position: absolute;
            top: 55px;
            right: 0;
            background-color: #ffffff;
            border: 1px solid var(--admin-border);
            border-radius: var(--radius-sm);
            width: 220px;
            box-shadow: var(--shadow-md);
            list-style: none;
            display: none;
            flex-direction: column;
            z-index: 500;
        }
        .admin-dropdown-menu.active {
            display: flex;
        }
        .admin-dropdown-item a {
            display: block;
            padding: 12px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--admin-text-main);
            transition: var(--transition-smooth);
        }
        .admin-dropdown-item a:hover {
            background-color: var(--admin-blue-light);
            color: var(--admin-blue-primary);
        }
    </style>
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js"></script>
    <script src="../js/emoji-replacer.js"></script>
</head>
<body>

    <!-- Admin Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <span class="logo-icon">🐾</span>
                <span>Admin Panel</span>
            </a>
            <button type="button" class="sidebar-toggle-btn" title="Toggle Sidebar">◀</button>
        </div>
        
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php">
                    <span class="menu-icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?= $current_page == 'pets.php' ? 'active' : '' ?>">
                <a href="pets.php">
                    <span class="menu-icon">🐾</span>
                    <span>Pet Profiles</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?= $current_page == 'users.php' ? 'active' : '' ?>">
                <a href="users.php">
                    <span class="menu-icon">👥</span>
                    <span>User Management</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?= $current_page == 'applications.php' ? 'active' : '' ?>">
                <a href="applications.php">
                    <span class="menu-icon">📄</span>
                    <span>Applications</span>
                    <?php if ($pending_apps > 0): ?>
                        <span style="background-color:#f472b6; color:#ffffff; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:50px; margin-left:auto;"><?= $pending_apps ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-menu-item <?= $current_page == 'blogs.php' ? 'active' : '' ?>">
                <a href="blogs.php">
                    <span class="menu-icon">📰</span>
                    <span>Blogs & News</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?= $current_page == 'cms.php' ? 'active' : '' ?>">
                <a href="cms.php">
                    <span class="menu-icon">⚙️</span>
                    <span>Page Content</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?= $current_page == 'inquiries.php' ? 'active' : '' ?>">
                <a href="inquiries.php">
                    <span class="menu-icon">💬</span>
                    <span>Service & Inquiries</span>
                    <?php if ($unread_inqs > 0): ?>
                        <span style="background-color:#0284c7; color:#ffffff; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:50px; margin-left:auto;"><?= $unread_inqs ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-menu-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <a href="profile.php">
                    <span class="menu-icon">👤</span>
                    <span>Profile Settings</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?= $current_page == 'reset_password.php' ? 'active' : '' ?>">
                <a href="reset_password.php">
                    <span class="menu-icon">🔒</span>
                    <span>Reset Password</span>
                </a>
            </li>
            
            <li class="sidebar-menu-item logout-item">
                <a href="../logout.php" onclick="return confirmAction('Are you sure you want to log out of the Admin panel?')">
                    <span class="menu-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="admin-main-wrapper">
        
        <!-- Top Navbar Control Bar -->
        <header class="admin-main-header">
            <div class="admin-page-title">
                <?php
                $title_text = 'Overview Dashboard';
                $sub_text = 'Quick metrics, statistics, and pending alerts for Petopia.';
                if ($current_page === 'pets.php') {
                    $title_text = 'Pet Profiles Management';
                    $sub_text = 'Create, update, delete, or modify rescue pet profiles.';
                } elseif ($current_page === 'users.php') {
                    $title_text = 'User Management';
                    $sub_text = 'View details, edit roles, toggle block status, or delete accounts.';
                } elseif ($current_page === 'applications.php') {
                    $title_text = 'Adoption Applications Review';
                    $sub_text = 'Approve or reject submissions from prospective adopters.';
                } elseif ($current_page === 'blogs.php') {
                    $title_text = 'Blog & News Publisher';
                    $sub_text = 'Manage published news feed articles, categories, and typography styling.';
                } elseif ($current_page === 'cms.php') {
                    $title_text = 'Content Management Desk';
                    $sub_text = 'Edit homepage content, mission/history records, contact numbers, and social links.';
                } elseif ($current_page === 'inquiries.php') {
                    $title_text = 'Service Inbox & Support Desk';
                    $sub_text = 'Review incoming user inquiries and submit replies directly to user feeds.';
                } elseif ($current_page === 'profile.php') {
                    $title_text = 'Admin Profile Settings';
                    $sub_text = 'Modify your name, phone number, address, and credentials.';
                } elseif ($current_page === 'reset_password.php') {
                    $title_text = 'Password Credentials Update';
                    $sub_text = 'Manage admin account security settings.';
                }
                ?>
                <h2><?= $title_text ?></h2>
                <p><?= $sub_text ?></p>
            </div>
            
            <div class="header-controls" style="display: flex; gap: 15px; align-items: center;">
                <!-- Notification Inquiries Dropdown -->
                <div class="notification-wrapper">
                    <button type="button" id="notificationMenuBtn" class="notification-trigger">
                        🔔<span class="notification-badge" style="<?= $unread_inqs === 0 ? 'display:none;' : '' ?>"><?= $unread_inqs ?></span>
                    </button>
                    
                    <div id="notificationPopoutBox" class="notification-popout hidden-window">
                        <div class="popout-header">
                            <h4>Recent Alerts</h4>
                        </div>
                        <div class="popout-body">
                            <?php
                            try {
                                $stmt_inqs_list = $pdo->query("SELECT id, name, message, created_at FROM inquiries WHERE admin_reply IS NULL ORDER BY id DESC LIMIT 5");
                                $unread_inqs_list = $stmt_inqs_list->fetchAll();
                                
                                if (count($unread_inqs_list) === 0):
                            ?>
                                    <div class="popout-item">
                                        <p>No new support inquiries.</p>
                                    </div>
                            <?php
                                else:
                                    foreach ($unread_inqs_list as $ui):
                            ?>
                                        <div class="popout-item unread-alert" onclick="location.href='inquiries.php#inquiry-<?= $ui['id'] ?>'" style="cursor:pointer;">
                                            <p>Inquiry from <strong><?= htmlspecialchars($ui['name']) ?></strong>: "<?= htmlspecialchars($ui['message']) ?>"</p>
                                            <span class="popout-time"><?= date('n/j/Y, g:i A', strtotime($ui['created_at'])) ?></span>
                                        </div>
                            <?php
                                    endforeach;
                                endif;
                            } catch (PDOException $e) {
                                echo '<div class="popout-item"><p>Error loading inquiries.</p></div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Avatar Profile Dropdown -->
                <div style="position: relative; display: inline-block;">
                    <div id="admin-avatar-trigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 5px 10px; border-radius: var(--radius-sm); transition: var(--transition-smooth);">
                        <div class="admin-avatar-circle"><?= htmlspecialchars($admin_initial) ?></div>
                        <span style="font-size:0.9rem; color:var(--admin-text-main); font-weight:700;">ADMIN</span>
                        <span style="font-size:0.85rem; font-weight:800; color:var(--admin-blue-primary);"><?= htmlspecialchars($admin_name) ?> ▾</span>
                    </div>
                    
                    <ul class="admin-dropdown-menu" id="admin-avatar-dropdown" style="top: 45px; right: 0;">
                        <li class="admin-dropdown-item"><a href="profile.php">👤 Profile Settings</a></li>
                        <li class="admin-dropdown-item"><a href="reset_password.php">🔒 Reset Password</a></li>
                        <li class="admin-dropdown-item" style="border-top:1px solid var(--admin-border);"><a href="../logout.php" style="color:#ef4444;">🚪 Log Out</a></li>
                    </ul>
                </div>
            </div>
        </header>
