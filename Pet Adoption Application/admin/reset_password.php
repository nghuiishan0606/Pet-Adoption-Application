<?php
require_once 'includes/admin_header.php';

$success = '';
$error = '';

// Handle Admin Password Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        try {
            // Fetch current password hash
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$admin_id]);
            $hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $hash)) {
                $error = 'Incorrect current password.';
            } else {
                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_up = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_up->execute([$new_hash, $admin_id]);
                
                $success = 'Administrative account password updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<?php if ($success): ?>
    <div style="background-color: #dcfce7; color: #15803d; border: 1px solid rgba(21,128,61,0.2); padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 25px; font-weight: 600;">
        ✅ <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background-color: #fef2f2; color: #b91c1c; border: 1px solid rgba(185,28,28,0.2); padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 25px; font-weight: 600;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <h3 class="panel-title">Update Account Password</h3>
    
    <form method="POST" action="reset_password.php">
        <div class="admin-form-group">
            <label for="pwd-current">Current Password *</label>
            <input type="password" name="current_password" id="pwd-current" class="admin-form-control" placeholder="Enter current password" required>
        </div>
        
        <div class="admin-form-group">
            <label for="pwd-new">New Password *</label>
            <input type="password" name="new_password" id="pwd-new" class="admin-form-control" placeholder="Enter new password" required>
        </div>
        
        <div class="admin-form-group">
            <label for="pwd-confirm">Confirm New Password *</label>
            <input type="password" name="confirm_password" id="pwd-confirm" class="admin-form-control" placeholder="Re-enter new password" required>
        </div>
        
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="admin-btn-primary">Update Secure Password</button>
        </div>
    </form>
</div>

<?php
require_once 'includes/admin_footer.php';
?>
