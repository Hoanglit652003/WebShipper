<?php
// db.php - kết nối DB và session
$host   = 'localhost';
$user   = 'root';
$pass   = '';
$db     = 'quanly_giaohang';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die('Connection error: ' . $mysqli->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>