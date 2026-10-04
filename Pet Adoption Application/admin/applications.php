<?php
require_once 'includes/admin_header.php';

$success = '';
$error = '';

// Handle Application Approval / Rejection
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $app_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($app_id) {
        try {
            // Fetch application details
            $stmt_app = $pdo->prepare("SELECT a.*, p.name AS pet_name, p.id AS pet_id FROM applications a JOIN pets p ON a.pet_id = p.id WHERE a.id = ?");
            $stmt_app->execute([$app_id]);
            $application = $stmt_app->fetch();
            
            if ($application) {
                if ($action === 'approve') {
                    // Update application status
                    $stmt_up = $pdo->prepare("UPDATE applications SET status = 'Approved' WHERE id = ?");
                    $stmt_up->execute([$app_id]);
                    
                    // Update pet status to Adopted
                    $stmt_pet = $pdo->prepare("UPDATE pets SET status = 'Adopted' WHERE id = ?");
                    $stmt_pet->execute([$application['pet_id']]);
                    
                    // Generate User Notification
                    $notif_msg = "Your adoption application for " . $application['pet_name'] . " has been Approved! Pickup is scheduled for " . date('m/d/Y, g:i A', strtotime($application['pickup_datetime'])) . ".";
                    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'application', ?, ?)");
                    $stmt_notif->execute([$application['user_id'], $app_id, $notif_msg]);
                    
                    $success = "Application app_" . $app_id . " approved successfully. Pet is now marked as Adopted.";
                }
                
                elseif ($action === 'reject') {
                    // Update application status
                    $stmt_up = $pdo->prepare("UPDATE applications SET status = 'Rejected' WHERE id = ?");
                    $stmt_up->execute([$app_id]);
                    
                    // Revert pet status to Available just in case it was Adopted
                    $stmt_pet = $pdo->prepare("UPDATE pets SET status = 'Available' WHERE id = ?");
                    $stmt_pet->execute([$application['pet_id']]);
                    
                    // Generate User Notification
                    $notif_msg = "Your adoption application for " . $application['pet_name'] . " has been Rejected. Please contact our services desk for more details.";
                    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'application', ?, ?)");
                    $stmt_notif->execute([$application['user_id'], $app_id, $notif_msg]);
                    
                    $success = "Application app_" . $app_id . " rejected. The user has been notified.";
                }
            } else {
                $error = 'Application record not found.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch all applications
try {
    $stmt_a = $pdo->query("SELECT a.*, p.name AS pet_name, p.breed AS pet_breed FROM applications a JOIN pets p ON a.pet_id = p.id ORDER BY a.id DESC");
    $applications = $stmt_a->fetchAll();
} catch (PDOException $e) {
    $applications = [];
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
    <?php if (count($applications) === 0): ?>
        <div style="text-align: center; background-color: #ffffff; border: 1px solid var(--admin-border); padding: 50px 0; border-radius: var(--radius-lg); color: var(--admin-text-muted);">
            No adoption applications submitted yet.
        </div>
    <?php else: ?>
        <?php foreach ($applications as $app): 
            $status_badge_class = '';
            $status_text = $app['status'];
            if ($app['status'] === 'Pending') {
                $status_badge_class = 'badge-pending';
                $status_text = 'Pending';
            } elseif ($app['status'] === 'Approved') {
                $status_badge_class = 'role-admin'; // green
            } elseif ($app['status'] === 'Rejected') {
                $status_badge_class = 'status-blocked'; // grey
            }
        ?>
            <div class="app-review-card" id="app-card-<?= $app['id'] ?>">
                <div class="app-card-details">
                    <div class="app-card-meta-row">
                        <span class="app-id-badge">APP ID: app_<?= $app['id'] ?></span>
                        <span class="app-submit-date">Submitted <?= date('n/j/Y', strtotime($app['created_at'])) ?></span>
                        <span class="status-badge <?= $status_badge_class ?>"><?= $status_text ?></span>
                    </div>
                    
                    <h4 class="app-card-heading">
                        <?= htmlspecialchars($app['name']) ?> <span>wants to adopt</span> <?= htmlspecialchars($app['pet_name']) ?>
                    </h4>
                    
                    <div class="app-info-grid">
                        <div class="app-info-item">
                            <span class="app-info-label">Email Address</span>
                            <span class="app-info-val"><?= htmlspecialchars($app['email']) ?></span>
                        </div>
                        <div class="app-info-item">
                            <span class="app-info-label">Phone Number</span>
                            <span class="app-info-val"><?= htmlspecialchars($app['phone']) ?></span>
                        </div>
                        <div class="app-info-item" style="grid-column: span 2;">
                            <span class="app-info-label">Address</span>
                            <span class="app-info-val"><?= htmlspecialchars($app['address']) ?></span>
                        </div>
                    </div>
                    
                    <div class="app-info-grid" style="margin-top: 5px;">
                        <div class="app-info-item" style="grid-column: span 2;">
                            <span class="app-info-label">Pickup Appointment</span>
                            <span class="app-info-val val-datetime">
                                📅 <?= date('n/j/Y, g:i A', strtotime($app['pickup_datetime'])) ?>
                            </span>
                        </div>
                        <div class="app-info-item" style="grid-column: span 2;">
                            <span class="app-info-label">Experience Owning Pets</span>
                            <span class="app-info-val"><?= $app['has_experience'] ? 'Has Prior Experience' : 'No Prior Experience' ?></span>
                        </div>
                    </div>
                    
                    <div class="app-remarks-box">
                        "<?= !empty($app['remarks']) ? htmlspecialchars($app['remarks']) : 'No comments provided by applicant.' ?>"
                    </div>
                </div>
                
                <!-- Action Controls (Right Column) -->
                <div class="app-card-actions">
                    <?php if ($app['status'] === 'Pending'): ?>
                        <a href="applications.php?action=approve&id=<?= $app['id'] ?>#app-card-<?= $app['id'] ?>" class="app-btn-approve" style="text-align: center; text-decoration: none;">
                            ✓ Approve
                        </a>
                        <a href="applications.php?action=reject&id=<?= $app['id'] ?>#app-card-<?= $app['id'] ?>" class="app-btn-reject" style="text-align: center; text-decoration: none;" onclick="return confirmAction('Are you sure you want to reject this adoption application?')">
                            &times; Reject
                        </a>
                    <?php elseif ($app['status'] === 'Approved'): ?>
                        <div style="text-align: center; font-weight: 700; color: #10b981; font-size: 0.95rem; border: 1px dashed #10b981; padding: 12px; border-radius: 8px;">
                            💚 Adoption Approved
                        </div>
                    <?php elseif ($app['status'] === 'Rejected'): ?>
                        <div style="text-align: center; font-weight: 700; color: #ef4444; font-size: 0.95rem; border: 1px dashed #ef4444; padding: 12px; border-radius: 8px;">
                            ❌ Adoption Rejected
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'includes/admin_footer.php';
?>
