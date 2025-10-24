# JobLinks - Professional Job Portal

JobLinks is a fully-responsive, professional job portal website built with HTML5, CSS3, vanilla JavaScript, PHP 8, and MySQL 8.

## Features

- Responsive design that works on all devices (mobile, tablet, desktop)
- Dark/light mode toggle with cookie persistence
- User authentication for job seekers and employers
- Advanced job search and filtering
- Job listings with detailed information
- Save jobs functionality
- Online job application system
- Company profiles
- Newsletter subscription
- Contact form
- AJAX features for seamless user experience
- Admin dashboard (basic)
- Security features (CSRF protection, XSS prevention, password hashing)

## Installation

### Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache or Nginx web server
- Composer (optional, for dependency management)

### Steps

1. Clone or download the JobLinks folder to your web server's document root.

2. Create a MySQL database named `joblinks`.

3. Import the `joblinks.sql` file into your database:
   ```bash
   mysql -u username -p joblinks < joblinks.sql