<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    showMessage('info', 'Please login to view your applications');
    redirect('auth/login.php?redirect=my-applications.php');
}

 $pageTitle = 'My Applications';
require_once 'includes/header.php';

// Get filter parameters
 $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "
    SELECT a.*, j.title as job_title, j.location as job_location, j.type as job_type,
           c.name as company_name, c.logo as company_logo
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    WHERE a.user_id = ?
";
 $params = [$_SESSION['user_id']];

if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

 $query .= " ORDER BY a.applied_at DESC";

// Get paginated results
 $result = paginate($query, $page, 10);
 $applications = $result['items'];
 $pagination = $result['pagination'];

// Get status counts
 $stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM applications 
    WHERE user_id = ?
    GROUP BY status
");
 $stmt->execute([$_SESSION['user_id']]);
 $statusCounts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>My Applications</h2>
            <p>Track the status of your job applications</p>
        </div>
        
        <div class="application-filters">
            <div class="filter-tabs">
                <a href="my-applications.php" class="<?php echo empty($status) ? 'active' : ''; ?>">All</a>
                <a href="?status=pending" class="<?php echo $status === 'pending' ? 'active' : ''; ?>">
                    Pending (<?php echo isset($statusCounts['pending']) ? $statusCounts['pending'] : 0; ?>)
                </a>
                <a href="?status=reviewed" class="<?php echo $status === 'reviewed' ? 'active' : ''; ?>">
                    Reviewed (<?php echo isset($statusCounts['reviewed']) ? $statusCounts['reviewed'] : 0; ?>)
                </a>
                <a href="?status=shortlisted" class="<?php echo $status === 'shortlisted' ? 'active' : ''; ?>">
                    Shortlisted (<?php echo isset($statusCounts['shortlisted']) ? $statusCounts['shortlisted'] : 0; ?>)
                </a>
                <a href="?status=rejected" class="<?php echo $status === 'rejected' ? 'active' : ''; ?>">
                    Rejected (<?php echo isset($statusCounts['rejected']) ? $statusCounts['rejected'] : 0; ?>)
                </a>
                <a href="?status=hired" class="<?php echo $status === 'hired' ? 'active' : ''; ?>">
                    Hired (<?php echo isset($statusCounts['hired']) ? $statusCounts['hired'] : 0; ?>)
                </a>
            </div>
        </div>
        
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>No Applications Yet</h3>
                <p>You haven't applied to any jobs yet. Start browsing and applying to your dream jobs!</p>
                <a href="jobs.php" class="btn">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="application-job">
                                <h3><a href="job-detail.php?id=<?php echo $app['job_id']; ?>"><?php echo $app['job_title']; ?></a></h3>
                                <p class="company-name"><?php echo $app['company_name']; ?></p>
                            </div>
                            <div class="application-status">
                                <span class="status-badge status-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="application-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo $app['job_location']; ?></span>
                            <span><i class="fas fa-briefcase"></i> <?php echo ucfirst($app['job_type']); ?></span>
                            <span><i class="fas fa-calendar"></i> Applied <?php echo timeAgo($app['applied_at']); ?></span>
                        </div>
                        
                        <div class="application-actions">
                            <button class="btn btn-sm view-application" data-id="<?php echo $app['id']; ?>">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <button class="btn btn-sm withdraw-application" data-id="<?php echo $app['id']; ?>">
                                <i class="fas fa-times"></i> Withdraw
                            </button>
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
.application-filters {
    margin-bottom: 30px;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-tabs a {
    padding: 8px 15px;
    background-color: var(--secondary-color);
    border-radius: var(--border-radius);
    color: var(--text-color);
    text-decoration: none;
    transition: var(--transition);
}

.dark .filter-tabs a {
    background-color: var(--dark-secondary);
    color: var(--dark-text);
}

.filter-tabs a.active {
    background-color: var(--primary-color);
    color: white;
}

.applications-list {
    display: grid;
    gap: 20px;
}

.application-card {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--box-shadow);
    border: 1px solid var(--border-color);
}

.dark .application-card {
    background-color: var(--dark-secondary);
    border-color: var(--dark-border);
}

.application-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.application-job h3 {
    margin: 0 0 5px;
    font-size: 18px;
}

.application-job h3 a {
    color: var(--text-color);
}

.dark .application-job h3 a {
    color: var(--dark-text);
}

.company-name {
    margin: 0;
    color: var(--light-text);
    font-size: 14px;
}

.dark .company-name {
    color: var(--dark-light-text);
}

.application-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    color: var(--light-text);
}

.dark .application-meta {
    color: var(--dark-light-text);
}

.application-meta i {
    margin-right: 5px;
}

.application-actions {
    display: flex;
    gap: 10px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    color: var(--light-text);
    margin-bottom: 20px;
}

.dark .empty-icon {
    color: var(--dark-light-text);
}

.empty-state h3 {
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--light-text);
    margin-bottom: 20px;
}

.dark .empty-state p {
    color: var(--dark-light-text);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View application details
    const viewButtons = document.querySelectorAll('.view-application');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const appId = this.getAttribute('data-id');
            
            fetch(`api/get-my-application.php?id=${appId}`)
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
    
    // Withdraw application
    const withdrawButtons = document.querySelectorAll('.withdraw-application');
    withdrawButtons.forEach(button => {
        button.addEventListener('click', function() {
            const appId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to withdraw this application?')) {
                fetch('api/withdraw-application.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        application_id: appId
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