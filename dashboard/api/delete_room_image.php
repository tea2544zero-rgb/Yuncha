<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] === 'counter') {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

    if ($image_id > 0) {
        try {
            // ดึงพาธรูปภาพมาเพื่อลบไฟล์
            $stmt_get = $conn->prepare("SELECT image_path FROM room_images WHERE id = ?");
            $stmt_get->execute([$image_id]);
            $image = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if ($image) {
                $file_path = "../../" . $image['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                // ลบออกจากฐานข้อมูลสำหรับห้องทุกห้องในกลุ่มเดียวกันที่มี path นี้
                $stmt_del = $conn->prepare("DELETE FROM room_images WHERE image_path = ?");
                $stmt_del->execute([$image['image_path']]);
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Image not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
