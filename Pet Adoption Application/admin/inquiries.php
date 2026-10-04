<?php
require_once 'includes/admin_header.php';

$success = '';
$error = '';

// Handle Support Inquiry Reply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply_inquiry') {
    $inq_id = filter_input(INPUT_POST, 'inquiry_id', FILTER_VALIDATE_INT);
    $reply_text = trim(filter_input(INPUT_POST, 'reply_text', FILTER_SANITIZE_SPECIAL_CHARS));
    
    if (!$inq_id || empty($reply_text)) {
        $error = 'Reply text cannot be empty.';
    } else {
        try {
            // Update Inquiry
            $stmt = $pdo->prepare("UPDATE inquiries SET admin_reply = ?, replied_at = NOW() WHERE id = ?");
            $stmt->execute([$reply_text, $inq_id]);
            
            // Fetch inquiry details to match email to a user profile
            $stmt_inq = $pdo->prepare("SELECT email, message FROM inquiries WHERE id = ?");
            $stmt_inq->execute([$inq_id]);
            $inq = $stmt_inq->fetch();
            
            if ($inq) {
                // Find matching user profile by email
                $stmt_u = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt_u->execute([$inq['email']]);
                $user_id = $stmt_u->fetchColumn();
                
                if ($user_id) {
                    // Send notification to user profile feed
                    $notif_msg = 'Admin replied to your inquiry: "' . $reply_text . '"';
                    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'inquiry', ?, ?)");
                    $stmt_notif->execute([$user_id, $inq_id, $notif_msg]);
                }
            }
            
            $success = "Support reply submitted successfully!";
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch all inquiries
try {
    $stmt_i = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC");
    $inquiries = $stmt_i->fetchAll();
} catch (PDOException $e) {
    $inquiries = [];
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

<div class="app-review-list">
    <?php if (count($inquiries) === 0): ?>
        <div style="text-align: center; background-color:#ffffff; border: 1px solid var(--admin-border); padding: 50px 0; border-radius: var(--radius-lg); color: var(--admin-text-muted);">
            No customer support inquiries submitted yet.
        </div>
    <?php else: ?>
        <?php foreach ($inquiries as $inq): ?>
            <div class="app-review-card" style="grid-template-columns: 1fr;" id="inquiry-<?= $inq['id'] ?>">
                <div class="app-card-details">
                    <div class="app-card-meta-row">
                        <span class="app-id-badge" style="background-color: #ffedd5; color: #c2410c;">INQUIRY ID: inq_<?= $inq['id'] ?></span>
                        <span class="app-submit-date">Received <?= date('n/j/Y, g:i A', strtotime($inq['created_at'])) ?></span>
                        <span class="status-badge <?= $inq['admin_reply'] === null ? 'status-active' : 'role-admin' ?>">
                            <?= $inq['admin_reply'] === null ? 'Unread / Awaiting Reply' : 'Replied' ?>
                        </span>
                    </div>
                    
                    <h4 class="app-card-heading">
                        <?= htmlspecialchars($inq['name']) ?> <span>(<?= htmlspecialchars($inq['email']) ?>)</span>
                    </h4>
                    
                    <div class="app-remarks-box" style="background-color: #ffffff; border-left: 3px solid var(--admin-blue-medium);">
                        "<?= htmlspecialchars($inq['message']) ?>"
                    </div>
                    
                    <!-- Admin Reply display or form -->
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--admin-border);">
                        <?php if ($inq['admin_reply'] !== null): ?>
                            <span class="app-info-label">Support Response (Sent <?= date('n/j/Y, g:i A', strtotime($inq['replied_at'])) ?>)</span>
                            <div style="background-color: #f0fdf4; border: 1px solid rgba(16,185,129,0.2); padding: 15px; border-radius: var(--radius-sm); font-size: 0.95rem; color:#065f46;">
                                💬 "<?= htmlspecialchars($inq['admin_reply']) ?>"
                            </div>
                        <?php else: ?>
                            <form method="POST" action="inquiries.php#inquiry-<?= $inq['id'] ?>">
                                <input type="hidden" name="action" value="reply_inquiry">
                                <input type="hidden" name="inquiry_id" value="<?= $inq['id'] ?>">
                                
                                <div class="admin-form-group" style="margin-bottom: 12px;">
                                    <label for="reply-text-<?= $inq['id'] ?>" style="font-size:0.75rem;">Write Support Response</label>
                                    <textarea name="reply_text" id="reply-text-<?= $inq['id'] ?>" class="admin-form-control" rows="3" placeholder="Enter support resolution response to send to user dashboard..." required></textarea>
                                </div>
                                <button type="submit" class="admin-btn-primary" style="padding:10px 20px; font-size:0.85rem;">📨 Send Reply</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'includes/admin_footer.php';
?>
