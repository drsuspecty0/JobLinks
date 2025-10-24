<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

 $pageTitle = 'Manage Users';
require_once '../includes/header.php';

// Get filter parameters
 $role = isset($_GET['role']) ? sanitize($_GET['role']) : '';
 $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
 $query = "SELECT * FROM users WHERE 1=1";
 $params = [];

if (!empty($role)) {
    $query .= " AND role = ?";
    $params[] = $role;
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

 $query .= " ORDER BY created_at DESC";

// Get paginated results
 $result = paginate($query, $page, 20);
 $users = $result['items'];
 $pagination = $result['pagination'];

// Get role counts
 $stmt = $pdo->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
 $roleCounts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roleCounts[$row['role']] = $row['count'];
}
?>

<section class="section">
    <div class="container">
        <div class="admin-header">
            <h1>Manage Users</h1>
            <p>View and manage user accounts</p>
        </div>
        
        <div class="admin-filters">
            <div class="filter-tabs">
                <a href="users.php" class="<?php echo empty($role) ? 'active' : ''; ?>">All</a>
                <a href="?role=admin" class="<?php echo $role === 'admin' ? 'active' : ''; ?>">
                    Admin (<?php echo isset($roleCounts['admin']) ? $roleCounts['admin'] : 0; ?>)
                </a>
                <a href="?role=employer" class="<?php echo $role === 'employer' ? 'active' : ''; ?>">
                    Employers (<?php echo isset($roleCounts['employer']) ? $roleCounts['employer'] : 0; ?>)
                </a>
                <a href="?role=job_seeker" class="<?php echo $role === 'job_seeker' ? 'active' : ''; ?>">
                    Job Seekers (<?php echo isset($roleCounts['job_seeker']) ? $roleCounts['job_seeker'] : 0; ?>)
                </a>
            </div>
            
            <form method="get" action="users.php" class="search-form">
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search users..." class="form-control">
                <button type="submit" class="btn">Search</button>
            </form>
        </div>
        
        <div class="admin-table-container">
            <?php if (empty($users)): ?>
                <div class="no-results">
                    <p>No users found</p>
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Location</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <h4><?php echo $user['name']; ?></h4>
                                        <?php if ($user['phone']): ?>
                                            <p><?php echo $user['phone']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo $user['email']; ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['location'] ?: 'Not specified'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm view-user" data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm edit-user" data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <button class="btn btn-sm delete-user" data-id="<?php echo $user['id']; ?>">
                                                <i class="fas fa-trash"></i> Delete
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

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>User Details</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="userDetails">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.user-info h4 {
    margin: 0 0 5px;
    font-size: 16px;
}

.user-info p {
    margin: 0;
    color: var(--light-text);
    font-size: 14px;
}

.dark .user-info p {
    color: var(--dark-light-text);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View user details
    const viewButtons = document.querySelectorAll('.view-user');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            
            fetch(`api/get-user.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userDetails').innerHTML = data.html;
                        document.getElementById('userModal').style.display = 'block';
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
        document.getElementById('userModal').style.display = 'none';
    });
    
    // Edit user
    const editButtons = document.querySelectorAll('.edit-user');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            window.location.href = `edit-user.php?id=${userId}`;
        });
    });
    
    // Delete user
    const deleteButtons = document.querySelectorAll('.delete-user');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                fetch('api/delete-user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId
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