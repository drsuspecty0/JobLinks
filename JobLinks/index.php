<?php
require_once 'includes/config.php';

 $pageTitle = 'Home';
require_once 'includes/header.php';

// Get featured jobs
 $stmt = $pdo->query("
    SELECT j.*, c.name as company_name, c.logo as company_logo 
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    WHERE j.is_active = 1 AND j.featured = 1 
    ORDER BY j.created_at DESC 
    LIMIT 6
");
 $featuredJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories with job counts
 $stmt = $pdo->query("
    SELECT cat.*, COUNT(j.id) as job_count 
    FROM categories cat 
    LEFT JOIN jobs j ON cat.name = j.category AND j.is_active = 1 
    GROUP BY cat.id 
    ORDER BY job_count DESC 
    LIMIT 8
");
 $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get testimonials
 $testimonials = [
    [
        'content' => 'JobLinks helped me find my dream job in just two weeks. The platform is easy to use and has great opportunities.',
        'author' => 'Sarah Johnson',
        'position' => 'Marketing Manager',
        'company' => 'TechCorp'
    ],
    [
        'content' => 'As an employer, JobLinks has been invaluable for finding qualified candidates. The quality of applicants is exceptional.',
        'author' => 'Michael Chen',
        'position' => 'HR Director',
        'company' => 'Digital Marketing Pro'
    ],
    [
        'content' => 'I landed my first job through JobLinks right after graduation. The career resources were incredibly helpful.',
        'author' => 'Emily Rodriguez',
        'position' => 'Junior Developer',
        'company' => 'StartupXYZ'
    ]
];
?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Find Your Dream Job</h1>
            <p>Connect with top companies and discover opportunities that match your skills and aspirations</p>
            <div class="hero-buttons">
                <a href="jobs.php" class="btn">Browse Jobs</a>
                <a href="auth/register.php" class="btn btn-outline">Create Account</a>
            </div>
        </div>
    </div>
</section>

<section class="hero-search">
    <div class="container">
        <form class="hero-search-form" action="jobs.php" method="get">
            <div class="form-group">
                <label for="search" class="form-label">What</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Job title, keywords, or company">
            </div>
            <div class="form-group">
                <label for="location" class="form-label">Where</label>
                <input type="text" class="form-control" id="location" name="location" placeholder="City, state, or remote">
            </div>
            <button type="submit" class="btn">Search Jobs</button>
        </form>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Featured Jobs</h2>
            <p>Hand-picked opportunities from top companies</p>
        </div>
        <div class="featured-jobs">
            <?php foreach ($featuredJobs as $job): ?>
                <div class="job-card">
                    <div class="job-card-header">
                        <div>
                            <h3 class="job-title">
                                <a href="job-detail.php?id=<?php echo $job['id']; ?>"><?php echo $job['title']; ?></a>
                            </h3>
                            <p class="company-name"><?php echo $job['company_name']; ?></p>
                        </div>
                        <span class="job-type"><?php echo $job['type']; ?></span>
                    </div>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo $job['location']; ?></span>
                        <span><i class="fas fa-money-bill-wave"></i> <?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                    </div>
                    <p class="job-description"><?php echo substr($job['description'], 0, 150); ?>...</p>
                    <div class="job-card-footer">
                        <span class="job-salary"><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                        <button class="save-job" data-job-id="<?php echo $job['id']; ?>">
                            <i class="far fa-bookmark"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="jobs.php" class="btn">View All Jobs</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Popular Categories</h2>
            <p>Explore jobs by category</p>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-<?php echo getCategoryIcon($category['name']); ?>"></i>
                    </div>
                    <h3 class="category-name"><?php echo $category['name']; ?></h3>
                    <p class="category-count"><?php echo $category['job_count']; ?> Jobs</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="testimonials">
    <div class="container">
        <div class="section-title">
            <h2>Success Stories</h2>
            <p>What our users say about JobLinks</p>
        </div>
        <div class="testimonial-grid">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <p class="testimonial-content">"<?php echo $testimonial['content']; ?>"</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><?php echo getInitials($testimonial['author']); ?></div>
                        <div class="testimonial-info">
                            <h4><?php echo $testimonial['author']; ?></h4>
                            <p><?php echo $testimonial['position']; ?> at <?php echo $testimonial['company']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Find your next career opportunity in 3 simple steps</p>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Search Jobs</h3>
                    <p>Browse through thousands of job listings from top companies</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Apply Easily</h3>
                    <p>Submit your application with just a few clicks</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>Get Hired</h3>
                    <p>Connect with employers and land your dream job</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>