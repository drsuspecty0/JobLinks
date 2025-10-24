<?php
require_once 'includes/config.php';

 $pageTitle = 'About Us';
require_once 'includes/header.php';

// Get team members
 $teamMembers = [
    [
        'name' => 'Sarah Johnson',
        'position' => 'CEO & Founder',
        'bio' => 'With over 15 years of experience in HR and tech, Sarah founded JobLinks to bridge the gap between talented professionals and great companies.',
        'image' => 'team-1.jpg',
        'linkedin' => '#',
        'twitter' => '#'
    ],
    [
        'name' => 'Michael Chen',
        'position' => 'CTO',
        'bio' => 'Michael leads our technical team with expertise in building scalable platforms that connect millions of job seekers with opportunities.',
        'image' => 'team-2.jpg',
        'linkedin' => '#',
        'twitter' => '#'
    ],
    [
        'name' => 'Emily Rodriguez',
        'position' => 'Head of Marketing',
        'bio' => 'Emily drives our mission to help people find meaningful work through innovative marketing strategies and user-centric design.',
        'image' => 'team-3.jpg',
        'linkedin' => '#',
        'twitter' => '#'
    ],
    [
        'name' => 'David Kim',
        'position' => 'VP of Partnerships',
        'bio' => 'David builds relationships with top companies worldwide to ensure JobLinks offers the best job opportunities available.',
        'image' => 'team-4.jpg',
        'linkedin' => '#',
        'twitter' => '#'
    ]
];

// Get statistics
 $totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE is_active = 1")->fetchColumn();
 $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
 $totalCompanies = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
 $totalApplications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
?>

<section class="about-hero">
    <div class="container">
        <div class="hero-content">
            <h1>About JobLinks</h1>
            <p>Empowering careers and connecting talent with opportunity since 2020</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="about-content">
            <div class="about-text">
                <h2>Our Mission</h2>
                <p>JobLinks is dedicated to transforming the way people find jobs and companies hire talent. We believe that the right job can change lives, and the right talent can transform businesses.</p>
                <p>Our platform uses cutting-edge technology to make the job search process more efficient, transparent, and rewarding for everyone involved.</p>
            </div>
            <div class="about-image">
                <img src="assets/images/ui/about-hero.jpg" alt="About JobLinks">
            </div>
        </div>
        
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($totalJobs); ?>+</div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($totalUsers); ?>+</div>
                    <div class="stat-label">Job Seekers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($totalCompanies); ?>+</div>
                    <div class="stat-label">Companies</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($totalApplications); ?>+</div>
                    <div class="stat-label">Applications</div>
                </div>
            </div>
        </div>
        
        <div class="values-section">
            <h2>Our Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>People First</h3>
                    <p>We prioritize the needs and experiences of both job seekers and employers in everything we do.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We continuously innovate to improve the job search and hiring process using technology and creativity.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>We operate with transparency and honesty, building trust with our users and partners.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Passion</h3>
                    <p>We are passionate about helping people find meaningful work and companies build great teams.</p>
                </div>
            </div>
        </div>
        
        <div class="team-section">
            <h2>Meet Our Team</h2>
            <div class="team-grid">
                <?php foreach ($teamMembers as $member): ?>
                    <div class="team-member">
                        <div class="member-photo">
                            <img src="assets/images/ui/<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>">
                        </div>
                        <div class="member-info">
                            <h3><?php echo $member['name']; ?></h3>
                            <p class="member-position"><?php echo $member['position']; ?></p>
                            <p class="member-bio"><?php echo $member['bio']; ?></p>
                            <div class="member-social">
                                <?php if ($member['linkedin'] !== '#'): ?>
                                    <a href="<?php echo $member['linkedin']; ?>" target="_blank">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($member['twitter'] !== '#'): ?>
                                    <a href="<?php echo $member['twitter']; ?>" target="_blank">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<style>
.about-hero {
    background: linear-gradient(135deg, var(--primary-color), #1e40af);
    color: white;
    padding: 100px 0;
    position: relative;
    overflow: hidden;
}

.about-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('assets/images/ui/hero-pattern.svg') no-repeat center;
    background-size: cover;
    opacity: 0.1;
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.about-hero h1 {
    font-size: 48px;
    margin-bottom: 20px;
    color: white;
}

.about-hero p {
    font-size: 20px;
    margin-bottom: 30px;
    opacity: 0.9;
}

.about-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    margin-bottom: 60px;
}

.about-text h2 {
    font-size: 36px;
    margin-bottom: 20px;
}

.about-text p {
    font-size: 16px;
    line-height: 1.8;
    margin-bottom: 20px;
}

.about-image img {
    width: 100%;
    height: auto;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.stats-section {
    margin-bottom: 60px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
}

.stat-item {
    text-align: center;
    padding: 30px;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.dark .stat-item {
    background-color: var(--dark-secondary);
}

.stat-number {
    font-size: 36px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.stat-label {
    font-size: 16px;
    color: var(--light-text);
}

.dark .stat-label {
    color: var(--dark-light-text);
}

.values-section {
    margin-bottom: 60px;
}

.values-section h2 {
    text-align: center;
    font-size: 36px;
    margin-bottom: 40px;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.value-card {
    text-align: center;
    padding: 30px;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
}

.dark .value-card {
    background-color: var(--dark-secondary);
}

.value-card:hover {
    transform: translateY(-5px);
}

.value-icon {
    width: 80px;
    height: 80px;
    background-color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 32px;
    color: white;
}

.value-card h3 {
    font-size: 20px;
    margin-bottom: 15px;
}

.value-card p {
    color: var(--light-text);
    line-height: 1.6;
}

.dark .value-card p {
    color: var(--dark-light-text);
}

.team-section h2 {
    text-align: center;
    font-size: 36px;
    margin-bottom: 40px;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 40px;
}

.team-member {
    background-color: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
}

.dark .team-member {
    background-color: var(--dark-secondary);
}

.member-photo {
    height: 250px;
    overflow: hidden;
}

.member-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.member-info {
    padding: 30px;
}

.member-info h3 {
    font-size: 20px;
    margin-bottom: 5px;
}

.member-position {
    color: var(--primary-color);
    font-weight: 500;
    margin-bottom: 15px;
}

.member-bio {
    color: var(--light-text);
    line-height: 1.6;
    margin-bottom: 20px;
}

.dark .member-bio {
    color: var(--dark-light-text);
}

.member-social {
    display: flex;
    gap: 15px;
}

.member-social a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background-color: var(--secondary-color);
    border-radius: 50%;
    color: var(--text-color);
    transition: var(--transition);
}

.dark .member-social a {
    background-color: var(--dark-bg);
    color: var(--dark-text);
}

.member-social a:hover {
    background-color: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .about-content {
        grid-template-columns: 1fr;
    }
    
    .about-hero h1 {
        font-size: 36px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .values-grid {
        grid-template-columns: 1fr;
    }
    
    .team-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>