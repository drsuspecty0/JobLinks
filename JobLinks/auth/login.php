<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
    redirect($redirect);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('auth/login.php');
    }
    
    // Get form data
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validate form data
    $errors = [];
    
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($password)) $errors[] = 'Password is required';
    
    if (empty($errors)) {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && verifyPassword($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Set remember me cookie if checked
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?");
                $stmt->execute([$token, date('Y-m-d H:i:s', $expires), $user['id']]);
                
                // Set cookie
                setcookie('remember_token', $token, $expires, '/');
            }
            
            // Redirect to intended page
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            showMessage('success', 'Welcome back, ' . $user['name'] . '!');
            redirect($redirect);
        } else {
            showMessage('error', 'Invalid email or password');
            redirect('auth/login.php');
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('auth/login.php');
    }
}

 $pageTitle = 'Login';
include '../includes/header.php';
?>

<section class="auth-page">
    <div class="container">
        <div class="auth-container">
            <div class="auth-form">
                <h2>Welcome Back</h2>
                <p>Login to your account to continue your job search</p>
                
                <div class="auth-tabs">
                    <button class="auth-tab active" data-tab="job-seeker">Job Seeker</button>
                    <button class="auth-tab" data-tab="employer">Employer</button>
                </div>
                
                <form method="post" action="login.php" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="user_type" id="user_type" value="job_seeker">
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">Please enter your email address.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">Please enter your password.</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">Login</button>
                </form>
                
                <div class="auth-links">
                    <p>Don't have an account? <a href="register.php">Sign up</a></p>
                    <p><a href="forgot-password.php">Forgot your password?</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>