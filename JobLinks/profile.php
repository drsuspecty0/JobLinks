<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    showMessage('info', 'Please login to view your profile');
    redirect('auth/login.php?redirect=profile.php');
}

// Get user information
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get company information if user is employer
 $company = null;
if (isEmployer()) {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('profile.php');
    }
    
    // Get form data
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    $bio = sanitize($_POST['bio']);
    $linkedinUrl = sanitize($_POST['linkedin_url']);
    $githubUrl = sanitize($_POST['github_url']);
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) $errors[] = 'Name is required';
    
    if (!empty($linkedinUrl) && !filter_var($linkedinUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid LinkedIn URL';
    }
    
    if (!empty($githubUrl) && !filter_var($githubUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid GitHub URL';
    }
    
    if (empty($errors)) {
        try {
            // Update user profile
            $stmt = $pdo->prepare("
                UPDATE users SET name = ?, phone = ?, location = ?, bio = ?, 
                               linkedin_url = ?, github_url = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $location, $bio, $linkedinUrl, $githubUrl, $_SESSION['user_id']]);
            
            // Update session variables
            $_SESSION['user_name'] = $name;
            
            showMessage('success', 'Profile updated successfully!');
            redirect('profile.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while updating your profile. Please try again.');
            redirect('profile.php');
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('profile.php');
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('profile.php');
    }
    
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate form data
    $errors = [];
    
    if (empty($currentPassword)) $errors[] = 'Current password is required';
    if (empty($newPassword)) $errors[] = 'New password is required';
    if (strlen($newPassword) < 8) $errors[] = 'Password must be at least 8 characters long';
    if ($newPassword !== $confirmPassword) $errors[] = 'Passwords do not match';
    
    // Verify current password
    if (!verifyPassword($currentPassword, $user['password_hash'])) {
        $errors[] = 'Current password is incorrect';
    }
    
    if (empty($errors)) {
        try {
            // Update password
            $newPasswordHash = hashPassword($newPassword);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newPasswordHash, $_SESSION['user_id']]);
            
            showMessage('success', 'Password changed successfully!');
            redirect('profile.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while changing your password. Please try again.');
            redirect('profile.php');
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('profile.php');
    }
}

// Get statistics
 $applicationsCount = 0;
 $savedJobsCount = 0;

if (isEmployer()) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE j.company_id = ?
    ");
    $stmt->execute([$company['id']]);
    $applicationsCount = $stmt->fetchColumn();
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $applicationsCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $savedJobsCount = $stmt->fetchColumn();
}

 $pageTitle = 'My Profile';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>My Profile</h2>
            <p>Manage your account information</p>
        </div>
        
        <div class="profile-content">
            <div class="profile-main">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo getInitials($user['name']); ?>
                        </div>
                        <div class="profile-info">
                            <h3><?php echo $user['name']; ?></h3>
                            <p class="profile-email"><?php echo $user['email']; ?></p>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                        <div class="profile-actions">
                            <button class="btn btn-sm edit-profile-btn">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $applicationsCount; ?></div>
                            <div class="stat-label"><?php echo isEmployer() ? 'Applications Received' : 'Applications Sent'; ?></div>
                        </div>
                        <?php if (!isEmployer()): ?>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $savedJobsCount; ?></div>
                            <div class="stat-label">Saved Jobs</div>
                        </div>
                        <?php endif; ?>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                            <div class="stat-label">Member Since</div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-forms">
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <form method="post" action="profile.php" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                                    <div class="invalid-feedback">Please enter your full name.</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" readonly>
                                    <small class="form-text">Email cannot be changed</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?php echo $user['location']; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $user['bio']; ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                    <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" value="<?php echo $user['linkedin_url']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="github_url" class="form-label">GitHub URL</label>
                                    <input type="url" class="form-control" id="github_url" name="github_url" value="<?php echo $user['github_url']; ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn">Update Profile</button>
                        </form>
                    </div>
                    
                    <div class="form-section">
                        <h3>Change Password</h3>
                        <form method="post" action="profile.php" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="form-group">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <div class="invalid-feedback">Please enter your current password.</div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="invalid-feedback">Please enter a new password.</div>
                                    <small class="form-text">Password must be at least 8 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="invalid-feedback">Please confirm your new password.</div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php if ($company): ?>
            <div class="profile-sidebar">
                <div class="company-card">
                    <h3>Company Information</h3>
                    <div class="company-details">
                        <p><strong>Name:</strong> <?php echo $company['name']; ?></p>
                        <p><strong>Industry:</strong> <?php echo $company['industry']; ?></p>
                        <p><strong>Size:</strong> <?php echo $company['size']; ?></p>
                        <p><strong>Location:</strong> <?php echo $company['location']; ?></p>
                        <p><strong>Website:</strong> <a href="<?php echo $company['website']; ?>" target="_blank"><?php echo $company['website']; ?></a></p>
                    </div>
                    <div class="company-actions">
                        <a href="edit-company.php" class="btn btn-sm">Edit Company</a>
                        <a href="post-job.php" class="btn btn-sm">Post Job</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.profile-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.profile-card {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
    margin-bottom: 30px;
}

.dark .profile-card {
    background-color: var(--dark-secondary);
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 600;
}

.profile-info h3 {
    margin: 0 0 5px;
    font-size: 24px;
}

.profile-email {
    margin: 0 0 10px;
    color: var(--light-text);
}

.dark .profile-email {
    color: var(--dark-light-text);
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.dark .profile-stats {
    border-top-color: var(--dark-border);
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 24px;
    font-weight: 600;
    color: var(--primary-color);
}

.stat-label {
    font-size: 14px;
    color: var(--light-text);
    margin-top: 5px;
}

.dark .stat-label {
    color: var(--dark-light-text);
}

.profile-forms {
    display: grid;
    gap: 30px;
}

.form-section {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
}

.dark .form-section {
    background-color: var(--dark-secondary);
}

.form-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}

.dark .form-section h3 {
    border-bottom-color: var(--dark-border);
}

.company-card {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--box-shadow);
}

.dark .company-card {
    background-color: var(--dark-secondary);
}

.company-details p {
    margin-bottom: 10px;
}

.company-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

@media (max-width: 992px) {
    .profile-content {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit profile button
    const editProfileBtn = document.querySelector('.edit-profile-btn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function() {
            const form = document.querySelector('.profile-forms');
            form.scrollIntoView({ behavior: 'smooth' });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>