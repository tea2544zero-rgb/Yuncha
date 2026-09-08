<?php
require_once 'config/security.php';
require_once 'config/db.php';

// บังคับใช้วันนี้เสมอสำหรับการเช็คอิน-เช็คเอาท์หน้าเคาน์เตอร์
$today = date('Y-m-d');

// ดึงข้อมูลวันหยุดจาก settings
$stmt_h = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'holiday_dates'");
$holiday_dates_str = $stmt_h->fetchColumn();
$holiday_dates_arr = [];
if($holiday_dates_str) {
    $parts = explode(',', $holiday_dates_str);
    foreach($parts as $p) {
        $holiday_dates_arr[] = trim($p);
    }
}

// =========================================================================
// ðŸš€ ระบบจัดการสถานะโดยพนักงานต้อนรับ (Check-in, Check-out, No-Show, Undo)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $b_id = (int)(isset($_POST['booking_id']) ? $_POST['booking_id'] :  0);
    
    if($action === 'check_in') {
        $stmt_chk = $conn->prepare("SELECT check_in FROM bookings WHERE id=?");
        $stmt_chk->execute([$b_id]);
        $book_in = $stmt_chk->fetchColumn();
        
        $allowed_time = $book_in . ' 14:00:00';
        $current_time = date('Y-m-d H:i:s');
        
        if ($current_time < $allowed_time) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'ยังไม่ถึงเวลาเช็คอิน! (สามารถเช็คอินได้ตั้งแต่ 14:00 น. ของวันที่เข้าพักเป็นต้นไป)',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
            exit;
        }

        $stmt = $conn->prepare("UPDATE bookings SET status='checked_in' WHERE id=?");
        $stmt->execute([$b_id]);
        header("Location: frontdesk.php?success=checkin"); exit;
    } elseif($action === 'approve') {
        $stmt = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=?");
        $stmt->execute([$b_id]);
        
        // --- ADD: Sync to Finance Ledger ---
        $stmt_b = $conn->prepare("SELECT b.booking_ref, b.room_id, b.total_price, c.first_name, c.last_name, p.payment_method, p.slip_image FROM bookings b JOIN customers c ON b.customer_id = c.id LEFT JOIN payments p ON b.id = p.booking_id WHERE b.id = ?");
        $stmt_b->execute([$b_id]);
        $book_data = $stmt_b->fetch(PDO::FETCH_ASSOC);
        if($book_data) {
            $tx_notes = "ผู้จอง: {$book_data['first_name']} {$book_data['last_name']} | จองห้องพัก ID: {$book_data['room_id']}";
            $stmt_chk = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE reference_no = ?");
            $stmt_chk->execute([$book_data['booking_ref']]);
            if($stmt_chk->fetchColumn() == 0) {
                $stmt_tx = $conn->prepare("INSERT INTO transactions (transaction_type, category, amount, vat_amount, net_amount, payment_method, reference_no, notes, transaction_date, transaction_time, created_by, image_path) VALUES ('income', 'ค่าห้องพัก', ?, 0, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_tx->execute([$book_data['total_price'], $book_data['total_price'], isset($book_data['payment_method']) ? $book_data['payment_method'] :  'Bank Transfer', $book_data['booking_ref'], date('Y-m-d'), date('H:i:s'), isset($_SESSION['emp_code']) ? $_SESSION['emp_code'] :  'SYSTEM', $book_data['slip_image']]);
            }
        }
        header("Location: frontdesk.php?success=approve"); exit;
    } elseif($action === 'check_out') {
        $stmt = $conn->prepare("UPDATE bookings SET status='checked_out' WHERE id=?");
        $stmt->execute([$b_id]);
        
        $stmt_r = $conn->prepare("UPDATE rooms SET status='cleaning' WHERE id = (SELECT room_id FROM bookings WHERE id=?)");
        $stmt_r->execute([$b_id]);
        
        header("Location: frontdesk.php?success=checkout"); exit;
    } elseif($action === 'mark_clean') {
        $r_id = (int)(isset($_POST['room_id']) ? $_POST['room_id'] :  0);
        $stmt = $conn->prepare("UPDATE rooms SET status='available' WHERE id=?");
        $stmt->execute([$r_id]);
        header("Location: frontdesk.php?success=clean"); exit;
    } elseif($action === 'undo_check_in') {
        $stmt = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=?");
        $stmt->execute([$b_id]);
        header("Location: frontdesk.php?success=undo"); exit;
    } elseif($action === 'undo_check_out') {
        $stmt = $conn->prepare("UPDATE bookings SET status='checked_in' WHERE id=?");
        $stmt->execute([$b_id]);
        
        $stmt_r = $conn->prepare("UPDATE rooms SET status='available' WHERE id = (SELECT room_id FROM bookings WHERE id=?)");
        $stmt_r->execute([$b_id]);
        
        header("Location: frontdesk.php?success=undo"); exit;
    } elseif($action === 'delete_walkin' || $action === 'delete') {
        $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=?");
        $stmt->execute([$b_id]);
        
        // --- Remove from transactions so it doesn't duplicate revenue ---
        $stmt_ref = $conn->prepare("SELECT booking_ref FROM bookings WHERE id=?");
        $stmt_ref->execute([$b_id]);
        $ref = $stmt_ref->fetchColumn();
        if($ref) {
            $conn->prepare("DELETE FROM transactions WHERE reference_no = ?")->execute([$ref]);
            $conn->prepare("DELETE FROM payments WHERE booking_id = ?")->execute([$b_id]);
        }
        
        header("Location: frontdesk.php?success=delete"); exit;
    } elseif($action === 'edit') {
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $stmt = $conn->prepare("UPDATE bookings SET check_in=?, check_out=? WHERE id=?");
        $stmt->execute([$check_in, $check_out, $b_id]);
        header("Location: frontdesk.php?success=edit"); exit;
    } elseif($action === 'add') {
        // ðŸŒŸ รับลูกค้า Walk-in (บันทึกเข้าระบบจองและเช็คอินทันที)
        $room_id = (int)$_POST['room_id'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $adults = (int)$_POST['adults'];
        $extra_bed = isset($_POST['add_extra_bed']) ? 1 : 0;
        $total_price = (float)$_POST['total_price'];
        $payment_method = $_POST['payment_method'];
        $status = isset($_POST['force_status']) ? $_POST['force_status'] :  'confirmed'; 
        
        $customer_type = isset($_POST['customer_type']) ? $_POST['customer_type'] :  'existing';
        $customer_id = 0;
        
        if($customer_type == 'new') {
            $fname = $_POST['new_fname'];
            $lname = $_POST['new_lname'];
            $phone = $_POST['new_phone'];
            $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, phone, auth_provider, status) VALUES (?, ?, ?, 'local', 'active')");
            $stmt->execute([$fname, $lname, $phone]);
            $customer_id = $conn->lastInsertId();
        } else {
            $search_str = isset($_POST['customer_search']) ? $_POST['customer_search'] :  '';
            if(preg_match('/ID:(\d+)/', $search_str, $matches)) {
                $customer_id = (int)$matches[1];
            }
        }
        
        $print_id = 0;
        if($customer_id > 0) {
            // ป้องกันการจองซ้อน (Double Booking Prevention)
            $stmt_check = $conn->prepare("SELECT id FROM bookings WHERE room_id = ? AND status != 'cancelled' AND check_in < ? AND check_out > ?");
            $stmt_check->execute([$room_id, $check_out, $check_in]);
            if($stmt_check->fetch()) {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'ขออภัย! ห้องพักช่วงวันดังกล่าวเพิ่งถูกจองไปเมื่อสักครู่นี้ กรุณาเลือกห้องอื่นหรือเปลี่ยนวันครับ',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
                exit;
            }

            $booking_ref = 'WK' . strtoupper(substr(md5(uniqid()), 0, 6));
            $sql = "INSERT INTO bookings (booking_ref, customer_id, room_id, check_in, check_out, adults, extra_bed, total_price, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if($stmt->execute([$booking_ref, $customer_id, $room_id, $check_in, $check_out, $adults, $extra_bed, $total_price, $status])) {
                $print_id = $conn->lastInsertId();
                
                $slip_path = null;
                if(isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == 0) {
                    $upload_dir = "../uploads/slips/";
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
                    $new_name = 'slip_' . uniqid() . '.' . $ext;
                    if(move_uploaded_file($_FILES['slip_image']['tmp_name'], $upload_dir . $new_name)) {
                        $slip_path = "uploads/slips/" . $new_name;
                    }
                }

                $stmt_pay = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, status, slip_image) VALUES (?, ?, ?, 'completed', ?)");
                $stmt_pay->execute([$print_id, $total_price, $payment_method, $slip_path]);
                
                // --- Sync to Finance Ledger (transactions table) ---
                $tx_notes = "ผู้จอง: " . (isset($fname) ? $fname :  'ลูกค้าเก่า ID:'.$customer_id) . " | จองห้องพัก ID: $room_id";
                $emp_code = isset($_SESSION['emp_code']) ? $_SESSION['emp_code'] :  'SYSTEM';
                $tx_date = date('Y-m-d');
                $tx_time = date('H:i:s');
                
                $stmt_tx = $conn->prepare("INSERT INTO transactions (transaction_type, category, amount, vat_amount, net_amount, payment_method, reference_no, notes, transaction_date, transaction_time, created_by, image_path) VALUES ('income', 'ค่าห้องพัก', ?, 0, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_tx->execute([$total_price, $total_price, $payment_method, $booking_ref, $tx_notes, $tx_date, $tx_time, $emp_code, $slip_path]);
            }
        }
        
        $print_query = (isset($_POST['print_receipt']) && $print_id > 0) ? "&print=$print_id" : "";
        header("Location: frontdesk.php?success=add" . $print_query);
        exit;
    }
}

// =========================================================================
// ðŸ“Š ดึงข้อมูลสำหรับหน้า Front Desk 
// =========================================================================
$sql_customers = "SELECT id, first_name, last_name, phone FROM customers WHERE status = 'active' ORDER BY first_name ASC";
$res_customers = $conn->query($sql_customers);
$result_all_customers = $res_customers ? $res_customers->fetchAll(PDO::FETCH_ASSOC) : [];

// ดึงข้อมูลการจองใหม่รอตรวจสอบ (Pending)
$sql_pending_noti = "SELECT b.id, b.booking_ref, b.check_in, b.check_out, r.room_number, c.first_name, c.last_name 
                     FROM bookings b 
                     JOIN rooms r ON b.room_id = r.id 
                     JOIN customers c ON b.customer_id = c.id 
                     WHERE b.status = 'pending' 
                     ORDER BY b.id DESC";
$res_pending_noti = $conn->query($sql_pending_noti);
$pending_notis = $res_pending_noti ? $res_pending_noti->fetchAll(PDO::FETCH_ASSOC) : [];
$pending_count = count($pending_notis);

$sql_all_future = "SELECT id, room_id, check_in, check_out FROM bookings WHERE status != 'cancelled' AND check_out > '$today'";
$res_all_future = $conn->query($sql_all_future);
$all_room_bookings = [];
if($res_all_future) {
    while($row = $res_all_future->fetch(PDO::FETCH_ASSOC)) {
        $all_room_bookings[$row['room_id']][] = [
            'id' => $row['id'],
            'check_in' => $row['check_in'],
            'check_out' => $row['check_out']
        ];
    }
}

$sql_rooms = "SELECT t.id as type_id, t.type_name, r.id as room_id, r.room_number, r.status as room_status, r.base_price, r.high_price, r.holiday_price, r.discount_percent, r.allow_extra_bed, r.extra_bed_price, r.max_guests 
              FROM room_types t 
              LEFT JOIN rooms r ON t.id = r.room_type_id 
              ORDER BY t.base_price DESC, r.room_number ASC";
$result_rooms = $conn->query($sql_rooms);
$hotel_data = [];
$total_rooms = 0;

if($result_rooms) {
    while($row = $result_rooms->fetch(PDO::FETCH_ASSOC)) {
        $tid = $row['type_id'];
        if(!isset($hotel_data[$tid])) {
            $hotel_data[$tid] = ['name' => $row['type_name'], 'rooms' => []];
        }
        if($row['room_id'] && $row['room_status'] != 'deleted') {
            $hotel_data[$tid]['rooms'][] = $row;
            $total_rooms++; 
        }
    }
}

// ดึงข้อมูลการจอง "ของวันนี้"
$sql_bookings = "SELECT b.id as booking_id, b.booking_ref, b.room_id, b.check_in, b.check_out, b.adults, b.extra_bed, b.total_price, b.status as booking_status, c.first_name, c.last_name, c.phone, c.email, r.room_number, p.slip_image 
                 FROM bookings b 
                 JOIN customers c ON b.customer_id = c.id 
                 JOIN rooms r ON b.room_id = r.id
                 LEFT JOIN payments p ON b.id = p.booking_id
                 WHERE b.check_in <= '$today' AND b.check_out >= '$today' 
                 AND b.status IN ('pending', 'confirmed', 'checked_in', 'checked_out')
                 ORDER BY CASE WHEN b.status = 'checked_out' THEN 1 WHEN b.status = 'confirmed' THEN 2 WHEN b.status = 'checked_in' THEN 3 WHEN b.status = 'pending' THEN 4 ELSE 5 END ASC";
$result_bookings = $conn->query($sql_bookings);

$today_bookings = [];
$checkout_today_list = [];
$arrivals = [];
$departures = [];
$inhouse = [];

if($result_bookings) {
    while($b = $result_bookings->fetch(PDO::FETCH_ASSOC)) {
        $today_bookings[$b['room_id']] = $b;
        
        if($b['booking_status'] === 'confirmed' && $b['check_in'] <= $today) {
            $arrivals[] = $b;
        } elseif($b['booking_status'] === 'pending' && $b['check_in'] <= $today) {
            $arrivals[] = $b; // ให้ Pending โผล่ในช่องรอเช็คอินด้วย
        } elseif($b['booking_status'] === 'checked_in') {
            $inhouse[] = $b;
            if($b['check_out'] === $today) {
                $departures[] = $b;
                $checkout_today_list[] = $b['room_number'];
            }
        }
    }
}

$count_waiting = count($arrivals);
$count_occupied = count($inhouse);

$avail_now = 0;
foreach($hotel_data as $tid => $type) {
    foreach($type['rooms'] as $room) {
        $rid = $room['room_id'];
        if($room['room_status'] == 'available' && !isset($today_bookings[$rid])) {
            $avail_now++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เคาน์เตอร์ต้อนรับ (Front Desk) | <?= htmlspecialchars($hotel_name) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 
                        ycDeep: '#000000', ycSurface: '#0f0f0f', 
                        ycGreen: '#00ff41', ycGold: '#ffcc00', 
                        ycBlue: '#00d0ff', ycPink: '#ff009d', ycRed: '#ff003c', ycOrange: '#ff5e00', ycMint: '#00e676', ycPurple: '#b537f2'
                    },
                    fontFamily: { sans: ['Prompt', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>

    <style>
        /* Flatpickr Customization for Neon Theme */
        .flatpickr-calendar.dark { background: #0f0f0f !important; border: 1px solid #333 !important; box-shadow: 0 0 20px rgba(193,0,241,0.2) !important; }
        .flatpickr-day.selected { background: #c100f1 !important; border-color: #c100f1 !important; }
        .flatpickr-day:hover { background: #333 !important; }
        .flatpickr-months .flatpickr-month { background: #050505 !important; }
        .flatpickr-current-month .flatpickr-monthDropdown-months { background: #050505 !important; }
        .flatpickr-weekdays { background: #050505 !important; }
        span.flatpickr-weekday { background: #050505 !important; }
        body { background-color: #000000; color: #ffffff; font-family: 'Prompt', sans-serif; overflow-x: hidden; position: relative;}
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #000000; }
        ::-webkit-scrollbar-thumb { background: #ff009d; border-radius: 10px; }

        .stars-container { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .star { position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0; animation: fall linear infinite; box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.8); }
        @keyframes fall { 0% { transform: translateY(-10vh) translateX(0) scale(1); opacity: 1; } 100% { transform: translateY(110vh) translateX(-20vw) scale(0); opacity: 0; } }

        .neon-pro { position: relative; background: rgba(15,15,15,0.8); backdrop-filter: blur(10px); border-radius: 1rem; z-index: 1; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 0 0 1px rgba(255,255,255,0.05); }
        .neon-pro-glow { position: absolute; inset: -1px; border-radius: 1.1rem; z-index: -2; overflow: hidden; opacity: 0; transition: opacity 0.3s ease; }
        .neon-pro-glow::before { content: ''; position: absolute; top: 50%; left: 50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 70%, var(--neon-color) 100%); transform: translate(-50%, -50%); animation: spin-border 2s linear infinite; }
        .neon-pro::before { content: ''; position: absolute; inset: 0; background: #0f0f0f; border-radius: 1rem; z-index: -1; }
        @keyframes spin-border { 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        .neon-pro:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 15px var(--neon-color); z-index: 10; }
        .neon-pro:hover .neon-pro-glow { opacity: 1; }
        .icon-glow { transition: all 0.3s; color: #a3a3a3; }
        .neon-pro:hover .icon-glow { color: var(--neon-color) !important; filter: drop-shadow(0 0 8px var(--neon-color)); transform: scale(1.15); }
        
        .input-dark { background-color: #050505; border: 1px solid #333; color: white; padding: 0.65rem 1rem; border-radius: 0.75rem; outline: none; transition: all 0.3s; width: 100%; appearance: textfield; }
        .input-dark:focus { border-color: var(--neon-color); box-shadow: 0 0 10px rgba(255,0,157,0.2); }
        .input-error { border-color: #ff003c !important; color: #ff003c !important; box-shadow: 0 0 15px rgba(255,0,60,0.4) !important; background-color: #1a0505 !important; }

        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { appearance: none; margin: 0; }

        .neon-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
        .neon-switch input { opacity: 0; width: 0; height: 0; }
        .neon-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #151515; transition: .3s; border-radius: 26px; border: 1px solid #333; }
        .neon-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #555; transition: .3s; border-radius: 50%; }
        input:checked + .neon-slider { background-color: rgba(255,0,157, 0.1); border-color: #ff009d; box-shadow: 0 0 10px rgba(255,0,157, 0.2); }
        input:checked + .neon-slider:before { transform: translateX(22px); background-color: #ff009d; box-shadow: 0 0 10px #ff009d; }
        .radio-btn-group input[type="radio"]:checked + div { background: rgba(255,0,157,0.1); border-color: #ff009d; color: #ff009d; }

        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(255, 0, 60, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(255, 0, 60, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 0, 60, 0); } }
        .alert-pulse { animation: pulse-red 2s infinite; }
        
        @keyframes shake {
          0%, 100% { transform: rotate(0deg); }
          25% { transform: rotate(15deg); }
          75% { transform: rotate(-15deg); }
        }
    </style>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #111; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #444; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #666; }
    </style>
</head>
<body class="h-screen flex selection:bg-ycPink selection:text-black">

    <div class="stars-container" id="starsBox"></div>
    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden w-full relative bg-[#050505]">
        
                <header class="h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 flex justify-between items-center z-30 sticky top-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg text-ycBlue border border-gray-800"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-black text-white tracking-wide">FRONT <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00d0ff] to-[#ff00ff]">DESK</span></h2>
                    <p class="text-sm text-gray-400 font-medium">เคาน์เตอร์ต้อนรับส่วนหน้า</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-4">เวลาปัจจุบัน: <span id="clockStatus" style="color: #00d0ff;"><?= date('H:i:s') ?></span></p>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth" id="main-content-wrapper">
            <div class="max-w-7xl mx-auto space-y-6 pb-12">
                
                <div class="bg-blue-900/20 border border-ycBlue/40 rounded-xl p-4 flex flex-col sm:flex-row items-center gap-4 gs-anim">
                    <div class="w-12 h-12 rounded-full bg-ycBlue/20 text-ycBlue flex items-center justify-center shrink-0 shadow-[0_0_15px_rgba(0,208,255,0.2)]">
                        <i class="ph-bold ph-info text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-lg mb-1">กฎและเวลาทำการ (Operation Hours)</h4>
                        <p class="text-sm text-gray-300">
                            <i class="ph-fill ph-arrow-circle-right text-ycGreen"></i> เช็คอินตั้งแต่ <span class="text-ycGreen font-bold">14:00 น.</span> | 
                            <i class="ph-fill ph-arrow-circle-left text-ycRed ml-2"></i> เช็คเอาท์ภายใน <span class="text-ycRed font-bold">12:00 น.</span>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 gs-anim">
                    <div class="bg-[#0f0f0f] border border-ycGreen/30 rounded-2xl p-5 shadow-[0_0_15px_rgba(0,255,65,0.05)] relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-ycGreen"><i class="ph-fill ph-door-open text-8xl"></i></div>
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">ห้องว่างพร้อมขาย (Walk-in)</p>
                        <h3 class="text-4xl font-black text-ycGreen"><?= $avail_now ?> <span class="text-lg text-gray-500">ห้อง</span></h3>
                    </div>
                    
                    <div class="bg-[#0f0f0f] border border-ycGold/30 rounded-2xl p-5 shadow-[0_0_15px_rgba(255,204,0,0.05)] relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-ycGold"><i class="ph-fill ph-suitcase-rolling text-8xl"></i></div>
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">รอดำเนินการเช็คอิน</p>
                        <h3 class="text-4xl font-black text-ycGold"><?= count($arrivals) ?> <span class="text-lg text-gray-500">ห้อง</span></h3>
                    </div>

                    <div class="bg-[#0f0f0f] border border-ycBlue/30 rounded-2xl p-5 shadow-[0_0_15px_rgba(0,208,255,0.05)] relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-ycBlue"><i class="ph-fill ph-bed text-8xl"></i></div>
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">กำลังพักอยู่ (In-house)</p>
                        <h3 class="text-4xl font-black text-ycBlue"><?= count($inhouse) ?> <span class="text-lg text-gray-500">ห้อง</span></h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 gs-anim">
                    
                    <div>
                        <div class="flex justify-between items-center mb-4 border-b border-gray-800 pb-2">
                            <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="ph-fill ph-arrow-circle-right text-ycGold"></i> รอเช็คอินวันนี้ <span class="bg-ycGold text-black text-xs px-2 py-0.5 rounded-full font-black"><?= count($arrivals) ?></span></h3>
                        </div>
                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #ffcc00 #111;">
                            <?php if(empty($arrivals)): ?>
                                <div class="text-center py-10 bg-[#0f0f0f] border border-gray-800 rounded-xl">
                                    <i class="ph-duotone ph-check-circle text-4xl text-gray-600 mb-2"></i>
                                    <p class="text-gray-400 font-bold">ไม่มีรายการรอเช็คอิน</p>
                                </div>
                            <?php else: foreach($arrivals as $a): ?>
                                <div class="bg-[#0f0f0f] border border-gray-800 hover:border-ycGold rounded-xl p-4 transition-all group shadow-lg">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span class="text-ycGold font-black text-2xl tracking-widest drop-shadow-[0_0_5px_rgba(255,204,0,0.5)]"><?= htmlspecialchars(isset($a['room_number']) ? $a['room_number'] :  '') ?></span>
                                            <p class="text-xs text-gray-500 font-mono mt-0.5">Ref: <?= isset($a['booking_ref']) ? $a['booking_ref'] :  '-' ?></p>
                                        </div>
                                        <button onclick="openActionModal('<?= $a['booking_status'] == 'pending' ? 'delete' : (strpos($a['booking_ref'], 'WK') === 0 ? 'delete_walkin' : '') ?>', '<?= $a['booking_id'] ?>', '<?= htmlspecialchars(isset($a['room_number']) ? $a['room_number'] :  '') ?>', '<?= htmlspecialchars(trim((isset($a['first_name']) ? $a['first_name'] : '') . ' ' . (isset($a['last_name']) ? $a['last_name'] : '')), ENT_QUOTES) ?>', '<?= htmlspecialchars(isset($a['phone']) ? $a['phone'] :  '', ENT_QUOTES) ?>')" class="<?= ($a['booking_status'] != 'pending' && strpos($a['booking_ref'], 'WK') !== 0) ? 'hidden' : '' ?> text-xs font-bold text-gray-500 hover:text-ycRed transition underline"><i class="ph-bold <?= $a['booking_status'] == 'pending' ? 'ph-x-circle' : 'ph-trash' ?>"></i> <?= $a['booking_status'] == 'pending' ? 'ปฏิเสธ/ยกเลิก' : 'ยกเลิก (กดผิด)' ?></button>
                                    </div>
                                    <div class="bg-[#111] rounded-lg p-3 mb-3 border border-gray-800">
                                        <p class="text-sm font-bold text-white mb-1"><i class="ph-fill ph-user text-gray-500"></i> <?= htmlspecialchars(trim((isset($a['first_name']) ? $a['first_name'] :  '') . ' ' . (isset($a['last_name']) ? $a['last_name'] :  ''))) ?></p>
                                        <p class="text-xs text-gray-400"><i class="ph-fill ph-phone text-gray-500"></i> <?= htmlspecialchars(isset($a['phone']) ? $a['phone'] :  '-') ?></p>
                                        <div class="mt-2 pt-2 border-t border-gray-800 flex justify-between items-center text-xs">
                                            <span class="text-gray-400">ผู้เข้าพัก: <span class="text-white font-bold"><?= $a['adults'] ?></span> คน <?= $a['extra_bed'] ? '(+เตียงเสริม)' : '' ?></span>
                                            <?php if(!empty($a['slip_image'])): ?>
                                            <a href="../<?= htmlspecialchars($a['slip_image']) ?>" target="_blank" class="text-ycBlue hover:text-white transition underline font-bold"><i class="ph-bold ph-image"></i> ดูสลิปโอนเงิน</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-bold text-gray-400">ยอดสุทธิ: <span class="text-white">฿<?= number_format(isset($a['total_price']) ? $a['total_price'] :  0) ?></span></span>
                                        <?php if($a['booking_status'] == 'pending'): ?>
                                            <button onclick="openActionModal('approve', '<?= $a['booking_id'] ?>', '<?= htmlspecialchars(isset($a['room_number']) ? $a['room_number'] :  '') ?>', '<?= htmlspecialchars(trim((isset($a['first_name']) ? $a['first_name'] : '') . ' ' . (isset($a['last_name']) ? $a['last_name'] : '')), ENT_QUOTES) ?>', '<?= htmlspecialchars(isset($a['phone']) ? $a['phone'] :  '', ENT_QUOTES) ?>')" class="px-5 py-2 bg-ycPink text-white font-black rounded-lg hover:bg-pink-600 transition shadow-[0_0_15px_rgba(255,0,157,0.3)] flex items-center gap-1 animate-pulse">
                                                <i class="ph-bold ph-check-circle"></i> ยืนยันชำระเงิน
                                            </button>
                                        <?php else: ?>
                                            <button onclick="openActionModal('check_in', '<?= $a['booking_id'] ?>', '<?= htmlspecialchars(isset($a['room_number']) ? $a['room_number'] :  '') ?>', '<?= htmlspecialchars(trim((isset($a['first_name']) ? $a['first_name'] : '') . ' ' . (isset($a['last_name']) ? $a['last_name'] : '')), ENT_QUOTES) ?>', '<?= htmlspecialchars(isset($a['phone']) ? $a['phone'] :  '', ENT_QUOTES) ?>')" class="px-5 py-2 bg-ycGold text-black font-black rounded-lg hover:bg-yellow-400 transition shadow-[0_0_15px_rgba(255,204,0,0.3)] flex items-center gap-1">
                                                <i class="ph-bold ph-sign-in"></i> เช็คอิน
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-4 border-b border-gray-800 pb-2">
                            <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="ph-fill ph-arrow-circle-left text-ycRed"></i> ต้องเช็คเอาท์วันนี้ <span class="bg-ycRed text-white text-xs px-2 py-0.5 rounded-full font-black"><?= count($departures) ?></span></h3>
                        </div>
                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #ff003c #111;">
                            <?php if(empty($departures)): ?>
                                <div class="text-center py-10 bg-[#0f0f0f] border border-gray-800 rounded-xl">
                                    <i class="ph-duotone ph-check-circle text-4xl text-gray-600 mb-2"></i>
                                    <p class="text-gray-400 font-bold">ไม่มีคิว หรือ เช็คเอาท์ครบหมดแล้ว</p>
                                </div>
                            <?php else: foreach($departures as $d): ?>
                                <div class="bg-[#0f0f0f] border border-gray-800 hover:border-ycRed rounded-xl p-4 transition-all group shadow-lg">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span class="text-ycRed font-black text-2xl tracking-widest drop-shadow-[0_0_5px_rgba(255,0,60,0.5)]"><?= htmlspecialchars(isset($d['room_number']) ? $d['room_number'] :  '') ?></span>
                                            <p class="text-xs text-gray-500 font-mono mt-0.5">Ref: <?= isset($d['booking_ref']) ? $d['booking_ref'] :  '-' ?></p>
                                        </div>
                                    </div>
                                    <div class="bg-[#111] rounded-lg p-3 mb-3 border border-gray-800">
                                        <p class="text-sm font-bold text-white mb-1"><i class="ph-fill ph-user text-gray-500"></i> <?= htmlspecialchars(trim((isset($d['first_name']) ? $d['first_name'] :  '') . ' ' . (isset($d['last_name']) ? $d['last_name'] :  ''))) ?></p>
                                        <p class="text-xs text-gray-400"><i class="ph-fill ph-phone text-gray-500"></i> <?= htmlspecialchars(isset($d['phone']) ? $d['phone'] :  '-') ?></p>
                                        <div class="mt-2 pt-2 border-t border-gray-800 flex justify-between items-center text-xs">
                                            <span class="text-gray-400">ผู้เข้าพัก: <span class="text-white font-bold"><?= $d['adults'] ?></span> คน <?= $d['extra_bed'] ? '(+เตียงเสริม)' : '' ?></span>
                                            <?php if(!empty($d['slip_image'])): ?>
                                            <a href="../<?= htmlspecialchars($d['slip_image']) ?>" target="_blank" class="text-ycBlue hover:text-white transition underline font-bold"><i class="ph-bold ph-image"></i> ดูสลิปโอนเงิน</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <a href="receipt.php?id=<?= $d['booking_id'] ?>" target="_blank" class="text-sm font-bold text-ycBlue hover:text-white transition underline"><i class="ph-bold ph-printer"></i> พิมพ์ใบเสร็จ</a>
                                        <?php if(isset($d['booking_status']) && $d['booking_status'] == 'confirmed'): ?>
                                        <?php else: ?>
                                            <button onclick="openActionModal('check_out', '<?= $d['booking_id'] ?>', '<?= htmlspecialchars(isset($d['room_number']) ? $d['room_number'] :  '') ?>', '<?= htmlspecialchars(trim((isset($d['first_name']) ? $d['first_name'] : '') . ' ' . (isset($d['last_name']) ? $d['last_name'] : '')), ENT_QUOTES) ?>', '<?= htmlspecialchars(isset($d['phone']) ? $d['phone'] :  '', ENT_QUOTES) ?>')" class="px-5 py-2 bg-ycRed text-white font-black rounded-lg hover:bg-red-700 transition shadow-[0_0_15px_rgba(255,0,60,0.3)] flex items-center gap-1">
                                                <i class="ph-bold ph-sign-out"></i> เช็คเอาท์
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                </div>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 pb-2 border-b border-gray-800 gap-4 mt-8 gs-anim">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="ph-fill ph-house-line text-gray-400"></i> แผนผังห้องพักประจำวัน (Room Rack)</h3>
                    <div class="relative w-full sm:w-72">
                        <i class="ph-bold ph-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                        <input type="text" id="roomSearchInput" onkeyup="searchRooms()" placeholder="ค้นหาเลขห้อง, ชื่อ หรือเบอร์โทรลูกค้า..." class="input-dark pl-10 text-sm" style="--neon-color: #ff009d;">
                    </div>
                </div>

                <?php 
                if(empty($hotel_data)) {
                    echo '<div class="text-center py-20 text-gray-600"><p class="text-xl font-bold">ไม่พบข้อมูลห้องพัก</p></div>';
                } else {
                    foreach($hotel_data as $tid => $type) { 
                        $tname = $type['name'];
                        $colorHex = '#ffffff'; 
                        $subtitle = '';
                        if(stripos($tname, 'Tea Valley') !== false) { 
                            $colorHex = '#00ff41'; 
                            $subtitle = 'วิวไร่ชา'; 
                        } elseif(stripos($tname, 'The Peak Pavilion') !== false) { 
                            $colorHex = '#ffcc00'; 
                            $subtitle = 'วิวภูเขา'; 
                        } elseif(stripos($tname, 'Tea Pavilion') !== false || stripos($tname, 'Lakefront') !== false) { 
                            $colorHex = '#00d0ff'; 
                            $subtitle = 'วิวแม่น้ำ'; 
                        } elseif(stripos($tname, 'Garden') !== false) { 
                            $colorHex = '#ff00ff'; 
                        }
                ?>
                <div class="mb-8 gs-anim room-category-section">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-4">
                        <h4 class="text-3xl font-black" style="color: <?= $colorHex ?>; text-shadow: 0 0 10px <?= $colorHex ?>33;"><?= htmlspecialchars($tname) ?></h4>
                        <?php if($subtitle): ?>
                        <span class="bg-[#111] border border-gray-800 text-gray-300 px-3 py-1 rounded-full text-sm font-bold shadow-inner w-fit"><?= $subtitle ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach($type['rooms'] as $room) { 
                            $rid = $room['room_id'];
                            $rnum = $room['room_number'];
                            $rstatus = $room['room_status'];
                            $base_price = $room['base_price'];
                            $high_price = $room['high_price'];
                            $holiday_price = $room['holiday_price'];
                            $discount = isset($room['discount_percent']) ? $room['discount_percent'] :  0;
                            $allow_extra = $room['allow_extra_bed'];
                            $extra_price = $room['extra_bed_price'];
                            $max_guests = $room['max_guests'];
                            $booking = isset($today_bookings[$rid]) ? $today_bookings[$rid] :  null;
                            
                            // ถ้าห้องว่างแล้ว และเป็นแค่การเช็คเอาท์ของวันนี้ ให้ซ่อนข้อมูลเพื่อเปิดรับห้องว่างเลย
                            if($booking && $booking['booking_status'] == 'checked_out' && ($rstatus == 'available' || $rstatus == 'active')) {
                                $booking = null;
                            }
                            
                            $cusName = '';
                            $cusPhone = '';
                            if($booking) {
                                $cusName = htmlspecialchars(trim((isset($booking['first_name']) ? $booking['first_name'] :  '') . ' ' . (isset($booking['last_name']) ? $booking['last_name'] :  '')), ENT_QUOTES, 'UTF-8');
                                $cusPhone = htmlspecialchars(isset($booking['phone']) ? $booking['phone'] :  '', ENT_QUOTES, 'UTF-8');
                            }
                            
                            $cardBorder = 'border-gray-800 bg-[#050505]';
                            $statusBadge = '<span class="text-gray-500 text-xs font-bold uppercase"><i class="ph-fill ph-check-circle text-ycGreen"></i> ว่างพร้อมขาย</span>';
                            
                            if($rstatus == 'maintenance') {
                                $cardBorder = 'border-orange-900/50 border-dashed bg-[#151000] opacity-50';
                                $statusBadge = '<span class="text-orange-500 text-xs font-bold uppercase"><i class="ph-fill ph-wrench"></i> ซ่อมบำรุง</span>';
                            } elseif($rstatus == 'cleaning') {
                                $cardBorder = 'border-ycPink/50 border-dashed bg-[#150010] opacity-80';
                                $statusBadge = '<span class="text-ycPink text-xs font-bold uppercase"><i class="ph-fill ph-broom"></i> กำลังทำความสะอาด</span>';
                            } elseif($booking) {
                                if($booking['booking_status'] == 'pending') {
                                    $cardBorder = 'border-ycPink/50 bg-[#1a0010] shadow-[0_0_15px_rgba(255,0,157,0.2)]';
                                    $statusBadge = '<span class="bg-ycPink text-white px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider animate-pulse"><i class="ph-bold ph-star"></i> NEW!!!</span>';
                                } elseif($booking['booking_status'] == 'confirmed') {
                                    if ($booking['check_in'] < $today) {
                                        $cardBorder = 'border-orange-500/50 bg-[#150500] shadow-[0_0_15px_rgba(255,165,0,0.1)]';
                                        $statusBadge = '<span class="bg-orange-500 text-white px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider">เลยกำหนด / ยังไม่มา</span>';
                                    } else {
                                        $cardBorder = 'border-ycGold/50 bg-[#151300] shadow-[0_0_15px_rgba(255,204,0,0.1)]';
                                        $statusBadge = '<span class="bg-ycGold text-black px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider">รอเช็คอิน</span>';
                                    }
                                } elseif($booking['booking_status'] == 'checked_in') {
                                    $cardBorder = 'border-ycBlue/50 bg-[#001015] shadow-[0_0_15px_rgba(0,208,255,0.1)]';
                                    $statusBadge = '<span class="bg-ycBlue text-black px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider">กำลังพัก (In-house)</span>';
                                } elseif($booking['booking_status'] == 'checked_out') {
                                    $cardBorder = 'border-gray-800 bg-[#050505] opacity-60';
                                    $statusBadge = '<span class="bg-gray-800 text-gray-400 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider">เช็คเอาท์แล้ว</span>';
                                }
                            }
                        ?>
                        <div class="room-card border <?= $cardBorder ?> rounded-xl p-4 flex flex-col justify-between transition-all duration-300">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-2xl font-black text-white tracking-widest"><?= htmlspecialchars($rnum) ?></span>
                                    <?= $statusBadge ?>
                                </div>
                                <div class="mb-3 text-gray-500 text-xs font-bold"><i class="ph-fill ph-users"></i> รองรับ <?= $max_guests ?> ท่าน</div>
                                
                                <?php if($booking) { ?>
                                    <div class="mb-4">
                                        <p class="text-sm font-bold text-gray-200 truncate"><i class="ph-fill ph-user text-gray-500"></i> <?= $cusName ?></p>
                                        <p class="text-[10px] text-gray-400 font-mono mt-1"><i class="ph-fill ph-phone text-gray-500"></i> <?= $cusPhone ? $cusPhone : '-' ?></p>
                                        <p class="text-[10px] text-gray-400 font-mono"><i class="ph-fill ph-envelope text-gray-500"></i> <?= htmlspecialchars(isset($booking['email']) ? $booking['email'] : '-') ?></p>
                                        
                                        <div class="mt-2 flex items-center gap-1.5 bg-[#0a0a0a] border border-gray-800 rounded px-2 py-1 w-fit">
                                            <span class="text-[10px] text-ycGreen font-bold">เข้า: <?= date('d/m/y', strtotime($booking['check_in'])) ?></span>
                                            <i class="ph-bold ph-arrow-right text-gray-600 text-[10px]"></i>
                                            <span class="text-[10px] text-ycRed font-bold">ออก: <?= date('d/m/y', strtotime($booking['check_out'])) ?></span>
                                        </div>

                                        <div class="flex items-center gap-2 mt-2 text-xs font-bold bg-[#111] w-fit px-2 py-1 rounded border border-gray-800">
                                            <span class="text-gray-400"><i class="ph-fill ph-users"></i> <?= $booking['adults'] ?> ท่าน</span>
                                            <?php if($booking['extra_bed'] == 1) { ?>
                                                <span class="text-ycPink border-l border-gray-700 pl-2">+ เตียงเสริม</span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <?php if($rstatus == 'available' || $rstatus == 'active'): ?>
                                        <button onclick="openBookingModal('<?= $rid ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $base_price ?>', '<?= $high_price ?>', '<?= $holiday_price ?>', '<?= $discount ?>', '<?= $allow_extra ?>', '<?= $extra_price ?>', '<?= $max_guests ?>')" class="h-[100px] w-full flex flex-col items-center justify-center border-2 border-dashed border-gray-800 hover:border-ycPink hover:text-ycPink rounded-lg text-gray-600 text-sm font-bold transition-colors group cursor-pointer" style="hover:border-color:#ff009d; hover:color:#ff009d;">
                                            <i class="ph-bold ph-user-plus text-2xl mb-1 group-hover:scale-110 transition-transform"></i> 
                                            <span>รับลูกค้า Walk-in</span>
                                        </button>
                                    <?php elseif($rstatus == 'cleaning'): ?>
                                        <div class="h-[100px] flex flex-col items-center justify-center rounded-lg bg-[#150010] border border-ycPink/50 gap-2 p-2">
                                            <i class="ph-fill ph-broom text-ycPink text-2xl animate-pulse"></i>
                                            <form method="POST" action="frontdesk.php" class="w-full">
                                                <input type="hidden" name="action" value="mark_clean">
                                                <input type="hidden" name="room_id" value="<?= $rid ?>">
                                                <button type="submit" class="w-full text-[10px] font-bold text-white bg-ycPink/20 border border-ycPink hover:bg-ycPink rounded py-1 transition">
                                                    ทำความสะอาดเสร็จ
                                                </button>
                                            </form>
                                        </div>

                                    <?php else: ?>
                                        <div class="h-[100px] flex flex-col items-center justify-center rounded-lg bg-[#0a0a0a] text-gray-600 text-sm font-medium border border-gray-800">
                                            <i class="ph-fill ph-lock-key text-xl mb-1"></i> ระงับการใช้งาน
                                        </div>
                                    <?php endif; ?>
                                <?php } ?>
                            </div>

                            <?php if($booking) { ?>
                                <?php if($rstatus == 'cleaning') { ?>
                                    <div class="h-[100px] flex flex-col justify-center items-center p-2 rounded-lg bg-[#150010] border border-ycPink/50">
                                        <form method="POST" action="frontdesk.php" class="w-full mb-1">
                                            <input type="hidden" name="action" value="mark_clean">
                                            <input type="hidden" name="room_id" value="<?= $rid ?>">
                                            <button type="submit" class="w-full text-[10px] font-bold text-white bg-ycPink border border-ycPink hover:bg-pink-600 rounded py-1 transition shadow-[0_0_10px_rgba(255,0,157,0.4)] flex items-center justify-center gap-1">
                                                <i class="ph-bold ph-broom animate-pulse"></i> ทำความสะอาดเสร็จ
                                            </button>
                                        </form>
                                        <?php if($booking['booking_status'] == 'checked_out') { ?>
                                        <button type="button" onclick="openActionModal('undo_check_out', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>')" class="text-ycBlue hover:text-white text-xs font-bold transition-colors underline mt-1">
                                            <i class="ph-bold ph-arrow-u-up-left"></i> ยกเลิกเช็คเอาท์ (กดผิด)
                                        </button>
                                        <?php } else { ?>
                                        <span class="text-[10px] text-gray-500 text-center leading-tight mt-1">ลูกค้ารอเช็คอิน: <br><span class="text-ycGold font-bold truncate max-w-[150px] inline-block"><?= $cusName ?></span></span>
                                        <?php } ?>
                                    </div>
                                <?php } elseif($booking['booking_status'] == 'pending') { ?>
                                    <div class="mt-3 flex gap-2">
                                        <button type="button" onclick="openActionModal('approve', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>', '<?= htmlspecialchars(isset($booking['slip_image']) ? $booking['slip_image'] :  '') ?>', '<?= number_format($booking['total_price']) ?>')" class="flex-1 py-2 rounded-lg bg-ycPink/10 border border-ycPink/50 text-ycPink hover:bg-ycPink hover:text-white font-bold text-sm transition-colors flex items-center justify-center gap-1 animate-pulse">
                                            <i class="ph-bold ph-check-circle"></i> ยืนยันชำระเงิน
                                        </button>
                                        <button type="button" onclick="openActionModal('delete', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>')" class="px-3 py-2 rounded-lg bg-gray-800 hover:bg-red-600 hover:text-white text-gray-400 font-bold text-xs transition-colors flex items-center justify-center" title="ปฏิเสธสลิป / ลบการจอง">
                                            <i class="ph-bold ph-x-circle text-lg"></i>
                                        </button>
                                    </div>
                                <?php } elseif($booking['booking_status'] == 'confirmed') { ?>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="openActionModal('check_in', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>')" class="flex-1 py-2 rounded-lg bg-ycGreen/10 border border-ycGreen/50 text-ycGreen hover:bg-ycGreen hover:text-black font-bold text-sm transition-colors flex items-center justify-center gap-1">
                                            <i class="ph-bold ph-sign-in"></i> เช็คอิน
                                        </button>
                                        <?php if(strpos($booking['booking_ref'], 'WK') === 0): ?>
                                        <button type="button" onclick="openActionModal('delete_walkin', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>')" class="px-3 py-2 rounded-lg bg-gray-800 hover:bg-red-600 hover:text-white text-gray-400 font-bold text-xs transition-colors flex items-center justify-center" title="ยกเลิก Walk-in (กดผิด)">
                                            <i class="ph-bold ph-trash text-lg"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>

                                <?php } elseif($booking['booking_status'] == 'checked_in') { ?>
                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2">
                                            <a href="receipt.php?id=<?= $booking['booking_id'] ?>" target="_blank" class="flex-1 py-2 rounded-lg bg-gray-800 hover:bg-gray-700 text-white text-center font-bold text-sm transition-colors flex items-center justify-center gap-1">
                                                <i class="ph-bold ph-printer"></i> ใบเสร็จ
                                            </a>
                                            <button type="button" onclick="openActionModal('check_out', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>')" class="flex-1 w-full py-2 rounded-lg bg-ycRed/10 border border-ycRed/50 text-ycRed hover:bg-ycRed hover:text-white font-bold text-sm transition-colors flex items-center justify-center gap-1">
                                                <i class="ph-bold ph-sign-out"></i> เช็คเอาท์
                                            </button>
                                        </div>
                                        <?php if(strpos($booking['booking_ref'], 'WK') === 0): ?>
                                        <button type="button" onclick="openActionModal('delete_walkin', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>')" class="w-full py-1 text-gray-500 hover:text-ycRed text-xs font-bold transition-colors text-center underline">
                                            <i class="ph-bold ph-trash"></i> ยกเลิก Walk-in (กดผิด)
                                        </button>
                                        <?php else: ?>
                                        <button type="button" onclick="openActionModal('undo_check_in', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>')" class="w-full py-1 text-gray-500 hover:text-ycGold text-xs font-bold transition-colors text-center underline">
                                            <i class="ph-bold ph-arrow-u-up-left"></i> ยกเลิกการเช็คอิน (กดผิด)
                                        </button>
                                        <?php endif; ?>
                                    </div>

                                <?php } elseif($booking['booking_status'] == 'checked_out') { ?>
                                    <div class="h-[100px] flex flex-col justify-center items-center p-2">
                                        <button type="button" onclick="openActionModal('undo_check_out', '<?= $booking['booking_id'] ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $cusName ?>', '<?= $cusPhone ?>')" class="text-ycBlue hover:text-white text-xs font-bold transition-colors underline mt-1">
                                            <i class="ph-bold ph-arrow-u-up-left"></i> ยกเลิกการเช็คเอาท์ (กดผิด)
                                        </button>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } } ?>

            </div>
            
            <div id="checkout-data" class="hidden"><?= json_encode($checkout_today_list) ?></div>
        </main>
    </div>

    <div id="bookingModal" class="fixed inset-0 bg-black/90 z-[100] hidden flex items-center justify-center backdrop-blur-sm overflow-y-auto pt-10 pb-10">
        <div class="bg-[#0f0f0f] w-[95%] md:w-full max-w-4xl max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-gray-800 shadow-[0_0_30px_rgba(255,0,157,0.15)] relative my-auto">
            
            <div class="flex justify-between items-center p-6 border-b border-gray-800 bg-[#0a0a0a] rounded-t-2xl sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-[0_0_10px_rgba(255,0,157,0.3)]" style="background-color: rgba(255,0,157,0.2); color:#ff009d;">
                        <i class="ph-bold ph-user-plus text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white drop-shadow-md">รับลูกค้า Walk-in</h3>
                </div>
                <button type="button" onclick="closeBookingModal()" class="w-8 h-8 bg-[#1a1a1a] hover:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 transition">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <form action="frontdesk.php" method="POST" id="formAddBooking" class="p-6 space-y-6" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="force_status" value="confirmed"> 
                <input type="hidden" name="room_id" id="bookRoomId">
                <input type="hidden" id="bookRoomPrice">
                <input type="hidden" id="bookHighPrice">
                <input type="hidden" id="bookHolidayPrice">
                <input type="hidden" id="bookRoomDiscount" value="0">
                <input type="hidden" id="bookExtraPrice">
                <input type="hidden" id="bookMaxGuests"> 
                
                <div class="bg-[#151515] rounded-xl p-4 border border-gray-800 flex justify-between items-center">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">ห้องที่เลือก</p>
                        <h4 class="text-2xl font-black text-white" id="bookRoomNumberDisplay">-</h4>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1 flex items-center justify-end gap-2">
                            ราคาปกติ/คืน 
                            <span id="bookRoomDiscountBadge" class="bg-ycBlue/20 text-ycBlue px-2 py-0.5 rounded text-[10px] font-black hidden">ลด %</span>
                        </p>
                        <h4 class="text-xl font-bold text-ycGold" id="bookRoomPriceDisplay">฿0.00</h4>
                    </div>
                </div>

                <div>
                    <input type="hidden" name="customer_type" value="new">
                    <div id="newCustomerDiv" class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border border-gray-800 rounded-xl bg-[#0a0a0a]">
                        <div><input type="text" name="new_fname" class="input-dark" style="--neon-color: #ff009d;" placeholder="ชื่อจริง"></div>
                        <div><input type="text" name="new_lname" class="input-dark" style="--neon-color: #ff009d;" placeholder="นามสกุล"></div>
                        <div><input type="tel" name="new_phone" class="input-dark" style="--neon-color: #ff009d;" placeholder="เบอร์โทรศัพท์ (08X-XXX-XXXX)"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 border-t border-gray-800 pt-5">
                    <div class="relative">
                        <label class="block text-sm font-bold text-gray-400 mb-2">วันที่เช็คอิน <span class="text-ycRed">*</span></label>
                        <input type="date" name="check_in" id="bookCheckIn" required class="input-dark w-full" onchange="updateCheckOut('book'); calculatePrice('book');" style="--neon-color: #ff009d;" value="<?= $today ?>">
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-bold text-gray-400 mb-2">วันที่เช็คเอาท์ <span class="text-ycRed">*</span></label>
                        <input type="date" name="check_out" id="bookCheckOut" required class="input-dark w-full" style="--neon-color: #ff009d;" onchange="calculatePrice('book')">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">ผู้เข้าพัก (ท่าน) <span class="text-ycRed">*</span></label>
                        <div class="bg-[#111] border border-gray-700 rounded-xl p-2.5 text-center text-white font-bold text-lg shadow-inner" id="bookAdultsDisplay">
                            2 ท่าน
                        </div>
                        <input type="hidden" name="adults" id="bookAdults" value="2">
                        <p class="text-xs text-gray-500 mt-1" id="bookMaxGuestText">รองรับ 2 ท่าน</p>
                    </div>
                </div>
                
                <p id="bookDateWarning" class="text-ycRed text-sm font-bold mt-1 hidden"><i class="ph-bold ph-warning-circle"></i> ห้องนี้มีการจองในวันดังกล่าวแล้ว กรุณาเปลี่ยนวันเช็คเอาท์</p>

                <div class="mt-2 bg-[#111] p-3 rounded-lg border border-gray-800">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2"><i class="ph-fill ph-calendar-x text-ycRed"></i> วันที่ห้องนี้ไม่ว่างล่วงหน้า</p>
                    <div id="bookBookedDatesDisplay" class="flex flex-wrap gap-2 text-sm font-medium"></div>
                </div>

                <div id="bookExtraBedWrap" class="hidden bg-[#0a0a0a] border border-gray-800 rounded-xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <p class="text-white font-bold"><i class="ph-fill ph-bed text-ycGreen mr-2"></i> จำนวนเตียงเสริม</p>
                        <p class="text-xs text-gray-500 mt-1">บวกเพิ่ม <span id="bookDisplayExtraPriceText" class="text-ycGreen font-bold">฿0</span> / คืน / เตียง</p>
                    </div>
                    <div class="flex items-center gap-3 bg-[#111] p-2 pr-4 rounded-xl border border-gray-800">
                        <label class="neon-switch">
                            <input type="checkbox" name="add_extra_bed" id="bookAddExtraBed" value="1" onchange="updateAdultsLimit('book'); calculatePrice('book');">
                            <span class="neon-slider"></span>
                        </label>
                        <span class="text-sm font-bold text-gray-300">เพิ่มเตียงเสริม</span>
                    </div>
                </div>

                <div class="border-t border-gray-800 pt-5">
                    <h4 class="text-lg font-bold text-white mb-4"><i class="ph-fill ph-wallet text-ycBlue mr-2"></i> ข้อมูลการชำระเงิน</h4>
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">ช่องทางชำระเงินหน้าเคาน์เตอร์ <span class="text-ycRed">*</span></label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="Cash" checked class="peer sr-only" onchange="toggleSlipUpload()">
                                <div class="border border-gray-800 rounded-xl p-3 text-center text-sm font-bold text-gray-400 peer-checked:border-ycBlue peer-checked:text-ycBlue peer-checked:bg-[#001015] hover:bg-[#111] transition">
                                    <i class="ph-bold ph-money mr-1"></i> เงินสด
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="Bank Transfer" class="peer sr-only" onchange="toggleSlipUpload()">
                                <div class="border border-gray-800 rounded-xl p-3 text-center text-sm font-bold text-gray-400 peer-checked:border-ycBlue peer-checked:text-ycBlue peer-checked:bg-[#001015] hover:bg-[#111] transition">
                                    <i class="ph-bold ph-bank mr-1"></i> โอนเงิน
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="Credit Card" class="peer sr-only" onchange="toggleSlipUpload()">
                                <div class="border border-gray-800 rounded-xl p-3 text-center text-sm font-bold text-gray-400 peer-checked:border-ycBlue peer-checked:text-ycBlue peer-checked:bg-[#001015] hover:bg-[#111] transition">
                                    <i class="ph-bold ph-credit-card mr-1"></i> บัตรเครดิต
                                </div>
                            </label>
                        </div>
                        
                        <div id="slipUploadWrap" class="hidden mt-4 bg-[#111] border border-gray-800 p-4 rounded-xl">
                            <label class="block text-sm font-bold text-gray-300 mb-2"><i class="ph-bold ph-image text-ycPink mr-1"></i> แนบสลิปโอนเงิน (ถ้ามี)</label>
                            <input type="file" name="slip_image" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-[#1a0010] file:text-ycPink hover:file:bg-[#2a001a] transition cursor-pointer">
                        </div>
                    </div>
                    
                    <div class="mt-5 flex items-center gap-4 bg-[#111] p-3 rounded-xl border border-gray-800 w-fit">
                        <label class="neon-switch">
                            <input type="checkbox" name="print_receipt" value="1" checked>
                            <span class="neon-slider"></span>
                        </label>
                        <span class="text-sm font-bold text-gray-300">สร้างและเปิดหน้าใบเสร็จทันที</span>
                    </div>
                </div>

                <div class="border border-gray-800 rounded-xl p-5 mt-4 flex flex-col md:flex-row md:justify-between items-start md:items-center gap-2 shadow-[0_0_15px_rgba(255,0,157,0.1)]" style="background-color: rgba(255,0,157,0.1); border-color: rgba(255,0,157,0.3);">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest" style="color: #ff009d;">ราคาสุทธิ (Total Price)</p>
                        <p class="text-xs text-gray-300 mt-1" id="bookNightsDisplay">คำนวณราคา...</p>
                    </div>
                    <div class="text-left md:text-right">
                        <input type="hidden" name="total_price" id="bookInputTotalPrice" value="0">
                        <h2 class="text-4xl font-black drop-shadow-[0_0_10px_rgba(255,0,157,0.5)]" style="color: #ff009d;">฿<span id="bookDisplayTotalPrice">0.00</span></h2>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end pt-6 border-t border-gray-800 gap-3">
                    <button type="button" onclick="closeBookingModal()" class="neon-pro w-full sm:w-auto px-8 py-3 flex justify-center items-center gap-2" style="--neon-color: #a3a3a3;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-x text-xl text-gray-300 relative z-10 icon-glow"></i>
                        <span class="font-bold text-gray-300 relative z-10">ปิดหน้าต่าง</span>
                    </button>
                    <button type="submit" id="bookBtnSubmit" class="neon-pro w-full sm:w-auto px-10 py-3 flex justify-center items-center gap-2 opacity-50 cursor-not-allowed" style="--neon-color: #ff009d;" disabled>
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-check-circle text-xl text-white relative z-10 icon-glow"></i>
                        <span class="font-bold text-white relative z-10">บันทึก Walk-in</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="actionModal" class="fixed inset-0 bg-black/90 z-50 hidden flex items-center justify-center backdrop-blur-sm">
        <div class="bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border shadow-xl p-5 text-center transform scale-95 transition-transform duration-300" id="actionModalContent">
            
            <div id="actionModalIconWrap" class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3">
                <i id="actionModalIcon" class="ph-fill text-3xl"></i>
            </div>
            
            <h3 class="text-xl font-black text-white mb-3" id="actionModalTitle">ยืนยันการดำเนินการ</h3>
            
            <div class="bg-[#111] border border-gray-800 rounded-xl p-3 mb-3 text-left shadow-inner">
                <p class="text-xl text-white font-black mb-2 text-center">ห้อง <span id="actionRoomNum" class="text-3xl tracking-wider"></span></p>
                <div class="border-t border-gray-800 pt-3">
                    <p class="text-sm text-gray-300 mb-1"><i class="ph-fill ph-user text-gray-500 mr-2"></i> ลูกค้า: <span id="actionCusName" class="font-bold text-white text-base"></span></p>
                    <p class="text-xs text-gray-400 mb-1"><i class="ph-fill ph-phone text-gray-500 mr-2"></i> โทร: <span id="actionCusPhone" class="font-bold text-white"></span></p>
                    <p class="text-xs text-gray-400 mb-1"><i class="ph-fill ph-envelope-simple text-gray-500 mr-2"></i> อีเมล: <span id="actionCusEmail" class="font-bold text-white"></span></p>
                    <div class="mt-2 pt-2 border-t border-gray-800 flex justify-between text-xs text-gray-400">
                        <span><i class="ph-fill ph-users text-gray-500"></i> ผู้เข้าพัก: <span id="actionAdults" class="text-white font-bold"></span> คน</span>
                        <span><i class="ph-fill ph-bed text-gray-500"></i> เตียงเสริม: <span id="actionExtraBed" class="text-white font-bold"></span> เตียง</span>
                    </div>
                </div>
            </div>

            <div id="actionSlipContainer" class="hidden mb-4 bg-black p-2 rounded-xl border border-gray-800">
            </div>

            <p class="text-gray-400 font-medium mb-6 text-sm" id="actionModalDesc"></p>
            
            <form action="frontdesk.php" method="POST" class="flex gap-3" onsubmit="return validateActionSubmit(event)">
                <input type="hidden" name="action" id="actionModalInput">
                <input type="hidden" name="booking_id" id="actionBookingId">
                <button type="button" onclick="closeActionModal()" class="flex-1 py-3 rounded-xl border border-gray-700 text-gray-400 font-bold hover:bg-[#111] hover:text-white transition">ยกเลิก</button>
                <button type="submit" id="actionSubmitBtn" class="flex-1 py-3 rounded-xl text-white font-bold transition flex items-center justify-center gap-2">
                    <i class="ph-bold ph-check-circle"></i> ยืนยัน
                </button>
            </form>
        </div>
    </div>

    <div id="alertTimeModal" class="fixed inset-0 bg-black/90 z-[60] hidden flex items-center justify-center backdrop-blur-sm">
        <div class="bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border p-5 text-center transform scale-95 transition-transform duration-300" id="alertTimeContent">
            <div id="alertTimeIconWrap" class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3">
                <i id="alertTimeIcon" class="ph-fill ph-bell-ringing text-5xl"></i>
            </div>
            <h3 class="text-2xl font-black text-white mb-2" id="alertTimeTitle">แจ้งเตือน</h3>
            <div class="bg-[#111] border border-gray-800 rounded-lg p-4 mb-6 text-left">
                <p class="text-gray-300 font-medium whitespace-pre-line text-sm leading-relaxed" id="alertTimeMessage"></p>
            </div>
            <button type="button" onclick="closeTimeAlert()" class="w-full py-3 rounded-xl bg-gray-800 text-white font-bold hover:bg-gray-700 transition">รับทราบแล้ว</button>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div id="successAlert" class="fixed top-5 right-5 z-[200] bg-[#0a0a0a] border border-ycGreen text-ycGreen shadow-[0_0_15px_rgba(0,255,65,0.3)] px-6 py-4 rounded-xl font-bold flex items-center gap-3 gs-anim-alert">
        <i class="ph-bold ph-check-circle text-2xl"></i>
        <span id="successMsgText">ดำเนินการเสร็จสิ้นเรียบร้อย</span>
    </div>
    <script>
        const successType = "<?= $_GET['success'] ?>";
        const msgEl = document.getElementById('successMsgText');
        if(successType === 'checkin') msgEl.innerText = "เช็คอินลูกค้าเรียบร้อยแล้ว";
        else if(successType === 'checkout') msgEl.innerText = "เช็คเอาท์เรียบร้อย คืนห้องสู่ระบบแล้ว";

        else if(successType === 'undo') msgEl.innerText = "ย้อนกลับสถานะสำเร็จ";
        else if(successType === 'add') msgEl.innerText = "สร้างรายการ Walk-in สำเร็จ";
        else if(successType === 'approve') msgEl.innerText = "ยืนยันชำระเงินเรียบร้อย สถานะเป็น Confirmed";
        
        setTimeout(() => { document.getElementById('successAlert').style.display = 'none'; }, 3000);
        window.history.replaceState({}, document.title, window.location.pathname);
    </script>
    <?php endif; ?>

    <?php if(isset($_GET['print'])): ?>
    <script>
        window.open('receipt.php?id=<?= (int)$_GET['print'] ?>', '_blank');
    </script>
    <?php endif; ?>

    <script>
        const allBookings = <?= json_encode($all_room_bookings) ?>;
        const holidayDates = <?= json_encode($holiday_dates_arr) ?>;
        
        function isDateConflict(roomId, checkInStr, checkOutStr, currentBookingId = null) {
            if(!allBookings[roomId]) return false; 
            let cIn = new Date(checkInStr);
            let cOut = new Date(checkOutStr);
            for(let i = 0; i < allBookings[roomId].length; i++) {
                let b = allBookings[roomId][i];
                if(currentBookingId && b.id == currentBookingId) continue; 
                let bIn = new Date(b.check_in);
                let bOut = new Date(b.check_out);
                if(cIn < bOut && cOut > bIn) { return true; }
            }
            return false;
        }

        function generateBookedDatesHTML(roomId, currentBookingId = null) {
            let datesHtml = '';
            if(allBookings[roomId]) {
                let sortedBookings = allBookings[roomId].sort((a,b) => new Date(a.check_in) - new Date(b.check_in));
                sortedBookings.forEach(b => {
                    if(currentBookingId && b.id == currentBookingId) return; 
                    let bin = new Date(b.check_in).toLocaleDateString('en-GB'); 
                    let bout = new Date(b.check_out).toLocaleDateString('en-GB');
                    datesHtml += `<span class="bg-red-900/40 border border-red-500/50 text-red-500 px-3 py-1.5 rounded-md text-xs font-bold tracking-wider">${bin} ถึง ${bout}</span>`;
                });
            }
            return datesHtml || '<span class="text-gray-500 text-sm italic">ไม่มีการจองล่วงหน้า (ห้องว่างตลอด)</span>';
        }

        function enforceMaxGuests(prefix) {
            let input = document.getElementById(prefix + 'Adults');
            let maxBase = parseInt(document.getElementById(prefix + 'MaxGuests').value) || 2;
            let extraWrap = document.getElementById(prefix + 'ExtraBedWrap');
            let allowExtra = extraWrap && !extraWrap.classList.contains('hidden');
            let maxAllowed = allowExtra ? maxBase + 1 : maxBase;
            
            let val = parseInt(input.value);
            if(val > maxAllowed) { input.value = maxAllowed; val = maxAllowed; }
            if(val < 1) { input.value = 1; val = 1; }
            
            if (allowExtra) {
                let extraCheckbox = document.getElementById(prefix + 'AddExtraBed');
                if (extraCheckbox) {
                    extraCheckbox.checked = (val > maxBase);
                }
            }
            updateAdultsLimit(prefix);
            calculatePrice(prefix);
        }

        function toggleSlipUpload() {
            const method = document.querySelector('input[name="payment_method"]:checked').value;
            const slipWrap = document.getElementById('slipUploadWrap');
            if (method === 'Bank Transfer') {
                slipWrap.classList.remove('hidden');
            } else {
                slipWrap.classList.add('hidden');
            }
        }

        function updateAdultsLimit(prefix) {
            let maxBase = parseInt(document.getElementById(prefix + 'MaxGuests').value) || 2;
            let extraInput = document.getElementById(prefix + 'AddExtraBed');
            let extraBeds = (extraInput && extraInput.checked) ? 1 : 0;
            let newMax = maxBase + extraBeds;
            
            let adultInput = document.getElementById(prefix + 'Adults');
            let adultDisplay = document.getElementById(prefix + 'AdultsDisplay');
            if (adultInput) adultInput.value = newMax;
            if (adultDisplay) adultDisplay.innerText = newMax + " ท่าน";
            
            let textElem = document.getElementById(prefix + 'MaxGuestText');
            if (textElem) {
                textElem.innerText = `รองรับ ${newMax} ท่าน` + (extraBeds > 0 ? ' (รวมเตียงเสริม)' : '');
            }
        }
    </script>

    <script>
                const bookingDetailsMap = {};
        <?php
        $all_for_json = array_merge(array_values(isset($today_bookings) ? $today_bookings : []), isset($arrivals) ? $arrivals : [], isset($departures) ? $departures : []);
        foreach($all_for_json as $bk) {
            if(isset($bk['booking_id'])) {
                echo "bookingDetailsMap['" . $bk['booking_id'] . "'] = { phone: '" . addslashes(isset($bk['phone']) ? $bk['phone'] :  '') . "', email: '" . addslashes(isset($bk['email']) ? $bk['email'] :  '-') . "', adults: '" . (isset($bk['adults']) ? $bk['adults'] :  '0') . "', extra_bed: '" . (isset($bk['extra_bed']) ? $bk['extra_bed'] :  '0') . "' };\n";
            }
        }
        ?>

        // จำตำแหน่ง Scroll
        document.addEventListener("DOMContentLoaded", function() { 
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if (sessionStorage.getItem('sidebarScrollPos') && sidebarEl) sidebarEl.scrollTop = sessionStorage.getItem('sidebarScrollPos');
        });
        window.onbeforeunload = function() {
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if(sidebarEl) sessionStorage.setItem('sidebarScrollPos', sidebarEl.scrollTop);
        };

        // ðŸŒŸ ระบบค้นหาห้องพัก/ลูกค้า
        function searchRooms() {
            const input = document.getElementById('roomSearchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.room-card');
            
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                if(text.includes(input)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // ตรวจสอบว่าในหมวดหมู่นั้นมีห้องโชว์อยู่ไหม ถ้าไม่มีให้ซ่อนหัวข้อหมวดหมู่
            document.querySelectorAll('.room-category-section').forEach(section => {
                const visibleCards = section.querySelectorAll('.room-card[style=""]');
                const visibleCards2 = section.querySelectorAll('.room-card:not([style*="display: none"])');
                if(visibleCards.length === 0 && visibleCards2.length === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = '';
                }
            });
        }

        // ดาวตก
        function createStars() {
            const box = document.getElementById('starsBox');
            for(let i=0; i<30; i++) {
                let star = document.createElement('div');
                star.className = 'star';
                star.style.left = `${Math.random() * 100}vw`;
                star.style.animationDuration = `${Math.random() * 3 + 2}s`;
                star.style.animationDelay = `${Math.random() * 5}s`;
                box.appendChild(star);
            }
        }
        createStars();

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        function toggleSidebar() { sidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); }
        document.getElementById('open-sidebar').addEventListener('click', toggleSidebar);
        document.getElementById('close-sidebar').addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // ปิด Dropdown เมื่อคลิกที่อื่น
        document.addEventListener('click', function(e) {
            const btn = document.getElementById('notiBtn');
            const dropdown = document.getElementById('notiDropdown');
            if(btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        const bookingModal = document.getElementById('bookingModal');

        // ðŸŒŸ รีเฟรชอัตโนมัติแบบ ไม่กระตุก (Silent Refresh ด้วย Fetch API)
        let idleTime = 0;
        let isSilentRefreshing = false;
        
        window.onload = resetIdle;
        window.onmousemove = resetIdle;
        window.onkeypress = resetIdle;
        function resetIdle() { idleTime = 0; }
        
        async function silentRefresh(manual = false) {
            if(isSilentRefreshing) return;
            
            let isActionModalOpen = !document.getElementById('actionModal').classList.contains('hidden');
            let isAlertModalOpen = !document.getElementById('alertTimeModal').classList.contains('hidden');
            let isBookingModalOpen = !bookingModal.classList.contains('hidden');
            
            if(isActionModalOpen || isAlertModalOpen || isBookingModalOpen) return;

            isSilentRefreshing = true;
            if(manual) {
                const icon = document.getElementById('refreshIcon');
                icon.classList.add('animate-spin', 'text-ycPink');
            }

            try {
                const response = await fetch(window.location.href);
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newMain = doc.getElementById('main-content-wrapper');
                if(newMain) {
                    document.getElementById('main-content-wrapper').innerHTML = newMain.innerHTML;
                    
                    const newData = doc.getElementById('checkout-data');
                    if(newData) {
                        pendingCheckOutRooms = JSON.parse(newData.innerText);
                    }
                    
                    // รักษาสถานะช่องค้นหา
                    const searchVal = document.getElementById('roomSearchInput').value;
                    if(searchVal) searchRooms();
                }
            } catch(e) {
                console.error("Silent refresh error: ", e);
            } finally {
                isSilentRefreshing = false;
                if(manual) {
                    setTimeout(() => {
                        const icon = document.getElementById('refreshIcon');
                        if(icon) icon.classList.remove('animate-spin', 'text-ycPink');
                    }, 500);
                }
            }
        }

        setInterval(function() {
            idleTime += 1;
            const clockEl = document.getElementById('clockStatus');
            if (clockEl) {
                const now = new Date();
                clockEl.innerText = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }

            if (idleTime >= 60) {
                silentRefresh();
                idleTime = 0; // reset
            }
        }, 1000);

        // ==========================================
        // ðŸŒŸ ระบบเวลา และการแจ้งเตือนอัจฉริยะตอนเที่ยงตรง
        // ==========================================
        let pendingCheckOutRooms = <?= json_encode($checkout_today_list) ?>;

        function checkCheckoutAlerts() {
            if (pendingCheckOutRooms.length === 0) return; 

            const now = new Date();
            const h = now.getHours();
            const m = now.getMinutes();
            const dateStr = now.toISOString().split('T')[0];

            if (h === 11 && m >= 0 && m <= 5) {
                let alertKey = 'alert_11_' + dateStr;
                if (!localStorage.getItem(alertKey)) {
                    let msg = "ห้องต่อไปนี้ยังไม่เช็คเอาท์ (เหลือเวลาอีก 1 ชั่วโมง):\n\nðŸ‘‰ " + pendingCheckOutRooms.join(", ");
                    showTimeAlert("â° แจ้งเตือน 11:00 น.", msg, "#ffcc00");
                    localStorage.setItem(alertKey, 'true');
                }
            }

            if (h === 12 && m >= 0 && m <= 5) {
                let alertKey = 'alert_12_' + dateStr;
                if (!localStorage.getItem(alertKey)) {
                    let msg = "ห้องต่อไปนี้เลยกำหนดเช็คเอาท์แล้ว กรุณาตรวจสอบและทวงคืนกุญแจทันที:\n\nðŸ‘‰ " + pendingCheckOutRooms.join(", ");
                    showTimeAlert("ðŸš¨ เลยเวลาเช็คเอาท์ 12:00 น.", msg, "#ff003c");
                    localStorage.setItem(alertKey, 'true');
                }
            }
        }

        function showTimeAlert(title, message, hexColor) {
            document.getElementById('alertTimeTitle').innerText = title;
            document.getElementById('alertTimeMessage').innerText = message;

            const modal = document.getElementById('alertTimeModal');
            const content = document.getElementById('alertTimeContent');
            const iconWrap = document.getElementById('alertTimeIconWrap');
            const icon = document.getElementById('alertTimeIcon');

            content.style.borderColor = hexColor;
            content.style.boxShadow = `0 0 30px ${hexColor}40`;
            iconWrap.style.backgroundColor = `${hexColor}33`;
            iconWrap.style.boxShadow = `0 0 20px ${hexColor}4d`;
            icon.style.color = hexColor;
            icon.style.filter = `drop-shadow(0 0 10px ${hexColor})`;

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }

        function closeTimeAlert() {
            const modal = document.getElementById('alertTimeModal');
            const content = document.getElementById('alertTimeContent');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => { modal.classList.add('hidden'); }, 200);
        }

        function updateClock() { checkCheckoutAlerts(); }
        setInterval(updateClock, 1000);

        function toggleCustomerType() {
            const type = document.querySelector('input[name="customer_type"]:checked').value;
            if(type === 'new') {
                document.getElementById('existingCustomerDiv').classList.add('hidden');
                document.getElementById('customerSearchInput').removeAttribute('required');
                document.getElementById('newCustomerDiv').classList.remove('hidden');
                document.querySelector('input[name="new_fname"]').setAttribute('required', 'true');
                document.querySelector('input[name="new_lname"]').setAttribute('required', 'true');
                document.querySelector('input[name="new_phone"]').setAttribute('required', 'true');
            } else {
                document.getElementById('newCustomerDiv').classList.add('hidden');
                document.querySelector('input[name="new_fname"]').removeAttribute('required');
                document.querySelector('input[name="new_lname"]').removeAttribute('required');
                document.querySelector('input[name="new_phone"]').removeAttribute('required');
                document.getElementById('existingCustomerDiv').classList.remove('hidden');
                document.getElementById('customerSearchInput').setAttribute('required', 'true');
            }
        }

        function updateCheckOut(prefix) {
            let checkInInput = document.getElementById(prefix + 'CheckIn').value;
            if(checkInInput) {
                let checkOutDate = new Date(checkInInput);
                checkOutDate.setDate(checkOutDate.getDate() + 1); 
                document.getElementById(prefix + 'CheckOut').value = checkOutDate.toISOString().split('T')[0];
            }
            calculatePrice(prefix);
        }

        function calculatePrice(prefix) {
            const checkInInput = document.getElementById(prefix + 'CheckIn');
            const checkOutInput = document.getElementById(prefix + 'CheckOut');
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            
            const basePrice = parseFloat(document.getElementById(prefix + 'RoomPrice').value) || 0;
            const highPriceElem = document.getElementById(prefix + 'HighPrice');
            const holidayPriceElem = document.getElementById(prefix + 'HolidayPrice');
            const highPrice = highPriceElem && highPriceElem.value ? parseFloat(highPriceElem.value) : basePrice;
            const holidayPrice = holidayPriceElem && holidayPriceElem.value ? parseFloat(holidayPriceElem.value) : basePrice;
            const discount = parseFloat(document.getElementById(prefix + 'RoomDiscount').value) || 0;
            const extraPrice = parseFloat(document.getElementById(prefix + 'ExtraPrice').value) || 0;
            const extraInput = document.getElementById(prefix + 'AddExtraBed');
            const extraBeds = (extraInput && extraInput.checked) ? 1 : 0;
            const btnSubmit = document.getElementById(prefix + 'BtnSubmit');
            const warningText = document.getElementById(prefix + 'DateWarning');
            const roomId = document.getElementById(prefix + 'RoomId').value;

            const hasConflict = isDateConflict(roomId, checkInInput.value, checkOutInput.value, null);

            if(hasConflict) {
                warningText.classList.remove('hidden');
                checkInInput.classList.add('input-error');
                checkOutInput.classList.add('input-error');
                btnSubmit.disabled = true;
                btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
                document.getElementById(prefix + 'NightsDisplay').innerText = `เปลี่ยนวันเพื่อคำนวณ...`;
                document.getElementById(prefix + 'DisplayTotalPrice').innerText = "ERROR";
                return;
            } else {
                warningText.classList.add('hidden');
                checkInInput.classList.remove('input-error');
                checkOutInput.classList.remove('input-error');
            }

            if (checkIn && checkOut && checkOut > checkIn) {
                const diffTime = Math.abs(checkOut - checkIn);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                
                let typeTotal = 0;
                let weekdayCount = 0;
                let weekendCount = 0;
                let holidayCount = 0;
                
                let currentDate = new Date(checkIn);
                
                for (let i = 0; i < diffDays; i++) {
                    const dayOfWeek = currentDate.getDay();
                    const year = currentDate.getFullYear();
                    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
                    const day = String(currentDate.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${month}-${day}`;
                    const isHoliday = holidayDates.includes(dateStr);
                    
                    if (isHoliday && holidayPrice > 0) {
                        typeTotal += holidayPrice;
                        holidayCount++;
                    } else if (dayOfWeek === 5 || dayOfWeek === 6) {
                        typeTotal += highPrice;
                        weekendCount++;
                    } else {
                        typeTotal += basePrice;
                        weekdayCount++;
                    }
                    currentDate.setDate(currentDate.getDate() + 1);
                }

                let discountAmount = 0;
                if (discount > 0) {
                    discountAmount = Math.round(typeTotal * (discount / 100));
                    typeTotal -= discountAmount;
                }

                let extraBedTotal = 0;
                if (extraBeds > 0) {
                    extraBedTotal = diffDays * extraPrice * extraBeds;
                    typeTotal += extraBedTotal;
                }

                let summaryHtml = "";
                if (weekdayCount > 0) summaryHtml += "วันธรรมดา " + weekdayCount + " คืน ฿" + (weekdayCount * basePrice).toLocaleString() + "<br>";
                if (weekendCount > 0) summaryHtml += "ศุกร์-เสาร์ " + weekendCount + " คืน ฿" + (weekendCount * highPrice).toLocaleString() + "<br>";
                if (holidayCount > 0) summaryHtml += "วันหยุด " + holidayCount + " คืน ฿" + (holidayCount * holidayPrice).toLocaleString() + "<br>";
                if (discountAmount > 0) summaryHtml += "<span class=\"text-ycPink\">- ส่วนลด " + discount + "% ฿" + discountAmount.toLocaleString() + "</span><br>";
                if (extraBeds > 0) summaryHtml += "<span class=\"text-ycGreen\">+ เตียงเสริม (" + extraBeds + " เตียง) ฿" + extraBedTotal.toLocaleString() + "</span>";

                document.getElementById(prefix + "NightsDisplay").innerHTML = summaryHtml;
                document.getElementById(prefix + "DisplayTotalPrice").innerText = typeTotal.toLocaleString("en-US", {minimumFractionDigits: 2});
                document.getElementById(prefix + "InputTotalPrice").value = typeTotal;
                btnSubmit.disabled = false;
                btnSubmit.classList.remove("opacity-50", "cursor-not-allowed");
            } else {
                document.getElementById(prefix + 'NightsDisplay').innerText = `วันที่ไม่ถูกต้อง`;
                document.getElementById(prefix + 'DisplayTotalPrice').innerText = "0.00";
                document.getElementById(prefix + 'InputTotalPrice').value = 0;
                btnSubmit.disabled = true;
                btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        function openBookingModal(roomId = '', roomNumber = '', basePrice = 0, highPrice = 0, holidayPrice = 0, discount = 0, allowExtra = 0, extraPrice = 0, maxGuests = 2) {
            bookingModal.classList.remove('hidden');
            document.getElementById('bookRoomId').value = roomId;
            document.getElementById('bookRoomNumberDisplay').innerText = roomNumber ? `ห้อง ${roomNumber}` : '-';
            document.getElementById('bookRoomPrice').value = basePrice;
            document.getElementById('bookHighPrice').value = highPrice;
            document.getElementById('bookHolidayPrice').value = holidayPrice;
            document.getElementById('bookRoomDiscount').value = discount;
            document.getElementById('bookMaxGuests').value = maxGuests;
            
            let bPrice = parseFloat(basePrice);
            let dPercent = parseInt(discount);
            if (dPercent > 0) {
                let netPrice = bPrice - (bPrice * (dPercent / 100));
                document.getElementById('bookRoomPriceDisplay').innerHTML = `<span class="text-sm text-gray-500 line-through mr-2">฿${bPrice.toLocaleString()}</span> ฿${netPrice.toLocaleString()}`;
                document.getElementById('bookRoomDiscountBadge').innerText = `ลด ${dPercent}%`;
                document.getElementById('bookRoomDiscountBadge').classList.remove('hidden');
            } else {
                document.getElementById('bookRoomPriceDisplay').innerText = `฿${bPrice.toLocaleString()}`;
                document.getElementById('bookRoomDiscountBadge').classList.add('hidden');
            }

            document.getElementById('bookExtraPrice').value = extraPrice;
            const extraBedWrap = document.getElementById('bookExtraBedWrap');
            if(allowExtra == 1 && extraPrice > 0) {
                extraBedWrap.classList.remove('hidden');
                document.getElementById('bookDisplayExtraPriceText').innerText = `฿${parseFloat(extraPrice).toLocaleString()}`;
                document.getElementById('bookAddExtraBed').checked = false;
            } else {
                extraBedWrap.classList.add('hidden');
                document.getElementById('bookAddExtraBed').checked = false;
            }
            
            // หน้า Front Desk ให้ตั้งวันเช็คอินเป็นวันนี้เสมอ
            let todayObj = new Date();
            document.getElementById('bookCheckIn').value = todayObj.toISOString().split('T')[0];
            
            let outDate = new Date();
            outDate.setDate(outDate.getDate() + 1);
            document.getElementById('bookCheckOut').value = outDate.toISOString().split('T')[0];

            updateAdultsLimit('book'); 
            calculatePrice('book'); 
            document.getElementById('bookBookedDatesDisplay').innerHTML = generateBookedDatesHTML(roomId, null);
            document.querySelector('input[value="existing"]').click();
            toggleCustomerType();
            document.getElementById('customerSearchInput').value = '';
            document.querySelector('input[name="payment_method"][value="Cash"]').click();
        }
        
        function closeBookingModal() { bookingModal.classList.add('hidden'); }

        // ==========================================
        // ðŸŒŸ ระบบ Modal ยืนยันการกระทำ
        // ==========================================
        const actionModal = document.getElementById('actionModal');
        const actionContent = document.getElementById('actionModalContent');

        function openActionModal(action, bookingId, roomNum, cusName, cusPhone, slipImage = '', totalPrice = '0') {
            document.getElementById('actionBookingId').value = bookingId;
            document.getElementById('actionRoomNum').innerText = roomNum;
            document.getElementById('actionCusName').innerText = cusName;
            document.getElementById('actionModalInput').value = action;
            
            const bData = bookingDetailsMap[bookingId] || {};
            document.getElementById('actionCusPhone').innerText = bData.phone || cusPhone || '-';
            document.getElementById('actionCusEmail').innerText = bData.email || '-';
            document.getElementById('actionAdults').innerText = bData.adults || '0';
            document.getElementById('actionExtraBed').innerText = bData.extra_bed || '0';

            const title = document.getElementById('actionModalTitle');
            const desc = document.getElementById('actionModalDesc');
            const icon = document.getElementById('actionModalIcon');
            const iconWrap = document.getElementById('actionModalIconWrap');
            const submitBtn = document.getElementById('actionSubmitBtn');
            const roomSpan = document.getElementById('actionRoomNum');
            
            const slipContainer = document.getElementById('actionSlipContainer');

            icon.className = 'ph-fill text-5xl';
            let extraWarning = '';

            // Handle Slip Image
            if (action === 'approve') {
                if (slipImage) {
                    slipContainer.innerHTML = `
                        <p class="text-xs text-ycPink font-bold uppercase tracking-widest mb-2"><i class="ph-bold ph-receipt"></i> หลักฐานการโอนเงิน (ยอด ฿${totalPrice})</p>
                        <a href="../${slipImage}" target="_blank" class="block w-full h-36 bg-[#0a0a0a] rounded-lg overflow-hidden border border-gray-700 hover:border-ycPink transition-colors relative group">
                            <img src="../${slipImage}" alt="Payment Slip" class="w-full h-full object-contain opacity-80 group-hover:opacity-100 transition-opacity">
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="bg-ycPink text-white text-xs font-bold px-3 py-1.5 rounded-full"><i class="ph-bold ph-magnifying-glass-plus"></i> คลิกเพื่อดูรูปเต็ม</span>
                            </div>
                        </a>
                    `;
                } else {
                    slipContainer.innerHTML = `
                        <div class="py-6 border-2 border-dashed border-gray-700 rounded-lg bg-[#0a0a0a]">
                            <i class="ph-bold ph-image-broken text-3xl text-gray-500 mb-2"></i>
                            <p class="text-gray-400 text-sm font-bold">ไม่พบรูปสลิปแนบมา</p>
                            <p class="text-xs text-gray-500 mt-1">ยอดที่ต้องชำระ: ฿${totalPrice}</p>
                        </div>
                    `;
                }
                slipContainer.classList.remove('hidden');
            } else {
                slipContainer.innerHTML = '';
                slipContainer.classList.add('hidden');
            }

            // ถ้ายืนยันเช็คอินก่อนเวลา 14:00 น.
            if (action === 'check_in') {
                const now = new Date();
                if (now.getHours() < 14) {
                    const timeStr = now.toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'});
                    extraWarning = `<br><br><span class="text-ycGold bg-yellow-900/30 px-3 py-2 rounded-lg border border-yellow-500/50 block mt-2 text-left shadow-inner"><i class="ph-fill ph-warning"></i> ขณะนี้เวลา ${timeStr} น. ยังไม่ถึงกำหนดเวลาให้เข้าพัก (14:00 น.)</span>`;
                }
            }

            if(action === 'check_in') {
                title.innerText = 'ยืนยันการเช็คอิน';
                desc.innerHTML = 'กรุณาตรวจสอบข้อมูลลูกค้าก่อนยืนยันให้เข้าพัก' + extraWarning;
                icon.classList.add('ph-sign-in', 'text-ycGreen');
                iconWrap.className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-ycGreen/20 shadow-[0_0_20px_rgba(0,255,65,0.3)]';
                actionContent.className = 'bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-ycGreen shadow-[0_0_30px_rgba(0,255,65,0.2)] p-5 text-center transform scale-95 transition-transform duration-300';
                submitBtn.className = 'flex-1 py-3 rounded-xl bg-ycGreen text-black font-black hover:bg-green-500 shadow-[0_0_15px_rgba(0,255,65,0.5)] transition flex items-center justify-center gap-2';
                roomSpan.className = 'text-3xl text-ycGreen font-black tracking-wider';
            } 
            else if(action === 'approve') {
                title.innerText = 'ยืนยันการชำระเงิน';
                desc.innerHTML = 'กรุณาตรวจสอบสลิปและยอดโอนให้เรียบร้อยก่อนยืนยัน <br><span class="text-ycPink font-bold">เปลี่ยนสถานะเป็น ยืนยันการจองแล้ว?</span>';
                icon.classList.add('ph-check-circle', 'text-ycPink');
                iconWrap.className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-ycPink/20 shadow-[0_0_20px_rgba(255,0,157,0.3)]';
                actionContent.className = 'bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-ycPink shadow-[0_0_30px_rgba(255,0,157,0.2)] p-5 text-center transform scale-95 transition-transform duration-300';
                submitBtn.className = 'flex-1 py-3 rounded-xl bg-ycPink text-white font-bold hover:bg-pink-600 shadow-[0_0_15px_rgba(255,0,157,0.5)] transition flex items-center justify-center gap-2';
                roomSpan.className = 'text-3xl text-ycPink font-black tracking-wider';
            }
            else if(action === 'check_out') {
                title.innerText = 'ตรวจสอบการเช็คเอาท์';
                desc.innerHTML = '<span class="text-ycRed font-bold">โปรดตรวจสอบ:</span> ลูกค้าคืนกุญแจ และเคลียร์ค่าใช้จ่ายส่วนเกินเรียบร้อยแล้ว';
                icon.classList.add('ph-sign-out', 'text-ycRed');
                iconWrap.className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-ycRed/20 shadow-[0_0_20px_rgba(255,0,60,0.3)]';
                actionContent.className = 'bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-ycRed shadow-[0_0_30px_rgba(255,0,60,0.2)] p-5 text-center transform scale-95 transition-transform duration-300';
                submitBtn.className = 'flex-1 py-3 rounded-xl bg-ycRed text-white font-bold hover:bg-red-700 shadow-[0_0_15px_rgba(255,0,60,0.5)] transition flex items-center justify-center gap-2';
                roomSpan.className = 'text-3xl text-ycRed font-black tracking-wider';
            }
            else if(action === 'delete') {
                title.innerText = 'ปฏิเสธ / ลบการจอง';
                desc.innerHTML = 'คุณต้องการ <span class="text-ycRed font-bold">ปฏิเสธสลิป และลบการจองนี้</span> ใช่หรือไม่? (ระบบจะคืนห้องว่างทันที)';
                icon.classList.add('ph-x-circle', 'text-ycRed');
                iconWrap.className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-ycRed/20 shadow-[0_0_20px_rgba(255,0,60,0.3)]';
                actionContent.className = 'bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-ycRed shadow-[0_0_30px_rgba(255,0,60,0.2)] p-5 text-center transform scale-95 transition-transform duration-300';
                submitBtn.className = 'flex-1 py-3 rounded-xl bg-ycRed text-white font-bold hover:bg-red-700 shadow-[0_0_15px_rgba(255,0,60,0.5)] transition flex items-center justify-center gap-2';
                roomSpan.className = 'text-3xl text-ycRed font-black tracking-wider';
            }

            else if(action === 'undo_check_in') {
                title.innerText = 'ย้อนกลับสถานะ (Undo)';
                desc.innerHTML = 'คุณต้องการย้อนกลับสถานะห้องนี้เป็น <span class="text-ycGold font-bold">"รอเช็คอิน"</span> ใช่หรือไม่?';
                icon.classList.add('ph-arrow-u-up-left', 'text-ycGold');
                iconWrap.className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-ycGold/20 shadow-[0_0_20px_rgba(255,204,0,0.3)]';
                actionContent.className = 'bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-ycGold shadow-[0_0_30px_rgba(255,204,0,0.2)] p-5 text-center transform scale-95 transition-transform duration-300';
                submitBtn.className = 'flex-1 py-3 rounded-xl bg-ycGold text-black font-bold hover:bg-yellow-500 shadow-[0_0_15px_rgba(255,204,0,0.5)] transition flex items-center justify-center gap-2';
                roomSpan.className = 'text-3xl text-ycGold font-black tracking-wider';
            }
            else if(action === 'delete_walkin') {
                title.innerText = 'ยกเลิก Walk-in (ลบการทำรายการ)';
                desc.innerHTML = 'คุณต้องการยกเลิกและลบการจอง Walk-in นี้เนื่องจาก <span class="text-ycRed font-bold">"กดผิด"</span> ใช่หรือไม่?';
                icon.classList.add('ph-trash', 'text-ycRed');
                iconWrap.className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-ycRed/20 shadow-[0_0_20px_rgba(255,0,60,0.3)]';
                actionContent.className = 'bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-ycRed shadow-[0_0_30px_rgba(255,0,60,0.2)] p-5 text-center transform scale-95 transition-transform duration-300';
                submitBtn.className = 'flex-1 py-3 rounded-xl bg-ycRed text-white font-bold hover:bg-red-700 shadow-[0_0_15px_rgba(255,0,60,0.5)] transition flex items-center justify-center gap-2';
                roomSpan.className = 'text-3xl text-ycRed font-black tracking-wider';
            }
            else if(action === 'delete_walkin') {
                title.innerText = 'ยกเลิก Walk-in (ลบการทำรายการ)';
                desc.innerHTML = 'คุณต้องการยกเลิกและลบการจอง Walk-in นี้เนื่องจาก <span class="text-ycRed font-bold">"กดผิด"</span> ใช่หรือไม่?';
                icon.classList.add('ph-trash', 'text-ycRed');
                iconWrap.className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-ycRed/20 shadow-[0_0_20px_rgba(255,0,60,0.3)]';
                actionContent.className = 'bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-ycRed shadow-[0_0_30px_rgba(255,0,60,0.2)] p-5 text-center transform scale-95 transition-transform duration-300';
                submitBtn.className = 'flex-1 py-3 rounded-xl bg-ycRed text-white font-bold hover:bg-red-700 shadow-[0_0_15px_rgba(255,0,60,0.5)] transition flex items-center justify-center gap-2';
                roomSpan.className = 'text-3xl text-ycRed font-black tracking-wider';
            }
            else if(action === 'undo_check_out') {
                title.innerText = 'ย้อนกลับสถานะ (Undo)';
                desc.innerHTML = 'คุณต้องการย้อนกลับสถานะห้องนี้เป็น <span class="text-ycBlue font-bold">"กำลังพักอยู่"</span> ใช่หรือไม่?';
                icon.classList.add('ph-arrow-u-up-left', 'text-ycBlue');
                iconWrap.className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-ycBlue/20 shadow-[0_0_20px_rgba(0,208,255,0.3)]';
                actionContent.className = 'bg-[#0f0f0f] w-[90%] max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar rounded-2xl border border-ycBlue shadow-[0_0_30px_rgba(0,208,255,0.2)] p-5 text-center transform scale-95 transition-transform duration-300';
                submitBtn.className = 'flex-1 py-3 rounded-xl bg-ycBlue text-black font-bold hover:bg-blue-500 shadow-[0_0_15px_rgba(0,208,255,0.5)] transition flex items-center justify-center gap-2';
                roomSpan.className = 'text-3xl text-ycBlue font-black tracking-wider';
            }

            actionModal.classList.remove('hidden');
            setTimeout(() => {
                actionContent.classList.remove('scale-95');
                actionContent.classList.add('scale-100');
            }, 10);
        }

        function closeActionModal() {
            actionContent.classList.remove('scale-100');
            actionContent.classList.add('scale-95');
            setTimeout(() => {
                actionModal.classList.add('hidden');
            }, 200);
        }

        function validateActionSubmit(e) {
            // Frontend validation removed. 
            // The backend PHP already properly validates if current_time < (booking_date + 14:00:00)
            return true;
        }

        // ==========================================
        // 🔄 ระบบ Auto-Refresh แผนผังห้องพัก (ทุก 3 นาที)
        // ==========================================
        setInterval(() => {
            let isActionModalOpen = !document.getElementById('actionModal').classList.contains('hidden');
            let isAlertModalOpen = !document.getElementById('alertTimeModal').classList.contains('hidden');
            let isBookingModalOpen = !document.getElementById('bookingModal').classList.contains('hidden');
            
            // ถ้ารีเซปชั่นไม่ได้เปิดหน้าต่างอะไรค้างไว้ ให้รีเฟรชหน้าเว็บอัตโนมัติ
            if(!isActionModalOpen && !isAlertModalOpen && !isBookingModalOpen) {
                // เช็คด้วยว่าไม่ได้พิมพ์ค้นหาค้างไว้
                let searchBox = document.getElementById('roomSearchInput');
                if(!searchBox || searchBox.value.trim() === '') {
                    window.location.reload();
                }
            }
        }, 180000); // 180000 ms = 3 นาที
    </script>
</body>
</html>



