<?php
/**
 * Smart Pole Management System - Single API Backend
 * All API endpoints handled here.
 * URL: http://localhost/inba%20project/api.php?action=...
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// ==================== DATABASE CONNECTION ====================
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'smart_pole_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

// ==================== HELPERS ====================
function jsonResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

function getInput() {
    $json = json_decode(file_get_contents('php://input'), true);
    return $json ?: $_POST;
}

function sanitize($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

function requireAuth() {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(false, null, 'Unauthorized. Please login.', 401);
    }
}

function generateId($prefix) {
    return $prefix . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
}

function getClientIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

// Log a security event
function logSecurity($conn, $userId, $eventType, $description, $severity = 'info', $metadata = null) {
    $ip = getClientIp();
    $ua = getUserAgent();
    $meta = $metadata ? json_encode($metadata) : null;
    $stmt = $conn->prepare("INSERT INTO security_logs (user_id, event_type, description, ip_address, user_agent, metadata, severity) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $userId, $eventType, $description, $ip, $ua, $meta, $severity);
    $stmt->execute();
    $stmt->close();
}

// Record login attempt
function recordLoginAttempt($conn, $email, $success, $failureReason = null) {
    $ip = getClientIp();
    $ua = getUserAgent();
    $s = $success ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, user_agent, success, failure_reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $email, $ip, $ua, $s, $failureReason);
    $stmt->execute();
    $stmt->close();
}

// Check if account is locked (5+ failed attempts in last 15 minutes)
function isAccountLocked($conn, $email) {
    $ip = getClientIp();
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM login_attempts WHERE (email = ? OR ip_address = ?) AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['cnt'] >= 5;
}

// Check if IP is blacklisted
function isIpBlacklisted($conn) {
    $ip = getClientIp();
    $stmt = $conn->prepare("SELECT id FROM ip_blacklist WHERE ip_address = ? AND (is_permanent = 1 OR expires_at > NOW())");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $result;
}

// Save active session to database
function saveSession($conn, $userId) {
    $sessionId = session_id();
    $ip = getClientIp();
    $ua = getUserAgent();
    // Deactivate any old sessions for this user
    $conn->query("UPDATE user_sessions SET is_active = 0, logout_at = NOW() WHERE user_id = $userId AND is_active = 1");
    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $sessionId, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}

// Save password to history
function savePasswordHistory($conn, $userId, $hash) {
    $stmt = $conn->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $hash);
    $stmt->execute();
    $stmt->close();
}

// Check IP blacklist on every request
if (isIpBlacklisted($conn)) {
    jsonResponse(false, null, 'Access denied. Your IP has been blocked.', 403);
}

// ==================== ROUTING ====================
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ==================== AUTH ====================
    case 'login':
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $email = sanitize($conn, $input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            jsonResponse(false, null, 'Email and password are required', 400);
        }

        // Brute-force protection: check if account is locked
        if (isAccountLocked($conn, $email)) {
            logSecurity($conn, null, 'failed_login', "Login blocked - account locked for: $email", 'warning', ['email' => $email]);
            jsonResponse(false, null, 'Account temporarily locked due to too many failed attempts. Try again in 15 minutes.', 429);
        }

        $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user || !password_verify($password, $user['password'])) {
            // Record failed attempt
            $reason = !$user ? 'Email not found' : 'Wrong password';
            recordLoginAttempt($conn, $email, false, $reason);
            logSecurity($conn, $user['id'] ?? null, 'failed_login', "Failed login attempt for: $email ($reason)", 'warning', ['email' => $email]);
            jsonResponse(false, null, 'Invalid email or password', 401);
        }

        // Check if user is suspended
        if ($user['status'] === 'suspended') {
            recordLoginAttempt($conn, $email, false, 'Account suspended');
            logSecurity($conn, $user['id'], 'failed_login', "Suspended account login attempt: $email", 'warning');
            jsonResponse(false, null, 'Your account has been suspended. Contact administrator.', 403);
        }

        // Successful login - record it
        recordLoginAttempt($conn, $email, true);

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Update last login
        $conn->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_uid'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['role_id'] = $user['role_id'];

        // Get worker info if worker
        $workerData = null;
        if ($user['role_name'] === 'worker') {
            $wr = $conn->query("SELECT * FROM workers WHERE user_id = {$user['id']}");
            $workerData = $wr->fetch_assoc();
            if ($workerData) {
                $_SESSION['worker_id'] = $workerData['id'];
                $_SESSION['worker_uid'] = $workerData['worker_id'];
            }
        }

        unset($user['password'], $user['reset_token'], $user['reset_token_expiry']);

        // Save session & log security event
        saveSession($conn, $user['id']);
        logSecurity($conn, $user['id'], 'login', 'User logged in successfully', 'info');

        jsonResponse(true, [
            'user' => $user,
            'role' => $user['role_name'],
            'worker' => $workerData
        ], 'Login successful');
        break;

    case 'logout':
        $logoutUserId = $_SESSION['user_id'] ?? null;
        $sid = session_id();
        // Deactivate session in database
        if ($logoutUserId) {
            $conn->query("UPDATE user_sessions SET is_active = 0, logout_at = NOW() WHERE user_id = $logoutUserId AND session_id = '" . $conn->real_escape_string($sid) . "'");
            logSecurity($conn, $logoutUserId, 'logout', 'User logged out', 'info');
        }
        session_destroy();
        jsonResponse(true, null, 'Logged out successfully');
        break;

    case 'session':
        if (!empty($_SESSION['user_id'])) {
            jsonResponse(true, [
                'user_id' => $_SESSION['user_id'],
                'name' => $_SESSION['name'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role'],
                'worker_id' => $_SESSION['worker_id'] ?? null,
                'worker_uid' => $_SESSION['worker_uid'] ?? null
            ]);
        } else {
            jsonResponse(false, null, 'No active session', 401);
        }
        break;

    // ==================== DASHBOARD ====================
    case 'dashboard':
        requireAuth();
        $stats = [];

        // Total poles
        $r = $conn->query("SELECT COUNT(*) as cnt FROM poles");
        $stats['total_poles'] = $r->fetch_assoc()['cnt'];

        // Active poles
        $r = $conn->query("SELECT COUNT(*) as cnt FROM poles WHERE status = 'active'");
        $stats['active_poles'] = $r->fetch_assoc()['cnt'];

        // Critical alerts
        $r = $conn->query("SELECT COUNT(*) as cnt FROM alerts WHERE severity = 'critical' AND status != 'resolved'");
        $stats['critical_alerts'] = $r->fetch_assoc()['cnt'];

        // Active workers
        $r = $conn->query("SELECT COUNT(*) as cnt FROM workers WHERE status = 'active'");
        $stats['active_workers'] = $r->fetch_assoc()['cnt'];

        // Pending tasks
        $r = $conn->query("SELECT COUNT(*) as cnt FROM tasks WHERE status IN ('pending', 'assigned')");
        $stats['pending_tasks'] = $r->fetch_assoc()['cnt'];

        // Open complaints
        $r = $conn->query("SELECT COUNT(*) as cnt FROM complaints WHERE status IN ('open', 'assigned', 'in_progress')");
        $stats['open_complaints'] = $r->fetch_assoc()['cnt'];

        // Risk distribution
        $r = $conn->query("SELECT risk_level, COUNT(*) as cnt FROM risk_levels GROUP BY risk_level");
        $risk = ['normal' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        while ($row = $r->fetch_assoc()) {
            $risk[$row['risk_level']] = (int)$row['cnt'];
        }
        $stats['risk_distribution'] = $risk;

        // Recent alerts
        $r = $conn->query("SELECT a.*, p.pole_id as pole_code, p.location FROM alerts a 
            JOIN poles p ON a.pole_id = p.id ORDER BY a.created_at DESC LIMIT 5");
        $recent = [];
        while ($row = $r->fetch_assoc()) $recent[] = $row;
        $stats['recent_alerts'] = $recent;

        jsonResponse(true, $stats);
        break;

    // ==================== POLES ====================
    case 'poles':
        requireAuth();
        if ($method === 'GET') {
            $search = sanitize($conn, $_GET['search'] ?? '');
            $zone = sanitize($conn, $_GET['zone'] ?? '');
            $risk = sanitize($conn, $_GET['risk'] ?? '');

            $sql = "SELECT p.*, 
                    hd.vibration, hd.temperature, hd.voltage, hd.humidity, hd.tilt_angle,
                    rl.risk_score, rl.risk_level, rl.action_required,
                    w.worker_id as worker_code, u.name as worker_name
                    FROM poles p
                    LEFT JOIN hardware_data hd ON p.id = hd.pole_id
                    LEFT JOIN risk_levels rl ON p.id = rl.pole_id
                    LEFT JOIN workers w ON p.assigned_worker_id = w.id
                    LEFT JOIN users u ON w.user_id = u.id
                    WHERE 1=1";

            if ($search) $sql .= " AND (p.pole_id LIKE '%$search%' OR p.location LIKE '%$search%')";
            if ($zone) $sql .= " AND p.zone = '$zone'";
            if ($risk) $sql .= " AND rl.risk_level = '$risk'";
            $sql .= " ORDER BY p.pole_id ASC";

            $r = $conn->query($sql);
            $poles = [];
            while ($row = $r->fetch_assoc()) $poles[] = $row;

            jsonResponse(true, $poles);
        } elseif ($method === 'POST') {
            $input = getInput();
            $poleId = sanitize($conn, $input['pole_id'] ?? generateId('POLE'));
            $location = sanitize($conn, $input['location'] ?? '');
            $zone = sanitize($conn, $input['zone'] ?? 'north');
            $lat = floatval($input['latitude'] ?? 0);
            $lng = floatval($input['longitude'] ?? 0);
            $workerId = intval($input['assigned_worker_id'] ?? 0) ?: null;

            $stmt = $conn->prepare("INSERT INTO poles (pole_id, location, zone, latitude, longitude, installation_date, assigned_worker_id) VALUES (?, ?, ?, ?, ?, CURDATE(), ?)");
            $stmt->bind_param("sssddi", $poleId, $location, $zone, $lat, $lng, $workerId);

            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                // Create default hardware data
                $conn->query("INSERT INTO hardware_data (pole_id, vibration, temperature, voltage) VALUES ($newId, 0, 30, 230)");
                // Create default risk level
                $conn->query("INSERT INTO risk_levels (pole_id, risk_score, risk_level, action_required) VALUES ($newId, 0, 'normal', 'Routine Check')");
                jsonResponse(true, ['id' => $newId, 'pole_id' => $poleId], 'Pole added successfully');
            } else {
                jsonResponse(false, null, 'Failed to add pole: ' . $conn->error, 500);
            }
        }
        break;

    case 'pole':
        requireAuth();
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'Pole ID required', 400);

        if ($method === 'GET') {
            $r = $conn->query("SELECT p.*, 
                hd.vibration, hd.temperature, hd.voltage, hd.current_amp, hd.humidity, hd.tilt_angle,
                rl.risk_score, rl.risk_level, rl.action_required,
                w.worker_id as worker_code, u.name as worker_name
                FROM poles p
                LEFT JOIN hardware_data hd ON p.id = hd.pole_id
                LEFT JOIN risk_levels rl ON p.id = rl.pole_id
                LEFT JOIN workers w ON p.assigned_worker_id = w.id
                LEFT JOIN users u ON w.user_id = u.id
                WHERE p.id = $id");
            $pole = $r->fetch_assoc();
            if ($pole) jsonResponse(true, $pole);
            else jsonResponse(false, null, 'Pole not found', 404);
        } elseif ($method === 'PUT') {
            $input = getInput();
            $sets = [];
            foreach (['location', 'zone', 'status', 'pole_type', 'material'] as $field) {
                if (isset($input[$field])) $sets[] = "$field = '" . sanitize($conn, $input[$field]) . "'";
            }
            if (array_key_exists('assigned_worker_id', $input)) {
                $wid = $input['assigned_worker_id'];
                $sets[] = "assigned_worker_id = " . ($wid ? intval($wid) : "NULL");
            }
            if (isset($input['power_status'])) $sets[] = "power_status = " . ($input['power_status'] ? 1 : 0);

            if (!empty($sets)) {
                $conn->query("UPDATE poles SET " . implode(', ', $sets) . " WHERE id = $id");
                jsonResponse(true, null, 'Pole updated');
            }
            jsonResponse(false, null, 'Nothing to update', 400);
        } elseif ($method === 'DELETE') {
            $conn->query("DELETE FROM poles WHERE id = $id");
            jsonResponse(true, null, 'Pole deleted');
        }
        break;

    // ==================== ALERTS ====================
    case 'alerts':
        requireAuth();
        if ($method === 'GET') {
            $severity = sanitize($conn, $_GET['severity'] ?? '');
            $status = sanitize($conn, $_GET['status'] ?? '');
            $type = sanitize($conn, $_GET['type'] ?? '');

            $sql = "SELECT a.*, p.pole_id as pole_code, p.location 
                    FROM alerts a JOIN poles p ON a.pole_id = p.id WHERE 1=1";
            if ($severity) $sql .= " AND a.severity = '$severity'";
            if ($status) $sql .= " AND a.status = '$status'";
            if ($type) $sql .= " AND a.alert_type = '$type'";
            $sql .= " ORDER BY p.pole_id ASC, a.created_at DESC";

            $r = $conn->query($sql);
            $alerts = [];
            while ($row = $r->fetch_assoc()) $alerts[] = $row;
            jsonResponse(true, $alerts);
        }
        break;

    case 'alert_resolve':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'Alert ID required', 400);

        $userId = $_SESSION['user_id'];
        $conn->query("UPDATE alerts SET status = 'resolved', resolved_by = $userId, resolved_at = NOW() WHERE id = $id");
        jsonResponse(true, null, 'Alert resolved');
        break;

    // ==================== FAULT LOGS ====================
    case 'fault_logs':
        requireAuth();
        if ($method === 'GET') {
            $sql = "SELECT fl.*, p.pole_id as pole_code, p.location,
                    u1.name as reported_by_name, u2.name as resolved_by_name
                    FROM fault_logs fl
                    JOIN poles p ON fl.pole_id = p.id
                    LEFT JOIN users u1 ON fl.reported_by = u1.id
                    LEFT JOIN users u2 ON fl.resolved_by = u2.id
                    ORDER BY p.pole_id ASC, fl.created_at DESC";
            $r = $conn->query($sql);
            $logs = [];
            while ($row = $r->fetch_assoc()) $logs[] = $row;
            jsonResponse(true, $logs);
        } elseif ($method === 'POST') {
            $input = getInput();
            $logId = generateId('FLT');
            $poleId = intval($input['pole_id'] ?? 0);
            $faultType = sanitize($conn, $input['fault_type'] ?? 'hardware');
            $desc = sanitize($conn, $input['description'] ?? '');
            $severity = sanitize($conn, $input['severity'] ?? 'medium');
            $reportedBy = $_SESSION['user_id'];

            $stmt = $conn->prepare("INSERT INTO fault_logs (log_id, pole_id, fault_type, description, severity, reported_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssi", $logId, $poleId, $faultType, $desc, $severity, $reportedBy);
            if ($stmt->execute()) {
                jsonResponse(true, ['id' => $conn->insert_id, 'log_id' => $logId], 'Fault log created');
            } else {
                jsonResponse(false, null, 'Failed to create fault log', 500);
            }
        }
        break;

    case 'fault_log_resolve':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        $notes = sanitize($conn, $input['resolution_notes'] ?? '');
        $userId = $_SESSION['user_id'];
        $conn->query("UPDATE fault_logs SET status = 'resolved', resolved_by = $userId, resolved_at = NOW(), resolution_notes = '$notes' WHERE id = $id");
        jsonResponse(true, null, 'Fault log resolved');
        break;

    // ==================== WORKERS ====================
    case 'workers':
        requireAuth();
        if ($method === 'GET') {
            $sql = "SELECT w.*, u.name, u.email, u.phone, u.status as user_status,
                    (SELECT COUNT(*) FROM poles WHERE assigned_worker_id = w.id) as assigned_poles
                    FROM workers w JOIN users u ON w.user_id = u.id
                    ORDER BY w.worker_id ASC";
            $r = $conn->query($sql);
            $workers = [];
            while ($row = $r->fetch_assoc()) $workers[] = $row;
            jsonResponse(true, $workers);
        } elseif ($method === 'POST') {
            $input = getInput();
            $name = sanitize($conn, $input['name'] ?? '');
            $email = sanitize($conn, $input['email'] ?? '');
            $phone = sanitize($conn, $input['phone'] ?? '');
            $zone = sanitize($conn, $input['zone'] ?? 'north');
            $spec = sanitize($conn, $input['specialization'] ?? '');
            $password = password_hash($input['password'] ?? 'worker123', PASSWORD_DEFAULT);

            $userId = 'USR-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            $workerId = 'WRK-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, phone, role_id) VALUES (?, ?, ?, ?, ?, 2)");
                $stmt->bind_param("sssss", $userId, $name, $email, $password, $phone);
                $stmt->execute();
                $newUserId = $conn->insert_id;

                $stmt2 = $conn->prepare("INSERT INTO workers (worker_id, user_id, zone, specialization, joined_date) VALUES (?, ?, ?, ?, CURDATE())");
                $stmt2->bind_param("siss", $workerId, $newUserId, $zone, $spec);
                $stmt2->execute();

                $conn->commit();
                jsonResponse(true, ['worker_id' => $workerId], 'Worker added successfully');
            } catch (Exception $e) {
                $conn->rollback();
                jsonResponse(false, null, 'Failed to add worker: ' . $e->getMessage(), 500);
            }
        }
        break;

    // ==================== WORKER DELETE ====================
    case 'worker_delete':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'Invalid worker ID', 400);

        // Get the user_id linked to this worker
        $wr = $conn->query("SELECT user_id FROM workers WHERE id = $id");
        $workerRow = $wr->fetch_assoc();
        if (!$workerRow) jsonResponse(false, null, 'Worker not found', 404);
        $userId = intval($workerRow['user_id']);

        $conn->begin_transaction();
        try {
            // Unassign poles assigned to this worker
            $conn->query("UPDATE poles SET assigned_worker_id = NULL WHERE assigned_worker_id = $id");
            // Delete tasks assigned to this worker
            $conn->query("DELETE FROM tasks WHERE worker_id = $id");
            // Delete the worker record
            $conn->query("DELETE FROM workers WHERE id = $id");
            // Delete the user record
            $conn->query("DELETE FROM users WHERE id = $userId");
            $conn->commit();
            jsonResponse(true, null, 'Worker removed successfully');
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse(false, null, 'Failed to remove worker: ' . $e->getMessage(), 500);
        }
        break;

    // ==================== USERS ====================
    case 'users':
        requireAuth();
        if ($method === 'GET') {
            $sql = "SELECT u.id, u.user_id, u.name, u.email, u.phone, u.status, u.last_login, u.created_at, r.role_name
                    FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.user_id ASC";
            $r = $conn->query($sql);
            $users = [];
            while ($row = $r->fetch_assoc()) $users[] = $row;
            jsonResponse(true, $users);
        } elseif ($method === 'POST') {
            $input = getInput();
            $uid = 'USR-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            $name = sanitize($conn, $input['name'] ?? '');
            $email = sanitize($conn, $input['email'] ?? '');
            $phone = sanitize($conn, $input['phone'] ?? '');
            $roleId = intval($input['role_id'] ?? 3);
            $password = password_hash($input['password'] ?? 'password123', PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, phone, role_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $uid, $name, $email, $password, $phone, $roleId);
            if ($stmt->execute()) {
                jsonResponse(true, ['id' => $conn->insert_id, 'user_id' => $uid], 'User created');
            } else {
                jsonResponse(false, null, 'Failed: ' . $conn->error, 500);
            }
        }
        break;

    case 'user_update':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        $sets = [];
        foreach (['name', 'email', 'phone', 'status'] as $field) {
            if (isset($input[$field])) $sets[] = "$field = '" . sanitize($conn, $input[$field]) . "'";
        }
        if (isset($input['role_id'])) $sets[] = "role_id = " . intval($input['role_id']);
        if (!empty($sets)) {
            $conn->query("UPDATE users SET " . implode(', ', $sets) . " WHERE id = $id");
        }
        jsonResponse(true, null, 'User updated');
        break;

    case 'user_delete':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        $conn->query("DELETE FROM users WHERE id = $id");
        jsonResponse(true, null, 'User deleted');
        break;

    // ==================== TASKS ====================
    case 'tasks':
        requireAuth();
        if ($method === 'GET') {
            $workerId = intval($_GET['worker_id'] ?? 0);
            $status = sanitize($conn, $_GET['status'] ?? '');

            $sql = "SELECT t.*, p.pole_id as pole_code, p.location, 
                    w.worker_id as worker_code, u.name as worker_name
                    FROM tasks t
                    JOIN poles p ON t.pole_id = p.id
                    LEFT JOIN workers w ON t.worker_id = w.id
                    LEFT JOIN users u ON w.user_id = u.id
                    WHERE 1=1";
            if ($workerId) $sql .= " AND t.worker_id = $workerId";
            if ($status) $sql .= " AND t.status = '$status'";
            $sql .= " ORDER BY p.pole_id ASC, t.created_at DESC";

            $r = $conn->query($sql);
            $tasks = [];
            while ($row = $r->fetch_assoc()) $tasks[] = $row;
            jsonResponse(true, $tasks);
        } elseif ($method === 'POST') {
            $input = getInput();
            $taskId = generateId('TSK');
            $poleId = intval($input['pole_id'] ?? 0);
            $workerId = intval($input['worker_id'] ?? 0) ?: null;
            $title = sanitize($conn, $input['title'] ?? '');
            $desc = sanitize($conn, $input['description'] ?? '');
            $type = sanitize($conn, $input['task_type'] ?? 'maintenance');
            $priority = sanitize($conn, $input['priority'] ?? 'medium');
            $dueDate = sanitize($conn, $input['due_date'] ?? date('Y-m-d'));

            $stmt = $conn->prepare("INSERT INTO tasks (task_id, pole_id, worker_id, title, description, task_type, priority, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisssss", $taskId, $poleId, $workerId, $title, $desc, $type, $priority, $dueDate);
            if ($stmt->execute()) {
                jsonResponse(true, ['id' => $conn->insert_id, 'task_id' => $taskId], 'Task created');
            } else {
                jsonResponse(false, null, 'Failed: ' . $conn->error, 500);
            }
        }
        break;

    case 'task_update':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        $status = sanitize($conn, $input['status'] ?? '');

        if ($status === 'in_progress') {
            $conn->query("UPDATE tasks SET status = 'in_progress', started_at = NOW() WHERE id = $id");
        } elseif ($status === 'completed') {
            $conn->query("UPDATE tasks SET status = 'completed', completed_at = NOW() WHERE id = $id");
            // Increment worker tasks_completed
            $r = $conn->query("SELECT worker_id FROM tasks WHERE id = $id");
            $task = $r->fetch_assoc();
            if ($task['worker_id']) {
                $conn->query("UPDATE workers SET tasks_completed = tasks_completed + 1 WHERE id = " . $task['worker_id']);
            }
        } elseif ($status === 'pending') {
            // Revert task to Not Done / Pending
            // Check if it was completed before, if so decrement counter
            $r = $conn->query("SELECT worker_id, status as old_status FROM tasks WHERE id = $id");
            $task = $r->fetch_assoc();
            if ($task && $task['old_status'] === 'completed' && $task['worker_id']) {
                $conn->query("UPDATE workers SET tasks_completed = GREATEST(tasks_completed - 1, 0) WHERE id = " . $task['worker_id']);
            }
            $conn->query("UPDATE tasks SET status = 'pending', completed_at = NULL WHERE id = $id");
        } else {
            $sets = [];
            foreach (['status', 'notes'] as $f) {
                if (isset($input[$f])) $sets[] = "$f = '" . sanitize($conn, $input[$f]) . "'";
            }
            if (isset($input['worker_id'])) $sets[] = "worker_id = " . intval($input['worker_id']);
            if (!empty($sets)) $conn->query("UPDATE tasks SET " . implode(', ', $sets) . " WHERE id = $id");
        }
        jsonResponse(true, null, 'Task updated');
        break;

    // ==================== COMPLAINTS ====================
    case 'complaints':
        requireAuth();
        if ($method === 'GET') {
            $workerId = intval($_GET['worker_id'] ?? 0);
            $status = sanitize($conn, $_GET['status'] ?? '');

            $sql = "SELECT c.*, p.pole_id as pole_code, p.location,
                    w.worker_id as worker_code, u.name as worker_name
                    FROM complaints c
                    LEFT JOIN poles p ON c.pole_id = p.id
                    LEFT JOIN workers w ON c.assigned_worker_id = w.id
                    LEFT JOIN users u ON w.user_id = u.id
                    WHERE 1=1";
            if ($workerId) $sql .= " AND c.assigned_worker_id = $workerId";
            if ($status) $sql .= " AND c.status = '$status'";
            $sql .= " ORDER BY p.pole_id ASC, c.created_at DESC";

            $r = $conn->query($sql);
            $complaints = [];
            while ($row = $r->fetch_assoc()) $complaints[] = $row;
            jsonResponse(true, $complaints);
        } elseif ($method === 'POST') {
            $input = getInput();
            $cid = generateId('CMP');
            $citizenName = sanitize($conn, $input['citizen_name'] ?? '');
            $citizenPhone = sanitize($conn, $input['citizen_phone'] ?? '');
            $citizenEmail = sanitize($conn, $input['citizen_email'] ?? '');
            $poleId = intval($input['pole_id'] ?? 0) ?: null;
            $issue = sanitize($conn, $input['issue'] ?? '');
            $category = sanitize($conn, $input['category'] ?? 'other');
            $priority = sanitize($conn, $input['priority'] ?? 'medium');

            $stmt = $conn->prepare("INSERT INTO complaints (complaint_id, citizen_name, citizen_phone, citizen_email, pole_id, issue, category, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssisss", $cid, $citizenName, $citizenPhone, $citizenEmail, $poleId, $issue, $category, $priority);
            if ($stmt->execute()) {
                jsonResponse(true, ['id' => $conn->insert_id, 'complaint_id' => $cid], 'Complaint registered');
            } else {
                jsonResponse(false, null, 'Failed: ' . $conn->error, 500);
            }
        }
        break;

    case 'complaint_update':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        $sets = [];
        foreach (['status', 'remarks', 'resolution_notes'] as $f) {
            if (isset($input[$f])) $sets[] = "$f = '" . sanitize($conn, $input[$f]) . "'";
        }
        if (array_key_exists('assigned_worker_id', $input)) {
            if ($input['assigned_worker_id'] === null || $input['assigned_worker_id'] === '' || $input['assigned_worker_id'] === 0) {
                $sets[] = "assigned_worker_id = NULL";
                if (!isset($input['status'])) $sets[] = "status = 'open'";
            } else {
                $sets[] = "assigned_worker_id = " . intval($input['assigned_worker_id']);
                if (!isset($input['status'])) $sets[] = "status = 'assigned'";
            }
        }
        if (isset($input['status']) && $input['status'] === 'resolved') {
            $sets[] = "resolved_at = NOW()";
        }
        if (isset($input['status']) && $input['status'] === 'escalated') {
            $sets[] = "escalated_at = NOW()";
        }
        if (!empty($sets)) {
            $conn->query("UPDATE complaints SET " . implode(', ', $sets) . " WHERE id = $id");
        }
        jsonResponse(true, null, 'Complaint updated');
        break;

    // ==================== RISK LEVELS ====================
    case 'risk_levels':
        requireAuth();
        $sql = "SELECT rl.*, p.id as p_id, p.pole_id as pole_code, p.location, p.zone, p.power_status,
                hd.vibration, hd.temperature, hd.voltage
                FROM risk_levels rl
                JOIN poles p ON rl.pole_id = p.id
                LEFT JOIN hardware_data hd ON p.id = hd.pole_id
                ORDER BY p.pole_id ASC";
        $r = $conn->query($sql);
        $risks = [];
        while ($row = $r->fetch_assoc()) $risks[] = $row;
        jsonResponse(true, $risks);
        break;

    // ==================== CUTOFF CONTROL ====================
    case 'cutoff':
        requireAuth();
        if ($method === 'GET') {
            $sql = "SELECT p.id, p.pole_id, p.location, p.zone, p.power_status,
                    hd.voltage, rl.risk_level, rl.risk_score,
                    (SELECT cr.created_at FROM cutoff_requests cr WHERE cr.pole_id = p.id ORDER BY cr.created_at DESC LIMIT 1) as last_cutoff
                    FROM poles p
                    LEFT JOIN hardware_data hd ON p.id = hd.pole_id
                    LEFT JOIN risk_levels rl ON p.id = rl.pole_id
                    ORDER BY p.pole_id";
            $r = $conn->query($sql);
            $poles = [];
            while ($row = $r->fetch_assoc()) $poles[] = $row;
            jsonResponse(true, $poles);
        }
        break;

    case 'cutoff_toggle':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $poleId = intval($input['pole_id'] ?? 0);
        $action = sanitize($conn, $input['action'] ?? 'cutoff');
        $reason = sanitize($conn, $input['reason'] ?? '');
        $userId = $_SESSION['user_id'];

        // Toggle power
        $newPower = ($action === 'restore') ? 1 : 0;
        $conn->query("UPDATE poles SET power_status = $newPower WHERE id = $poleId");

        // Create cutoff request record
        $reqId = generateId('CUT');
        $stmt = $conn->prepare("INSERT INTO cutoff_requests (request_id, pole_id, action, reason, requested_by, status, executed_at) VALUES (?, ?, ?, ?, ?, 'executed', NOW())");
        $stmt->bind_param("sissi", $reqId, $poleId, $action, $reason, $userId);
        $stmt->execute();

        jsonResponse(true, ['power_status' => $newPower], "Power " . ($newPower ? 'restored' : 'cut off'));
        break;

    // ==================== PROOF VERIFICATION ====================
    case 'proofs':
        requireAuth();
        if ($method === 'GET') {
            $status = sanitize($conn, $_GET['status'] ?? '');
            $workerId = intval($_GET['worker_id'] ?? 0);

            $sql = "SELECT pv.*, t.task_id as task_code, p.pole_id as pole_code, u.name as worker_name
                    FROM proof_verification pv
                    LEFT JOIN tasks t ON pv.task_id = t.id
                    JOIN poles p ON pv.pole_id = p.id
                    JOIN workers w ON pv.worker_id = w.id
                    JOIN users u ON w.user_id = u.id
                    WHERE 1=1";
            if ($status) $sql .= " AND pv.status = '$status'";
            if ($workerId) $sql .= " AND pv.worker_id = $workerId";
            $sql .= " ORDER BY p.pole_id ASC, pv.created_at DESC";

            $r = $conn->query($sql);
            $proofs = [];
            while ($row = $r->fetch_assoc()) $proofs[] = $row;
            jsonResponse(true, $proofs);
        } elseif ($method === 'POST') {
            // Handle file upload
            $proofId = generateId('PRF');
            $taskId = intval($_POST['task_id'] ?? 0) ?: null;
            $complaintId = intval($_POST['complaint_id'] ?? 0) ?: null;
            $workerId = intval($_POST['worker_id'] ?? ($_SESSION['worker_id'] ?? 0));
            $poleId = intval($_POST['pole_id'] ?? 0);
            $proofType = sanitize($conn, $_POST['proof_type'] ?? 'inspection');
            $remarks = sanitize($conn, $_POST['remarks'] ?? '');

            $filePath = '';
            $fileName = '';
            $fileSize = 0;

            if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === 0) {
                $uploadDir = __DIR__ . '/uploads/proofs/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $ext = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
                $fileName = $_FILES['proof_file']['name'];
                $newName = $proofId . '_' . time() . '.' . $ext;
                $filePath = 'uploads/proofs/' . $newName;
                $fileSize = $_FILES['proof_file']['size'];

                move_uploaded_file($_FILES['proof_file']['tmp_name'], $uploadDir . $newName);
            } else {
                $filePath = 'uploads/proofs/placeholder.jpg';
                $fileName = 'placeholder.jpg';
            }

            $stmt = $conn->prepare("INSERT INTO proof_verification (proof_id, task_id, complaint_id, worker_id, pole_id, proof_type, file_path, file_name, file_size, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiissssis", $proofId, $taskId, $complaintId, $workerId, $poleId, $proofType, $filePath, $fileName, $fileSize, $remarks);
            if ($stmt->execute()) {
                jsonResponse(true, ['proof_id' => $proofId], 'Proof uploaded');
            } else {
                jsonResponse(false, null, 'Failed: ' . $conn->error, 500);
            }
        }
        break;

    case 'proof_verify':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        $status = sanitize($conn, $input['status'] ?? 'approved');
        $reason = sanitize($conn, $input['rejection_reason'] ?? '');
        $userId = $_SESSION['user_id'];

        $conn->query("UPDATE proof_verification SET status = '$status', verified_by = $userId, verified_at = NOW(), rejection_reason = '$reason' WHERE id = $id");
        jsonResponse(true, null, 'Proof ' . $status);
        break;

    // ==================== WORKER DASHBOARD ====================
    case 'worker_dashboard':
        requireAuth();
        $workerId = intval($_SESSION['worker_id'] ?? 0);
        if (!$workerId) jsonResponse(false, null, 'Worker not found', 400);

        // ====== AUTO-PROVISION: Ensure every worker has 10-20 poles with tasks & complaints ======
        $chkPoles = $conn->query("SELECT COUNT(*) as cnt FROM poles WHERE assigned_worker_id = $workerId");
        $poleCount = $chkPoles->fetch_assoc()['cnt'];
        $chkTasks = $conn->query("SELECT COUNT(DISTINCT pole_id) as cnt FROM tasks WHERE worker_id = $workerId");
        $taskPoleCount = $chkTasks->fetch_assoc()['cnt'];
        $chkComps = $conn->query("SELECT COUNT(DISTINCT pole_id) as cnt FROM complaints WHERE assigned_worker_id = $workerId");
        $compPoleCount = $chkComps->fetch_assoc()['cnt'];

        $minPoles = 10;
        if ($poleCount < $minPoles || $taskPoleCount < $minPoles || $compPoleCount < $minPoles) {
            // Get worker zone
            $wInfo = $conn->query("SELECT zone FROM workers WHERE id = $workerId")->fetch_assoc();
            $wZone = $wInfo['zone'] ?? 'north';

            // 1. Top up poles to 10-20
            if ($poleCount < $minPoles) {
                $targetPoles = rand(10, 20);
                $need = $targetPoles - $poleCount;
                // Try unassigned poles first
                $conn->query("UPDATE poles SET assigned_worker_id = $workerId WHERE zone = '$wZone' AND assigned_worker_id IS NULL ORDER BY RAND() LIMIT $need");
                $gotPoles = $conn->affected_rows;
                // If not enough, share poles from same zone
                if ($gotPoles < $need) {
                    $still = $need - $gotPoles;
                    $conn->query("UPDATE poles SET assigned_worker_id = $workerId WHERE zone = '$wZone' AND assigned_worker_id != $workerId ORDER BY RAND() LIMIT $still");
                    $gotPoles += $conn->affected_rows;
                }
                // If still not enough, take from other zones
                if ($gotPoles < $need) {
                    $still = $need - $gotPoles;
                    $conn->query("UPDATE poles SET assigned_worker_id = $workerId WHERE assigned_worker_id IS NULL ORDER BY RAND() LIMIT $still");
                    $gotPoles += $conn->affected_rows;
                }
                if ($gotPoles < $need) {
                    $still = $need - $gotPoles;
                    $conn->query("UPDATE poles SET assigned_worker_id = $workerId WHERE assigned_worker_id != $workerId ORDER BY RAND() LIMIT $still");
                }
            }

            // 2. Create tasks for assigned poles that don't have tasks yet (4-6 tasks per pole)
            if ($taskPoleCount < $minPoles) {
                // Only get poles that have NO tasks for this worker
                $pRes = $conn->query("SELECT p.id, p.pole_id, p.location FROM poles p 
                    WHERE p.assigned_worker_id = $workerId 
                    AND p.id NOT IN (SELECT DISTINCT pole_id FROM tasks WHERE worker_id = $workerId)");
                $taskTemplates = [
                    ['Routine Inspection', 'Perform monthly routine inspection of all systems', 'inspection', 'medium'],
                    ['Sensor Calibration', 'Calibrate vibration and temperature sensors', 'maintenance', 'high'],
                    ['Structural Assessment', 'Inspect pole structure for cracks or corrosion', 'inspection', 'medium'],
                    ['Wiring Check', 'Inspect wiring connections and cable integrity', 'maintenance', 'medium'],
                    ['LED Panel Check', 'Inspect LED lighting panel for damage', 'inspection', 'low'],
                    ['Voltage Monitoring', 'Check voltage levels and power supply unit', 'inspection', 'high'],
                    ['Emergency Foundation Check', 'Check foundation stability after alert', 'emergency', 'critical'],
                    ['Safety Compliance Review', 'Verify pole meets safety standards', 'inspection', 'high'],
                    ['Paint & Anti-Corrosion', 'Apply anti-corrosion coating and repaint', 'maintenance', 'low'],
                    ['Camera Mount Check', 'Inspect CCTV and sensor mount brackets', 'inspection', 'medium'],
                    ['Transformer Inspection', 'Inspect transformer unit for overheating signs', 'inspection', 'high'],
                    ['Cable Replacement', 'Replace damaged power cable', 'repair', 'critical'],
                    ['Solar Panel Cleaning', 'Clean solar panel and check output', 'maintenance', 'low'],
                    ['Tilt Correction', 'Correct pole tilt to safe angle', 'repair', 'high'],
                    ['Foundation Repair', 'Repair cracked or damaged foundation', 'emergency', 'critical'],
                    ['Humidity Sensor Fix', 'Replace or recalibrate humidity sensor', 'repair', 'medium'],
                ];
                $ti = 0;
                while ($pRes && $p = $pRes->fetch_assoc()) {
                    $numTasks = rand(4, 6);
                    for ($j = 0; $j < $numTasks; $j++) {
                        $td = $taskTemplates[$ti % count($taskTemplates)];
                        $tskId = generateId('TSK');
                        $tTitle = $conn->real_escape_string($td[0] . ' - ' . $p['location']);
                        $tDesc = $conn->real_escape_string($td[1]);
                        $statusArr = ['pending', 'pending', 'in_progress', 'pending', 'completed'];
                        $tStatus = $statusArr[array_rand($statusArr)];
                        $dueDate = date('Y-m-d', strtotime('+' . rand(1, 21) . ' days'));
                        $conn->query("INSERT INTO tasks (task_id, pole_id, worker_id, title, description, task_type, priority, status, due_date" . ($tStatus === 'completed' ? ", completed_at" : "") . ")
                            VALUES ('$tskId', {$p['id']}, $workerId, '$tTitle', '$tDesc', '{$td[2]}', '{$td[3]}', '$tStatus', '$dueDate'" . ($tStatus === 'completed' ? ", NOW()" : "") . ")");
                        $ti++;
                    }
                }
            }

            // 3. Top up complaints so all assigned poles have complaints (10-20 total)
            $chkCompNow = $conn->query("SELECT COUNT(*) as cnt FROM complaints WHERE assigned_worker_id = $workerId")->fetch_assoc()['cnt'];
            if ($compPoleCount < $minPoles) {
                // Get the actual pole count to ensure every pole has at least 1 complaint
                $actualPoles = $conn->query("SELECT COUNT(*) as cnt FROM poles WHERE assigned_worker_id = $workerId")->fetch_assoc()['cnt'];
                $targetComps = max(rand(10, 20), $actualPoles);
                $needed = $targetComps - $chkCompNow;
                if ($needed > 0) {
                    // Get worker's poles that don't already have complaints, prioritize those first
                    $wpRes = $conn->query("SELECT p.id, p.pole_id FROM poles p 
                        WHERE p.assigned_worker_id = $workerId 
                        AND p.id NOT IN (SELECT DISTINCT pole_id FROM complaints WHERE assigned_worker_id = $workerId AND pole_id IS NOT NULL)
                        ORDER BY RAND()");
                    $noPoles = [];
                    while ($wpRes && $cp = $wpRes->fetch_assoc()) $noPoles[] = $cp;

                    // Also get all worker's poles for cycling
                    $allPRes = $conn->query("SELECT id, pole_id FROM poles WHERE assigned_worker_id = $workerId ORDER BY RAND()");
                    $allPoles = [];
                    while ($allPRes && $cp = $allPRes->fetch_assoc()) $allPoles[] = $cp;

                    // Merge: poles without complaints first, then all poles for extras
                    $orderedPoles = array_merge($noPoles, $allPoles);

                    $issues = [
                        ['Street light not working at night', 'lighting', 'high'],
                        ['Buzzing sound from transformer unit', 'noise', 'medium'],
                        ['Pole appears tilted after storm', 'structural', 'urgent'],
                        ['Flickering light disturbing residents', 'lighting', 'medium'],
                        ['Exposed wiring near base of pole', 'electrical', 'urgent'],
                        ['Light stays on during daytime', 'electrical', 'medium'],
                        ['Sparking observed at pole base', 'electrical', 'urgent'],
                        ['Pole paint peeling and rusting badly', 'structural', 'low'],
                        ['Camera not working on pole', 'other', 'medium'],
                        ['Loud crackling noise at night', 'noise', 'high'],
                        ['Transformer oil leaking on road', 'safety', 'urgent'],
                        ['Broken glass panel on ground near pole', 'safety', 'high'],
                        ['No light in entire colony since 3 days', 'lighting', 'urgent'],
                        ['Pole base flooded during rain', 'structural', 'medium'],
                        ['Sensor box hanging loosely from pole', 'other', 'high'],
                        ['Electric shock felt when touching pole', 'electrical', 'urgent'],
                        ['Street light too dim to see road', 'lighting', 'medium'],
                        ['Pole number plate missing', 'other', 'low'],
                        ['Foundation crack visible at base', 'structural', 'high'],
                        ['Wires hanging dangerously from pole top', 'safety', 'urgent'],
                    ];
                    $names = ['Ramesh Kumar', 'Sunita Devi', 'Ajay Verma', 'Priya Sharma', 'Vikash Singh', 'Anita Rao', 'Deepak Gupta', 'Meena Joshi', 'Suresh Yadav', 'Kavita Nair', 'Mohan Lal', 'Rekha Pandey', 'Sanjay Mishra', 'Poonam Devi', 'Harish Chandra', 'Geeta Rani', 'Manoj Tripathi', 'Savita Kumari', 'Prakash Jha', 'Neelam Singh'];
                    $statuses = ['assigned', 'assigned', 'open', 'assigned', 'resolved'];
                    for ($ci = 0; $ci < $needed; $ci++) {
                        $cp = $orderedPoles[$ci % count($orderedPoles)];
                        $cmpId = generateId('CMP');
                        $iss = $issues[$ci % count($issues)];
                        $cName = $conn->real_escape_string($names[$ci % count($names)]);
                        $cIssue = $conn->real_escape_string($iss[0]);
                        $cPhone = '+91 98765 ' . rand(10000, 99999);
                        $cEmail = strtolower(str_replace(' ', '.', $names[$ci % count($names)])) . '@email.com';
                        $cStatus = $statuses[$ci % count($statuses)];
                        $conn->query("INSERT INTO complaints (complaint_id, citizen_name, citizen_phone, citizen_email, pole_id, issue, category, status, assigned_worker_id, priority" . ($cStatus === 'resolved' ? ", resolved_at" : "") . ")
                            VALUES ('$cmpId', '$cName', '$cPhone', '$cEmail', {$cp['id']}, '$cIssue', '{$iss[1]}', '$cStatus', $workerId, '{$iss[2]}'" . ($cStatus === 'resolved' ? ", NOW()" : "") . ")");
                    }
                }
            }

            // Give worker starter stats if they have 0
            $wStat = $conn->query("SELECT tasks_completed, rating FROM workers WHERE id = $workerId")->fetch_assoc();
            if (intval($wStat['tasks_completed']) == 0) {
                $starterCompleted = rand(10, 50);
                $starterRating = round(rand(35, 48) / 10, 1);
                $conn->query("UPDATE workers SET tasks_completed = $starterCompleted, rating = $starterRating WHERE id = $workerId");
            }
        }
        // ====== END AUTO-PROVISION ======

        $stats = [];

        // Assigned poles
        $r = $conn->query("SELECT COUNT(*) as cnt FROM poles WHERE assigned_worker_id = $workerId");
        $stats['assigned_poles'] = $r->fetch_assoc()['cnt'];

        // Today's tasks
        $r = $conn->query("SELECT COUNT(*) as cnt FROM tasks WHERE worker_id = $workerId AND status IN ('pending', 'in_progress')");
        $stats['todays_tasks'] = $r->fetch_assoc()['cnt'];

        // Critical alerts on assigned poles
        $r = $conn->query("SELECT COUNT(*) as cnt FROM alerts a 
            JOIN poles p ON a.pole_id = p.id 
            WHERE p.assigned_worker_id = $workerId AND a.severity = 'critical' AND a.status != 'resolved'");
        $stats['critical_alerts'] = $r->fetch_assoc()['cnt'];

        // Worker info
        $r = $conn->query("SELECT w.*, u.name, u.phone FROM workers w JOIN users u ON w.user_id = u.id WHERE w.id = $workerId");
        $stats['worker'] = $r->fetch_assoc();

        // Performance
        $stats['performance'] = $stats['worker'] ? round(($stats['worker']['tasks_completed'] / max($stats['worker']['tasks_completed'] + $stats['todays_tasks'], 1)) * 100) : 0;

        // Assigned poles details
        $r = $conn->query("SELECT p.*, rl.risk_level, rl.risk_score, hd.vibration, hd.temperature, hd.voltage
            FROM poles p
            LEFT JOIN risk_levels rl ON p.id = rl.pole_id
            LEFT JOIN hardware_data hd ON p.id = hd.pole_id
            WHERE p.assigned_worker_id = $workerId
            ORDER BY p.pole_id ASC");
        $poles = [];
        while ($row = $r->fetch_assoc()) $poles[] = $row;
        $stats['poles'] = $poles;

        jsonResponse(true, $stats);
        break;

    // ==================== CHANGE PASSWORD ====================
    case 'change_password':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();
        $userId = $_SESSION['user_id'];
        $current = $input['current_password'] ?? '';
        $newPass = $input['new_password'] ?? '';

        $r = $conn->query("SELECT password FROM users WHERE id = $userId");
        $user = $r->fetch_assoc();

        if (!password_verify($current, $user['password'])) {
            jsonResponse(false, null, 'Current password is incorrect', 400);
        }

        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$hash' WHERE id = $userId");

        // Save to password history
        savePasswordHistory($conn, $userId, $hash);
        logSecurity($conn, $userId, 'password_change', 'Password changed successfully', 'info');

        jsonResponse(true, null, 'Password changed successfully');
        break;

    // ==================== REGISTER ====================
    case 'register':
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $input = getInput();

        $name     = trim($input['name'] ?? '');
        $email    = trim($input['email'] ?? '');
        $phone    = trim($input['phone'] ?? '');
        $password = $input['password'] ?? '';
        $role     = $input['role'] ?? 'worker';        // 'worker' or 'admin'
        $zone     = $input['zone'] ?? 'north';          // for workers

        // Validation
        if (!$name || !$email || !$password) {
            jsonResponse(false, null, 'Name, email and password are required', 400);
        }
        if (strlen($password) < 6) {
            jsonResponse(false, null, 'Password must be at least 6 characters', 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, null, 'Invalid email address', 400);
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            jsonResponse(false, null, 'Email already registered', 409);
        }
        $stmt->close();

        // Determine role_id
        $roleId = ($role === 'admin') ? 1 : 2;

        // Generate unique user ID
        $userId = 'USR-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
        // Ensure uniqueness
        $check = $conn->query("SELECT id FROM users WHERE user_id = '$userId'");
        while ($check->num_rows > 0) {
            $userId = 'USR-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
            $check = $conn->query("SELECT id FROM users WHERE user_id = '$userId'");
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $safeName  = sanitize($conn, $name);
        $safeEmail = sanitize($conn, $email);
        $safePhone = sanitize($conn, $phone);

        $stmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssssi", $userId, $safeName, $safeEmail, $hash, $safePhone, $roleId);

        if (!$stmt->execute()) {
            jsonResponse(false, null, 'Registration failed: ' . $stmt->error, 500);
        }

        $newUserId = $conn->insert_id;
        $stmt->close();

        // If worker, create worker record and auto-assign starter data
        if ($roleId === 2) {
            $workerId = 'WRK-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            $wCheck = $conn->query("SELECT id FROM workers WHERE worker_id = '$workerId'");
            while ($wCheck->num_rows > 0) {
                $workerId = 'WRK-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
                $wCheck = $conn->query("SELECT id FROM workers WHERE worker_id = '$workerId'");
            }
            $safeZone = sanitize($conn, $zone);
            $today = date('Y-m-d');
            $conn->query("INSERT INTO workers (worker_id, user_id, zone, status, joined_date) VALUES ('$workerId', $newUserId, '$safeZone', 'active', '$today')");
            $newWorkerInternalId = $conn->insert_id;

            // ---- AUTO-ASSIGN STARTER DATA FOR NEW WORKER ----

            // 1. Assign some poles from their zone to the new worker
            //    First try unassigned poles, then share from least-loaded workers
            $conn->query("UPDATE poles SET assigned_worker_id = $newWorkerInternalId
                WHERE zone = '$safeZone' AND assigned_worker_id IS NULL
                ORDER BY RAND() LIMIT 3");
            $assignedPoles = $conn->affected_rows;

            // If not enough unassigned poles, share some from the same zone
            if ($assignedPoles < 3) {
                $needed = 3 - $assignedPoles;
                $conn->query("UPDATE poles SET assigned_worker_id = $newWorkerInternalId
                    WHERE zone = '$safeZone' AND assigned_worker_id != $newWorkerInternalId
                    ORDER BY RAND() LIMIT $needed");
            }

            // 2. Create tasks for assigned poles
            $poleRes = $conn->query("SELECT id, pole_id, location FROM poles WHERE assigned_worker_id = $newWorkerInternalId");
            $taskTitles = [
                ['Routine Inspection', 'Perform monthly routine inspection and verify all systems', 'inspection', 'medium'],
                ['Sensor Calibration', 'Calibrate vibration and temperature sensors', 'maintenance', 'high'],
                ['Monthly Maintenance', 'Complete scheduled monthly maintenance check', 'maintenance', 'medium'],
                ['LED Panel Check', 'Inspect LED lighting panel for damage or malfunction', 'inspection', 'low'],
                ['Voltage Monitoring', 'Check voltage levels and power supply unit', 'inspection', 'high'],
                ['Structural Assessment', 'Inspect pole structure for cracks or corrosion', 'inspection', 'medium'],
                ['Wiring Check', 'Inspect wiring connections and cable integrity', 'maintenance', 'medium'],
                ['Safety Compliance Review', 'Verify pole meets safety standards', 'inspection', 'high'],
            ];
            $tIdx = 0;
            while ($pole = $poleRes->fetch_assoc()) {
                // Create 2-3 tasks per pole
                $tasksPerPole = rand(2, 3);
                for ($j = 0; $j < $tasksPerPole; $j++) {
                    $tData = $taskTitles[$tIdx % count($taskTitles)];
                    $tskId = generateId('TSK');
                    $tTitle = $conn->real_escape_string($tData[0] . ' - ' . $pole['location']);
                    $tDesc = $conn->real_escape_string($tData[1]);
                    $tType = $tData[2];
                    $tPri = $tData[3];
                    $statusArr = ['pending', 'pending', 'pending', 'in_progress'];
                    $tStatus = $statusArr[array_rand($statusArr)];
                    $dueDate = date('Y-m-d', strtotime('+' . rand(1, 14) . ' days'));
                    $conn->query("INSERT INTO tasks (task_id, pole_id, worker_id, title, description, task_type, priority, status, due_date)
                        VALUES ('$tskId', {$pole['id']}, $newWorkerInternalId, '$tTitle', '$tDesc', '$tType', '$tPri', '$tStatus', '$dueDate')");
                    $tIdx++;
                }
            }

            // 3. Assign unassigned complaints to this worker
            $conn->query("UPDATE complaints SET assigned_worker_id = $newWorkerInternalId, status = IF(status = 'open', 'assigned', status)
                WHERE assigned_worker_id IS NULL
                ORDER BY RAND() LIMIT 3");
            $assignedComplaints = $conn->affected_rows;

            // If not enough unassigned complaints, create new ones for the worker's poles
            if ($assignedComplaints < 2) {
                $needed = 3 - $assignedComplaints;
                $workerPoles = $conn->query("SELECT id, pole_id FROM poles WHERE assigned_worker_id = $newWorkerInternalId ORDER BY RAND() LIMIT $needed");
                $complaintIssues = [
                    ['Light not working properly at night', 'lighting', 'medium'],
                    ['Strange noise from pole transformer', 'noise', 'high'],
                    ['Pole paint peeling and rusting', 'structural', 'low'],
                    ['Flickering lights disturbing residents', 'lighting', 'medium'],
                    ['Exposed wires near pole base', 'electrical', 'urgent'],
                ];
                $citizenNames = ['Amit Kumar', 'Priya Singh', 'Rahul Verma', 'Neha Gupta', 'Sanjay Rao'];
                $ci = 0;
                while ($workerPoles && $cp = $workerPoles->fetch_assoc()) {
                    $cmpId = generateId('CMP');
                    $cIssue = $complaintIssues[$ci % count($complaintIssues)];
                    $cName = $conn->real_escape_string($citizenNames[$ci % count($citizenNames)]);
                    $cIssueText = $conn->real_escape_string($cIssue[0]);
                    $cCat = $cIssue[1];
                    $cPri = $cIssue[2];
                    $conn->query("INSERT INTO complaints (complaint_id, citizen_name, citizen_phone, citizen_email, pole_id, issue, category, status, assigned_worker_id, priority)
                        VALUES ('$cmpId', '$cName', '+91 98765 " . rand(10000, 99999) . "', '" . strtolower(str_replace(' ', '.', $citizenNames[$ci % count($citizenNames)])) . "@email.com', {$cp['id']}, '$cIssueText', '$cCat', 'assigned', $newWorkerInternalId, '$cPri')");
                    $ci++;
                }
            }

            // Update worker's tasks_completed with a starter value
            $starterCompleted = rand(5, 30);
            $starterRating = round(rand(35, 48) / 10, 1);
            $conn->query("UPDATE workers SET tasks_completed = $starterCompleted, rating = $starterRating WHERE id = $newWorkerInternalId");
        }

        // Save initial password to history
        savePasswordHistory($conn, $newUserId, $hash);

        // Log the registration
        logSecurity($conn, $newUserId, 'register', "New $role account registered: $safeEmail", 'info', [
            'user_id' => $userId,
            'role' => $role,
            'email' => $safeEmail
        ]);

        jsonResponse(true, ['user_id' => $userId], 'Registration successful! You can now login.');
        break;

    // ==================== SECURITY LOGS (Admin only) ====================
    case 'security-logs':
        requireAuth();
        if ($_SESSION['role'] !== 'admin') jsonResponse(false, null, 'Admin access required', 403);

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        $eventFilter = sanitize($conn, $_GET['event'] ?? '');
        $severityFilter = sanitize($conn, $_GET['severity'] ?? '');

        $where = "1=1";
        if ($eventFilter) $where .= " AND sl.event_type = '$eventFilter'";
        if ($severityFilter) $where .= " AND sl.severity = '$severityFilter'";

        // Total count
        $r = $conn->query("SELECT COUNT(*) as cnt FROM security_logs sl WHERE $where");
        $total = $r->fetch_assoc()['cnt'];

        $r = $conn->query("SELECT sl.*, u.name as user_name, u.email as user_email 
            FROM security_logs sl LEFT JOIN users u ON sl.user_id = u.id 
            WHERE $where ORDER BY sl.created_at DESC LIMIT $limit OFFSET $offset");
        $logs = [];
        while ($row = $r->fetch_assoc()) $logs[] = $row;

        jsonResponse(true, ['logs' => $logs, 'total' => (int)$total, 'page' => $page, 'limit' => $limit]);
        break;

    // ==================== LOGIN ATTEMPTS (Admin only) ====================
    case 'login-attempts':
        requireAuth();
        if ($_SESSION['role'] !== 'admin') jsonResponse(false, null, 'Admin access required', 403);

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $r = $conn->query("SELECT COUNT(*) as cnt FROM login_attempts");
        $total = $r->fetch_assoc()['cnt'];

        $r = $conn->query("SELECT * FROM login_attempts ORDER BY attempted_at DESC LIMIT $limit OFFSET $offset");
        $attempts = [];
        while ($row = $r->fetch_assoc()) $attempts[] = $row;

        jsonResponse(true, ['attempts' => $attempts, 'total' => (int)$total, 'page' => $page, 'limit' => $limit]);
        break;

    // ==================== ACTIVE SESSIONS (Admin only) ====================
    case 'active-sessions':
        requireAuth();
        if ($_SESSION['role'] !== 'admin') jsonResponse(false, null, 'Admin access required', 403);

        $r = $conn->query("SELECT us.*, u.name, u.email, u.user_id as uid 
            FROM user_sessions us JOIN users u ON us.user_id = u.id 
            WHERE us.is_active = 1 ORDER BY us.last_activity DESC");
        $sessions = [];
        while ($row = $r->fetch_assoc()) $sessions[] = $row;

        jsonResponse(true, $sessions);
        break;

    // ==================== IP BLACKLIST MANAGEMENT (Admin only) ====================
    case 'ip-blacklist':
        requireAuth();
        if ($_SESSION['role'] !== 'admin') jsonResponse(false, null, 'Admin access required', 403);

        if ($method === 'GET') {
            $r = $conn->query("SELECT bl.*, u.name as blocked_by_name FROM ip_blacklist bl LEFT JOIN users u ON bl.blocked_by = u.id ORDER BY bl.created_at DESC");
            $list = [];
            while ($row = $r->fetch_assoc()) $list[] = $row;
            jsonResponse(true, $list);
        }
        elseif ($method === 'POST') {
            $input = getInput();
            $ip = sanitize($conn, $input['ip_address'] ?? '');
            $reason = sanitize($conn, $input['reason'] ?? 'Manually blocked');
            $permanent = !empty($input['is_permanent']) ? 1 : 0;
            $hours = intval($input['hours'] ?? 24);
            $adminId = $_SESSION['user_id'];

            if (!$ip) jsonResponse(false, null, 'IP address is required', 400);

            $expiresAt = $permanent ? null : date('Y-m-d H:i:s', strtotime("+$hours hours"));
            $stmt = $conn->prepare("INSERT INTO ip_blacklist (ip_address, reason, blocked_by, is_permanent, expires_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE reason=VALUES(reason), is_permanent=VALUES(is_permanent), expires_at=VALUES(expires_at)");
            $stmt->bind_param("sssis", $ip, $reason, $adminId, $permanent, $expiresAt);
            $stmt->execute();
            $stmt->close();

            logSecurity($conn, $adminId, 'suspicious_activity', "IP blocked: $ip - $reason", 'critical', ['ip' => $ip]);
            jsonResponse(true, null, "IP $ip has been blocked");
        }
        elseif ($method === 'DELETE') {
            $input = getInput();
            $ip = sanitize($conn, $input['ip_address'] ?? '');
            if (!$ip) jsonResponse(false, null, 'IP address is required', 400);
            $conn->query("DELETE FROM ip_blacklist WHERE ip_address = '$ip'");
            logSecurity($conn, $_SESSION['user_id'], 'suspicious_activity', "IP unblocked: $ip", 'info', ['ip' => $ip]);
            jsonResponse(true, null, "IP $ip has been unblocked");
        }
        break;

    // ==================== MATERIAL REQUESTS ====================
    case 'material_requests':
        requireAuth();
        $workerId = $_SESSION['worker_id'] ?? 0;
        if (!$workerId) jsonResponse(false, null, 'Worker access required', 403);
        
        $r = $conn->query("SELECT * FROM material_requests WHERE worker_id = $workerId ORDER BY id ASC");
        $reqs = [];
        while ($row = $r->fetch_assoc()) $reqs[] = $row;
        jsonResponse(true, $reqs);
        break;

    case 'material_request_add':
        requireAuth();
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        $workerId = $_SESSION['worker_id'] ?? 0;
        if (!$workerId) jsonResponse(false, null, 'Worker access required', 403);
        
        $input = getInput();
        $name = sanitize($conn, $input['material_name'] ?? '');
        $qty = intval($input['quantity'] ?? 1);
        $urgency = sanitize($conn, $input['urgency'] ?? 'low');
        $notes = sanitize($conn, $input['notes'] ?? '');
        
        if (!$name) jsonResponse(false, null, 'Material name is required', 400);
        
        $stmt = $conn->prepare("INSERT INTO material_requests (worker_id, material_name, quantity, urgency, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $workerId, $name, $qty, $urgency, $notes);
        $stmt->execute();
        
        jsonResponse(true, null, 'Material request submitted');
        break;

    case 'admin_material_requests':
        requireAuth();
        if ($_SESSION['role'] !== 'admin') jsonResponse(false, null, 'Admin access required', 403);
        
        $r = $conn->query("SELECT m.*, w.worker_id as worker_code, u.name as worker_name 
                           FROM material_requests m 
                           JOIN workers w ON m.worker_id = w.id 
                           JOIN users u ON w.user_id = u.id 
                           ORDER BY w.worker_id ASC, m.created_at DESC");
        $reqs = [];
        while ($row = $r->fetch_assoc()) $reqs[] = $row;
        jsonResponse(true, $reqs);
        break;

    case 'admin_material_request_update':
        requireAuth();
        if ($_SESSION['role'] !== 'admin') jsonResponse(false, null, 'Admin access required', 403);
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);
        
        $input = getInput();
        $id = intval($input['id'] ?? 0);
        $status = sanitize($conn, $input['status'] ?? '');
        
        if (!$id || !in_array($status, ['approved', 'rejected', 'delivered'])) {
            jsonResponse(false, null, 'Invalid input', 400);
        }
        
        $conn->query("UPDATE material_requests SET status = '$status' WHERE id = $id");
        jsonResponse(true, null, 'Material request updated');
        break;

    // ==================== ACCELEROMETER ====================
    case 'accelerometer_readings':
        requireAuth();
        $poleId = intval($_GET['pole_id'] ?? 0);
        $limit = intval($_GET['limit'] ?? 50);
        if ($limit > 500) $limit = 500;

        $where = '';
        if ($poleId) $where = "WHERE a.pole_id = $poleId";

        $r = $conn->query("SELECT a.*, p.pole_id as pole_code, p.location
                           FROM accelerometer_readings a
                           LEFT JOIN poles p ON a.pole_id = p.id
                           $where
                           ORDER BY a.recorded_at DESC LIMIT $limit");
        $readings = [];
        while ($row = $r->fetch_assoc()) $readings[] = $row;
        jsonResponse(true, $readings);
        break;

    case 'accelerometer_submit':
        // Accept readings from ESP32 circuit or manual input
        if ($method !== 'POST') jsonResponse(false, null, 'Method not allowed', 405);

        $input = getInput();
        $poleId = intval($input['pole_id'] ?? 0);
        $accelX = floatval($input['accel_x'] ?? 0);
        $accelY = floatval($input['accel_y'] ?? 0);
        $accelZ = floatval($input['accel_z'] ?? 0);
        $magnitude = sqrt($accelX * $accelX + $accelY * $accelY + $accelZ * $accelZ);
        $sourceIp = getClientIp();

        $stmt = $conn->prepare("INSERT INTO accelerometer_readings (pole_id, accel_x, accel_y, accel_z, magnitude, source_ip) VALUES (?, ?, ?, ?, ?, ?)");
        $nullPole = $poleId ?: null;
        $stmt->bind_param("idddds", $nullPole, $accelX, $accelY, $accelZ, $magnitude, $sourceIp);
        $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();

        jsonResponse(true, ['id' => $insertId, 'magnitude' => round($magnitude, 4)], 'Accelerometer reading saved');
        break;

    case 'accelerometer_live':
        // Fetch from connected ESP32 and return (proxy endpoint)
        requireAuth();
        $espIp = sanitize($conn, $_GET['esp_ip'] ?? '');
        if (!$espIp) jsonResponse(false, null, 'ESP IP is required', 400);

        // Validate IP format
        if (!filter_var($espIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
            jsonResponse(false, null, 'Invalid IP address', 400);
        }

        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $url = 'http://' . $espIp . '/accelerometer';
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            jsonResponse(false, null, 'Cannot reach ESP32 accelerometer endpoint');
        }
        $data = json_decode($response, true);
        if (!$data) {
            jsonResponse(false, null, 'Invalid response from ESP32');
        }
        jsonResponse(true, $data, 'Live reading fetched');
        break;

    // ==================== HEALTH CHECK ====================
    case 'health':
        jsonResponse(true, [
            'message' => 'Smart Pole API is running (PHP)',
            'timestamp' => date('c'),
            'version' => '2.0.0-php'
        ]);
        break;

    // ==================== DEFAULT ====================
    default:
        jsonResponse(false, null, 'Unknown action: ' . $action, 404);
        break;
}

$conn->close();
?>
