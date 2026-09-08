<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type_name = trim(isset($_POST['type_name']) ? $_POST['type_name'] :  '');
    $prefix = trim(isset($_POST['prefix']) ? $_POST['prefix'] :  '');
    $quantity_2pax = (int)(isset($_POST['quantity_2pax']) ? $_POST['quantity_2pax'] :  0);
    $quantity_4pax = (int)(isset($_POST['quantity_4pax']) ? $_POST['quantity_4pax'] :  0);
    
    $base_price = (float)(isset($_POST['base_price']) ? $_POST['base_price'] :  0);
    $high_price = (float)(isset($_POST['high_price']) ? $_POST['high_price'] :  0);
    $holiday_price = (float)(isset($_POST['holiday_price']) ? $_POST['holiday_price'] :  0);
    
    $price_4p_base = (float)(isset($_POST['price_4p_base']) ? $_POST['price_4p_base'] :  0);
    $price_4p_high = (float)(isset($_POST['price_4p_high']) ? $_POST['price_4p_high'] :  0);
    $price_4p_holiday = (float)(isset($_POST['price_4p_holiday']) ? $_POST['price_4p_holiday'] :  0);
    
    $extra_bed_price = (float)(isset($_POST['extra_bed_price']) ? $_POST['extra_bed_price'] :  0);
    $discount_percent = (float)(isset($_POST['discount_percent']) ? $_POST['discount_percent'] :  0);
    
    $details_index = isset($_POST['details_index']) ? $_POST['details_index'] :  '';
    $details_room = isset($_POST['details_room']) ? $_POST['details_room'] :  '';
    $details_highlights = isset($_POST['details_highlights']) ? $_POST['details_highlights'] :  '';
    $details = $details_index . "|||" . $details_room . "|||" . $details_highlights;
    
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] :  [];

    $total_quantity = $quantity_2pax + $quantity_4pax;

    if ($type_name && $prefix && $total_quantity > 0) {
        try {
            $conn->beginTransaction();

            // 1. Insert into room_types
            $stmt = $conn->prepare("INSERT INTO room_types (type_name, base_price, high_price, holiday_price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$type_name, $base_price, $high_price, $holiday_price]);
            $type_id = $conn->lastInsertId();

            // 2. Upload images
            $upload_dir = "../../uploads/rooms/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $uploaded_paths = [];
            
            if (!empty($_FILES['room_images']['name'][0])) {
                $file_count = count($_FILES['room_images']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    $file_name = $_FILES['room_images']['name'][$i];
                    $file_tmp = $_FILES['room_images']['tmp_name'][$i];
                    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_name = uniqid() . '_' . time() . '.' . $ext;
                    $dest = $upload_dir . $new_name;
                    if (move_uploaded_file($file_tmp, $dest)) {
                        $uploaded_paths[] = "uploads/rooms/" . $new_name;
                    }
                }
            }

            // 3. Create N rooms
            $stmt_room = $conn->prepare("INSERT INTO rooms (room_number, room_type_id, status, base_price, high_price, holiday_price, discount_percent, max_guests, allow_extra_bed, extra_bed_price, details) 
                                         VALUES (?, ?, 'available', ?, ?, ?, ?, ?, 1, ?, ?)");
            
            $stmt_am = $conn->prepare("INSERT INTO room_amenities (room_id, amenity_code) VALUES (?, ?)");
            $stmt_img = $conn->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");

            $current_num = 0;

            // สร้างห้องพัก 2 คน
            for ($i = 0; $i < $quantity_2pax; $i++) {
                $current_num++;
                $numStr = str_pad($current_num, 2, '0', STR_PAD_LEFT);
                $room_number = $prefix . "-" . $numStr; // e.g. TV-01
                
                $stmt_room->execute([$room_number, $type_id, $base_price, $high_price, $holiday_price, $discount_percent, 2, $extra_bed_price, $details]);
                $room_id = $conn->lastInsertId();

                foreach ($amenities as $am) {
                    $stmt_am->execute([$room_id, $am]);
                }
                foreach ($uploaded_paths as $idx => $path) {
                    $is_primary = ($idx === 0) ? 1 : 0;
                    $stmt_img->execute([$room_id, $path, $is_primary]);
                }
            }

            // สร้างห้องพัก 4 คน
            if ($price_4p_base <= 0) $price_4p_base = $base_price + 900;
            if ($price_4p_high <= 0) $price_4p_high = $high_price + 900;
            if ($price_4p_holiday <= 0) $price_4p_holiday = $holiday_price + 900;
            
            for ($i = 0; $i < $quantity_4pax; $i++) {
                $current_num++;
                $numStr = str_pad($current_num, 2, '0', STR_PAD_LEFT);
                $room_number = $prefix . "-" . $numStr; // e.g. TV-01
                
                $stmt_room->execute([$room_number, $type_id, $price_4p_base, $price_4p_high, $price_4p_holiday, $discount_percent, 4, $extra_bed_price, $details]);
                $room_id = $conn->lastInsertId();

                foreach ($amenities as $am) {
                    $stmt_am->execute([$room_id, $am]);
                }
                foreach ($uploaded_paths as $idx => $path) {
                    $is_primary = ($idx === 0) ? 1 : 0;
                    $stmt_img->execute([$room_id, $path, $is_primary]);
                }
            }

            $conn->commit();
            header("Location: ../rooms.php?msg=type_created");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            die("Error: " . $e->getMessage());
        }
    }
}
header("Location: ../rooms.php");
exit;
