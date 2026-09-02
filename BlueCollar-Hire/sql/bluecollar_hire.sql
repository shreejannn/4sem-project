-- crete database

CREATE DATABASE IF NOT EXISTS bluecollar_hire;
USE bluecollar_hire;

-- Table: users
-- Stores clients, workers and the admin account

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('client','worker','admin') NOT NULL DEFAULT 'client',
    avatar VARCHAR(20) DEFAULT '🙂',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Table: categories
-- Job categories that a worker can belong to

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);


-- Table: worker_profiles
-- Extra details for users who registered as "worker"
-- Must be approved by admin before it is publicly visible

CREATE TABLE worker_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    experience INT NOT NULL DEFAULT 0,
    daily_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    address VARCHAR(255) NOT NULL,
    bio TEXT,
    availability ENUM('Available','Busy') NOT NULL DEFAULT 'Available',
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);


-- Table: work_requests
-- A client sends a hire request to a worker profile

CREATE TABLE work_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    worker_profile_id INT NOT NULL,
    work_date DATE NOT NULL,
    location TEXT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending','Accepted','Rejected','Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE
);


-- Sample / seed data


-- Job categories
INSERT INTO categories (name) VALUES
('Plumber'),
('Electrician'),
('Carpenter'),
('Painter'),
('Cleaner'),
('Mechanic'),
('Gardener'),
('Mason');

-- Default admin account
-- email: admin@bluecollar.com | password: admin123
INSERT INTO users (name, email, phone, password, role, avatar) VALUES
('Site Admin', 'admin@bluecollar.com', '9800000000',
'$2y$10$g0hrCLd0NEoGBBDCI0aLc.EN.Fok/lYCL6SvGZSTR9eAROqZltPYq', 'admin', '🛠️');

-- Sample client account
-- email: client@bluecollar.com | password: password123
INSERT INTO users (name, email, phone, password, role, avatar) VALUES
('Rahul Sharma', 'client@bluecollar.com', '9811111111',
'$2y$10$S.og/GlDfWIJYpzTeokBteS9uBlziLad0z2vvRZ1tIiubiUxeQrOy', 'client', '👤');

-- Sample worker accounts
-- email: worker1@bluecollar.com | password: password123
INSERT INTO users (name, email, phone, password, role, avatar) VALUES
('Suresh Thapa', 'worker1@bluecollar.com', '9822222222',
'$2y$10$S.og/GlDfWIJYpzTeokBteS9uBlziLad0z2vvRZ1tIiubiUxeQrOy', 'worker', '👷'),
('Anita Gurung', 'worker2@bluecollar.com', '9833333333',
'$2y$10$S.og/GlDfWIJYpzTeokBteS9uBlziLad0z2vvRZ1tIiubiUxeQrOy', 'worker', '👷‍♀️');

-- Sample worker profiles (already approved so Browse page has data)
INSERT INTO worker_profiles (user_id, category_id, experience, daily_rate, address, bio, availability, status) VALUES
(3, 1, 5, 1200.00, 'Kathmandu, Nepal', 'Experienced plumber for pipe fitting, leak repair and bathroom installation.', 'Available', 'Approved'),
(4, 4, 3, 1000.00, 'Lalitpur, Nepal', 'Professional painter for homes and offices, interior and exterior work.', 'Available', 'Approved');
