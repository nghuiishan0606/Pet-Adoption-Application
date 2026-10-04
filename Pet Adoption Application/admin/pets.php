<?php
ob_start();
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_admin();

// Handle DELETE actions BEFORE importing any HTML or header files
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $target_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($target_id) {
        try {
            $pdo->prepare("DELETE FROM wishlists WHERE pet_id = ?")->execute([$target_id]);
            $pdo->prepare("DELETE FROM applications WHERE pet_id = ?")->execute([$target_id]);
            $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
            $stmt->execute([$target_id]);
            header("Location: pets.php?success=deleted");
            exit();
        } catch (PDOException $e) {
            header("Location: pets.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

require_once 'includes/admin_header.php';

$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success = 'Pet profile added successfully!';
    } elseif ($_GET['success'] === 'updated') {
        $success = 'Pet profile updated successfully!';
    } elseif ($_GET['success'] === 'deleted') {
        $success = 'Pet profile deleted successfully!';
    }
}
$error = isset($_GET['error']) ? $_GET['error'] : '';
$form_mode = 'list'; // 'list', 'add', 'edit'
$edit_pet = [];

// Ensure upload directory exists
$upload_dir = '../uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle Form Submissions & Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
        $breed = trim(filter_input(INPUT_POST, 'breed', FILTER_SANITIZE_SPECIAL_CHARS));
        $age = trim(filter_input(INPUT_POST, 'age', FILTER_SANITIZE_SPECIAL_CHARS));
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
        $personality = trim(filter_input(INPUT_POST, 'personality', FILTER_SANITIZE_SPECIAL_CHARS));
        $vaccination = trim(filter_input(INPUT_POST, 'vaccination_record', FILTER_SANITIZE_SPECIAL_CHARS));
        $medical = trim(filter_input(INPUT_POST, 'medical_history', FILTER_SANITIZE_SPECIAL_CHARS));
        
        if (empty($name) || empty($type) || empty($breed) || empty($age) || empty($personality) || empty($vaccination) || empty($medical)) {
            $error = 'Please fill out all fields.';
            $form_mode = $action;
        } else {
            // Handle File Upload
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $image_path = 'uploads/' . $file_name;
                }
            }
            
            try {
                if ($action === 'add') {
                     if (empty($image_path)) {
                        $defaults = [
                            'Dog' => 'uploads/default_dog.jpg',
                            'Cat' => 'uploads/default_cat.jpg',
                            'Rabbit' => 'uploads/default_rabbit.jpg',
                            'Bird' => 'uploads/default_bird.jpg',
                        ];
                        $image_path = $defaults[$type] ?? 'uploads/default.jpg';
                    }
                    $stmt = $pdo->prepare("INSERT INTO pets (name, type, breed, age, status, personality, vaccination_record, medical_history, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $type, $breed, $age, $status, $personality, $vaccination, $medical, $image_path]);
                    $success = 'Pet profile added successfully!';
                    $form_mode = 'list';
                } else {
                    $pet_id = filter_input(INPUT_POST, 'pet_id', FILTER_VALIDATE_INT);
                    
                    if (empty($image_path)) {
                        // Keep existing image
                        $stmt = $pdo->prepare("UPDATE pets SET name = ?, type = ?, breed = ?, age = ?, status = ?, personality = ?, vaccination_record = ?, medical_history = ? WHERE id = ?");
                        $stmt->execute([$name, $type, $breed, $age, $status, $personality, $vaccination, $medical, $pet_id]);
                    } else {
                        // Update with new image
                        $stmt = $pdo->prepare("UPDATE pets SET name = ?, type = ?, breed = ?, age = ?, status = ?, personality = ?, vaccination_record = ?, medical_history = ?, image_path = ? WHERE id = ?");
                        $stmt->execute([$name, $type, $breed, $age, $status, $personality, $vaccination, $medical, $image_path, $pet_id]);
                    }
                    $success = 'Pet profile updated successfully!';
                    $form_mode = 'list';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
                $form_mode = $action;
            }
        }
    }
}

// GET actions handled at top

// Fetch all pets
try {
    $stmt_p = $pdo->query("SELECT * FROM pets ORDER BY id DESC");
    $pets = $stmt_p->fetchAll();
} catch (PDOException $e) {
    $pets = [];
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

<!-- LIST MODE -->
<?php if ($form_mode === 'list'): ?>
    <div style="margin-bottom: 25px; display: flex; justify-content: flex-end;">
        <a href="add_pet_info.php" class="admin-btn-primary" style="text-decoration:none; padding:12px 24px;">➕ Add New Pet Profile</a>
    </div>
    
    <div class="admin-table-container">
        <div class="admin-table-header">Registered Rescue Animals</div>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Breed</th>
                        <th>Age</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pets) === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--admin-text-muted);">No pets registered.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pets as $p): ?>
                            <tr>
                                <td>
                                    <img src="../<?= htmlspecialchars($p['image_path']) . '?' . time() ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 1px solid var(--admin-border);" onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=100&q=80'">
                                </td>
                                <td style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['type']) ?></td>
                                <td><?= htmlspecialchars($p['breed']) ?></td>
                                <td><?= htmlspecialchars($p['age']) ?></td>
                                <td>
                                    <span class="status-badge <?= $p['status'] === 'Available' ? 'role-admin' : 'status-active' ?>">
                                        <?= htmlspecialchars($p['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btn-list">
                                        <a href="edit_pet_info.php?edit_id=<?= $p['id'] ?>" class="action-btn btn-edit">📝 Edit</a>
                                        <a href="pets.php?action=delete&id=<?= $p['id'] ?>" class="action-btn btn-delete" onclick="return confirmAction('Are you sure you want to delete this pet profile permanently?')">🗑️ Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- ADD / EDIT MODE -->
<?php elseif ($form_mode === 'add' || $form_mode === 'edit'): ?>
    <div style="margin-bottom: 25px;">
        <a href="pets.php" style="color:var(--admin-blue-primary); font-weight:700;">&larr; Back to Pet List</a>
    </div>
    
    <div class="form-card">
        <h3 class="panel-title" style="margin-bottom: 30px;"><?= $form_mode === 'add' ? 'Add Rescue Pet Profile' : 'Edit Pet Profile' ?></h3>
        
        <form method="POST" action="pets.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $form_mode ?>">
            <?php if ($form_mode === 'edit'): ?>
                <input type="hidden" name="pet_id" value="<?= $edit_pet['id'] ?>">
            <?php endif; ?>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="pet-name">Pet Name *</label>
                    <input type="text" name="name" id="pet-name" class="admin-form-control" value="<?= $form_mode === 'edit' ? htmlspecialchars($edit_pet['name']) : '' ?>" placeholder="e.g. Oliver" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="pet-type">Pet Type *</label>
                    <select name="type" id="pet-type" class="admin-form-control" style="background-color:#ffffff;" required>
                        <option value="Dog" <?= ($form_mode === 'edit' && $edit_pet['type'] === 'Dog') ? 'selected' : '' ?>>Dog</option>
                        <option value="Cat" <?= ($form_mode === 'edit' && $edit_pet['type'] === 'Cat') ? 'selected' : '' ?>>Cat</option>
                        <option value="Rabbit" <?= ($form_mode === 'edit' && $edit_pet['type'] === 'Rabbit') ? 'selected' : '' ?>>Rabbit</option>
                        <option value="Bird" <?= ($form_mode === 'edit' && $edit_pet['type'] === 'Bird') ? 'selected' : '' ?>>Bird</option>
                    </select>
                </div>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="pet-breed">Breed *</label>
                    <input type="text" name="breed" id="pet-breed" class="admin-form-control" value="<?= $form_mode === 'edit' ? htmlspecialchars($edit_pet['breed']) : '' ?>" placeholder="e.g. Golden Retriever" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="pet-age">Age Description *</label>
                    <input type="text" name="age" id="pet-age" class="admin-form-control" value="<?= $form_mode === 'edit' ? htmlspecialchars($edit_pet['age']) : '' ?>" placeholder="e.g. 2 years, 6 months" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="pet-status">Adoption Status *</label>
                    <select name="status" id="pet-status" class="admin-form-control" style="background-color:#ffffff;" required>
                        <option value="Available" <?= ($form_mode === 'edit' && $edit_pet['status'] === 'Available') ? 'selected' : '' ?>>Available</option>
                        <option value="Adopted" <?= ($form_mode === 'edit' && $edit_pet['status'] === 'Adopted') ? 'selected' : '' ?>>Adopted</option>
                    </select>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="pet-personality">Personality Description *</label>
                <textarea name="personality" id="pet-personality" class="admin-form-control" rows="3" placeholder="Friendly, energetic, cuddly, shy..." required><?= $form_mode === 'edit' ? htmlspecialchars($edit_pet['personality']) : '' ?></textarea>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="pet-vaccination">Vaccination Record *</label>
                    <textarea name="vaccination_record" id="pet-vaccination" class="admin-form-control" rows="3" placeholder="DHPP, Rabies, FVRCP updates..." required><?= $form_mode === 'edit' ? htmlspecialchars($edit_pet['vaccination_record']) : '' ?></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label for="pet-medical">Medical History *</label>
                    <textarea name="medical_history" id="pet-medical" class="admin-form-control" rows="3" placeholder="Spayed/neutered, allergies, treatments..." required><?= $form_mode === 'edit' ? htmlspecialchars($edit_pet['medical_history']) : '' ?></textarea>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="pet-image">Pet Image File</label>
                <?php if ($form_mode === 'edit' && !empty($edit_pet['image_path'])): ?>
                    <div style="margin-bottom: 12px;">
                        <img src="../<?= htmlspecialchars($edit_pet['image_path']) . '?' . time() ?>" style="height: 100px; border-radius: 8px; border:1px solid var(--admin-border);">
                        <p style="font-size:0.8rem; color:var(--admin-text-muted);">Current image file path: <?= htmlspecialchars($edit_pet['image_path']) ?></p>
                    </div>
                <?php endif; ?>
                <input type="file" name="image" id="pet-image" class="admin-form-control" accept="image/*" style="background-color:#ffffff; padding:10px;">
                <p style="font-size:0.8rem; color:var(--admin-text-muted); margin-top:5px;">Leave empty to keep current image (if editing).</p>
            </div>
            
            <button type="submit" class="admin-btn-primary"><?= $form_mode === 'add' ? 'Publish Pet Profile' : 'Save Changes' ?></button>
        </form>
    </div>
<?php endif; ?>

<?php
require_once 'includes/admin_footer.php';
ob_end_flush();
?>
