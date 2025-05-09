<?php
session_start();
require 'db.php';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $mysqli->prepare(
        "SELECT Id_nhanVien, Password, phanQuyen 
         FROM NhanVien WHERE Email = ?"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $role);
    if ($stmt->fetch() && $password === $hash) {
        $_SESSION['user_id'] = $id;
        $_SESSION['role']    = $role;
        if($role === 1)
            header('Location: dashboard.php');
        else
        header('Location: my_orders.php');
        exit;
    }
    $error = 'Email hoặc mật khẩu không đúng';
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - FlybeeMove</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #ff5722 0%, #ff9800 50%, #ff5722 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255,255,255,0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.15) 0%, transparent 50%),
                linear-gradient(45deg, rgba(255,255,255,0.1) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.1) 75%, transparent 75%, transparent);
            background-size: 100% 100%, 100% 100%, 60px 60px;
            animation: rotate 30s linear infinite;
            opacity: 0.8;
        }

        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,87,34,0.2) 0%, rgba(255,152,0,0.2) 100%);
            backdrop-filter: blur(5px);
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2),
                        0 0 0 1px rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1;
        }

        .company-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .company-logo img {
            max-width: 200px;
            height: auto;
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .company-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }

        .login-title {
            font-size: 28px;
            font-weight: 500;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #666;
            font-size: 18px;
        }

        .input-group input:focus {
            outline: none;
            border-color: #ff5722;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(255, 87, 34, 0.1);
        }

        .input-group input::placeholder {
            color: #999;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(45deg, #ff5722, #ff9800);
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 87, 34, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error i {
            font-size: 16px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .company-logo img {
                max-width: 180px;
            }

            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="company-logo">
            <img src="../admin/img/admin1.png" alt="FlybeeMove Logo">
            <div class="company-name">CÔNG TY FLYBEEMOVE</div>
            <div class="company-subtitle">ADMIN - NHÂN VIÊN</div>
        </div>

        <h2 class="login-title">Đăng nhập</h2>

        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="input-group">
                <label for="email">Email</label>
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" required placeholder="Nhập email của bạn">
            </div>
            <div class="input-group">
                <label for="password">Mật khẩu</label>
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu của bạn">
            </div>
            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
