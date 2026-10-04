<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Helper to clean image paths (remove leading relative path syntax)
if (!function_exists('clean_image_path')) {
    function clean_image_path($path) {
        $pos = strpos($path, 'uploads/');
        if ($pos !== false) {
            return substr($path, $pos);
        }
        return ltrim($path, './\\');
    }
}

// Enforce authentication
require_login();

function has_applied($pdo, $user_id, $pet_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND pet_id = ?");
        $stmt->execute([$user_id, $pet_id]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

$user_id = $_SESSION['user_session']['id'];
$success_msg = '';
$error_msg = '';
$active_tab = 'edit-profile';

// Handle Profile Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_profile') {
            $active_tab = 'edit-profile';
            $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
            $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
            $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
            
            if (empty($name) || empty($phone) || empty($address)) {
                $error_msg = 'Please fill out all fields.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
                    $stmt->execute([$name, $phone, $address, $user_id]);
                    $_SESSION['user_session']['name'] = $name;
                    $success_msg = 'Profile updated successfully!';
                } catch (PDOException $e) {
                    $error_msg = 'Database error: ' . $e->getMessage();
                }
            }
        }
        
        elseif ($action === 'reset_password') {
            $active_tab = 'account-settings';
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_msg = 'Please fill out all password fields.';
            } elseif ($new_password !== $confirm_password) {
                $error_msg = 'New passwords do not match.';
            } else {
                try {
                    // Fetch current password hash
                    $stmt_u = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt_u->execute([$user_id]);
                    $hash = $stmt_u->fetchColumn();
                    
                    if (!password_verify($current_password, $hash)) {
                        $error_msg = 'Incorrect current password.';
                    } else {
                        // Update password
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt_up = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt_up->execute([$new_hash, $user_id]);
                        $success_msg = 'Password reset successfully!';
                    }
                } catch (PDOException $e) {
                    $error_msg = 'Database error: ' . $e->getMessage();
                }
            }
        }
        
        elseif ($action === 'apply_adoption') {
            $active_tab = 'wishlist';
            $pet_id = filter_input(INPUT_POST, 'pet_id', FILTER_VALIDATE_INT);
            $app_name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
            $app_email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $app_phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
            $app_address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
            $pickup_datetime = filter_input(INPUT_POST, 'pickup_datetime', FILTER_SANITIZE_SPECIAL_CHARS);
            $has_experience = isset($_POST['has_experience']) ? 1 : 0;
            $remarks = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));
            
            if (!$pet_id || empty($app_name) || empty($app_email) || empty($app_phone) || empty($app_address) || empty($pickup_datetime)) {
                $error_msg = 'Please fill out all required fields.';
            } else {
                try {
                    $stmt_pet = $pdo->prepare("SELECT status FROM pets WHERE id = ?");
                    $stmt_pet->execute([$pet_id]);
                    $pet_status = $stmt_pet->fetchColumn();
                    
                    if ($pet_status !== 'Available') {
                        $error_msg = 'Sorry, this pet is already adopted or unavailable.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO applications (user_id, pet_id, name, email, phone, address, pickup_datetime, has_experience, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                        $stmt->execute([$user_id, $pet_id, $app_name, $app_email, $app_phone, $app_address, $pickup_datetime, $has_experience, $remarks]);
                        
                        $app_id = $pdo->lastInsertId();
                        $notif_msg = "Your adoption application for pet has been submitted. Status: Pending. Pickup date: " . date('m/d/Y, g:i A', strtotime($pickup_datetime));
                        $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'application', ?, ?)");
                        $stmt_notif->execute([$user_id, $app_id, $notif_msg]);
                        
                        $success_msg = 'Application submitted successfully! You can track its status under My Adoptions.';
                    }
                } catch (PDOException $e) {
                    $error_msg = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch User Info
try {
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch();
    
    // Format User ID as U-000X
    $formatted_user_id = sprintf("U-%04d", $user['id']);
    
    // Fetch Adoption Applications (Adoption Records)
    $stmt_app = $pdo->prepare("SELECT a.*, p.name AS pet_name, p.breed AS pet_breed, p.image_path AS pet_image FROM applications a JOIN pets p ON a.pet_id = p.id WHERE a.user_id = ? ORDER BY a.id DESC");
    $stmt_app->execute([$user_id]);
    $applications = $stmt_app->fetchAll();
    
    // Fetch Wishlist Items
    $stmt_wish = $pdo->prepare("SELECT w.id AS wishlist_id, p.* FROM wishlists w JOIN pets p ON w.pet_id = p.id WHERE w.user_id = ? ORDER BY w.id DESC");
    $stmt_wish->execute([$user_id]);
    $wishlist_pets = $stmt_wish->fetchAll();
    
    // Fetch Notifications (Live updates & admin replies)
    $stmt_notif = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC");
    $stmt_notif->execute([$user_id]);
    $notifications = $stmt_notif->fetchAll();
    
    // Mark notifications as read once viewed
    $stmt_read = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt_read->execute([$user_id]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="container" style="padding: 40px 24px;">
    
    <?php if ($success_msg): ?>
        <div style="background-color: var(--color-success-bg); color: var(--color-success); border: 1px solid rgba(76,217,100,0.2); padding: 18px 24px; border-radius: var(--radius-md); margin-bottom: 30px; font-weight:600;">
            ✅ <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div style="background-color: var(--color-danger-bg); color: var(--color-danger); border: 1px solid rgba(255,59,48,0.2); padding: 18px 24px; border-radius: var(--radius-md); margin-bottom: 30px; font-weight:600;">
            ⚠️ <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <div class="profile-wrapper">
        <!-- Sidebar Navigation Card (Left Column) -->
        <aside class="profile-sidebar">
            <div class="profile-card-avatar">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            
            <h3 class="profile-card-name"><?= htmlspecialchars($user['name']) ?></h3>
            <span class="profile-card-id">ID: <?= $formatted_user_id ?></span>
            <p class="profile-card-email"><?= htmlspecialchars($user['email']) ?></p>
            
            <ul class="profile-menu-list">
                <li class="profile-menu-item <?= $active_tab === 'edit-profile' ? 'active' : '' ?>" data-tab="edit-profile">
                    <a href="#edit-profile">📝 Edit Profile</a>
                </li>
                <li class="profile-menu-item <?= $active_tab === 'account-settings' ? 'active' : '' ?>" data-tab="account-settings">
                    <a href="#account-settings">🔒 Account Settings</a>
                </li>
                <li class="profile-menu-item <?= $active_tab === 'my-adoptions' ? 'active' : '' ?>" data-tab="my-adoptions">
                    <a href="#my-adoptions">🕒 My Adoptions</a>
                </li>
                <li class="profile-menu-item <?= $active_tab === 'wishlist' ? 'active' : '' ?>" data-tab="wishlist">
                    <a href="#wishlist">💖 Wishlist</a>
                </li>
                <li class="profile-menu-item <?= $active_tab === 'notifications' ? 'active' : '' ?>" data-tab="notifications">
                    <a href="#notifications">🔔 Notifications</a>
                </li>
                <li class="profile-menu-item logout-item">
                    <a href="logout.php">🚪 Log Out</a>
                </li>
            </ul>
        </aside>

        <!-- Dynamic Panels (Right Column) -->
        <main class="profile-content-area">
            
            <!-- Panel 1: Edit Profile -->
            <section id="tab-edit-profile" class="tab-panel" style="display: <?= $active_tab === 'edit-profile' ? 'block' : 'none' ?>;">
                <h3 class="panel-title">Edit Profile</h3>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="edit-name">Full Name *</label>
                        <input type="text" name="name" id="edit-name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-phone">Phone Number *</label>
                        <input type="text" name="phone" id="edit-phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-address">Home Address *</label>
                        <textarea name="address" id="edit-address" class="form-control" rows="4" required><?= htmlspecialchars($user['address']) ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </section>

            <!-- Panel 2: Account Settings (Reset Password) -->
            <section id="tab-account-settings" class="tab-panel" style="display: <?= $active_tab === 'account-settings' ? 'block' : 'none' ?>;">
                <h3 class="panel-title">Account Security</h3>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div class="form-group">
                        <label for="pwd-current">Current Password *</label>
                        <input type="password" name="current_password" id="pwd-current" class="form-control" placeholder="Enter your current password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pwd-new">New Password *</label>
                        <input type="password" name="new_password" id="pwd-new" class="form-control" placeholder="Enter new secure password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pwd-confirm">Confirm New Password *</label>
                        <input type="password" name="confirm_password" id="pwd-confirm" class="form-control" placeholder="Re-enter new password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            </section>

            <!-- Panel 3: My Adoptions (Matches Screenshot 5 layout) -->
            <section id="tab-my-adoptions" class="tab-panel" style="display: <?= $active_tab === 'my-adoptions' ? 'block' : 'none' ?>;">
                <h3 class="panel-title">My Adoptions</h3>
                <div class="record-card-list">
                    <?php if (count($applications) === 0): ?>
                        <div style="text-align: center; padding: 40px 0;">
                            <span style="font-size: 2.5rem;">🐾</span>
                            <h4 style="font-weight: 700; color: var(--macaron-text-muted); margin-top: 10px;">No Adoption Applications Yet</h4>
                            <p style="color: var(--macaron-text-muted); font-size: 0.95rem; margin-bottom: 20px;">Browse our shelter pets and submit your first request!</p>
                            <a href="adopt.php" class="btn btn-primary">Find a Pet</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($applications as $app): 
                            $status_badge_class = '';
                            $status_text = $app['status'];
                            if ($app['status'] === 'Pending') {
                                $status_badge_class = 'badge-pending';
                                $status_text = 'Pending Review';
                            } elseif ($app['status'] === 'Approved') {
                                $status_badge_class = 'badge-approved';
                            } elseif ($app['status'] === 'Rejected') {
                                $status_badge_class = 'badge-rejected';
                            }
                        ?>
                            <div class="record-card">
                                <div class="record-card-header">
                                    <div>
                                        <h4 class="record-card-title">PET NAME: <span><?= htmlspecialchars($app['pet_name']) ?></span></h4>
                                        <span class="record-card-date">Submitted on <?= date('m/d/Y', strtotime($app['created_at'])) ?></span>
                                    </div>
                                    <span class="badge <?= $status_badge_class ?>" style="position: absolute; right: 24px; top: 24px; font-size: 0.9rem; padding: 8px 16px;">
                                        <?= htmlspecialchars($status_text) ?>
                                    </span>
                                </div>
                                
                                <div class="record-card-grid">
                                    <div class="record-meta-item">
                                        <span class="record-meta-label">Pickup Appointment</span>
                                        <span class="record-meta-val" style="background-color: #ffeef2; color: var(--macaron-pink-primary); padding: 4px 10px; border-radius: 50px; font-size: 0.85rem; display: inline-block;">
                                            📅 <?= date('n/j/Y, g:i A', strtotime($app['pickup_datetime'])) ?>
                                        </span>
                                    </div>
                                    <div class="record-meta-item">
                                        <span class="record-meta-label">Pet Experience</span>
                                        <span class="record-meta-val"><?= $app['has_experience'] ? 'Has Prior Experience' : 'No Prior Experience' ?></span>
                                    </div>
                                </div>
                                
                                <div class="record-remarks-box">
                                    "<?= !empty($app['remarks']) ? htmlspecialchars($app['remarks']) : 'No remarks provided.' ?>"
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Panel 4: Wishlist -->
            <section id="tab-wishlist" class="tab-panel" style="display: <?= $active_tab === 'wishlist' ? 'block' : 'none' ?>;">
                <h3 class="panel-title">My Wishlist</h3>
                <div class="pets-grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px;">
                    <?php if (count($wishlist_pets) === 0): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px 0;">
                            <span style="font-size: 2.5rem;">🤍</span>
                            <h4 style="font-weight: 700; color: var(--macaron-text-muted); margin-top: 10px;">Wishlist is Empty</h4>
                            <p style="color: var(--macaron-text-muted); font-size: 0.95rem; margin-bottom: 20px;">Save your favorite pets to track them here!</p>
                            <a href="adopt.php" class="btn btn-primary">Browse Pets</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($wishlist_pets as $pet): 
                            $user_has_applied = has_applied($pdo, $user_id, $pet['id']);
                        ?>
                            <div class="pet-card">
                                <div class="pet-image-container" style="height: 180px;">
                                    <img src="<?= htmlspecialchars(clean_image_path($pet['image_path'])) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($pet['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=400&q=80'">
                                    <button class="wishlist-heart-btn active" data-pet-id="<?= $pet['id'] ?>">❤️</button>
                                    <span style="position: absolute; bottom: 8px; left: 8px; background-color: var(--macaron-pink-light); color: var(--macaron-pink-primary); font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; border: 1px solid var(--macaron-border);"><?= htmlspecialchars($pet['type']) ?></span>
                                </div>
                                <div class="pet-info" style="padding: 16px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
                                        <span class="pet-name" style="font-size:1.15rem; font-weight:800; color:var(--macaron-text-main);"><?= htmlspecialchars($pet['name']) ?></span>
                                        <?php if ($user_has_applied): ?>
                                            <span class="badge badge-pending" style="font-size:0.7rem; padding: 2px 8px; font-weight:700;">Applied</span>
                                        <?php else: ?>
                                            <span class="badge badge-approved" style="font-size:0.7rem; padding: 2px 8px; font-weight:700;"><?= htmlspecialchars($pet['status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.85rem; color:var(--macaron-text-muted); margin-bottom:15px; line-height: 1.4;">
                                        <p style="font-weight:700; color:var(--macaron-text-main); margin-bottom: 1px;"><?= htmlspecialchars($pet['breed']) ?></p>
                                        <p>Age: <?= htmlspecialchars($pet['age']) ?></p>
                                    </div>
                                    <div class="pet-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                        <a href="adopt.php?learn_more_id=<?= $pet['id'] ?>" class="btn btn-secondary" style="width: 100%; text-align: center; text-decoration: none; padding: 8px; font-size: 0.8rem;">Learn More</a>
                                        
                                        <?php if ($pet['status'] !== 'Available'): ?>
                                            <button type="button" class="btn btn-secondary" style="width: 100%; opacity:0.5; cursor:not-allowed; padding: 8px; font-size: 0.8rem;" disabled>Adopted</button>
                                        <?php elseif ($user_has_applied): ?>
                                            <button type="button" class="btn btn-secondary" style="width: 100%; opacity:0.5; cursor:not-allowed; padding: 8px; font-size: 0.8rem;" disabled>Under Review</button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-primary" onclick="openApplyModal(<?= $pet['id'] ?>, '<?= htmlspecialchars($pet['name'], ENT_QUOTES) ?>')" style="width: 100%; padding: 8px; font-size: 0.8rem;">Apply</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Panel 5: Notifications (Support Tickets & Applications) -->
            <section id="tab-notifications" class="tab-panel" style="display: <?= $active_tab === 'notifications' ? 'block' : 'none' ?>;">
                <h3 class="panel-title">Notifications Feed</h3>
                <div class="notifications-list">
                    <?php if (count($notifications) === 0): ?>
                        <div style="text-align: center; padding: 40px 0;">
                            <span style="font-size: 2.5rem;">🔔</span>
                            <h4 style="font-weight: 700; color: var(--macaron-text-muted); margin-top: 10px;">No Notifications</h4>
                            <p style="color: var(--macaron-text-muted); font-size: 0.95rem;">You are up to date! System alerts will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): 
                            $icon = $notif['type'] === 'application' ? '🐾' : '✉️';
                            $target_tab = $notif['type'] === 'application' ? 'my-adoptions' : '';
                        ?>
                            <div class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>" 
                                 onclick="handleNotificationClick('<?= $notif['type'] ?>', '<?= $notif['related_id'] ?>')">
                                <?php if (!$notif['is_read']): ?>
                                    <span class="notification-unread-dot"></span>
                                <?php endif; ?>
                                <div class="notification-icon-wrap"><?= $icon ?></div>
                                <div class="notification-body-text">
                                    <p class="notification-msg"><?= htmlspecialchars($notif['message']) ?></p>
                                    <span class="notification-time"><?= date('m/d/Y, g:i A', strtotime($notif['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </div>
</div>

<script>
    // Handle notification clicks
    function handleNotificationClick(type, id) {
        if (type === 'application') {
            // Activate adoption records tab
            const adoptionsTab = document.querySelector('.profile-menu-item[data-tab="my-adoptions"]');
            if (adoptionsTab) {
                adoptionsTab.click();
            }
        } else if (type === 'inquiry') {
            // For inquiry replies, redirect user to the inquiry detail or scroll down to notification list
            // Simply display alert details for quick accessibility if they want to view full text
            alert("Support Desk Update: Check your message notifications for the full admin response.");
        }
    }
</script>

<!-- Adoption Form Modal -->
<div id="apply-modal" class="modal-overlay" onclick="if(event.target === this) closeModal('apply-modal')">
    <div class="modal-box" style="max-width: 580px;">
        <button type="button" class="modal-close-x" onclick="closeModal('apply-modal')">&times;</button>
        <div class="modal-body">
            <div class="modal-header">
                <h3 class="modal-title">Adopt <span id="apply-pet-name-title">Pet</span></h3>
                <p style="color: var(--macaron-text-muted); font-size: 0.9rem;">Fill out this application form to book a pick-up appointment.</p>
            </div>
            
            <?php
            try {
                $stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt_u->execute([$user_id]);
                $user_details = $stmt_u->fetch();
            } catch (PDOException $e) {
                $user_details = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
            }
            ?>
            <form id="apply-adoption-form" method="POST" action="profile.php">
                <input type="hidden" name="action" value="apply_adoption">
                <input type="hidden" name="pet_id" id="apply-pet-id">
                
                <div class="form-group">
                    <label for="apply-name">Full Name *</label>
                    <input type="text" name="name" id="apply-name" class="form-control" value="<?= htmlspecialchars($user_details['name']) ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="apply-email">Email Address *</label>
                        <input type="email" name="email" id="apply-email" class="form-control" value="<?= htmlspecialchars($user_details['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="apply-phone">Phone Number *</label>
                        <input type="text" name="phone" id="apply-phone" class="form-control" value="<?= htmlspecialchars($user_details['phone']) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="apply-address">Home Address *</label>
                    <textarea name="address" id="apply-address" class="form-control" rows="2" required><?= htmlspecialchars($user_details['address']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="apply-datetime">Appointment Pick-Up Date & Time *</label>
                    <input type="datetime-local" name="pickup_datetime" id="apply-datetime" class="form-control" required>
                </div>
                
                <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                    <input type="checkbox" name="has_experience" id="apply-experience" style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="apply-experience" style="margin-bottom: 0; cursor: pointer; font-weight: 500;">I have prior experience owning pets</label>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label for="apply-remarks">Remarks (Optional)</label>
                    <textarea name="remarks" id="apply-remarks" class="form-control" rows="2" placeholder="Tell us more about your living environment or questions..."></textarea>
                </div>
                
                <div class="modal-footer" style="margin-top: 20px; padding-top: 15px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('apply-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/validation.js"></script>
<script>
    function openApplyModal(petId, petName) {
        document.getElementById('apply-pet-name-title').innerText = petName;
        document.getElementById('apply-pet-id').value = petId;
        
        // Set minimum pickup date to today
        const datetimeInput = document.getElementById('apply-datetime');
        if (datetimeInput) {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            datetimeInput.min = `${yyyy}-${mm}-${dd}T${hh}:${min}`;
        }
        
        openModal('apply-modal');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Edit Profile form
        attachPhoneValidation('#tab-edit-profile form', 'edit-phone');
        attachAddressValidation('#tab-edit-profile form', 'edit-address');

        // Apply Adoption modal form
        attachEmailValidation('#apply-adoption-form', 'apply-email');
        attachPhoneValidation('#apply-adoption-form', 'apply-phone');
        attachAddressValidation('#apply-adoption-form', 'apply-address');
    });
</script>

<?php
require_once 'includes/footer.php';
?>
