<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

 $pageTitle = 'Manage Applications';
require_once '../includes/header.php';

// Get filter parameters
 $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
 $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "
    SELECT a.*, j.title as job_title, u.name as applicant_name, u.email as applicant_email, 
           c.name as company_name 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    JOIN users u ON a.user_id = u.id 
    JOIN companies c ON j.company_id = c.id 
    WHERE 1=1
";
 $params = [];

if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR j.title LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

 $query .= " ORDER BY a.applied_at DESC";

// Get paginated results
 $result = paginate($query, $page, 20);
 $applications = $result['items'];
 $pagination = $result['pagination'];

// Get status counts
 $stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM applications 
    GROUP BY status
");
 $statusCounts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<section class="section">
    <div class="container">
        <div class="admin-header">
            <h1>Manage Applications</h1>
            <p>View and manage job applications</p>
        </div>
        
        <div class="admin-filters">
            <div class="filter-tabs">
                <a href="applications.php" class="<?php echo empty($status) ? 'active' : ''; ?>">All</a>
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
            
            <form method="get" action="applications.php" class="search-form">
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search applications..." class="form-control">
                <button type="submit" class="btn">Search</button>
            </form>
        </div>
        
        <div class="admin-table-container">
            <?php if (empty($applications)): ?>
                <div class="no-results">
                    <p>No applications found</p>
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Job</th>
                            <th>Company</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>
                                    <div class="applicant-info">
                                        <h4><?php echo $app['applicant_name']; ?></h4>
                                        <p><?php echo $app['applicant_email']; ?></p>
                                    </div>
                                </td>
                                <td><?php echo $app['job_title']; ?></td>
                                <td><?php echo $app['company_name']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm view-application" data-id="<?php echo $app['id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <div class="status-dropdown">
                                            <button class="btn btn-sm dropdown-toggle">
                                                <i class="fas fa-edit"></i> Status
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
.admin-header {
    margin-bottom: 30px;
}

.admin-filters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
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

.search-form {
    display: flex;
    gap: 10px;
}

.search-form input {
    width: 250px;
}

.admin-table-container {
    background-color: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
}

.dark .admin-table-container {
    background-color: var(--dark-secondary);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    background-color: var(--secondary-color);
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: var(--text-color);
}

.dark .admin-table th {
    background-color: var(--dark-bg);
    color: var(--dark-text);
}

.admin-table td {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
}

.dark .admin-table td {
    border-bottom-color: var(--dark-border);
}

.applicant-info h4 {
    margin: 0 0 5px;
    font-size: 16px;
}

.applicant-info p {
    margin: 0;
    color: var(--light-text);
    font-size: 14px;
}

.dark .applicant-info p {
    color: var(--dark-light-text);
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.status-dropdown {
    position: relative;
}

.dropdown-toggle {
    background-color: var(--secondary-color);
    border: 1px solid var(--border-color);
}

.dark .dropdown-toggle {
    background-color: var(--dark-bg);
    border-color: var(--dark-border);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    min-width: 150px;
    z-index: 100;
    display: none;
}

.dark .dropdown-menu {
    background-color: var(--dark-secondary);
    border-color: var(--dark-border);
}

.dropdown-menu a {
    display: block;
    padding: 8px 15px;
    color: var(--text-color);
    text-decoration: none;
}

.dark .dropdown-menu a {
    color: var(--dark-text);
}

.dropdown-menu a:hover {
    background-color: var(--secondary-color);
}

.dark .dropdown-menu a:hover {
    background-color: var(--dark-bg);
}

.no-results {
    text-align: center;
    padding: 40px;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.dark .no-results {
    background-color: var(--dark-secondary);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    background-color: white;
    border-radius: var(--border-radius);
    max-width: 800px;
    margin: 50px auto;
    max-height: 90vh;
    overflow-y: auto;
}

.dark .modal-content {
    background-color: var(--dark-secondary);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
}

.dark .modal-header {
    border-bottom-color: var(--dark-border);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View application details
    const viewButtons = document.querySelectorAll('.view-application');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const appId = this.getAttribute('data-id');
            
            fetch(`api/get-application.php?id=${appId}`)
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
            
            fetch('api/update-application-status.php', {
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

<?php require_once '../includes/footer.php'; ?>