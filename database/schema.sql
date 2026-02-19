-- Create database
CREATE DATABASE IF NOT EXISTS grade_hub;
USE grade_hub;

-- Users/Profiles table
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('faculty', 'registrar', 'student', 'admin') NOT NULL DEFAULT 'student',
    department VARCHAR(255),
    student_id VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id VARCHAR(36) PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    units INT NOT NULL,
    semester VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    faculty_id VARCHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id),
    INDEX idx_code (code),
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester)
);

-- Enrollments table
CREATE TABLE IF NOT EXISTS enrollments (
    id VARCHAR(36) PRIMARY KEY,
    student_id VARCHAR(36) NOT NULL,
    subject_id VARCHAR(36) NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    UNIQUE KEY unique_enrollment (student_id, subject_id),
    INDEX idx_student (student_id),
    INDEX idx_subject (subject_id)
);

-- Grade entries table
CREATE TABLE IF NOT EXISTS grade_entries (
    id VARCHAR(36) PRIMARY KEY,
    enrollment_id VARCHAR(36) NOT NULL,
    prelim_grade DECIMAL(5,2),
    midterm_grade DECIMAL(5,2),
    final_grade DECIMAL(5,2),
    computed_grade DECIMAL(5,2),
    status ENUM('draft', 'submitted', 'pending_approval', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    remarks TEXT,
    encoded_by VARCHAR(36),
    submitted_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    approved_by VARCHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id),
    FOREIGN KEY (encoded_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_enrollment (enrollment_id),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_approved_at (approved_at)
);

-- Grade correction requests table
CREATE TABLE IF NOT EXISTS grade_corrections (
    id VARCHAR(36) PRIMARY KEY,
    grade_entry_id VARCHAR(36) NOT NULL,
    requested_by VARCHAR(36) NOT NULL,
    original_grade DECIMAL(5,2) NOT NULL,
    requested_grade DECIMAL(5,2) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by VARCHAR(36),
    reviewer_remarks TEXT,
    FOREIGN KEY (grade_entry_id) REFERENCES grade_entries(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_grade_entry (grade_entry_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Remember-me tokens table
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    selector VARCHAR(64) NOT NULL UNIQUE,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    revoked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_selector (selector),
    INDEX idx_user (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    selector VARCHAR(64) NOT NULL UNIQUE,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_selector (selector),
    INDEX idx_user (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_used_at (used_at)
);

-- Create indexes for common queries
CREATE INDEX idx_grade_entries_enrollment ON grade_entries(enrollment_id);
CREATE INDEX idx_enrollments_student_subject ON enrollments(student_id, subject_id);
