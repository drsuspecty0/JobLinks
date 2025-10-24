<?php
?>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>JobLinks</h3>
                    <p>Connecting talented professionals with their dream jobs. Find your next career opportunity with us.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>For Job Seekers</h4>
                    <ul>
                        <li><a href="jobs.php">Browse Jobs</a></li>
                        <li><a href="companies.php">Companies</a></li>
                        <li><a href="career-advice.php">Career Advice</a></li>
                        <li><a href="resume-builder.php">Resume Builder</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>For Employers</h4>
                    <ul>
                        <li><a href="post-job.php">Post a Job</a></li>
                        <li><a href="pricing.php">Pricing</a></li>
                        <li><a href="employer-resources.php">Resources</a></li>
                        <li><a href="talent-search.php">Talent Search</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Newsletter</h4>
                    <p>Get the latest jobs and career advice delivered to your inbox.</p>
                    <form id="newsletter-form" class="newsletter-form">
                        <input type="email" id="newsletter-email" placeholder="Your email address" required>
                        <button type="submit">Subscribe</button>
                    </form>
                    <div id="newsletter-message" class="newsletter-message"></div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>