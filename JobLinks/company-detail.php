<?php
require_once 'includes/config.php';

// Get company ID
 $companyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($companyId <= 0) {
    redirect('companies.php');
}

// Get company details
 $stmt = $pdo->prepare("
    SELECT c.*, u.name as owner_name, u.email as owner_email,
           (SELECT COUNT(*) FROM jobs WHERE company_id = c.id AND is_active = 1) as active_jobs_count
    FROM companies c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
 $stmt->execute([$companyId]);
 $company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    showMessage('error', 'Company not found');
    redirect('companies.php');
}

// Get company jobs
 $stmt = $pdo->prepare("
    SELECT * FROM jobs 
    WHERE company_id = ? AND is_active = 1 
    ORDER BY created_at DESC 
    LIMIT 6
");
 $stmt->execute([$companyId]);
 $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $pageTitle = $company['name'];
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="companies.php">Companies</a>
            <span>/</span>
            <span><?php echo $company['name']; ?></span>
        </nav>
        
        <div class="company-detail">
            <div class="company-header">
                <div class="company-logo-large">
                    <?php echo getInitials($company['name']); ?>
                </div>
                <div class="company-info-detail">
                    <h1><?php echo $company['name']; ?></h1>
                    <p class="company-meta">
                        <span><i class="fas fa-industry"></i> <?php echo $company['industry']; ?></span>
                        <span><i class="fas fa-users"></i> <?php echo $company['size']; ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo $company['location']; ?></span>
                        <span><i class="fas fa-calendar"></i> Founded <?php echo $company['founded_year']; ?></span>
                    </p>
                    <div class="company-actions">
                        <a href="<?php echo $company['website']; ?>" target="_blank" class="btn btn-outline">
                            <i class="fas fa-external-link-alt"></i> Visit Website
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="company-content">
                <div class="company-tabs">
                    <button class="tab-btn active" data-tab="about">About</button>
                    <button class="tab-btn" data-tab="jobs">Jobs</button>
                </div>
                
                <div class="tab-content active" id="about">
                    <h3>About <?php echo $company['name']; ?></h3>
                    <p><?php echo nl2br($company['description']); ?></p>
                </div>
                
                <div class="tab-content" id="jobs">
                    <h3>Open Positions at <?php echo $company['name']; ?></h3>
                    <?php if (empty($jobs)): ?>
                        <p>No open positions at this time.</p>
                    <?php else: ?>
                        <div class="jobs-list">
                            <?php foreach ($jobs as $job): ?>
                                <div class="job-card">
                                    <div class="job-card-header">
                                        <div>
                                            <h4 class="job-title">
                                                <a href="job-detail.php?id=<?php echo $job['id']; ?>"><?php echo $job['title']; ?></a>
                                            </h4>
                                            <p class="company-name"><?php echo $company['name']; ?></p>
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
                                        <a href="job-detail.php?id=<?php echo $job['id']; ?>" class="btn btn-sm">View Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="jobs.php?company=<?php echo $company['id']; ?>" class="btn">View All Jobs</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.company-detail {
    display: grid;
    grid-template-columns: 1fr;
    gap: 40px;
}

.company-header {
    display: flex;
    align-items: center;
    gap: 30px;
    margin-bottom: 40px;
    padding: 30px;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.dark .company-header {
    background-color: var(--dark-secondary);
}

.company-logo-large {
    width: 120px;
    height: 120px;
    border-radius: var(--border-radius);
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
}

.company-info-detail h1 {
    font-size: 36px;
    margin-bottom: 15px;
}

.company-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.company-meta span {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--light-text);
}

.dark .company-meta span {
    color: var(--dark-light-text);
}

.company-meta i {
    font-size: 16px;
}

.company-content {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
}

.dark .company-content {
    background-color: var(--dark-secondary);
}

.company-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 30px;
}

.dark .company-tabs {
    border-bottom-color: var(--dark-border);
}

.tab-btn {
    padding: 15px 20px;
    background: none;
    border: none;
    font-size: 16px;
    font-weight: 500;
    color: var(--light-text);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: var(--transition);
}

.dark .tab-btn {
    color: var(--dark-light-text);
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.jobs-list {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .company-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .company-logo-large {
        width: 80px;
        height: 80px;
        font-size: 32px;
    }
    
    .company-info-detail h1 {
        font-size: 28px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Update active button
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update active content
            tabContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === tabId) {
                    content.classList.add('active');
                }
            });
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>