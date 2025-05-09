<?php
require 'header.php';
if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}
$id = (int)$_GET['id'];

// Thống kê đơn hàng
// Đơn chưa giao
$stmt1 = $mysqli->prepare(
    "SELECT COUNT(*) FROM DonHang 
     WHERE id_nhanVien = ? AND trangThaiDonHang != 'Đã giao'"
);
$stmt1->bind_param('i', $id);
$stmt1->execute();
$stmt1->bind_result($pending);
$stmt1->fetch();
$stmt1->close();

// Đơn đã giao
$stmt2 = $mysqli->prepare(
    "SELECT COUNT(*) FROM DonHang 
     WHERE id_nhanVien = ? AND trangThaiDonHang = 'Đã giao'"
);
$stmt2->bind_param('i', $id);
$stmt2->execute();
$stmt2->bind_result($done);
$stmt2->fetch();
$stmt2->close();

$perf = ($done + $pending) > 0
      ? round($done / ($done + $pending) * 100, 1)
      : 0;

// Thông tin nhân viên
$stmt3 = $mysqli->prepare(
    "SELECT tenNhanVien, viTri FROM NhanVien WHERE Id_nhanVien = ?"
);
$stmt3->bind_param('i', $id);
$stmt3->execute();
$stmt3->bind_result($name, $position);
$stmt3->fetch();
$stmt3->close();
?>
<h2>Chi tiết nhân viên: <?= htmlspecialchars($name) ?></h2>
<p>Khu vực phụ trách: <?= htmlspecialchars($position) ?></p>
<p>Đơn đang xử lý: <?= $pending ?></p>
<p>Hiệu suất: <?= $perf ?>%</p>
<p><a href="orders.php">Quay lại</a></p>
<?php require 'footer.php'; ?>