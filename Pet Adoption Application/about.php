<?php
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

// Retrieve About Us settings from CMS
$about = [
    'about_mission' => 'To connect loving families with pets in need, ensuring every animal receives care, shelter, and a second chance at life.',
    'about_history' => 'Founded in 2020, Petopia began as a small shelter with only ten rescue kennels. Over the years, through community support and passionate volunteers, we have grown to be one of the region\'s largest rescue centers, helping over 500 animals find their forever homes.',
    'about_values' => 'Compassion, Integrity, Respect, Dedication, Community'
];

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM cms_content WHERE meta_key IN ('about_mission', 'about_history', 'about_values')");
        while ($row = $stmt->fetch()) {
            $about[$row['meta_key']] = $row['meta_value'];
        }
    } catch (PDOException $e) { }
}

$values_array = explode(',', $about['about_values']);

// Employee list
$employees = [
    [
        'name' => 'Danial bin Razak ',
        'role' => 'Lead Veterinarian & Director',
        'experience' => '12 Years in Veterinary Medicine',
         'avatar' => 'uploads/Razak.jpg',
        'desc' => 'Danial bin Razak oversees the medical team, ensuring every rescued animal is vaccinated, neutered, and healthy.'
    ],
    [
        'name' => 'Tay Mei Yee',
        'role' => 'Animal Behaviorist',
        'experience' => '8 Years in Canine Behavior',
        'avatar' => 'uploads/MeiYee.jpg',
        'desc' => 'Mei Yee works one-on-one with our rescue dogs to build confidence, sociability, and basic training skills.'
    ],
    [
        'name' => 'Kavitha a/p Subramaniam',
        'role' => 'Adoption Coordinator',
        'experience' => '5 Years in Shelter Services',
        'avatar' => 'uploads/Kavitha.jpg',
        'desc' => 'Kavitha manages applications, matching families with pets to ensure lifelong successful placements.'
    ]
];
?>

<div class="container" style="padding: 60px 24px;">
    <!-- Mission and History -->
    <div class="section-header">
        <h2>About Petopia</h2>
        <p>Learn more about our history, our dedicated rescue team, and the core values that drive us.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 50px; margin-bottom: 60px; align-items: center;">
        <div>
            <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 15px;">Our History & Journey</h3>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--macaron-text-main); margin-bottom: 20px; text-align: justify;">
                <?= nl2br(htmlspecialchars($about['about_history'])) ?>
            </p>
            
            <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 15px;">Our Mission</h3>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--macaron-text-main); font-style: italic; border-left: 4px solid var(--macaron-pink-primary); padding-left: 20px;">
                "<?= htmlspecialchars($about['about_mission']) ?>"
            </p>
        </div>
        
        <div style="background-color: #ffffff; border: 1px solid var(--macaron-border); border-radius: var(--radius-lg); padding: 40px; box-shadow: var(--shadow-sm); text-align: center;">
            <span style="font-size: 3.5rem; display: block; margin-bottom: 15px;">❤️</span>
            <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 15px;">Get in Touch</h3>
            <p style="color: var(--macaron-text-muted); margin-bottom: 30px;">Have questions about our center or volunteering? Feel free to contact us!</p>
            <a href="services.php" class="btn btn-primary" style="width: 100%;">Contact Us</a>
        </div>
    </div>

    <!-- Core Values -->
    <div style="background: linear-gradient(135deg, var(--macaron-pink-light) 0%, #ffdee4 100%); border-radius: var(--radius-lg); padding: 50px; text-align: center; margin-bottom: 60px; border: 1px solid var(--macaron-border);">
        <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--macaron-pink-primary); margin-bottom: 30px;">Our Core Values</h3>
        <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
            <?php foreach ($values_array as $value): if(trim($value) == '') continue; ?>
                <div style="background-color: #ffffff; padding: 12px 28px; border-radius: 50px; font-weight: 700; color: var(--macaron-pink-primary); border: 1px solid var(--macaron-border); box-shadow: var(--shadow-sm);">
                    ✨ <?= htmlspecialchars(trim($value)) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Team Profiles -->
    <div>
        <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--macaron-pink-primary); text-align: center; margin-bottom: 40px;">Meet Our Specialists</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
            <?php foreach ($employees as $emp): ?>
                <div style="background-color: #ffffff; border: 1px solid var(--macaron-border); border-radius: var(--radius-lg); padding: 30px; text-align: center; box-shadow: var(--shadow-sm); transition: var(--transition-smooth);" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='none';">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background-color: var(--macaron-pink-light); overflow: hidden; margin: 0 auto 20px; border: 1px solid var(--macaron-border);">
                        <img src="<?= htmlspecialchars($emp['avatar']) ?>" alt="<?= htmlspecialchars($emp['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h4 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 4px;"><?= htmlspecialchars($emp['name']) ?></h4>
                    <p style="font-size: 0.9rem; font-weight: 700; color: var(--macaron-pink-primary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($emp['role']) ?></p>
                    <p style="font-size: 0.85rem; font-weight: 600; color: var(--macaron-text-muted); margin-bottom: 15px;"><?= htmlspecialchars($emp['experience']) ?></p>
                    <p style="font-size: 0.95rem; line-height: 1.6; color: var(--macaron-text-main);"><?= htmlspecialchars($emp['desc']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
