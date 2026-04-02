<?php
/**
 * Smart Pole Management System - Database Setup
 * Run this once to create database, tables, and seed data.
 * URL: http://localhost/inba%20project/setup.php
 */

$host = 'localhost';
$user = 'root';
$pass = '';

// Connect without database first
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Enable multi-statement execution
$sql = "
-- =====================================================
-- CREATE DATABASE
-- =====================================================
CREATE DATABASE IF NOT EXISTS smart_pole_db;
USE smart_pole_db;

-- ==================== ROLES ====================
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==================== USERS ====================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    avatar VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended', 'offline') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    reset_token VARCHAR(255),
    reset_token_expiry TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- ==================== WORKERS ====================
CREATE TABLE IF NOT EXISTS workers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    worker_id VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL UNIQUE,
    zone ENUM('north', 'south', 'east', 'west') NOT NULL,
    specialization VARCHAR(100),
    experience_years INT DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 0.0,
    tasks_completed INT DEFAULT 0,
    status ENUM('active', 'on_leave', 'offline', 'busy') DEFAULT 'active',
    current_location_lat DECIMAL(10,8),
    current_location_lng DECIMAL(11,8),
    joined_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==================== POLES ====================
CREATE TABLE IF NOT EXISTS poles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pole_id VARCHAR(20) NOT NULL UNIQUE,
    location VARCHAR(255) NOT NULL,
    zone ENUM('north', 'south', 'east', 'west') NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    installation_date DATE,
    pole_type VARCHAR(50) DEFAULT 'standard',
    height_meters DECIMAL(5,2) DEFAULT 10.0,
    material VARCHAR(50) DEFAULT 'steel',
    power_status BOOLEAN DEFAULT TRUE,
    assigned_worker_id INT,
    status ENUM('active', 'inactive', 'maintenance', 'decommissioned') DEFAULT 'active',
    last_maintenance DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_worker_id) REFERENCES workers(id) ON DELETE SET NULL
);

-- ==================== HARDWARE DATA ====================
CREATE TABLE IF NOT EXISTS hardware_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    pole_id INT NOT NULL,
    vibration DECIMAL(5,2) NOT NULL DEFAULT 0,
    temperature DECIMAL(5,2) NOT NULL DEFAULT 0,
    voltage DECIMAL(6,2) NOT NULL DEFAULT 0,
    current_amp DECIMAL(5,2) DEFAULT 0,
    humidity DECIMAL(5,2) DEFAULT 0,
    tilt_angle DECIMAL(5,2) DEFAULT 0,
    wind_speed DECIMAL(5,2) DEFAULT 0,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE CASCADE,
    INDEX idx_pole_time (pole_id, recorded_at),
    INDEX idx_recorded (recorded_at)
);

-- ==================== ACCELEROMETER READINGS ====================
CREATE TABLE IF NOT EXISTS accelerometer_readings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    pole_id INT,
    accel_x DECIMAL(8,4) NOT NULL DEFAULT 0,
    accel_y DECIMAL(8,4) NOT NULL DEFAULT 0,
    accel_z DECIMAL(8,4) NOT NULL DEFAULT 0,
    magnitude DECIMAL(8,4) NOT NULL DEFAULT 0,
    source_ip VARCHAR(45),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE SET NULL,
    INDEX idx_accel_time (recorded_at),
    INDEX idx_accel_pole (pole_id, recorded_at)
);

-- ==================== RISK LEVELS ====================
CREATE TABLE IF NOT EXISTS risk_levels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pole_id INT NOT NULL UNIQUE,
    risk_score INT DEFAULT 0,
    risk_level ENUM('normal', 'medium', 'high', 'critical') DEFAULT 'normal',
    vibration_score INT DEFAULT 0,
    temperature_score INT DEFAULT 0,
    voltage_score INT DEFAULT 0,
    structural_score INT DEFAULT 0,
    last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    action_required VARCHAR(255),
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE CASCADE
);

-- ==================== ALERTS ====================
CREATE TABLE IF NOT EXISTS alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    alert_id VARCHAR(20) NOT NULL UNIQUE,
    pole_id INT NOT NULL,
    alert_type ENUM('vibration', 'temperature', 'voltage', 'tilt', 'humidity', 'structural', 'power') NOT NULL,
    value VARCHAR(50) NOT NULL,
    threshold VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('pending', 'acknowledged', 'in_progress', 'resolved', 'escalated') DEFAULT 'pending',
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- ==================== FAULT LOGS ====================
CREATE TABLE IF NOT EXISTS fault_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_id VARCHAR(20) NOT NULL UNIQUE,
    pole_id INT NOT NULL,
    fault_type ENUM('hardware', 'software', 'electrical', 'structural', 'environmental') NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('open', 'investigating', 'resolved', 'closed') DEFAULT 'open',
    reported_by INT,
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ==================== TASKS ====================
CREATE TABLE IF NOT EXISTS tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id VARCHAR(20) NOT NULL UNIQUE,
    pole_id INT NOT NULL,
    worker_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    task_type ENUM('maintenance', 'repair', 'inspection', 'installation', 'emergency') DEFAULT 'maintenance',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    due_date DATE,
    estimated_hours DECIMAL(4,1),
    actual_hours DECIMAL(4,1),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_worker (worker_id)
);

-- ==================== COMPLAINTS ====================
CREATE TABLE IF NOT EXISTS complaints (
    id INT PRIMARY KEY AUTO_INCREMENT,
    complaint_id VARCHAR(20) NOT NULL UNIQUE,
    citizen_name VARCHAR(100) NOT NULL,
    citizen_phone VARCHAR(20),
    citizen_email VARCHAR(100),
    pole_id INT,
    issue TEXT NOT NULL,
    category ENUM('safety', 'electrical', 'structural', 'lighting', 'noise', 'other') DEFAULT 'other',
    status ENUM('open', 'assigned', 'in_progress', 'resolved', 'escalated', 'closed') DEFAULT 'open',
    assigned_worker_id INT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    remarks TEXT,
    resolution_notes TEXT,
    resolved_at TIMESTAMP NULL,
    escalated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_worker_id) REFERENCES workers(id) ON DELETE SET NULL
);

-- ==================== PROOF VERIFICATION ====================
CREATE TABLE IF NOT EXISTS proof_verification (
    id INT PRIMARY KEY AUTO_INCREMENT,
    proof_id VARCHAR(20) NOT NULL UNIQUE,
    task_id INT,
    complaint_id INT,
    worker_id INT NOT NULL,
    pole_id INT NOT NULL,
    proof_type ENUM('before_repair', 'during_repair', 'after_repair', 'inspection', 'incident') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    file_size INT,
    remarks TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE SET NULL,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ==================== CUTOFF REQUESTS ====================
CREATE TABLE IF NOT EXISTS cutoff_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id VARCHAR(20) NOT NULL UNIQUE,
    pole_id INT NOT NULL,
    action ENUM('cutoff', 'restore') NOT NULL,
    reason TEXT,
    requested_by INT NOT NULL,
    approved_by INT,
    status ENUM('pending', 'approved', 'rejected', 'executed') DEFAULT 'pending',
    executed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pole_id) REFERENCES poles(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- ==================== SYSTEM LOGS ====================
CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id VARCHAR(50),
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- ==================== LOGIN ATTEMPTS (Brute-Force Protection) ====================
CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(100),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_attempted (attempted_at)
);

-- ==================== USER SESSIONS (Session Tracking) ====================
CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    logout_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_active (is_active)
);

-- ==================== PASSWORD HISTORY (Prevent Reuse) ====================
CREATE TABLE IF NOT EXISTS password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- ==================== SECURITY LOGS (Audit Trail) ====================
CREATE TABLE IF NOT EXISTS security_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    event_type ENUM('login','logout','failed_login','register','password_change','account_locked','account_unlocked','session_expired','suspicious_activity') NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    severity ENUM('info','warning','critical') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event (event_type),
    INDEX idx_severity (severity),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id)
);

-- ==================== IP BLACKLIST ====================
CREATE TABLE IF NOT EXISTS ip_blacklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    reason VARCHAR(255),
    blocked_by INT,
    is_permanent BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE INDEX idx_ip (ip_address)
);

-- ==================== CSRF TOKENS ====================
CREATE TABLE IF NOT EXISTS csrf_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(128) NOT NULL,
    token VARCHAR(128) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_token (token)
);

-- ==================== MATERIAL REQUESTS ====================
CREATE TABLE IF NOT EXISTS material_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    worker_id INT NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected', 'delivered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
);

-- ==================== NOTIFICATIONS ====================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('alert', 'task', 'complaint', 'system', 'info') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
);
";

// Execute table creation
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
}

if ($conn->error) {
    die("Table creation error: " . $conn->error);
}

$conn->close();

// Reconnect to the database for seeding
$conn = new mysqli($host, $user, $pass, 'smart_pole_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Check if already seeded
$check = $conn->query("SELECT COUNT(*) as cnt FROM roles");
$row = $check->fetch_assoc();
if ($row['cnt'] > 0) {
    echo "<h2 style='color:green;font-family:sans-serif;'>&#10004; Database already set up! Tables exist and are seeded.</h2>";
    echo "<p style='font-family:sans-serif;'><a href='index.php'>Go to Login Page</a></p>";
    $conn->close();
    exit;
}

// SEED DATA
$seed = "
-- SEED ROLES
INSERT INTO roles (role_name, description, permissions) VALUES
('admin', 'Full system administrator', '{\"all\": true}'),
('worker', 'Field maintenance worker', '{\"tasks\": true, \"poles\": \"assigned\", \"complaints\": \"assigned\", \"proofs\": true}'),
('viewer', 'Read-only access', '{\"read\": true}');
";
$conn->multi_query($seed);
do { if ($r = $conn->store_result()) $r->free(); } while ($conn->next_result());

// Seed users with PHP password_hash
$users = [
    ['USR-001', 'Admin User', 'admin@smart.com', 'admin123', '+91 99999 00001', 1],
    ['USR-002', 'Ravi Kumar', 'worker@smart.com', 'worker123', '+91 98765 43210', 2],
    ['USR-003', 'Priya Sharma', 'priya@smart.com', 'worker123', '+91 98765 43211', 2],
    ['USR-004', 'Amit Patel', 'amit@smart.com', 'admin123', '+91 98765 43213', 1],
    ['USR-005', 'Sanjay Reddy', 'sanjay@smart.com', 'worker123', '+91 98765 43212', 2],
    ['USR-006', 'Deepak Singh', 'deepak@smart.com', 'worker123', '+91 98765 43214', 2],
    ['USR-007', 'Meera Joshi', 'meera@smart.com', 'worker123', '+91 98765 43215', 2],
    ['USR-008', 'Viewer User', 'viewer@smart.com', 'viewer123', '+91 99999 00002', 3],
];

$stmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
foreach ($users as $u) {
    $hash = password_hash($u[3], PASSWORD_DEFAULT);
    $stmt->bind_param("sssssi", $u[0], $u[1], $u[2], $hash, $u[4], $u[5]);
    $stmt->execute();
}
$stmt->close();

// Seed workers
$conn->query("INSERT INTO workers (worker_id, user_id, zone, specialization, experience_years, rating, tasks_completed, status, joined_date) VALUES
('WRK-001', 2, 'north', 'Electrical Systems', 5, 4.5, 156, 'active', '2019-03-15'),
('WRK-002', 3, 'south', 'Structural Repair', 4, 4.7, 142, 'active', '2020-01-10'),
('WRK-003', 5, 'west', 'General Maintenance', 3, 4.2, 98, 'offline', '2021-06-20'),
('WRK-004', 4, 'east', 'Sensor Calibration', 6, 4.4, 134, 'active', '2018-09-01'),
('WRK-005', 6, 'north', 'Electrical Systems', 2, 4.0, 89, 'active', '2022-02-14'),
('WRK-006', 7, 'south', 'Painting & Finishing', 3, 4.6, 112, 'active', '2021-08-05')");

// Seed poles
$conn->query("INSERT INTO poles (pole_id, location, zone, latitude, longitude, installation_date, assigned_worker_id, power_status) VALUES
('POLE-001', 'Salem Junction', 'north', 11.6643, 78.1460, '2020-01-15', 1, TRUE),
('POLE-012', 'Trichy Chatram', 'north', 10.8280, 78.6995, '2020-02-20', 1, TRUE),
('POLE-023', 'Madurai Meenakshi', 'south', 9.9195, 78.1195, '2020-03-10', 2, TRUE),
('POLE-034', 'Coimbatore Town Hall', 'north', 11.0168, 76.9558, '2019-11-05', 1, TRUE),
('POLE-047', 'Tirunelveli Junction', 'south', 8.7274, 77.6970, '2019-08-20', 1, TRUE),
('POLE-056', 'Erode Gandhipuram', 'north', 11.3408, 77.7172, '2020-04-18', 2, TRUE),
('POLE-067', 'Vellore Fort', 'south', 12.9245, 79.1355, '2020-05-25', 3, TRUE),
('POLE-078', 'Thanjavur Old Bus Stand', 'east', 10.7816, 79.1367, '2020-06-30', 4, TRUE),
('POLE-089', 'Dindigul Periyar', 'south', 10.3621, 77.9690, '2019-12-12', 1, TRUE),
('POLE-098', 'Karur Bus Stand', 'north', 10.9568, 78.0790, '2020-07-15', 2, TRUE),
('POLE-112', 'Nagercoil Vannarpettai', 'east', 8.1825, 77.4180, '2020-08-22', 1, TRUE),
('POLE-123', 'Kanyakumari Beach', 'west', 8.0833, 77.5412, '2020-09-10', 3, TRUE),
('POLE-134', 'Ooty RS Puram', 'south', 11.4100, 76.6900, '2020-10-05', 2, TRUE),
('POLE-145', 'Tiruppur Bus Stand', 'north', 11.1092, 77.3472, '2020-11-18', 4, TRUE),
('POLE-156', 'Kumbakonam Big Temple', 'south', 10.9594, 79.3801, '2019-07-08', 1, FALSE),
('POLE-167', 'Rajapalayam Market', 'west', 9.4500, 77.5550, '2020-12-20', 3, TRUE),
('POLE-178', 'Pudukkottai Town', 'east', 10.3823, 78.8220, '2021-01-14', 2, TRUE),
('POLE-189', 'Hosur Bus Stand', 'north', 12.7400, 77.8200, '2021-02-28', 4, TRUE),
('POLE-201', 'Ambur Town', 'south', 12.7855, 78.7100, '2019-05-10', 1, TRUE),
('POLE-212', 'Karaikudi Bypass', 'west', 10.0750, 78.7900, '2021-03-15', 3, TRUE),
('POLE-220', 'Neyveli Arch', 'north', 11.5329, 79.4895, '2021-04-10', 1, TRUE),
('POLE-221', 'Cuddalore Port', 'north', 11.7445, 79.7600, '2021-04-18', 1, TRUE),
('POLE-222', 'Kanchipuram Silk', 'west', 12.8323, 79.7082, '2021-05-05', 3, TRUE),
('POLE-223', 'Tiruvannamalai Temple', 'north', 12.2250, 79.0670, '2021-05-20', 5, TRUE),
('POLE-224', 'Arakkonam Bazaar', 'north', 13.0875, 79.6630, '2021-06-01', 5, TRUE),
('POLE-225', 'Tiruvallur Town', 'west', 13.1468, 79.9078, '2021-06-15', 3, TRUE),
('POLE-226', 'Chengalpattu Junction', 'south', 12.6956, 79.9773, '2021-07-01', 2, TRUE),
('POLE-227', 'Villupuram Bus Stand', 'south', 11.9418, 79.4842, '2021-07-12', 6, TRUE),
('POLE-228', 'Kallakurichi Bypass', 'south', 11.7387, 78.9635, '2021-07-25', 6, TRUE),
('POLE-229', 'Perambalur Town', 'east', 10.2310, 78.8810, '2021-08-03', 4, TRUE),
('POLE-230', 'Ariyalur Bypass', 'east', 11.1362, 79.0750, '2021-08-14', 4, TRUE),
('POLE-231', 'Nagapattinam Beach', 'east', 10.7675, 79.8423, '2021-08-22', 4, TRUE),
('POLE-232', 'Tiruvarur Town', 'east', 10.7720, 79.6390, '2021-09-05', 4, TRUE),
('POLE-233', 'Mayiladuthurai Bus Stand', 'east', 11.1090, 79.6550, '2021-09-15', 4, TRUE),
('POLE-234', 'Sivaganga Town', 'north', 10.1222, 78.4845, '2021-09-28', 5, TRUE),
('POLE-235', 'Ramanathapuram Palace', 'north', 9.3630, 78.8320, '2021-10-05', 5, TRUE),
('POLE-236', 'Virudhunagar Bypass', 'north', 9.5880, 77.9550, '2021-10-18', 5, TRUE),
('POLE-237', 'Tenkasi Junction', 'west', 8.9515, 77.3190, '2021-10-30', 3, TRUE),
('POLE-238', 'Thoothukudi Port', 'west', 8.7620, 78.1395, '2021-11-08', 3, FALSE),
('POLE-239', 'Kovilpatti Town', 'south', 9.1773, 77.8730, '2021-11-20', 6, TRUE),
('POLE-240', 'Sankarankovil Bypass', 'south', 9.1730, 77.5320, '2021-12-01', 6, TRUE),
('POLE-241', 'Palani Temple', 'south', 10.4490, 77.5190, '2021-12-12', 2, TRUE),
('POLE-242', 'Pollachi Bus Stand', 'south', 10.6590, 77.0045, '2021-12-22', 2, FALSE),
('POLE-243', 'Udumalpet Town', 'south', 10.5828, 77.2482, '2022-01-05', 6, TRUE),
('POLE-244', 'Mettupalayam Bypass', 'south', 10.3015, 76.9293, '2022-01-18', 1, TRUE),
('POLE-245', 'Dharapuram Town', 'east', 10.7390, 77.5210, '2022-02-01', 1, TRUE),
('POLE-246', 'Kangeyam Bypass', 'east', 11.0085, 77.5587, '2022-02-14', 1, TRUE),
('POLE-247', 'Vellakoil Town', 'west', 10.9380, 77.7130, '2022-03-01', 3, TRUE),
('POLE-248', 'Gobichettipalayam', 'west', 11.4590, 77.4370, '2022-03-12', 3, TRUE),
('POLE-249', 'Sathyamangalam', 'north', 11.5060, 77.2450, '2022-03-25', 5, TRUE),
('POLE-250', 'Bhavani Town', 'north', 11.4435, 77.6870, '2022-04-05', 5, TRUE)");


// Seed hardware data
$conn->query("INSERT INTO hardware_data (pole_id, vibration, temperature, voltage, current_amp, humidity, tilt_angle) VALUES
(1, 2.1, 35, 230, 5.2, 45, 0.3),
(2, 3.5, 42, 225, 5.5, 48, 0.5),
(3, 1.2, 33, 232, 4.8, 42, 0.2),
(4, 5.8, 55, 210, 6.1, 52, 1.2),
(5, 8.5, 72, 195, 7.8, 58, 3.5),
(6, 1.8, 36, 229, 5.0, 44, 0.3),
(7, 4.2, 48, 218, 5.8, 50, 0.8),
(8, 2.5, 38, 228, 5.1, 46, 0.4),
(9, 6.1, 58, 205, 6.5, 55, 1.8),
(10, 1.0, 32, 231, 4.7, 43, 0.2),
(11, 1.5, 34, 230, 4.9, 44, 0.3),
(12, 7.2, 65, 198, 7.2, 60, 2.5),
(13, 3.0, 40, 226, 5.3, 47, 0.6),
(14, 1.3, 33, 231, 4.8, 43, 0.2),
(15, 9.0, 78, 190, 8.5, 65, 4.2),
(16, 2.8, 37, 228, 5.2, 46, 0.4),
(17, 4.5, 46, 220, 5.7, 49, 0.7),
(18, 5.5, 52, 212, 6.0, 53, 1.0),
(19, 8.8, 75, 192, 8.0, 62, 3.8),
(20, 1.6, 35, 230, 5.0, 45, 0.3),
(21, 1.9, 34, 231, 5.1, 44, 0.2),
(22, 2.3, 36, 229, 5.3, 46, 0.4),
(23, 3.8, 44, 222, 5.6, 49, 0.6),
(24, 1.4, 32, 232, 4.9, 42, 0.2),
(25, 6.5, 60, 202, 6.8, 56, 1.5),
(26, 2.0, 35, 230, 5.0, 45, 0.3),
(27, 4.8, 50, 215, 5.9, 51, 0.9),
(28, 3.2, 41, 224, 5.4, 48, 0.5),
(29, 7.5, 68, 196, 7.5, 61, 2.8),
(30, 1.1, 31, 233, 4.6, 41, 0.1),
(31, 2.6, 37, 228, 5.2, 46, 0.4),
(32, 5.2, 53, 211, 6.2, 54, 1.1),
(33, 1.7, 34, 230, 5.0, 44, 0.3),
(34, 8.2, 71, 194, 7.9, 59, 3.2),
(35, 3.4, 39, 226, 5.4, 47, 0.5),
(36, 1.3, 33, 231, 4.8, 43, 0.2),
(37, 4.0, 45, 221, 5.6, 49, 0.7),
(38, 6.8, 62, 200, 7.0, 57, 2.0),
(39, 2.2, 36, 229, 5.1, 45, 0.3),
(40, 5.0, 51, 214, 6.0, 52, 1.0),
(41, 3.6, 43, 223, 5.5, 48, 0.6),
(42, 9.2, 80, 188, 8.8, 66, 4.5),
(43, 1.5, 33, 231, 4.9, 43, 0.2),
(44, 2.7, 38, 227, 5.2, 46, 0.4),
(45, 4.3, 47, 219, 5.7, 50, 0.8),
(46, 7.0, 64, 199, 7.3, 59, 2.3),
(47, 1.8, 35, 230, 5.0, 45, 0.3),
(48, 3.1, 40, 225, 5.3, 47, 0.5),
(49, 5.8, 56, 208, 6.4, 54, 1.3),
(50, 2.4, 37, 228, 5.1, 45, 0.4)");

// Seed risk levels
$conn->query("INSERT INTO risk_levels (pole_id, risk_score, risk_level, vibration_score, temperature_score, voltage_score, structural_score, action_required) VALUES
(1, 15, 'normal', 10, 15, 5, 5, 'Routine Check'),
(2, 42, 'medium', 35, 40, 15, 10, 'Monitor Closely'),
(3, 10, 'normal', 8, 10, 3, 3, 'Routine Check'),
(4, 72, 'high', 60, 65, 40, 25, 'Schedule Repair'),
(5, 95, 'critical', 90, 85, 70, 55, 'Immediate Inspection'),
(6, 12, 'normal', 12, 15, 5, 5, 'Routine Check'),
(7, 45, 'medium', 40, 45, 20, 15, 'Monitor Closely'),
(8, 18, 'normal', 15, 20, 8, 8, 'Routine Check'),
(9, 75, 'high', 65, 70, 45, 30, 'Schedule Repair'),
(10, 8, 'normal', 5, 8, 3, 2, 'Routine Check'),
(11, 12, 'normal', 10, 12, 5, 5, 'Routine Check'),
(12, 88, 'critical', 80, 78, 65, 45, 'Immediate Inspection'),
(13, 35, 'medium', 30, 35, 12, 10, 'Monitor Closely'),
(14, 10, 'normal', 8, 10, 4, 3, 'Routine Check'),
(15, 98, 'critical', 95, 92, 80, 65, 'Immediate Inspection'),
(16, 20, 'normal', 18, 18, 8, 8, 'Routine Check'),
(17, 48, 'medium', 42, 42, 22, 15, 'Monitor Closely'),
(18, 68, 'high', 58, 55, 38, 22, 'Schedule Repair'),
(19, 96, 'critical', 92, 88, 75, 60, 'Immediate Inspection'),
(20, 13, 'normal', 10, 12, 5, 5, 'Routine Check'),
(21, 14, 'normal', 12, 14, 5, 5, 'Routine Check'),
(22, 22, 'normal', 18, 20, 8, 7, 'Routine Check'),
(23, 40, 'medium', 35, 38, 16, 12, 'Monitor Closely'),
(24, 11, 'normal', 9, 10, 4, 3, 'Routine Check'),
(25, 78, 'high', 68, 72, 48, 32, 'Schedule Repair'),
(26, 16, 'normal', 13, 15, 6, 5, 'Routine Check'),
(27, 52, 'medium', 45, 48, 25, 18, 'Monitor Closely'),
(28, 32, 'medium', 28, 32, 12, 9, 'Monitor Closely'),
(29, 85, 'critical', 78, 76, 62, 42, 'Immediate Inspection'),
(30, 9, 'normal', 6, 8, 3, 2, 'Routine Check'),
(31, 25, 'normal', 20, 22, 10, 8, 'Routine Check'),
(32, 62, 'high', 52, 50, 35, 20, 'Schedule Repair'),
(33, 13, 'normal', 10, 12, 5, 4, 'Routine Check'),
(34, 92, 'critical', 85, 82, 72, 58, 'Immediate Inspection'),
(35, 34, 'medium', 30, 32, 12, 10, 'Monitor Closely'),
(36, 10, 'normal', 8, 10, 4, 3, 'Routine Check'),
(37, 42, 'medium', 38, 40, 18, 14, 'Monitor Closely'),
(38, 80, 'high', 72, 70, 52, 35, 'Schedule Repair'),
(39, 18, 'normal', 15, 16, 7, 6, 'Routine Check'),
(40, 58, 'medium', 48, 50, 28, 20, 'Monitor Closely'),
(41, 38, 'medium', 34, 36, 14, 11, 'Monitor Closely'),
(42, 97, 'critical', 94, 90, 78, 62, 'Immediate Inspection'),
(43, 12, 'normal', 10, 11, 5, 4, 'Routine Check'),
(44, 24, 'normal', 20, 22, 9, 7, 'Routine Check'),
(45, 46, 'medium', 40, 42, 20, 15, 'Monitor Closely'),
(46, 82, 'high', 74, 72, 55, 38, 'Schedule Repair'),
(47, 15, 'normal', 12, 14, 6, 5, 'Routine Check'),
(48, 30, 'medium', 26, 28, 11, 8, 'Monitor Closely'),
(49, 70, 'high', 60, 58, 42, 28, 'Schedule Repair'),
(50, 20, 'normal', 16, 18, 8, 7, 'Routine Check')");

// Seed alerts
$conn->query("INSERT INTO alerts (alert_id, pole_id, alert_type, value, threshold, severity, status) VALUES
('ALT-001', 5, 'vibration', '8.5 mm/s', '5.0 mm/s', 'critical', 'pending'),
('ALT-002', 15, 'temperature', '78°C', '60°C', 'critical', 'pending'),
('ALT-003', 12, 'voltage', '198V', '210V', 'high', 'in_progress'),
('ALT-004', 9, 'vibration', '6.1 mm/s', '5.0 mm/s', 'high', 'in_progress'),
('ALT-005', 19, 'tilt', '12°', '8°', 'critical', 'pending'),
('ALT-006', 4, 'temperature', '55°C', '50°C', 'medium', 'resolved'),
('ALT-007', 7, 'vibration', '4.2 mm/s', '4.0 mm/s', 'medium', 'resolved'),
('ALT-008', 18, 'voltage', '212V', '215V', 'medium', 'pending'),
('ALT-009', 25, 'vibration', '6.5 mm/s', '5.0 mm/s', 'high', 'pending'),
('ALT-010', 29, 'temperature', '68°C', '60°C', 'high', 'pending'),
('ALT-011', 34, 'vibration', '8.2 mm/s', '5.0 mm/s', 'critical', 'pending'),
('ALT-012', 38, 'voltage', '200V', '210V', 'high', 'pending'),
('ALT-013', 42, 'temperature', '80°C', '60°C', 'critical', 'pending'),
('ALT-014', 32, 'tilt', '5.2°', '4.0°', 'medium', 'in_progress'),
('ALT-015', 46, 'vibration', '7.0 mm/s', '5.0 mm/s', 'high', 'pending'),
('ALT-016', 49, 'temperature', '56°C', '50°C', 'medium', 'pending'),
('ALT-017', 27, 'humidity', '65%', '60%', 'medium', 'pending'),
('ALT-018', 40, 'voltage', '214V', '215V', 'low', 'pending')");

// Seed fault logs
$conn->query("INSERT INTO fault_logs (log_id, pole_id, fault_type, description, severity, status, reported_by, resolved_by) VALUES
('FLT-001', 5, 'structural', 'Excessive vibration - possible foundation issue', 'critical', 'open', 1, NULL),
('FLT-002', 15, 'electrical', 'Overheating in transformer unit', 'critical', 'open', 1, NULL),
('FLT-003', 9, 'hardware', 'Sensor malfunction - vibration sensor', 'high', 'resolved', 1, 2),
('FLT-004', 12, 'electrical', 'Voltage regulator failure', 'high', 'open', 1, NULL),
('FLT-005', 4, 'software', 'Data transmission error - firmware update needed', 'medium', 'resolved', 1, 3),
('FLT-006', 7, 'structural', 'Minor corrosion at base plate', 'medium', 'open', 1, NULL)");

// Seed tasks
$conn->query("INSERT INTO tasks (task_id, pole_id, worker_id, title, description, task_type, priority, status, due_date) VALUES
('TSK-041', 5, 1, 'High Vibration - Foundation Check', 'Inspect foundation for damage due to high vibration readings', 'emergency', 'critical', 'pending', '2024-01-16'),
('TSK-042', 9, 1, 'Sensor Replacement', 'Replace malfunctioning vibration sensor', 'repair', 'high', 'in_progress', '2024-01-16'),
('TSK-043', 2, 1, 'Routine Maintenance', 'Monthly routine maintenance check', 'maintenance', 'medium', 'pending', '2024-01-17'),
('TSK-044', 19, 1, 'Emergency - Tilt Correction', 'Critical tilt detected - needs immediate correction', 'emergency', 'critical', 'pending', '2024-01-15'),
('TSK-045', 11, 1, 'Paint & Cleaning', 'Repaint and clean pole exterior', 'maintenance', 'low', 'completed', '2024-01-14'),
('TSK-046', 4, 2, 'Firmware Update', 'Update sensor firmware to latest version', 'maintenance', 'medium', 'completed', '2024-01-14'),
('TSK-047', 21, 1, 'Routine Inspection - Neyveli Arch', 'Monthly inspection of pole near Neyveli Arch', 'inspection', 'medium', 'pending', '2024-01-20'),
('TSK-048', 22, 1, 'LED Light Replacement', 'Replace burnt-out LED panel on Cuddalore Port pole', 'repair', 'high', 'pending', '2024-01-18'),
('TSK-049', 25, 5, 'Critical Vibration Alert', 'Investigate excessive vibration near Arakkonam Bazaar', 'emergency', 'critical', 'pending', '2024-01-16'),
('TSK-050', 27, 6, 'Corrosion Treatment', 'Apply anti-corrosion coating at Chengalpattu Junction pole base', 'maintenance', 'medium', 'in_progress', '2024-01-19'),
('TSK-051', 29, 4, 'Transformer Overhaul', 'Critical overheating in Kallakurichi Bypass transformer unit', 'emergency', 'critical', 'pending', '2024-01-15'),
('TSK-052', 30, 4, 'Sensor Calibration', 'Recalibrate all sensors at Perambalur Town', 'maintenance', 'medium', 'pending', '2024-01-21'),
('TSK-053', 32, 4, 'Tilt Sensor Fix', 'Repair tilt sensor showing incorrect readings at Nagapattinam Beach', 'repair', 'high', 'in_progress', '2024-01-17'),
('TSK-054', 34, 5, 'Emergency Structural Repair', 'Critical foundation crack at Mayiladuthurai Bus Stand', 'emergency', 'critical', 'pending', '2024-01-15'),
('TSK-055', 35, 5, 'Wiring Inspection', 'Inspect underground wiring connections at Sivaganga Town', 'inspection', 'medium', 'pending', '2024-01-22'),
('TSK-056', 37, 3, 'Panel Replacement', 'Replace damaged solar panel at Virudhunagar Bypass', 'repair', 'high', 'pending', '2024-01-18'),
('TSK-057', 38, 3, 'Power Restoration', 'Restore power to Tenkasi Junction pole - currently offline', 'repair', 'critical', 'pending', '2024-01-16'),
('TSK-058', 39, 6, 'Routine Maintenance', 'Monthly routine check at Thoothukudi Port', 'maintenance', 'low', 'pending', '2024-01-23'),
('TSK-059', 40, 6, 'Voltage Regulator Check', 'Inspect voltage regulator at Kovilpatti Town', 'inspection', 'medium', 'pending', '2024-01-20'),
('TSK-060', 42, 2, 'Critical - Power Outage', 'Investigate and fix power failure at Palani Temple', 'emergency', 'critical', 'pending', '2024-01-15'),
('TSK-061', 44, 1, 'Painting & Cleanup', 'Repaint and clean pole at Udumalpet Town', 'maintenance', 'low', 'pending', '2024-01-24'),
('TSK-062', 45, 1, 'Traffic Sensor Install', 'Install new traffic monitoring sensor at Mettupalayam Bypass', 'installation', 'high', 'pending', '2024-01-19'),
('TSK-063', 46, 1, 'Camera Mount Repair', 'Fix broken CCTV camera mount at Dharapuram Town', 'repair', 'medium', 'pending', '2024-01-20'),
('TSK-064', 47, 3, 'Routine Inspection', 'Quarterly inspection at Kangeyam Bypass pole', 'inspection', 'low', 'pending', '2024-01-25'),
('TSK-065', 48, 3, 'Cable Replacement', 'Replace damaged power cable at Vellakoil Town', 'repair', 'high', 'pending', '2024-01-18'),
('TSK-066', 49, 5, 'High Temp Investigation', 'Investigate high temperature readings at Gobichettipalayam', 'inspection', 'high', 'in_progress', '2024-01-17'),
('TSK-067', 50, 5, 'Pole Assessment', 'Structural assessment of pole near Sathyamangalam', 'inspection', 'medium', 'pending', '2024-01-22'),
('TSK-068', 23, 3, 'Humidity Sensor Replace', 'Replace faulty humidity sensor at Kanchipuram Silk', 'repair', 'medium', 'pending', '2024-01-21'),
('TSK-069', 28, 6, 'Villupuram Maintenance', 'Scheduled maintenance at Villupuram Bus Stand pole', 'maintenance', 'low', 'pending', '2024-01-23'),
('TSK-070', 41, 2, 'Voltage Spike Analysis', 'Analyze recurring voltage spikes at Sankarankovil Bypass pole', 'inspection', 'high', 'pending', '2024-01-19')");

// Seed complaints
$conn->query("INSERT INTO complaints (complaint_id, citizen_name, citizen_phone, citizen_email, pole_id, issue, category, status, assigned_worker_id, priority, remarks) VALUES
('CMP-015', 'Rajesh Gupta', '+91 99887 76655', 'rajesh@email.com', 5, 'Pole shaking during wind', 'structural', 'open', 1, 'high', NULL),
('CMP-016', 'Sunita Devi', '+91 99887 76656', 'sunita@email.com', 15, 'Sparking observed at night', 'electrical', 'open', NULL, 'urgent', NULL),
('CMP-017', 'Mohammed Ali', '+91 99887 76657', 'ali@email.com', 9, 'Pole leaning sideways', 'structural', 'assigned', 1, 'high', 'Inspection scheduled'),
('CMP-018', 'Anita Roy', '+91 99887 76658', 'anita@email.com', 11, 'Light not working', 'lighting', 'resolved', 1, 'medium', 'Bulb replaced'),
('CMP-019', 'Vikram Singh', '+91 99887 76659', 'vikram@email.com', 4, 'Buzzing sound from transformer', 'noise', 'escalated', 2, 'high', 'Needs expert inspection')");

// Seed proofs
$conn->query("INSERT INTO proof_verification (proof_id, task_id, worker_id, pole_id, proof_type, file_path, file_name, status) VALUES
('PRF-001', 5, 1, 11, 'after_repair', '/uploads/proofs/prf001.jpg', 'pole112_after.jpg', 'approved'),
('PRF-002', 6, 2, 4, 'before_repair', '/uploads/proofs/prf002.jpg', 'pole034_before.jpg', 'approved'),
('PRF-003', 2, 1, 9, 'before_repair', '/uploads/proofs/prf003.jpg', 'pole089_before.jpg', 'pending'),
('PRF-004', 2, 1, 9, 'during_repair', '/uploads/proofs/prf004.jpg', 'pole089_during.jpg', 'pending'),
('PRF-005', 1, 1, 5, 'inspection', '/uploads/proofs/prf005.jpg', 'pole047_inspection.jpg', 'pending')");

// Seed cutoff requests
$conn->query("INSERT INTO cutoff_requests (request_id, pole_id, action, reason, requested_by, status) VALUES
('CUT-001', 15, 'cutoff', 'Critical overheating - safety cutoff', 1, 'executed'),
('CUT-002', 5, 'cutoff', 'High vibration - pending inspection', 1, 'pending'),
('CUT-003', 19, 'cutoff', 'Severe tilt - safety concern', 1, 'pending')");

echo "<html><head><style>body{font-family:'Segoe UI',sans-serif;background:#0a0a1a;color:#fff;padding:40px;text-align:center;}
h1{color:#6c5ce7;} .success{color:#00b894;font-size:24px;} a{color:#6c5ce7;font-size:18px;}</style></head><body>";
echo "<h1>&#9889; Smart Pole Management System</h1>";
echo "<div class='success'>&#10004; Database created and seeded successfully!</div>";
echo "<br><br><a href='index.php'>&#8594; Go to Login Page</a>";
echo "</body></html>";

$conn->close();
?>
