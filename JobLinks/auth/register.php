<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('auth/register.php');
    }
    
    // Get form data
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $userType = sanitize($_POST['user_type']);
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!validateEmail($email)) $errors[] = 'Please enter a valid email address';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        try {
            // Create new user
            $passwordHash = hashPassword($password);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $passwordHash, $userType]);
            
            // Get user ID
            $userId = $pdo->lastInsertId();
            
            // Set session variables
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $userType;
            
            // Send welcome email
            $subject = "Welcome to JobLinks";
            $body = "Dear $name,\n\nWelcome to JobLinks! Your account has been successfully created. You can now browse jobs and apply for positions that match your skills.\n\nIf you're an employer, you can also post job listings and manage applications.\n\nBest regards,\nThe JobLinks Team";
            
            sendEmail($email, $subject, $body);
            
            showMessage('success', 'Account created successfully! Welcome to JobLinks.');
            redirect('index.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while creating your account. Please try again.');
            redirect('auth/register.php');
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('auth/register.php');
    }
}

 $pageTitle = 'Sign Up';
include '../includes/header.php';
?>

<section class="auth-page">
    <div class="container">
        <div class="auth-container">
            <div class="auth-form">
                <h2>Create Account</h2>
                <p>Join JobLinks to find your dream job or hire top talent</p>
                
                <div class="auth-tabs">
                    <button class="auth-tab active" data-tab="job-seeker">Job Seeker</button>
                    <button class="auth-tab" data-tab="employer">Employer</button>
                </div>
                
                <form method="post" action="register.php" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="user_type" id="user_type" value="job_seeker">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">Please enter a password.</div>
                        <small class="form-text">Password must be at least 8 characters long.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">Please confirm your password.</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                            </label>
                            <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">Create Account</button>
                </form>
                
                <div class="auth-links">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>