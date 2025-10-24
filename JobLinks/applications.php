<?php
require_once 'includes/config.php';

// Check if user is logged in and is employer
if (!isLoggedIn() || !isEmployer()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

// Get user's company
 $stmt = $pdo->prepare("SELECT id FROM companies WHERE user_id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    showMessage('info', 'Please create a company profile first');
    redirect('post-company.php');
}

// Get filter parameters
 $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
 $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "
    SELECT a.*, j.title as job_title, j.location as job_location, j.type as job_type,
           u.name as applicant_name, u.email as applicant_email, u.phone as applicant_phone
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    WHERE j.company_id = ?
";
 $params = [$company['id']];

if ($jobId > 0) {
    $query .= " AND a.job_id = ?";
    $params[] = $jobId;
}

if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

 $query .= " ORDER BY a.applied_at DESC";

// Get paginated results
 $result = paginate($query, $page, 20);
 $applications = $result['items'];
 $pagination = $result['pagination'];

// Get status counts
 $stmt = $pdo->prepare("
    SELECT a.status, COUNT(*) as count 
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.company_id = ?
    GROUP BY a.status
");
 $stmt->execute([$company['id']]);
 $statusCounts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusCounts[$row['status']] = $row['count'];
}

// Get jobs for filter dropdown
 $stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE company_id = ? ORDER BY title");
 $stmt->execute([$company['id']]);
 $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $pageTitle = 'Applications';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Job Applications</h2>
            <a href="my-jobs.php" class="btn">Back to My Jobs</a>
        </div>
        
        <div class="applications-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="job-filter" class="form-label">Job</label>
                    <select id="job-filter" class="form-control">
                        <option value="">All Jobs</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?php echo $job['id']; ?>" <?php echo $jobId == $job['id'] ? 'selected' : ''; ?>><?php echo $job['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status-filter" class="form-label">Status</label>
                    <select id="status-filter" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="reviewed" <?php echo $status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                        <option value="shortlisted" <?php echo $status === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="hired" <?php echo $status === 'hired' ? 'selected' : ''; ?>>Hired</option>
                    </select>
                </div>
                
                <button class="btn apply-filters">Apply Filters</button>
                <button class="btn btn-outline clear-filters">Clear</button>
            </div>
        </div>
        
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>No Applications Found</h3>
                <p>No applications match your current filters.</p>
                <button class="btn clear-filters">Clear Filters</button>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="applicant-info">
                                <h3><?php echo $app['applicant_name']; ?></h3>
                                <p class="applicant-contact">
                                    <span><i class="fas fa-envelope"></i> <?php echo $app['applicant_email']; ?></span>
                                    <?php if ($app['applicant_phone']): ?>
                                        <span><i class="fas fa-phone"></i> <?php echo $app['applicant_phone']; ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="application-status">
                                <span class="status-badge status-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="application-job">
                            <h4><a href="job-detail.php?id=<?php echo $app['job_id']; ?>"><?php echo $app['job_title']; ?></a></h4>
                            <p class="job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo $app['job_location']; ?></span>
                                <span><i class="fas fa-briefcase"></i> <?php echo ucfirst($app['job_type']); ?></span>
                            </p>
                        </div>
                        
                        <div class="application-meta">
                            <span><i class="fas fa-calendar"></i> Applied <?php echo timeAgo($app['applied_at']); ?></span>
                        </div>
                        
                        <div class="application-actions">
                            <button class="btn btn-sm view-application" data-id="<?php echo $app['id']; ?>">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <div class="status-dropdown">
                                <button class="btn btn-sm dropdown-toggle">
                                    <i class="fas fa-edit"></i> Update Status
                                </button>
                                <div class="dropdown-menu">
                                    <a href="#" class="update-status" data-id="<?php echo $app['id']; ?>" data-status="pending">Pending</a>
                                    <a href="#" class="update-status" data-id="<?php echo $app['id']; ?>" data-status="reviewed">Reviewed</a>
                                    <a href="#" class="update-status" data-id="<?php echo $app['id']; ?>" data-status="shortlisted">Shortlisted</a>
                                    <a href="#" class="update-status" data-id="<?php echo $app['id']; ?>" data-status="rejected">Rejected</a>
                                    <a href="#" class="update-status" data-id="<?php echo $app['id']; ?>" data-status="hired">Hired</a>
                                </div>
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

<!-- Application Modal -->
<div id="applicationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Application Details</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="applicationDetails">
                <!-- Application details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.applications-filters {
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

.applicant-contact {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 5px;
}

.applicant-contact span {
    font-size: 14px;
    color: var(--light-text);
}

.dark .applicant-contact span {
    color: var(--dark-light-text);
}

.applicant-contact i {
    margin-right: 5px;
}

.application-job {
    margin: 15px 0;
    padding: 15px;
    background-color: var(--secondary-color);
    border-radius: var(--border-radius);
}

.dark .application-job {
    background-color: var(--dark-bg);
}

.application-job h4 {
    margin: 0 0 10px;
}

.application-job h4 a {
    color: var(--text-color);
}

.dark .application-job h4 a {
    color: var(--dark-text);
}

.job-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: var(--light-text);
}

.dark .job-meta {
    color: var(--dark-light-text);
}

.job-meta i {
    margin-right: 5px;
}

.application-actions {
    display: flex;
    gap: 5px;
    margin-top: 15px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Apply filters
    const applyFiltersBtn = document.querySelector('.apply-filters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            const jobId = document.getElementById('job-filter').value;
            const status = document.getElementById('status-filter').value;
            
            const params = new URLSearchParams();
            if (jobId) params.append('job_id', jobId);
            if (status) params.append('status', status);
            
            window.location.href = 'applications.php?' + params.toString();
        });
    }
    
    // Clear filters
    const clearFiltersBtn = document.querySelector('.clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            window.location.href = 'applications.php';
        });
    }
    
    // View application details
    const viewButtons = document.querySelectorAll('.view-application');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const appId = this.getAttribute('data-id');
            
            fetch(`api/get-employer-application.php?id=${appId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('applicationDetails').innerHTML = data.html;
                        document.getElementById('applicationModal').style.display = 'block';
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('error', 'An error occurred');
                });
        });
    });
    
    // Close modal
    document.querySelector('.modal-close').addEventListener('click', function() {
        document.getElementById('applicationModal').style.display = 'none';
    });
    
    // Update application status
    const updateStatusButtons = document.querySelectorAll('.update-status');
    updateStatusButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const appId = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            fetch('api/update-employer-application-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: appId,
                    status: status
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
        });
    });
    
    // Dropdown toggle
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const dropdown = this.nextElementSibling;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle')) {
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            dropdowns.forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>