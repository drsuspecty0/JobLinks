<?php
require_once 'includes/config.php';

 $pageTitle = 'Companies';
require_once 'includes/header.php';

// Get filter parameters
 $industry = isset($_GET['industry']) ? sanitize($_GET['industry']) : '';
 $location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
 $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "
    SELECT c.*, u.name as owner_name,
           (SELECT COUNT(*) FROM jobs WHERE company_id = c.id AND is_active = 1) as active_jobs_count
    FROM companies c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE 1=1
";
 $params = [];

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($industry)) {
    $query .= " AND c.industry = ?";
    $params[] = $industry;
}

if (!empty($location)) {
    $query .= " AND c.location LIKE ?";
    $params[] = "%$location%";
}

 $query .= " ORDER BY c.created_at DESC";

// Get paginated results
 $result = paginate($query, $page, 12);
 $companies = $result['items'];
 $pagination = $result['pagination'];

// Get industries for filter
 $stmt = $pdo->query("SELECT DISTINCT industry FROM companies ORDER BY industry");
 $industries = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Companies</h2>
            <p>Discover great companies to work for</p>
        </div>
        
        <div class="companies-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search-filter" class="form-label">Search</label>
                    <input type="text" id="search-filter" class="form-control" placeholder="Company name..." value="<?php echo $search; ?>">
                </div>
                
                <div class="filter-group">
                    <label for="industry-filter" class="form-label">Industry</label>
                    <select id="industry-filter" class="form-control">
                        <option value="">All Industries</option>
                        <?php foreach ($industries as $ind): ?>
                            <option value="<?php echo $ind; ?>" <?php echo $industry === $ind ? 'selected' : ''; ?>><?php echo $ind; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="location-filter" class="form-label">Location</label>
                    <input type="text" id="location-filter" class="form-control" placeholder="City or remote..." value="<?php echo $location; ?>">
                </div>
                
                <button class="btn apply-filters">Apply Filters</button>
                <button class="btn btn-outline clear-filters">Clear</button>
            </div>
        </div>
        
        <?php if (empty($companies)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>No Companies Found</h3>
                <p>No companies match your current filters.</p>
                <button class="btn clear-filters">Clear Filters</button>
            </div>
        <?php else: ?>
            <div class="companies-grid">
                <?php foreach ($companies as $company): ?>
                    <div class="company-card">
                        <div class="company-header">
                            <div class="company-logo">
                                <?php echo getInitials($company['name']); ?>
                            </div>
                            <div class="company-info">
                                <h3><a href="company-detail.php?id=<?php echo $company['id']; ?>"><?php echo $company['name']; ?></a></h3>
                                <p class="company-meta">
                                    <span><i class="fas fa-industry"></i> <?php echo $company['industry']; ?></span>
                                    <span><i class="fas fa-users"></i> <?php echo $company['size']; ?></span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="company-description">
                            <p><?php echo substr($company['description'], 0, 150); ?>...</p>
                        </div>
                        
                        <div class="company-footer">
                            <div class="company-stats">
                                <span><i class="fas fa-briefcase"></i> <?php echo $company['active_jobs_count']; ?> Active Jobs</span>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo $company['location']; ?></span>
                            </div>
                            <a href="company-detail.php?id=<?php echo $company['id']; ?>" class="btn btn-sm">View Company</a>
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
.companies-filters {
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

.companies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.company-card {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    border: 1px solid var(--border-color);
}

.dark .company-card {
    background-color: var(--dark-secondary);
    border-color: var(--dark-border);
}

.company-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.company-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}

.company-logo {
    width: 60px;
    height: 60px;
    border-radius: var(--border-radius);
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 600;
}

.company-info h3 {
    margin: 0 0 5px;
    font-size: 18px;
}

.company-info h3 a {
    color: var(--text-color);
}

.dark .company-info h3 a {
    color: var(--dark-text);
}

.company-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 14px;
    color: var(--light-text);
}

.dark .company-meta {
    color: var(--dark-light-text);
}

.company-meta i {
    margin-right: 5px;
}

.company-description {
    margin-bottom: 20px;
    line-height: 1.6;
    color: var(--light-text);
}

.dark .company-description {
    color: var(--dark-light-text);
}

.company-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
}

.dark .company-footer {
    border-top-color: var(--dark-border);
}

.company-stats {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 14px;
    color: var(--light-text);
}

.dark .company-stats {
    color: var(--dark-light-text);
}

.company-stats i {
    margin-right: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Apply filters
    const applyFiltersBtn = document.querySelector('.apply-filters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            const search = document.getElementById('search-filter').value;
            const industry = document.getElementById('industry-filter').value;
            const location = document.getElementById('location-filter').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (industry) params.append('industry', industry);
            if (location) params.append('location', location);
            
            window.location.href = 'companies.php?' + params.toString();
        });
    }
    
    // Clear filters
    const clearFiltersBtn = document.querySelector('.clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            window.location.href = 'companies.php';
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>