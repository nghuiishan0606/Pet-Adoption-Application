<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_admin();

$error = '';
$pet_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
if (!$pet_id) {
    // If post request, we might pass pet_id in the post
    $pet_id = filter_input(INPUT_POST, 'pet_id', FILTER_VALIDATE_INT);
}

if (!$pet_id) {
    header("Location: pets.php");
    exit();
}

// Fetch pet details
try {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->execute([$pet_id]);
    $pet = $stmt->fetch();
    if (!$pet) {
        header("Location: pets.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = 'The total upload size exceeds the server limit of ' . ini_get('post_max_size') . '.';
    } else {
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
        } else {
            // Handle File Upload
            try {
                // Step A: Fetch the existing image path from the database first
                $stmt_current = $pdo->prepare("SELECT image_path FROM pets WHERE id = ?");
                $stmt_current->execute([$pet_id]);
                $current_pet = $stmt_current->fetch();
                
                // Default to the old image path if no new one is uploaded
                $image_path = $current_pet ? $current_pet['image_path'] : 'uploads/default.jpg'; 

                // Step B: Check if a new file was uploaded
                if (isset($_FILES['image'])) {
                    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['image']['tmp_name'];
                        $file_name = $_FILES['image']['name'];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                        if (in_array($file_ext, $allowed)) {
                            // Generate unique name to prevent cache or overwrite conflicts
                            $new_file_name = uniqid('pet_', true) . '.' . $file_ext;
                            $upload_dir = '../uploads/';
                            if (!file_exists($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }
                            
                            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                                // Delete the old physical image file to save disk space
                                if (!empty($image_path) && file_exists('../' . $image_path)) {
                                    // Make sure we aren't deleting a default placeholder image
                                    if (strpos($image_path, 'default') === false) {
                                        unlink('../' . $image_path);
                                    }
                                }
                                // Set the path to the new image
                                $image_path = 'uploads/' . $new_file_name;
                            }
                        } else {
                            $error = 'Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.';
                        }
                    } elseif ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['image']['error'] === UPLOAD_ERR_FORM_SIZE) {
                        $error = 'The uploaded image size is too large. Server limit is ' . ini_get('upload_max_filesize') . '.';
                    }
                }
                
                if (empty($error)) {
                    // Step C: Update the database using the determined image path
                    $stmt = $pdo->prepare("UPDATE pets SET name = ?, type = ?, breed = ?, age = ?, status = ?, personality = ?, vaccination_record = ?, medical_history = ?, image_path = ? WHERE id = ?");
                    $stmt->execute([$name, $type, $breed, $age, $status, $personality, $vaccination, $medical, $image_path, $pet_id]);
                    header("Location: pets.php?success=updated");
                    exit();
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

require_once 'includes/admin_header.php';
?>
<link rel="stylesheet" href="../css/style.css">
<div style="margin-bottom: 25px;">
    <a href="pets.php" style="color:var(--macaron-pink-primary); font-weight:700; text-decoration:none;">&larr; Back to Pet List</a>
</div>

<?php if ($error): ?>
    <div style="background-color: var(--color-danger-bg); color: var(--color-danger); border: 1px solid rgba(255,59,48,0.2); padding: 18px 24px; border-radius: var(--radius-md); margin-bottom: 30px; font-weight:600;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="profile-content-area" style="background-color: #ffffff; padding: 40px; border-radius: var(--radius-lg); box-shadow: 0 10px 30px rgba(0,0,0,0.03); max-width: 800px; margin: 0 auto;">
    <h3 class="panel-title" style="font-size: 1.8rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 30px; border-bottom: 2px solid var(--macaron-border); padding-bottom: 15px;">Edit Pet Profile</h3>
    
    <form method="POST" action="edit_pet_info.php" enctype="multipart/form-data">
        <input type="hidden" name="pet_id" value="<?= htmlspecialchars($pet['id']) ?>">
        
        <div class="form-group">
            <label for="pet-name">Pet Name *</label>
            <input type="text" name="name" id="pet-name" class="form-control" value="<?= htmlspecialchars($pet['name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="pet-type">Pet Type *</label>
            <select name="type" id="pet-type" class="form-control" required>
                <option value="Dog" <?= $pet['type'] === 'Dog' ? 'selected' : '' ?>>Dog</option>
                <option value="Cat" <?= $pet['type'] === 'Cat' ? 'selected' : '' ?>>Cat</option>
                <option value="Rabbit" <?= $pet['type'] === 'Rabbit' ? 'selected' : '' ?>>Rabbit</option>
                <option value="Bird" <?= $pet['type'] === 'Bird' ? 'selected' : '' ?>>Bird</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="pet-breed">Breed *</label>
            <input type="text" name="breed" id="pet-breed" class="form-control" value="<?= htmlspecialchars($pet['breed']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="pet-age">Age Description *</label>
            <input type="text" name="age" id="pet-age" class="form-control" value="<?= htmlspecialchars($pet['age']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="pet-status">Adoption Status *</label>
            <select name="status" id="pet-status" class="form-control" required>
                <option value="Available" <?= $pet['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                <option value="Adopted" <?= $pet['status'] === 'Adopted' ? 'selected' : '' ?>>Adopted</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="pet-personality">Personality Description *</label>
            <textarea name="personality" id="pet-personality" class="form-control" rows="3" required><?= htmlspecialchars($pet['personality']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="pet-vaccination">Vaccination Record *</label>
            <textarea name="vaccination_record" id="pet-vaccination" class="form-control" rows="3" required><?= htmlspecialchars($pet['vaccination_record']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="pet-medical">Medical History *</label>
            <textarea name="medical_history" id="pet-medical" class="form-control" rows="3" required><?= htmlspecialchars($pet['medical_history']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="pet-image">Pet Image File</label>
            <?php if (!empty($pet['image_path'])): ?>
                <div style="margin-bottom: 12px;" id="current-image-container">
                    <img src="../<?= htmlspecialchars($pet['image_path']) . '?' . time() ?>" style="height: 100px; border-radius: 8px; border:1px solid var(--admin-border);">
                    <p style="font-size:0.8rem; color:var(--admin-text-muted);">Current image file: <?= htmlspecialchars($pet['image_path']) ?></p>
                </div>
            <?php endif; ?>
            <input type="file" name="image" id="pet-image" class="form-control" accept="image/*" style="padding: 10px;">
            <p style="font-size:0.8rem; color:var(--admin-text-muted); margin-top:5px;">Leave empty to keep current image.</p>
        </div>
        
        <div style="margin-top: 30px; display: flex; gap: 15px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="pets.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('pet-image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            let container = document.getElementById('image-preview-container');
            let img = document.getElementById('image-preview');
            if (!container) {
                container = document.createElement('div');
                container.id = 'image-preview-container';
                container.style.marginTop = '15px';
                img = document.createElement('img');
                img.id = 'image-preview';
                img.style.maxHeight = '150px';
                img.style.borderRadius = '8px';
                img.style.border = '1px solid var(--macaron-border)';
                container.appendChild(img);
                e.target.parentNode.appendChild(container);
            }
            img.src = event.target.result;
            container.style.display = 'block';
            
            // Hide the old image preview if it exists
            const currentImgWrap = document.getElementById('current-image-container');
            if (currentImgWrap) {
                currentImgWrap.style.opacity = '0.5';
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
<?php
require_once 'includes/admin_footer.php';
?>
