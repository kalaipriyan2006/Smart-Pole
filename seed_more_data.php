<?php
/**
 * Seed MORE sample data into Smart Pole Management System
 * Adds many more records to: users, workers, alerts, fault_logs, complaints, cutoff_requests, proof_verification
 */

$conn = new mysqli('localhost', 'root', '', 'smart_pole_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$errors = [];
$success = [];

// =====================================================
// 1. ADD MORE USERS (15 new users)
// =====================================================
$newUsers = [
    ['USR-010', 'Karthik Nair',     'karthik@smart.com',    'worker123', '+91 98000 10001', 2],
    ['USR-011', 'Lakshmi Iyer',     'lakshmi@smart.com',    'worker123', '+91 98000 10002', 2],
    ['USR-012', 'Suresh Babu',      'suresh@smart.com',     'worker123', '+91 98000 10003', 2],
    ['USR-013', 'Neha Kapoor',      'neha@smart.com',       'worker123', '+91 98000 10004', 2],
    ['USR-014', 'Rahul Verma',      'rahul@smart.com',      'worker123', '+91 98000 10005', 2],
    ['USR-015', 'Pooja Deshmukh',   'pooja@smart.com',      'worker123', '+91 98000 10006', 2],
    ['USR-016', 'Arjun Mehta',      'arjun@smart.com',      'admin123',  '+91 98000 10007', 1],
    ['USR-017', 'Divya Pillai',     'divya@smart.com',      'viewer123', '+91 98000 10008', 3],
    ['USR-018', 'Manish Tiwari',    'manish@smart.com',     'worker123', '+91 98000 10009', 2],
    ['USR-019', 'Kavita Rao',       'kavita@smart.com',     'worker123', '+91 98000 10010', 2],
    ['USR-020', 'Bharat Yadav',     'bharat@smart.com',     'worker123', '+91 98000 10011', 2],
    ['USR-021', 'Sneha Jain',       'sneha@smart.com',      'viewer123', '+91 98000 10012', 3],
    ['USR-022', 'Vijay Chauhan',    'vijay@smart.com',      'worker123', '+91 98000 10013', 2],
    ['USR-023', 'Rekha Pandey',     'rekha@smart.com',      'admin123',  '+91 98000 10014', 1],
    ['USR-024', 'Gaurav Saxena',    'gaurav@smart.com',     'worker123', '+91 98000 10015', 2],
];

$stmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
$userCount = 0;
foreach ($newUsers as $u) {
    $hash = password_hash($u[3], PASSWORD_DEFAULT);
    $stmt->bind_param("sssssi", $u[0], $u[1], $u[2], $hash, $u[4], $u[5]);
    if ($stmt->execute()) {
        $userCount++;
    }
}
$stmt->close();
$success[] = "Users: $userCount new users added";

// =====================================================
// 2. ADD MORE WORKERS (12 new workers linked to new users)
// =====================================================
// Get user IDs for new worker users
$workerInserts = [
    ['WRK-007', 'USR-010', 'east',  'Electrical Systems',    4, 4.3, 78],
    ['WRK-008', 'USR-011', 'west',  'LED & Lighting',        3, 4.5, 65],
    ['WRK-009', 'USR-012', 'north', 'Structural Welding',    7, 4.8, 210],
    ['WRK-010', 'USR-013', 'south', 'Sensor Calibration',    2, 4.1, 45],
    ['WRK-011', 'USR-014', 'east',  'General Maintenance',   5, 4.4, 130],
    ['WRK-012', 'USR-015', 'west',  'Painting & Finishing',  3, 4.2, 88],
    ['WRK-013', 'USR-018', 'north', 'Cable & Wiring',        6, 4.6, 175],
    ['WRK-014', 'USR-019', 'south', 'Transformer Repair',    4, 4.0, 95],
    ['WRK-015', 'USR-020', 'east',  'Foundation Work',       8, 4.7, 245],
    ['WRK-016', 'USR-022', 'west',  'Solar Panel Install',   3, 4.3, 72],
    ['WRK-017', 'USR-024', 'north', 'Emergency Response',    5, 4.5, 160],
];

$workerCount = 0;
foreach ($workerInserts as $w) {
    $res = $conn->query("SELECT id FROM users WHERE user_id = '{$w[1]}'");
    if ($res && $row = $res->fetch_assoc()) {
        $userId = $row['id'];
        $stmt = $conn->prepare("INSERT INTO workers (worker_id, user_id, zone, specialization, experience_years, rating, tasks_completed, status, joined_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', CURDATE() - INTERVAL FLOOR(RAND()*1000) DAY)");
        $stmt->bind_param("sissidi", $w[0], $userId, $w[2], $w[3], $w[4], $w[5], $w[6]);
        if ($stmt->execute()) {
            $workerCount++;
        }
        $stmt->close();
    }
}
$success[] = "Workers: $workerCount new workers added";

// =====================================================
// 3. ADD MORE ALERTS (30+ new alerts)
// =====================================================
$alertSql = "INSERT INTO alerts (alert_id, pole_id, alert_type, value, threshold, severity, status, created_at) VALUES
('ALT-019', 1, 'humidity', '72%', '60%', 'medium', 'pending', '2026-02-20 08:15:00'),
('ALT-020', 3, 'vibration', '6.8 mm/s', '5.0 mm/s', 'high', 'pending', '2026-02-20 09:30:00'),
('ALT-021', 6, 'temperature', '62°C', '60°C', 'medium', 'acknowledged', '2026-02-19 14:20:00'),
('ALT-022', 8, 'voltage', '185V', '210V', 'critical', 'pending', '2026-02-20 10:45:00'),
('ALT-023', 10, 'tilt', '6.5°', '4.0°', 'high', 'in_progress', '2026-02-19 16:00:00'),
('ALT-024', 13, 'vibration', '7.3 mm/s', '5.0 mm/s', 'high', 'pending', '2026-02-20 11:00:00'),
('ALT-025', 14, 'power', 'OFF', 'ON', 'critical', 'pending', '2026-02-20 03:20:00'),
('ALT-026', 16, 'temperature', '58°C', '50°C', 'medium', 'resolved', '2026-02-18 22:10:00'),
('ALT-027', 17, 'humidity', '78%', '60%', 'high', 'pending', '2026-02-20 12:30:00'),
('ALT-028', 20, 'vibration', '5.5 mm/s', '5.0 mm/s', 'low', 'acknowledged', '2026-02-19 18:45:00'),
('ALT-029', 21, 'voltage', '192V', '210V', 'high', 'pending', '2026-02-20 07:00:00'),
('ALT-030', 23, 'structural', 'crack detected', 'none', 'critical', 'escalated', '2026-02-18 11:30:00'),
('ALT-031', 24, 'temperature', '65°C', '60°C', 'high', 'in_progress', '2026-02-19 20:15:00'),
('ALT-032', 26, 'vibration', '8.0 mm/s', '5.0 mm/s', 'critical', 'pending', '2026-02-20 06:40:00'),
('ALT-033', 28, 'tilt', '9.2°', '8.0°', 'high', 'pending', '2026-02-20 13:10:00'),
('ALT-034', 30, 'power', 'OFF', 'ON', 'critical', 'pending', '2026-02-20 02:50:00'),
('ALT-035', 31, 'voltage', '205V', '210V', 'medium', 'pending', '2026-02-19 15:30:00'),
('ALT-036', 33, 'temperature', '70°C', '60°C', 'high', 'pending', '2026-02-20 08:55:00'),
('ALT-037', 35, 'vibration', '6.2 mm/s', '5.0 mm/s', 'high', 'acknowledged', '2026-02-19 12:20:00'),
('ALT-038', 36, 'humidity', '68%', '60%', 'medium', 'pending', '2026-02-20 14:00:00'),
('ALT-039', 37, 'voltage', '188V', '210V', 'critical', 'pending', '2026-02-20 04:15:00'),
('ALT-040', 39, 'tilt', '7.1°', '4.0°', 'high', 'in_progress', '2026-02-19 09:45:00'),
('ALT-041', 41, 'vibration', '9.5 mm/s', '5.0 mm/s', 'critical', 'pending', '2026-02-20 05:30:00'),
('ALT-042', 43, 'temperature', '54°C', '50°C', 'medium', 'pending', '2026-02-20 15:20:00'),
('ALT-043', 44, 'power', 'OFF', 'ON', 'critical', 'escalated', '2026-02-18 23:00:00'),
('ALT-044', 47, 'vibration', '5.8 mm/s', '5.0 mm/s', 'medium', 'pending', '2026-02-20 16:10:00'),
('ALT-045', 48, 'voltage', '195V', '210V', 'high', 'pending', '2026-02-20 09:00:00'),
('ALT-046', 50, 'temperature', '63°C', '60°C', 'medium', 'acknowledged', '2026-02-19 21:30:00'),
('ALT-047', 2, 'structural', 'base corrosion', 'none', 'high', 'pending', '2026-02-20 10:20:00'),
('ALT-048', 7, 'vibration', '7.8 mm/s', '5.0 mm/s', 'critical', 'pending', '2026-02-20 17:00:00'),
('ALT-049', 11, 'humidity', '75%', '60%', 'high', 'pending', '2026-02-20 11:45:00'),
('ALT-050', 18, 'tilt', '10.5°', '8.0°', 'critical', 'pending', '2026-02-20 07:30:00')";

if ($conn->query($alertSql)) {
    $success[] = "Alerts: 32 new alerts added (ALT-019 to ALT-050)";
} else {
    $errors[] = "Alerts: " . $conn->error;
}

// =====================================================
// 4. ADD MORE FAULT LOGS (25 new fault logs)
// =====================================================
$faultSql = "INSERT INTO fault_logs (log_id, pole_id, fault_type, description, severity, status, reported_by, resolved_by, created_at) VALUES
('FLT-007', 1, 'electrical', 'Intermittent power fluctuation in main circuit', 'medium', 'open', 1, NULL, '2026-02-20 06:00:00'),
('FLT-008', 3, 'structural', 'Hairline crack observed at weld joint', 'high', 'open', 1, NULL, '2026-02-19 14:30:00'),
('FLT-009', 6, 'hardware', 'Temperature sensor giving erratic readings', 'medium', 'investigating', 1, NULL, '2026-02-18 10:00:00'),
('FLT-010', 8, 'electrical', 'Complete voltage drop - transformer failure', 'critical', 'open', 1, NULL, '2026-02-20 10:45:00'),
('FLT-011', 10, 'structural', 'Foundation settling causing tilt increase', 'high', 'investigating', 1, NULL, '2026-02-19 16:00:00'),
('FLT-012', 13, 'hardware', 'Vibration sensor mounting bracket loose', 'medium', 'resolved', 1, 2, '2026-02-15 08:30:00'),
('FLT-013', 14, 'electrical', 'Power supply unit burned out', 'critical', 'open', 1, NULL, '2026-02-20 03:20:00'),
('FLT-014', 17, 'environmental', 'Water ingress in sensor housing', 'high', 'open', 1, NULL, '2026-02-20 12:30:00'),
('FLT-015', 20, 'software', 'Communication module firmware crash', 'medium', 'resolved', 1, 3, '2026-02-16 09:15:00'),
('FLT-016', 21, 'electrical', 'Voltage regulator overheating in north zone', 'high', 'open', 1, NULL, '2026-02-20 07:00:00'),
('FLT-017', 23, 'structural', 'Major crack in concrete foundation', 'critical', 'open', 1, NULL, '2026-02-18 11:30:00'),
('FLT-018', 24, 'hardware', 'LED panel controller malfunction', 'medium', 'investigating', 1, NULL, '2026-02-19 20:15:00'),
('FLT-019', 26, 'structural', 'Excessive vibration causing bolt loosening', 'critical', 'open', 1, NULL, '2026-02-20 06:40:00'),
('FLT-020', 28, 'environmental', 'Lightning strike damage to antenna', 'high', 'open', 1, NULL, '2026-02-20 13:10:00'),
('FLT-021', 30, 'electrical', 'Underground cable insulation failure', 'critical', 'open', 1, NULL, '2026-02-20 02:50:00'),
('FLT-022', 33, 'hardware', 'Thermal camera lens fogged up', 'medium', 'resolved', 1, 5, '2026-02-17 11:00:00'),
('FLT-023', 35, 'software', 'GPS module returning incorrect coordinates', 'medium', 'investigating', 1, NULL, '2026-02-19 12:20:00'),
('FLT-024', 37, 'electrical', 'Short circuit in junction box', 'critical', 'open', 1, NULL, '2026-02-20 04:15:00'),
('FLT-025', 39, 'structural', 'Base plate corrosion reaching critical level', 'high', 'investigating', 1, NULL, '2026-02-19 09:45:00'),
('FLT-026', 41, 'hardware', 'Wind speed sensor jammed by debris', 'medium', 'open', 1, NULL, '2026-02-20 05:30:00'),
('FLT-027', 43, 'electrical', 'Battery backup system failure', 'high', 'open', 1, NULL, '2026-02-20 15:20:00'),
('FLT-028', 44, 'environmental', 'Bird nest causing obstruction in solar panel', 'low', 'open', 1, NULL, '2026-02-18 23:00:00'),
('FLT-029', 47, 'hardware', 'Humidity sensor calibration drift', 'medium', 'investigating', 1, NULL, '2026-02-20 16:10:00'),
('FLT-030', 48, 'electrical', 'Phase imbalance in 3-phase supply', 'high', 'open', 1, NULL, '2026-02-20 09:00:00'),
('FLT-031', 50, 'software', 'Data logger memory overflow error', 'medium', 'open', 1, NULL, '2026-02-19 21:30:00')";

if ($conn->query($faultSql)) {
    $success[] = "Fault Logs: 25 new fault logs added (FLT-007 to FLT-031)";
} else {
    $errors[] = "Fault Logs: " . $conn->error;
}

// =====================================================
// 5. ADD MORE COMPLAINTS (25 new complaints)
// =====================================================
$complaintSql = "INSERT INTO complaints (complaint_id, citizen_name, citizen_phone, citizen_email, pole_id, issue, category, status, assigned_worker_id, priority, remarks) VALUES
('CMP-020', 'Ramesh Choudhary',  '+91 99001 10001', 'ramesh.c@email.com',  1, 'Light flickering continuously at night',           'lighting',    'open',        1,    'medium',  NULL),
('CMP-021', 'Fatima Begum',      '+91 99001 10002', 'fatima.b@email.com',  3, 'Exposed wires at pole base - very dangerous',      'electrical',  'open',        NULL, 'urgent',  NULL),
('CMP-022', 'Anil Thakur',       '+91 99001 10003', 'anil.t@email.com',    6, 'Pole tilting after recent storm',                  'structural',  'assigned',    2,    'high',    'Inspection team dispatched'),
('CMP-023', 'Geeta Sharma',      '+91 99001 10004', 'geeta.s@email.com',   8, 'No light since last week - entire street dark',    'lighting',    'open',        NULL, 'urgent',  NULL),
('CMP-024', 'Syed Hussain',      '+91 99001 10005', 'syed.h@email.com',   10, 'Humming noise from transformer - very loud',       'noise',       'assigned',    4,    'high',    'Noise level check scheduled'),
('CMP-025', 'Parveen Kaur',      '+91 99001 10006', 'parveen.k@email.com',13, 'Sparks flying when it rains',                      'electrical',  'escalated',   2,    'urgent',  'Urgent safety hazard reported'),
('CMP-026', 'Dinesh Kumar',      '+91 99001 10007', 'dinesh.k@email.com', 16, 'Loose panel about to fall on road',                'safety',      'open',        NULL, 'urgent',  NULL),
('CMP-027', 'Sarita Mishra',     '+91 99001 10008', 'sarita.m@email.com', 18, 'Pole paint peeling off - looks rusty',             'structural',  'open',        NULL, 'low',     NULL),
('CMP-028', 'Rajan Pillai',      '+91 99001 10009', 'rajan.p@email.com',  20, 'Street light turns on during daytime',             'electrical',  'assigned',    1,    'medium',  'Timer module check needed'),
('CMP-029', 'Meenakshi Reddy',   '+91 99001 10010', 'meena.r@email.com',  22, 'Pole blocking pedestrian pathway after lean',      'structural',  'in_progress', 3,    'high',    'Worker on site'),
('CMP-030', 'Amit Sharma',       '+91 99001 10011', 'amit.sh@email.com',  24, 'Broken glass from light panel on ground',          'safety',      'resolved',    5,    'high',    'Cleaned up and panel replaced'),
('CMP-031', 'Lata Mangeshkar',   '+91 99001 10012', 'lata.m@email.com',   26, 'Electric shock when touching pole',                'electrical',  'escalated',   6,    'urgent',  'CRITICAL: Earthing issue'),
('CMP-032', 'Suraj Chauhan',     '+91 99001 10013', 'suraj.c@email.com',  28, 'Pole camera not working - security concern',       'other',       'open',        NULL, 'medium',  NULL),
('CMP-033', 'Nirmala Devi',      '+91 99001 10014', 'nirmala.d@email.com',30, 'Complete power outage in colony since 2 days',     'electrical',  'open',        NULL, 'urgent',  NULL),
('CMP-034', 'Pappu Yadav',       '+91 99001 10015', 'pappu.y@email.com',  32, 'Loud crackling sound at night',                    'noise',       'assigned',    4,    'high',    'Possible loose connection'),
('CMP-035', 'Kavitha Nair',      '+91 99001 10016', 'kavitha.n@email.com',34, 'Pole foundation exposed and unstable',             'structural',  'in_progress', 5,    'urgent',  'Emergency repair underway'),
('CMP-036', 'Rakesh Gupta',      '+91 99001 10017', 'rakesh.g@email.com', 36, 'Street light too dim - barely visible',            'lighting',    'open',        NULL, 'low',     NULL),
('CMP-037', 'Shabnam Parveen',   '+91 99001 10018', 'shabnam.p@email.com',38, 'Pole not working after power was cut off',         'electrical',  'open',        NULL, 'medium',  NULL),
('CMP-038', 'Harish Chandra',    '+91 99001 10019', 'harish.c@email.com', 40, 'Water accumulation at pole base during rain',      'safety',      'assigned',    6,    'medium',  'Drainage check scheduled'),
('CMP-039', 'Asha Bhosle',       '+91 99001 10020', 'asha.b@email.com',   42, 'Pole completely non-functional for weeks',         'electrical',  'escalated',   2,    'urgent',  'Repeated complaints'),
('CMP-040', 'Vijay Malhotra',    '+91 99001 10021', 'vijay.m@email.com',  44, 'Wires hanging dangerously from pole top',          'safety',      'open',        NULL, 'urgent',  NULL),
('CMP-041', 'Priyanka Das',      '+91 99001 10022', 'priyanka.d@email.com',46,'Pole near school - flickering scares children',    'lighting',    'assigned',    1,    'high',    'Priority: near school zone'),
('CMP-042', 'Rajendra Prasad',   '+91 99001 10023', 'rajendra.p@email.com',48,'Transformer leaking oil on road',                  'safety',      'escalated',   3,    'urgent',  'Environmental hazard'),
('CMP-043', 'Sunita Pandey',     '+91 99001 10024', 'sunita.p@email.com', 50, 'Pole number plate missing - hard to report issues','other',       'open',        NULL, 'low',     NULL),
('CMP-044', 'Manoj Tripathi',    '+91 99001 10025', 'manoj.t@email.com',   2, 'Birds nesting on pole causing debris to fall',     'other',       'open',        NULL, 'low',     NULL),
('CMP-045', 'Deepa Joshi',       '+91 99001 10026', 'deepa.j@email.com',   7, 'Light stays on 24/7 - waste of electricity',       'electrical',  'assigned',    3,    'medium',  'Timer replacement ordered')";

if ($conn->query($complaintSql)) {
    $success[] = "Complaints: 26 new complaints added (CMP-020 to CMP-045)";
} else {
    $errors[] = "Complaints: " . $conn->error;
}

// =====================================================
// 6. ADD MORE CUTOFF REQUESTS (18 new cutoff requests)
// =====================================================
$cutoffSql = "INSERT INTO cutoff_requests (request_id, pole_id, action, reason, requested_by, approved_by, status, created_at) VALUES
('CUT-004', 8,  'cutoff',  'Critical voltage drop - transformer failure suspected',     1, NULL, 'pending',   '2026-02-20 10:50:00'),
('CUT-005', 13, 'cutoff',  'High vibration alert - safety precaution',                  1, 1,    'approved',  '2026-02-19 14:00:00'),
('CUT-006', 14, 'cutoff',  'Power supply burned out - needs replacement',               1, 1,    'executed',  '2026-02-20 03:30:00'),
('CUT-007', 21, 'cutoff',  'Voltage regulator malfunction in north zone',               1, NULL, 'pending',   '2026-02-20 07:10:00'),
('CUT-008', 23, 'cutoff',  'Foundation crack detected - structural risk',               1, 1,    'executed',  '2026-02-18 12:00:00'),
('CUT-009', 26, 'cutoff',  'Excessive vibration - immediate power cut needed',          1, NULL, 'pending',   '2026-02-20 06:45:00'),
('CUT-010', 30, 'cutoff',  'Underground cable failure - electrocution risk',            1, 1,    'approved',  '2026-02-20 03:00:00'),
('CUT-011', 37, 'cutoff',  'Short circuit in junction box - fire risk',                 1, 1,    'executed',  '2026-02-20 04:20:00'),
('CUT-012', 41, 'cutoff',  'Wind sensor debris causing false emergency readings',       1, NULL, 'pending',   '2026-02-20 05:35:00'),
('CUT-013', 44, 'cutoff',  'Bird nest obstruction on solar panel - overheating',        1, NULL, 'rejected',  '2026-02-18 23:10:00'),
('CUT-014', 15, 'restore', 'Repair completed - transformer replaced successfully',      1, 1,    'executed',  '2026-02-20 16:00:00'),
('CUT-015', 5,  'restore', 'Foundation repair done - engineer cleared for power',       1, NULL, 'pending',   '2026-02-20 14:30:00'),
('CUT-016', 42, 'cutoff',  'Complete power failure investigation needed',               1, 1,    'approved',  '2026-02-19 10:00:00'),
('CUT-017', 46, 'cutoff',  'High vibration causing loose mounting bolts',               1, NULL, 'pending',   '2026-02-20 09:15:00'),
('CUT-018', 48, 'cutoff',  'Phase imbalance - risk of equipment damage',                1, 1,    'approved',  '2026-02-20 09:30:00'),
('CUT-019', 34, 'cutoff',  'Emergency structural fail - pole leaning dangerously',      1, 1,    'executed',  '2026-02-19 08:00:00'),
('CUT-020', 10, 'cutoff',  'Scheduled maintenance - sensor replacement',                1, NULL, 'pending',   '2026-02-21 06:00:00'),
('CUT-021', 19, 'restore', 'Tilt corrected and engineer approved power restore',        1, 1,    'executed',  '2026-02-20 18:00:00')";

if ($conn->query($cutoffSql)) {
    $success[] = "Cutoff Requests: 18 new cutoff requests added (CUT-004 to CUT-021)";
} else {
    $errors[] = "Cutoff Requests: " . $conn->error;
}

// =====================================================
// 7. ADD MORE PROOF VERIFICATION (20 new proofs)
// =====================================================
$proofSql = "INSERT INTO proof_verification (proof_id, task_id, worker_id, pole_id, proof_type, file_path, file_name, file_size, remarks, status, created_at) VALUES
('PRF-006', 1,  1, 5,  'before_repair',  '/uploads/proofs/prf006.jpg', 'pole047_foundation_before.jpg',   245000, 'Visible cracks in foundation',           'pending',   '2026-02-18 08:00:00'),
('PRF-007', 1,  1, 5,  'during_repair',  '/uploads/proofs/prf007.jpg', 'pole047_foundation_during.jpg',   312000, 'Concrete filling in progress',           'pending',   '2026-02-18 14:00:00'),
('PRF-008', 2,  1, 9,  'after_repair',   '/uploads/proofs/prf008.jpg', 'pole089_sensor_after.jpg',        198000, 'New sensor installed and tested',         'approved',  '2026-02-17 16:30:00'),
('PRF-009', 7,  1, 21, 'inspection',     '/uploads/proofs/prf009.jpg', 'pole220_inspection_feb.jpg',      267000, 'Monthly inspection - all parameters ok',  'approved',  '2026-02-15 10:00:00'),
('PRF-010', 8,  1, 22, 'before_repair',  '/uploads/proofs/prf010.jpg', 'pole221_led_before.jpg',          189000, 'Burnt LED panel visible',                'pending',   '2026-02-19 09:00:00'),
('PRF-011', 9,  5, 25, 'inspection',     '/uploads/proofs/prf011.jpg', 'pole224_vibration_inspect.jpg',   223000, 'Vibration source identified at base',    'pending',   '2026-02-19 11:30:00'),
('PRF-012', 10, 6, 27, 'before_repair',  '/uploads/proofs/prf012.jpg', 'pole227_corrosion_before.jpg',    345000, 'Severe rusting at base plate',           'approved',  '2026-02-17 08:15:00'),
('PRF-013', 10, 6, 27, 'during_repair',  '/uploads/proofs/prf013.jpg', 'pole227_corrosion_during.jpg',    298000, 'Sandblasting and priming underway',      'approved',  '2026-02-18 10:30:00'),
('PRF-014', 10, 6, 27, 'after_repair',   '/uploads/proofs/prf014.jpg', 'pole227_corrosion_after.jpg',     278000, 'Anti-corrosion coating applied',         'pending',   '2026-02-19 15:00:00'),
('PRF-015', 11, 4, 29, 'before_repair',  '/uploads/proofs/prf015.jpg', 'pole229_transformer_before.jpg',  412000, 'Overheated transformer unit',            'pending',   '2026-02-20 07:00:00'),
('PRF-016', 13, 4, 32, 'before_repair',  '/uploads/proofs/prf016.jpg', 'pole232_tilt_before.jpg',         234000, 'Tilt sensor showing incorrect angle',    'approved',  '2026-02-17 09:45:00'),
('PRF-017', 13, 4, 32, 'during_repair',  '/uploads/proofs/prf017.jpg', 'pole232_tilt_during.jpg',         256000, 'Sensor recalibration in progress',       'pending',   '2026-02-18 13:20:00'),
('PRF-018', 14, 5, 34, 'inspection',     '/uploads/proofs/prf018.jpg', 'pole234_crack_inspect.jpg',       478000, 'Critical foundation crack documented',   'pending',   '2026-02-19 08:00:00'),
('PRF-019', 16, 3, 37, 'before_repair',  '/uploads/proofs/prf019.jpg', 'pole237_panel_before.jpg',        201000, 'Damaged solar panel photo',              'pending',   '2026-02-20 10:00:00'),
('PRF-020', 17, 3, 38, 'inspection',     '/uploads/proofs/prf020.jpg', 'pole238_power_inspect.jpg',       189000, 'Offline pole inspection report',         'rejected',  '2026-02-18 14:00:00'),
('PRF-021', 20, 2, 42, 'before_repair',  '/uploads/proofs/prf021.jpg', 'pole242_power_before.jpg',        356000, 'Burned fuse box at pole base',           'pending',   '2026-02-20 06:30:00'),
('PRF-022', 22, 1, 45, 'before_repair',  '/uploads/proofs/prf022.jpg', 'pole245_sensor_mount.jpg',        167000, 'Mounting location for traffic sensor',   'approved',  '2026-02-19 11:00:00'),
('PRF-023', 25, 3, 48, 'before_repair',  '/uploads/proofs/prf023.jpg', 'pole248_cable_before.jpg',        289000, 'Damaged power cable exposed',            'pending',   '2026-02-20 08:30:00'),
('PRF-024', 26, 5, 49, 'inspection',     '/uploads/proofs/prf024.jpg', 'pole249_temp_inspect.jpg',        234000, 'Thermal image showing hot spots',        'pending',   '2026-02-19 16:00:00'),
('PRF-025', 27, 5, 50, 'inspection',     '/uploads/proofs/prf025.jpg', 'pole250_structural_assess.jpg',   445000, 'Bridge pole structural survey photo',    'pending',   '2026-02-20 09:15:00')";

if ($conn->query($proofSql)) {
    $success[] = "Proof Verification: 20 new proofs added (PRF-006 to PRF-025)";
} else {
    $errors[] = "Proof Verification: " . $conn->error;
}

// =====================================================
// 8. ADD MORE TASKS (15 new tasks)
// =====================================================
$taskSql = "INSERT INTO tasks (task_id, pole_id, worker_id, title, description, task_type, priority, status, due_date, created_at) VALUES
('TSK-071', 1,  1,  'Humidity Control Fix',         'Fix humidity control system at Salem Junction pole',          'repair',       'medium',   'pending',     '2026-03-01', '2026-02-20 08:00:00'),
('TSK-072', 8,  4,  'Emergency Voltage Fix',        'Critical voltage drop at Thanjavur Old Bus Stand - transformer', 'emergency',    'critical',  'pending',    '2026-02-21', '2026-02-20 10:45:00'),
('TSK-073', 10, 4,  'Tilt Correction Work',         'Foundation settling at Karur Bus Stand - correct tilt',    'repair',       'high',      'in_progress','2026-02-23', '2026-02-19 16:00:00'),
('TSK-074', 14, 1,  'Power Supply Replacement',     'Replace burned PSU at Tiruppur Bus Stand pole',            'repair',       'critical',  'pending',    '2026-02-22', '2026-02-20 03:20:00'),
('TSK-075', 17, 6,  'Waterproofing Sensors',        'Seal sensor housing against water ingress at Pudukkottai Town', 'maintenance',  'high',      'pending',    '2026-02-24', '2026-02-20 12:30:00'),
('TSK-076', 21, 1,  'Voltage Regulator Replace',    'Replace faulty voltage regulator at Neyveli Arch pole',    'repair',       'high',      'pending',    '2026-02-23', '2026-02-20 07:00:00'),
('TSK-077', 23, 3,  'Foundation Emergency Repair',  'Critical concrete crack repair at Kanchipuram Silk',       'emergency',    'critical',  'in_progress','2026-02-21', '2026-02-18 11:30:00'),
('TSK-078', 26, 6,  'Anti-Vibration Mount',        'Install anti-vibration mounts at Tiruvallur Town',          'installation', 'critical',  'pending',    '2026-02-22', '2026-02-20 06:40:00'),
('TSK-079', 30, 4,  'Cable Repair Underground',    'Fix underground cable insulation at Perambalur Town',       'repair',       'critical',  'pending',    '2026-02-21', '2026-02-20 02:50:00'),
('TSK-080', 37, 3,  'Junction Box Repair',         'Fix short circuit in junction box at Virudhunagar Bypass',  'emergency',    'critical',  'pending',    '2026-02-21', '2026-02-20 04:15:00'),
('TSK-081', 39, 6,  'Base Plate Treatment',        'Anti-corrosion treatment at Thoothukudi Port pole base',    'maintenance',  'high',      'in_progress','2026-02-24', '2026-02-19 09:45:00'),
('TSK-082', 44, 1,  'Solar Panel Clearing',        'Remove bird nest and clean solar panel at Udumalpet Town',  'maintenance',  'medium',    'pending',    '2026-02-25', '2026-02-18 23:00:00'),
('TSK-083', 46, 1,  'Camera Mount Installation',   'Install new CCTV mount bracket at Dharapuram Town',        'installation', 'medium',    'pending',    '2026-02-26', '2026-02-20 16:10:00'),
('TSK-084', 48, 3,  'Phase Balance Correction',    'Fix phase imbalance in 3-phase supply at Vellakoil Town',   'repair',       'high',      'pending',    '2026-02-23', '2026-02-20 09:00:00'),
('TSK-085', 34, 5,  'Emergency Pole Stabilize',    'Stabilize dangerously leaning pole at Mayiladuthurai Bus Stand', 'emergency',    'critical',  'in_progress','2026-02-21', '2026-02-19 08:00:00')";

if ($conn->query($taskSql)) {
    $success[] = "Tasks: 15 new tasks added (TSK-071 to TSK-085)";
} else {
    $errors[] = "Tasks: " . $conn->error;
}

// =====================================================
// 9b. SEED ACCELEROMETER READINGS
// =====================================================
$accelCount = 0;
$accelStmt = $conn->prepare("INSERT INTO accelerometer_readings (pole_id, accel_x, accel_y, accel_z, magnitude, source_ip, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Generate readings for multiple poles over the last 24 hours
$accelPoles = [1, 2, 3, 5, 8, 10, 14, 17, 21, 23, 26, 30, 34, 37, 39, 44];
$baseTime = strtotime('2026-03-12 00:00:00');

foreach ($accelPoles as $pId) {
    // Each pole gets 15-25 readings spread over 24 hours
    $numReadings = rand(15, 25);
    for ($i = 0; $i < $numReadings; $i++) {
        $timeOffset = rand(0, 86400); // random second within 24h
        $recordedAt = date('Y-m-d H:i:s', $baseTime + $timeOffset);

        // Realistic accelerometer values: mostly near 0 for X/Y, ~9.8 for Z (gravity)
        // Add some vibration noise
        $vibrationLevel = rand(0, 100) / 100; // 0 to 1.0
        $ax = round((rand(-50, 50) / 100) * (1 + $vibrationLevel), 4);
        $ay = round((rand(-50, 50) / 100) * (1 + $vibrationLevel), 4);
        $az = round(9.8 + (rand(-30, 30) / 100) * (1 + $vibrationLevel), 4);

        // Some poles have higher vibration (simulating traffic, wind, etc.)
        if (in_array($pId, [8, 26, 37, 44])) {
            $ax = round($ax * 2.5, 4);
            $ay = round($ay * 2.5, 4);
            $az = round(9.8 + (rand(-80, 80) / 100), 4);
        }

        // Occasional spike readings (simulating impacts or strong wind)
        if (rand(1, 20) === 1) {
            $ax = round((rand(-200, 200) / 100), 4);
            $ay = round((rand(-200, 200) / 100), 4);
            $az = round(9.8 + (rand(-150, 150) / 100), 4);
        }

        $mag = round(sqrt($ax * $ax + $ay * $ay + $az * $az), 4);
        $srcIp = '192.168.1.' . rand(100, 200);

        $accelStmt->bind_param("iddddss", $pId, $ax, $ay, $az, $mag, $srcIp, $recordedAt);
        if ($accelStmt->execute()) {
            $accelCount++;
        }
    }
}
$accelStmt->close();
$success[] = "Accelerometer: $accelCount readings seeded across " . count($accelPoles) . " poles";

// =====================================================
// 9. ASSIGN POLES, TASKS & COMPLAINTS TO WORKERS 7-17
//    (These workers had no data assigned previously)
// =====================================================

// Get all worker internal IDs for WRK-007 to WRK-017
$newWorkerIds = [];
$wRes = $conn->query("SELECT w.id, w.worker_id, w.zone FROM workers w WHERE w.worker_id IN ('WRK-007','WRK-008','WRK-009','WRK-010','WRK-011','WRK-012','WRK-013','WRK-014','WRK-015','WRK-016','WRK-017')");
if ($wRes) {
    while ($wr = $wRes->fetch_assoc()) {
        $newWorkerIds[] = $wr;
    }
}

$assignCount = 0;
foreach ($newWorkerIds as $nw) {
    $wid = $nw['id'];
    $wzone = $nw['zone'];

    // Assign 2-4 poles from their zone
    $conn->query("UPDATE poles SET assigned_worker_id = $wid
        WHERE zone = '$wzone' AND assigned_worker_id IS NULL
        ORDER BY RAND() LIMIT 3");
    $assigned = $conn->affected_rows;
    if ($assigned < 2) {
        $need = 3 - $assigned;
        $conn->query("UPDATE poles SET assigned_worker_id = $wid
            WHERE zone = '$wzone' AND assigned_worker_id != $wid
            ORDER BY RAND() LIMIT $need");
    }

    // Create tasks for their poles
    $pRes = $conn->query("SELECT id, pole_id, location FROM poles WHERE assigned_worker_id = $wid");
    $taskDefs = [
        ['Routine Inspection', 'Monthly inspection and system verification', 'inspection', 'medium'],
        ['Sensor Calibration', 'Calibrate all sensors on pole', 'maintenance', 'high'],
        ['Wiring Check', 'Inspect wiring and cable integrity', 'maintenance', 'medium'],
        ['LED Panel Maintenance', 'Check LED panel functionality', 'inspection', 'low'],
        ['Voltage Monitoring', 'Monitor voltage levels and power supply', 'inspection', 'high'],
        ['Structural Assessment', 'Check for cracks, corrosion, tilt', 'inspection', 'medium'],
    ];
    $ti = 0;
    while ($pRes && $p = $pRes->fetch_assoc()) {
        $numTasks = rand(2, 3);
        for ($j = 0; $j < $numTasks; $j++) {
            $td = $taskDefs[$ti % count($taskDefs)];
            $tskId = 'TSK-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            $tCheck = $conn->query("SELECT id FROM tasks WHERE task_id = '$tskId'");
            while ($tCheck && $tCheck->num_rows > 0) {
                $tskId = 'TSK-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                $tCheck = $conn->query("SELECT id FROM tasks WHERE task_id = '$tskId'");
            }
            $tTitle = $conn->real_escape_string($td[0] . ' - ' . $p['location']);
            $tDesc = $conn->real_escape_string($td[1]);
            $statusArr = ['pending', 'pending', 'pending', 'in_progress'];
            $tStatus = $statusArr[array_rand($statusArr)];
            $dueDate = date('Y-m-d', strtotime('+' . rand(1, 21) . ' days'));
            $conn->query("INSERT INTO tasks (task_id, pole_id, worker_id, title, description, task_type, priority, status, due_date)
                VALUES ('$tskId', {$p['id']}, $wid, '$tTitle', '$tDesc', '{$td[2]}', '{$td[3]}', '$tStatus', '$dueDate')");
            $ti++;
        }
    }

    // Assign unassigned complaints
    $conn->query("UPDATE complaints SET assigned_worker_id = $wid, status = IF(status = 'open', 'assigned', status)
        WHERE assigned_worker_id IS NULL
        ORDER BY RAND() LIMIT 2");
    $compAssigned = $conn->affected_rows;

    // Create complaints if needed
    if ($compAssigned < 2) {
        $need = 2 - $compAssigned;
        $cpRes = $conn->query("SELECT id, pole_id FROM poles WHERE assigned_worker_id = $wid ORDER BY RAND() LIMIT $need");
        $issues = [
            ['Street light not turning on at dusk', 'lighting', 'medium'],
            ['Transformer making loud buzzing noise', 'noise', 'high'],
            ['Rust visible on pole body', 'structural', 'low'],
            ['Wires hanging loose from pole', 'electrical', 'urgent'],
            ['Light too dim to illuminate road', 'lighting', 'medium'],
        ];
        $names = ['Arjun Patel', 'Kavita Devi', 'Sunil Rao', 'Madhu Bala', 'Ramesh Jha'];
        $ci = 0;
        while ($cpRes && $cp = $cpRes->fetch_assoc()) {
            $cmpId = 'CMP-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            $cCheck = $conn->query("SELECT id FROM complaints WHERE complaint_id = '$cmpId'");
            while ($cCheck && $cCheck->num_rows > 0) {
                $cmpId = 'CMP-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                $cCheck = $conn->query("SELECT id FROM complaints WHERE complaint_id = '$cmpId'");
            }
            $iss = $issues[$ci % count($issues)];
            $cName = $conn->real_escape_string($names[$ci % count($names)]);
            $cIssue = $conn->real_escape_string($iss[0]);
            $conn->query("INSERT INTO complaints (complaint_id, citizen_name, citizen_phone, citizen_email, pole_id, issue, category, status, assigned_worker_id, priority)
                VALUES ('$cmpId', '$cName', '+91 98765 " . rand(10000, 99999) . "', '" . strtolower(str_replace(' ', '.', $names[$ci % count($names)])) . "@email.com', {$cp['id']}, '$cIssue', '{$iss[1]}', 'assigned', $wid, '{$iss[2]}')");
            $ci++;
        }
    }
    $assignCount++;
}
$success[] = "Worker Assignments: $assignCount workers (WRK-007 to WRK-017) now have poles, tasks, and complaints";

// =====================================================
// FINAL COUNTS
// =====================================================
$tables = ['users', 'workers', 'poles', 'alerts', 'fault_logs', 'risk_levels', 'complaints', 'cutoff_requests', 'proof_verification', 'tasks', 'accelerometer_readings'];
$counts = [];
foreach ($tables as $t) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM $t");
    $counts[$t] = $r->fetch_assoc()['cnt'];
}

$conn->close();

// OUTPUT
echo "<html><head><style>
body{font-family:'Segoe UI',sans-serif;background:#0a0a1a;color:#fff;padding:40px;max-width:800px;margin:0 auto;}
h1{color:#6c5ce7;text-align:center;margin-bottom:30px;}
.box{background:#12122a;border:1px solid rgba(108,92,231,0.3);border-radius:14px;padding:20px;margin-bottom:20px;}
.success{color:#00b894;margin:5px 0;} .error{color:#d63031;margin:5px 0;}
.count-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:10px;}
.count-item{background:#1a1a3e;padding:15px;border-radius:10px;text-align:center;}
.count-item .num{font-size:28px;font-weight:800;color:#6c5ce7;}
.count-item .lbl{font-size:12px;color:#636e72;text-transform:uppercase;margin-top:4px;}
a{color:#6c5ce7;text-decoration:none;font-size:18px;display:block;text-align:center;margin-top:25px;}
</style></head><body>";

echo "<h1>&#9889; Data Seeding Complete!</h1>";

echo "<div class='box'><h3>Results:</h3>";
foreach ($success as $s) echo "<p class='success'>&#10004; $s</p>";
foreach ($errors as $e) echo "<p class='error'>&#10008; $e</p>";
echo "</div>";

echo "<div class='box'><h3>Final Record Counts:</h3><div class='count-grid'>";
foreach ($counts as $t => $c) {
    $label = str_replace('_', ' ', ucfirst($t));
    echo "<div class='count-item'><div class='num'>$c</div><div class='lbl'>$label</div></div>";
}
echo "</div></div>";

echo "<a href='admin_login.php'>&#8594; Go to Admin Login</a>";
echo "</body></html>";
?>
