<?php
// assign.php
require 'header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

$message = '';
$messageType = '';

// Xử lý phân công khi submit form
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['orders']) &&
    !empty($_POST['staff_id'])
) {
    $staffId = (int)$_POST['staff_id'];
    $orders  = $_POST['orders'];

    $mysqli->begin_transaction();
    try {
        $stmtUpdate = $mysqli->prepare(
            "UPDATE DonHang 
             SET id_nhanVien = ?, trangThaiDonHang = 'Đã phân công' 
             WHERE maVanDon = ?"
        );
        $stmtNotify = $mysqli->prepare(
            "INSERT INTO ThongBao (Id_NhanVien, noiDung) VALUES (?, ?)"
        );

        foreach ($orders as $md) {
            $stmtUpdate->bind_param('is', $staffId, $md);
            $stmtUpdate->execute();

            $msgNotify = "Bạn được phân công đơn hàng $md";
            $stmtNotify->bind_param('is', $staffId, $msgNotify);
            $stmtNotify->execute();
        }
        $mysqli->commit();
        $message = 'Phân công thành công!';
        $messageType = 'success';
    } catch (Exception $e) {
        $mysqli->rollback();
        $message = 'Lỗi: Không thể cập nhật. Vui lòng thử lại!';
        $messageType = 'error';
    }
}

// Lấy đơn chờ phân công
$sql = "
    SELECT D.maVanDon,
           N.diaChi    AS diaChiNhan,
           S.tenSanPham AS loaiHang,
           D.ngayTaoDon
    FROM DonHang D
    JOIN NguoiNhan N ON D.id_nguoiNhan = N.Id_NguoiNhan
    JOIN SanPham S   ON D.id_sanPham  = S.Id_SanPham
    WHERE D.trangThaiDonHang = 'Chờ phân công'
    ORDER BY D.ngayTaoDon DESC
";
$result = $mysqli->query($sql);

// Lấy danh sách nhân viên giao hàng và thống kê
$staffs = $mysqli->query(
    "SELECT Id_nhanVien, tenNhanVien, viTri FROM NhanVien WHERE viTri = 'Giao hàng'"
);
$staffList = [];
$staffStats = [];
while ($s = $staffs->fetch_assoc()) {
    $sid = $s['Id_nhanVien'];
    // Đếm pending
    $st1 = $mysqli->prepare("SELECT COUNT(*) FROM DonHang WHERE id_nhanVien = ? AND trangThaiDonHang != 'Đã giao'");
    $st1->bind_param('i', $sid);
    $st1->execute(); $st1->bind_result($pending); $st1->fetch(); $st1->close();
    // Đếm done
    $st2 = $mysqli->prepare("SELECT COUNT(*) FROM DonHang WHERE id_nhanVien = ? AND trangThaiDonHang = 'Đã giao'");
    $st2->bind_param('i', $sid);
    $st2->execute(); $st2->bind_result($done); $st2->fetch(); $st2->close();
    $perf = ($done + $pending) > 0 ? round($done/($done+$pending)*100,1) : 0;
    
    $staffList[] = $s;
    $staffStats[$sid] = [
        'name'        => $s['tenNhanVien'],
        'position'    => $s['viTri'],
        'pending'     => $pending,
        'done'        => $done,
        'performance' => $perf
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Phân công đơn hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    .content-container { 
      max-width: 1200px; 
      margin: 2rem auto; 
      padding: 2rem; 
      background: #fff; 
      border-radius: 15px; 
      box-shadow: 0 8px 30px rgba(0,0,0,0.1); 
      min-height: 85vh; 
    }
    .content-container h2 { 
      margin-bottom: 1.5rem; 
      color: #2c3e50; 
      font-size: 2rem;
      font-weight: 600;
      border-bottom: 2px solid #0dcaf0;
      padding-bottom: 0.5rem;
    }
    .no-orders { 
      text-align: center; 
      margin: 3rem 0; 
      font-size: 1.25rem; 
      color: #666;
      padding: 2rem;
      background: #f8f9fa;
      border-radius: 10px;
    }
    table { 
      width: 100%; 
      border-collapse: separate;
      border-spacing: 0;
      margin-bottom: 2rem;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0,0,0,0.05);
    }
    th, td { 
      padding: 1rem 1.5rem; 
      border-bottom: 1px solid #e0e0e0; 
      text-align: left; 
    }
    th { 
      background: #0dcaf0; 
      color: #fff; 
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.9rem;
      letter-spacing: 0.5px;
    }
    tr:nth-child(even) { background: #f8f9fa; }
    tr:hover { background: #f1f9ff; }
    .form-group {
      margin-bottom: 1.5rem;
      padding: 1.5rem;
      background: #f8f9fa;
      border-radius: 10px;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #2c3e50;
    }
    select, button { 
      padding: 0.8rem 1.2rem; 
      border: 2px solid #e0e0e0; 
      border-radius: 8px; 
      font-size: 1rem; 
      outline: none;
      transition: all 0.3s ease;
    }
    select { 
      min-width: 300px; 
      background: #fff;
    }
    select:focus { 
      border-color: #0dcaf0; 
      box-shadow: 0 0 0 3px rgba(13,202,240,0.1); 
    }
    button { 
      background: #0dcaf0; 
      color: #fff; 
      border: none;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    button:hover { 
      background: #0bb9dd;
      transform: translateY(-1px);
    }
    button:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }
    #detailLink { 
      margin-left: 1rem; 
      font-size: 0.95rem; 
      color: #0dcaf0;
      text-decoration: none;
      font-weight: 500;
    }
    #detailLink:hover { 
      color: #0bb9dd;
      text-decoration: underline;
    }
    .checkbox-wrapper {
      position: relative;
      width: 20px;
      height: 20px;
    }
    .checkbox-wrapper input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }
    .order-info {
      display: flex;
      gap: 1rem;
      align-items: center;
    }
    .order-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
    }
    .badge-new {
      background: #e3f2fd;
      color: #1976d2;
    }
    .badge-urgent {
      background: #fbe9e7;
      color: #d84315;
    }
    /* Loading Spinner */
    .spinner {
      display: none;
      width: 20px;
      height: 20px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #0dcaf0;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    /* Enhanced Modals */
    .modal { 
      display: none; 
      position: fixed; 
      z-index: 1000; 
      left: 0; 
      top: 0; 
      width: 100%; 
      height: 100%; 
      background-color: rgba(0,0,0,0.5); 
      backdrop-filter: blur(5px);
    }
    .modal-content { 
      background: #fff; 
      margin: 5% auto; 
      padding: 2rem; 
      border-radius: 15px; 
      max-width: 500px; 
      position: relative;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      animation: modalSlideIn 0.3s ease;
    }
    @keyframes modalSlideIn {
      from { transform: translateY(-20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    .modal-header { 
      margin-bottom: 1.5rem; 
      font-size: 1.5rem; 
      color: #2c3e50;
      font-weight: 600;
    }
    .modal-body { 
      font-size: 1.1rem;
      line-height: 1.6;
    }
    .modal-body ul { 
      list-style: none; 
      padding: 0;
      margin: 0;
    }
    .modal-body li { 
      margin: 1rem 0;
      padding: 0.5rem 0;
      border-bottom: 1px solid #eee;
    }
    .modal-body li:last-child {
      border-bottom: none;
    }
    .close { 
      position: absolute; 
      right: 1.5rem; 
      top: 1rem; 
      font-size: 1.8rem; 
      cursor: pointer; 
      color: #666;
      transition: color 0.3s;
    }
    .close:hover { 
      color: #000; 
    }
    .modal-content.success { 
      border-top: 5px solid #2ecc71; 
    }
    .modal-content.error { 
      border-top: 5px solid #e74c3c; 
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-top: 1rem;
    }
    .stat-item {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
      text-align: center;
    }
    .stat-value {
      font-size: 1.5rem;
      font-weight: 600;
      color: #0dcaf0;
      margin: 0.5rem 0;
    }
    .stat-label {
      color: #666;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<div class="content-container">
  <h2>Đơn hàng chờ phân công</h2>
  <form id="assignForm" method="post">
    <?php if ($result->num_rows === 0): ?>
      <p class="no-orders">Không có đơn hàng nào.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Chọn</th><th>Mã đơn</th><th>Địa chỉ</th><th>Loại hàng</th><th>Ngày tạo</th></tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><input type="checkbox" name="orders[]" value="<?= htmlspecialchars($row['maVanDon']) ?>"></td>
          <td><?= htmlspecialchars($row['maVanDon']) ?></td>
          <td><?= htmlspecialchars($row['diaChiNhan']) ?></td>
          <td><?= htmlspecialchars($row['loaiHang']) ?></td>
          <td><?= date('d/m/Y', strtotime($row['ngayTaoDon'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php endif; ?>
    <div class="form-group">
      <label for="staffSelect">Phân công cho:</label>
      <select name="staff_id" id="staffSelect" required>
        <option value="">-- Chọn nhân viên giao hàng --</option>
        <?php foreach ($staffList as $s): ?>
          <option value="<?= $s['Id_nhanVien'] ?>">
            <?= htmlspecialchars($s['tenNhanVien']) ?> 
            (<?= $staffStats[$s['Id_nhanVien']]['pending'] ?> đơn đang xử lý)
          </option>
        <?php endforeach; ?>
      </select>
      <a id="detailLink" href="#" style="display:none;">Xem chi tiết nhân viên</a>
    </div>
    <button type="submit" id="assignButton">
      <span class="spinner" id="submitSpinner"></span>
      Phân công đơn hàng
    </button>
  </form>
</div>

<!-- Assignment Result Modal -->
<?php if ($message): ?>
<div id="assignModal" class="modal">
  <div class="modal-content <?= $messageType ?>">
    <span class="close" id="closeAssign">&times;</span>
    <p><?= htmlspecialchars($message) ?></p>
  </div>
</div>
<?php endif; ?>

<!-- Staff Detail Modal -->
<div id="staffModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeStaff">&times;</span>
    <div class="modal-header">Chi tiết nhân viên</div>
    <div class="modal-body" id="staffModalBody"></div>
  </div>
</div>

<script>
// Pass PHP stats to JS
const staffStats = <?= json_encode($staffStats) ?>;

// Elements
const staffSelect = document.getElementById('staffSelect');
const detailLink = document.getElementById('detailLink');
const staffModal = document.getElementById('staffModal');
const staffBody = document.getElementById('staffModalBody');
const closeStaff = document.getElementById('closeStaff');
const assignButton = document.getElementById('assignButton');
const submitSpinner = document.getElementById('submitSpinner');

// Show detail link on select change
staffSelect.addEventListener('change', function() {
  if (this.value) {
    detailLink.style.display = 'inline';
  } else {
    detailLink.style.display = 'none';
  }
});

// Open staff detail modal
detailLink.addEventListener('click', function(e) {
  e.preventDefault();
  const sid = staffSelect.value;
  const st = staffStats[sid];
  if (st) {
    staffBody.innerHTML = `
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-label">Đang xử lý</div>
          <div class="stat-value">${st.pending}</div>
        </div>
        <div class="stat-item">
          <div class="stat-label">Đã giao</div>
          <div class="stat-value">${st.done}</div>
        </div>
        <div class="stat-item">
          <div class="stat-label">Hiệu suất</div>
          <div class="stat-value">${st.performance}%</div>
        </div>
      </div>
      <ul>
        <li><strong>Tên:</strong> ${st.name}</li>
        <li><strong>Vị trí:</strong> ${st.position}</li>
      </ul>
    `;
    staffModal.style.display = 'block';
  }
});

// Close staff modal
closeStaff.onclick = () => staffModal.style.display = 'none';
window.addEventListener('click', e => { if (e.target === staffModal) staffModal.style.display = 'none'; });

// Assignment result modal
const assignModal = document.getElementById('assignModal');
const closeAssign = document.getElementById('closeAssign');
if (assignModal) {
  assignModal.style.display = 'block';
  closeAssign.onclick = () => assignModal.style.display = 'none';
  window.onclick = e => { if (e.target === assignModal) assignModal.style.display = 'none'; };
}

// Form validation and submission
document.getElementById('assignForm').addEventListener('submit', function(e) {
  const checkedOrders = document.querySelectorAll('input[name="orders[]"]:checked');
  if (checkedOrders.length === 0) {
    e.preventDefault();
    alert('Vui lòng chọn ít nhất một đơn hàng để phân công!');
    return;
  }

  if (!confirm(`Bạn có chắc chắn muốn phân công ${checkedOrders.length} đơn hàng cho nhân viên này?`)) {
    e.preventDefault();
    return;
  }

  // Show loading state
  assignButton.disabled = true;
  submitSpinner.style.display = 'inline-block';
  assignButton.innerHTML = '<span class="spinner" id="submitSpinner"></span> Đang xử lý...';
});
</script>

<?php require 'footer.php'; ?>
