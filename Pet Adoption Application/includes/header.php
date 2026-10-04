<?php
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';

// Fetch user info and notification count if logged in
$unread_count = 0;
$user_initial = '';
$user_fullname = '';

if (is_logged_in()) {
    $user_id = $_SESSION['user_session']['id'];
    $user_fullname = $_SESSION['user_session']['name'];
    $user_initial = strtoupper(substr($user_fullname, 0, 1));
    
    try {
        // Fetch unread notifications count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $unread_count = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Fail silently
    }
}

// Helper to determine active class
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petopia - Premium Pet Adoption Center</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js"></script>
    <script src="js/emoji-replacer.js"></script>
</head>
<body>
    <header class="navbar-header">
        <div class="container navbar-container">
            <a href="home.php" class="brand-logo">
                <span class="logo-icon">🐾</span>
                <span>Petopia</span>
            </a>
            
            <button class="hamburger-toggle" aria-label="Toggle Navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <nav>
                <ul class="nav-links">
                    <li class="<?= $current_page == 'home.php' ? 'active' : '' ?>"><a href="home.php">Home</a></li>
                    <li class="<?= $current_page == 'about.php' ? 'active' : '' ?>"><a href="about.php">About Us</a></li>
                    <li class="<?= $current_page == 'adopt.php' ? 'active' : '' ?>"><a href="adopt.php">Adopt</a></li>
                    <li class="<?= $current_page == 'blogs.php' ? 'active' : '' ?>"><a href="blogs.php">Blogs</a></li>
                    <?php if (is_logged_in()): ?>
                        <li class="<?= $current_page == 'wishlist.php' ? 'active' : '' ?>"><a href="wishlist.php">Wishlist</a></li>
                        <li class="<?= $current_page == 'profile.php' ? 'active' : '' ?>"><a href="profile.php">Profile</a></li>
                    <?php endif; ?>
                    <li class="<?= $current_page == 'services.php' ? 'active' : '' ?>"><a href="services.php">Services</a></li>
                </ul>
            </nav>
            
            <div class="navbar-controls">
                <?php if (is_logged_in()): ?>
                    <div class="notification-wrapper">
                        <button type="button" id="notificationMenuBtn" class="notification-trigger">
                            🔔<span class="notification-badge" style="<?= $unread_count === 0 ? 'display:none;' : '' ?>"><?= $unread_count ?></span>
                        </button>
                        
                        <div id="notificationPopoutBox" class="notification-popout hidden-window">
                            <div class="popout-header">
                                <h4>Recent Alerts</h4>
                            </div>
                            <div class="popout-body">
                                <?php
                                try {
                                    $stmt_notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5");
                                    $stmt_notifs->execute([$user_id]);
                                    $dropdown_notifs = $stmt_notifs->fetchAll();
                                    
                                    if (count($dropdown_notifs) === 0):
                                ?>
                                        <div class="popout-item">
                                            <p>No new notifications.</p>
                                        </div>
                                <?php
                                    else:
                                        foreach ($dropdown_notifs as $dn):
                                            $dn_class = !$dn['is_read'] ? 'unread-alert' : '';
                                ?>
                                            <div class="popout-item <?= $dn_class ?>" onclick="location.href='profile.php#notifications'" style="cursor:pointer;">
                                                <p><?= htmlspecialchars($dn['message']) ?></p>
                                                <span class="popout-time"><?= date('n/j/Y, g:i A', strtotime($dn['created_at'])) ?></span>
                                            </div>
                                <?php
                                        endforeach;
                                    endif;
                                } catch (PDOException $e) {
                                    echo '<div class="popout-item"><p>Error loading alerts.</p></div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <a href="profile.php" class="profile-avatar-btn" title="Go to Profile">
                        <div class="avatar-circle"><?= htmlspecialchars($user_initial) ?></div>
                        <span style="font-size: 0.95rem; font-weight:600; color:var(--macaron-text-main)"><?= htmlspecialchars($user_fullname) ?></span>
                    </a>
                    <a href="logout.php" class="logout-nav-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary" style="padding: 10px 20px; font-size: 0.95rem;">Login / Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
