<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_pin = isset($_POST['admin_pin']) ? $_POST['admin_pin'] :  '';
    
    // Check permission
    if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
        echo json_encode(['status' => 'error', 'message' => 'ปฏิเสธการเข้าถึง! เฉพาะผู้จัดการเท่านั้น']);
        exit;
    }

    try {
        // Verify PIN
        $stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'admin_pin'");
        $real_pin = $stmt->fetchColumn();

        $emp_name = isset($_SESSION['emp_name']) ? $_SESSION['emp_name'] :  'System';
        $emp_code = isset($_SESSION['emp_code']) ? $_SESSION['emp_code'] :  'SYS';
        $user_fullname = $emp_code . ' (' . $emp_name . ')';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] :  'Unknown';

        if ($input_pin !== $real_pin) {
            // Log failure
            $log_stmt = $conn->prepare("INSERT INTO security_logs (user_name, action, ip_address, status) VALUES (?, 'พยายามล้างข้อมูลระบบด้วย PIN ผิดพลาด', ?, 'Denied')");
            $log_stmt->execute([$user_fullname, $ip]);

            echo json_encode(['status' => 'error', 'message' => 'รหัส Admin PIN ไม่ถูกต้อง']);
            exit;
        }

        // If PIN is correct, perform cache clearing (delete unused uploads)
        $stmt_active = $conn->query("SELECT setting_value FROM settings WHERE setting_key IN ('hotel_logo', 'qr_image', 'chatbot_avatar')");
        $keep_files = $stmt_active->fetchAll(PDO::FETCH_COLUMN);
        // filter out empty values
        $keep_files = array_filter($keep_files);
        
        $upload_dir = '../uploads/';
        $deleted_count = 0;
        
        if (is_dir($upload_dir)) {
            $files = scandir($upload_dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    // ถ้าไฟล์ไม่ได้ถูกใช้อยู่ในฐานข้อมูลปัจจุบัน ให้ลบทิ้ง
                    if (is_file($upload_dir . $file) && !in_array($file, $keep_files)) {
                        if(@unlink($upload_dir . $file)) {
                            $deleted_count++;
                        }
                    }
                }
            }
        }

        // Log success
        $action_msg = $deleted_count > 0 ? "ล้างไฟล์ขยะรูปภาพจำนวน {$deleted_count} ไฟล์สำเร็จ" : "เรียกใช้คำสั่งล้างข้อมูล แต่ไม่มีไฟล์ขยะ";
        $log_stmt = $conn->prepare("INSERT INTO security_logs (user_name, action, ip_address, status) VALUES (?, ?, ?, 'Success')");
        $log_stmt->execute([$user_fullname, $action_msg, $ip]);

        echo json_encode(['status' => 'success', 'message' => "ล้างข้อมูลขยะสำเร็จ! คืนพื้นที่จากการลบ {$deleted_count} ไฟล์"]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
