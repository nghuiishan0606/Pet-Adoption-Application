<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_admin();

$error = '';

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
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $image_path = 'uploads/default_blog.jpg'; // fallback
            
            if (isset($_FILES['image'])) {
                if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['image']['tmp_name'];
                    $file_name = $_FILES['image']['name'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    if (in_array($file_ext, $allowed)) {
                        $new_file_name = uniqid('blog_', true) . '.' . $file_ext;
                        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
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
                try {
                    $stmt = $pdo->prepare("INSERT INTO blogs (title, content, category, hashtags, title_font_size, title_font_color, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $content, $category, $hashtags, $title_font_size, $title_font_color, $image_path]);
                    header("Location: blogs.php?success=added");
                    exit();
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
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
    <h3 class="panel-title" style="font-size: 1.8rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 30px; border-bottom: 2px solid var(--macaron-border); padding-bottom: 15px;">Write & Publish News Article</h3>
    
    <form method="POST" action="add_blog.php" enctype="multipart/form-data">
        <div class="form-group">
            <label for="blog-title">Article Title *</label>
            <input type="text" name="title" id="blog-title" class="form-control" placeholder="e.g. Welcoming Your New Pet Home" required>
        </div>
        
        <div class="form-group">
            <label for="blog-font-size">Title Header Font Size (px) *</label>
            <input type="number" name="title_font_size" id="blog-font-size" class="form-control" value="24" min="12" max="64" required>
        </div>
        
        <div class="form-group">
            <label for="blog-font-color">Title Header Font Color *</label>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="color" name="title_font_color" id="blog-font-color" value="#333333" style="width: 50px; height: 50px; border-radius: 8px; border:1px solid var(--macaron-border); cursor:pointer; background:none; padding:2px;">
                <span style="font-size:0.9rem; color:var(--macaron-text-muted);">Use color picker to select header styling color</span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="blog-category">Category *</label>
            <input type="text" name="category" id="blog-category" class="form-control" placeholder="e.g. Pet Care, Health, Announcement" required>
        </div>
        
        <div class="form-group">
            <label for="blog-hashtags">Hashtags * (Space separated)</label>
            <input type="text" name="hashtags" id="blog-hashtags" class="form-control" placeholder="e.g. #New #Announcement #Veterinary" required>
        </div>
        
        <div class="form-group">
            <label for="blog-content">Article Content Body *</label>
            <textarea name="content" id="blog-content" class="form-control" rows="8" placeholder="Write full article body text..." required></textarea>
        </div>
        
        <div class="form-group">
            <label for="blog-image">Cover Image File</label>
            <input type="file" name="image" id="blog-image" class="form-control" accept="image/*" style="padding: 10px;">
        </div>
        
        <div style="margin-top: 30px; display: flex; gap: 15px;">
            <button type="submit" class="btn btn-primary">Publish Article</button>
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
        };
        reader.readAsDataURL(file);
    }
});
</script>
<?php
require_once 'includes/admin_footer.php';
?>
