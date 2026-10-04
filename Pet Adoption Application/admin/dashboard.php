<?php
require_once 'includes/admin_header.php';

// Fetch Dashboard Metrics
try {
    // 1. Total Animals
    $total_animals = $pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
    $available_animals = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'Available'")->fetchColumn();
    
    // 2. Adoptions Pending
    $pending_adoptions = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")->fetchColumn();
    $total_adoptions_submitted = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    
    // 3. Successful Matches
    $adopted_animals = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'Adopted'")->fetchColumn();
    
    // 4. Contact Messages
    $total_inquiries = $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
    $unread_inquiries = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE admin_reply IS NULL")->fetchColumn();
    
    // 5. Category Trends (Dog, Cat, Rabbit, Bird)
    $dog_count = $pdo->query("SELECT COUNT(*) FROM pets WHERE type = 'Dog'")->fetchColumn();
    $cat_count = $pdo->query("SELECT COUNT(*) FROM pets WHERE type = 'Cat'")->fetchColumn();
    $rabbit_count = $pdo->query("SELECT COUNT(*) FROM pets WHERE type = 'Rabbit'")->fetchColumn();
    $bird_count = $pdo->query("SELECT COUNT(*) FROM pets WHERE type = 'Bird'")->fetchColumn();
    
    $dog_pct = $total_animals > 0 ? round(($dog_count / $total_animals) * 100) : 0;
    $cat_pct = $total_animals > 0 ? round(($cat_count / $total_animals) * 100) : 0;
    $rabbit_pct = $total_animals > 0 ? round(($rabbit_count / $total_animals) * 100) : 0;
    $bird_pct = $total_animals > 0 ? round(($bird_count / $total_animals) * 100) : 0;
    
    // 6. Adoption Application Stats
    $app_approved = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Approved'")->fetchColumn();
    $app_pending = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")->fetchColumn();
    $app_rejected = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Rejected'")->fetchColumn();
    
    $app_approved_pct = $total_adoptions_submitted > 0 ? round(($app_approved / $total_adoptions_submitted) * 100) : 0;
    $app_pending_pct = $total_adoptions_submitted > 0 ? round(($app_pending / $total_adoptions_submitted) * 100) : 0;
    $app_rejected_pct = $total_adoptions_submitted > 0 ? round(($app_rejected / $total_adoptions_submitted) * 100) : 0;
    
    // 7. Recent Applications list (Latest 4)
    $stmt_rec_apps = $pdo->query("SELECT a.*, p.name AS pet_name FROM applications a JOIN pets p ON a.pet_id = p.id ORDER BY a.id DESC LIMIT 4");
    $recent_applications = $stmt_rec_apps->fetchAll();
    
    // 8. Recent Inquiries list (Latest 4)
    $stmt_rec_inqs = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC LIMIT 4");
    $recent_inquiries = $stmt_rec_inqs->fetchAll();
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!-- Analytics Metrics Cards -->
<section class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon-wrap icon-blue">🐾</div>
        <div class="admin-stat-info">
            <div class="admin-stat-label">Total Animals</div>
            <div class="admin-stat-number"><?= $total_animals ?></div>
            <div class="admin-stat-sub"><strong><?= $available_animals ?></strong> Available</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon-wrap icon-pink">📄</div>
        <div class="admin-stat-info">
            <div class="admin-stat-label">Adoptions Pending</div>
            <div class="admin-stat-number"><?= $pending_adoptions ?></div>
            <div class="admin-stat-sub"><strong><?= $total_adoptions_submitted ?></strong> Total Submitted</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon-wrap icon-green">✔️</div>
        <div class="admin-stat-info">
            <div class="admin-stat-label">Successful Matches</div>
            <div class="admin-stat-number"><?= $adopted_animals ?></div>
            <div class="admin-stat-sub"><strong><?= $adopted_animals ?></strong> Adopted Pets</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon-wrap icon-orange">💬</div>
        <div class="admin-stat-info">
            <div class="admin-stat-label">Contact Messages</div>
            <div class="admin-stat-number"><?= $total_inquiries ?></div>
            <div class="admin-stat-sub"><strong><?= $unread_inquiries ?></strong> Unread Messages</div>
        </div>
    </div>
</section>

<!-- Relational Trends Section (Screenshot 4) -->
<section class="admin-charts-grid">
    <!-- Pet Category Trends Chart -->
    <div class="chart-card">
        <h3 class="chart-card-title">🌸 Pet Category Trends</h3>
        <div class="css-bar-chart">
            <div class="bar-row">
                <div class="bar-label-val">
                    <span>Dogs</span>
                    <span><?= $dog_count ?> (<?= $dog_pct ?>%)</span>
                </div>
                <div class="bar-container">
                    <div class="bar-fill fill-pink" style="width: <?= $dog_pct ?>%;"></div>
                </div>
            </div>
            
            <div class="bar-row">
                <div class="bar-label-val">
                    <span>Cats</span>
                    <span><?= $cat_count ?> (<?= $cat_pct ?>%)</span>
                </div>
                <div class="bar-container">
                    <div class="bar-fill fill-pink" style="width: <?= $cat_pct ?>%;"></div>
                </div>
            </div>
            
            <div class="bar-row">
                <div class="bar-label-val">
                    <span>Rabbits</span>
                    <span><?= $rabbit_count ?> (<?= $rabbit_pct ?>%)</span>
                </div>
                <div class="bar-container">
                    <div class="bar-fill fill-pink" style="width: <?= $rabbit_pct ?>%;"></div>
                </div>
            </div>
            
            <?php if ($bird_count > 0): ?>
            <div class="bar-row">
                <div class="bar-label-val">
                    <span>Birds</span>
                    <span><?= $bird_count ?> (<?= $bird_pct ?>%)</span>
                </div>
                <div class="bar-container">
                    <div class="bar-fill fill-pink" style="width: <?= $bird_pct ?>%;"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Adoption Application Stats -->
    <div class="chart-card">
        <h3 class="chart-card-title">📊 Adoption Application Stats</h3>
        <div class="css-bar-chart">
            <div class="bar-row">
                <div class="bar-label-val">
                    <span>Approved</span>
                    <span><?= $app_approved ?> (<?= $app_approved_pct ?>%)</span>
                </div>
                <div class="bar-container">
                    <div class="bar-fill fill-green" style="width: <?= $app_approved_pct ?>%;"></div>
                </div>
            </div>
            
            <div class="bar-row">
                <div class="bar-label-val">
                    <span>Pending Review</span>
                    <span><?= $app_pending ?> (<?= $app_pending_pct ?>%)</span>
                </div>
                <div class="bar-container">
                    <div class="bar-fill fill-orange" style="width: <?= $app_pending_pct ?>%;"></div>
                </div>
            </div>
            
            <div class="bar-row">
                <div class="bar-label-val">
                    <span>Rejected / Cancelled</span>
                    <span><?= $app_rejected ?> (<?= $app_rejected_pct ?>%)</span>
                </div>
                <div class="bar-container">
                    <div class="bar-fill fill-red" style="width: <?= $app_rejected_pct ?>%;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Lists Section -->
<section class="admin-charts-grid" style="margin-bottom: 0;">
    <!-- Recent Applications -->
    <div class="chart-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="chart-card-title" style="margin-bottom: 0;">Recent Applications</h3>
            <a href="applications.php" style="color:var(--admin-blue-primary); font-weight:700; font-size:0.9rem;">Manage</a>
        </div>
        
        <div class="recent-card-list">
            <?php if (count($recent_applications) === 0): ?>
                <div style="text-align: center; color: var(--admin-text-muted); padding: 20px 0;">No recent applications.</div>
            <?php else: ?>
                <?php foreach ($recent_applications as $app): 
                    $badge = 'status-active'; // pending
                    if ($app['status'] === 'Approved') $badge = 'role-admin'; // green-ish
                    elseif ($app['status'] === 'Rejected') $badge = 'status-blocked'; // grey-ish
                ?>
                    <div class="recent-card-item">
                        <div class="recent-card-info">
                            <h5><?= htmlspecialchars($app['name']) ?> &rarr; <?= htmlspecialchars($app['pet_name']) ?></h5>
                            <p>Submitted on <?= date('m/d/Y', strtotime($app['created_at'])) ?></p>
                        </div>
                        <span class="status-badge <?= $app['status'] === 'Pending' ? 'status-active' : ($app['status'] === 'Approved' ? 'role-admin' : 'status-blocked') ?>" style="padding: 2px 8px; font-size:0.75rem;">
                            <?= htmlspecialchars($app['status']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Inquiries -->
    <div class="chart-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="chart-card-title" style="margin-bottom: 0;">Recent Inquiries</h3>
            <a href="inquiries.php" style="color:var(--admin-blue-primary); font-weight:700; font-size:0.9rem;">View</a>
        </div>
        
        <div class="recent-card-list">
            <?php if (count($recent_inquiries) === 0): ?>
                <div style="text-align: center; color: var(--admin-text-muted); padding: 20px 0;">No recent inquiries.</div>
            <?php else: ?>
                <?php foreach ($recent_inquiries as $inq): ?>
                    <div class="recent-card-item">
                        <div class="recent-card-info">
                            <h5 style="font-weight: 500; font-style:italic;">"<?= htmlspecialchars(substr($inq['message'], 0, 32)) ?>..."</h5>
                            <p>From: <strong><?= htmlspecialchars($inq['name']) ?></strong> (<?= htmlspecialchars($inq['email']) ?>)</p>
                        </div>
                        <span class="status-badge <?= $inq['admin_reply'] === null ? 'status-active' : 'status-blocked' ?>" style="padding: 2px 8px; font-size:0.75rem;">
                            <?= $inq['admin_reply'] === null ? 'Unread' : 'Replied' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once 'includes/admin_footer.php';
?>
