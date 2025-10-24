<?php
require_once 'includes/config.php';

// Get job ID
 $jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId <= 0) {
    redirect('jobs.php');
}

// Get job details
 $stmt = $pdo->prepare("
    SELECT j.*, c.name as company_name, c.logo as company_logo, c.description as company_description, 
           c.website, c.location as company_location, c.size as company_size 
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    WHERE j.id = ? AND j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at >= CURDATE())
");
 $stmt->execute([$jobId]);
 $job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    showMessage('error', 'Job not found');
    redirect('jobs.php');
}

// Check if job is saved
 $isSaved = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$_SESSION['user_id'], $jobId]);
    $isSaved = $stmt->fetchColumn() > 0;
}

// Check if already applied
 $hasApplied = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$_SESSION['user_id'], $jobId]);
    $hasApplied = $stmt->fetchColumn() > 0;
}

// Get similar jobs
 $stmt = $pdo->prepare("
    SELECT j.*, c.name as company_name 
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    WHERE j.category = ? AND j.id != ? AND j.is_active = 1 
    ORDER BY j.created_at DESC 
    LIMIT 4
");
 $stmt->execute([$job['category'], $jobId]);
 $similarJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $pageTitle = $job['title'];
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="jobs.php">Jobs</a>
            <span>/</span>
            <span><?php echo $job['title']; ?></span>
        </nav>
        
        <div class="job-detail">
            <div class="job-main">
                <div class="job-header">
                    <h1 class="job-title-detail"><?php echo $job['title']; ?></h1>
                    <div class="company-info">
                        <div class="company-logo">
                            <?php echo getInitials($job['company_name']); ?>
                        </div>
                        <div class="company-detail">
                            <h3><?php echo $job['company_name']; ?></h3>
                            <p><?php echo $job['company_location']; ?></p>
                        </div>
                    </div>
                    <div class="job-meta-detail">
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo $job['location']; ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-briefcase"></i>
                            <span><?php echo ucfirst($job['type']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Posted <?php echo timeAgo($job['created_at']); ?></span>
                        </div>
                    </div>
                    <div class="job-actions">
                        <?php if (isLoggedIn()): ?>
                            <?php if ($hasApplied): ?>
                                <button class="btn" disabled>Already Applied</button>
                            <?php else: ?>
                                <button class="btn apply-now-btn" id="apply-now-btn" data-job-id="<?php echo $job['id']; ?>">Apply Now</button>
                            <?php endif; ?>
                            <button class="btn btn-outline save-job <?php echo $isSaved ? 'saved' : ''; ?>" data-job-id="<?php echo $job['id']; ?>">
                                <i class="<?php echo $isSaved ? 'fas' : 'far'; ?> fa-bookmark"></i>
                                <?php echo $isSaved ? 'Saved' : 'Save Job'; ?>
                            </button>
                        <?php else: ?>
                            <a href="auth/login.php?redirect=job-detail.php?id=<?php echo $job['id']; ?>" class="btn">Login to Apply</a>
                            <button class="btn btn-outline save-job" data-job-id="<?php echo $job['id']; ?>">
                                <i class="far fa-bookmark"></i>
                                Save Job
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="job-content">
                    <div class="job-section">
                        <h3>Job Description</h3>
                        <p><?php echo nl2br($job['description']); ?></p>
                    </div>
                    
                    <div class="job-section">
                        <h3>Requirements</h3>
                        <p><?php echo nl2br($job['requirements']); ?></p>
                    </div>
                    
                    <div class="job-section">
                        <h3>Benefits</h3>
                        <p><?php echo nl2br($job['benefits']); ?></p>
                    </div>
                    
                    <div class="job-section">
                        <h3>About <?php echo $job['company_name']; ?></h3>
                        <p><?php echo $job['company_description']; ?></p>
                        <div class="company-meta">
                            <p><strong>Website:</strong> <a href="<?php echo $job['website']; ?>" target="_blank"><?php echo $job['website']; ?></a></p>
                            <p><strong>Size:</strong> <?php echo $job['company_size']; ?></p>
                            <p><strong>Location:</strong> <?php echo $job['company_location']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div id="application-form" style="display: none;">
                    <div class="application-form">
                        <h3>Apply for this Position</h3>
                        <form method="post" action="api/apply-job.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-group">
                                <label for="cover_letter" class="form-label">Cover Letter</label>
                                <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5" required>Tell us why you're interested in this position and why you'd be a great fit.</textarea>
                                <div class="invalid-feedback">Please enter a cover letter.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="resume" class="form-label">Resume (PDF or DOC)</label>
                                <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                                <div class="invalid-feedback">Please upload your resume.</div>
                            </div>
                            
                            <button type="submit" class="btn">Submit Application</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="job-sidebar">
                <div class="job-summary">
                    <h3>Job Summary</h3>
                    <div class="summary-row">
                        <span class="summary-label">Job Title</span>
                        <span class="summary-value"><?php echo $job['title']; ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Company</span>
                        <span class="summary-value"><?php echo $job['company_name']; ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Location</span>
                        <span class="summary-value"><?php echo $job['location']; ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Job Type</span>
                        <span class="summary-value"><?php echo ucfirst($job['type']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Salary</span>
                        <span class="summary-value"><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Posted</span>
                        <span class="summary-value"><?php echo timeAgo($job['created_at']); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($similarJobs)): ?>
                    <div class="job-summary">
                        <h3>Similar Jobs</h3>
                        <?php foreach ($similarJobs as $similarJob): ?>
                            <div style="padding: 15px 0; border-bottom: 1px solid var(--border-color);">
                                <h4 style="margin-bottom: 5px;">
                                    <a href="job-detail.php?id=<?php echo $similarJob['id']; ?>" style="color: var(--text-color); font-size: 16px;">
                                        <?php echo $similarJob['title']; ?>
                                    </a>
                                </h4>
                                <p style="margin: 0; color: var(--light-text); font-size: 14px;"><?php echo $similarJob['company_name']; ?></p>
                                <p style="margin: 5px 0 0; color: var(--primary-color); font-weight: 500;"><?php echo formatSalary($similarJob['salary_min'], $similarJob['salary_max']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>