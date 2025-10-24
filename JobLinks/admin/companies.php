<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

 $pageTitle = 'Manage Companies';
require_once '../includes/header.php';

// Get filter parameters
 $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "
    SELECT c.*, u.name as owner_name, u.email as owner_email,
           (SELECT COUNT(*) FROM jobs WHERE company_id = c.id) as job_count
    FROM companies c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE 1=1
";
 $params = [];

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.industry LIKE ? OR c.location LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

 $query .= " ORDER BY c.created_at DESC";

// Get paginated results
 $result = paginate($query, $page, 20);
 $companies = $result['items'];
 $pagination = $result['pagination'];
?>

<section class="section">
    <div class="container">
        <div class="admin-header">
            <h1>Manage Companies</h1>
            <p>View and manage company profiles</p>
        </div>
        
        <div class="admin-filters">
            <form method="get" action="companies.php" class="search-form">
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search companies..." class="form-control">
                <button type="submit" class="btn">Search</button>
            </form>
        </div>
        
        <div class="admin-table-container">
            <?php if (empty($companies)): ?>
                <div class="no-results">
                    <p>No companies found</p>
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Industry</th>
                            <th>Location</th>
                            <th>Jobs Posted</th>
                            <th>Owner</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td>
                                    <div class="company-info">
                                        <h4><?php echo $company['name']; ?></h4>
                                        <p><?php echo $company['size']; ?> employees</p>
                                    </div>
                                </td>
                                <td><?php echo $company['industry']; ?></td>
                                <td><?php echo $company['location']; ?></td>
                                <td><?php echo $company['job_count']; ?></td>
                                <td>
                                    <div class="owner-info">
                                        <p><?php echo $company['owner_name']; ?></p>
                                        <small><?php echo $company['owner_email']; ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($company['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm view-company" data-id="<?php echo $company['id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm edit-company" data-id="<?php echo $company['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
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

<!-- Company Modal -->
<div id="companyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Company Details</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="companyDetails">
                <!-- Company details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.company-info h4 {
    margin: 0 0 5px;
    font-size: 16px;
}

.company-info p {
    margin: 0;
    color: var(--light-text);
    font-size: 14px;
}

.dark .company-info p {
    color: var(--dark-light-text);
}

.owner-info p {
    margin: 0 0 5px;
    font-size: 14px;
}

.owner-info small {
    color: var(--light-text);
    font-size: 12px;
}

.dark .owner-info small {
    color: var(--dark-light-text);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View company details
    const viewButtons = document.querySelectorAll('.view-company');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const companyId = this.getAttribute('data-id');
            
            fetch(`api/get-company.php?id=${companyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('companyDetails').innerHTML = data.html;
                        document.getElementById('companyModal').style.display = 'block';
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
        document.getElementById('companyModal').style.display = 'none';
    });
    
    // Edit company
    const editButtons = document.querySelectorAll('.edit-company');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const companyId = this.getAttribute('data-id');
            window.location.href = `edit-company.php?id=${companyId}`;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>