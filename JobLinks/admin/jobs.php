<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

 $pageTitle = 'Manage Jobs';
require_once '../includes/header.php';

// Get filter parameters
 $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
 $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "
    SELECT j.*, c.name as company_name 
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    WHERE 1=1
";
 $params = [];

if ($status === 'active') {
    $query .= " AND j.is_active = 1";
} elseif ($status === 'inactive') {
    $query .= " AND j.is_active = 0";
} elseif ($status === 'expired') {
    $query .= " AND j.expires_at < CURDATE()";
}

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

 $query .= " ORDER BY j.created_at DESC";

// Get paginated results
 $result = paginate($query, $page, 20);
 $jobs = $result['items'];
 $pagination = $result['pagination'];

// Get status counts
 $stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN expires_at < CURDATE() THEN 1 ELSE 0 END) as expired
    FROM jobs
");
 $statusCounts = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<section class="section">
    <div class="container">
        <div class="admin-header">
            <h1>Manage Jobs</h1>
            <p>View and manage job postings</p>
        </div>
        
        <div class="admin-filters">
            <div class="filter-tabs">
                <a href="jobs.php" class="<?php echo empty($status) ? 'active' : ''; ?>">All</a>
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
            
            <form method="get" action="jobs.php" class="search-form">
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search jobs..." class="form-control">
                <button type="submit" class="btn">Search</button>
            </form>
        </div>
        
        <div class="admin-table-container">
            <?php if (empty($jobs)): ?>
                <div class="no-results">
                    <p>No jobs found</p>
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Salary</th>
                            <th>Posted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td>
                                    <div class="job-info">
                                        <h4><?php echo $job['title']; ?></h4>
                                        <p><?php echo $job['category']; ?></p>
                                    </div>
                                </td>
                                <td><?php echo $job['company_name']; ?></td>
                                <td><?php echo ucfirst($job['type']); ?></td>
                                <td><?php echo $job['location']; ?></td>
                                <td><?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $isExpired = $job['expires_at'] && $job['expires_at'] < date('Y-m-d');
                                    if ($isExpired): ?>
                                        <span class="status-badge status-expired">Expired</span>
                                    <?php elseif ($job['is_active']): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm view-job" data-id="<?php echo $job['id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm edit-job" data-id="<?php echo $job['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($job['is_active'] && !$isExpired): ?>
                                            <button class="btn btn-sm deactivate-job" data-id="<?php echo $job['id']; ?>">
                                                <i class="fas fa-pause"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm activate-job" data-id="<?php echo $job['id']; ?>">
                                                <i class="fas fa-play"></i> Activate
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
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
    </div>
</section>

<!-- Job Modal -->
<div id="jobModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Job Details</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="jobDetails">
                <!-- Job details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.job-info h4 {
    margin: 0 0 5px;
    font-size: 16px;
}

.job-info p {
    margin: 0;
    color: var(--light-text);
    font-size: 14px;
}

.dark .job-info p {
    color: var(--dark-light-text);
}

.status-expired {
    background-color: #fef3c7;
    color: #92400e;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View job details
    const viewButtons = document.querySelectorAll('.view-job');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.getAttribute('data-id');
            
            fetch(`api/get-job.php?id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('jobDetails').innerHTML = data.html;
                        document.getElementById('jobModal').style.display = 'block';
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
        document.getElementById('jobModal').style.display = 'none';
    });
    
    // Edit job
    const editButtons = document.querySelectorAll('.edit-job');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.getAttribute('data-id');
            window.location.href = `edit-job.php?id=${jobId}`;
        });
    });
    
    // Deactivate job
    const deactivateButtons = document.querySelectorAll('.deactivate-job');
    deactivateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to deactivate this job?')) {
                fetch('api/toggle-job-status.php', {
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
    
    // Activate job
    const activateButtons = document.querySelectorAll('.activate-job');
    activateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to activate this job?')) {
                fetch('api/toggle-job-status.php', {
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

<?php require_once '../includes/footer.php'; ?>