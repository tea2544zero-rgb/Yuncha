<?php
header('Content-Type: application/json');
require_once '../dashboard/config/db.php';

$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;

if ($type_id > 0) {
    // หาห้องสักห้องหนึ่งที่อยู่ในประเภทนี้
    $stmt = $conn->prepare("SELECT id FROM rooms WHERE room_type_id = ? LIMIT 1");
    $stmt->execute([$type_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($room) {
        $room_id = $room['id'];
        // ดึง amenities ทั้งหมดของห้องนั้นที่ "ไม่ใช่" kingbed หรือ twinbed (Base Amenities)
        $stmt_am = $conn->prepare("SELECT amenity_code FROM room_amenities WHERE room_id = ? AND amenity_code NOT IN ('kingbed', 'twinbed')");
        $stmt_am->execute([$room_id]);
        $amenities = $stmt_am->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['success' => true, 'amenities' => $amenities]);
        exit;
    }
}

echo json_encode(['success' => false, 'amenities' => []]);
