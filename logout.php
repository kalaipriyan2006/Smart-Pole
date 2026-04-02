<?php
session_start();
$role = $_SESSION['role'] ?? '';
session_unset();
session_destroy();

// Redirect back to the appropriate login page
if ($role === 'worker') {
    header('Location: worker_login.php');
} else {
    header('Location: admin_login.php');
}
exit;
