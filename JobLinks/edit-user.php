<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    showMessage('info', 'Please login to edit your profile');
    redirect('auth/login.php?redirect=edit-user.php');
}

// Get user information
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    showMessage('error', 'User not found');
    redirect('index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('edit-user.php');
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
                               linkedin_url = ?, github_url = ? ? : NULL
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $phone, $location, $bio, $linkedinUrl, $githubUrl, $_SESSION['user_id']
            ]);
            
            // Update session variables
            $_SESSION['user_name'] = $name;
            
            showMessage('success', 'Profile updated successfully!');
            redirect('profile.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while updating your profile. Please try again.');
            redirect('edit-user.php');
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('edit-user.php');
    }
}

 $pageTitle = 'Edit Profile';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="edit-profile-container">
            <form method="post" action="edit-user.php" class="edit-profile-form needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" readonly>
                        <small class="form-text">Email cannot be changed</small>
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
                </div>
                
                <div>
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
                </div>
                
                <button type="submit" class="btn">Update Profile</button>
                <a href="profile.php" class="btn btn-outline">Cancel</a>
            </form>
        </div>
    </div>
</section>

<style>
.edit-profile-container {
    max-width: 800px;
    margin: 0 auto;
}

.edit-profile-form {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
}

.dark .edit-profile-form {
    background-color: var(--dark-secondary);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}
</style>

<?php require_once 'includes/footer.php'; ?>