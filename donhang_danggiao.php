<?php
require 'header1.php';
require 'db.php';
require 'vendor/autoload.php';    // Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Kiểm tra đăng nhập
$idNhanVien = $_SESSION['user_id'] ?? null;
if (!$idNhanVien) {
    header('Location: login.php');
    exit;
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maVanDon'])) {
    $maVanDon     = $_POST['maVanDon'];
    $trangThaiMoi = $_POST['trangThai'] ?? '';
    $idNhanVien   = $_SESSION['user_id'];

    // 1) Cập nhật trạng thái đơn hàng
    $stmt = $mysqli->prepare(
        "UPDATE DonHang 
         SET trangThaiDonHang = ? 
         WHERE maVanDon = ? 
           AND id_nhanVien = ?"
    );
    $stmt->bind_param("ssi", $trangThaiMoi, $maVanDon, $idNhanVien);

    if ($stmt->execute()) {
        // 2) Thêm thông báo cho nhân viên
        $noiDungTB = "Đơn hàng $maVanDon đã được cập nhật trạng thái: $trangThaiMoi";
        $stmtTB = $mysqli->prepare(
            "INSERT INTO ThongBao (Id_NhanVien, noiDung) 
             VALUES (?, ?)"
        );
        $stmtTB->bind_param("is", $idNhanVien, $noiDungTB);
        $stmtTB->execute();

        // 3) Lấy email và tên khách hàng
        $stmtEmail = $mysqli->prepare(
            "SELECT k.Email, k.tenKhachHang
             FROM DonHang d
             JOIN KhachHang k ON d.id_KhachHang = k.Id_KhachHang
             WHERE d.maVanDon = ?"
        );
        $stmtEmail->bind_param("s", $maVanDon);
        $stmtEmail->execute();
        $stmtEmail->bind_result($emailKH, $tenKH);
        $stmtEmail->fetch();
        $stmtEmail->close();

        // 4) Gửi email thông báo cho khách hàng bằng PHPMailer
        if (filter_var($emailKH, FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';            // thay SMTP của bạn nếu cần
                $mail->SMTPAuth   = true;
                $mail->Username   = 'hoanglit652003@gmail.com';      // email SMTP
                $mail->Password   = 'nhefbuicsvrrtnlz';         // app password hoặc mật khẩu SMTP
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('hoanglit652003@gmail.com', 'CÔNG TY FLYBEEMOVE CHUYỂN PHÁT NHANH TOÀN QUỐC');
                $mail->addAddress($emailKH, $tenKH);

                $mail->CharSet    = 'UTF-8';
                $mail->Encoding   = 'base64';       // mã hoá Base64 sẽ giữ nguyên dấu
                $mail->setLanguage('vi');  
                // Content
                $mail->isHTML(false);
                $mail->Subject = "Cập nhật trạng thái đơn hàng $maVanDon";
                $mail->Body    = "Kính gửi $tenKH,\n\n"
                               . "Đơn hàng của quý khách với mã vận đơn $maVanDon "
                               . "hiện đã được cập nhật trạng thái: $trangThaiMoi.\n\n"
                               . "Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!\n"
                               . "Trân trọng,\n"
                               . "🚚 CÔNG TY FLYBEEMOVE CHUYỂN PHÁT NHANH TOÀN QUỐC";

                $mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer Error ({$emailKH}): " . $mail->ErrorInfo);
                echo "<script>
                        alert('Không gửi được email: " . addslashes($mail->ErrorInfo) . "');
                      </script>";
            }
        }

        // 5) Thông báo trên trình duyệt và reload trang
        echo "<script>
                alert('Cập nhật trạng thái thành công và đã gửi email cho khách hàng!');
                window.location.href = window.location.pathname;
              </script>";
        exit;
    } else {
        echo "<script>
                alert('Lỗi cập nhật trạng thái: " . addslashes($stmt->error) . "');
              </script>";
    }
}

// Lấy danh sách đơn đang giao kèm thông tin chi tiết
$stmt = $mysqli->prepare(
    "SELECT D.maVanDon, D.ngayTaoDon, D.trangThaiDonHang,
            S.tenSanPham, N.tenNguoiNhan, N.diaChi, K.soDienThoai
     FROM DonHang D
     JOIN NguoiNhan N ON D.id_nguoiNhan = N.Id_NguoiNhan
     JOIN SanPham S ON D.id_sanPham = S.Id_SanPham
     JOIN KhachHang K ON D.id_KhachHang = K.Id_KhachHang
     WHERE D.id_nhanVien = ? AND D.trangThaiDonHang = 'Đang giao'
     ORDER BY D.ngayTaoDon DESC"
);
$stmt->bind_param("i", $idNhanVien);
$stmt->execute();
$orders = $stmt->get_result();
?>

<style>
.content-wrapper {
    min-height: 85vh;
    padding: 20px;
}
.card-orders {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    padding: 0 0 10px 0;
    margin: 0 auto;
    max-width: 98%;
}
.card-header {
    display: flex;
    align-items: center;
    padding: 20px 24px 0 24px;
    font-size: 1.5rem;
    font-weight: 600;
    color: #222;
    background: transparent;
    border-radius: 12px 12px 0 0;
    border-bottom: none;
}
.card-header i {
    font-size: 2rem;
    margin-right: 12px;
    color: #222;
}
.table-orders {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}
.table-orders th, .table-orders td {
    padding: 14px 18px;
    border-bottom: 1px solid #f0f0f0;
    text-align: left;
    font-size: 16px;
}
.table-orders th {
    background: #0dcaf0;
    font-weight: 600;
    color: #000;
}
.table-orders tr:last-child td {
    border-bottom: none;
}
.bold {
    font-weight: 700;
}
.status-label {
    display: inline-block;
    padding: 4px 16px;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 500;
    color: #ff9800;
    background: #fff4e5;
}
.btn {
    display: inline-flex;
    align-items: center;
    padding: 8px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 15px;
    text-decoration: none;
    margin: 2px;
    font-weight: 500;
    transition: background 0.2s;
}
.btn-primary {
    background-color: #1976d2;
    color: #fff;
}
.btn-primary i {
    margin-right: 6px;
}
.btn-primary:hover {
    background: #1256a3;
}
.btn-close { background: none; border: none; font-size: 20px; cursor: pointer; float: right; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
.modal-content { background: #fff; margin: 10% auto; padding: 20px; border-radius: 8px; max-width: 500px; position: relative; }
.modal-header { margin-bottom: 10px; }
.modal-body { margin-bottom: 20px; }
#trangThai {
    width: 180px;
    height: 30px;
    margin-top: 5px;
}

</style>

<div class="content-wrapper">
    <div class="card-orders">
        <div class="card-header">
            <i class="bi bi-truck"></i>
            Danh sách đơn hàng đang giao
            
        </div>
        <table class="table-orders">
            <tr>
                <th>Mã vận đơn</th>
                <th>Sản phẩm</th>
                <th>Người nhận</th>
                <th>Địa chỉ</th>
                <th>Ngày tạo</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            <?php while ($don = $orders->fetch_assoc()): ?>
            <tr>
                <td class="bold"><?= htmlspecialchars($don['maVanDon']) ?></td>
                <td><?= htmlspecialchars($don['tenSanPham']) ?></td>
                <td>
                    <?= htmlspecialchars($don['tenNguoiNhan']) ?><br>
                    <span style="color:#222;font-size:15px"><i class="bi bi-telephone"></i> <?= htmlspecialchars($don['soDienThoai']) ?></span>
                </td>
                <td><?= htmlspecialchars($don['diaChi']) ?></td>
                <td><?= htmlspecialchars($don['ngayTaoDon']) ?></td>
                <td><span class="status-label">Đang giao</span></td>
                <td>
                    <button
                        class="btn btn-primary btn-update"
                        data-ma="<?= htmlspecialchars($don['maVanDon']) ?>"
                        data-product="<?= htmlspecialchars($don['tenSanPham']) ?>"
                        data-recipient="<?= htmlspecialchars($don['tenNguoiNhan']) ?>"
                        data-phone="<?= htmlspecialchars($don['soDienThoai']) ?>"
                        data-address="<?= htmlspecialchars($don['diaChi']) ?>"
                        data-date="<?= htmlspecialchars($don['ngayTaoDon']) ?>"
                        data-status="<?= htmlspecialchars($don['trangThaiDonHang']) ?>"
                    ><i class="bi bi-pencil-square"></i> Cập nhật</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- Modal cập nhật và xem chi tiết -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <button class="btn-close" id="closeModal">&times;</button>
        <div class="modal-header"><h3>Chi tiết đơn hàng</h3></div>
        <div class="modal-body" id="modalBody">
            <!-- Nội dung sẽ được JS chèn vào -->
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('updateModal');
const modalBody = document.getElementById('modalBody');
const closeModal = document.getElementById('closeModal');

// Đóng modal
closeModal.addEventListener('click', () => modal.style.display = 'none');
window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

// Xử lý click Chi tiết & Cập nhật
document.querySelectorAll('.btn-update').forEach(btn => {
    btn.addEventListener('click', () => {
        const ma = btn.dataset.ma;
        const product = btn.dataset.product;
        const recipient = btn.dataset.recipient;
        const phone = btn.dataset.phone;
        const address = btn.dataset.address;
        const date = btn.dataset.date;
        const status = btn.dataset.status;
        
        // Tạo HTML chi tiết + form
        let html = `
            <ul>
                <li><strong>Mã vận đơn:</strong> ${ma}</li>
                <li><strong>Sản phẩm:</strong> ${product}</li>
                <li><strong>Người nhận:</strong> ${recipient}</li>
                <li><strong>Số điện thoại:</strong> <i class='bi bi-telephone'></i> ${phone}</li>
                <li><strong>Địa chỉ:</strong> ${address}</li>
                <li><strong>Ngày tạo:</strong> ${date}</li>
                <li><strong>Trạng thái hiện tại:</strong> ${status}</li>
            </ul>
            <hr>
            <form method="post">
                <input type="hidden" name="maVanDon" value="${ma}">
                <label for="trangThai">Chọn trạng thái mới:</label><br>
                <select name="trangThai" id="trangThai" required>
                    <option value="">-- Chọn --</option>
                    <option value="Đang giao">Đang giao</option>
                    <option value="Đã giao">Đã giao</option>
                    <option value="Giao không thành công">Giao không thành công</option>
                </select><br><br>
                <button type="submit" class="btn btn-success">Xác nhận</button>
            </form>
        `;
        modalBody.innerHTML = html;
        modal.style.display = 'block';
    });
});
</script>
<script>
        // Khởi tạo kết nối WebSocket đến server Node.js
        const socket = new WebSocket('ws://localhost:8081');

        socket.addEventListener('open', () => {
        console.log('WebSocket connected');
        });

        socket.addEventListener('error', (err) => {
        console.error('WebSocket error:', err);
        });

        // Gửi vị trí liên tục khi có thay đổi
        if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            pos => {
            const { latitude: lat, longitude: lng } = pos.coords;
            const payload = {
                staffId: <?= $idNhanVien ?>,
                timestamp: new Date().toISOString(),
                lat,
                lng
            };
            if (socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify(payload));
            }
            },
            err => {
            console.error('Error getting location:', err.code, err.message);
            },
            {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
            }
        );
        } else {
        console.error('Geolocation không được hỗ trợ');
        }
        socket.addEventListener('close', () => {
        console.log('Socket closed, retry in 3s');
        setTimeout(initWebSocket, 3000);
        });
</script>

<?php require 'footer.php'; ?>
