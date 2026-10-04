<?php
// Session Authentication and Authorization Helper

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a standard user is logged in.
 */
function is_logged_in() {
    return isset($_SESSION['user_session']) && 
           isset($_SESSION['user_session']['logged_in']) && 
           $_SESSION['user_session']['logged_in'] === true &&
           isset($_SESSION['user_session']['id']) && 
           isset($_SESSION['user_session']['role']) && 
           $_SESSION['user_session']['role'] === 'user';
}

/**
 * Check if an admin is logged in.
 */
function is_admin() {
    return isset($_SESSION['admin_session']) && 
           isset($_SESSION['admin_session']['logged_in']) && 
           $_SESSION['admin_session']['logged_in'] === true &&
           isset($_SESSION['admin_session']['id']) && 
           isset($_SESSION['admin_session']['role']) && 
           $_SESSION['admin_session']['role'] === 'admin';
}

/**
 * Restrict page access to logged-in users only.
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Restrict page access to admins only.
 */
function require_admin() {
    if (!is_admin()) {
        header("Location: ../admin_login.php?error=unauthorized");
        exit();
    }
}

/**
 * Redirect user if they are already logged in.
 */
function redirect_if_logged_in() {
    if (is_admin()) {
        header("Location: admin/dashboard.php");
        exit();
    } elseif (is_logged_in()) {
        header("Location: home.php");
        exit();
    }
}
?>
