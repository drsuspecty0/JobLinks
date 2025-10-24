<?php
require_once 'includes/config.php';

// Check if user is logged in and is employer
if (!isLoggedIn() || !isEmployer()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

 $pageTitle = 'My Jobs';
require_once 'includes/header.php';

// Get filter parameters
 $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Get user's company
 $stmt = $pdo->prepare("SELECT id FROM companies WHERE user_id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    showMessage('info', 'Please create a company profile first');
    redirect('post-company.php');
}

// Build query
 $query = "
    SELECT j.*, 
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count
    FROM jobs j
    WHERE j.company_id = ?
";
 $params = [$company['id']];

if ($status === 'active') {
    $query .= " AND j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at >= CURDATE())";
} elseif ($status === 'inactive') {
    $query .= " AND j.is_active = 0";
} elseif ($status === 'expired') {
    $query .= " AND j.expires_at < CURDATE()";
}

 $query .= " ORDER BY j.created_at DESC";

// Get paginated results
 $result = paginate($query, $page, 10);
 $jobs = $result['items'];
 $pagination = $result['pagination'];

// Get status counts
 $stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN expires_at < CURDATE() THEN 1 ELSE 0 END) as expired
    FROM jobs
    WHERE company_id = ?
");
 $stmt->execute([$company['id']]);
 $statusCounts = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>My Jobs</h2>
            <a href="post-job.php" class="btn">Post New Job</a>
        </div>
        
        <div class="job-filters">
            <div class="filter-tabs">
                <a href="my-jobs.php" class="<?php echo empty($status) ? 'active' : ''; ?>">All</a>
                <a href="?status=active" class="<?php echo $status === 'active' ? 'active' : ''; ?>">
                    Active (<?php echo $statusCounts['active']; ?>)
                </a>
                <a href="?status=inactive" class="<?php echo $status === 'inactive' ? 'active' : ''; ?>">
                    Inactive (<?php echo $statusCounts['inactive']; ?>)
                </a>
                <a href="?status=expired" class="<?php echo $status === 'expired' ? 'active' : ''; ?>">
                    Expired (<?php echo $statusCounts['expired']; ?>)
                </a>
            </div>
        </div>
        
        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3>No Jobs Posted Yet</h3>
                <p>Start by posting your first job opening to attract qualified candidates.</p>
                <a href="post-job.php" class="btn">Post a Job</a>
            </div>
        <?php else: ?>
            <div class="jobs-grid">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-card-header">
                            <div>
                                <h3 class="job-title">
                                    <a href="job-detail.php?id=<?php echo $job['id']; ?>"><?php echo $job['title']; ?></a>
                                </h3>
                                <p class="company-name">Your Company</p>
                            </div>
                            <div class="job-status">
                                <?php
                                $isExpired = $job['expires_at'] && $job['expires_at'] < date('Y-m-d');
                                if ($isExpired): ?>
                                    <span class="status-badge status-expired">Expired</span>
                                <?php elseif ($job['is_active']): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="job-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo $job['location']; ?></span>
                            <span><i class="fas fa-briefcase"></i> <?php echo ucfirst($job['type']); ?></span>
                            <span><i class="fas fa-money-bill-wave"></i> <?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                        </div>
                        
                        <p class="job-description"><?php echo substr($job['description'], 0, 150); ?>...</p>
                        
                        <div class="job-stats">
                            <span><i class="fas fa-users"></i> <?php echo $job['application_count']; ?> Applications</span>
                            <span><i class="fas fa-eye"></i> <?php echo rand(50, 500); ?> Views</span>
                        </div>
                        
                        <div class="job-card-footer">
                            <span class="job-salary"><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                            <div class="job-actions">
                                <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-users"></i> View Apps
                                </a>
                                <?php if ($job['is_active'] && !$isExpired): ?>
                                    <button class="btn btn-sm deactivate-job" data-id="<?php echo $job['id']; ?>">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm activate-job" data-id="<?php echo $job['id']; ?>">
                                        <i class="fas fa-play"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <ul>
                        <?php if ($pagination['has_prev']): ?>
                            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['prev_page'])); ?>"><i class="fas fa-chevron-left"></i></a></li>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        
                        if ($start > 1) {
                            echo '<li><a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                            if ($start > 2) {
                                echo '<li><span>...</span></li>';
                            }
                        }
                        
                        for ($i = $start; $i <= $end; $i++) {
                            $active = $i === $pagination['current_page'] ? 'active' : '';
                            echo '<li><a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '" class="' . $active . '">' . $i . '</a></li>';
                        }
                        
                        if ($end < $pagination['total_pages']) {
                            if ($end < $pagination['total_pages'] - 1) {
                                echo '<li><span>...</span></li>';
                            }
                            echo '<li><a href="?' . http_build_query(array_merge($_GET, ['page' => $pagination['total_pages']])) . '">' . $pagination['total_pages'] . '</a></li>';
                        }
                        ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page'])); ?>"><i class="fas fa-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<style>
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.job-filters {
    margin-bottom: 30px;
}

.job-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    color: var(--light-text);
}

.dark .job-stats {
    color: var(--dark-light-text);
}

.job-stats i {
    margin-right: 5px;
}

.job-actions {
    display: flex;
    gap: 5px;
}

.job-actions .btn {
    padding: 5px 10px;
    font-size: 12px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle job status
    const deactivateButtons = document.querySelectorAll('.deactivate-job');
    deactivateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to deactivate this job?')) {
                fetch('api/toggle-my-job-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        job_id: jobId,
                        status: 0
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        location.reload();
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('error', 'An error occurred');
                });
            }
        });
    });
    
    const activateButtons = document.querySelectorAll('.activate-job');
    activateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to activate this job?')) {
                fetch('api/toggle-my-job-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        job_id: jobId,
                        status: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        location.reload();
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('error', 'An error occurred');
                });
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>