<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

 $pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';

// Get dashboard statistics
 $totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
 $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
 $totalCompanies = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
 $totalApplications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

// Get recent applications
 $stmt = $pdo->query("
    SELECT a.*, j.title as job_title, u.name as applicant_name, c.name as company_name 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    JOIN users u ON a.user_id = u.id 
    JOIN companies c ON j.company_id = c.id 
    ORDER BY a.applied_at DESC 
    LIMIT 5
");
 $recentApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent jobs
 $stmt = $pdo->query("
    SELECT j.*, c.name as company_name 
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    ORDER BY j.created_at DESC 
    LIMIT 5
");
 $recentJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent users
 $stmt = $pdo->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
 $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
    <div class="container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Manage your job portal</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalJobs; ?></h3>
                    <p>Total Jobs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalCompanies; ?></h3>
                    <p>Total Companies</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalApplications; ?></h3>
                    <p>Total Applications</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="admin-card">
                    <h3>Recent Applications</h3>
                    <div class="recent-list">
                        <?php if (empty($recentApplications)): ?>
                            <p>No applications yet</p>
                        <?php else: ?>
                            <?php foreach ($recentApplications as $app): ?>
                                <div class="recent-item">
                                    <div class="recent-item-header">
                                        <h4><?php echo $app['applicant_name']; ?></h4>
                                        <span class="status-badge status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                                    </div>
                                    <p>Applied for: <?php echo $app['job_title']; ?> at <?php echo $app['company_name']; ?></p>
                                    <small><?php echo timeAgo($app['applied_at']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="applications.php" class="btn btn-sm">View All Applications</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="admin-card">
                    <h3>Recent Jobs</h3>
                    <div class="recent-list">
                        <?php if (empty($recentJobs)): ?>
                            <p>No jobs posted yet</p>
                        <?php else: ?>
                            <?php foreach ($recentJobs as $job): ?>
                                <div class="recent-item">
                                    <div class="recent-item-header">
                                        <h4><?php echo $job['title']; ?></h4>
                                        <span class="status-badge <?php echo $job['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $job['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <p>Company: <?php echo $job['company_name']; ?></p>
                                    <small><?php echo timeAgo($job['created_at']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="jobs.php" class="btn btn-sm">View All Jobs</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="admin-card">
                    <h3>Recent Users</h3>
                    <div class="recent-list">
                        <?php if (empty($recentUsers)): ?>
                            <p>No users registered yet</p>
                        <?php else: ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="recent-item">
                                    <div class="recent-item-header">
                                        <h4><?php echo $user['name']; ?></h4>
                                        <span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                                    </div>
                                    <p>Email: <?php echo $user['email']; ?></p>
                                    <small><?php echo timeAgo($user['created_at']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="users.php" class="btn btn-sm">View All Users</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.admin-header {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--box-shadow);
    display: flex;
    align-items: center;
    gap: 15px;
}

.dark .stat-card {
    background-color: var(--dark-secondary);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background-color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.stat-content h3 {
    font-size: 32px;
    margin-bottom: 5px;
}

.stat-content p {
    margin: 0;
    color: var(--light-text);
}

.dark .stat-content p {
    color: var(--dark-light-text);
}

.admin-card {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--box-shadow);
    margin-bottom: 20px;
    height: 100%;
}

.dark .admin-card {
    background-color: var(--dark-secondary);
}

.admin-card h3 {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}

.dark .admin-card h3 {
    border-bottom-color: var(--dark-border);
}

.recent-list {
    max-height: 300px;
    overflow-y: auto;
}

.recent-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.dark .recent-item {
    border-bottom-color: var(--dark-border);
}

.recent-item:last-child {
    border-bottom: none;
}

.recent-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.recent-item-header h4 {
    margin: 0;
    font-size: 16px;
}

.recent-item p {
    margin: 0 0 5px;
    font-size: 14px;
}

.recent-item small {
    color: var(--light-text);
}

.dark .recent-item small {
    color: var(--dark-light-text);
}

.status-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-reviewed {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-shortlisted {
    background-color: #d1fae5;
    color: #065f46;
}

.status-rejected {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-hired {
    background-color: #d1fae5;
    color: #065f46;
}

.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background-color: #fee2e2;
    color: #991b1b;
}

.role-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.role-admin {
    background-color: #ede9fe;
    color: #5b21b6;
}

.role-employer {
    background-color: #dbeafe;
    color: #1e40af;
}

.role-job_seeker {
    background-color: #fef3c7;
    color: #92400e;
}
</style>

<?php require_once '../includes/footer.php'; ?>