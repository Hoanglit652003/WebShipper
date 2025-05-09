<?php
session_start();

// Kiểm tra nếu đã đăng nhập
if(isset($_SESSION['admin_id'])) {
    // Nếu đã đăng nhập, chuyển hướng đến dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Nếu chưa đăng nhập, chuyển hướng đến trang login
    header("Location: login.php");
    exit();
}
?> 