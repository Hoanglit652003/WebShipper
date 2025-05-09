<?php
// session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}
$userId = $_SESSION['user_id'];

// 1) Đánh dấu đã đọc
$sqlUpd = "UPDATE thongbao 
           SET trangThai = 'Đã đọc' 
           WHERE Id_NhanVien = $userId 
             AND trangThai = 'Chưa đọc'";
$mysqli->query($sqlUpd);

// 2) Lấy danh sách thông báo (mới nhất lên đầu)
$sql = "SELECT Id_ThongBao, noiDung, ngayTao, trangThai 
        FROM thongbao 
        WHERE Id_NhanVien = $userId 
        ORDER BY ngayTao DESC
        LIMIT 20";
$res = $mysqli->query($sql);

$notifs = [];
while($r = $res->fetch_assoc()) {
    // bạn có thể format lại ngày ở đây nếu muốn
    $notifs[] = $r;
}

header('Content-Type: application/json');
echo json_encode($notifs);
exit;
