<?php
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

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

// Retrieve Homepage content from CMS
$cms = [
    'homepage_hero_title' => 'Find Your Pawfect <br> Companion',
    'homepage_hero_desc' => 'Welcome to Petopia, where loving hearts meet rescue pets in need of a forever home. Start your adoption journey today and change a life forever.'
];

try {
    $stmt = $pdo->query("SELECT * FROM cms_content WHERE meta_key IN ('homepage_hero_title', 'homepage_hero_desc')");
    while ($row = $stmt->fetch()) {
        $cms[$row['meta_key']] = $row['meta_value'];
    }
    
    // Fetch dynamic impact statistics
    // 1. Total Animals Rescued (Total in Database)
    $total_rescued = $pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
    
    // 2. Total Vaccinated Animals
    $total_vaccinated = $pdo->query("SELECT COUNT(*) FROM pets WHERE vaccination_record NOT LIKE 'N/A%' AND vaccination_record != ''")->fetchColumn();
    
    // 3. Successfully Adopted Animals
    $total_adopted = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'Adopted'")->fetchColumn();
    
    // Fetch 3 featured pets (e.g., available pets)
    $stmt_pets = $pdo->prepare("SELECT * FROM pets WHERE status = 'Available' LIMIT 3");
    $stmt_pets->execute();
    $featured_pets = $stmt_pets->fetchAll();
    
    // Fallback if not enough available pets, show any
    if (count($featured_pets) < 3) {
        $stmt_all_pets = $pdo->prepare("SELECT * FROM pets LIMIT 3");
        $stmt_all_pets->execute();
        $featured_pets = $stmt_all_pets->fetchAll();
    }
} catch (PDOException $e) {
    // Basic fallback values on database error
    $total_rescued = 6;
    $total_vaccinated = 5;
    $total_adopted = 2;
    $featured_pets = [];
}
?>

<!-- Hero Section -->
<section class="hero-section container hero-with-bg">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <span class="hero-tag">🏡 Adopt a pet, but don't shop</span>
        <h1><span><?= htmlspecialchars($cms['homepage_hero_title']) ?></span></h1>
        <p><?= htmlspecialchars($cms['homepage_hero_desc']) ?></p>
        <div class="hero-buttons">
            <a href="adopt.php" class="btn btn-primary">Adopt Now</a>
            <a href="about.php" class="btn btn-secondary">Learn More</a>
        </div>
    </div>
</section>

<!-- Impact Metrics Dashboard -->
<section class="metrics-section">
    <div class="container">
        <div class="section-header">
            <h2>Our Adoption Journey</h2>
            <p>The lives we have changed together through our rescue and adoption program.</p>
        </div>
        <div class="metrics-grid">
            <div class="metric-card">
                <span class="metric-icon">🐾</span>
                <div class="metric-number"><?= $total_rescued ?></div>
                <div class="metric-label">Total Animals Rescued</div>
            </div>
            <div class="metric-card">
                <span class="metric-icon">🛡️</span>
                <div class="metric-number"><?= $total_vaccinated ?></div>
                <div class="metric-label">Total Vaccinated Animals</div>
            </div>
            <div class="metric-card">
                <span class="metric-icon">💖</span>
                <div class="metric-number"><?= $total_adopted ?></div>
                <div class="metric-label">Successfully Adopted</div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Showcase Section -->
<section class="pets-section container">
    <div class="section-header">
        <h2>Meet Our Lovable Pets</h2>
        <p>These lovely pets are still waiting for a home. Give them a chance and start your adoption journey.</p>
    </div>
    
    <div class="pets-grid">
        <?php foreach ($featured_pets as $pet): 
            $is_wishlisted = false;
            if (is_logged_in()) {
                // Check if this pet is in the user's wishlist
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
                    <div class="pet-actions" style="grid-template-columns: 1fr;">
                        <a href="adopt.php?learn_more_id=<?= $pet['id'] ?>" class="btn btn-secondary" style="width: 100%;">View Details & Adopt</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center;">
        <a href="adopt.php" class="btn btn-primary">View All Pets</a>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
