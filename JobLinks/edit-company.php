<?php
require_once 'includes/config.php';

// Check if user is logged in and is employer
if (!isLoggedIn() || !isEmployer()) {
    showMessage('error', 'Access denied');
    redirect('auth/login.php?redirect=edit-company.php');
}

// Get company ID
 $companyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($companyId <= 0) {
    redirect('my-jobs.php');
}

// Get company details
 $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ? AND user_id = ?");
 $stmt->execute([$companyId, $_SESSION['user_id']]);
 $company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    showMessage('error', 'Company not found');
    redirect('my-jobs.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('edit-company.php?id=' . $companyId);
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
    
    if (!empty($foundedYear && ($foundedYear < 1800 || $foundedYear > date('Y')) {
        $errors[] = 'Please enter a valid founded year';
    }
    
    if (empty($errors)) {
        try {
            // Update company
            $stmt = $pdo->prepare("
                UPDATE companies SET name = ?, industry = ?, size = ?, website = ?, description = ?, location = ?, founded_year = ? ? : NULL 
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $industry, $size, $website, $description, $location, $foundedYear, $companyId
            ]);
            
            showMessage('success', 'Company information updated successfully!');
            redirect('my-jobs.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while updating company information. Please try again.');
            redirect('edit-company.php?id=' . $companyId);
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('edit-company.php?id=' . $companyId);
    }
}

 $pageTitle = 'Edit Company';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Edit Company</h2>
            <p>Update your company information</p>
        </div>
        
        <div class="edit-company-container">
            <form method="post" action="edit-company.php?id=<?php echo $companyId; ?>" class="edit-company-form needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $company['name']; ?>" required>
                        <div class="invalid-feedback">Please enter company name.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="industry" class="form-label">Industry *</label>
                        <select class="form-control" id="industry" name="industry" required>
                            <option value="">Select industry</option>
                            <option value="Technology" <?php echo $company['industry']; ?> selected>Technology</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Finance">Finance</option>
                            <option value="Education">Education</option>
                            <option value="Design">Design</option>
                            <option value="Retail">Retail</option>
                        </select>
                        <div class="invalid-feedback">Please select an industry.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="size" class="form-label">Company Size *</label>
                        <input type="text" class="form-control" id="size" name="size" value="<?php echo $company['size']; ?>" required>
                        <div class="invalid-feedback">Please enter company size.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo $company['location']; ?>" required>
                        <div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" class="form-control" id="website" name="website" value="<?php echo $company['website']; ?>">
                    <div class="invalid-feedback">Please enter a valid website URL.</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="founded_year" class="form-label">Founded Year</label>
                        <input type="number" class="form-control" id="founded_year" name="founded_year" value="<?php echo $company['founded_year']; ?>" min="1800" max="<?php echo date('Y'); ?>">
                        <div class="invalid-feedback">Please enter a valid year.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Company Description</label>
                        <textarea class="form-control" id="description" name="description" rows="6"><?php echo $company['description']; ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">Update Company</button>
                    <a href="my-jobs.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.edit-company-container {
    max-width: 800px;
    margin: 0 auto;
}

.edit-company-form {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
}

.dark .edit-company-form {
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