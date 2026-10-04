<?php
// Session Termination Handler
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
if (strpos($referer, 'admin/') !== false) {
    unset($_SESSION['admin_session']);
} else {
    unset($_SESSION['user_session']);
}

header("Location: index.php");
exit();
?>
