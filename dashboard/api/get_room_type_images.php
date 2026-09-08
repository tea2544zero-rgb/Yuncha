<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] === 'counter') {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$type_id = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;

if ($type_id > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT ri.id, ri.image_path, ri.is_primary, ri.slot_index 
            FROM room_images ri 
            WHERE ri.room_id = (SELECT id FROM rooms WHERE room_type_id = ? LIMIT 1)
            ORDER BY ri.slot_index ASC, ri.id ASC
        ");
        $stmt->execute([$type_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'images' => $images]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid type ID']);
}
