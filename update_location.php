<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    $stmt = $mysqli->prepare("UPDATE NhanVien SET viTriLat=?, viTriLng=? WHERE Id_nhanVien=?");
    $stmt->bind_param('ddi', $lat, $lng, $id);
    $stmt->execute();

    echo json_encode(['success' => true]);
}
