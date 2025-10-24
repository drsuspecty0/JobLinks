<?php
require_once 'includes/config.php';

// Check if user is logged in and is employer
if (!isLoggedIn() || !isEmployer()) {
    showMessage('error', 'Access denied');
    redirect('auth/login.php?redirect=post-company.php');
}

// Check if user already has a company
 $stmt = $pdo->prepare("SELECT id FROM companies WHERE user_id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $existingCompany = $stmt->fetchColumn();

if ($existingCompany) {
    showMessage('info', 'You already have a company profile');
    redirect('my-jobs.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token']) {
        showMessage('error', 'Invalid request');
        redirect('post-company.php');
    }
    
    // Get form data
    $name = sanitize($_POST['name']);
    $industry = sanitize($_POST['industry']);
    $size = sanitize($_POST['size']);
    $website = sanitize($_POST['website']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $foundedYear = isset($_POST['founded_year']) ? (int)$_POST['founded_year'] : null;
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) $errors[] = 'Company name is required';
    if (empty($industry)) $errors[] = 'Industry is required';
    if (empty($size)) $errors[] = 'Company size is required';
    if (empty($location)) $errors[] = 'Location is required';
    
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid website URL';
    }
    
    if (!empty($foundedYear) && ($foundedYear < 1800 || $foundedYear > date('Y')) {
        $errors[] = 'Please enter a valid founded year';
    }
    
    if (empty($errors)) {
        try {
            // Create company
            $stmt = $pdo->prepare("
                INSERT INTO companies (user_id, name, slug, industry, size, website, description, location, founded_year) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $name,
                getUniqueSlug('companies', 'name', $name),
                $industry,
                $size,
                $website,
                $description,
                $location,
                $foundedYear
            ]);
            
            showMessage('success', 'Company profile created successfully!');
            redirect('my-jobs.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while creating your company profile. Please try again.');
            redirect('post-company.php');
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('post-company.php');
    }
}

 $pageTitle = 'Create Company Profile';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Create Company Profile</h2>
            <p>Create your company profile to start posting jobs</p>
        </div>
        
        <div class="post-company-container">
            <form method="post" action="post-company.php" class="post-company-form needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Please enter company name.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="industry" class="form-label">Industry *</label>
                        <select class="form-control" id="industry" name="industry" required>
                            <option value="">Select industry</option>
                            <option value="Technology">Technology</option>
                            <option value="Marketing</option>
                            <option value="Healthcare</option>
                            <option value="Finance</option>
                            <option value="Education</option>
                            <option value="Design</option>
                            <option value="Retail</option>
                        </select>
                        <div class="invalid-feedback">Please select an industry.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="size" class="form-label">Company Size *</label>
                        <select class="form-control" id="size" name="size" required>
                            <option value="">Select company size</option>
                            <option value="1-10">1-10 employees</option>
                            <option value="11-50">11-50 employees</option>
                            <option value="51-200">51-200 employees</option>
                            <option value="201-500">201-500 employees</option>
                            <option value="500+">500+ employees</option>
                        </select>
                        <div class="invalid-feedback">Please select company size.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                        <div class="invalid-feedback">Please enter location.</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com">
                    <div class="invalid-feedback">Please enter a valid website URL.</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="founded_year" class="form-label">Founded Year</label>
                        <input type="number" class="form-control" id="founded_year" name="founded_year" min="1800" max="<?php echo date('Y'); ?>">
                        <div class="invalid-feedback">Please enter a valid year.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Company Description</label>
                        <textarea class="form-control" id="description" name="description" rows="6" required></textarea>
                        <div class="form-text">Tell us about your company</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">Create Company Profile</button>
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.post-company-container {
    max-width: 800px;
    margin: 0 auto;
}

.post-company-form {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
}

.dark .post-company-form {
    background-color: var(--dark-secondary);
}

.form-row {
    display: grid;
    form-group {
        margin-bottom: 1.5rem;
    }

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}
</style>

<?php require_once 'includes/footer.php'; ?>