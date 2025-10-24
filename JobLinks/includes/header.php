<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isDarkMode() ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="header-left">
                    <p>Your career starts here</p>
                </div>
                <div class="header-right">
                    <a href="tel:+1234567890"><i class="fas fa-phone"></i> +1 (234) 567-890</a>
                    <a href="mailto:info@joblinks.com"><i class="fas fa-envelope"></i> info@joblinks.com</a>
                </div>
            </div>
            <nav class="navbar">
                <div class="navbar-brand">
                    <a href="index.php"><?php echo SITE_NAME; ?></a>
                </div>
                <ul class="navbar-menu">
                    <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="jobs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : ''; ?>">Jobs</a></li>
                    <li><a href="companies.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'companies.php' ? 'active' : ''; ?>">Companies</a></li>
                    <li><a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a></li>
                    <li><a href="contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
                </ul>
                <div class="navbar-actions">
                    <div class="search-container">
                        <input type="text" id="live-search" placeholder="Search jobs...">
                        <button type="button" id="search-btn"><i class="fas fa-search"></i></button>
                        <div id="search-results" class="search-results"></div>
                    </div>
                    <a href="saved-jobs.php" class="icon-link">
                        <i class="far fa-bookmark"></i>
                        <?php if (isLoggedIn() && getSavedJobsCount() > 0): ?>
                            <span class="badge"><?php echo getSavedJobsCount(); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if (isEmployer()): ?>
                        <a href="applications.php" class="icon-link">
                            <i class="fas fa-file-alt"></i>
                            <?php if (getApplicationCount() > 0): ?>
                                <span class="badge"><?php echo getApplicationCount(); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <div class="user-menu">
                        <?php if (isLoggedIn()): ?>
                            <a href="#" class="icon-link user-toggle">
                                <i class="fas fa-user"></i>
                            </a>
                            <div class="user-dropdown">
                                <a href="profile.php">My Profile</a>
                                <?php if (isEmployer()): ?>
                                    <a href="post-job.php">Post a Job</a>
                                    <a href="my-jobs.php">My Jobs</a>
                                    <a href="applications.php">Applications</a>
                                <?php else: ?>
                                    <a href="my-applications.php">My Applications</a>
                                    <a href="saved-jobs.php">Saved Jobs</a>
                                <?php endif; ?>
                                <?php if (isAdmin()): ?>
                                    <a href="admin/">Admin Panel</a>
                                <?php endif; ?>
                                <a href="auth/logout.php">Logout</a>
                            </div>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-outline">Login</a>
                            <a href="auth/register.php" class="btn">Sign Up</a>
                        <?php endif; ?>
                    </div>
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
                <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
        </div>
    </header>

    <?php $message = getMessage(); ?>
    <?php if ($message): ?>
        <div class="message message-<?php echo $message['type']; ?>">
            <div class="container">
                <p><?php echo $message['text']; ?></p>
                <button class="message-close">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <main class="main">