<?php
/**
 * Seed Accelerometer Readings into Smart Pole Management System
 * Run this once: http://localhost/inba%20project/seed_accelerometer.php
 */

$conn = new mysqli('localhost', 'root', '', 'smart_pole_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Check if readings already exist
$check = $conn->query("SELECT COUNT(*) as cnt FROM accelerometer_readings");
$existing = $check->fetch_assoc()['cnt'];

if ($existing > 0) {
    echo "<h2 style='color:orange;font-family:Segoe UI;'>Accelerometer table already has $existing readings. Skipping seed.</h2>";
    echo "<p style='font-family:Segoe UI;'>To re-seed, run: <code>TRUNCATE TABLE accelerometer_readings;</code> first.</p>";
    $conn->close();
    exit;
}

$accelCount = 0;
$accelStmt = $conn->prepare("INSERT INTO accelerometer_readings (pole_id, accel_x, accel_y, accel_z, magnitude, source_ip, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Get actual pole IDs from database
$poleResult = $conn->query("SELECT id FROM poles ORDER BY id LIMIT 50");
$accelPoles = [];
while ($row = $poleResult->fetch_assoc()) {
    $accelPoles[] = intval($row['id']);
}

if (empty($accelPoles)) {
    echo "<h2 style='color:red;font-family:Segoe UI;'>No poles found in database. Run setup.php first.</h2>";
    $conn->close();
    exit;
}

// Use up to 16 poles
$accelPoles = array_slice($accelPoles, 0, 16);
$baseTime = strtotime('2026-03-12 00:00:00');

foreach ($accelPoles as $pId) {
    // Each pole gets 15-25 readings spread over 24 hours
    $numReadings = rand(15, 25);
    for ($i = 0; $i < $numReadings; $i++) {
        $timeOffset = rand(0, 86400);
        $recordedAt = date('Y-m-d H:i:s', $baseTime + $timeOffset);

        // Realistic accelerometer values: mostly near 0 for X/Y, ~9.8 for Z (gravity)
        $vibrationLevel = rand(0, 100) / 100;
        $ax = round((rand(-50, 50) / 100) * (1 + $vibrationLevel), 4);
        $ay = round((rand(-50, 50) / 100) * (1 + $vibrationLevel), 4);
        $az = round(9.8 + (rand(-30, 30) / 100) * (1 + $vibrationLevel), 4);

        // Some poles have higher vibration (traffic/wind zones)
        if (in_array($pId, array_slice($accelPoles, 0, 4))) {
            $ax = round($ax * 2.5, 4);
            $ay = round($ay * 2.5, 4);
            $az = round(9.8 + (rand(-80, 80) / 100), 4);
        }

        // Occasional spike readings (impacts, strong wind)
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

// Show results
$totalCheck = $conn->query("SELECT COUNT(*) as cnt FROM accelerometer_readings");
$totalReadings = $totalCheck->fetch_assoc()['cnt'];

$conn->close();
?>
<!DOCTYPE html>
<html>
<head><title>Accelerometer Seed</title></head>
<body style="font-family:'Segoe UI',sans-serif;background:#0a0a1a;color:#fff;padding:40px;max-width:600px;margin:0 auto;">
<h1 style="color:#6c5ce7;text-align:center;">Accelerometer Data Seeded</h1>
<div style="background:rgba(0,184,148,0.15);border:1px solid #00b894;border-radius:10px;padding:20px;margin:20px 0;">
  <p style="color:#00b894;font-size:18px;margin:0;">
    <strong><?php echo $accelCount; ?></strong> readings inserted across <strong><?php echo count($accelPoles); ?></strong> poles
  </p>
  <p style="color:#b2bec3;margin:10px 0 0;">Total accelerometer readings in DB: <strong><?php echo $totalReadings; ?></strong></p>
</div>
<div style="margin-top:20px;text-align:center;">
  <a href="admin.php" style="color:#6c5ce7;margin-right:20px;text-decoration:none;font-weight:600;">Go to Admin Panel &rarr;</a>
  <a href="worker.php" style="color:#00cec9;text-decoration:none;font-weight:600;">Go to Worker Panel &rarr;</a>
</div>
</body>
</html>
