<?php
require_once 'includes/admin_header.php';

$success = '';
$error = '';

// Handle Admin Profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
    $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
    
    if (empty($name) || empty($phone) || empty($address)) {
        $error = 'All fields are required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $admin_id]);
            
            // Update session values
            $_SESSION['admin_session']['name'] = $name;
            $admin_name = $name;
            
            $success = 'Administrative profile details updated successfully!';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch current details
try {
    $stmt_a = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_a->execute([$admin_id]);
    $admin = $stmt_a->fetch();
} catch (PDOException $e) {}
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
    <h3 class="panel-title">Administrative Settings</h3>
    
    <form method="POST" action="profile.php">
        <div class="admin-form-group">
            <label for="admin-name">Full Administrator Name *</label>
            <input type="text" name="name" id="admin-name" class="admin-form-control" value="<?= htmlspecialchars($admin['name']) ?>" required>
        </div>
        
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="admin-email-static">Official Email (Username)</label>
                <input type="email" id="admin-email-static" class="admin-form-control" value="<?= htmlspecialchars($admin['email']) ?>" style="opacity:0.6; cursor:not-allowed;" readonly disabled>
            </div>
            
            <div class="admin-form-group">
                <label for="admin-phone">Contact Phone Number *</label>
                <input type="text" name="phone" id="admin-phone" class="admin-form-control" value="<?= htmlspecialchars($admin['phone']) ?>" required>
            </div>
        </div>
        
        <div class="admin-form-group">
            <label for="admin-address">Office Address *</label>
            <textarea name="address" id="admin-address" class="admin-form-control" rows="3" required><?= htmlspecialchars($admin['address']) ?></textarea>
        </div>
        
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="admin-btn-primary">Save Profile Changes</button>
        </div>
    </form>
</div>
<script src="../js/validation.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        attachPhoneValidation('.form-card form', 'admin-phone');
        attachAddressValidation('.form-card form', 'admin-address');
    });
</script>
<?php
require_once 'includes/admin_footer.php';
?>
