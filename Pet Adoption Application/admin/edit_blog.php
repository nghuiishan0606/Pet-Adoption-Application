<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_admin();

$error = '';
$blog_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
if (!$blog_id) {
    $blog_id = filter_input(INPUT_POST, 'blog_id', FILTER_VALIDATE_INT);
}

if (!$blog_id) {
    header("Location: blogs.php");
    exit();
}

// Fetch blog details
try {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
    $stmt->execute([$blog_id]);
    $blog = $stmt->fetch();
    if (!$blog) {
        header("Location: blogs.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = 'The total upload size exceeds the server limit of ' . ini_get('post_max_size') . '.';
    } else {
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS));
        $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));
        $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS));
        $hashtags = trim(filter_input(INPUT_POST, 'hashtags', FILTER_SANITIZE_SPECIAL_CHARS));
        $title_font_size = filter_input(INPUT_POST, 'title_font_size', FILTER_VALIDATE_INT);
        $title_font_color = trim(filter_input(INPUT_POST, 'title_font_color', FILTER_SANITIZE_SPECIAL_CHARS));
        
        if (empty($title) || empty($content) || empty($category) || empty($hashtags) || !$title_font_size || empty($title_font_color)) {
            $error = 'Please fill out all fields.';
        } else {
            // Handle File Upload
            try {
                // Step A: Fetch the existing image path from the database first
                $stmt_current = $pdo->prepare("SELECT image_path FROM blogs WHERE id = ?");
                $stmt_current->execute([$blog_id]);
                $current_blog = $stmt_current->fetch();
                
                // Default to the old image path if no new one is uploaded
                $image_path = $current_blog ? $current_blog['image_path'] : 'uploads/default_blog.jpg'; 

                // Step B: Check if a new file was uploaded
                if (isset($_FILES['image'])) {
                    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['image']['tmp_name'];
                        $file_name = $_FILES['image']['name'];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                        if (in_array($file_ext, $allowed)) {
                            // Generate unique name to prevent cache or overwrite conflicts
                            $new_file_name = uniqid('blog_', true) . '.' . $file_ext;
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
                    $stmt = $pdo->prepare("UPDATE blogs SET title = ?, content = ?, category = ?, hashtags = ?, title_font_size = ?, title_font_color = ?, image_path = ? WHERE id = ?");
                    $stmt->execute([$title, $content, $category, $hashtags, $title_font_size, $title_font_color, $image_path, $blog_id]);
                    header("Location: blogs.php?success=updated");
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
    <a href="blogs.php" style="color:var(--macaron-pink-primary); font-weight:700; text-decoration:none;">&larr; Back to Articles List</a>
</div>

<?php if ($error): ?>
    <div style="background-color: var(--color-danger-bg); color: var(--color-danger); border: 1px solid rgba(255,59,48,0.2); padding: 18px 24px; border-radius: var(--radius-md); margin-bottom: 30px; font-weight:600;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="profile-content-area" style="background-color: #ffffff; padding: 40px; border-radius: var(--radius-lg); box-shadow: 0 10px 30px rgba(0,0,0,0.03); max-width: 800px; margin: 0 auto;">
    <h3 class="panel-title" style="font-size: 1.8rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 30px; border-bottom: 2px solid var(--macaron-border); padding-bottom: 15px;">Edit Published Article</h3>
    
    <form method="POST" action="edit_blog.php" enctype="multipart/form-data">
        <input type="hidden" name="blog_id" value="<?= htmlspecialchars($blog['id']) ?>">
        
        <div class="form-group">
            <label for="blog-title">Article Title *</label>
            <input type="text" name="title" id="blog-title" class="form-control" value="<?= htmlspecialchars($blog['title']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="blog-font-size">Title Header Font Size (px) *</label>
            <input type="number" name="title_font_size" id="blog-font-size" class="form-control" value="<?= htmlspecialchars($blog['title_font_size']) ?>" min="12" max="64" required>
        </div>
        
        <div class="form-group">
            <label for="blog-font-color">Title Header Font Color *</label>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="color" name="title_font_color" id="blog-font-color" value="<?= htmlspecialchars($blog['title_font_color']) ?>" style="width: 50px; height: 50px; border-radius: 8px; border:1px solid var(--macaron-border); cursor:pointer; background:none; padding:2px;">
                <span style="font-size:0.9rem; color:var(--macaron-text-muted);">Use color picker to select header styling color</span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="blog-category">Category *</label>
            <input type="text" name="category" id="blog-category" class="form-control" value="<?= htmlspecialchars($blog['category']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="blog-hashtags">Hashtags * (Space separated)</label>
            <input type="text" name="hashtags" id="blog-hashtags" class="form-control" value="<?= htmlspecialchars($blog['hashtags']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="blog-content">Article Content Body *</label>
            <textarea name="content" id="blog-content" class="form-control" rows="8" required><?= htmlspecialchars($blog['content']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="blog-image">Cover Image File</label>
            <?php if (!empty($blog['image_path'])): ?>
                <div style="margin-bottom: 12px;" id="current-image-container">
                    <img src="../<?= htmlspecialchars($blog['image_path']) . '?' . time() ?>" style="height: 100px; border-radius: 8px; border:1px solid var(--admin-border);">
                    <p style="font-size:0.8rem; color:var(--admin-text-muted);">Current image file: <?= htmlspecialchars($blog['image_path']) ?></p>
                </div>
            <?php endif; ?>
            <input type="file" name="image" id="blog-image" class="form-control" accept="image/*" style="padding: 10px;">
            <p style="font-size:0.8rem; color:var(--admin-text-muted); margin-top:5px;">Leave empty to keep current image.</p>
        </div>
        
        <div style="margin-top: 30px; display: flex; gap: 15px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="blogs.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('blog-image')?.addEventListener('change', function(e) {
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
