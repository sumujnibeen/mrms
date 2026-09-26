<?php
// auth.php
// Redirects to login if user is not logged in

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Redirect to project root login regardless of subfolder
    $sub = ['admin','manager','user','receptionist','sslcommerz'];
    $parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
    if (in_array(end($parts), $sub) || (count($parts) > 1 && in_array($parts[count($parts)-2], $sub))) {
        array_pop($parts); // remove filename
        if (in_array(end($parts), $sub)) array_pop($parts); // remove subfolder
    } else {
        array_pop($parts); // remove filename only
    }
    $root = '/' . implode('/', array_filter($parts));
    $root = rtrim($root, '/') . '/';
    header("Location: " . $root . "login.php");
    exit();
}
