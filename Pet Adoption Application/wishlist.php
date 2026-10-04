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

// Enforce login
require_login();

$user_id = $_SESSION['user_session']['id'];
$success_message = '';
$error_message = '';

// Handle Application Form Submission (in case user clicks apply and submits from here)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_adoption') {
    $pet_id = filter_input(INPUT_POST, 'pet_id', FILTER_VALIDATE_INT);
    $app_name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $app_email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $app_phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
    $app_address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
    $pickup_datetime = filter_input(INPUT_POST, 'pickup_datetime', FILTER_SANITIZE_SPECIAL_CHARS);
    $has_experience = isset($_POST['has_experience']) ? 1 : 0;
    $remarks = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));
    
    if (!$pet_id || empty($app_name) || empty($app_email) || empty($app_phone) || empty($app_address) || empty($pickup_datetime)) {
        $error_message = 'Please fill out all required fields.';
    } else {
        try {
            $stmt_pet = $pdo->prepare("SELECT status FROM pets WHERE id = ?");
            $stmt_pet->execute([$pet_id]);
            $pet_status = $stmt_pet->fetchColumn();
            
            if ($pet_status !== 'Available') {
                $error_message = 'Sorry, this pet is already adopted or unavailable.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO applications (user_id, pet_id, name, email, phone, address, pickup_datetime, has_experience, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([$user_id, $pet_id, $app_name, $app_email, $app_phone, $app_address, $pickup_datetime, $has_experience, $remarks]);
                
                $app_id = $pdo->lastInsertId();
                $notif_msg = "Your adoption application for pet has been submitted. Status: Pending. Pickup date: " . date('m/d/Y, g:i A', strtotime($pickup_datetime));
                $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'application', ?, ?)");
                $stmt_notif->execute([$user_id, $app_id, $notif_msg]);
                
                $success_message = 'Application submitted successfully! You can track its status under your Profile Dashboard.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch Wishlist Items
try {
    $stmt_wish = $pdo->prepare("SELECT w.id AS wishlist_id, p.* FROM wishlists w JOIN pets p ON w.pet_id = p.id WHERE w.user_id = ? ORDER BY w.id DESC");
    $stmt_wish->execute([$user_id]);
    $wishlist_pets = $stmt_wish->fetchAll();
} catch (PDOException $e) {
    $wishlist_pets = [];
}

// Helper to check if user has already applied for a pet
function has_applied($pdo, $user_id, $pet_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND pet_id = ?");
        $stmt->execute([$user_id, $pet_id]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

require_once 'includes/header.php';
?>

<div class="container" style="padding: 40px 24px;">
    
    <?php if ($success_message): ?>
        <div style="background-color: var(--color-success-bg); color: var(--color-success); border: 1px solid rgba(76,217,100,0.2); padding: 18px 24px; border-radius: var(--radius-md); margin-bottom: 30px; font-weight:600;">
            ✅ <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div style="background-color: var(--color-danger-bg); color: var(--color-danger); border: 1px solid rgba(255,59,48,0.2); padding: 18px 24px; border-radius: var(--radius-md); margin-bottom: 30px; font-weight:600;">
            ⚠️ <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="section-header" style="margin-bottom: 40px;">
        <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 8px;">My Saved Companions</h2>
        <p style="color: var(--macaron-text-muted); font-size: 1.05rem;">A cozy list of shelter animals you are interested in.</p>
    </div>

    <!-- Wishlist Grid -->
    <div class="pets-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px;">
        <?php if (count($wishlist_pets) === 0): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 0;">
                <span style="font-size: 3rem;">🤍</span>
                <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--macaron-text-muted); margin-top: 15px;">Your Wishlist is Empty</h3>
                <p style="color: var(--macaron-text-muted); margin-top: 5px;">Browse our adoptable animals and click the heart icon to save them here.</p>
                <a href="adopt.php" class="btn btn-primary" style="margin-top: 20px;">Find a Companion</a>
            </div>
        <?php else: ?>
            <?php foreach ($wishlist_pets as $pet): 
                $user_has_applied = has_applied($pdo, $user_id, $pet['id']);
            ?>
                <div class="pet-card">
                    <div class="pet-image-container">
                        <img src="<?= htmlspecialchars(clean_image_path($pet['image_path'])) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($pet['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=400&q=80'">
                        <button class="wishlist-heart-btn active" data-pet-id="<?= $pet['id'] ?>">❤️</button>
                        <span style="position: absolute; bottom: 12px; left: 12px; background-color: var(--macaron-pink-light); color: var(--macaron-pink-primary); font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase; border: 1px solid var(--macaron-border);"><?= htmlspecialchars($pet['type']) ?></span>
                    </div>
                    <div class="pet-info" style="padding: 24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                            <span class="pet-name" style="font-size:1.4rem; font-weight:800; color:var(--macaron-text-main);"><?= htmlspecialchars($pet['name']) ?></span>
                            <?php if ($user_has_applied): ?>
                                <span class="badge badge-pending" style="font-size:0.75rem; padding: 4px 12px; font-weight:700;">Applied</span>
                            <?php else: ?>
                                <span class="badge badge-approved" style="font-size:0.75rem; padding: 4px 12px; font-weight:700;"><?= htmlspecialchars($pet['status']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.95rem; color:var(--macaron-text-muted); margin-bottom:20px; line-height: 1.5;">
                            <p style="font-weight:700; color:var(--macaron-text-main); margin-bottom: 2px;"><?= htmlspecialchars($pet['breed']) ?></p>
                            <p>Age: <?= htmlspecialchars($pet['age']) ?></p>
                        </div>
                        <div class="pet-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <a href="adopt.php?learn_more_id=<?= $pet['id'] ?>" class="btn btn-secondary" style="width: 100%; text-align: center; text-decoration: none; padding: 10px; font-size: 0.9rem;">Learn More</a>
                            
                            <?php if ($pet['status'] !== 'Available'): ?>
                                <button type="button" class="btn btn-secondary" style="width: 100%; opacity:0.5; cursor:not-allowed; padding: 10px; font-size: 0.9rem;" disabled>Adopted</button>
                            <?php elseif ($user_has_applied): ?>
                                <button type="button" class="btn btn-secondary" style="width: 100%; opacity:0.5; cursor:not-allowed; padding: 10px; font-size: 0.9rem;" disabled>Under Review</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary" onclick="openApplyModal(<?= $pet['id'] ?>, '<?= htmlspecialchars($pet['name'], ENT_QUOTES) ?>')" style="width: 100%; padding: 10px; font-size: 0.9rem;">Apply</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

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
            <form id="apply-adoption-form" method="POST" action="wishlist.php">
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
    
    // Auto-reload standalone wishlist page when a pet is removed from wishlist
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('wishlist-heart-btn')) {
            setTimeout(function() {
                location.reload();
            }, 300);
        }
    });
</script>

<?php
require_once 'includes/footer.php';
?>
