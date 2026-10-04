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
            $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
            $stmt->execute([$target_id]);
            header("Location: blogs.php?success=deleted");
            exit();
        } catch (PDOException $e) {
            header("Location: blogs.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

require_once 'includes/admin_header.php';

$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success = 'Blog post published successfully!';
    } elseif ($_GET['success'] === 'updated') {
        $success = 'Blog post updated successfully!';
    } elseif ($_GET['success'] === 'deleted') {
        $success = 'Blog article deleted successfully!';
    }
}
$error = isset($_GET['error']) ? $_GET['error'] : '';
$form_mode = 'list'; // 'list', 'add', 'edit'
$edit_blog = [];

$upload_dir = '../uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add' || $action === 'edit') {
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS));
        $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));
        $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS));
        $hashtags = trim(filter_input(INPUT_POST, 'hashtags', FILTER_SANITIZE_SPECIAL_CHARS));
        $title_font_size = filter_input(INPUT_POST, 'title_font_size', FILTER_VALIDATE_INT);
        $title_font_color = trim(filter_input(INPUT_POST, 'title_font_color', FILTER_SANITIZE_SPECIAL_CHARS));
        
        if (empty($title) || empty($content) || empty($category) || empty($hashtags) || !$title_font_size || empty($title_font_color)) {
            $error = 'Please fill out all fields.';
            $form_mode = $action;
        } else {
            // Handle image upload
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
                        $image_path = 'uploads/default_blog.jpg'; // fallback
                    }
                    $stmt = $pdo->prepare("INSERT INTO blogs (title, content, category, hashtags, title_font_size, title_font_color, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $content, $category, $hashtags, $title_font_size, $title_font_color, $image_path]);
                    $success = 'Blog post published successfully!';
                    $form_mode = 'list';
                } else {
                    $blog_id = filter_input(INPUT_POST, 'blog_id', FILTER_VALIDATE_INT);
                    
                    if (empty($image_path)) {
                        // Keep current image
                        $stmt = $pdo->prepare("UPDATE blogs SET title = ?, content = ?, category = ?, hashtags = ?, title_font_size = ?, title_font_color = ? WHERE id = ?");
                        $stmt->execute([$title, $content, $category, $hashtags, $title_font_size, $title_font_color, $blog_id]);
                    } else {
                        // Update with new image
                        $stmt = $pdo->prepare("UPDATE blogs SET title = ?, content = ?, category = ?, hashtags = ?, title_font_size = ?, title_font_color = ?, image_path = ? WHERE id = ?");
                        $stmt->execute([$title, $content, $category, $hashtags, $title_font_size, $title_font_color, $image_path, $blog_id]);
                    }
                    $success = 'Blog post updated successfully!';
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

// Fetch all blogs
try {
    $stmt_b = $pdo->query("SELECT * FROM blogs ORDER BY id DESC");
    $blogs = $stmt_b->fetchAll();
} catch (PDOException $e) {
    $blogs = [];
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

<!-- LIST VIEW -->
<?php if ($form_mode === 'list'): ?>
    <div style="margin-bottom: 25px; display: flex; justify-content: flex-end;">
        <a href="add_blog.php" class="admin-btn-primary" style="text-decoration:none; padding:12px 24px;">➕ Write New Article</a>
    </div>
    
    <div class="admin-table-container">
        <div class="admin-table-header">Published Articles</div>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Hashtags</th>
                        <th>Title Styles</th>
                        <th>Date Published</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($blogs) === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--admin-text-muted);">No blog posts published yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($blogs as $b): ?>
                            <tr>
                                <td>
                                    <img src="../<?= htmlspecialchars($b['image_path']) . '?' . time() ?>" alt="<?= htmlspecialchars($b['title']) ?>" style="width: 60px; height: 40px; border-radius: 4px; object-fit: cover; border: 1px solid var(--admin-border);" onerror="this.src='https://images.unsplash.com/photo-1516738901171-8eb4fc13bd20?auto=format&fit=crop&w=100&q=80'">
                                </td>
                                <td style="font-weight: 700; color: #1e293b; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($b['title']) ?></td>
                                <td><?= htmlspecialchars($b['category']) ?></td>
                                <td><span style="font-size:0.8rem; color:var(--admin-blue-primary); font-weight:600;"><?= htmlspecialchars($b['hashtags']) ?></span></td>
                                <td>
                                    <span style="font-size: 0.85rem; background-color:#f1f5f9; padding:4px 8px; border-radius:4px;">
                                        <?= $b['title_font_size'] ?>px / <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background-color:<?= $b['title_font_color'] ?>; vertical-align:middle; border:1px solid #94a3b8;"></span> <?= $b['title_font_color'] ?>
                                    </span>
                                </td>
                                <td><?= date('m/d/Y', strtotime($b['created_at'])) ?></td>
                                <td>
                                    <div class="action-btn-list">
                                        <a href="edit_blog.php?edit_id=<?= $b['id'] ?>" class="action-btn btn-edit">📝 Edit</a>
                                        <a href="blogs.php?action=delete&id=<?= $b['id'] ?>" class="action-btn btn-delete" onclick="return confirmAction('Are you sure you want to delete this article permanently?')">🗑️ Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- ADD / EDIT VIEW -->
<?php elseif ($form_mode === 'add' || $form_mode === 'edit'): ?>
    <div style="margin-bottom: 25px;">
        <a href="blogs.php" style="color:var(--admin-blue-primary); font-weight:700;">&larr; Back to Articles List</a>
    </div>
    
    <div class="form-card">
        <h3 class="panel-title" style="margin-bottom: 30px;"><?= $form_mode === 'add' ? 'Write & Publish News Article' : 'Edit Published Article' ?></h3>
        
        <form method="POST" action="blogs.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $form_mode ?>">
            <?php if ($form_mode === 'edit'): ?>
                <input type="hidden" name="blog_id" value="<?= $edit_blog['id'] ?>">
            <?php endif; ?>
            
            <div class="admin-form-group">
                <label for="blog-title">Article Title *</label>
                <input type="text" name="title" id="blog-title" class="admin-form-control" value="<?= $form_mode === 'edit' ? htmlspecialchars($edit_blog['title']) : '' ?>" placeholder="e.g. Welcoming Your New Pet Home" required>
            </div>
            
            <!-- Typography Customize Parameters -->
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="blog-font-size">Title Header Font Size (px) *</label>
                    <input type="number" name="title_font_size" id="blog-font-size" class="admin-form-control" value="<?= $form_mode === 'edit' ? (int)$edit_blog['title_font_size'] : 24 ?>" min="12" max="64" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="blog-font-color">Title Header Font Color *</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="color" name="title_font_color" id="blog-font-color" value="<?= $form_mode === 'edit' ? htmlspecialchars($edit_blog['title_font_color']) : '#333333' ?>" style="width: 50px; height: 50px; border-radius: 8px; border:1px solid var(--admin-border); cursor:pointer; background:none; padding:2px;">
                        <span style="font-size:0.9rem; color:var(--admin-text-muted);">Use color picker to select header styling color</span>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="blog-category">Category *</label>
                    <input type="text" name="category" id="blog-category" class="admin-form-control" value="<?= $form_mode === 'edit' ? htmlspecialchars($edit_blog['category']) : '' ?>" placeholder="e.g. Pet Care, Health, Announcement" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="blog-hashtags">Hashtags * (Space separated)</label>
                    <input type="text" name="hashtags" id="blog-hashtags" class="admin-form-control" value="<?= $form_mode === 'edit' ? htmlspecialchars($edit_blog['hashtags']) : '' ?>" placeholder="e.g. #New #Announcement #Veterinary" required>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="blog-content">Article Content Body *</label>
                <textarea name="content" id="blog-content" class="admin-form-control" rows="8" placeholder="Write full article body text..." required><?= $form_mode === 'edit' ? htmlspecialchars($edit_blog['content']) : '' ?></textarea>
            </div>
            
            <div class="admin-form-group">
                <label for="blog-image">Cover Image File</label>
                <?php if ($form_mode === 'edit' && !empty($edit_blog['image_path'])): ?>
                    <div style="margin-bottom: 12px;">
                        <img src="../<?= htmlspecialchars($edit_blog['image_path']) . '?' . time() ?>" style="height: 100px; border-radius: 8px; border:1px solid var(--admin-border);">
                        <p style="font-size:0.8rem; color:var(--admin-text-muted);">Current image file: <?= htmlspecialchars($edit_blog['image_path']) ?></p>
                    </div>
                <?php endif; ?>
                <input type="file" name="image" id="blog-image" class="admin-form-control" accept="image/*" style="background-color:#ffffff; padding:10px;">
                <p style="font-size:0.8rem; color:var(--admin-text-muted); margin-top:5px;">Leave empty to keep current image (if editing).</p>
            </div>
            
            <button type="submit" class="admin-btn-primary"><?= $form_mode === 'add' ? 'Publish Article' : 'Save Changes' ?></button>
        </form>
    </div>
<?php endif; ?>

<?php
require_once 'includes/admin_footer.php';
ob_end_flush();
?>
