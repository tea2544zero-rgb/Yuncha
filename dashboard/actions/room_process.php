<?php
// เชื่อมต่อฐานข้อมูล
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] :  '';

    // ==========================================
    // 🔥 1. การเพิ่มห้องพัก (Add Room)
    // ==========================================
    if ($action == 'add') {
        $room_type_id = (int)$_POST['room_type_id'];
        $quantity_2pax = (int)(isset($_POST['quantity_2pax']) ? $_POST['quantity_2pax'] :  0);
        $quantity_4pax = (int)(isset($_POST['quantity_4pax']) ? $_POST['quantity_4pax'] :  0);

        // ดึงข้อมูลต้นแบบจากห้องแรกในกลุ่มนี้ หรือจาก room_types
        // ค่าเริ่มต้น
        $base_price = 0;
        $details = '';
        $discount_percent = 0;

        $stmt_type = $conn->prepare("SELECT base_price FROM room_types WHERE id = ?");
        $stmt_type->execute([$room_type_id]);
        $type_data = $stmt_type->fetch();
        if($type_data) {
            $base_price = $type_data['base_price'];
        }

        $stmt_sibling = $conn->prepare("SELECT details, discount_percent, extra_bed_price, allow_extra_bed FROM rooms WHERE room_type_id = ? LIMIT 1");
        $stmt_sibling->execute([$room_type_id]);
        $sibling_data = $stmt_sibling->fetch();
        
        $allow_extra_bed = 1;
        $extra_bed_price = 0;

        if($sibling_data) {
            $details = $sibling_data['details'];
            $discount_percent = $sibling_data['discount_percent'];
            $extra_bed_price = $sibling_data['extra_bed_price'];
            $allow_extra_bed = isset($sibling_data['allow_extra_bed']) ? $sibling_data['allow_extra_bed'] :  1;
        }

        // Fetch price for 4 pax
        $price_4pax = $base_price + 900;
        $stmt_4pax = $conn->prepare("SELECT base_price FROM rooms WHERE room_type_id = ? AND max_guests = 4 LIMIT 1");
        $stmt_4pax->execute([$room_type_id]);
        if($row = $stmt_4pax->fetch()) {
            $price_4pax = $row['base_price'];
        }
        $status = 'available';

        // หา Prefix เลขห้อง และ ลำดับล่าสุด
        $stmt_next = $conn->prepare("SELECT room_number FROM rooms WHERE room_type_id = ? ORDER BY id DESC LIMIT 1");
        $stmt_next->execute([$room_type_id]);
        $last_room = $stmt_next->fetchColumn();

        $prefix = "T" . $room_type_id . "-";
        $current_num = 0;
        
        if($last_room) {
            if(preg_match('/(.*?)(\d+)$/', $last_room, $m)) {
                $prefix = $m[1];
                $current_num = (int)$m[2];
            } else {
                $prefix = $last_room . "-";
            }
        }

        $sql_room = "INSERT INTO rooms (room_number, room_type_id, status, base_price, discount_percent, max_guests, allow_extra_bed, extra_bed_price, details) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_room = $conn->prepare($sql_room);
        
        // ค้นหา Amenities ต้นแบบ
        $stmt_am = $conn->prepare("SELECT amenity_code FROM room_amenities WHERE room_id = (SELECT id FROM rooms WHERE room_type_id = ? LIMIT 1)");
        $stmt_am->execute([$room_type_id]);
        $sibling_amenities = $stmt_am->fetchAll(PDO::FETCH_COLUMN);
        
        // ค้นหารูปภาพต้นแบบ
        $stmt_img = $conn->prepare("SELECT image_path, is_primary FROM room_images WHERE room_id = (SELECT id FROM rooms WHERE room_type_id = ? LIMIT 1)");
        $stmt_img->execute([$room_type_id]);
        $sibling_images = $stmt_img->fetchAll(PDO::FETCH_ASSOC);

        $stmt_insert_am = $conn->prepare("INSERT INTO room_amenities (room_id, amenity_code) VALUES (?, ?)");
        $stmt_insert_img = $conn->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");

        // 1. สร้างห้องพัก 2 คน
        for ($i = 0; $i < $quantity_2pax; $i++) {
            $current_num++;
            $room_number = $prefix . str_pad($current_num, 2, '0', STR_PAD_LEFT);
            
            if ($stmt_room->execute([$room_number, $room_type_id, $status, $base_price, $discount_percent, 2, $allow_extra_bed, $extra_bed_price, $details])) {
                $new_room_id = $conn->lastInsertId();

                if (!empty($sibling_amenities)) {
                    foreach ($sibling_amenities as $am) {
                        $stmt_insert_am->execute([$new_room_id, $am]);
                    }
                }
                if (!empty($sibling_images)) {
                    foreach ($sibling_images as $img) {
                        $stmt_insert_img->execute([$new_room_id, $img['image_path'], $img['is_primary']]);
                    }
                }
            }
        }

        // 2. สร้างห้องพัก 4 คน
        for ($i = 0; $i < $quantity_4pax; $i++) {
            $current_num++;
            $room_number = $prefix . str_pad($current_num, 2, '0', STR_PAD_LEFT);
            
            if ($stmt_room->execute([$room_number, $room_type_id, $status, $price_4pax, $discount_percent, 4, $allow_extra_bed, $extra_bed_price, $details])) {
                $new_room_id = $conn->lastInsertId();

                if (!empty($sibling_amenities)) {
                    foreach ($sibling_amenities as $am) {
                        $stmt_insert_am->execute([$new_room_id, $am]);
                    }
                }
                if (!empty($sibling_images)) {
                    foreach ($sibling_images as $img) {
                        $stmt_insert_img->execute([$new_room_id, $img['image_path'], $img['is_primary']]);
                    }
                }
            }
        }

        echo "<script>window.location.href='../rooms.php';</script>";
    }

    // ==========================================
    // 🔥 2. การลบห้องพัก (Soft Delete)
    // ==========================================
    elseif ($action == 'delete') {
        $room_id = (int)$_POST['room_id'];
        
        $stmt = $conn->prepare("UPDATE rooms SET status = 'deleted' WHERE id = ?");
        if ($stmt->execute([$room_id])) {
            echo "<script>window.location.href='../rooms.php';</script>";
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'เกิดข้อผิดพลาดในการลบห้องพัก',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
        }
    }

    // ==========================================
    // 🔥 3. กู้คืนห้องพัก (Restore Room)
    // ==========================================
    elseif ($action == 'restore') {
        $room_id = (int)$_POST['room_id'];
        
        $stmt = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        if ($stmt->execute([$room_id])) {
            echo "<script>window.location.href='../rooms.php';</script>";
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'เกิดข้อผิดพลาดในการกู้คืนห้องพัก',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
        }
    }

    // ==========================================
    // 🔥 4. ลบประเภทห้องพัก (Delete Room Type)
    // ==========================================
    elseif ($action == 'delete_type') {
        $type_id = (int)$_POST['type_id'];
        
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE room_type_id = ? LIMIT 1");
        $stmt->execute([$type_id]);
        if ($stmt->fetch()) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'ไม่สามารถลบได้! เนื่องจากมีห้องพักที่ใช้ประเภทนี้อยู่ (ต้องลบห้องพักทั้งหมดออกก่อน)',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
        } else {
            $stmt_del = $conn->prepare("DELETE FROM room_types WHERE id = ?");
            if($stmt_del->execute([$type_id])) {
                echo "<script>window.location.href='../rooms.php';</script>";
            } else {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'เกิดข้อผิดพลาด',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
            }
        }
    }
}
?>
