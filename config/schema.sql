CREATE DATABASE IF NOT EXISTS job_order_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE job_order_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    department VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS job_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jo_number VARCHAR(50) UNIQUE NOT NULL,
    requesting_department VARCHAR(150) NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    date_filed DATE NOT NULL,
    date_needed DATE NOT NULL,
    job_description TEXT NOT NULL,
    urgency ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Low',
    status ENUM('Pending', 'Approved', 'Denied', 'Archived') DEFAULT 'Pending',
    requested_by VARCHAR(150),
    requested_by_date DATE,
    noted_by VARCHAR(150),
    approved_by VARCHAR(150),
    assigned_to VARCHAR(150),
    date_received DATE,
    date_accomplished DATE,
    checklist_tasks LONGTEXT,
    admin_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS job_order_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_order_id INT NOT NULL,
    category_id INT NOT NULL,
    other_category_text VARCHAR(255),
    FOREIGN KEY (job_order_id) REFERENCES job_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS job_order_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_order_id INT NOT NULL,
    admin_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_order_id) REFERENCES job_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS job_order_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_order_id INT NOT NULL,
    edited_by INT NOT NULL,
    field_changed VARCHAR(150) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_order_id) REFERENCES job_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS job_order_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    detected_text LONGTEXT,
    import_status ENUM('Pending Review', 'Saved', 'Rejected') DEFAULT 'Pending Review',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notification_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO categories (category_name) VALUES
('Publicity Campaign'),
('Marketing Campaign'),
('Forms'),
('Press Release'),
('Collaterals'),
('Video'),
('Social Media Campaign/Announcement'),
('Off-site Billboard'),
('Crisis Management'),
('Event Management'),
('Event Coverage'),
('Others');

INSERT IGNORE INTO users (full_name, email, password, role, department)
VALUES
('System Admin', 'admin@example.com', '$2y$10$BK9KgHdt8YoXZUVPg.VSDuh1gZ5GlUEPRXHTjmzVd9nGX7LZdBlOy', 'admin', 'BD&MC'),
('Sample User', 'user@example.com', '$2y$10$BK9KgHdt8YoXZUVPg.VSDuh1gZ5GlUEPRXHTjmzVd9nGX7LZdBlOy', 'user', 'Marketing');
