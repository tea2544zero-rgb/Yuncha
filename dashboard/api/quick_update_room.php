<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] === 'counter') {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit;
    }

    $room_id = isset($_POST['room_id']) ? $_POST['room_id'] :  null;
    $field = isset($_POST['field']) ? $_POST['field'] :  null;
    $value = isset($_POST['value']) ? $_POST['value'] :  null;

    if ($room_id && $field && isset($value)) {
        try {
            $allowed_fields = ['status', 'discount_percent'];
            if (!in_array($field, $allowed_fields)) {
                echo json_encode(['success' => false, 'message' => 'Invalid field.']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE rooms SET {$field} = ? WHERE id = ?");
            $stmt->execute([$value, $room_id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    }
}
