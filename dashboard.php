<?php
// thongke.php

// 1. Include header (đảm bảo đã load Bootstrap & Bootstrap Icons)
include 'header.php';
?>

<!-- 2. Custom CSS cho dashboard -->
<style>
  /* Toàn trang */
  .container-fluid {
    font-family: 'Poppins', sans-serif;
  }
  h2 {
    font-weight: 600;
  }
  .text-muted {
    color: #888 !important;
  }

  /* Cards thống kê */
  .dashboard-stats .card {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: transform .2s ease, box-shadow .2s ease;
    overflow: hidden;
  }
  .dashboard-stats .card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 20px rgba(0,0,0,0.15);
  }
  .dashboard-stats .card .card-body {
    display: flex;
    align-items: center;
  }
  .dashboard-stats .card .card-body i {
    font-size: 2rem;
    opacity: .85;
    margin-right: 1rem;
  }
  .dashboard-stats .card .card-body h3 {
    font-size: 1.75rem;
    margin: 0;
  }
  .dashboard-stats .card .card-body small {
    opacity: .8;
  }

  /* Màu nền từng loại thống kê */
  .stat-orders     { background: #4aa8f2; }
  .stat-customers  { background: #28c76f; }
  .stat-employees  { background: #f4d03f; }
  .stat-revenue    { background: #ea5455; }

  /* Card đơn mới 7 ngày */
  .stat-new-orders {
    background: #ffffff;
    border-left: 5px solid #4aa8f2;
  }
  .stat-new-orders .card-body {
    flex-direction: column;
    align-items: flex-start;
  }
  .stat-new-orders .card-title {
    color: #4aa8f2;
    font-weight: 600;
  }
  .stat-new-orders .card-text {
    font-size: 2rem;
    margin-top: .5rem;
    color: #333;
  }

  /* Card biểu đồ */
  .chart-card .card-header {
    background: #f7f7f7;
    font-weight: 600;
  }
</style>

<?php
// 3. Kết nối database
$host     = 'localhost';
$db       = 'quanly_giaohang';
$user     = 'root';
$pass     = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "<div class='alert alert-danger'>Kết nối DB thất bại: " . $e->getMessage() . "</div>";
    exit;
}

// 4. Các truy vấn thống kê
$totalOrders    = $pdo->query("SELECT COUNT(*) FROM DonHang")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM KhachHang")->fetchColumn();
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM NhanVien")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(tongTien),0) FROM DoanhThu")->fetchColumn();

$newOrdersStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM DonHang 
    WHERE ngayTaoDon >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$newOrdersStmt->execute();
$newOrders = $newOrdersStmt->fetchColumn();

$revenueDataStmt = $pdo->query("
    SELECT ngayTinh, SUM(tongTien) AS doanhThu 
    FROM DoanhThu
    WHERE ngayTinh >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY ngayTinh
    ORDER BY ngayTinh ASC
");
$labels = [];
$data   = [];
while ($row = $revenueDataStmt->fetch()) {
    $labels[] = $row['ngayTinh'];
    $data[]   = $row['doanhThu'];
}
?>

<div class="container-fluid py-4">
  <!-- Tiêu đề -->
  <div class="row" style="margin-bottom: 20px;">
    <div class="col">
      <h2>Dashboard</h2>
      <small class="text-muted">Tổng quan nhanh</small>
    </div>
  </div>

  <!-- Cards số liệu chính -->
  <div class="row dashboard-stats mb-4">
    <div class="col-md-3 mb-3">
      <div class="card text-white stat-orders h-100">
        <div class="card-body">
          <i class="bi bi-receipt-cutoff"></i>
          <div>
            <h3><?= number_format($totalOrders) ?></h3>
            <small>Số đơn hàng</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white stat-customers h-100">
        <div class="card-body">
          <i class="bi bi-people"></i>
          <div>
            <h3><?= number_format($totalCustomers) ?></h3>
            <small>Khách hàng</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white stat-employees h-100">
        <div class="card-body">
          <i class="bi bi-person-badge"></i>
          <div>
            <h3><?= number_format($totalEmployees) ?></h3>
            <small>Nhân viên</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white stat-revenue h-100">
        <div class="card-body">
          <i class="bi bi-currency-dollar"></i>
          <div>
            <h3><?= number_format($totalRevenue, 0) ?>₫</h3>
            <small>Tổng doanh thu</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Card đơn mới 7 ngày -->
  <div class="row mb-5">
    <div class="col-md-4">
      <div class="card stat-new-orders h-100">
        <div class="card-body">
          <h5 class="card-title">Đơn mới (7 ngày)</h5>
          <p class="card-text"><?= number_format($newOrders) ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Biểu đồ doanh thu 30 ngày -->
  <div class="row">
    <div class="col">
      <div class="card chart-card">
        <div class="card-header">
          Doanh Thu Theo Ngày (30 ngày gần nhất)
        </div>
        <div class="card-body">
          <canvas id="revenueChart" height="100"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// 5. Include footer và Chart.js
include 'footer.php';
?>
<!-- Nếu footer chưa include Chart.js thì thêm: -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('revenueChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Doanh thu',
        data: <?= json_encode($data, JSON_NUMERIC_CHECK) ?>,
        fill: false,
        tension: 0.4,
        borderWidth: 2
      }]
    },
    options: {
      scales: {
        x: { 
          display: true,
          title: { display: true, text: 'Ngày' }
        },
        y: {
          display: true,
          title: { display: true, text: 'Tổng tiền (₫)' }
        }
      },
      plugins: {
        legend: { display: false }
      }
    }
  });
</script>
