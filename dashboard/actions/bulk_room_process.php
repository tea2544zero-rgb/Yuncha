<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] === 'counter') {
        die("ไม่มีสิทธิ์เข้าถึงหน้านี้ (Permission denied)");
    }

    $type_id = isset($_POST['type_id']) ? $_POST['type_id'] :  null;
    $base_price = isset($_POST['base_price']) ? $_POST['base_price'] :  null;
    $high_price = isset($_POST['high_price']) ? $_POST['high_price'] :  0;
    $holiday_price = isset($_POST['holiday_price']) ? $_POST['holiday_price'] :  0;
    
    $price_4p_base = isset($_POST['price_4p_base']) ? $_POST['price_4p_base'] :  0;
    $price_4p_high = isset($_POST['price_4p_high']) ? $_POST['price_4p_high'] :  0;
    $price_4p_holiday = isset($_POST['price_4p_holiday']) ? $_POST['price_4p_holiday'] :  0;
    
    $extra_bed_price = isset($_POST['extra_bed_price']) ? $_POST['extra_bed_price'] :  0;
    $discount_percent = isset($_POST['discount_percent']) ? $_POST['discount_percent'] :  0;
    $details_index = isset($_POST['details_index']) ? $_POST['details_index'] :  '';
    $details_room = isset($_POST['details_room']) ? $_POST['details_room'] :  '';
    $details_highlights = isset($_POST['details_highlights']) ? $_POST['details_highlights'] :  '';
    $details = $details_index . "|||" . $details_room . "|||" . $details_highlights;
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] :  [];

    if ($type_id && $base_price) {
        try {
            $conn->beginTransaction();

            // อัปเดตราคาตั้งต้นและราคาอื่นๆ ในตาราง room_types
            $stmt = $conn->prepare("UPDATE room_types SET base_price = ?, high_price = ?, holiday_price = ? WHERE id = ?");
            $stmt->execute([$base_price, $high_price, $holiday_price, $type_id]);

            // อัปเดตส่วนลด รายละเอียด และราคาเตียงเสริม ให้ห้องทุกขนาดในกลุ่มนี้
            $stmt_update = $conn->prepare("UPDATE rooms SET discount_percent = ?, details = ?, allow_extra_bed = 1, extra_bed_price = ? WHERE room_type_id = ?");
            $stmt_update->execute([$discount_percent, $details, $extra_bed_price, $type_id]);

            // อัปเดตราคาสำหรับห้องพัก 2 คน
            $stmt2 = $conn->prepare("UPDATE rooms SET base_price = ?, high_price = ?, holiday_price = ? WHERE room_type_id = ? AND max_guests = 2");
            $stmt2->execute([$base_price, $high_price, $holiday_price, $type_id]);

            // อัปเดตราคาสำหรับห้องพัก 4 คน
            if ($price_4p_base <= 0) $price_4p_base = $base_price + 900;
            if ($price_4p_high <= 0) $price_4p_high = $high_price + 900;
            if ($price_4p_holiday <= 0) $price_4p_holiday = $holiday_price + 900;
            
            $stmt4 = $conn->prepare("UPDATE rooms SET base_price = ?, high_price = ?, holiday_price = ? WHERE room_type_id = ? AND max_guests = 4");
            $stmt4->execute([$price_4p_base, $price_4p_high, $price_4p_holiday, $type_id]);

            // =====================================
            // จัดการสิ่งอำนวยความสะดวก (Amenities)
            // =====================================
            $conn->exec("DELETE FROM room_amenities WHERE room_id IN (SELECT id FROM rooms WHERE room_type_id = $type_id)");
            if (!empty($amenities)) {
                $rooms_stmt = $conn->query("SELECT id FROM rooms WHERE room_type_id = $type_id");
                $room_ids = $rooms_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($room_ids)) {
                    $stmt_am = $conn->prepare("INSERT INTO room_amenities (room_id, amenity_code) VALUES (?, ?)");
                    foreach ($room_ids as $r_id) {
                        foreach ($amenities as $code) {
                            $stmt_am->execute([$r_id, $code]);
                        }
                    }
                }
            }

            // =====================================
            // จัดการรูปภาพ (Images)
            // =====================================
            if (isset($_FILES['room_images']['name']) && is_array($_FILES['room_images']['name'])) {
                $rooms_stmt = $conn->query("SELECT id FROM rooms WHERE room_type_id = $type_id");
                $room_ids = $rooms_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($room_ids)) {
                    $upload_dir = "../../uploads/rooms/";
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $uploaded_slots = [];
                    foreach ($_FILES['room_images']['name'] as $slot_idx => $file_name) {
                        if (empty($file_name)) continue;
                        $file_tmp = $_FILES['room_images']['tmp_name'][$slot_idx];
                        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        $new_name = uniqid() . '_' . time() . '.' . $ext;
                        $dest = $upload_dir . $new_name;
                        
                        if (move_uploaded_file($file_tmp, $dest)) {
                            $uploaded_slots[$slot_idx] = "uploads/rooms/" . $new_name;
                        }
                    }

                    if (!empty($uploaded_slots)) {
                        $stmt_img = $conn->prepare("INSERT INTO room_images (room_id, image_path, is_primary, slot_index) VALUES (?, ?, ?, ?)");
                        foreach ($room_ids as $r_id) {
                            foreach ($uploaded_slots as $slot_idx => $path) {
                                $is_primary = ($slot_idx == 0) ? 1 : 0;
                                $stmt_img->execute([$r_id, $path, $is_primary, $slot_idx]);
                            }
                        }
                    }
                }
            }

            $conn->commit();
            header("Location: ../rooms.php?msg=bulk_updated");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            die("เกิดข้อผิดพลาด: " . $e->getMessage());
        }
    }
}
header("Location: ../rooms.php");
exit;
