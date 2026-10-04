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

// Fetch Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : 'All';
$tag_filter = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$selected_id = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : 0;
$selected_blog = null;

if ($selected_id) {
    try {
        $stmt_sel = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
        $stmt_sel->execute([$selected_id]);
        $selected_blog = $stmt_sel->fetch();
    } catch (PDOException $e) {
        $selected_blog = null;
    }
}

// Build SQL Query for blogs
$sql = "SELECT * FROM blogs WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR content LIKE ? OR hashtags LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category !== 'All' && !empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if (!empty($tag_filter)) {
    $sql .= " AND hashtags LIKE ?";
    $params[] = "%" . $tag_filter . "%";
}

$sql .= " ORDER BY id DESC";

try {
    $stmt_blogs = $pdo->prepare($sql);
    $stmt_blogs->execute($params);
    $blogs = $stmt_blogs->fetchAll();
    
    // Get unique categories for filter
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM blogs ORDER BY category ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get sidebar latest news (up to 5)
    $sidebar_stmt = $pdo->query("SELECT id, title, created_at FROM blogs ORDER BY id DESC LIMIT 5");
    $other_news = $sidebar_stmt->fetchAll();
} catch (PDOException $e) {
    $blogs = [];
    $categories = [];
    $other_news = [];
}

require_once 'includes/header.php';
?>

<div class="container" style="padding: 40px 24px;">
    <div class="section-header">
        <h2>Petopia News & Blog</h2>
        <p>Stay updated with our latest rescue announcements, pet care tips, and veterinary health guides.</p>
    </div>

    <!-- Blog Layout Newspaper Style -->
    <div class="blog-wrapper">
        
        <!-- Main News Feed (Left Column) -->
        <div class="blog-main-feed">
            
            <!-- Search & Filter Widget (Only visible in main feed on desktop/mobile) -->
            <div style="background-color: #ffffff; border: 1px solid var(--macaron-border); border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 30px;">
                <form method="GET" action="blogs.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Search articles..." value="<?= htmlspecialchars($search) ?>" style="background-color: var(--macaron-bg);">
                    </div>
                    <div>
                        <select name="category" class="dropdown-filter-select" onchange="this.form.submit()" style="background-color: var(--macaron-bg); border-radius: var(--radius-md);">
                            <option value="All">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($search) || $category !== 'All' || !empty($tag_filter)): ?>
                        <div>
                            <a href="blogs.php" style="font-size:0.9rem; color:var(--macaron-pink-primary); font-weight:700; text-decoration:underline;">Reset</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($selected_blog): 
                $blog = $selected_blog;
                $tags = array_filter(explode(' ', $blog['hashtags']));
            ?>
                <!-- Back Link -->
                <div style="margin-bottom: 25px;">
                    <a href="blogs.php" style="color: var(--macaron-pink-primary); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <span>&larr;</span> Back to All Articles
                    </a>
                </div>

                <article class="blog-card-long" id="blog-<?= $blog['id'] ?>">
                    <div class="blog-img-long">
                        <img src="<?= htmlspecialchars(clean_image_path($blog['image_path'])) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($blog['title']) ?>" onerror="this.src='https://images.unsplash.com/photo-1516738901171-8eb4fc13bd20?auto=format&fit=crop&w=800&q=80'">
                    </div>
                    <div class="blog-desc-content">
                        <div class="blog-tags-row">
                            <?php foreach ($tags as $tag): ?>
                                <a href="blogs.php?tag=<?= urlencode(trim($tag)) ?>" class="blog-tag" style="text-decoration: none;"><?= htmlspecialchars(trim($tag)) ?></a>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="blog-date-cat">
                            Published on <strong><?= date('F j, Y', strtotime($blog['created_at'])) ?></strong> | Category: <strong><?= htmlspecialchars($blog['category']) ?></strong>
                        </div>
                        
                        <!-- Admin customized font styling -->
                        <h3 class="blog-title" style="font-size: <?= (int)$blog['title_font_size'] ?>px; color: <?= htmlspecialchars($blog['title_font_color']) ?>;">
                            <?= htmlspecialchars($blog['title']) ?>
                        </h3>
                        
                        <p class="blog-excerpt"><?= nl2br(htmlspecialchars($blog['content'])) ?></p>
                    </div>
                </article>

            <?php else: ?>
                <?php if (count($blogs) === 0): ?>
                    <div style="text-align: center; padding: 60px 0; background-color:#ffffff; border-radius:var(--radius-lg); border:1px solid var(--macaron-border);">
                        <span style="font-size: 3rem;">📰</span>
                        <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--macaron-text-muted); margin-top: 15px;">No Articles Found</h3>
                        <p style="color: var(--macaron-text-muted);">We couldn't find any articles matching your filters.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($blogs as $blog): 
                        // Parse hashtags into array
                        $tags = array_filter(explode(' ', $blog['hashtags']));
                    ?>
                        <article class="blog-card-long" id="blog-<?= $blog['id'] ?>">
                            <div class="blog-img-long">
                                <img src="<?= htmlspecialchars(clean_image_path($blog['image_path'])) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($blog['title']) ?>" onerror="this.src='https://images.unsplash.com/photo-1516738901171-8eb4fc13bd20?auto=format&fit=crop&w=800&q=80'">
                            </div>
                            <div class="blog-desc-content">
                                <div class="blog-tags-row">
                                    <?php foreach ($tags as $tag): ?>
                                        <a href="blogs.php?tag=<?= urlencode(trim($tag)) ?>" class="blog-tag" style="text-decoration: none;"><?= htmlspecialchars(trim($tag)) ?></a>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="blog-date-cat">
                                    Published on <strong><?= date('F j, Y', strtotime($blog['created_at'])) ?></strong> | Category: <strong><?= htmlspecialchars($blog['category']) ?></strong>
                                </div>
                                
                                <!-- Admin customized font styling -->
                                <h3 class="blog-title" style="font-size: <?= (int)$blog['title_font_size'] ?>px; color: <?= htmlspecialchars($blog['title_font_color']) ?>;">
                                    <a href="blogs.php?id=<?= $blog['id'] ?>" style="color: inherit; text-decoration: none;">
                                        <?= htmlspecialchars($blog['title']) ?>
                                    </a>
                                </h3>
                                
                                <p class="blog-excerpt"><?= nl2br(htmlspecialchars($blog['content'])) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar Widgets (Right Column) -->
        <aside class="blog-sidebar">
            <div class="sidebar-widget">
                <h3 class="widget-title">Latest & Other News</h3>
                <ul class="other-news-list">
                    <?php if (count($other_news) === 0): ?>
                        <li style="font-size: 0.9rem; color: var(--macaron-text-muted);">No other news available.</li>
                    <?php else: ?>
                        <?php foreach ($other_news as $news): ?>
                            <li class="other-news-item">
                                <a href="blogs.php?id=<?= $news['id'] ?>"><?= htmlspecialchars($news['title']) ?></a>
                                <div class="other-news-date"><?= date('M d, Y', strtotime($news['created_at'])) ?></div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="sidebar-widget" style="background-color: var(--macaron-pink-light); border-color: var(--macaron-pink-medium);">
                <h3 class="widget-title" style="border-bottom-color: var(--macaron-pink-medium);">Adoption Impact</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 15px;">Every article, care tip, and story is written to support pet owners and rescue efforts. Together, we can find a loving home for every animal in need.</p>
                <a href="adopt.php" class="btn btn-primary" style="width:100%; font-size:0.95rem;">Adopt a Pet Today</a>
            </div>
        </aside>

    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
