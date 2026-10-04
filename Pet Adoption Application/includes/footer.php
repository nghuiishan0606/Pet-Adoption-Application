
<?php
// Retrieve contact and social settings from CMS
$cms_footer = [
    'contact_address' => 'Pekan Batu 14 Hulu Langat, 43100 Hulu Langat, Selangor',
    'contact_phone' => '+ 012-919 2263',
    'contact_email' => 'support@petopia.com',
    'contact_facebook' => 'https://facebook.com/petopia',
    'contact_instagram' => 'https://instagram.com/petopia'
];

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM cms_content WHERE meta_key LIKE 'contact_%'");
        while ($row = $stmt->fetch()) {
            $cms_footer[$row['meta_key']] = $row['meta_value'];
        }
    } catch (PDOException $e) {
        // Fail silently
    }
}
?>
    <!-- Footer CTA Section -->
    <?php if (basename($_SERVER['PHP_SELF']) !== 'services.php'): ?>
    <section class="footer-cta">
        <div class="container">
            <h3>Have Questions or Need Help?</h3>
            <p>Our dedicated customer service team is here to support you in every step of your adoption journey.</p>
            <a href="services.php" class="btn btn-primary">Contact Customer Service</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer Main Section -->
    <footer class="footer-main">
        <div class="container footer-container">
            <div>
                <p style="font-weight: 700; color: var(--macaron-pink-primary); margin-bottom: 5px;">🐾 Petopia Rescue Center</p>
                <p style="font-size: 0.9rem; max-width: 320px;"><?= htmlspecialchars($cms_footer['contact_address']) ?></p>
            </div>
            
            <div style="text-align: center;">
                <p>Phone: <?= htmlspecialchars($cms_footer['contact_phone']) ?></p>
                <p>Email: <a href="mailto:<?= htmlspecialchars($cms_footer['contact_email']) ?>" style="text-decoration: underline;"><?= htmlspecialchars($cms_footer['contact_email']) ?></a></p>
            </div>
            
            <div>
                <div class="social-links" style="margin-bottom: 8px;">
                    <a href="<?= htmlspecialchars($cms_footer['contact_facebook']) ?>" target="_blank" class="social-icon-btn" title="Facebook">FB</a>
                    <a href="<?= htmlspecialchars($cms_footer['contact_instagram']) ?>" target="_blank" class="social-icon-btn" title="Instagram">IG</a>
                </div>
                <p style="font-size: 0.85rem;">&copy; <?= date('Y') ?> Petopia. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Global scripts -->
    <script src="js/main.js"></script>
</body>
</html>
