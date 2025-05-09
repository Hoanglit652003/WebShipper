<?php
require 'header.php';
// session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$maVanDon = $_GET['maVanDon'] ?? '';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Lấy chi tiết đơn hàng
$stmt = $mysqli->prepare("
    SELECT D.maVanDon, S.tenSanPham, N.diaChi, D.trangThaiDonHang, D.ngayTaoDon, 
           NV.tenNhanVien, NV.viTriLat, NV.viTriLng, D.id_nhanVien
    FROM DonHang D
    JOIN NguoiNhan N ON D.id_nguoiNhan = N.Id_NguoiNhan
    JOIN SanPham S ON D.id_sanPham = S.Id_SanPham
    LEFT JOIN NhanVien NV ON D.id_nhanVien = NV.Id_nhanVien
    WHERE D.maVanDon = ? AND (D.id_KhachHang = ? OR D.id_nhanVien = ?)
");
$stmt->bind_param("sii", $maVanDon, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<p>Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn này.</p>";
    require 'footer.php';
    exit;
}
?>

<div class="content-container">
    <h2>Chi tiết đơn hàng: <?= htmlspecialchars($order['maVanDon']) ?></h2>
    <div class="order-info">
        <ul>
            <li>Địa chỉ: <?= htmlspecialchars($order['diaChi']) ?></li>
            <li>Loại hàng: <?= htmlspecialchars($order['tenSanPham']) ?></li>
            <li>Ngày tạo: <?= $order['ngayTaoDon'] ?></li>
            <li>Trạng thái: <?= htmlspecialchars($order['trangThaiDonHang']) ?></li>
            <?php if ($order['tenNhanVien']): ?>
                <li>Shipper: <?= htmlspecialchars($order['tenNhanVien']) ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <?php if ($userRole === 2 && $order['trangThaiDonHang'] === 'Đã phân công'): ?>
        <form method="post">
            <button type="submit" name="start_delivery" class="btn btn-primary">Bắt đầu giao hàng</button>
        </form>
    <?php endif; ?>

    <?php if ($order['trangThaiDonHang'] === 'Đang giao' && $order['id_nhanVien']): ?>
        <a href="shipper_route.php?maVanDon=<?= urlencode($maVanDon) ?>" class="btn btn-info">
            <i class="fas fa-map-marker-alt"></i> Xem đường đi của shipper
        </a>
    <?php endif; ?>
</div>

<?php
// Xử lý cập nhật trạng thái nếu nhấn "Bắt đầu giao hàng"
if (isset($_POST['start_delivery']) && $userRole === 2) {
    $updateStmt = $mysqli->prepare("UPDATE DonHang SET trangThaiDonHang = 'Đang giao' WHERE maVanDon = ? AND id_nhanVien = ?");
    $updateStmt->bind_param("si", $maVanDon, $userId);
    if ($updateStmt->execute()) {
        echo "<p style='color: green;'>Đã cập nhật trạng thái đơn hàng thành 'Đang giao'.</p>";
        echo "<script>setTimeout(() => window.location.href = 'my_orders.php', 1500);</script>";
    } else {
        echo "<p style='color: red;'>Không thể cập nhật trạng thái đơn hàng. Vui lòng thử lại.</p>";
    }
}
?>

<style>
.content-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 1.5rem;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.order-info {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
}
.order-info ul {
    list-style: none;
    padding: 0;
}
.order-info li {
    margin: 0.5rem 0;
    padding: 0.5rem;
    border-bottom: 1px solid #eee;
}
.order-info li:last-child {
    border-bottom: none;
}
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    color: #fff;
    border: none;
    cursor: pointer;
    margin: 0.5rem 0;
    font-weight: 500;
    transition: all 0.3s ease;
}
.btn i {
    margin-right: 0.5rem;
}
.btn-primary {
    background: #007bff;
}
.btn-info {
    background: #17a2b8;
}
.btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.btn:active {
    transform: translateY(0);
}
</style>

<?php require 'footer.php'; ?>
