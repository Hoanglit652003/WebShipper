<?php
// lichlamviec.php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
require 'header.php';

$actionMessage = '';
// Thêm mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $mysqli->prepare("INSERT INTO LichLamViec (Id_NhanVien, ngayLamViec, thoiGianBatDau, thoiGianKetThuc, ghiChu) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $_POST['Id_NhanVien'], $_POST['ngayLamViec'], $_POST['thoiGianBatDau'], $_POST['thoiGianKetThuc'], $_POST['ghiChu']);
    $stmt->execute();
    $actionMessage = "Thêm lịch làm việc thành công!";
}

// Xóa
if (isset($_GET['delete'])) {
    $stmt = $mysqli->prepare("DELETE FROM LichLamViec WHERE Id_LichLamViec = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    $actionMessage = "Đã xóa lịch làm việc!";
}

// Cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    // 1. Cập nhật lịch làm việc
    $stmt = $mysqli->prepare("UPDATE LichLamViec SET ngayLamViec=?, thoiGianBatDau=?, thoiGianKetThuc=?, ghiChu=? WHERE Id_LichLamViec=?");
    $stmt->bind_param("ssssi", $_POST['ngayLamViec'], $_POST['thoiGianBatDau'], $_POST['thoiGianKetThuc'], $_POST['ghiChu'], $_POST['Id_LichLamViec']);
    $stmt->execute();

    // 2. Lấy Id_NhanVien từ lịch làm việc vừa sửa
    $stmt = $mysqli->prepare("SELECT Id_NhanVien FROM LichLamViec WHERE Id_LichLamViec = ?");
    $stmt->bind_param("i", $_POST['Id_LichLamViec']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $idNhanVien = $row['Id_NhanVien'];

    // 3. Tạo thông báo kèm thời gian chi tiết
    $ngayTao = date("Y-m-d H:i:s");
    $ngayLam = $_POST['ngayLamViec'];
    $batDau = $_POST['thoiGianBatDau'];
    $ketThuc = $_POST['thoiGianKetThuc'];

    $noiDung = "🗓 Lịch làm việc ngày $ngayLam từ $batDau đến $ketThuc của bạn đã được cập nhật.";
    $trangThai = "Chưa đọc";

    $stmt = $mysqli->prepare("INSERT INTO ThongBao (Id_NhanVien, noiDung, trangThai) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $idNhanVien, $noiDung, $trangThai);
    $stmt->execute();

    $actionMessage = "Cập nhật thành công!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $tenNhanVien = $mysqli->real_escape_string($_POST['tenNhanVien']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $soDienThoai = $mysqli->real_escape_string($_POST['soDienThoai']);
    $diaChi = $mysqli->real_escape_string($_POST['diaChi']);
    $password = $mysqli->real_escape_string($_POST['password']);
    
    $stmt = $mysqli->prepare("INSERT INTO NhanVien (tenNhanVien, Email, soDienThoai, diaChi, phanQuyen, Password) VALUES (?, ?, ?, ?, 2, ?)");
    $stmt->bind_param("sssss", $tenNhanVien, $email, $soDienThoai, $diaChi, $password);
    
    if ($stmt->execute()) {
        $actionMessage = "Thêm nhân viên mới thành công!";
    } else {
        $actionMessage = "Có lỗi xảy ra khi thêm nhân viên!";
    }
}

// Xóa nhân viên
if (isset($_GET['delete_employee'])) {
    $id = (int)$_GET['delete_employee'];
    // Xóa lịch làm việc của nhân viên trước
    $stmt = $mysqli->prepare("DELETE FROM LichLamViec WHERE Id_NhanVien = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Sau đó xóa nhân viên
    $stmt = $mysqli->prepare("DELETE FROM NhanVien WHERE Id_nhanVien = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $actionMessage = "Đã xóa nhân viên thành công!";
    } else {
        $actionMessage = "Có lỗi xảy ra khi xóa nhân viên!";
    }
}

// Cập nhật thông tin nhân viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $id = (int)$_POST['Id_nhanVien'];
    $tenNhanVien = $mysqli->real_escape_string($_POST['tenNhanVien']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $soDienThoai = $mysqli->real_escape_string($_POST['soDienThoai']);
    $diaChi = $mysqli->real_escape_string($_POST['diaChi']);
    
    $stmt = $mysqli->prepare("UPDATE NhanVien SET tenNhanVien=?, Email=?, soDienThoai=?, diaChi=? WHERE Id_nhanVien=?");
    $stmt->bind_param("ssssi", $tenNhanVien, $email, $soDienThoai, $diaChi, $id);
    
    if ($stmt->execute()) {
        $actionMessage = "Cập nhật thông tin nhân viên thành công!";
    } else {
        $actionMessage = "Có lỗi xảy ra khi cập nhật thông tin!";
    }
}

// Danh sách nhân viên
$nhanvien = $mysqli->query("SELECT * FROM NhanVien ORDER BY tenNhanVien");

// Nếu chọn nhân viên
$lichlamviec = [];
$selectedStaff = null;
if (isset($_GET['staff_id'])) {
    $sid = (int)$_GET['staff_id'];
    $selectedStaff = $mysqli->query("SELECT * FROM NhanVien WHERE Id_nhanVien=$sid")->fetch_assoc();
    $lichlamviec = $mysqli->query("SELECT * FROM LichLamViec WHERE Id_NhanVien=$sid ORDER BY ngayLamViec DESC");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý lịch làm việc</title>
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
            min-height: 80vh;
            margin: 2rem auto; 
            padding: 2rem; 
            background: #fff; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        h2, h3 { 
            color: #2c3e50; 
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .section {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: #ff5722;
            color: white;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            text-align: center;
            font-size: 0.95rem;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 87, 34, 0.3);
            opacity: 0.9;
        }
        .btn-danger { 
            background: #e74c3c; 
        }
        .btn-danger:hover {
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        .btn-edit { 
            background: #f39c12; 
        }
        .btn-edit:hover {
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #ff5722;
            box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1);
            outline: none;
            background: #fff;
        }
        .alert {
            padding: 1rem 1.5rem;
            background: #dff0d8;
            color: #3c763d;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border: 1px solid #d6e9c6;
            font-size: 0.95rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: #fff;
            width: 90%;
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 15px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .close {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }
        .close:hover {
            color: #ff5722;
        }
        .employee-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .employee-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .employee-actions {
            display: flex;
            gap: 0.8rem;
        }
        .employee-actions .btn {
            flex: 1;
            padding: 0.7rem;
            font-size: 0.9rem;
        }
        .employee-name {
            font-size: 1.1rem;
            font-weight: 500;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        details {
            margin-top: 1rem;
        }
        details summary {
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.3s ease;
        }
        details summary:hover {
            background: #f8f9fa;
        }
        details[open] summary {
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1rem;
            }
            .grid {
                grid-template-columns: 1fr;
            }
            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
            }
            .employee-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Quản lý lịch làm việc nhân viên</h2>

    <?php if ($actionMessage): ?>
        <div class="alert"><?= $actionMessage ?></div>
    <?php endif; ?>

    <div class="section">
        <h3>Thêm nhân viên mới</h3>
        <button class="btn" onclick="document.getElementById('addEmployeeModal').style.display='block'">Thêm nhân viên mới</button>
    </div>

    <div class="section">
        <h3>Danh sách nhân viên</h3>
        <div class="grid">
            <?php while ($nv = $nhanvien->fetch_assoc()): ?>
                <div class="employee-card">
                    <div class="employee-name"><?= htmlspecialchars($nv['tenNhanVien']) ?></div>
                    <div class="employee-actions">
                        <button class="btn btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($nv)) ?>)">Sửa</button>
                        <a class="btn btn-danger" href="?delete_employee=<?= $nv['Id_nhanVien'] ?>" onclick="return confirm('Bạn có chắc muốn xóa nhân viên này?')">Xóa</a>
                    </div>
                    <a class="btn" href="?staff_id=<?= $nv['Id_nhanVien'] ?>">Xem lịch làm việc</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php if ($selectedStaff): ?>
        <div class="section">
            <h3>Lịch làm việc của: <?= htmlspecialchars($selectedStaff['tenNhanVien']) ?></h3>

            <form method="post" class="form-group">
                <input type="hidden" name="Id_NhanVien" value="<?= $selectedStaff['Id_nhanVien'] ?>">
                <div class="form-group">
                    <label>Ngày làm việc:</label>
                    <input type="date" name="ngayLamViec" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Bắt đầu:</label>
                    <input type="time" name="thoiGianBatDau" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kết thúc:</label>
                    <input type="time" name="thoiGianKetThuc" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Ghi chú:</label>
                    <textarea name="ghiChu" class="form-control"></textarea>
                </div>
                <button type="submit" name="add" class="btn">Thêm lịch</button>
            </form>

            <?php if ($lichlamviec && $lichlamviec->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Thời gian</th>
                            <th>Ghi chú</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $lichlamviec->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['ngayLamViec'] ?></td>
                            <td><?= $row['thoiGianBatDau'] ?> - <?= $row['thoiGianKetThuc'] ?></td>
                            <td><?= htmlspecialchars($row['ghiChu']) ?></td>
                            <td>
                                <a class="btn btn-danger" href="?staff_id=<?= $selectedStaff['Id_nhanVien'] ?>&delete=<?= $row['Id_LichLamViec'] ?>" onclick="return confirm('Xóa lịch này?')">Xóa</a>
                                <details>
                                    <summary class="btn btn-edit">Sửa</summary>
                                    <form method="post" class="form-group">
                                        <input type="hidden" name="Id_LichLamViec" value="<?= $row['Id_LichLamViec'] ?>">
                                        <div class="form-group">
                                            <label>Ngày:</label>
                                            <input type="date" name="ngayLamViec" value="<?= $row['ngayLamViec'] ?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Bắt đầu:</label>
                                            <input type="time" name="thoiGianBatDau" value="<?= $row['thoiGianBatDau'] ?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Kết thúc:</label>
                                            <input type="time" name="thoiGianKetThuc" value="<?= $row['thoiGianKetThuc'] ?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Ghi chú:</label>
                                            <input name="ghiChu" value="<?= htmlspecialchars($row['ghiChu']) ?>" class="form-control">
                                        </div>
                                        <button type="submit" name="edit" class="btn">Cập nhật</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Không có lịch làm việc nào.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal thêm nhân viên -->
<div id="addEmployeeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addEmployeeModal').style.display='none'">&times;</span>
        <h3>Thêm nhân viên mới</h3>
        <form method="post" class="form-group">
            <div class="form-group">
                <label>Tên nhân viên:</label>
                <input type="text" name="tenNhanVien" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="tel" name="soDienThoai" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Địa chỉ:</label>
                <textarea name="diaChi" class="form-control" required></textarea>
            </div>
            <button type="submit" name="add_employee" class="btn">Thêm nhân viên</button>
        </form>
    </div>
</div>

<!-- Modal sửa nhân viên -->
<div id="editEmployeeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editEmployeeModal').style.display='none'">&times;</span>
        <h3>Sửa thông tin nhân viên</h3>
        <form method="post" class="form-group">
            <input type="hidden" name="Id_nhanVien" id="edit_Id_nhanVien">
            <div class="form-group">
                <label>Tên nhân viên:</label>
                <input type="text" name="tenNhanVien" id="edit_tenNhanVien" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="tel" name="soDienThoai" id="edit_soDienThoai" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Địa chỉ:</label>
                <textarea name="diaChi" id="edit_diaChi" class="form-control" required></textarea>
            </div>
            <button type="submit" name="edit_employee" class="btn">Cập nhật</button>
        </form>
    </div>
</div>

<script>
function openEditModal(employee) {
    document.getElementById('edit_Id_nhanVien').value = employee.Id_nhanVien;
    document.getElementById('edit_tenNhanVien').value = employee.tenNhanVien;
    document.getElementById('edit_email').value = employee.Email;
    document.getElementById('edit_soDienThoai').value = employee.soDienThoai;
    document.getElementById('edit_diaChi').value = employee.diaChi;
    document.getElementById('editEmployeeModal').style.display = 'block';
}

// Đóng modal khi click bên ngoài
window.onclick = function(event) {
    if (event.target == document.getElementById('addEmployeeModal')) {
        document.getElementById('addEmployeeModal').style.display = "none";
    }
    if (event.target == document.getElementById('editEmployeeModal')) {
        document.getElementById('editEmployeeModal').style.display = "none";
    }
}
</script>

<?php require 'footer.php'; ?>
</body>
</html>
