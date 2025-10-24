<?php
require_once 'includes/config.php';

 $pageTitle = 'Jobs';
require_once 'includes/header.php';

// Get filter parameters
 $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
 $location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
 $category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
 $type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
 $sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_at';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "
    SELECT j.*, c.name as company_name, c.logo as company_logo 
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at >= CURDATE())
";
 $params = [];

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($category)) {
    $query .= " AND j.category = ?";
    $params[] = $category;
}

if (!empty($type)) {
    $query .= " AND j.type = ?";
    $params[] = $type;
}

// Add sorting
switch ($sort) {
    case 'title':
        $query .= " ORDER BY j.title ASC";
        break;
    case 'company':
        $query .= " ORDER BY c.name ASC";
        break;
    case 'salary_high':
        $query .= " ORDER BY j.salary_max DESC";
        break;
    case 'created_at':
    default:
        $query .= " ORDER BY j.created_at DESC";
        break;
}

// Get paginated results
 $result = paginate($query, $page, 10);
 $jobs = $result['items'];
 $pagination = $result['pagination'];

// Get all categories for filter
 $stmt = $pdo->query("SELECT DISTINCT category FROM jobs WHERE is_active = 1 ORDER BY category");
 $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all job types
 $stmt = $pdo->query("SELECT DISTINCT type FROM jobs WHERE is_active = 1 ORDER BY type");
 $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Job Listings</h2>
            <p><?php echo $pagination['total_items']; ?> opportunities available</p>
        </div>
        
        <div class="jobs-header">
            <div class="jobs-filters">
                <select id="sort" onchange="location.href='?<?php echo http_build_query(array_merge($_GET, ['sort' => $this->value])); ?>'">
                    <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Most Recent</option>
                    <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Job Title</option>
                    <option value="company" <?php echo $sort === 'company' ? 'selected' : ''; ?>>Company</option>
                    <option value="salary_high" <?php echo $sort === 'salary_high' ? 'selected' : ''; ?>>Highest Salary</option>
                </select>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-3 col-md-4">
                <div class="filters-sidebar">
                    <h3>Filters</h3>
                    <form method="get" action="jobs.php">
                        <div class="filter-group">
                            <h4>Keywords</h4>
                            <input type="text" name="search" value="<?php echo $search; ?>" class="form-control" placeholder="Job title or keywords">
                        </div>
                        
                        <div class="filter-group">
                            <h4>Location</h4>
                            <input type="text" name="location" value="<?php echo $location; ?>" class="form-control" placeholder="City or remote">
                        </div>
                        
                        <div class="filter-group">
                            <h4>Category</h4>
                            <ul class="filter-options">
                                <li>
                                    <label>
                                        <input type="radio" name="category" value="" <?php echo empty($category) ? 'checked' : ''; ?>>
                                        All Categories
                                    </label>
                                </li>
                                <?php foreach ($categories as $cat): ?>
                                    <li>
                                        <label>
                                            <input type="radio" name="category" value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'checked' : ''; ?>>
                                            <?php echo $cat; ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="filter-group">
                            <h4>Job Type</h4>
                            <ul class="filter-options">
                                <li>
                                    <label>
                                        <input type="radio" name="type" value="" <?php echo empty($type) ? 'checked' : ''; ?>>
                                        All Types
                                    </label>
                                </li>
                                <?php foreach ($types as $t): ?>
                                    <li>
                                        <label>
                                            <input type="radio" name="type" value="<?php echo $t; ?>" <?php echo $type === $t ? 'checked' : ''; ?>>
                                            <?php echo ucfirst($t); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn">Apply Filters</button>
                        <a href="jobs.php" class="btn btn-outline">Clear Filters</a>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-9 col-md-8">
                <div class="jobs-grid">
                    <?php if (empty($jobs)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <p>No jobs found matching your criteria. Please try different filters.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
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
                                    <span><i class="fas fa-clock"></i> <?php echo timeAgo($job['created_at']); ?></span>
                                </div>
                                <p class="job-description"><?php echo substr($job['description'], 0, 200); ?>...</p>
                                <div class="job-card-footer">
                                    <span class="job-salary"><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
                                    <button class="save-job" data-job-id="<?php echo $job['id']; ?>">
                                        <i class="far fa-bookmark"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>