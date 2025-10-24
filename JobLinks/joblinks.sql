-- joblinks.sql
CREATE DATABASE IF NOT EXISTS joblinks;
USE joblinks;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('job_seeker', 'employer', 'admin') DEFAULT 'job_seeker',
    phone VARCHAR(20),
    location VARCHAR(100),
    bio TEXT,
    resume_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    github_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Companies table
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    industry VARCHAR(50),
    size VARCHAR(50),
    website VARCHAR(255),
    logo VARCHAR(255),
    description TEXT,
    location VARCHAR(100),
    founded_year INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Jobs table
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    company_id INT NOT NULL,
    category VARCHAR(50),
    type ENUM('full-time', 'part-time', 'contract', 'internship', 'remote') DEFAULT 'full-time',
    location VARCHAR(100),
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',
    description TEXT,
    requirements TEXT,
    benefits TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    featured BOOLEAN DEFAULT FALSE,
    expires_at DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Applications table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    cover_letter TEXT,
    resume_url VARCHAR(255),
    status ENUM('pending', 'reviewed', 'shortlisted', 'rejected', 'hired') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id)
);

-- Saved jobs table
CREATE TABLE saved_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_saved_job (user_id, job_id)
);

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Newsletter table
CREATE TABLE newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    token VARCHAR(255) NOT NULL,
    subscribed BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO users (name, email, password_hash, role) VALUES 
('Admin User', 'admin@joblinks.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'job_seeker'),
('Jane Smith', 'jane@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer');

INSERT INTO companies (name, slug, industry, size, website, description, location, founded_year) VALUES 
('TechCorp Solutions', 'techcorp-solutions', 'Technology', '100-500', 'https://techcorp.com', 'Leading technology company specializing in software development and digital transformation.', 'San Francisco, CA', 2010),
('Digital Marketing Pro', 'digital-marketing-pro', 'Marketing', '50-100', 'https://dmpro.com', 'Full-service digital marketing agency helping businesses grow online.', 'New York, NY', 2015),
('Healthcare Plus', 'healthcare-plus', 'Healthcare', '500+', 'https://healthcareplus.com', 'Providing quality healthcare services and medical solutions.', 'Boston, MA', 2005);

INSERT INTO jobs (title, slug, company_id, category, type, location, salary_min, salary_max, description, requirements, benefits, expires_at) VALUES 
('Senior Frontend Developer', 'senior-frontend-developer', 1, 'Technology', 'full-time', 'San Francisco, CA', 120000, 160000, 'We are looking for an experienced Frontend Developer to join our growing team.', '5+ years of experience with React, Vue.js or Angular. Strong understanding of modern frontend development practices.', 'Health insurance, 401k, flexible work hours, remote options', '2024-12-31'),
('Digital Marketing Manager', 'digital-marketing-manager', 2, 'Marketing', 'full-time', 'New York, NY', 80000, 100000, 'Lead our digital marketing efforts and help clients achieve their goals.', '3+ years of digital marketing experience. Proven track record of successful campaigns.', 'Health insurance, paid time off, professional development budget', '2024-12-15'),
('Registered Nurse', 'registered-nurse', 3, 'Healthcare', 'full-time', 'Boston, MA', 70000, 90000, 'Join our healthcare team and make a difference in patients lives.', 'Valid nursing license, 2+ years of clinical experience.', 'Excellent benefits package, continuing education support', '2024-12-20'),
('UX/UI Designer', 'ux-ui-designer', 1, 'Design', 'contract', 'Remote', 80000, 100000, 'Create beautiful and intuitive user interfaces for our products.', '3+ years of UX/UI design experience. Strong portfolio required.', 'Flexible schedule, remote work, competitive pay', '2024-11-30'),
('Marketing Intern', 'marketing-intern', 2, 'Marketing', 'internship', 'New York, NY', 20000, 25000, 'Great opportunity to learn digital marketing hands-on.', 'Currently enrolled in marketing or related program.', 'Mentorship program, potential for full-time position', '2024-10-31');

INSERT INTO categories (name, slug, description) VALUES
('Technology', 'technology', 'IT, software development, and tech-related roles'),
('Marketing', 'marketing', 'Digital marketing, advertising, and communications'),
('Healthcare', 'healthcare', 'Medical, nursing, and healthcare administration'),
('Design', 'design', 'Graphic design, UX/UI, and creative roles'),
('Finance', 'finance', 'Accounting, banking, and financial services'),
('Education', 'education', 'Teaching, training, and educational roles');