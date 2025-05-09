<?php
// tracking.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
require 'header.php';

// Hàm tính khoảng cách giữa 2 toạ độ GPS (Haversine)
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371; // km
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);

    $dLat = $lat2 - $lat1;
    $dLng = $lng2 - $lng1;

    $a = sin($dLat/2) * sin($dLat/2) +
         cos($lat1) * cos($lat2) *
         sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earthRadius * $c;
}

// Lấy danh sách toàn bộ nhân viên giao hàng
$sqlStaff = "SELECT Id_nhanVien, tenNhanVien, viTriLat, viTriLng FROM NhanVien WHERE viTri = 'Giao hàng'";
$resultStaff = $mysqli->query($sqlStaff);

// Xử lý lọc theo bán kính
$filteredStaff = [];
$latCenter = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lngCenter = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$radius    = isset($_GET['radius']) ? (float)$_GET['radius'] : 5; // km mặc định

if ($latCenter !== null && $lngCenter !== null) {
    foreach ($resultStaff as $st) {
        if ($st['viTriLat'] !== null && $st['viTriLng'] !== null) {
            $dist = calculateDistance($latCenter, $lngCenter, $st['viTriLat'], $st['viTriLng']);
            if ($dist <= $radius) {
                $filteredStaff[] = $st;
            }
        }
    }
} else {
    // không lọc nếu chưa chọn
    while ($row = $resultStaff->fetch_assoc()) {
        $filteredStaff[] = $row;
    }
}

// Xử lý khi chọn nhân viên để xem chi tiết
$selectedStaff = null;
$orders = [];
if (isset($_GET['staff_id'])) {
    $sid = (int)$_GET['staff_id'];
    $infoStmt = $mysqli->prepare("SELECT tenNhanVien, viTriLat, viTriLng FROM NhanVien WHERE Id_nhanVien=?");
    $infoStmt->bind_param('i', $sid);
    $infoStmt->execute();
    $selectedStaff = $infoStmt->get_result()->fetch_assoc();

    $orderStmt = $mysqli->prepare(
        "SELECT D.maVanDon, N.tenNguoiNhan, N.diaChi AS diaChiNhan, D.ngayGiao, D.trangThaiDonHang
         FROM DonHang D
         JOIN NguoiNhan N ON D.id_nguoiNhan = N.Id_NguoiNhan
         WHERE D.id_nhanVien=? AND D.trangThaiDonHang='Đang giao'"
    );
    $orderStmt->bind_param('i', $sid);
    $orderStmt->execute();
    $orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Theo dõi giao hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
  <style>
    .content-container { 
      max-width: 1200px; 
      margin: 2rem auto; 
      padding: 1.5rem; 
      background: #fff; 
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
      min-height: 85vh; 
    }
    h2 { 
      color: #2c3e50; 
      font-size: 1.75rem; 
      margin-bottom: 1rem; 
    }
    /* Improved map container */
    #filterMap { 
      height: 350px; 
      width: 100%; 
      margin-bottom: 1.5rem; 
      border: 1px solid #ddd; 
      border-radius: 6px; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      z-index: 1; /* Ensure proper stacking */
    }
    .controls { 
      display: flex; 
      flex-wrap: wrap;
      gap: 1rem; 
      margin-bottom: 1.5rem; 
      align-items: center; 
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
    }
    .controls label { 
      font-size: 1rem; 
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .controls input[type="range"] {
      width: 150px;
      cursor: pointer;
    }
    table { 
      width: 100%; 
      border-collapse: collapse; 
      margin-bottom: 1.5rem; 
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    th, td { 
      padding: 0.75rem 1rem; 
      text-align: left; 
    }
    th { 
      background: #0dcaf0; 
      color: #ecf0f1; 
    }
    tr:nth-child(even) { 
      background: #f9f9f9; 
    }
    tr:hover {
      background: #f0f4f8;
      transition: background 0.3s;
    }
    .btn { 
      display: inline-block; 
      margin: 0.5rem 0; 
      padding: 0.5rem 1rem; 
      background: #ff5722;
      color: #fff; 
      border-radius: 6px; 
      text-decoration: none; 
      transition: background 0.3s, transform 0.2s; 
      cursor: pointer; 
      border: none; 
      font-weight: 600;
    }
    .btn:hover { 
      background: #0dcaf0; 
      transform: translateY(-2px);
    }
    .btn:active {
      transform: translateY(0);
    }
    .notification { 
      padding: 1rem; 
      text-align: center; 
      color: #e67e22; 
      background: #fff8f0;
      border-radius: 6px;
      margin-bottom: 1rem;
    }
    .detail-section { 
      margin-top: 2rem; 
      background: #f8f9fa;
      padding: 1.5rem;
      border-radius: 8px;
    }
    .detail-section h3 {
      color: #2c3e50;
      font-size: 1.4rem;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
    }
    /* Fixed map iframe */
    iframe.map-frame { 
      width: 100%; 
      height: 400px; 
      border: none; 
      border-radius: 8px; 
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    /* Modal */
    .modal { 
      display: none; 
      position: fixed; 
      z-index: 1000; 
      left: 0; 
      top: 0; 
      width: 100%; 
      height: 100%; 
      background: rgba(0,0,0,0.5); 
      overflow: auto; 
      opacity: 0;
      transition: opacity 0.3s;
    }
    .modal.show {
      opacity: 1;
    }
    .modal-content { 
      background: #fff; 
      margin: 10% auto; 
      padding: 1.5rem; 
      border-radius: 8px; 
      max-width: 500px; 
      position: relative; 
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      transform: translateY(-20px);
      transition: transform 0.3s;
    }
    .modal.show .modal-content {
      transform: translateY(0);
    }
    .modal .close { 
      position: absolute; 
      top: 0.5rem; 
      right: 1rem; 
      font-size: 1.5rem; 
      cursor: pointer; 
      color: #aaa; 
      transition: color 0.3s;
    }
    .modal .close:hover { 
      color: #000; 
    }
    .modal-header { 
      font-size: 1.25rem; 
      margin-bottom: 1rem; 
      color: #2c3e50; 
      padding-bottom: 0.5rem;
    }
    .modal-body ul { 
      list-style: none; 
      padding: 0; 
    }
    .modal-body li { 
      margin: 0.75rem 0;
      padding: 0.5rem;
      border-bottom: 1px solid #eee;
    }
    .modal-body li:last-child {
      border-bottom: none;
    }
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .content-container {
        padding: 1rem;
        margin: 1rem;
      }
      #filterMap {
        height: 250px;
      }
      .controls {
        flex-direction: column;
        align-items: flex-start;
      }
      iframe.map-frame {
        height: 300px;
      }
      .modal-content {
        width: 90%;
        margin: 20% auto;
      }
      th, td {
        padding: 0.5rem;
      }
    }
    /* Leaflet control styles */
    .leaflet-touch .leaflet-control-layers, 
    .leaflet-touch .leaflet-bar {
      border: 2px solid rgba(0,0,0,0.2);
      border-radius: 4px;
    }
    .leaflet-control-zoom a {
      transition: background-color 0.3s;
    }
  </style>
</head>
<body>
  <div class="content-container">
    <h2>Theo dõi giao hàng</h2>

    <!-- Bản đồ chọn trung tâm và bán kính -->
    <div id="filterMap"></div>
    <div class="controls">
      <label>Bán kính (km): <input type="range" id="radius" min="1" max="50" value="<?= $radius ?>"> <span id="radiusVal"><?= $radius ?></span> km</label>
      <button id="applyFilter" class="btn">Áp dụng lọc</button>
      <button id="resetMap" class="btn" style="background: #0dcaf0;">Vị trí bưu cục</button>
    </div>

    <?php if (empty($filteredStaff)): ?>
      <div class="notification">Không tìm thấy nhân viên trong khu vực đã chọn</div>
    <?php else: ?>
      <table>
        <thead><tr><th>Nhân viên giao</th><th>Lat</th><th>Lng</th><th>Hành động</th></tr></thead>
        <tbody>
        <?php foreach ($filteredStaff as $st): ?>
          <tr>
            <td><?= htmlspecialchars($st['tenNhanVien']) ?></td>
            <td><?= $st['viTriLat'] ?></td>
            <td><?= $st['viTriLng'] ?></td>
            <td><a class="btn" href="?staff_id=<?= $st['Id_nhanVien'] ?>&lat=<?= urlencode($latCenter) ?>&lng=<?= urlencode($lngCenter) ?>&radius=<?= urlencode($radius) ?>">Xem tiến độ</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($selectedStaff): ?>
        <div class="detail-section">
          <h3>Vị trí hiện tại của <?= htmlspecialchars($selectedStaff['tenNhanVien']) ?>:</h3>
          <?php if (!empty($selectedStaff['viTriLat'])): ?>
            <iframe class="map-frame" src="https://maps.google.com/maps?q=<?= urlencode($selectedStaff['viTriLat']) ?>,<?= urlencode($selectedStaff['viTriLng']) ?>&hl=vi&z=15&output=embed" allowfullscreen></iframe>
          <?php else: ?>
            <p>Chưa có dữ liệu vị trí GPS.</p>
          <?php endif; ?>

          <h3>Danh sách đơn đang giao:</h3>
          <?php if (empty($orders)): ?>
            <div class="notification">Không có đơn hàng đang giao</div>
          <?php else: ?>
            <table>
              <thead><tr><th>Mã đơn</th><th>Người nhận</th><th>Địa chỉ</th><th>Ngày giao</th><th>Thao tác</th></tr></thead>
              <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td><?= htmlspecialchars($o['maVanDon']) ?></td>
                  <td><?= htmlspecialchars($o['tenNguoiNhan']) ?></td>
                  <td><?= htmlspecialchars($o['diaChiNhan']) ?></td>
                  <td><?= date('d/m/Y', strtotime($o['ngayGiao'])) ?></td>
                  <td><button class="btn view-order" data-ma="<?= htmlspecialchars($o['maVanDon']) ?>" data-recipient="<?= htmlspecialchars($o['tenNguoiNhan']) ?>" data-address="<?= htmlspecialchars($o['diaChiNhan']) ?>" data-date="<?= htmlspecialchars(date('d/m/Y', strtotime($o['ngayGiao']))) ?>" data-status="<?= htmlspecialchars($o['trangThaiDonHang']) ?>">Xem</button></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Modal chi tiết đơn hàng -->
  <div id="orderModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeOrder">&times;</span>
      <div class="modal-header">Chi tiết đơn hàng</div>
      <div class="modal-body" id="orderModalBody"></div>
    </div>
  </div>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
  

<script>
document.addEventListener('DOMContentLoaded', () => {
  const defaultLat  = 10.827073,
        defaultLng  = 106.721028,
        latCenter   = <?= $latCenter ?? 'null' ?>,
        lngCenter   = <?= $lngCenter ?? 'null' ?>,
        initialZoom = 13;

  // Biến chứa marker mỗi nhân viên
  let staffMarkers = {};

  // Dữ liệu nhân viên từ PHP
  const staffData = <?= json_encode($filteredStaff, JSON_HEX_TAG) ?>;

  // Khởi tạo map
  const map = L.map('filterMap').setView(
    [latCenter || defaultLat, lngCenter || defaultLng],
    initialZoom
  );
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Icon cho nhân viên
  const empIcon = L.icon({
    iconUrl: 'img/icon-shipper.png',
    iconSize: [32,32],
    iconAnchor: [16,32],
    popupAnchor: [0,-32],
  });

  // Vẽ marker ban đầu cho mỗi nhân viên
  staffData.forEach(st => {
    if (st.viTriLat && st.viTriLng) {
      const m = L.marker([+st.viTriLat, +st.viTriLng], { icon: empIcon })
        .addTo(map)
        .bindPopup(`<b>${st.tenNhanVien}</b><br>Lat: ${(+st.viTriLat).toFixed(5)}<br>Lng: ${(+st.viTriLng).toFixed(5)}`);
      staffMarkers[st.Id_nhanVien] = m;
    }
  });

  // Marker trung tâm & vòng tròn bán kính
  const centerMarker = L.marker(
    [latCenter || defaultLat, lngCenter || defaultLng],
    { draggable: true }
  ).addTo(map);

  const circle = L.circle(centerMarker.getLatLng(), {
    radius: <?= $radius ?> * 1000,
    color: '#1abc9c',
    fillColor: '#1abc9c',
    fillOpacity: 0.2
  }).addTo(map);

  // Khi kéo marker trung tâm
  centerMarker.on('move', e => circle.setLatLng(e.latlng));
  centerMarker.on('dragend', () => map.panTo(centerMarker.getLatLng()));

  // Điều khiển bán kính
  const radiusInput = document.getElementById('radius'),
        radiusVal   = document.getElementById('radiusVal');
  radiusInput.addEventListener('input', e => {
    const r = e.target.value * 1000;
    circle.setRadius(r);
    radiusVal.innerText = e.target.value;
  });

  // Click bản đồ để đặt tâm
  map.on('click', e => {
  //   const lat = e.latlng.lat.toFixed(6); // vĩ độ
  // const lng = e.latlng.lng.toFixed(6); // kinh độ

  // alert(`Tọa độ bạn vừa click:\nLatitude: ${lat}\nLongitude: ${lng}`);
    centerMarker.setLatLng(e.latlng);
    circle.setLatLng(e.latlng);
    map.panTo(e.latlng);
  });

  // Áp dụng lọc (reload với params mới)
  document.getElementById('applyFilter').addEventListener('click', () => {
    const c      = centerMarker.getLatLng();
    const params = new URLSearchParams(location.search);
    params.set('lat', c.lat);
    params.set('lng', c.lng);
    params.set('radius', radiusInput.value);
    const sid = new URLSearchParams(location.search).get('staff_id');
    if (sid) params.set('staff_id', sid);
    location.search = params.toString();
  });

  // Reset map về bưu cục
  document.getElementById('resetMap').addEventListener('click', () => {
    centerMarker.setLatLng([defaultLat, defaultLng]);
    circle.setLatLng([defaultLat, defaultLng]);
    map.setView([defaultLat, defaultLng], initialZoom);
    radiusInput.value = 5;
    radiusVal.innerText = '5';
    circle.setRadius(5000);
  });

  // === WebSocket real-time ===
  const socket = new WebSocket('ws://localhost:8081');
  socket.addEventListener('open', () => console.log('WS connected'));
  socket.addEventListener('error', e => console.error('WS error', e));
  socket.addEventListener('close', () => console.log('WS closed'));

  socket.addEventListener('message', evt => {
    try {
      const data = JSON.parse(evt.data);
      // data = { staffId, lat, lng, timestamp }
      const m = staffMarkers[data.staffId];
      if (m) {
        m.setLatLng([data.lat, data.lng]);
      }
    } catch (err) {
      console.error('Invalid WS data', err);
    }
  });

  // === Modal chi tiết đơn hàng ===
  // Modal handling with animations
  const orderModal = document.getElementById('orderModal');
      const closeOrderBtn = document.getElementById('closeOrder');
      
      // Add show/hide animations
      function showModal() {
        orderModal.style.display = 'block';
        setTimeout(() => orderModal.classList.add('show'), 10);
      }
      
      function hideModal() {
        orderModal.classList.remove('show');
        setTimeout(() => orderModal.style.display = 'none', 300);
      }
      
      // Order view buttons
      document.querySelectorAll('.view-order').forEach(btn => {
        btn.addEventListener('click', function() {
          document.getElementById('orderModalBody').innerHTML = `
            <ul>
              <li><strong>Mã đơn:</strong> ${this.dataset.ma}</li>
              <li><strong>Người nhận:</strong> ${this.dataset.recipient}</li>
              <li><strong>Địa chỉ:</strong> ${this.dataset.address}</li>
              <li><strong>Ngày giao:</strong> ${this.dataset.date}</li>
              <li><strong>Trạng thái:</strong> ${this.dataset.status}</li>
            </ul>
          `;
          showModal();
        });
      });
      
      // Close button and outside click
      closeOrderBtn.addEventListener('click', hideModal);
      window.addEventListener('click', function(e) {
        if (e.target === orderModal) {
          hideModal();
        }
      });
      
      // Handle escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && orderModal.style.display === 'block') {
          hideModal();
        }
      });
});
</script>

<?php require 'footer.php'; ?>