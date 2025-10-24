<?php
require_once 'includes/config.php';

// Check if user is logged in and is employer
if (!isLoggedIn() || !isEmployer()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

// Get job ID
 $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if ($jobId <= 0) {
    showMessage('error', 'Invalid job ID');
    redirect('my-jobs.php');
}

// Get job details
 $stmt = $pdo->prepare("
    SELECT j.*, c.name as company_name 
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    WHERE j.id = ? AND c.user_id = ?
");
 $stmt->execute([$jobId, $_SESSION['user_id']]);
 $job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    showMessage('error', 'Job not found');
    redirect('my-jobs.php');
}

// Get applications for this job
 $stmt = $pdo->prepare("
    SELECT a.*, u.name as applicant_name, u.email as applicant_email
    FROM applications a
    JOIN users u ON a.user_id = u.id
    WHERE a.job_id = ?
    ORDER BY a.applied_at DESC
");
 $stmt->execute([$jobId]);
 $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $pageTitle = 'Job Applications - ' . $job['title'];
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Applications for <?php echo $job['title']; ?></h2>
            <a href="my-jobs.php" class="btn">Back to My Jobs</a>
        </div>
        
        <div class="job-summary">
            <h3><?php echo $job['title']; ?></h3>
            <p class="company-name"><?php echo $job['company_name']; ?></p>
            <div class="job-meta">
                <span><i class="fas fa-map-marker-alt"></i> <?php echo $job['location']; ?></span>
                <span><i class="fas fa-briefcase"></i> <?php echo ucfirst($job['type']); ?></span>
                <span><i class="fas fa-money-bill-wave"></i> <?php echo formatSalary($job['salary_min'], $job['salary_max']); ?></span>
            </div>
        </div>
        
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>No Applications Yet</h3>
                <p>No one has applied to this job yet.</p>
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
                                </p>
                            </div>
                            <div class="application-status">
                                <span class="status-badge status-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </div>
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

.job-summary {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: var(--box-shadow);
}

.dark .job-summary {
    background-color: var(--dark-secondary);
}

.job-summary h3 {
    margin: 0 0 10px;
}

.job-summary p {
    margin: 0;
    color: var(--light-text);
}

.dark .job-summary p {
    color: var(--dark-light-text);
}

.job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 10px;
    font-size: 14px;
    color: var(--light-text);
}

.dark .job-meta {
    color: var(--dark-light-text);
}

.job-meta i {
    margin-right: 5px;
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

.applicant-info h3 {
    margin: 0 0 5px;
    font-size: 18px;
}

.applicant-contact {
    margin: 0;
    color: var(--light-text);
    font-size: 14px;
}

.dark .applicant-contact {
    color: var(--dark-light-text);
}

.application-actions {
    display: flex;
    gap: 5px;
    margin-top: 15px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
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