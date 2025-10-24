<?php
require_once 'includes/config.php';

// Check if user is logged in and is employer
if (!isLoggedIn() || !isEmployer()) {
    showMessage('error', 'Access denied');
    redirect('index.php');
}

// Get user's company
 $stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    showMessage('info', 'Please create a company profile first');
    redirect('post-company.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('post-job.php');
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
    
    if (!empty($expiresAt) && strtotime($expiresAt) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Expiry date cannot be in the past';
    }
    
    if (empty($errors)) {
        try {
            // Generate unique slug
            $slug = getUniqueSlug('jobs', 'slug', $title);
            
            // Insert job
            $stmt = $pdo->prepare("
                INSERT INTO jobs (title, slug, company_id, category, type, location, salary_min, salary_max, 
                                 description, requirements, benefits, expires_at, featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title, $slug, $company['id'], $category, $type, $location, 
                $salaryMin, $salaryMax, $description, $requirements, $benefits, $expiresAt, $featured
            ]);
            
            showMessage('success', 'Job posted successfully!');
            redirect('my-jobs.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while posting the job. Please try again.');
            redirect('post-job.php');
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('post-job.php');
    }
}

 $pageTitle = 'Post a Job';
require_once 'includes/header.php';

// Get categories
 $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
 $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Post a New Job</h2>
            <p>Find the perfect candidate for your company</p>
        </div>
        
        <div class="post-job-container">
            <div class="company-info">
                <h3>Posting as: <?php echo $company['name']; ?></h3>
                <p><?php echo $company['location']; ?> • <?php echo $company['industry']; ?></p>
                <a href="edit-company.php" class="btn btn-sm">Edit Company</a>
            </div>
            
            <form method="post" action="post-job.php" class="post-job-form needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title" class="form-label">Job Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                        <div class="invalid-feedback">Please enter a job title.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="form-label">Category *</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['name']; ?>"><?php echo $cat['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a category.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="type" class="form-label">Job Type *</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="">Select job type</option>
                            <option value="full-time">Full-time</option>
                            <option value="part-time">Part-time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                            <option value="remote">Remote</option>
                        </select>
                        <div class="invalid-feedback">Please select a job type.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                        <div class="invalid-feedback">Please enter a location.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary_min" class="form-label">Minimum Salary</label>
                        <input type="number" class="form-control" id="salary_min" name="salary_min" min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_max" class="form-label">Maximum Salary</label>
                        <input type="number" class="form-control" id="salary_max" name="salary_max" min="0" step="1000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Job Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="6" required></textarea>
                    <div class="invalid-feedback">Please enter a job description.</div>
                </div>
                
                <div class="form-group">
                    <label for="requirements" class="form-label">Requirements *</label>
                    <textarea class="form-control" id="requirements" name="requirements" rows="6" required></textarea>
                    <div class="invalid-feedback">Please enter job requirements.</div>
                </div>
                
                <div class="form-group">
                    <label for="benefits" class="form-label">Benefits</label>
                    <textarea class="form-control" id="benefits" name="benefits" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expires_at" class="form-label">Expiry Date</label>
                        <input type="date" class="form-control" id="expires_at" name="expires_at" min="<?php echo date('Y-m-d'); ?>">
                        <small class="form-text">Leave blank for no expiry</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="featured" name="featured">
                            <label class="form-check-label" for="featured">
                                Feature this job (additional fees may apply)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">Post Job</button>
                    <a href="my-jobs.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.post-job-container {
    max-width: 800px;
    margin: 0 auto;
}

.company-info {
    background-color: var(--secondary-color);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dark .company-info {
    background-color: var(--dark-secondary);
}

.company-info h3 {
    margin: 0 0 5px;
}

.company-info p {
    margin: 0;
    color: var(--light-text);
}

.dark .company-info p {
    color: var(--dark-light-text);
}

.post-job-form {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
}

.dark .post-job-form {
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