<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Lấy số thông báo chưa đọc
$userId = $_SESSION['user_id'];
$sqlCount = "SELECT COUNT(*) AS cnt FROM thongbao 
             WHERE Id_NhanVien = $userId AND trangThai = 'Chưa đọc'";
$resCount = $mysqli->query($sqlCount);
$rowCount = $resCount->fetch_assoc();
$unreadCount = (int)$rowCount['cnt'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý giao hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary-color: #ff5722;
      --primary-dark: #e64a19;
      --sidebar-bg: #111212;
      --text-light: #ecf0f1;
      --text-dark: #2c3e50;
      --border-color: rgba(255,255,255,0.1);
    }

    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }

    body {
      font-family: 'Roboto', sans-serif;
      display: flex;
      min-height: 100vh;
      background: #f4f6f8;
    }

    /* Topbar */
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 64px;
      background: var(--primary-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1.5rem;
      color: var(--text-light);
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      z-index: 100;
    }

    .topbar .logo {
      font-size: 1.5rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .topbar .logo i {
      font-size: 1.75rem;
    }

    .topbar .actions {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .topbar .notification {
      position: relative;
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 50%;
      transition: background-color 0.2s;
    }

    .topbar .notification:hover {
      background: rgba(255,255,255,0.1);
    }

    .topbar .bell-icon {
      font-size: 1.4rem;
      color: var(--text-light);
    }

    .topbar .count {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #e74c3c;
      color: white;
      border-radius: 50%;
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
      font-weight: 600;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .topbar .logout {
      text-decoration: none;
      color: var(--text-light);
      font-weight: 500;
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .topbar .logout:hover {
      background: rgba(255,255,255,0.1);
      color: var(--text-light);
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      padding-top: 64px;
      background: var(--sidebar-bg);
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 8px rgba(0,0,0,0.15);
    }

    .sidebar a {
      color: var(--text-light);
      padding: 1rem 1.5rem;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: all 0.2s;
      border-left: 3px solid transparent;
      font-weight: 500;
    }

    .sidebar a:hover {
      background: rgba(255,255,255,0.05);
      border-left-color: var(--primary-color);
      color: var(--primary-color);
    }

    .sidebar a i {
      font-size: 1.25rem;
      width: 24px;
      text-align: center;
    }

    /* Main Content */
    .main-content {
      margin: 64px 0 0 260px;
      padding: 2rem;
      width: calc(100% - 260px);
    }

    /* Notification Dropdown */
    .notification .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      background: white;
      width: 320px;
      max-height: 400px;
      overflow-y: auto;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      border-radius: 0.5rem;
      margin-top: 0.75rem;
    }

    .notification .dropdown-content .item {
      padding: 1rem;
      border-bottom: 1px solid #eee;
      color: var(--text-dark);
    }

    .notification .dropdown-content .item:last-child {
      border-bottom: none;
    }

    .notification .dropdown-content .item p {
      margin-bottom: 0.25rem;
      font-weight: 500;
    }

    .notification .dropdown-content .item small {
      color: #666;
      font-size: 0.875rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .sidebar {
        width: 0;
        transform: translateX(-100%);
        transition: all 0.3s;
      }

      .sidebar.active {
        width: 260px;
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
        width: 100%;
      }

      .topbar .logo span {
        display: none;
      }
    }
  </style>
</head>
<body>
  <!-- Topbar -->
  <header class="topbar">
    <div class="logo">
      <i class="bi bi-truck"></i>
      <span>NHÂN VIÊN GIAO HÀNG</span>
    </div>
    <div class="actions">
      <?php if ($_SESSION['role'] === 2): ?>
        <div class="notification" id="notif">
          <i class="bi bi-bell bell-icon"></i>
          <?php if($unreadCount > 0): ?>
            <span class="count"><?php echo $unreadCount; ?></span>
          <?php endif; ?>
          <div id="notif-dropdown" class="dropdown-content"></div>
        </div>
      <?php endif; ?>
      <a href="logout.php" class="logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Đăng xuất</span>
      </a>
    </div>
  </header>

  <!-- Sidebar -->
  <aside class="sidebar">
    <?php if ($_SESSION['role'] === 1): ?>
      
    <?php else: ?>
      <a href="my_orders.php">
        <i class="bi bi-box"></i>
        <span>Đơn hàng của tôi</span>
      </a>
      <a href="donhang_danggiao.php">
        <i class="bi bi-truck"></i>
        <span>Đơn hàng đang giao</span>
      </a>
      <a href="lichlamviec_nv.php">
        <i class="bi bi-calendar3"></i>
        <span>Xem lịch làm việc</span>
      </a>
    <?php endif; ?>
  </aside>

  <!-- Main Content -->
  <main class="main-content">

  <script>
  document.getElementById('notif').addEventListener('click', function(e) {
    e.stopPropagation();
    const dd = document.getElementById('notif-dropdown');
    if (dd.style.display === 'block') {
      dd.style.display = 'none';
      return;
    }
    fetch('notifications.php')
      .then(res => res.json())
      .then(data => {
        dd.innerHTML = '';
        if (data.length === 0) {
          dd.innerHTML = '<div class="item">Không có thông báo mới</div>';
        } else {
          data.forEach(n => {
            dd.innerHTML += `<div class="item">
                               <p>${n.noiDung}</p>
                               <small>${n.ngayTao}</small>
                             </div>`;
          });
        }
        dd.style.display = 'block';
        const cnt = document.querySelector('.notification .count');
        if (cnt) cnt.remove();
      })
      .catch(console.error);
  });
  window.addEventListener('click', function(e) {
    const dd = document.getElementById('notif-dropdown');
    if (dd.style.display === 'block' && !dd.contains(e.target)) {
      dd.style.display = 'none';
    }
  });
</script>
