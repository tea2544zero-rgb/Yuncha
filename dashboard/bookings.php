<?php
require_once 'config/security.php';
require_once 'config/db.php';

// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $b_id = (int)(isset($_POST['booking_id']) ? $_POST['booking_id'] : 0);
    
    if($action === 'check_in') {
        $stmt_chk = $conn->prepare("SELECT check_in FROM bookings WHERE id=?");
        $stmt_chk->execute([$b_id]);
        $book_in = $stmt_chk->fetchColumn();
        $allowed_time = $book_in . ' 14:00:00';
        $current_time = date('Y-m-d H:i:s');
        if ($current_time < $allowed_time) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'ยังไม่ถึงเวลา Check-in! (สามารถ Check-in ได้ตั้งแต่วันที่เข้าพัก เวลา 14:00 น.)',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.history.back()))</script>";
            exit;
        }
        $stmt = $conn->prepare("UPDATE bookings SET status='checked_in' WHERE id=?");
        $stmt->execute([$b_id]);
        header("Location: bookings.php?success=checkin"); exit;
    } elseif($action === 'approve') {
        $stmt = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=?");
        $stmt->execute([$b_id]);
        $stmt_b = $conn->prepare("SELECT b.booking_ref, b.room_id, b.total_price, c.first_name, c.last_name, p.payment_method, p.slip_image FROM bookings b JOIN customers c ON b.customer_id = c.id LEFT JOIN payments p ON b.id = p.booking_id WHERE b.id = ?");
        $stmt_b->execute([$b_id]);
        $book_data = $stmt_b->fetch(PDO::FETCH_ASSOC);
        if($book_data) {
            $tx_notes = "ลูกค้า: {$book_data['first_name']} {$book_data['last_name']} | ห้องพัก ID: {$book_data['room_id']}";
            $stmt_chk = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE reference_no = ?");
            $stmt_chk->execute([$book_data['booking_ref']]);
            if($stmt_chk->fetchColumn() == 0) {
                $stmt_tx = $conn->prepare("INSERT INTO transactions (transaction_type, category, amount, vat_amount, net_amount, payment_method, reference_no, notes, transaction_date, transaction_time, created_by, image_path) VALUES ('income', 'ค่าห้องพัก', ?, 0, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_tx->execute([$book_data['total_price'], $book_data['total_price'], isset($book_data['payment_method']) ? $book_data['payment_method'] : 'Bank Transfer', $book_data['booking_ref'], $tx_notes, date('Y-m-d'), date('H:i:s'), isset($_SESSION['emp_code']) ? $_SESSION['emp_code'] : 'SYSTEM', $book_data['slip_image']]);
            }
        }
        header("Location: bookings.php?success=approve"); exit;
    } elseif($action === 'check_out') {
        $stmt = $conn->prepare("UPDATE bookings SET status='checked_out' WHERE id=?");
        $stmt->execute([$b_id]);
        $stmt_r = $conn->prepare("UPDATE rooms SET status='cleaning' WHERE id = (SELECT room_id FROM bookings WHERE id=?)");
        $stmt_r->execute([$b_id]);
        header("Location: bookings.php?success=checkout"); exit;
    } elseif($action === 'delete') {
        $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=?");
        $stmt->execute([$b_id]);
        $stmt_ref = $conn->prepare("SELECT booking_ref FROM bookings WHERE id=?");
        $stmt_ref->execute([$b_id]);
        $ref = $stmt_ref->fetchColumn();
        if($ref) {
            $conn->prepare("DELETE FROM transactions WHERE reference_no = ?")->execute([$ref]);
            $conn->prepare("DELETE FROM payments WHERE booking_id = ?")->execute([$b_id]);
        }
        header("Location: bookings.php?success=delete"); exit;
    } elseif($action === 'edit') {
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $stmt = $conn->prepare("UPDATE bookings SET check_in=?, check_out=? WHERE id=?");
        $stmt->execute([$check_in, $check_out, $b_id]);
        header("Location: bookings.php?success=edit"); exit;
    }
}
// =========================================================================
// ðŸ“Š ดึงข้อมูลห้องพักและการจอง (เชื่อมกับหน้าต้อนรับ 100%)
// =========================================================================
$filter_date = isset($_GET['date']) ? $_GET['date'] :  date('Y-m-d');
$search = trim(isset($_GET['search']) ? $_GET['search'] :  '');

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

$sql_customers = "SELECT id, first_name, last_name, phone FROM customers WHERE status = 'active' ORDER BY first_name ASC";
$result_all_customers = $conn->query($sql_customers);

$sql_rooms = "SELECT t.id as type_id, t.type_name, r.id as room_id, r.room_number, r.status as room_status, r.base_price, r.discount_percent, r.allow_extra_bed, r.extra_bed_price, r.max_guests 
              FROM room_types t 
              LEFT JOIN rooms r ON t.id = r.room_type_id ";
if ($search !== '') {
    $search_safe = $conn->quote('%' . $search . '%');
    $sql_rooms .= " WHERE r.room_number LIKE $search_safe ";
}
$sql_rooms .= " ORDER BY t.base_price DESC, r.room_number ASC";
$result_rooms = $conn->query($sql_rooms);
$hotel_data = [];

if($result_rooms) {
    while($row = $result_rooms->fetch(PDO::FETCH_ASSOC)) {
        $tid = $row['type_id'];
        if(!isset($hotel_data[$tid])) $hotel_data[$tid] = ['name' => $row['type_name'], 'rooms' => []];
        if($row['room_id']) $hotel_data[$tid]['rooms'][] = $row;
    }
}

$sql_bookings = "SELECT b.id as booking_id, b.room_id, b.check_in, b.check_out, b.adults, b.extra_bed, b.total_price, b.status as booking_status, c.first_name, c.last_name, c.phone, c.email, r.room_number, p.slip_image 
                 FROM bookings b 
                 JOIN customers c ON b.customer_id = c.id 
                 JOIN rooms r ON b.room_id = r.id
                 LEFT JOIN payments p ON b.id = p.booking_id
                 WHERE b.check_in <= '$filter_date' AND b.check_out >= '$filter_date' 
                 AND b.status IN ('pending', 'confirmed', 'checked_in', 'checked_out')
                 ORDER BY CASE b.status WHEN 'checked_out' THEN 1 WHEN 'confirmed' THEN 2 WHEN 'checked_in' THEN 3 WHEN 'pending' THEN 4 ELSE 5 END ASC";
$result_bookings = $conn->query($sql_bookings);

$active_bookings = [];
if($result_bookings) {
    while($b = $result_bookings->fetch(PDO::FETCH_ASSOC)) {
        // ข้ามห้องที่ถึงวันเช็คเอาท์แล้วแต่ยังไม่เคยเช็คอิน (ล็อกห้องไว้เพราะลูกค้าจ่ายเงินแล้ว แต่ไม่ต้องโชว์ซ้ำ)
        if($b['check_out'] == $filter_date && $b['booking_status'] == 'confirmed') continue; 
        $active_bookings[$b['room_id']] = $b;
    }
}

$sql_all_future = "SELECT id, room_id, check_in, check_out FROM bookings WHERE status != 'cancelled' AND check_out > CURRENT_DATE";
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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองห้องพัก (Bookings) | Yuncha Valley</title>
    
    <script src="https://cdn.tailwindcss.com"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 
                        ycDeep: '#000000', ycSurface: '#0f0f0f', 
                        ycGreen: '#00ff41', ycGold: '#ffcc00', 
                        ycBlue: '#00d0ff', ycPink: '#ff00ff', ycRed: '#ff003c', ycOrange: '#ff5e00', ycMint: '#00e676', ycPurple: '#b537f2'
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
        ::-webkit-scrollbar-thumb { background: #c100f1; border-radius: 10px; }

        .stars-container { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .star { position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0; animation: fall linear infinite; box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.8); }
        @keyframes fall { 0% { transform: translateY(-10vh) translateX(0) scale(1); opacity: 1; } 100% { transform: translateY(110vh) translateX(-20vw) scale(0); opacity: 0; } }

        @keyframes shake {
          0%, 100% { transform: rotate(0deg); }
          25% { transform: rotate(15deg); }
          75% { transform: rotate(-15deg); }
        }

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
        .input-dark:focus { border-color: var(--neon-color); box-shadow: 0 0 10px rgba(193,0,241,0.2); }
        .input-error { border-color: #ff003c !important; color: #ff003c !important; box-shadow: 0 0 15px rgba(255,0,60,0.4) !important; background-color: #1a0505 !important; }

        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { appearance: none; margin: 0; }

        .neon-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
        .neon-switch input { opacity: 0; width: 0; height: 0; }
        .neon-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #151515; transition: .3s; border-radius: 26px; border: 1px solid #333; }
        .neon-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #555; transition: .3s; border-radius: 50%; }
        input:checked + .neon-slider { background-color: rgba(193,0,241, 0.1); border-color: #c100f1; box-shadow: 0 0 10px rgba(193,0,241, 0.2); }
        input:checked + .neon-slider:before { transform: translateX(22px); background-color: #c100f1; box-shadow: 0 0 10px #c100f1; }
        .radio-btn-group input[type="radio"]:checked + div { background: rgba(193,0,241,0.1); border-color: #c100f1; color: #c100f1; }
    </style>
</head>
<body class="h-screen flex selection:bg-ycPink selection:text-black">

    <div class="stars-container" id="starsBox"></div>
    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden w-full relative z-10 bg-transparent">
        
        <header class="h-auto md:h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 py-4 flex flex-col md:flex-row justify-between items-start md:items-center z-30 sticky top-0 gap-4">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg border border-gray-800" style="color: #c100f1;"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-bold text-white flex items-center gap-2"><i class="ph-fill ph-calendar-check drop-shadow-[0_0_10px_#c100f1]" style="color: #c100f1;"></i> การจองห้องพัก</h2>
                    <p class="text-sm text-gray-400 font-medium">ดูสถานะห้องพัก ค้นหา และจัดการการเข้าพักล่วงหน้า</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-2">เวลาปัจจุบัน: <span id="clockStatus" style="color: #c100f1;"><?= date('H:i:s') ?></span></p>

                <!-- แจ้งเตือนการจองใหม่ -->
                <div class="relative">
                    <button type="button" id="notiBtn" onclick="document.getElementById('notiDropdown').classList.toggle('hidden')" class="bg-[#111] hover:bg-gray-800 text-gray-300 p-3 rounded-xl border border-gray-700 transition shadow-md relative">
                        <i class="ph-bold ph-bell text-xl <?= $pending_count > 0 ? 'text-ycRed animate-[shake_0.5s_infinite]' : '' ?>"></i>
                        <?php if($pending_count > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-ycRed text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-black animate-pulse"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </button>
                    <!-- Dropdown -->
                    <div id="notiDropdown" class="hidden absolute left-0 md:right-0 md:left-auto mt-3 w-80 bg-[#0f0f0f] border border-gray-800 rounded-2xl shadow-2xl z-50 overflow-hidden">
                        <div class="p-4 border-b border-gray-800 bg-black flex justify-between items-center">
                            <h4 class="text-white font-bold flex items-center gap-2"><i class="ph-fill ph-bell-ringing text-ycRed"></i> การจองใหม่รอตรวจสอบ</h4>
                            <span class="bg-gray-800 text-gray-300 text-xs px-2 py-1 rounded-md"><?= $pending_count ?> รายการ</span>
                        </div>
                        <div class="max-h-[300px] overflow-y-auto">
                            <?php if($pending_count == 0): ?>
                                <div class="p-6 text-center text-gray-500 font-medium">ไม่มีรายการใหม่</div>
                            <?php else: foreach($pending_notis as $noti): ?>
                                <a href="bookings.php?date=<?= $noti['check_in'] ?>" class="block p-4 border-b border-gray-800 hover:bg-[#151515] transition">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-ycGold font-black tracking-widest text-lg"><?= $noti['room_number'] ?></span>
                                        <span class="text-[10px] text-white bg-ycPink px-2 py-0.5 rounded uppercase font-bold animate-pulse">NEW!</span>
                                    </div>
                                    <p class="text-sm text-gray-300 truncate"><i class="ph-fill ph-user text-gray-500"></i> <?= $noti['first_name'] . ' ' . $noti['last_name'] ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><i class="ph-fill ph-calendar text-gray-600"></i> เข้าพัก: <?= date('d/m/Y', strtotime($noti['check_in'])) ?></p>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <form method="GET" action="bookings.php" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <div class="relative hidden md:block">
                        <i class="ph-bold ph-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาเลขห้อง..." class="input-dark pl-10 w-40 text-sm font-bold placeholder-gray-600" style="--neon-color: #c100f1;">
                    </div>
                    
                    <div class="relative flex items-center bg-[#0a0a0a] border border-gray-700 rounded-xl overflow-hidden focus-within:shadow-[0_0_10px_rgba(193,0,241,0.3)] focus-within:border-[#c100f1] transition-all">
                        <div class="bg-gray-800 px-3 py-[0.65rem] flex items-center justify-center border-r border-gray-700">
                            <i class="ph-bold ph-calendar text-gray-300"></i>
                        </div>
                        <input type="date" name="date" id="filterDate" value="<?= $filter_date ?>" onchange="this.form.submit()" class="bg-transparent border-none text-white text-sm font-bold px-3 py-2 outline-none cursor-pointer w-full">
                    </div>
                    <button type="submit" class="hidden">Search</button>
                </form>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
            <div class="max-w-[1500px] mx-auto space-y-6 pb-12">
                
                <div class="bg-purple-900/10 border border-purple-500/30 text-purple-300 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2 gs-anim mb-6">
                    <i class="ph-fill ph-info text-lg"></i> แผงควบคุมข้อมูลการเข้าพักประจำวันที่ <span class="text-white bg-purple-900/50 px-2 py-0.5 rounded ml-1"><?= date('d/m/Y', strtotime($filter_date)) ?></span>
                </div>

                <?php 
                if(empty($hotel_data)): 
                    echo '<div class="text-center py-20 bg-[#0f0f0f] border border-gray-800 rounded-2xl"><i class="ph-duotone ph-bed text-6xl text-gray-600 mb-4"></i><p class="text-xl font-bold text-gray-400">ไม่พบข้อมูลห้องพักที่ค้นหา</p></div>';
                else:
                    foreach($hotel_data as $tid => $type): 
                        $tname = $type['name'];
                        $colorHex = '#ffffff'; $icon = 'ph-bed'; $desc = '';
                        
                        if(stripos($tname, 'Valley') !== false) { $colorHex = '#00ff41'; $icon = 'ph-leaf'; $desc = '• วิวไร่ชา'; } 
                        elseif(stripos($tname, 'Peak') !== false) { $colorHex = '#ffcc00'; $icon = 'ph-mountains'; $desc = '• วิวภูเขา'; } 
                        elseif(stripos($tname, 'Pavilion') !== false) { $colorHex = '#00d0ff'; $icon = 'ph-waves'; $desc = '• วิวแม่น้ำ'; }
                        
                        $roomCount = count($type['rooms']);
                ?>
                <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-5 mb-8 shadow-2xl gs-anim relative overflow-hidden">
                    <div class="absolute bottom-0 left-0 w-full h-[2px]" style="background: linear-gradient(90deg, transparent, <?= $colorHex ?>, transparent);"></div>

                    <div class="flex items-center gap-5 mb-6 pb-4">
                        <div class="w-16 h-16 rounded-2xl bg-black border border-gray-800 flex items-center justify-center" style="box-shadow: inset 0 0 20px rgba(255,255,255,0.05), 0 0 15px <?= $colorHex ?>40;">
                            <i class="ph-fill <?= $icon ?> text-4xl" style="color: <?= $colorHex ?>; filter: drop-shadow(0 0 8px <?= $colorHex ?>);"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-white tracking-wide"><?= htmlspecialchars($tname) ?></h3>
                            <p class="text-sm font-bold mt-1" style="color: <?= $colorHex ?>;"><?= $roomCount ?> ห้อง <?= $desc ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach($type['rooms'] as $room): 
                            $rid = $room['room_id'];
                            $rnum = $room['room_number'];
                            $rstatus = $room['room_status'];
                            $base_price = $room['base_price'];
                            $discount = isset($room['discount_percent']) ? $room['discount_percent'] :  0;
                            $allow_extra = $room['allow_extra_bed'];
                            $extra_price = $room['extra_bed_price'];
                            $max_guests = $room['max_guests'];
                            $booking = isset($active_bookings[$rid]) ? $active_bookings[$rid] :  null;

                            $customerName = '';
                            $ci = '';
                            $co = '';
                            $bStatus = '';
                            if($booking) {
                                $customerName = htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name'], ENT_QUOTES, 'UTF-8');
                                $ci = date('d/m/y', strtotime($booking['check_in']));
                                $co = date('d/m/y', strtotime($booking['check_out']));
                                $bStatus = $booking['booking_status'];
                            }
                            
                            $cardBorder = 'border-gray-800 hover:border-gray-600';
                            $statusTag = '<span class="text-gray-500 text-xs font-bold uppercase"><i class="ph-fill ph-check-circle text-ycGreen"></i> ว่างพร้อมขาย</span>';
                            
                            $subtitle = '';
                            if($tid == 3 || stripos($tname, 'Valley') !== false) { $subtitle = 'วิวไร่ชา'; }
                            elseif($tid == 5 || stripos($tname, 'Peak') !== false) { $subtitle = 'วิวภูเขา'; }
                            elseif($tid == 4 || stripos($tname, 'Pavilion') !== false) { $subtitle = 'วิวแม่น้ำ'; }

                            if($rstatus == 'deleted') {
                                $cardBorder = 'border-red-900/50 opacity-40';
                                $statusTag = '<span class="bg-red-900/30 text-red-500 px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider">ถูกลบ</span>';
                            } elseif($rstatus == 'maintenance') {
                                $cardBorder = 'border-orange-900/50 border-dashed bg-[#151000]';
                                $statusTag = '<span class="bg-orange-900/40 text-orange-500 px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider">ซ่อมบำรุง</span>';
                            } elseif($booking) {
                                if($bStatus == 'checked_in') {
                                    $cardBorder = 'border-ycGreen/60 bg-[#001505] shadow-[0_0_15px_rgba(0,255,65,0.15)] transform scale-[1.02]';
                                    $statusTag = '<span class="bg-green-900/50 text-ycGreen px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider">เช็คอินแล้ว</span>';
                                } elseif($bStatus == 'pending') {
                                    $cardBorder = 'border-ycBlue/60 bg-[#001015] shadow-[0_0_15px_rgba(0,208,255,0.15)] transform scale-[1.02]';
                                    $statusTag = '<span class="bg-blue-900/50 text-ycBlue px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider"><i class="ph-bold ph-clock"></i> รอตรวจสอบชำระเงิน</span>';
                                } else {
                                    $cardBorder = 'border-ycGold/60 bg-[#151300] shadow-[0_0_15px_rgba(255,204,0,0.15)] transform scale-[1.02]';
                                    $statusTag = '<span class="bg-yellow-900/50 text-ycGold px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider">ยืนยันการจองแล้ว</span>';
                                }
                            }
                        ?>
                        <div class="bg-[#050505] border <?= $cardBorder ?> rounded-xl p-4 flex flex-col justify-between transition-all duration-300">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-2xl font-black text-white tracking-widest drop-shadow-[0_0_10px_rgba(255,255,255,0.3)]"><?= htmlspecialchars($rnum) ?></span>
                                    <?= $statusTag ?>
                                </div>
                                <div class="mb-3 text-gray-500 text-xs font-bold flex items-center gap-2">
                                    <span><i class="ph-fill ph-users"></i> รองรับ <?= $max_guests ?> ท่าน</span>
                                    <span class="text-gray-600">•</span>
                                    <span><?= $subtitle ?></span>
                                </div>
                            </div>
                            
                            <?php if($booking): ?>
                                <div class="bg-[#0a0a0a] rounded-lg p-3 flex flex-col gap-2 border border-[#222]">
                                    <div>
                                        <div class="flex items-center gap-2 text-sm text-gray-200 font-bold mb-1">
                                            <i class="ph-fill ph-user text-gray-500"></i> <span class="truncate"><?= $customerName ?></span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-mono space-y-0.5 ml-6">
                                            <p><i class="ph-fill ph-phone text-gray-500"></i> <?= htmlspecialchars(isset($booking['phone']) ? $booking['phone'] : '-') ?></p>
                                            <p><i class="ph-fill ph-envelope text-gray-500"></i> <?= htmlspecialchars(isset($booking['email']) ? $booking['email'] : '-') ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-gray-500 font-medium bg-[#111] p-2 rounded border border-[#222]">
                                        <span class="text-green-500 font-bold">เข้า: <?= $ci ?></span>
                                        <i class="ph-bold ph-arrow-right text-gray-600"></i>
                                        <span class="text-red-500 font-bold">ออก: <?= $co ?></span>
                                    </div>
                                    <button onclick="openManageModal('<?= $booking['booking_id'] ?>', '<?= $rid ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $customerName ?>', '<?= date('Y-m-d', strtotime($booking['check_in'])) ?>', '<?= date('Y-m-d', strtotime($booking['check_out'])) ?>', '<?= $booking['adults'] ?>', '<?= $booking['extra_bed'] ?>', '<?= $bStatus ?>', '<?= htmlspecialchars(isset($booking['slip_image']) ? $booking['slip_image'] :  '') ?>', '<?= number_format($booking['total_price']) ?>')" class="mt-2 w-full py-1.5 rounded-md bg-gray-800 hover:bg-ycBlue hover:text-black text-xs font-bold text-gray-300 transition-colors flex items-center justify-center gap-2">
                                        <i class="ph-bold ph-gear"></i> จัดการ / อัปเดตสถานะ
                                    </button>
                                </div>
                            <?php else: ?>
                                <?php if($rstatus == 'available'): ?>
                                    <button onclick="openBookingModal('<?= $rid ?>', '<?= htmlspecialchars($rnum) ?>', '<?= $base_price ?>', '<?= $discount ?>', '<?= $allow_extra ?>', '<?= $extra_price ?>', '<?= $max_guests ?>')" class="h-[100px] w-full flex flex-col items-center justify-center border-2 border-dashed border-gray-800 hover:border-ycPink hover:text-ycPink rounded-lg text-gray-600 text-sm font-bold transition-colors group cursor-pointer" style="hover:border-color:#c100f1; hover:color:#c100f1;">
                                        <i class="ph-bold ph-plus-circle text-2xl mb-1 group-hover:scale-110 transition-transform"></i> 
                                        <span>กดเพื่อสร้างการจอง</span>
                                    </button>
                                <?php else: ?>
                                    <div class="h-[100px] flex flex-col items-center justify-center rounded-lg bg-[#0a0a0a] text-gray-600 text-sm font-medium border border-gray-800">
                                        <i class="ph-fill ph-lock-key text-xl mb-1"></i> ระงับการใช้งาน
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php 
                    endforeach; 
                endif; 
                ?>
            </div>
        </main>
    </div>

    <div id="bookingModal" class="fixed inset-0 bg-black/90 z-[100] hidden flex items-center justify-center backdrop-blur-sm overflow-y-auto pt-10 pb-10">
        <div class="bg-[#0f0f0f] w-[95%] md:w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl border border-gray-800 shadow-[0_0_30px_rgba(193,0,241,0.15)] relative my-auto">
            
            <div class="flex justify-between items-center p-6 border-b border-gray-800 bg-[#0a0a0a] rounded-t-2xl sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-[0_0_10px_rgba(193,0,241,0.3)]" style="background-color: rgba(193,0,241,0.2); color:#c100f1;">
                        <i class="ph-bold ph-calendar-plus text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white drop-shadow-md">สร้างการจองใหม่</h3>
                </div>
                <button type="button" onclick="closeBookingModal()" class="w-8 h-8 bg-[#1a1a1a] hover:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 transition">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <form action="actions/booking_process.php" method="POST" id="formAddBooking" enctype="multipart/form-data" class="p-6 space-y-6" onsubmit="event.preventDefault(); Swal.fire({title:'ยืนยัน?', text:'ตรวจสอบข้อมูลครบถ้วนแล้วใช่หรือไม่?', icon:'warning', showCancelButton:true, confirmButtonColor:'#ff003c', cancelButtonColor:'#333', confirmButtonText:'ยืนยัน', cancelButtonText:'ยกเลิก', background:'#1a1a1a', color:'#fff'}).then((r)=>{if(r.isConfirmed){this.removeAttribute('onsubmit'); this.submit();}})">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="return_url" value="../bookings.php">
                <input type="hidden" name="room_id" id="bookRoomId">
                <input type="hidden" name="return_date" value="<?= $filter_date ?>">
                <input type="hidden" id="bookRoomPrice">
                <input type="hidden" id="bookRoomDiscount" value="0">
                <input type="hidden" id="bookExtraPrice">
                <input type="hidden" id="bookMaxGuests"> 
                
                <div class="bg-[#151515] rounded-xl p-4 border border-gray-800 flex justify-between items-center">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">ห้องที่เลือก</p>
                        <h4 class="text-2xl font-black text-white" id="bookRoomNumberDisplay">L-01</h4>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1 flex items-center justify-end gap-2">
                            ราคาปกติ/คืน 
                            <span id="bookRoomDiscountBadge" class="bg-ycBlue/20 text-ycBlue px-2 py-0.5 rounded text-[10px] font-black hidden">ลด %</span>
                        </p>
                        <h4 class="text-xl font-bold text-ycGold" id="bookRoomPriceDisplay">฿0.00</h4>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border border-gray-800 rounded-xl bg-[#0a0a0a]">
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">ชื่อจริง <span class="text-ycRed">*</span></label>
                        <input type="text" name="new_fname" required class="input-dark w-full" style="--neon-color: #c100f1;" placeholder="ชื่อจริง">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">นามสกุล <span class="text-ycRed">*</span></label>
                        <input type="text" name="new_lname" required class="input-dark w-full" style="--neon-color: #c100f1;" placeholder="นามสกุล">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">เบอร์โทรศัพท์ <span class="text-ycRed">*</span></label>
                        <input type="tel" name="new_phone" required class="input-dark w-full" style="--neon-color: #c100f1;" placeholder="เบอร์โทรศัพท์">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 border-t border-gray-800 pt-5">
                    <div class="relative">
                        <label class="block text-sm font-bold text-gray-400 mb-2">วันที่เช็คอิน <span class="text-ycRed">*</span></label>
                        <input type="date" name="check_in" id="bookCheckIn" required class="input-dark w-full" style="--neon-color: #c100f1;" onchange="updateCheckOut('book')">
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-bold text-gray-400 mb-2">วันที่เช็คเอาท์ <span class="text-ycRed">*</span></label>
                        <input type="date" name="check_out" id="bookCheckOut" required class="input-dark w-full" style="--neon-color: #c100f1;" onchange="calculatePrice('book')">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">ผู้เข้าพัก (ท่าน) <span class="text-ycRed">*</span></label>
                        <input type="number" name="adults" id="bookAdults" min="1" value="2" class="input-dark text-center" style="--neon-color: #c100f1;" oninput="enforceMaxGuests('book')">
                        <p class="text-xs text-gray-500 mt-1" id="bookMaxGuestText">รับได้สูงสุด 2 ท่าน</p>
                    </div>
                </div>
                
                <p id="bookDateWarning" class="text-ycRed text-sm font-bold mt-1 hidden"><i class="ph-bold ph-warning-circle"></i> ห้องนี้มีการจองในวันดังกล่าวแล้ว กรุณาเปลี่ยนวันที่</p>

                <div class="mt-2 bg-[#111] p-3 rounded-lg border border-gray-800">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2"><i class="ph-fill ph-calendar-x text-ycRed"></i> วันที่ห้องนี้ไม่ว่างล่วงหน้า</p>
                    <div id="bookBookedDatesDisplay" class="flex flex-wrap gap-2 text-sm font-medium"></div>
                </div>

                <div id="bookExtraBedWrap" class="hidden bg-[#0a0a0a] border border-gray-800 rounded-xl p-4 flex justify-between items-center">
                    <div>
                        <p class="text-white font-bold"><i class="ph-fill ph-bed text-ycGreen mr-2"></i> ต้องการเตียงเสริมหรือไม่?</p>
                        <p class="text-xs text-gray-500 mt-1">บวกเพิ่ม <span id="bookDisplayExtraPriceText" class="text-ycGreen font-bold">฿0</span> / คืน</p>
                    </div>
                    <label class="neon-switch">
                        <input type="checkbox" name="add_extra_bed" id="bookAddExtraBed" value="1" onchange="updateAdultsLimit('book'); calculatePrice('book');">
                        <span class="neon-slider"></span>
                    </label>
                </div>

                <div class="border-t border-gray-800 pt-5">
                    <h4 class="text-lg font-bold text-white mb-4"><i class="ph-fill ph-wallet text-ycBlue mr-2"></i> ข้อมูลการชำระเงิน</h4>
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">ช่องทางชำระเงิน <span class="text-ycRed">*</span></label>
                        <div class="relative w-full md:max-w-sm">
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
                    </div>
                    
                    <div class="mt-5 flex items-center gap-4 bg-[#111] p-3 rounded-xl border border-gray-800 w-fit">
                        <label class="neon-switch">
                            <input type="checkbox" name="print_receipt" value="1" checked>
                            <span class="neon-slider"></span>
                        </label>
                        <span class="text-sm font-bold text-gray-300">สร้างและเปิดหน้าใบเสร็จทันทีหลังบันทึก</span>
                    </div>
                </div>

                <div class="border border-gray-800 rounded-xl p-5 mt-4 flex flex-col md:flex-row md:justify-between items-start md:items-center gap-2 shadow-[0_0_15px_rgba(193,0,241,0.1)]" style="background-color: rgba(193,0,241,0.1); border-color: rgba(193,0,241,0.3);">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest" style="color: #c100f1;">ราคาสุทธิ (Total Price)</p>
                        <p class="text-xs text-gray-300 mt-1" id="bookNightsDisplay">คำนวณราคา...</p>
                    </div>
                    <div class="text-left md:text-right">
                        <input type="hidden" name="total_price" id="bookInputTotalPrice" value="0">
                        <h2 class="text-4xl font-black drop-shadow-[0_0_10px_rgba(193,0,241,0.5)]" style="color: #c100f1;">฿<span id="bookDisplayTotalPrice">0.00</span></h2>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end pt-6 border-t border-gray-800 gap-3">
                    <button type="button" onclick="closeBookingModal()" class="neon-pro w-full sm:w-auto px-8 py-3 flex justify-center items-center gap-2" style="--neon-color: #a3a3a3;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-x text-xl text-gray-300 relative z-10 icon-glow"></i>
                        <span class="font-bold text-gray-300 relative z-10">ปิดหน้าต่าง</span>
                    </button>
                    <button type="submit" id="bookBtnSubmit" class="neon-pro w-full sm:w-auto px-10 py-3 flex justify-center items-center gap-2 opacity-50 cursor-not-allowed" style="--neon-color: #c100f1;" disabled>
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-check-circle text-xl text-white relative z-10 icon-glow"></i>
                        <span class="font-bold text-white relative z-10">ยืนยันการจอง</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="manageModal" class="fixed inset-0 bg-black/90 z-[100] hidden flex items-center justify-center backdrop-blur-sm overflow-y-auto pt-10 pb-10">
        <div class="bg-[#0f0f0f] w-[95%] md:w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl border border-gray-800 shadow-[0_0_30px_rgba(0,208,255,0.15)] relative my-auto">
            
            <div class="flex justify-between items-center p-6 border-b border-gray-800 bg-[#0a0a0a] rounded-t-2xl sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-ycBlue/20 rounded-xl flex items-center justify-center text-ycBlue shadow-[0_0_10px_rgba(0,208,255,0.3)]">
                        <i class="ph-bold ph-gear text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white drop-shadow-md">แผงควบคุมการเข้าพัก</h3>
                </div>
                <button type="button" onclick="closeManageModal()" class="w-8 h-8 bg-[#1a1a1a] hover:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 transition">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <form action="bookings.php" method="POST" id="formManageBooking" class="p-6 space-y-6">
                <input type="hidden" name="action" id="manageAction" value="edit">
                <input type="hidden" name="booking_id" id="manageBookingId">
                <input type="hidden" name="room_id" id="manageRoomId">
                <input type="hidden" name="return_date" value="<?= $filter_date ?>">
                <input type="hidden" id="manageOriginalNights">
                
                <div class="bg-[#151515] rounded-xl p-4 border border-gray-800 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">ข้อมูลผู้จอง</p>
                        <h4 class="text-lg font-bold text-white"><i class="ph-fill ph-user text-gray-500"></i> <span id="manageCustomerName">-</span></h4>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">ห้องพัก</p>
                        <h4 class="text-2xl font-black text-ycBlue" id="manageRoomNumberDisplay">-</h4>
                    </div>
                </div>

                <div id="manageSlipContainer" class="hidden mb-4 bg-black p-4 rounded-xl border border-gray-800 text-center"></div>

                <div class="bg-[#050505] p-5 rounded-xl border border-gray-800">
                    <p class="text-sm font-bold text-gray-400 mb-3"><i class="ph-bold ph-lightning text-ycGold"></i> ดำเนินการด่วน (Quick Action)</p>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <button type="button" id="btnApprove" onclick="quickUpdateStatus('approve', 'ยืนยันการชำระเงินและเปลี่ยนสถานะเป็น Confirmed?')" class="hidden py-3 rounded-xl bg-blue-900/30 border border-blue-500/50 hover:bg-blue-600 hover:text-white text-ycBlue font-black transition flex flex-col items-center gap-1 shadow-[0_0_15px_rgba(0,208,255,0.1)]">
                            <i class="ph-bold ph-check-circle text-2xl"></i> ยืนยันการชำระเงิน
                        </button>
                        
                        <button type="button" id="btnCheckIn" onclick="quickUpdateStatus('check_in', 'ยืนยันการให้ลูกค้าเข้าพัก (Check-in)?')" class="hidden py-3 rounded-xl bg-green-900/30 border border-green-500/50 hover:bg-green-600 hover:text-black text-ycGreen font-black transition flex flex-col items-center gap-1 shadow-[0_0_15px_rgba(0,255,65,0.1)]">
                            <i class="ph-bold ph-sign-in text-2xl"></i> เช็คอิน (Check-In)
                        </button>
                        
                        <button type="button" id="btnCheckOut" onclick="quickUpdateStatus('check_out', 'ยืนยันการให้ลูกค้าออกและคืนกุญแจ (Check-out)?')" class="hidden py-3 rounded-xl bg-purple-900/30 border border-purple-500/50 hover:bg-purple-600 hover:text-white text-purple-400 font-black transition flex flex-col items-center gap-1 shadow-[0_0_15px_rgba(181,55,242,0.1)]">
                            <i class="ph-bold ph-sign-out text-2xl"></i> เช็คเอาท์ (Check-Out)
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-3 italic">* หากปุ่มไม่ขึ้นแสดงว่าสถานะปัจจุบันไม่รองรับการทำงานนี้</p>
                </div>

                <div id="manageDateEditSection" class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-gray-800 pt-5 mt-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">แก้ไขวันที่เช็คอิน <span class="text-ycRed">*</span></label>
                        <input type="date" name="check_in" id="manageCheckIn" required class="input-dark w-full" style="--neon-color: #00d0ff;" onchange="autoShiftCheckOut()">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-400 mb-2">วันที่เช็คเอาท์ (ล็อกจำนวนคืน)</label>
                        <input type="date" name="check_out" id="manageCheckOut" required class="input-dark w-full bg-[#111] text-gray-500 cursor-not-allowed" readonly>
                    </div>
                </div>
                
                <p id="manageDateWarning" class="text-ycRed text-sm font-bold mt-1 hidden"><i class="ph-bold ph-warning-circle"></i> ห้องนี้มีการจองในวันดังกล่าวแล้ว กรุณาเลื่อนเป็นวันอื่น</p>
                <div id="manageDateLockedMessage" class="hidden border-t border-gray-800 pt-5 mt-5 text-center">
                    <p class="text-gray-500 font-bold text-sm"><i class="ph-bold ph-lock-key"></i> ลูกค้ากำลังเข้าพัก หรือเช็คเอาท์ไปแล้ว ไม่สามารถเลื่อนวันได้</p>
                </div>

                <div class="mt-2 bg-[#111] p-4 rounded-lg border border-gray-800">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3"><i class="ph-fill ph-calendar-x text-ycRed"></i> วันที่ห้องนี้ไม่ว่าง (อ้างอิงเพื่อหลบเลี่ยง)</p>
                    <div id="manageBookedDatesDisplay" class="flex flex-wrap gap-2 text-sm font-medium"></div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center pt-6 border-t border-gray-800 gap-4 mt-6">
                    <button type="button" onclick="quickUpdateStatus('delete', 'คุณต้องการลบ/ยกเลิกการจองนี้ใช่หรือไม่? (ลบแล้วระบบจะคืนห้องว่างทันที)')" class="w-full sm:w-auto px-6 py-3 rounded-xl border border-gray-700 text-gray-400 font-bold hover:bg-ycRed hover:text-white hover:border-ycRed transition flex justify-center items-center gap-2">
                        <i class="ph-bold ph-trash text-xl"></i> ลบการจองที่ผิดพลาด
                    </button>
                    
                    <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-3">
                        <button type="button" onclick="closeManageModal()" class="neon-pro w-full sm:w-auto px-8 py-3 flex justify-center items-center gap-2" style="--neon-color: #a3a3a3;">
                            <div class="neon-pro-glow"></div>
                            <span class="font-bold text-gray-300 relative z-10">ปิดหน้าต่าง</span>
                        </button>
                        
                        <button type="button" onclick="Swal.fire({title:'ยืนยัน?', text:'ยืนยันการบันทึกเลื่อนวันเข้าพัก?', icon:'warning', showCancelButton:true, confirmButtonColor:'#00d0ff', cancelButtonColor:'#333', confirmButtonText:'ยืนยัน', cancelButtonText:'ยกเลิก', background:'#1a1a1a', color:'#fff'}).then((r)=>{if(r.isConfirmed){ document.getElementById('manageAction').value='edit'; document.getElementById('formManageBooking').action='bookings.php'; document.getElementById('formManageBooking').submit(); }})" id="manageBtnSubmit" class="neon-pro w-full sm:w-auto px-10 py-3 flex justify-center items-center gap-2" style="--neon-color: #00d0ff;">
                            <div class="neon-pro-glow"></div>
                            <i class="ph-bold ph-floppy-disk text-xl text-white relative z-10 icon-glow"></i>
                            <span class="font-bold text-white relative z-10">บันทึกวันเลื่อน</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if(isset($_GET['success'])): 
        $s = $_GET['success'];
        $bClass = 'border-ycGreen text-ycGreen shadow-[0_0_15px_rgba(0,255,65,0.3)]';
        $iClass = 'ph-check-circle';
        if($s == 'delete') {
            $bClass = 'border-ycRed text-ycRed shadow-[0_0_15px_rgba(255,0,60,0.3)]';
            $iClass = 'ph-trash';
        } elseif($s == 'edit') {
            $bClass = 'border-ycBlue text-ycBlue shadow-[0_0_15px_rgba(0,208,255,0.3)]';
            $iClass = 'ph-pencil-simple';
        }
    ?>
    <div id="successAlert" class="fixed top-5 right-5 z-[200] bg-[#0a0a0a] border <?= $bClass ?> px-6 py-4 rounded-xl font-bold flex items-center gap-3 gs-anim-alert">
        <i class="ph-bold <?= $iClass ?> text-2xl"></i>
        <span id="successMsgText">ดำเนินการเสร็จสิ้นเรียบร้อย</span>
    </div>
    <script>
        const successType = "<?= $s ?>";
        const msgEl = document.getElementById('successMsgText');
        if(successType === 'checkin') msgEl.innerText = "เช็คอินลูกค้าเรียบร้อยแล้ว";
        else if(successType === 'approve') msgEl.innerText = "ยืนยันการชำระเงินเรียบร้อยแล้ว";
        else if(successType === 'checkout') msgEl.innerText = "เช็คเอาท์เรียบร้อย คืนห้องสู่ระบบแล้ว";

        else if(successType === 'undo') msgEl.innerText = "ย้อนกลับสถานะสำเร็จ";
        else if(successType === 'delete') msgEl.innerText = "ลบการจองเรียบร้อย";
        else if(successType === 'edit') msgEl.innerText = "บันทึกการเลื่อนวันเข้าพักเรียบร้อย";
        else if(successType === 'add') msgEl.innerText = "สร้างการจองใหม่เรียบร้อย";
        
        setTimeout(() => { document.getElementById('successAlert').style.display = 'none'; }, 3000);
        window.history.replaceState({}, document.title, window.location.pathname + '?date=' + "<?= $filter_date ?>");
    </script>
    <?php endif; ?>

    <script>
        const allBookings = <?= json_encode($all_room_bookings) ?>;
        const hotelData = <?= json_encode($hotel_data) ?>;
        
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

        function suggestAlternativeRooms(targetRoomId, checkInStr, checkOutStr, currentBookingId = null) {
            let suggestions = [];
            let targetType = null;
            for(let tId in hotelData) {
                let rooms = hotelData[tId].rooms;
                let found = rooms.find(r => r.room_id == targetRoomId);
                if(found) { targetType = tId; break; }
            }
            if(!targetType) return [];
            
            let typeRooms = hotelData[targetType].rooms;
            for(let i=0; i<typeRooms.length; i++) {
                let r = typeRooms[i];
                if(r.room_id == targetRoomId) continue; 
                if(r.room_status === 'maintenance' || r.room_status === 'deleted') continue; 
                if(!isDateConflict(r.room_id, checkInStr, checkOutStr, currentBookingId)) {
                    suggestions.push(r.room_number);
                }
            }
            return suggestions;
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
            let max = parseInt(input.max) || 2;
            let val = parseInt(input.value);
            if(val > max) { input.value = max; }
            if(val < 1) { input.value = 1; }
        }

        function updateAdultsLimit(prefix) {
            let maxBase = parseInt(document.getElementById(prefix + 'MaxGuests').value) || 2;
            let isExtraChecked = document.getElementById(prefix + 'AddExtraBed').checked;
            let newMax = isExtraChecked ? maxBase + 1 : maxBase;
            let adultInput = document.getElementById(prefix + 'Adults');
            adultInput.max = newMax;
            document.getElementById(prefix + 'MaxGuestText').innerText = `รับได้สูงสุด ${newMax} ท่าน` + (isExtraChecked ? ' (รวมเสริม)' : '');
            if(parseInt(adultInput.value) > newMax) { adultInput.value = newMax; }
        }
    </script>

    <script>
        // จำตำแหน่ง Scroll
        document.addEventListener("DOMContentLoaded", function() { 
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if (sessionStorage.getItem('sidebarScrollPos') && sidebarEl) sidebarEl.scrollTop = sessionStorage.getItem('sidebarScrollPos');
        });
        window.onbeforeunload = function() {
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if(sidebarEl) sessionStorage.setItem('sidebarScrollPos', sidebarEl.scrollTop);
        };

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

        const bookingModal = document.getElementById('bookingModal');
        const manageModal = document.getElementById('manageModal');
        const defaultDate = document.getElementById('filterDate').value;

        // Idle Check System
        let idleTime = 0;
        window.onload = resetIdle;
        window.onmousemove = resetIdle;
        window.onkeypress = resetIdle;
        function resetIdle() { idleTime = 0; }
        
        setInterval(function() {
            idleTime += 1;
            const clockEl = document.getElementById('clockStatus');
            if (clockEl) {
                const now = new Date();
                clockEl.innerText = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }

            if (idleTime >= 60) {
                let isAddModalOpen = !bookingModal.classList.contains('hidden');
                let isManageModalOpen = !manageModal.classList.contains('hidden');
                if(!isAddModalOpen && !isManageModalOpen) {
                    location.reload();
                }
            }
        }, 1000);

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
                let year = checkOutDate.getFullYear();
                let month = String(checkOutDate.getMonth() + 1).padStart(2, "0");
                let day = String(checkOutDate.getDate()).padStart(2, "0");
                document.getElementById(prefix + "CheckOut").value = `${year}-${month}-${day}`;
            }
            calculatePrice(prefix);
        }

        function openBookingModal(roomId = '', roomNumber = '', basePrice = 0, discount = 0, allowExtra = 0, extraPrice = 0, maxGuests = 2) {
            bookingModal.classList.remove('hidden');
            document.getElementById('bookRoomId').value = roomId;
            document.getElementById('bookRoomNumberDisplay').innerText = roomNumber ? `ห้อง ${roomNumber}` : '-';
            document.getElementById('bookRoomPrice').value = basePrice;
            document.getElementById('bookRoomDiscount').value = discount;
            document.getElementById('bookMaxGuests').value = maxGuests;
            document.getElementById('bookAdults').value = maxGuests; 
            
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
            
            document.getElementById('bookCheckIn').value = defaultDate;
            updateAdultsLimit('book'); 
            updateCheckOut('book'); 
            document.getElementById('bookBookedDatesDisplay').innerHTML = generateBookedDatesHTML(roomId, null);
            document.querySelector('input[value="existing"]').click();
            toggleCustomerType();
            document.getElementById('customerSearchInput').value = '';
        }
        
        function closeBookingModal() { bookingModal.classList.add('hidden'); }

        // ðŸŒŸ อัปเกรดให้เปิด Modal พร้อมแสดงปุ่มสถานะให้ถูกตาม Context
        function openManageModal(bookingId, roomId, roomNumber, customerName, checkIn, checkOut, adults, hasExtra, status, slipImage = '', totalPrice = '0') {
            manageModal.classList.remove('hidden');
            
            // เตรียมข้อมูลส่งฟอร์มสำหรับสถานะด่วน
            document.getElementById('manageBookingId').value = bookingId;
            document.getElementById('manageRoomId').value = roomId;
            document.getElementById('manageRoomNumberDisplay').innerText = roomNumber;
            document.getElementById('manageCustomerName').innerText = customerName;
            
            // จัดการข้อมูลเลื่อนวัน
            document.getElementById('manageCheckIn').value = checkIn;
            document.getElementById('manageCheckOut').value = checkOut;

            // ðŸŒŸ ซ่อน/โชว์ ปุ่มลัดตามสถานะปัจจุบัน
            const btnApprove = document.getElementById('btnApprove');
            const btnIn = document.getElementById('btnCheckIn');
            const btnOut = document.getElementById('btnCheckOut');
            
            if(status === 'pending') {
                btnApprove.classList.remove('hidden');
                btnIn.classList.add('hidden');
                btnOut.classList.add('hidden');
            } else if(status === 'confirmed') {
                btnApprove.classList.add('hidden');
                btnIn.classList.remove('hidden');
                btnOut.classList.add('hidden');
            } else if(status === 'checked_in') {
                btnApprove.classList.add('hidden');
                btnIn.classList.add('hidden');
                btnOut.classList.remove('hidden');
            } else {
                btnApprove.classList.add('hidden');
                btnIn.classList.add('hidden');
                btnOut.classList.add('hidden');
            }

            const slipContainer = document.getElementById('manageSlipContainer');
            if (slipImage) {
                slipContainer.innerHTML = `
                    <p class="text-xs text-ycPink font-bold uppercase tracking-widest mb-2"><i class="ph-bold ph-receipt"></i> หลักฐานการโอนเงิน (ยอด ฿${totalPrice})</p>
                    <a href="../${slipImage}" target="_blank" class="block w-full h-48 bg-[#0a0a0a] rounded-lg overflow-hidden border border-gray-700 hover:border-ycPink transition-colors relative group">
                        <img src="../${slipImage}" alt="Payment Slip" class="w-full h-full object-contain opacity-80 group-hover:opacity-100 transition-opacity">
                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="bg-ycPink text-white text-xs font-bold px-3 py-1.5 rounded-full"><i class="ph-bold ph-magnifying-glass-plus"></i> คลิกเพื่อดูรูปเต็ม</span>
                        </div>
                    </a>
                `;
                slipContainer.classList.remove('hidden');
            } else if (status === 'pending') {
                slipContainer.innerHTML = `
                    <div class="py-6 border-2 border-dashed border-gray-700 rounded-lg bg-[#0a0a0a]">
                        <i class="ph-bold ph-image-broken text-3xl text-gray-500 mb-2"></i>
                        <p class="text-gray-400 text-sm font-bold">ไม่พบรูปสลิปแนบมา</p>
                        <p class="text-xs text-gray-500 mt-1">ยอดที่ต้องชำระ: ฿${totalPrice}</p>
                    </div>
                `;
                slipContainer.classList.remove('hidden');
            } else {
                slipContainer.innerHTML = '';
                slipContainer.classList.add('hidden');
            }

            let cIn = new Date(checkIn);
            let cOut = new Date(checkOut);
            let nights = Math.ceil(Math.abs(cOut - cIn) / (1000 * 60 * 60 * 24));
            document.getElementById('manageOriginalNights').value = nights;
            document.getElementById('manageBookedDatesDisplay').innerHTML = generateBookedDatesHTML(roomId, bookingId);
            autoShiftCheckOut(); 
            
            // ล็อกไม่ให้เลื่อนวันถ้าเช็คเอาท์แล้ว แต่ถ้า checked_in ให้แก้ไขวันเช็คเอาท์ได้ (ขยายวันพัก)
            if(status === 'checked_out') {
                document.getElementById('manageDateEditSection').classList.add('hidden');
                document.getElementById('manageBtnSubmit').classList.add('hidden');
                document.getElementById('manageDateLockedMessage').classList.remove('hidden');
            } else if(status === 'checked_in') {
                document.getElementById('manageDateEditSection').classList.remove('hidden');
                document.getElementById('manageBtnSubmit').classList.remove('hidden');
                document.getElementById('manageDateLockedMessage').classList.add('hidden');
                // ล็อกวันเช็คอิน แต่ปลดล็อกวันเช็คเอาท์ให้ขยายได้
                document.getElementById('manageCheckIn').readOnly = true;
                document.getElementById('manageCheckIn').classList.add('bg-[#111]', 'text-gray-500', 'cursor-not-allowed');
                document.getElementById('manageCheckOut').readOnly = false;
                document.getElementById('manageCheckOut').classList.remove('bg-[#111]', 'text-gray-500', 'cursor-not-allowed');
            } else {
                document.getElementById('manageDateEditSection').classList.remove('hidden');
                document.getElementById('manageBtnSubmit').classList.remove('hidden');
                document.getElementById('manageDateLockedMessage').classList.add('hidden');
                // ปลดล็อกทั้งสองฟิลด์
                document.getElementById('manageCheckIn').readOnly = false;
                document.getElementById('manageCheckIn').classList.remove('bg-[#111]', 'text-gray-500', 'cursor-not-allowed');
                document.getElementById('manageCheckOut').readOnly = false;
                document.getElementById('manageCheckOut').classList.remove('bg-[#111]', 'text-gray-500', 'cursor-not-allowed');
            }
        }

        // ðŸŒŸ ฟังก์ชันส่งคำสั่งลัด ส่งเข้าตัวเอง(bookings.php) ชัวร์ๆ
        function quickUpdateStatus(actionType, msg) {
            Swal.fire({
                title: 'ยืนยัน?',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#333',
                confirmButtonText: 'ตกลง',
                cancelButtonText: 'ยกเลิก',
                background: '#1a1a1a',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('manageAction').value = actionType;
                    document.getElementById('formManageBooking').action = 'bookings.php';
                    document.getElementById('formManageBooking').submit();
                }
            });
        }

        function closeManageModal() { manageModal.classList.add('hidden'); }

        function autoShiftCheckOut() {
            let inDateStr = document.getElementById('manageCheckIn').value;
            let nights = parseInt(document.getElementById('manageOriginalNights').value) || 1;
            
            if(inDateStr) {
                let outDate = new Date(inDateStr);
                outDate.setDate(outDate.getDate() + nights);
                document.getElementById('manageCheckOut').value = `${outDate.getFullYear()}-${String(outDate.getMonth() + 1).padStart(2, "0")}-${String(outDate.getDate()).padStart(2, "0")}`;
            }
            
            let roomId = document.getElementById('manageRoomId').value;
            let bId = document.getElementById('manageBookingId').value;
            let cIn = document.getElementById('manageCheckIn');
            let cOut = document.getElementById('manageCheckOut');
            let btn = document.getElementById('manageBtnSubmit');
            let warning = document.getElementById('manageDateWarning');

            if(isDateConflict(roomId, cIn.value, cOut.value, bId)) {
                warning.classList.remove('hidden');
                cIn.classList.add('input-error');
                cOut.classList.add('input-error');
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                warning.classList.add('hidden');
                cIn.classList.remove('input-error');
                cOut.classList.remove('input-error');
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        function calculatePrice(prefix) {
            const checkInInput = document.getElementById(prefix + 'CheckIn');
            const checkOutInput = document.getElementById(prefix + 'CheckOut');
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            
            const basePrice = parseFloat(document.getElementById(prefix + 'RoomPrice').value) || 0;
            const discount = parseFloat(document.getElementById(prefix + 'RoomDiscount').value) || 0;
            const extraPrice = parseFloat(document.getElementById(prefix + 'ExtraPrice').value) || 0;
            const isExtraBed = document.getElementById(prefix + 'AddExtraBed').checked;
            const btnSubmit = document.getElementById(prefix + 'BtnSubmit');
            const warningText = document.getElementById(prefix + 'DateWarning');
            const roomId = document.getElementById(prefix + 'RoomId').value;
            const currentBookingId = (prefix === 'manage') ? document.getElementById('manageBookingId').value : null;

            const hasConflict = isDateConflict(roomId, checkInInput.value, checkOutInput.value, currentBookingId);

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

            let netPrice = basePrice;
            if (discount > 0) netPrice = basePrice - (basePrice * (discount / 100));

            if (checkIn && checkOut && checkOut > checkIn && netPrice > 0) {
                const diffTime = Math.abs(checkOut - checkIn);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                let totalPrice = diffDays * netPrice;
                let summaryText = `เข้าพัก ${diffDays} คืน (คืนละ ฿${netPrice.toLocaleString()})`;

                if(isExtraBed) {
                    totalPrice += (diffDays * extraPrice);
                    summaryText += ` + เตียงเสริม ฿${extraPrice.toLocaleString()}/คืน`;
                }

                document.getElementById(prefix + 'NightsDisplay').innerText = summaryText;
                document.getElementById(prefix + 'DisplayTotalPrice').innerText = totalPrice.toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById(prefix + 'InputTotalPrice').value = totalPrice;
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                document.getElementById(prefix + 'NightsDisplay').innerText = `วันที่ไม่ถูกต้อง`;
                document.getElementById(prefix + 'DisplayTotalPrice').innerText = "0.00";
                document.getElementById(prefix + 'InputTotalPrice').value = 0;
                btnSubmit.disabled = true;
                btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        // ปิด Dropdown เมื่อคลิกที่อื่น
        document.addEventListener('click', function(e) {
            const btn = document.getElementById('notiBtn');
            const dropdown = document.getElementById('notiDropdown');
            if(btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        gsap.fromTo(".gs-anim", { y: 30, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: "power3.out" });
        function toggleSlipUpload() {
            const el = document.querySelector('input[name="payment_method"]:checked');
            if(!el) return;
            const method = el.value;
            const slipWrap = document.getElementById('slipUploadWrap');
            if(slipWrap) {
                if (method === 'Bank Transfer') {
                    slipWrap.classList.remove('hidden');
                } else {
                    slipWrap.classList.add('hidden');
                }
            }
        }
        
        // ==========================================
        // 📅 Initialize Flatpickr
        // ==========================================
        flatpickr("input[type=date]", {
            locale: "th",
            altInput: true,
            altFormat: "j F Y",
            dateFormat: "Y-m-d",
            theme: "dark"
        });

        // ==========================================
        // 🔄 ระบบ Auto-Refresh (ทุก 3 นาที)
        // ==========================================
        setInterval(() => {
            let isBookingModalOpen = document.getElementById('bookingModal') && !document.getElementById('bookingModal').classList.contains('hidden');
            let isManageModalOpen = document.getElementById('manageModal') && !document.getElementById('manageModal').classList.contains('hidden');
            
            if(!isBookingModalOpen && !isManageModalOpen) {
                let searchBox = document.querySelector('input[name="search"]');
                if(!searchBox || searchBox.value.trim() === '') {
                    window.location.reload();
                }
            }
        }, 180000);
    </script>
</body>
</html>







