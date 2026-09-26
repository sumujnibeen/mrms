<?php
// manager/login.php
// Manager uses the main project login at root level.
// This file just redirects there so old links don't break.

$parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
array_pop($parts); // remove login.php
array_pop($parts); // remove manager/
$root = '/' . implode('/', array_filter($parts));
$root = rtrim($root, '/') . '/';
header("Location: " . $root . "login.php");
exit();
?>
