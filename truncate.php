<?php
$conn = new mysqli('localhost', 'root', '', 'smart_pole_db');
$conn->query('SET FOREIGN_KEY_CHECKS = 0;');
$conn->query('TRUNCATE TABLE poles;');
$conn->query('TRUNCATE TABLE hardware_data;');
$conn->query('TRUNCATE TABLE risk_levels;');
$conn->query('SET FOREIGN_KEY_CHECKS = 1;');
echo "Tables truncated successfully.";
?>