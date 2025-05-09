<?php
// lichlamviec_nv.php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
require 'header1.php';

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 2) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Lấy lịch làm việc của nhân viên
$stmt = $mysqli->prepare("
    SELECT * FROM LichLamViec 
    WHERE Id_NhanVien = ? 
    ORDER BY ngayLamViec DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch làm việc của tôi</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Roboto', sans-serif; 
            background: #f4f4f4; 
            margin: 0; 
            padding: 0; 
        }
        .container { 
            max-width: 1200px; 
            margin: 2rem auto; 
            padding: 2rem; 
            background: #fff; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        h2 { 
            color: #2c3e50; 
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .schedule-table th,
        .schedule-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .schedule-table th {
            background: #0dcaf0;
            font-weight: 500;
        }
        .schedule-table tr:hover {
            background: #f8f9fa;
        }
        .no-schedule {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        .date {
            font-weight: 500;
            color: #2c3e50;
        }
        .time {
            color: #000;
        }
        .note {
            color: #000;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Lịch làm việc của tôi</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Thời gian</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="date"><?= date('d/m/Y', strtotime($row['ngayLamViec'])) ?></td>
                            <td class="time"><?= $row['thoiGianBatDau'] ?> - <?= $row['thoiGianKetThuc'] ?></td>
                            <td class="note"><?= htmlspecialchars($row['ghiChu']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-schedule">
                <p>Bạn chưa có lịch làm việc nào.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php require 'footer.php'; ?> 