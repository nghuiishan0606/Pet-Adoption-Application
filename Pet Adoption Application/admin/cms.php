<?php
require_once 'includes/admin_header.php';

$success = '';
$error = '';

// Handle CMS form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cms_updates = [
        'homepage_hero_title' => $_POST['homepage_hero_title'],
        'homepage_hero_desc' => $_POST['homepage_hero_desc'],
        'about_mission' => $_POST['about_mission'],
        'about_history' => $_POST['about_history'],
        'about_values' => $_POST['about_values'],
        'contact_address' => $_POST['contact_address'],
        'contact_phone' => $_POST['contact_phone'],
        'contact_email' => $_POST['contact_email'],
        'contact_facebook' => $_POST['contact_facebook'],
        'contact_instagram' => $_POST['contact_instagram'],
    ];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO cms_content (meta_key, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = ?");
        foreach ($cms_updates as $key => $val) {
            $stmt->execute([$key, trim($val), trim($val)]);
        }
        
        $pdo->commit();
        $success = 'Page configurations and CMS content updated successfully!';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch all current CMS settings
$cms = [
    'homepage_hero_title' => '',
    'homepage_hero_desc' => '',
    'about_mission' => '',
    'about_history' => '',
    'about_values' => '',
    'contact_address' => '',
    'contact_phone' => '',
    'contact_email' => '',
    'contact_facebook' => '',
    'contact_instagram' => '',
];

try {
    $stmt = $pdo->query("SELECT * FROM cms_content");
    while ($row = $stmt->fetch()) {
        $cms[$row['meta_key']] = $row['meta_value'];
    }
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

<form method="POST" action="cms.php">
    
    <!-- Homepage Settings -->
    <div class="form-card">
        <h3 class="panel-title">Homepage CMS Settings</h3>
        
        <div class="admin-form-group">
            <label for="cms-hero-title">Title Text *</label>
            <input type="text" name="homepage_hero_title" id="cms-hero-title" class="admin-form-control" value="<?= htmlspecialchars($cms['homepage_hero_title']) ?>" required>
        </div>
        
        <div class="admin-form-group">
            <label for="cms-hero-desc">Description Paragraph *</label>
            <textarea name="homepage_hero_desc" id="cms-hero-desc" class="admin-form-control" rows="4" required><?= htmlspecialchars($cms['homepage_hero_desc']) ?></textarea>
        </div>
    </div>

    <!-- About Us Settings -->
    <div class="form-card">
        <h3 class="panel-title">About Us Page CMS</h3>
        
        <div class="admin-form-group">
            <label for="cms-about-mission">Shelter Mission Statement *</label>
            <textarea name="about_mission" id="cms-about-mission" class="admin-form-control" rows="3" required><?= htmlspecialchars($cms['about_mission']) ?></textarea>
        </div>
        
        <div class="admin-form-group">
            <label for="cms-about-history">Center History Narrative *</label>
            <textarea name="about_history" id="cms-about-history" class="admin-form-control" rows="5" required><?= htmlspecialchars($cms['about_history']) ?></textarea>
        </div>
        
        <div class="admin-form-group">
            <label for="cms-about-values">Core Values * (Comma separated)</label>
            <input type="text" name="about_values" id="cms-about-values" class="admin-form-control" value="<?= htmlspecialchars($cms['about_values']) ?>" placeholder="e.g. Compassion, Integrity, Respect" required>
        </div>
    </div>

    <!-- Contact & Social Settings -->
    <div class="form-card">
        <h3 class="panel-title">Contact & Social Channels CMS</h3>
        
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="cms-phone">Contact Phone Number *</label>
                <input type="text" name="contact_phone" id="cms-phone" class="admin-form-control" value="<?= htmlspecialchars($cms['contact_phone']) ?>" required>
            </div>
            
            <div class="admin-form-group">
                <label for="cms-email">Official Support Email *</label>
                <input type="email" name="contact_email" id="cms-email" class="admin-form-control" value="<?= htmlspecialchars($cms['contact_email']) ?>" required>
            </div>
        </div>
        
        <div class="admin-form-group">
            <label for="cms-address">Physical Office Address *</label>
            <textarea name="contact_address" id="cms-address" class="admin-form-control" rows="2" required><?= htmlspecialchars($cms['contact_address']) ?></textarea>
        </div>
        
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="cms-facebook">Facebook Page URL *</label>
                <input type="url" name="contact_facebook" id="cms-facebook" class="admin-form-control" value="<?= htmlspecialchars($cms['contact_facebook']) ?>" required>
            </div>
            
            <div class="admin-form-group">
                <label for="cms-instagram">Instagram Profile URL *</label>
                <input type="url" name="contact_instagram" id="cms-instagram" class="admin-form-control" value="<?= htmlspecialchars($cms['contact_instagram']) ?>" required>
            </div>
        </div>
    </div>
    
    <div style="margin-bottom: 40px; display: flex; justify-content: flex-end;">
        <button type="submit" class="admin-btn-primary" style="padding:15px 40px;">💾 Save Configurations</button>
    </div>
</form>

<?php
require_once 'includes/admin_footer.php';
?>
