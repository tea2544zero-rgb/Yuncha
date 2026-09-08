<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $action = isset($_POST['action']) ? $_POST['action'] : 'add'; 
    $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : '../bookings.php';

    try {
        // ==========================================
        // 1. เพิ่มการจองใหม่ (ADD)
        // ==========================================
        if ($action == 'add') {
            $customer_id = 0;
            
            // 1.1 จัดการข้อมูลลูกค้า (สร้างใหม่ หรือ เลือกคนเดิม)
            if ($_POST['customer_type'] == 'new') {
                $fname = isset($_POST['new_fname']) ? $_POST['new_fname'] : '';
                $lname = isset($_POST['new_lname']) ? $_POST['new_lname'] : '';
                $phone = isset($_POST['new_phone']) ? $_POST['new_phone'] : '';
                
                // เช็คว่ามีเบอร์นี้ในระบบแล้วหรือไม่ (ป้องกันการสร้างซ้ำซ้อน)
                $stmt_chk = $conn->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
                $stmt_chk->execute([$phone]);
                if ($row_chk = $stmt_chk->fetch(PDO::FETCH_ASSOC)) {
                    $customer_id = $row_chk['id'];
                } else {
                    $stmt_cus = $conn->prepare("INSERT INTO customers (first_name, last_name, phone, auth_provider, status) VALUES (?, ?, ?, 'Walk-in', 'active')");
                    if ($stmt_cus->execute([$fname, $lname, $phone])) {
                        $customer_id = $conn->lastInsertId();
                    } else {
                        die("Error creating customer");
                    }
                }
            } else {
                $cus_search = $_POST['customer_search'];
                if(preg_match('/ID:(\d+)/', $cus_search, $matches)) {
                    $customer_id = (int)$matches[1];
                } else {
                    die("<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'กรุณาเลือกลูกค้าจากรายชื่อที่ค้นพบให้ถูกต้อง',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>");
                }
            }

            // 1.2 รับข้อมูลห้องพักและวันเวลา
            $room_id = (int)$_POST['room_id'];
            $check_in = isset($_POST['check_in']) ? $_POST['check_in'] : '';
            $check_out = isset($_POST['check_out']) ? $_POST['check_out'] : '';
            $adults = (int)$_POST['adults'];
            $extra_bed = isset($_POST['add_extra_bed']) ? 1 : 0;
            $total_price = (float)$_POST['total_price'];
            
            // 1.3 เช็คห้องว่าง (ป้องกันการจองทับซ้อน)
            $stmt_check = $conn->prepare("SELECT id FROM bookings WHERE room_id = ? AND status != 'cancelled' AND (? < check_out AND ? > check_in)");
            $stmt_check->execute([$room_id, $check_in, $check_out]);
            if ($stmt_check->rowCount() > 0 || $stmt_check->fetch()) {
                die("<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'ขออภัย! ห้องพักนี้ถูกจองไปแล้วในช่วงเวลาดังกล่าว',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>");
            }

            // 1.4 รันรหัสอ้างอิงการจอง
            $date_prefix = date("Ymd", strtotime($check_in));
            $stmt_ref = $conn->prepare("SELECT booking_ref FROM bookings WHERE booking_ref LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt_ref->execute(["YC-$date_prefix-%"]);
            $row_ref = $stmt_ref->fetch(PDO::FETCH_ASSOC);
            
            if($row_ref && isset($row_ref['booking_ref'])) {
                $last_ref = $row_ref['booking_ref'];
                $new_num = str_pad((int)substr($last_ref, -3) + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $new_num = '001';
            }
            $booking_ref = "YC-$date_prefix-$new_num";

            // ดึง force_status จาก POST ถ้ามี (หน้า frontdesk ตอน walk-in มักจะบังคับเป็น confirmed เลย)
            $status_to_insert = isset($_POST['force_status']) ? $_POST['force_status'] : 'confirmed';

            // 1.5 บันทึกลงตาราง bookings
            $stmt_book = $conn->prepare("INSERT INTO bookings (booking_ref, customer_id, room_id, check_in, check_out, adults, extra_bed, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt_book->execute([$booking_ref, $customer_id, $room_id, $check_in, $check_out, $adults, $extra_bed, $total_price, $status_to_insert])) {
                $booking_id = $conn->lastInsertId();

                // 🌟 1.6 จัดการการชำระเงิน + อัปโหลดสลิป
                $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
                $slip_filename = null; 
                
                if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == UPLOAD_ERR_OK) {
                    $slip_dir = '../uploads/slips/';
                    if (!is_dir($slip_dir)) { mkdir($slip_dir, 0777, true); }
                    $file_info = pathinfo($_FILES['slip_image']['name']);
                    $ext = strtolower($file_info['extension']);
                    $new_slip_name = "slip_" . $booking_ref . "_" . time() . "." . $ext;
                    if(move_uploaded_file($_FILES['slip_image']['tmp_name'], $slip_dir . $new_slip_name)) {
                        $slip_filename = $new_slip_name;
                    }
                }
                
                $stmt_pay = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_date, status, slip_image) VALUES (?, ?, ?, CURRENT_TIMESTAMP, 'verified', ?)");
                $stmt_pay->execute([$booking_id, $total_price, $payment_method, $slip_filename]);

                // 🌟 1.7 Finance Sync (เพิ่มลงสมุดบัญชี transactions)
                if ($status_to_insert == 'confirmed') {
                    $tx_time = date('H:i:s');
                    $stmt_tx = $conn->prepare("INSERT INTO transactions (booking_id, amount, type, description, method, image_path, transaction_time) VALUES (?, ?, 'income', 'ชำระค่าห้องพัก (Walk-in)', ?, ?, ?)");
                    $stmt_tx->execute([$booking_id, $total_price, $payment_method, $slip_filename, $tx_time]);
                }

                // 1.8 ตรวจสอบว่าจะให้เปิดหน้าใบเสร็จทันที หรือ กลับไปหน้าเดิม
                if (isset($_POST['print_receipt']) && $_POST['print_receipt'] == '1') {
                    echo "<script>window.location.href='../receipt.php?id=$booking_id&return_url=" . urlencode($return_url) . "';</script>";
                } else {
                    $redirect_target = (strpos($return_url, 'bookings.php') !== false) ? "$return_url?date=$check_in" : $return_url;
                    echo "<script>window.location.href='$redirect_target';</script>";
                }
            }

        // ==========================================
        // 2. แก้ไขการจอง (EDIT) เลื่อนวันอย่างเดียว
        // ==========================================
        } elseif ($action == 'edit') {
            $booking_id = (int)$_POST['booking_id'];
            $room_id = (int)$_POST['room_id'];
            $check_in = isset($_POST['check_in']) ? $_POST['check_in'] : '';
            $check_out = isset($_POST['check_out']) ? $_POST['check_out'] : '';

            $stmt_check = $conn->prepare("SELECT id FROM bookings WHERE room_id = ? AND id != ? AND status != 'cancelled' AND (? < check_out AND ? > check_in)");
            $stmt_check->execute([$room_id, $booking_id, $check_in, $check_out]);
            if ($stmt_check->rowCount() > 0 || $stmt_check->fetch()) {
                die("<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'ขออภัย! วันที่เลื่อนทับซ้อนกับการจองอื่นในระบบ',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>");
            }

            $stmt_update = $conn->prepare("UPDATE bookings SET check_in = ?, check_out = ? WHERE id = ?");
            if ($stmt_update->execute([$check_in, $check_out, $booking_id])) {
                $redirect_target = (strpos($return_url, 'bookings.php') !== false) ? "$return_url?date=$check_in" : $return_url;
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'บันทึกการเลื่อนวันเรียบร้อยแล้ว',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.location.href='$redirect_target'))</script>";
            }

        // ==========================================
        // 3. ลบการจอง / จองผิดพลาด (DELETE)
        // ==========================================
        } elseif ($action == 'delete' || $action == 'delete_walkin') {
            $booking_id = (int)$_POST['booking_id'];
            
            $stmt_slip = $conn->prepare("SELECT slip_image FROM payments WHERE booking_id = ? AND slip_image IS NOT NULL");
            $stmt_slip->execute([$booking_id]);
            $row_slip = $stmt_slip->fetch(PDO::FETCH_ASSOC);
            if ($row_slip && !empty($row_slip['slip_image'])) {
                $file_path = '../uploads/slips/' . $row_slip['slip_image'];
                if (file_exists($file_path)) unlink($file_path);
            }
            
            $conn->prepare("DELETE FROM transactions WHERE booking_id = ?")->execute([$booking_id]);
            $conn->prepare("DELETE FROM payments WHERE booking_id = ?")->execute([$booking_id]);
            $stmt_del = $conn->prepare("DELETE FROM bookings WHERE id = ?");
            if ($stmt_del->execute([$booking_id])) {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'ลบข้อมูลการจองสำเร็จ',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.location.replace('$return_url')))</script>";
            } else {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'เกิดข้อผิดพลาดในการลบ',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
            }

        // ==========================================
        // 4. เช็คอินเข้าพัก (CHECK-IN)
        // ==========================================
        } elseif ($action == 'check_in') {
            $booking_id = (int)$_POST['booking_id'];
            $stmt_chk = $conn->prepare("SELECT check_in FROM bookings WHERE id=?");
            $stmt_chk->execute([$booking_id]);
            $book_in = $stmt_chk->fetchColumn();
            
            $allowed_time = $book_in . ' 14:00:00';
            $current_time = date('Y-m-d H:i:s');
            
            if ($current_time < $allowed_time) {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'ยังไม่ถึงเวลาเช็คอิน! (สามารถเช็คอินได้ตั้งแต่ 14:00 น. ของวันที่เข้าพักเป็นต้นไป)',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
            if ($stmt->execute([$booking_id])) echo "<script>window.location.href='$return_url';</script>";

        // ==========================================
        // 5. เช็คเอาท์จบงาน (CHECK-OUT)
        // ==========================================
        } elseif ($action == 'check_out') {
            $booking_id = (int)$_POST['booking_id'];
            $stmt = $conn->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
            if ($stmt->execute([$booking_id])) {
                $stmt_r = $conn->prepare("UPDATE rooms SET status='cleaning' WHERE id = (SELECT room_id FROM bookings WHERE id=?)");
                $stmt_r->execute([$booking_id]);
                echo "<script>window.location.href='$return_url';</script>";
            }

        // ==========================================
        // 6. ยกเลิกการเช็คอิน (เผลอกดผิด)
        // ==========================================
        } elseif ($action == 'undo_check_in') {
            $booking_id = (int)$_POST['booking_id'];
            $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            if ($stmt->execute([$booking_id])) echo "<script>window.location.href='$return_url';</script>";

        // ==========================================
        // 7. ยกเลิกการเช็คเอาท์ (เผลอกดผิด)
        // ==========================================
        } elseif ($action == 'undo_check_out') {
            $booking_id = (int)$_POST['booking_id'];
            $stmt = $conn->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
            if ($stmt->execute([$booking_id])) {
                $stmt_r = $conn->prepare("UPDATE rooms SET status='available' WHERE id = (SELECT room_id FROM bookings WHERE id=?)");
                $stmt_r->execute([$booking_id]);
                echo "<script>window.location.href='$return_url';</script>";
            }
            
        // ==========================================
        // 8. อนุมัติการจอง (APPROVE)
        // ==========================================
        } elseif ($action == 'approve') {
            $booking_id = (int)$_POST['booking_id'];
            $stmt_info = $conn->prepare("SELECT total_price, booking_ref FROM bookings WHERE id = ?");
            $stmt_info->execute([$booking_id]);
            $b_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            
            if ($b_info) {
                $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
                $stmt_pay = $conn->prepare("UPDATE payments SET status = 'verified' WHERE booking_id = ?");
                if ($stmt->execute([$booking_id]) && $stmt_pay->execute([$booking_id])) {
                    // 🌟 Finance Sync: เช็คว่าเคยลงบัญชีไว้หรือยัง
                    $stmt_chk_tx = $conn->prepare("SELECT id FROM transactions WHERE booking_id = ? AND type = 'income'");
                    $stmt_chk_tx->execute([$booking_id]);
                    if($stmt_chk_tx->rowCount() == 0) {
                        $stmt_slip = $conn->prepare("SELECT slip_image, payment_method FROM payments WHERE booking_id = ?");
                        $stmt_slip->execute([$booking_id]);
                        $s_info = $stmt_slip->fetch(PDO::FETCH_ASSOC);
                        $slip = $s_info ? $s_info['slip_image'] : null;
                        $method = $s_info ? $s_info['payment_method'] : 'Transfer';
                        
                        $tx_time = date('H:i:s');
                        $stmt_tx = $conn->prepare("INSERT INTO transactions (booking_id, amount, type, description, method, image_path, transaction_time) VALUES (?, ?, 'income', 'รับชำระค่าห้องพัก (Approve)', ?, ?, ?)");
                        $stmt_tx->execute([$booking_id, $b_info['total_price'], $method, $slip, $tx_time]);
                    }
                    echo "<script>window.location.href='$return_url';</script>";
                }
            }

        // ==========================================
        // 9. ยืนยันทำความสะอาดเสร็จสิ้น (MARK CLEAN)
        // ==========================================
        } elseif ($action == 'mark_clean') {
            $room_id = (int)$_POST['room_id'];
            $stmt = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
            if ($stmt->execute([$room_id])) echo "<script>window.location.href='$return_url';</script>";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
