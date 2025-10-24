<?php
require_once 'includes/config.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', 'Invalid request');
        redirect('contact.php');
    }
    
    // Get form data
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!validateEmail($email)) $errors[] = 'Please enter a valid email address';
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($message)) $errors[] = 'Message is required';
    
    if (empty($errors)) {
        try {
            // Save message to database
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, subject, body) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                isLoggedIn() ? $_SESSION['user_id'] : null,
                $subject,
                $message
            ]);
            
            // Send notification email to admin
            $adminSubject = "New Contact Form Submission: $subject";
            $adminBody = "Name: $name\nEmail: $email\n\nMessage:\n$message";
            
            sendEmail('admin@joblinks.com', $adminSubject, $adminBody);
            
            // Send auto-reply email
            $autoReplySubject = "Thank you for contacting JobLinks";
            $autoReplyBody = "Dear $name,\n\nThank you for reaching out to us. We have received your message and will get back to you within 24-48 hours.\n\nBest regards,\nThe JobLinks Team";
            
            sendEmail($email, $autoReplySubject, $autoReplyBody);
            
            showMessage('success', 'Your message has been sent successfully. We will get back to you soon!');
            redirect('contact.php');
            
        } catch (PDOException $e) {
            showMessage('error', 'An error occurred while sending your message. Please try again.');
            redirect('contact.php');
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            showMessage('error', $error);
        }
        redirect('contact.php');
    }
}

 $pageTitle = 'Contact Us';
require_once 'includes/header.php';
?>

<section class="contact-hero">
    <div class="container">
        <div class="hero-content">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-content">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>Whether you have a question about our services, need technical support, or want to share feedback, our team is here to help.</p>
                
                <div class="contact-items">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Visit Our Office</h3>
                            <p>123 JobLinks Tower<br>San Francisco, CA 94105<br>United States</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Call Us</h3>
                            <p>Mon-Fri: 9am-6pm PST<br>Sat: 10am-4pm PST<br>Sun: Closed</p>
                            <p><a href="tel:+1234567890">+1 (234) 567-890</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Email Us</h3>
                            <p>For general inquiries:<br>info@joblinks.com</p>
                            <p>For support:<br>support@joblinks.com</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <h2>Send Us a Message</h2>
                <form method="post" action="contact.php" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Your Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Your Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Please enter your email address.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject" class="form-label">Subject *</label>
                        <select class="form-control" id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Technical Support">Technical Support</option>
                            <option value="Partnership">Partnership</option>
                            <option value="Feedback">Feedback</option>
                            <option value="Report Issue">Report Issue</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="invalid-feedback">Please select a subject.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        <div class="invalid-feedback">Please enter your message.</div>
                    </div>
                    
                    <button type="submit" class="btn">Send Message</button>
                </form>
            </div>
        </div>
        
        <div class="map-container">
            <h2>Find Us</h2>
            <div class="map-wrapper">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.123456789!2d12.3456789012!2f37.765432109876!3m2!1i1024!2i768!4f13.1!2m3!1e2!2e0!5b0!3m0!1m3!1e2!2e0!3m2!1i1024!2i768!4f13.1!2m3!1e2!2e0!6b14!1m0!3m0!1m3!1e2!2e0!6b14!1m0!3m0!1m3!1e2!2e0" 
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</section>

<style>
.contact-hero {
    background: linear-gradient(135deg, var(--primary-color), #1e40af);
    color: white;
    padding: 100px 0;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('assets/images/ui/hero-pattern.svg') no-repeat center;
    background-size: cover;
    opacity: 0.1;
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.contact-hero h1 {
    font-size: 48px;
    margin-bottom: 20px;
    color: white;
}

.contact-hero p {
    font-size: 20px;
    margin-bottom: 30px;
    opacity: 0.9;
}

.contact-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    margin-bottom: 50px;
}

.contact-info h2 {
    font-size: 36px;
    margin-bottom: 30px;
}

.contact-items {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.contact-icon {
    width: 60px;
    height: 60px;
    background-color: var(--secondary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: var(--primary-color);
    flex-shrink: 0;
}

.dark .contact-icon {
    background-color: var(--dark-bg);
}

.contact-details h3 {
    margin-bottom: 10px;
}

.contact-details p {
    margin: 0;
    line-height: 1.6;
}

.contact-form {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
}

.dark .contact-form {
    background-color: var(--dark-secondary);
}

.contact-form h2 {
    font-size: 24px;
    margin-bottom: 20px;
}

.map-container {
    margin-top: 50px;
}

.map-container h2 {
    font-size: 24px;
    margin-bottom: 20px;
}

.map-wrapper {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
}

@media (max-width: 768px) {
    .contact-content {
        grid-template-columns: 1fr;
    }
    
    .contact-hero h1 {
        font-size: 36px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>