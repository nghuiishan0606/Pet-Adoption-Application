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

// Handle Adoption Form Submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_adoption') {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
    
    $user_id = $_SESSION['user_session']['id'];
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
            // Check if pet is available
            $stmt_pet = $pdo->prepare("SELECT status FROM pets WHERE id = ?");
            $stmt_pet->execute([$pet_id]);
            $pet_status = $stmt_pet->fetchColumn();
            
            if ($pet_status !== 'Available') {
                $error_message = 'Sorry, this pet is already adopted or unavailable.';
            } else {
                // Insert application
                $stmt = $pdo->prepare("INSERT INTO applications (user_id, pet_id, name, email, phone, address, pickup_datetime, has_experience, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([$user_id, $pet_id, $app_name, $app_email, $app_phone, $app_address, $pickup_datetime, $has_experience, $remarks]);
                
                $app_id = $pdo->lastInsertId();
                
                // Create a notification for the user
                $notif_msg = "Your adoption application for pet has been submitted. Status: Pending. Pickup date: " . date('m/d/Y, g:i A', strtotime($pickup_datetime));
                $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'application', ?, ?)");
                $stmt_notif->execute([$user_id, $app_id, $notif_msg]);
                
                header("Location: adopt.php?success=1");
                exit();
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['success'])) {
    $success_message = 'Application submitted successfully! You can track its status under your Profile Dashboard.';
}

// Fetch Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : 'All';
$breed = isset($_GET['breed']) ? trim($_GET['breed']) : 'All';

// Build SQL Query for pets
$sql = "SELECT * FROM pets WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR breed LIKE ? OR personality LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category !== 'All' && !empty($category)) {
    $sql .= " AND type = ?";
    $params[] = $category;
}

if ($breed !== 'All' && !empty($breed)) {
    $sql .= " AND breed = ?";
    $params[] = $breed;
}

$sql .= " ORDER BY id DESC";

try {
    $stmt_pets = $pdo->prepare($sql);
    $stmt_pets->execute($params);
    $pets = $stmt_pets->fetchAll();
    
    // Get unique breeds for dropdown list based on category
    $breed_sql = "SELECT DISTINCT breed FROM pets";
    $breed_params = [];
    if ($category !== 'All' && !empty($category)) {
        $breed_sql .= " WHERE type = ?";
        $breed_params[] = $category;
    }
    $breed_sql .= " ORDER BY breed ASC";
    
    $stmt_breeds = $pdo->prepare($breed_sql);
    $stmt_breeds->execute($breed_params);
    $breeds = $stmt_breeds->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $pets = [];
    $breeds = [];
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

    <div class="section-header">
        <h2>Find Your Perfect Match</h2>
        <p>Browse through our lovable rescue animals. Apply for adoption or add them to your wishlist to track them.</p>
    </div>

    <!-- Filter Controls Bar -->
    <div class="filter-wrapper">
        <form method="GET" action="adopt.php">
            <div class="search-bar-container">
                <input type="text" name="search" class="search-input" placeholder="Search by name, breed, keywords..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-controls-row">
                <!-- Category Tabs (Submit form on click) -->
                <div class="category-tabs">
                    <input type="hidden" name="category" id="category-input" value="<?= htmlspecialchars($category) ?>">
                    <button type="button" class="category-tab <?= $category === 'All' ? 'active' : '' ?>" onclick="setCategory('All')">All Animals</button>
                    <button type="button" class="category-tab <?= $category === 'Cat' ? 'active' : '' ?>" onclick="setCategory('Cat')">Cats</button>
                    <button type="button" class="category-tab <?= $category === 'Dog' ? 'active' : '' ?>" onclick="setCategory('Dog')">Dogs</button>
                    <button type="button" class="category-tab <?= $category === 'Rabbit' ? 'active' : '' ?>" onclick="setCategory('Rabbit')">Rabbits</button>
                    <button type="button" class="category-tab <?= $category === 'Bird' ? 'active' : '' ?>" onclick="setCategory('Bird')">Birds</button>
                </div>
                
                <!-- Breed Select Dropdown -->
                <div style="display: flex; gap: 10px; align-items: center;">
                    <label for="breed-select" style="font-weight:700; font-size:0.9rem; color:var(--macaron-text-muted);">BREED:</label>
                    <select name="breed" id="breed-select" class="dropdown-filter-select" onchange="this.form.submit()">
                        <option value="All">All Breeds</option>
                        <?php foreach ($breeds as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>" <?= $breed === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if (!empty($search) || $category !== 'All' || $breed !== 'All'): ?>
                        <a href="adopt.php" style="font-size:0.9rem; color:var(--macaron-pink-primary); font-weight:700; text-decoration:underline; margin-left:10px;">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Pet Grid System -->
    <div class="pets-grid">
        <?php if (count($pets) === 0): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 65px 0;">
                <span style="font-size: 3rem;">🔍</span>
                <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--macaron-text-muted); margin-top: 15px;">No Pets Found</h3>
                <p style="color: var(--macaron-text-muted);">Try adjusting your search query or filter tags.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pets as $pet): 
                $is_wishlisted = false;
                if (is_logged_in()) {
                    try {
                        $stmt_wish = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ? AND pet_id = ?");
                        $stmt_wish->execute([$_SESSION['user_session']['id'], $pet['id']]);
                        $is_wishlisted = $stmt_wish->fetchColumn() > 0;
                    } catch (PDOException $e) { }
                }
            ?>
                <div class="pet-card">
                    <div class="pet-image-container">
                        <img src="<?= htmlspecialchars(clean_image_path($pet['image_path'])) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($pet['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=400&q=80'">
                        <?php if (is_logged_in()): ?>
                            <button class="wishlist-heart-btn <?= $is_wishlisted ? 'active' : '' ?>" data-pet-id="<?= $pet['id'] ?>">
                                <?= $is_wishlisted ? '❤️' : '🤍' ?>
                            </button>
                        <?php endif; ?>
                        <span class="pet-status-badge <?= $pet['status'] === 'Available' ? 'status-available' : 'status-adopted' ?>">
                            <?= htmlspecialchars($pet['status']) ?>
                        </span>
                    </div>
                    <div class="pet-info">
                        <div class="pet-name-breed">
                            <span class="pet-name"><?= htmlspecialchars($pet['name']) ?></span>
                            <span class="pet-breed"><?= htmlspecialchars($pet['breed']) ?></span>
                        </div>
                        <div class="pet-meta">
                            Age: <strong><?= htmlspecialchars($pet['age']) ?></strong> | Type: <strong><?= htmlspecialchars($pet['type']) ?></strong>
                        </div>
                        <div class="pet-actions">
                            <button type="button" class="btn btn-secondary" onclick="openLearnMoreModal(<?= htmlspecialchars(json_encode($pet)) ?>)">Learn More</button>
                            
                            <?php if ($pet['status'] === 'Available'): ?>
                                <button type="button" class="btn btn-primary" onclick="openApplyModal(<?= $pet['id'] ?>, '<?= htmlspecialchars($pet['name'], ENT_QUOTES) ?>')">Apply</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary" style="opacity: 0.5; cursor: not-allowed;" disabled>Adopted</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal 1: Learn More Modal -->
<div id="learn-more-modal" class="modal-overlay" onclick="if(event.target === this) closeModal('learn-more-modal')">
    <div class="modal-box" style="max-width: 680px;">
        <button type="button" class="modal-close-x" onclick="closeModal('learn-more-modal')">&times;</button>
        <div class="modal-body">
            <div class="modal-pet-details">
                <div class="modal-pet-img">
                    <img id="detail-pet-img" src="" alt="Pet Image">
                </div>
                <div class="modal-pet-info">
                    <h3 id="detail-pet-name">Pet Name</h3>
                    <div class="modal-pet-meta-grid">
                        <div class="modal-meta-card">
                            <span class="modal-meta-label">Breed</span>
                            <div class="modal-meta-val" id="detail-pet-breed">Breed</div>
                        </div>
                        <div class="modal-meta-card">
                            <span class="modal-meta-label">Age</span>
                            <div class="modal-meta-val" id="detail-pet-age">Age</div>
                        </div>
                        <div class="modal-meta-card">
                            <span class="modal-meta-label">Type</span>
                            <div class="modal-meta-val" id="detail-pet-type">Cat</div>
                        </div>
                        <div class="modal-meta-card">
                            <span class="modal-meta-label">Status</span>
                            <div class="modal-meta-val" id="detail-pet-status">Available</div>
                        </div>
                    </div>
                    
                    <div class="modal-pet-text-block">
                        <div class="modal-block-label">💡 Personality</div>
                        <div class="modal-block-text" id="detail-pet-personality">Description</div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="modal-pet-text-block" style="background-color: var(--macaron-bg); padding: 15px; border-radius: var(--radius-md); border:1px solid var(--macaron-border);">
                    <div class="modal-block-label">💉 Vaccination Record</div>
                    <div class="modal-block-text" id="detail-pet-vaccination" style="white-space: pre-line;">Record details</div>
                </div>
                <div class="modal-pet-text-block" style="background-color: var(--macaron-bg); padding: 15px; border-radius: var(--radius-md); border:1px solid var(--macaron-border);">
                    <div class="modal-block-label">🏥 Medical History</div>
                    <div class="modal-block-text" id="detail-pet-medical" style="white-space: pre-line;">Medical details</div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('learn-more-modal')">Close</button>
                <button type="button" class="btn btn-primary" id="detail-apply-btn">Apply for Adoption</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Adoption Application Form Modal -->
<div id="apply-modal" class="modal-overlay" onclick="if(event.target === this) closeModal('apply-modal')">
    <div class="modal-box" style="max-width: 580px;">
        <button type="button" class="modal-close-x" onclick="closeModal('apply-modal')">&times;</button>
        <div class="modal-body">
            <div class="modal-header">
                <h3 class="modal-title">Adopt <span id="apply-pet-name-title">Pet</span></h3>
                <p style="color: var(--macaron-text-muted); font-size: 0.9rem;">Fill out this application form to book a pick-up appointment.</p>
            </div>
            
            <?php if (!is_logged_in()): ?>
                <div style="text-align: center; padding: 20px 0;">
                    <span style="font-size: 2.5rem;">🔒</span>
                    <h4 style="font-size:1.2rem; font-weight:700; margin-top:15px;">Login Required</h4>
                    <p style="color:var(--macaron-text-muted); margin-bottom:20px;">You must sign in to submit an adoption application.</p>
                    <a href="login.php" class="btn btn-primary">Log In / Register</a>
                </div>
            <?php else: 
                // Pre-populate user details
                try {
                    $stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt_u->execute([$_SESSION['user_session']['id']]);
                    $user_details = $stmt_u->fetch();
                } catch (PDOException $e) {
                    $user_details = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
                }
            ?>
                <form id="apply-adoption-form" method="POST" action="adopt.php">
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
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="js/validation.js"></script>
<script>
    // Helper to submit search form on tab switch
    function setCategory(cat) {
        document.getElementById('category-input').value = cat;
        // Reset breed to All when category changes to fetch correct breeds
        document.getElementById('breed-select').value = 'All';
        document.getElementById('category-input').form.submit();
    }

    // Modal populate and open triggers
    function openLearnMoreModal(pet) {
        document.getElementById('detail-pet-name').innerText = pet.name;
        document.getElementById('detail-pet-breed').innerText = pet.breed;
        document.getElementById('detail-pet-age').innerText = pet.age;
        document.getElementById('detail-pet-type').innerText = pet.type;
        document.getElementById('detail-pet-status').innerText = pet.status;
        document.getElementById('detail-pet-personality').innerText = pet.personality;
        document.getElementById('detail-pet-vaccination').innerText = pet.vaccination_record;
        document.getElementById('detail-pet-medical').innerText = pet.medical_history;
        
        const img = document.getElementById('detail-pet-img');
        let cleanPath = pet.image_path || '';
        const idx = cleanPath.indexOf('uploads/');
        if (idx !== -1) {
            cleanPath = cleanPath.substring(idx);
        } else {
            cleanPath = cleanPath.replace(/^[./\\]+/, '');
        }
        img.src = cleanPath + '?t=' + new Date().getTime();
        img.onerror = function() {
            this.src = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=400&q=80';
        };
        
        // Setup Apply Button inside Details Modal
        const applyBtn = document.getElementById('detail-apply-btn');
        if (pet.status === 'Available') {
            applyBtn.style.display = 'inline-flex';
            applyBtn.onclick = function() {
                closeModal('learn-more-modal');
                openApplyModal(pet.id, pet.name);
            };
        } else {
            applyBtn.style.display = 'none';
        }
        
        openModal('learn-more-modal');
    }

    function openApplyModal(petId, petName) {
        const titleSpan = document.getElementById('apply-pet-name-title');
        if (titleSpan) {
            titleSpan.innerText = petName;
        }
        const idInput = document.getElementById('apply-pet-id');
        if (idInput) {
            idInput.value = petId;
        }
        
        // Set minimum pickup date to today
        const datetimeInput = document.getElementById('apply-datetime');
        if (datetimeInput) {
            const now = new Date();
            // Format to YYYY-MM-DDTHH:MM
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            datetimeInput.min = `${yyyy}-${mm}-${dd}T${hh}:${min}`;
        }
        
        openModal('apply-modal');
    }

    // Auto-open modal if specified in URL (for homepage details redirect)
    document.addEventListener('DOMContentLoaded', function() {
        attachEmailValidation('#apply-adoption-form', 'apply-email');
        attachPhoneValidation('#apply-adoption-form', 'apply-phone');
        attachAddressValidation('#apply-adoption-form', 'apply-address');
        <?php if (isset($_GET['learn_more_id'])): 
            $lmid = filter_input(INPUT_GET, 'learn_more_id', FILTER_VALIDATE_INT);
            if ($lmid):
                try {
                    $stmt_lm = $pdo->prepare("SELECT * FROM pets WHERE id = ?");
                    $stmt_lm->execute([$lmid]);
                    $lm_pet = $stmt_lm->fetch();
                    if ($lm_pet):
        ?>
                        openLearnMoreModal(<?= json_encode($lm_pet) ?>);
        <?php 
                    endif;
                } catch (PDOException $e) {}
            endif;
        endif; 
        ?>
    });

    
</script>

<?php
require_once 'includes/footer.php';
?>
