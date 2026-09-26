<?php
// manager/config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use the project's shared DB connection
include '../db.php';

// Auth: only manager (and admin) can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['manager', 'admin'])) {
    // Redirect to project root login
    $parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
    if (in_array(end($parts), ['manager','admin','user','receptionist'])) array_pop($parts);
    elseif (count($parts) > 1) array_pop($parts);
    $root = '/' . implode('/', array_filter($parts));
    $root = rtrim($root, '/') . '/';
    header("Location: " . $root . "login.php");
    exit();
}
?>
