document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    const navbarActions = document.querySelector('.navbar-actions');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
            navbarActions.classList.toggle('active');
        });
    }
    
    // User dropdown toggle
    const userToggle = document.querySelector('.user-toggle');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (userToggle && userDropdown) {
        userToggle.addEventListener('click', function(e) {
            e.preventDefault();
            userDropdown.style.display = userDropdown.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.style.display = 'none';
            }
        });
    }
    
    // Theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const isDark = html.classList.toggle('dark');
            const icon = themeToggle.querySelector('i');
            
            if (isDark) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                document.cookie = 'dark_mode=true; path=/; max-age=31536000'; // 1 year
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                document.cookie = 'dark_mode=false; path=/; max-age=31536000'; // 1 year
            }
        });
        
        // Set initial icon based on current theme
        const icon = themeToggle.querySelector('i');
        if (html.classList.contains('dark')) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }
    
    // Message close
    const messageClose = document.querySelector('.message-close');
    if (messageClose) {
        messageClose.addEventListener('click', function() {
            const message = document.querySelector('.message');
            if (message) {
                message.style.display = 'none';
            }
        });
    }
    
    // Live search
    const liveSearch = document.getElementById('live-search');
    const searchResults = document.getElementById('search-results');
    const searchBtn = document.getElementById('search-btn');
    
    if (liveSearch && searchResults) {
        let searchTimeout;
        
        liveSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(function() {
                fetch(`api/search.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.jobs.length > 0) {
                            let html = '';
                            data.jobs.forEach(job => {
                                html += `
                                    <a href="job-detail.php?id=${job.id}" class="search-result-item">
                                        <div class="search-result-content">
                                            <div class="search-result-title">${job.title}</div>
                                            <div class="search-result-company">${job.company}</div>
                                            <div class="search-result-meta">
                                                <span><i class="fas fa-map-marker-alt"></i> ${job.location}</span>
                                                <span><i class="fas fa-briefcase"></i> ${job.type}</span>
                                            </div>
                                        </div>
                                    </a>
                                `;
                            });
                            searchResults.innerHTML = html;
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.innerHTML = '<div class="search-result-item">No jobs found</div>';
                            searchResults.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }, 300);
        });
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!liveSearch.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
        
        // Search button click
        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                const query = liveSearch.value.trim();
                if (query) {
                    window.location.href = `jobs.php?search=${encodeURIComponent(query)}`;
                }
            });
        }
        
        // Enter key in search input
        liveSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    window.location.href = `jobs.php?search=${encodeURIComponent(query)}`;
                }
            }
        });
    }
    
    // Newsletter form
    const newsletterForm = document.getElementById('newsletter-form');
    const newsletterEmail = document.getElementById('newsletter-email');
    const newsletterMessage = document.getElementById('newsletter-message');
    
    if (newsletterForm && newsletterEmail && newsletterMessage) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = newsletterEmail.value.trim();
            
            if (!email) {
                newsletterMessage.textContent = 'Please enter your email address';
                newsletterMessage.style.color = 'var(--danger-color)';
                return;
            }
            
            fetch('api/newsletter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    newsletterMessage.textContent = data.message;
                    newsletterMessage.style.color = 'var(--success-color)';
                    newsletterEmail.value = '';
                } else {
                    newsletterMessage.textContent = data.message;
                    newsletterMessage.style.color = 'var(--danger-color)';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                newsletterMessage.textContent = 'An error occurred. Please try again.';
                newsletterMessage.style.color = 'var(--danger-color)';
            });
        });
    }
    
    // Save job functionality
    const saveJobBtns = document.querySelectorAll('.save-job');
    
    saveJobBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const jobId = this.getAttribute('data-job-id');
            const isSaved = this.classList.contains('saved');
            
            fetch(`api/${isSaved ? 'unsave-job' : 'save-job'}.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ job_id: jobId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle saved class
                    this.classList.toggle('saved');
                    
                    // Update icon
                    const icon = this.querySelector('i');
                    if (isSaved) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    } else {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    }
                    
                    // Update saved jobs count
                    const savedJobsBadge = document.querySelector('.icon-link[href="saved-jobs.php"] .badge');
                    if (savedJobsBadge) {
                        savedJobsBadge.textContent = data.saved_jobs_count;
                        if (data.saved_jobs_count === 0) {
                            savedJobsBadge.style.display = 'none';
                        } else {
                            savedJobsBadge.style.display = 'flex';
                        }
                    }
                    
                    // Show message
                    showMessage('success', data.message);
                } else {
                    // Show error message
                    showMessage('error', data.message);
                    
                    // Redirect to login if not logged in
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('error', 'An error occurred. Please try again.');
            });
        });
    });
    
    // Apply for job
    const applyNowBtn = document.getElementById('apply-now-btn');
    
    if (applyNowBtn) {
        applyNowBtn.addEventListener('click', function() {
            const jobId = this.getAttribute('data-job-id');
            
            // Check if user is logged in
            fetch('api/check-auth.php')
                .then(response => response.json())
                .then(data => {
                    if (data.logged_in) {
                        // Show application form
                        document.getElementById('application-form').style.display = 'block';
                        this.style.display = 'none';
                    } else {
                        // Redirect to login
                        window.location.href = `auth/login.php?redirect=job-detail.php?id=${jobId}`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    }
    
    // Application form submission
    const applicationForm = document.getElementById('application-form');
    
    if (applicationForm) {
        applicationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const jobId = formData.get('job_id');
            
            fetch('api/apply-job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message);
                    this.reset();
                    this.style.display = 'none';
                    document.getElementById('apply-now-btn').style.display = 'block';
                } else {
                    showMessage('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('error', 'An error occurred. Please try again.');
            });
        });
    }
    
    // Auth tabs
    const authTabs = document.querySelectorAll('.auth-tab');
    
    authTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabContainer = this.closest('.auth-tabs');
            const userType = this.getAttribute('data-tab');
            
            // Update active tab
            authTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update hidden input
            const userTypeInput = document.getElementById('user_type');
            if (userTypeInput) {
                userTypeInput.value = userType;
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Smooth scroll
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Helper functions
    function showMessage(type, text) {
        // Create message element
        const message = document.createElement('div');
        message.className = `message message-${type}`;
        message.innerHTML = `
            <div class="container">
                <p>${text}</p>
                <button class="message-close">&times;</button>
            </div>
        `;
        
        // Insert after header
        const header = document.querySelector('.header');
        header.insertAdjacentElement('afterend', message);
        
        // Add close functionality
        const closeBtn = message.querySelector('.message-close');
        closeBtn.addEventListener('click', function() {
            message.remove();
        });
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            message.remove();
        }, 5000);
    }
});