<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS));
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill out all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $message]);
            $success = true;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Retrieve CMS Contact Settings
$contact = [
    'contact_address' => 'Pekan Batu 14 Hulu Langat, 43100 Hulu Langat, Selangor',
    'contact_phone' => '+ 012-919 2263',
    'contact_email' => 'support@petopia.com',
    'contact_facebook' => 'https://facebook.com/petopia',
    'contact_instagram' => 'https://instagram.com/petopia'
];

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM cms_content WHERE meta_key LIKE 'contact_%'");
        while ($row = $stmt->fetch()) {
            $contact[$row['meta_key']] = $row['meta_value'];
        }
    } catch (PDOException $e) { }
}

// Prepopulate user if logged in
$pre_name = '';
$pre_email = '';
if (is_logged_in()) {
    $pre_name = $_SESSION['user_session']['name'];
    $pre_email = $_SESSION['user_session']['email'];
}

require_once 'includes/header.php';
?>

<div class="container" style="padding: 40px 24px;">
    <div class="section-header">
        <h2>Customer Service & Support</h2>
        <p>Do you have inquiries, feedback, or need assistance? Submit a message below and we will reply directly to your notifications feed.</p>
    </div>

    <div class="contact-grid">
        <!-- Inquiry Form (Left Side) -->
        <div class="contact-card-box">
            <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 25px;">Submit an Inquiry</h3>
            
            <?php if ($success): ?>
                <div style="background-color: var(--color-success-bg); color: var(--color-success); border: 1px solid rgba(76,217,100,0.2); padding: 18px 24px; border-radius: var(--radius-md); margin-bottom: 25px; font-weight:600;">
                    ✅ Inquiry submitted successfully! Our support desk will review and post a reply in your dashboard notifications shortly.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background-color: var(--color-danger-bg); color: var(--color-danger); border: 1px solid rgba(255,59,48,0.2); padding: 18px 24px; border-radius: var(--radius-md); margin-bottom: 25px; font-weight:600;">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="services.php">
                <div class="form-group">
                    <label for="contact-name">Full Name *</label>
                    <input type="text" name="name" id="contact-name" class="form-control" value="<?= htmlspecialchars($pre_name) ?>" required>
                </div>
                <div class="form-group">
                    <label for="contact-email">Email Address *</label>
                    <input type="email" name="email" id="contact-email" class="form-control" value="<?= htmlspecialchars($pre_email) ?>" required>
                </div>
                <div class="form-group">
                    <label for="contact-message">Your Message *</label>
                    <textarea name="message" id="contact-message" class="form-control" rows="5" placeholder="Write details about your question, rescue inquiry, or feedback..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px; width: 100%;">Send Inquiry</button>
            </form>
        </div>

        <!-- Contact Information (Right Side) -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <div class="contact-card-box" style="background-color: var(--macaron-pink-light); border-color: var(--macaron-border);">
                <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 25px;">Contact Info</h3>
                
                <div class="info-widget-list">
                    <div class="info-item">
                        <div class="info-icon-circle">📍</div>
                        <div class="info-text">
                            <h4>Physical Address</h4>
                            <p><?= htmlspecialchars($contact['contact_address']) ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon-circle">📞</div>
                        <div class="info-text">
                            <h4>Phone Number</h4>
                            <p><?= htmlspecialchars($contact['contact_phone']) ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon-circle">✉️</div>
                        <div class="info-text">
                            <h4>Official Email</h4>
                            <p><a href="mailto:<?= htmlspecialchars($contact['contact_email']) ?>"><?= htmlspecialchars($contact['contact_email']) ?></a></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-card-box" style="text-align: center;">
                <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 20px;">Connect With Us</h3>
                <p style="color: var(--macaron-text-muted); margin-bottom: 20px;">Follow us on social media for rescue highlights and adoption stories!</p>
                <div style="display: flex; justify-content: center; gap: 15px;">
                    <a href="<?= htmlspecialchars($contact['contact_facebook']) ?>" target="_blank" class="btn btn-secondary" style="padding: 12px 24px; border-radius: 50px;">
                        📘 Facebook
                    </a>
                    <a href="<?= htmlspecialchars($contact['contact_instagram']) ?>" target="_blank" class="btn btn-secondary" style="padding: 12px 24px; border-radius: 50px;">
                        📸 Instagram
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/validation.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        attachEmailValidation('.contact-card-box form', 'contact-email');
    });
</script>

<?php
require_once 'includes/footer.php';
?>
