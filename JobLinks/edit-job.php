<?php
require_once 'includes/config.php';

// Check if user is logged in and is employer
if (!isLoggedIn() || !isEmployer()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

// Get job ID
 $jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId <= 0) {
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

// Get categories
 $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
 $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('edit-job.php?id=' . $jobId);
    }
    
    // Get form data
    $title = sanitize($_POST['title']);
    $category = sanitize($_POST['category']);
    $type = sanitize($_POST['type']);
    $location = sanitize($_POST['location']);
    $salaryMin = isset($_POST['salary_min']) ? (float)$_POST['salary_min'] : 0;
    $salaryMax = isset($_POST['salary_max']) ? (float)$_POST['salary_max'] : 0;
    $description = sanitize($_POST['description']);
    $requirements = sanitize($_POST['requirements']);
    $benefits = sanitize($_POST['benefits']);
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate form data
    $errors = [];
    
    if (empty($title)) $errors[] = 'Job title is required';
    if (empty($category)) $errors[] = 'Category is required';
    if (empty($type)) $errors[] = 'Job type is required';
    if (empty($location)) $errors[] = 'Location is required';
    if (empty($description)) $errors[] = 'Description is required';
    if (empty($requirements)) $errors[] = 'Requirements are required';
    
    if ($salaryMin > 0 && $salaryMax > 0 && $salaryMin > $salaryMax) {
        $errors[] = 'Minimum salary cannot be greater than maximum salary';
    }
    
    if (!empty($expiresAt) && strtotime($expiresAt) < strtotime(date('Y-m-d')) {
        $errors[] = 'Expiry date cannot be in the past';
    }
    
    if (empty($errors)) {
        try {
            // Update job
            $stmt = $pdo->prepare("
                UPDATE jobs SET title = ?, category = ?, type = ?, location = ?, salary_min = ?, salary_max = ?, 
                               description = ?, requirements = ?, benefits = ?, expires_at = ?, featured = ? ? : 0
            WHERE id = ?
            ");
            $stmt->execute([
                $title, $category, $type, $location, $salaryMin, $salaryMax, 
                $description, $requirements, $benefits, $expiresAt, $featured, $jobId
            ]);
            
            showMessage('success', 'Job updated successfully!');
            redirect('my-jobs.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while updating the job. Please try again.');
            redirect('edit-job.php?id=' . $jobId);
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('edit-job.php?id=' . $jobId);
    }
}

 $pageTitle = 'Edit Job';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Edit Job</h2>
            <p>Update your job posting</p>
        </div>
        
        <div class="edit-job-container">
            <form method="post" action="edit-job.php?id=<?php echo $jobId; ?>" class="edit-job-form needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title" class="form-label">Job Title *</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $job['title']; ?>" required>
                        <div class="invalid-feedback">Please enter a job title.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="form-label">Category *</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['name']; ?>" <?php echo $job['category'] === $cat['name'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="div class="invalid-feedback">Please select a category.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="type" class="form-label">Job Type *</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="">Select job type</option>
                            <option value="full-time" <?php echo $job['type'] === 'full-time' ? 'selected' : ''; ?>>Full-time</option>
                            <option value="part-time" <?php echo $job['type'] === 'part-time' ? 'selected' : ''; ?>>Part-time</option>
                            <option value="contract" <?php echo $job['type'] === 'contract' ? 'selected' : ''; ?>Contract</option>
                            <option value="internship" <?php echo $job['type'] === 'internship' ? 'selected' : ''; ?>Internship</option>
                            <option value="remote" <?php echo $job['type'] === 'remote' ? 'selected' : ''; ?>Remote</option>
                        </select>
                        <div class="invalid-feedback">Please select a job type.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo $job['location']; ?>" required>
                        <div class="invalid-feedback">Please enter a location.</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salary_min" class="form-label">Min Salary</label>
                            <input type="number" class="form-control" id="salary_min" name="salary_min" value="<?php echo $job['salary_min']; ?>" min="0" step="1000">
                        </div>
                        
                        <div class="form-group">
                            <label for="salary_max" class="form-label">Max Salary</label>
                            <input type="number" class="form-control" id="salary_max" name="salary_max" value="<?php echo $job['salary_max']; ?>" min="0" step="1000">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="6" required><?php echo $job['description']; ?></textarea>
                        <div class="invalid-feedback">Please enter a description.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements" class="form-label">Requirements *</label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="6" required><?php echo $job['requirements']; ?></textarea>
                        <div class="invalid-feedback">Please enter requirements.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="benefits" class="form-label">Benefits</label>
                        <textarea class="form-control" id="benefits" name="benefits" rows="4"><?php echo $job['benefits']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="expires_at" class="form-label">Expiry Date</label>
                        <input type="date" class="form-control" id="expires_at" name="expires_at" value="<?php echo $job['expires_at']; ?>" min="<?php echo date('Y-m-d'); ?>">
                        <small class="form-text">Leave blank for no expiry</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">Update Job</button>
                    <a href="my-jobs.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.edit-job-container {
    max-width: 800px;
    margin: 0 auto;
}

.edit-job-form {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
}

.dark .edit-job-form {
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