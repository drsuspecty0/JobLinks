<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    showMessage('info', 'Please login to view your saved jobs');
    redirect('auth/login.php?redirect=saved-jobs.php');
}

 $pageTitle = 'Saved Jobs';
require_once 'includes/header.php';

// Get filter parameters
 $category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
 $type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "
    SELECT j.*, c.name as company_name, c.logo as company_logo,
           sj.created_at as saved_at
    FROM saved_jobs sj
    JOIN jobs j ON sj.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    WHERE sj.user_id = ? AND j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at >= CURDATE())
";
 $params = [$_SESSION['user_id']];

if (!empty($category)) {
    $query .= " AND j.category = ?";
    $params[] = $category;
}

if (!empty($type)) {
    $query .= " AND j.type = ?";
    $params[] = $type;
}

 $query .= " ORDER BY sj.created_at DESC";

// Get paginated results
 $result = paginate($query, $page, 12);
 $savedJobs = $result['items'];
 $pagination = $result['pagination'];

// Get categories for filter
 $stmt = $pdo->query("SELECT DISTINCT category FROM jobs WHERE is_active = 1 ORDER BY category");
 $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get job types for filter
 $stmt = $pdo->query("SELECT DISTINCT type FROM jobs WHERE is_active = 1 ORDER BY type");
 $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Saved Jobs</h2>
            <p>Jobs you've saved for later</p>
        </div>
        
        <div class="saved-jobs-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="category-filter" class="form-label">Category</label>
                    <select id="category-filter" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="type-filter" class="form-label">Job Type</label>
                    <select id="type-filter" class="form-control">
                        <option value="">All Types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button class="btn apply-filters">Apply Filters</button>
                <button class="btn btn-outline clear-filters">Clear</button>
            </div>
        </div>
        
        <?php if (empty($savedJobs)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="far fa-bookmark"></i>
                </div>
                <h3>No Saved Jobs</h3>
                <p>Start saving jobs that interest you so you can easily find them later.</p>
                <a href="jobs.php" class="btn">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="saved-jobs-grid">
                <?php foreach ($savedJobs as $job): ?>
                    <div class="job-card">
                        <div class="job-card-header">
                            <div>
                                <h3 class="job-title">
                                    <a href="job-detail.php?id=<?php echo $job['id']; ?>"><?php echo $job['title']; ?></a>
                                </h3>
                                <p class="company-name"><?php echo $job['company_name']; ?></p>
                            </div>
                            <button class="save-job saved" data-job-id="<?php echo $job['id']; ?>">
                                <i class="fas fa-bookmark"></i>
                            </button>
                        </div>
                        
                        <div class="job-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo $job['location']; ?></span>
                            <span><i class="fas fa-briefcase"></i> <?php echo ucfirst($job['type']); ?></span>
                            <span><i class="fas fa-money-bill-wave"></i> <?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                        </div>
                        
                        <p class="job-description"><?php echo substr($job['description'], 0, 150); ?>...</p>
                        
                        <div class="job-card-footer">
                            <span class="job-salary"><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                            <small class="saved-date">Saved <?php echo timeAgo($job['saved_at']); ?></small>
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
.saved-jobs-filters {
    margin-bottom: 30px;
}

.filter-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    min-width: 200px;
}

.saved-date {
    color: var(--light-text);
    font-size: 12px;
}

.dark .saved-date {
    color: var(--dark-light-text);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Apply filters
    const applyFiltersBtn = document.querySelector('.apply-filters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            const category = document.getElementById('category-filter').value;
            const type = document.getElementById('type-filter').value;
            
            const params = new URLSearchParams();
            if (category) params.append('category', category);
            if (type) params.append('type', type);
            
            window.location.href = 'saved-jobs.php?' + params.toString();
        });
    }
    
    // Clear filters
    const clearFiltersBtn = document.querySelector('.clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            window.location.href = 'saved-jobs.php';
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>