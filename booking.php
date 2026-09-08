<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] :  'th';

// Load dynamic settings
// Removed legacy SQLite path
$site_settings = [];
$room_types_db = [];
try {
    if (true) {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdo = $conn;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) {
            $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        $stmt_rooms = $pdo->query("SELECT id, type_name, base_price, high_price FROM room_types");
        if ($stmt_rooms) {
            foreach ($stmt_rooms->fetchAll(PDO::FETCH_ASSOC) as $rt) {
                $room_types_db[$rt['id']] = $rt;
            }
        }
    }
} catch (Exception $e) {}

// Load Policies
$policies = [];
// Removed legacy SQLite path
try {
    if (true) {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdoPolicy = $conn;
        $pdoPolicy->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmtPolicy = $pdoPolicy->query("SELECT * FROM policies ORDER BY id ASC");
        $policies = $stmtPolicy->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

$hotel_name_en = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'YUNCHA VALLEY RESORT';
$hotel_name_th = 'หุบเขาหยุนชา';
$bank_name = isset($site_settings['bank_name']) ? $site_settings['bank_name'] :  'PromptPay';
$bank_account = isset($site_settings['bank_account']) ? $site_settings['bank_account'] :  '080-342-9396';
$account_name = isset($site_settings['account_name']) ? $site_settings['account_name'] :  'YUNCHA VALLEY RESORT';
$qr_image = !empty($site_settings['qr_image']) ? 'dashboard/uploads/' . $site_settings['qr_image'] : null;
$hotel_logo = !empty($site_settings['hotel_logo']) ? 'dashboard/uploads/' . $site_settings['hotel_logo'] : 'img/logo.png';

$base_price = isset($room_types_db[1]) ? number_format($room_types_db[1]['base_price']) : "2,490";
$high_price = isset($room_types_db[1]) ? number_format($room_types_db[1]['high_price']) : "2,999";

$t = [
    'th' => [
        'nav_home' => 'หน้าแรก', 'nav_rooms' => 'ห้องพัก', 'nav_exp' => 'กิจกรรม', 'nav_policy' => 'นโยบาย', 'nav_contact' => 'ติดต่อที่พัก', 'nav_book' => 'จองห้องพัก',
        'nav_signin' => 'เข้าสู่ระบบ',
        
        'hero_title' => 'สำรองห้องพัก',
        'hero_sub' => 'ร่วมเดินทางสู่ช่วงเวลาแห่งการพักผ่อนเหนือระดับ ณ หุบเขาหยุนชา',
        'hero_scroll' => 'เลื่อนลงเพื่อเริ่มขั้นตอนการจอง',
        
        'step1_title' => 'เลือกห้องพักและวันที่',
        'step2_title' => 'ข้อมูลผู้เข้าพักและชำระเงิน',
        
        'lbl_checkin' => 'วันที่เช็คอิน',
        'lbl_checkout' => 'วันที่เช็คเอาท์',
        'lbl_room' => 'ประเภทห้องพัก',
        'lbl_guests' => 'จำนวนผู้เข้าพัก',
        
        'opt_select_room' => '-- กรุณาเลือกห้องพัก --',
        'lbl_name' => 'ชื่อ-นามสกุล',
        'lbl_email' => 'อีเมล',
        'lbl_phone' => 'เบอร์โทรศัพท์',
        
        'payment_title' => 'ชำระเงินเพื่อยืนยันการจอง',
        'payment_desc' => 'กรุณาสแกน QR Code เพื่อโอนชำระเงินเต็มจำนวน จากนั้นอัปโหลดภาพหลักฐานการโอนเงินด้านล่าง',
        'lbl_slip' => 'อัปโหลดสลิปโอนเงิน (JPG, PNG)',
        
        'btn_check' => 'ตรวจสอบห้องว่าง',
        'btn_submit' => 'ยืนยันการจองห้องพัก',
        'btn_booking' => 'กำลังดำเนินการ...',
        
        'msg_available' => '✓ ห้องพักว่างสำหรับช่วงเวลาที่เลือก',
        'msg_unavailable' => '✕ ห้องพักนี้ไม่ว่างในช่วงเวลาดังกล่าว กรุณาเปลี่ยนห้องหรือวันที่',
        'msg_checking' => 'กำลังตรวจสอบห้องว่าง...',
        
        'footer_rights' => '© 2026 ' . strtoupper($hotel_name_en) . '. สงวนลิขสิทธิ์.'
    ],
    'en' => [
        'nav_home' => 'Home', 'nav_rooms' => 'Rooms', 'nav_exp' => 'Experiences', 'nav_policy' => 'Policies', 'nav_contact' => 'Contact', 'nav_book' => 'Booking',
        'nav_signin' => 'Sign In',
        
        'hero_title' => 'BOOK A SANCTUARY',
        'hero_sub' => 'Begin your journey to a serene escape at Yuncha Valley Resort',
        'hero_scroll' => 'SCROLL DOWN TO BOOK',
        
        'step1_title' => 'ROOM & DATES',
        'step2_title' => 'GUEST DETAILS & PAYMENT',
        
        'lbl_checkin' => 'Check-in Date',
        'lbl_checkout' => 'Check-out Date',
        'lbl_room' => 'Select Room Type',
        'lbl_guests' => 'Number of Guests',
        
        'opt_select_room' => '-- Select a Room Type --',
        'lbl_name' => 'Full Name',
        'lbl_email' => 'Email Address',
        'lbl_phone' => 'Phone Number',
        
        'payment_title' => 'PROMPTPAY BANK TRANSFER',
        'payment_desc' => 'Scan the QR code to make a full payment, then upload your transfer slip below to confirm.',
        'lbl_slip' => 'Upload Payment Slip (JPG, PNG)',
        
        'btn_check' => 'Check Availability',
        'btn_submit' => 'Confirm Reservation',
        'btn_booking' => 'Processing...',
        
        'msg_available' => '✓ Room is available for the selected dates.',
        'msg_unavailable' => '✕ Room is occupied. Please choose another room or change dates.',
        'msg_checking' => 'Checking availability...',
        
        'footer_rights' => '© 2026 ' . strtoupper($hotel_name_en) . '. ALL RIGHTS RESERVED.'
    ]
];

$text = $t[$lang];

// Get pre-selected room from URL query
$selected_room = isset($_GET['room']) ? $_GET['room'] :  '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $text['hero_title'] ?> | <?= htmlspecialchars(strtoupper($hotel_name_en)) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Noto+Serif+SC:wght@400;500;600&family=Noto+Serif+Thai:wght@300;400;500;600&family=Prompt:wght@300;400&display=swap" rel="stylesheet">
    
    <!-- CDNs -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.19/bundled/lenis.min.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chatbot.css?v=<?= time() ?>">

    <style>
        body {
            background-color: #020202;
            color: #ffffff;
            overflow-x: hidden;
        }
        .font-cinzel { font-family: 'Cinzel', serif; }
        .font-serif-thai { font-family: 'Noto Serif Thai', serif; }
        ::-webkit-scrollbar { display: none; }
        @media (any-pointer: fine) {
            body, * { cursor: none !important; }
        }

        /* Custom Scrollbar for specific elements */
        .custom-scrollbar::-webkit-scrollbar { display: block !important; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(217, 119, 6, 0.5); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(217, 119, 6, 0.8); }

        /* Custom Cursor */
        .cursor-dot { position: fixed; top: 0; left: 0; width: 6px; height: 6px; background: #d97706; border-radius: 50%; pointer-events: none; z-index: 100000; transform: translate(-50%, -50%); transition: opacity 0.2s; }
        .cursor-ring { position: fixed; top: 0; left: 0; width: 40px; height: 40px; border: 1px solid rgba(217,119,6,0.6); border-radius: 50%; pointer-events: none; z-index: 99999; transform: translate(-50%, -50%); transition: width 0.3s, height 0.3s, background 0.3s, border 0.3s; display: flex; justify-content: center; align-items: center; color: transparent; font-size: 9px; font-weight: bold; letter-spacing: 2px; }
        @media not all and (any-pointer: fine) {
            .cursor-dot, .cursor-ring { display: none !important; }
        }

        /* Glassmorphism Panel */
        .glass-panel {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* Scrolled Glass Navbar */
        .glass-nav {
            background: transparent;
            border-bottom: 1px solid transparent;
        }
        .glass-nav.scrolled {
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .theme-glow-gold {
            text-shadow: 0 0 15px rgba(217, 119, 6, 0.6);
        }
        .btn-gradient-glow {
            background: linear-gradient(135deg, #f59e0b, #9a3412) !important;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4), inset 0 0 5px rgba(255, 255, 255, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .btn-gradient-glow:hover {
            transform: translateY(-3px) scale(1.02) !important;
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.7), inset 0 0 10px rgba(255, 255, 255, 0.3) !important;
            background: linear-gradient(135deg, #fbbf24, #c2410c) !important;
        }

        /* Ambient Fog Layer */
        .fog-container { position: absolute; inset: 0; overflow: hidden; z-index: 2; pointer-events: none; mix-blend-mode: screen; }
        .fog-layer-1 { position: absolute; height: 100%; width: 300%; opacity: 0.25; background: url('https://raw.githubusercontent.com/danielstuart14/CSS_FOG_ANIMATION/master/fog1.png') repeat-x; background-size: 50% 100%; animation: fogMove 55s linear infinite; }
        .fog-layer-2 { position: absolute; height: 100%; width: 300%; opacity: 0.15; background: url('https://raw.githubusercontent.com/danielstuart14/CSS_FOG_ANIMATION/master/fog2.png') repeat-x; background-size: 50% 100%; animation: fogMove 35s linear infinite reverse; }
        @keyframes fogMove { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100vw, 0, 0); } }

        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 100000;
            transform: translate(-50%, -50%) scale(0.9);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
            opacity: 0;
            pointer-events: none;
        }
        .toast-notification.active {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }

        /* Input styling with gold glow */
        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 12px 14px;
            color: #ffffff;
            font-size: 13px;
            transition: all 0.3s ease;
            outline: none;
        }
        .form-input:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: #d97706;
            box-shadow: 0 0 15px rgba(217, 119, 6, 0.3);
        }
        .form-select {
            width: 100%;
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 12px 14px;
            color: #ffffff;
            font-size: 13px;
            transition: all 0.3s ease;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg fill='white' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
        }
        .form-select:focus {
            border-color: #d97706;
            box-shadow: 0 0 15px rgba(217, 119, 6, 0.3);
        }

        /* Custom Select UI */
        .custom-select-trigger {
            width: 100%;
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 12px 14px;
            color: #ffffff;
            font-size: 13px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .custom-select-trigger:focus, .custom-select-wrapper.open .custom-select-trigger {
            border-color: #d97706;
            box-shadow: 0 0 15px rgba(217, 119, 6, 0.3);
        }
        .custom-select-options {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 100%;
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(217, 119, 6, 0.3);
            border-radius: 12px;
            overflow: hidden;
            z-index: 100;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 15px rgba(217,119,6,0.1);
        }
        .custom-select-wrapper.open .custom-select-options {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        .custom-option {
            padding: 12px 14px;
            font-size: 13px;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .custom-option:last-child {
            border-bottom: none;
        }
        .custom-option:hover {
            background: rgba(217, 119, 6, 0.15);
            color: #f59e0b;
        }

        /* Gold gradient ticket style */
        .ticket-box {
            background: linear-gradient(135deg, rgba(17, 17, 17, 0.95) 0%, rgba(30, 20, 10, 0.9) 100%);
            border: 1px solid rgba(217, 119, 6, 0.3);
            box-shadow: 0 0 30px rgba(217, 119, 6, 0.15);
        }

        /* Custom Calendar Styling */
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 2px;
        }
        .cal-cell {
            aspect-ratio: 2.5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            color: #e4e4e7;
            user-select: none;
            border: 1px solid transparent;
        }
        .cal-cell:hover:not(.cal-disabled):not(.cal-active) {
            background-color: rgba(217, 119, 6, 0.15);
            border-color: rgba(217, 119, 6, 0.3);
            color: #fbbf24;
            box-shadow: 0 0 10px rgba(217, 119, 6, 0.15);
        }
        .cal-disabled {
            color: #9ca3af;
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        .cal-empty {
            pointer-events: none;
            opacity: 0;
        }
        .cal-active {
            background: linear-gradient(135deg, #f59e0b, #9a3412) !important;
            color: #ffffff !important;
            font-weight: bold;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.5);
            border-radius: 12px !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }
        .cal-in-range {
            background-color: rgba(217, 119, 6, 0.12) !important;
            color: #fbbf24 !important;
            border-radius: 0 !important;
            border-top: 1px dashed rgba(217, 119, 6, 0.2);
            border-bottom: 1px dashed rgba(217, 119, 6, 0.2);
        }
        .cal-range-start {
            border-top-left-radius: 12px !important;
            border-bottom-left-radius: 12px !important;
            border-left: 1px solid rgba(217, 119, 6, 0.4);
        }
        .cal-range-end {
            border-top-right-radius: 12px !important;
            border-bottom-right-radius: 12px !important;
            border-right: 1px solid rgba(217, 119, 6, 0.4);
        }
        .cal-today::after {
            content: '';
            width: 4px;
            height: 4px;
            background-color: #d97706;
            border-radius: 50%;
            margin-top: 2px;
        }
        .cal-active.cal-today::after {
            background-color: #ffffff;
        }

        /* Step Progress Line transition */
        .step-line {
            transition: background-color 0.4s ease;
        }

        /* Room card select outline */
        .room-card-selected {
            border-color: #d97706 !important;
            box-shadow: 0 0 25px rgba(217, 119, 6, 0.25) !important;
            background: rgba(217, 119, 6, 0.04) !important;
        }

        /* Receipt Voucher Boarding Pass style */
        .ticket-dashed-line {
            position: relative;
            border-top: 1px dashed rgba(255, 255, 255, 0.15);
        }
        .ticket-dashed-line::before, .ticket-dashed-line::after {
            content: '';
            position: absolute;
            top: -8px;
            width: 16px;
            height: 16px;
            background-color: #020202;
            border-radius: 50%;
        }
        .ticket-dashed-line::before { left: -24px; border-right: 1px solid #d97706; }
        .ticket-dashed-line::after { right: -24px; border-left: 1px solid #d97706; }
    </style>
</head>
<body class="antialiased overflow-x-hidden">

    <!-- Toast Notification -->
    <div id="toast" class="toast-notification flex items-center gap-3 p-4 rounded-xl border glass-panel shadow-2xl max-w-sm">
        <div id="toast-icon" class="w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold"></div>
        <div id="toast-msg" class="text-xs font-medium tracking-wide"></div>
    </div>

    <!-- Custom Cursor -->
    <div class="cursor-dot"></div>
    <div class="cursor-ring"><span id="cursor-text" class="text-[9px] font-bold tracking-widest text-white"></span></div>

    <!-- Background Canvas and Fog overlays -->
    <div class="fixed inset-0 z-0 bg-[#020202]">
        <canvas id="particles-canvas" class="w-full h-full block opacity-70"></canvas>
    </div>
    <div class="fixed inset-0 pointer-events-none z-10 fog-container">
        <div class="fog-layer-1"></div>
        <div class="fog-layer-2"></div>
    </div>
    <div class="fixed inset-0 pointer-events-none z-0" style="background: radial-gradient(circle at 50% 50%, rgba(217,119,6,0.04), transparent 70%);"></div>

    <!-- Header & Navigation -->
    <nav class="fixed top-0 left-0 w-full z-50 px-6 py-5 glass-nav transition-all duration-500" id="navbar">
        <div class="max-w-[1600px] mx-auto flex justify-between items-center w-full">
            
            <a href="index.php" class="flex items-center gap-2 hover-target cursor-pointer drop-shadow-md">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l2.4 7.6L22 12l-7.6 2.4L12 22l-2.4-7.6L2 12l7.6-2.4L12 2z"/></svg>
                <div class="flex flex-col items-center">
                    <span class="font-cinzel text-xl md:text-3xl font-bold text-white block leading-none">Yuncha Valley</span>
                    <span class="font-prompt text-[8px] md:text-[10px] text-gray-400 uppercase tracking-[0.4em] block mt-1.5">RESORT & TEA HOUSE</span>
                </div>
            </a>
            
            <div class="hidden xl:flex gap-10 items-center text-[13px] md:text-sm font-semibold tracking-widest text-white uppercase drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                <a href="index.php" class="nav-item group flex items-center gap-2 hover-target <?= $lang == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= $text['nav_home'] ?></span>
                </a>
                <a href="room/room.php" class="nav-item group flex items-center gap-2 hover-target <?= $lang == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= $text['nav_rooms'] ?></span>
                </a>
                <a href="activities.php" class="nav-item group flex items-center gap-2 hover-target <?= $lang == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= $text['nav_exp'] ?></span>
                </a>
                <a href="policy.php" class="nav-item group flex items-center gap-2 hover-target <?= $lang == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= $text['nav_policy'] ?></span>
                </a>
                <a href="contact.php" class="nav-item group flex items-center gap-2 hover-target <?= $lang == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= $text['nav_contact'] ?></span>
                </a>
            </div>

            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-2 text-sm font-bold tracking-widest">
                    <div class="flex items-center bg-gray-500/30 rounded-full p-1 backdrop-blur-sm border border-white/5 w-fit">
                        <a href="?lang=th<?= $selected_room ? '&room='.$selected_room : '' ?>" class="cursor-pointer relative px-3 py-1.5 md:px-4 md:py-2 rounded-full text-[10px] md:text-[11px] font-black transition-all duration-300 <?= $lang == 'th' ? 'bg-[#d97706] text-black shadow-md' : 'text-gray-300 hover:text-white' ?>">
                            TH
                        </a>
                        <a href="?lang=en<?= $selected_room ? '&room='.$selected_room : '' ?>" class="cursor-pointer relative px-3 py-1.5 md:px-4 md:py-2 rounded-full text-[10px] md:text-[11px] font-black transition-all duration-300 <?= $lang == 'en' ? 'bg-[#d97706] text-black shadow-md' : 'text-gray-300 hover:text-white' ?>">
                            EN
                        </a>
                    </div>
                    
                    <a href="login.php" class="nav-item group cursor-pointer hover-target text-gray-400 hover:text-white uppercase <?= $lang == 'th' ? 'font-serif-thai font-bold text-base' : 'font-cinzel font-bold' ?> ml-2">
                        <span class="nav-text relative pb-1 whitespace-nowrap"><?= $text['nav_signin'] ?></span>
                    </a>
                </div>
                
                <a href="booking.php" class="hidden md:flex items-center gap-2 hover-target text-sm tracking-[0.2em] btn-gradient-glow px-8 py-3.5 rounded-full uppercase active <?= $lang == 'th' ? 'font-serif-thai font-bold' : '' ?>">
                    <span class="whitespace-nowrap font-semibold"><?= $text['nav_book'] ?></span>
                </a>
                
                <div class="flex md:hidden items-center bg-gray-500/30 rounded-full p-1 backdrop-blur-sm border border-white/5 w-fit">
                    <a href="?lang=th<?= $selected_room ? '&room='.$selected_room : '' ?>" onclick="if(typeof currentStep !== 'undefined' && currentStep > 1 && !confirm('<?= $lang === 'en' ? 'Changing language will reset your current booking progress. Continue?' : 'การเปลี่ยนภาษาจะทำให้การจองปัจจุบันถูกรีเซ็ต ต้องการดำเนินการต่อหรือไม่?' ?>')) return false;" class="cursor-pointer relative px-3 py-1.5 rounded-full text-[10px] font-black transition-all duration-300 <?= $lang == 'th' ? 'bg-[#d97706] text-black shadow-md' : 'text-gray-300 hover:text-white' ?>">
                        TH
                    </a>
                    <a href="?lang=en<?= $selected_room ? '&room='.$selected_room : '' ?>" onclick="if(typeof currentStep !== 'undefined' && currentStep > 1 && !confirm('<?= $lang === 'en' ? 'Changing language will reset your current booking progress. Continue?' : 'การเปลี่ยนภาษาจะทำให้การจองปัจจุบันถูกรีเซ็ต ต้องการดำเนินการต่อหรือไม่?' ?>')) return false;" class="cursor-pointer relative px-3 py-1.5 rounded-full text-[10px] font-black transition-all duration-300 <?= $lang == 'en' ? 'bg-[#d97706] text-black shadow-md' : 'text-gray-300 hover:text-white' ?>">
                        EN
                    </a>
                </div>
                
                <button id="hamburger-btn" class="xl:hidden text-white p-2 drop-shadow-md"><svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
            </div>
        </div>
    </nav>

    <!-- Mobile Nav overlay -->
    <div id="mobile-menu" class="fixed inset-0 bg-[#020202]/95 backdrop-blur-2xl z-[60] hidden flex-col items-center justify-center opacity-0 transition-opacity duration-300">
        <button id="close-menu-btn" class="absolute top-6 right-6 text-white p-2"><svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        <div class="flex flex-col items-center gap-6 w-full px-6 mt-10">
            <a href="index.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= $lang == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= $text['nav_home'] ?></span></a>
            <a href="room/room.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= $lang == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= $text['nav_rooms'] ?></span></a>
            <a href="activities.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= $lang == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= $text['nav_exp'] ?></span></a>
            <a href="policy.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= $lang == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= $text['nav_policy'] ?></span></a>
            <a href="contact.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= $lang == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= $text['nav_contact'] ?></span></a>
            <a href="login.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= $lang == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= $text['nav_signin'] ?></span></a>
            <a href="booking.php" class="mobile-link mt-8 bg-[#c2410c] text-white w-full py-4 rounded-full text-center active <?= $lang == 'th' ? 'font-serif-thai text-2xl font-bold' : 'font-cinzel text-xl tracking-widest font-bold' ?>"><?= $text['nav_book'] ?></a>
        </div>
        <div class="flex gap-8 mt-12 text-xl font-cinzel tracking-widest bg-white/5 px-10 py-4 rounded-full">
            <a href="?lang=th<?= $selected_room ? '&room='.$selected_room : '' ?>" class="nav-item <?= $lang == 'th' ? 'text-amber-500' : 'text-gray-500' ?>"><span class="nav-text relative pb-1">TH</span></a><span class="text-gray-700">|</span>
            <a href="?lang=en<?= $selected_room ? '&room='.$selected_room : '' ?>" class="nav-item <?= $lang == 'en' ? 'text-amber-500' : 'text-gray-500' ?>"><span class="nav-text relative pb-1">EN</span></a>
        </div>
    </div>

    <!-- Load html2canvas CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Main Wizard Wrapper -->
    <div class="relative w-full min-h-[100vh] pt-32 pb-24 z-20 flex flex-col items-center">
        
        <!-- Step Wizard Header -->
        <div class="max-w-[1400px] mx-auto px-6 text-center mb-8">
            
            <span class="text-amber-500 font-cinzel text-xs md:text-sm tracking-[0.4em] mb-2 uppercase block"><?= htmlspecialchars(strtoupper($hotel_name_en)) ?></span>
            <h1 class="font-cinzel text-3xl md:text-5xl font-bold text-white tracking-wider uppercase">
                <?= $lang === 'en' ? 'Book a Sanctuary' : 'จองห้องพัก' ?>
            </h1>
        </div>

        <!-- Progress Steps Tracker -->
        <div class="max-w-[1000px] mx-auto px-6 mb-12 flex justify-center items-center gap-2 md:gap-6 text-xs md:text-sm font-semibold tracking-wider text-gray-500 select-none">
            <!-- Step 1 -->
            <div id="step-indicator-1" class="flex items-center gap-2 text-amber-500">
                <span class="w-8 h-8 rounded-full border border-amber-500 bg-amber-500/10 flex items-center justify-center font-bold">1</span>
                <span class="hidden sm:inline"><?= $lang === 'en' ? 'Dates & Guests' : 'วันที่ & ผู้เข้าพัก' ?></span>
            </div>
            <div class="h-[1px] w-8 md:w-16 bg-white/10 step-line" id="step-line-1"></div>
            
            <!-- Step 2 -->
            <div id="step-indicator-2" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full border border-white/10 bg-white/5 flex items-center justify-center font-bold">2</span>
                <span class="hidden sm:inline"><?= $lang === 'en' ? 'Select Room' : 'เลือกห้องพัก' ?></span>
            </div>
            <div class="h-[1px] w-8 md:w-16 bg-white/10 step-line" id="step-line-2"></div>
            
            <!-- Step 3 -->
            <div id="step-indicator-3" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full border border-white/10 bg-white/5 flex items-center justify-center font-bold">3</span>
                <span class="hidden sm:inline"><?= $lang === 'en' ? 'Payment' : 'ข้อมูล & ชำระเงิน' ?></span>
            </div>
            
            <div class="h-[1px] w-8 md:w-16 bg-white/10 step-line" id="step-line-3"></div>
            
            <!-- Step 4 -->
            <div id="step-indicator-4" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full border border-white/10 bg-white/5 flex items-center justify-center font-bold">4</span>
                <span class="hidden sm:inline"><?= $lang === 'en' ? 'Success' : 'สำเร็จ' ?></span>
            </div>
        </div>

        <!-- Split Grid Form Layout -->
        <div class="max-w-[1400px] w-full mx-auto px-6 grid grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Form Cards -->
            <div id="main-left-col" class="col-span-12 lg:col-span-8 flex flex-col gap-6">
                <div class="glass-panel p-6 md:p-10 rounded-3xl shadow-2xl relative transition-all duration-500 min-h-[450px]">
                    
                    <!-- STEP 1 PANEL: Dates & Guests -->
                    <div id="step-panel-1" class="step-panel flex flex-col gap-6">
                        <h2 class="font-cinzel text-lg md:text-xl font-bold text-white tracking-widest uppercase border-b border-white/5 pb-4 mb-2">
                            <?= $lang === 'en' ? '1. Select Dates & Guests' : '1. เลือกวันที่และจำนวนผู้เข้าพัก' ?>
                        </h2>
                        
                        <!-- Custom Premium Calendar -->
                        <div>
                            <label class="block text-xs font-semibold text-amber-500 tracking-wider uppercase mb-3">
                                <?= $lang === 'en' ? 'Select Check-in & Check-out Range' : 'เลือกช่วงวันที่เช็คอิน - เช็คเอาท์' ?>
                            </label>
                            
                            <div class="w-full bg-black/60 border border-white/5 rounded-2xl p-3 shadow-xl relative">
                                <div class="flex justify-between items-center mb-6">
                                    <button type="button" id="cal-prev" class="w-8 h-8 rounded-full border border-white/10 flex items-center justify-center hover:bg-amber-500/10 hover:border-amber-500 transition-colors text-white font-bold">&lt;</button>
                                    <span id="cal-month-year" class="font-cinzel text-sm md:text-base font-bold text-white tracking-wider">May 2026</span>
                                    <button type="button" id="cal-next" class="w-8 h-8 rounded-full border border-white/10 flex items-center justify-center hover:bg-amber-500/10 hover:border-amber-500 transition-colors text-white font-bold">&gt;</button>
                                </div>
                                <div class="grid grid-cols-7 gap-1 md:gap-2 text-center text-[10px] md:text-xs font-bold text-amber-500 uppercase tracking-widest mb-4">
                                    <span><?= $lang === 'en' ? 'Su' : 'อา' ?></span>
                                    <span><?= $lang === 'en' ? 'Mo' : 'จ' ?></span>
                                    <span><?= $lang === 'en' ? 'Tu' : 'อ' ?></span>
                                    <span><?= $lang === 'en' ? 'We' : 'พ' ?></span>
                                    <span><?= $lang === 'en' ? 'Th' : 'พฤ' ?></span>
                                    <span><?= $lang === 'en' ? 'Fr' : 'ศ' ?></span>
                                    <span><?= $lang === 'en' ? 'Sa' : 'ส' ?></span>
                                </div>
                                <div id="cal-days-grid" class="cal-grid">
                                    <!-- Days populated dynamically by JS -->
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <!-- STEP 2 PANEL: Select Room -->
                    <div id="step-panel-2" class="step-panel hidden flex flex-col gap-6">
                        <h2 class="font-cinzel text-lg md:text-xl font-bold text-white tracking-widest uppercase border-b border-white/5 pb-4 mb-2">
                            <?= $lang === 'en' ? '2. Select Your Sanctuary' : '2. เลือกห้องพักของคุณ' ?>
                        </h2>
                        
                        <div id="room-selection-loading" class="py-16 text-center text-gray-400 text-sm">
                            <span class="inline-block animate-pulse"><?= $text['msg_checking'] ?></span>
                        </div>
                        
                        <!-- List of rooms with photos -->
                        <div id="room-selection-list" class="flex flex-col gap-6 hidden">
                            <!-- Populated dynamically by JS -->
                        </div>
                        
                        <div class="flex justify-between items-center mt-4 border-t border-white/5 pt-6">
                            <button type="button" id="btn-back-to-step1" class="px-6 py-3 rounded-xl border border-white/10 hover:border-amber-500 text-white font-semibold text-xs tracking-wider uppercase hover:bg-amber-500/5 transition-all">
                                &larr; <?= $lang === 'en' ? 'Back' : 'ย้อนกลับ' ?>
                            </button>
                            <button type="button" id="btn-to-step3" disabled class="px-8 py-3.5 rounded-xl font-bold btn-gradient-glow uppercase tracking-[0.2em] text-xs hover-target disabled:opacity-50 disabled:pointer-events-none">
                                <?= $lang === 'en' ? 'Next: Details & Pay' : 'ขั้นตอนถัดไป: ชำระเงิน' ?> &rarr;
                            </button>
                        </div>
                    </div>
                    
                    <!-- STEP 3 PANEL: Contact Info & Slip Upload -->
                    <div id="step-panel-3" class="step-panel hidden flex flex-col gap-6">
                        <h2 class="font-cinzel text-lg md:text-xl font-bold text-white tracking-widest uppercase border-b border-white/5 pb-4 mb-2">
                            <?= $lang === 'en' ? '3. Guest Details & Payment' : '3. ข้อมูลผู้เข้าพักและชำระเงิน' ?>
                        </h2>
                        
                        <form id="bookingForm" class="flex flex-col md:flex-row gap-6">
                            <!-- Guest contact details -->
                            <div class="w-full md:w-1/2 flex flex-col gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-amber-500 tracking-wider uppercase mb-1.5"><?= $text['lbl_name'] ?></label>
                                    <input type="text" name="name" required class="form-input" placeholder="e.g. John Doe">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-amber-500 tracking-wider uppercase mb-1.5"><?= $text['lbl_email'] ?></label>
                                    <input type="email" name="email" required class="form-input" placeholder="e.g. john@email.com">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-amber-500 tracking-wider uppercase mb-1.5"><?= $text['lbl_phone'] ?></label>
                                    <input type="text" name="phone" required class="form-input" placeholder="e.g. 080-123-4567">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-amber-500 tracking-wider uppercase mb-1.5"><?= $text['lbl_slip'] ?></label>
                                    <div class="relative w-full group">
                                        <input type="file" name="slip" id="slip-input" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 hover-target" onchange="updateFileName(this)" required>
                                        <div class="flex items-center gap-3 bg-zinc-900/50 border border-zinc-700/50 group-hover:border-amber-500/50 rounded-xl p-2 w-full transition-all">
                                            <div class="bg-amber-600/20 text-amber-500 border border-amber-500/30 text-[10px] font-bold tracking-widest uppercase px-4 py-2 rounded-lg whitespace-nowrap group-hover:bg-amber-600 group-hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                <?= $lang === 'en' ? 'Choose File' : 'เลือกรูปภาพ' ?>
                                            </div>
                                            <span id="slip-file-name" class="text-xs text-zinc-500 truncate pr-2"><?= $lang === 'en' ? 'No file chosen' : 'ยังไม่ได้เลือกไฟล์' ?></span>
                                        </div>
                                    </div>
                                    <script>
                                        function updateFileName(input) {
                                            const nameSpan = document.getElementById('slip-file-name');
                                            if (input.files && input.files[0]) {
                                                nameSpan.textContent = input.files[0].name;
                                                nameSpan.classList.remove('text-zinc-500');
                                                nameSpan.classList.add('text-amber-400');
                                            } else {
                                                nameSpan.textContent = '<?= $lang === 'en' ? 'No file chosen' : 'ยังไม่ได้เลือกไฟล์' ?>';
                                                nameSpan.classList.add('text-zinc-500');
                                                nameSpan.classList.remove('text-amber-400');
                                            }
                                        }
                                    </script>
                                </div>
                            </div>
                            
                            <!-- PromptPay QR & Slip -->
                            <div class="w-full md:w-1/2 flex flex-col gap-4">
                                <div class="bg-amber-600/5 border border-amber-500/10 rounded-2xl p-4 flex flex-col gap-2">
                                    <h4 class="text-xs font-bold text-amber-500 font-cinzel tracking-wider uppercase flex items-center gap-2">
                                        💳 <?= $text['payment_title'] ?>
                                    </h4>
                                    <p class="text-[10px] text-gray-400 leading-normal">
                                        <?= $text['payment_desc'] ?>
                                    </p>
                                    
                                    <div class="mx-auto bg-white p-3 rounded-xl flex flex-col items-center shadow-lg w-44 mt-2 relative">
                                        <?php if ($qr_image): ?>
                                            <div class="w-28 h-28 flex items-center justify-center relative">
                                                <img src="<?= $qr_image ?>" alt="QR Code" class="max-w-full max-h-full object-contain">
                                            </div>
                                        <?php else: ?>
                                        <div class="w-28 h-28 bg-gray-100 flex items-center justify-center border border-gray-300 relative">
                                            <svg class="w-24 h-24 text-blue-900" viewBox="0 0 100 100" fill="currentColor">
                                                <rect x="5" y="5" width="20" height="20" />
                                                <rect x="10" y="10" width="10" height="10" fill="white" />
                                                <rect x="75" y="5" width="20" height="20" />
                                                <rect x="80" y="10" width="10" height="10" fill="white" />
                                                <rect x="5" y="75" width="20" height="20" />
                                                <rect x="10" y="80" width="10" height="10" fill="white" />
                                                <rect x="35" y="35" width="30" height="30" />
                                                <rect x="40" y="40" width="20" height="20" fill="white" />
                                                <rect x="30" y="10" width="8" height="8" />
                                                <rect x="45" y="20" width="10" height="8" />
                                                <rect x="60" y="15" width="8" height="15" />
                                                <rect x="15" y="40" width="12" height="12" />
                                                <rect x="75" y="45" width="15" height="10" />
                                                <rect x="70" y="65" width="25" height="10" />
                                                <rect x="10" y="60" width="8" height="10" />
                                                <rect x="35" y="75" width="10" height="20" />
                                                <rect x="50" y="80" width="15" height="8" />
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <span class="text-[9px] font-bold text-blue-900 bg-white px-1 border border-blue-900 rounded"><?= htmlspecialchars($bank_name) ?></span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <span class="text-[10px] font-bold text-gray-800 tracking-wider mt-1.5 font-mono"><?= htmlspecialchars($bank_account) ?></span>
                                        <span class="text-[9px] font-bold text-blue-900 mt-1 text-center leading-tight"><?= htmlspecialchars($hotel_name_en) ?><br><span class="font-normal text-gray-500 text-[8px]">(<?= htmlspecialchars($hotel_name_th) ?>)</span></span>
                                    </div>
                                    
                                    <div class="text-center mt-3 p-2 bg-amber-500/10 border border-amber-500/20 rounded-xl">
                                        <span class="block text-[10px] text-amber-500 uppercase font-semibold tracking-wider">ชื่อบัญชี / Account Name</span>
                                        <span class="text-xs font-bold text-white tracking-widest font-cinzel"><?= htmlspecialchars($account_name) ?></span>
                                    </div>
                                </div>
                                <!-- Old Slip location removed -->
                            </div>
                        </form>
                        
                        <div class="flex justify-between items-center mt-4 border-t border-white/5 pt-6">
                            <button type="button" id="btn-back-to-step2" class="px-6 py-3 rounded-xl border border-white/10 hover:border-amber-500 text-white font-semibold text-xs tracking-wider uppercase hover:bg-amber-500/5 transition-all">
                                &larr; <?= $lang === 'en' ? 'Back' : 'ย้อนกลับ' ?>
                            </button>
                            <button type="button" id="btn-submit-booking" class="px-8 py-3.5 rounded-xl font-bold btn-gradient-glow uppercase tracking-[0.2em] text-xs hover-target">
                                <?= $text['btn_submit'] ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- SUCCESS PANEL: White Receipt -->
                    <div id="step-panel-success" class="step-panel hidden flex flex-col items-center gap-6 w-full">
                        
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-400 px-6 py-6 rounded-xl text-left font-prompt w-full max-w-2xl mx-auto shadow-xl shadow-amber-500/10">
                            <h3 class="text-xl sm:text-2xl font-black text-red-600 mb-3 flex items-center gap-2">
                                <i class="ph-fill ph-warning-circle text-3xl animate-pulse"></i> <?= $lang === 'en' ? '🚨 CRITICAL ALERT!' : '🚨 แจ้งเตือนสำคัญมาก!' ?>
                            </h3>
                            <p class="text-gray-800 text-sm sm:text-base font-bold mb-4">
                                <?= $lang === 'en' 
                                    ? 'Please screenshot or download your receipt containing the <span class="text-red-600 underline">Booking ID</span> below to check your booking status later.'
                                    : 'กรุณาแคปหน้าจอ หรือดาวน์โหลดใบเสร็จที่มี <span class="text-red-600 underline">รหัสการจอง (Booking ID)</span> ด้านล่างนี้เก็บไว้ เพื่อใช้ตรวจสอบสถานะการจอง' 
                                ?>
                            </p>
                            
                            <div class="bg-white/80 rounded-lg p-4 border border-amber-200">
                                <h4 class="font-bold text-amber-800 flex items-center gap-2 mb-2"><i class="ph-fill ph-lightbulb"></i> <?= $lang === 'en' ? '💡 If you lose your receipt:' : '💡 กรณีทำใบเสร็จหาย:' ?></h4>
                                <p class="text-sm text-gray-700 mb-2">
                                    <?= $lang === 'en' 
                                        ? 'You can log in later to download a new receipt at any time. For security, you will need 2 pieces of information:'
                                        : 'คุณลูกค้าสามารถเข้าสู่ระบบในภายหลัง เพื่อดาวน์โหลดใบเสร็จใหม่ได้ตลอดเวลา โดยจะต้องใช้ข้อมูล 2 ส่วนประกอบกันเพื่อความปลอดภัย ดังนี้:' 
                                    ?>
                                </p>
                                <ul class="list-disc list-inside text-sm text-gray-800 font-bold ml-2 space-y-1">
                                    <li><?= $lang === 'en' ? 'Booking ID' : 'รหัสการจอง (Booking ID)' ?></li>
                                    <li><?= $lang === 'en' ? 'Phone Number used for booking' : 'เบอร์โทรศัพท์ ที่ใช้จอง' ?></li>
                                </ul>
                            </div>
                        </div>

                        <!-- White Receipt Document -->
                        <div id="receipt-voucher" class="bg-white border border-gray-200 shadow-xl rounded-lg p-6 md:p-8 text-gray-800 relative transition-all duration-300 w-full max-w-2xl mx-auto">
                            <!-- Header -->
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b-2 border-gray-100 pb-6 mb-6 gap-6">
                                <div class="flex items-center gap-4">
                                    <img id="rec_hotel_logo" src="<?= $hotel_logo ?>" alt="Hotel Logo" class="w-16 h-16 object-contain">
                                    <div>
                                        <h2 class="font-cinzel text-xl md:text-2xl font-bold text-gray-900 tracking-wider uppercase leading-tight" id="rec_hotel_name"><?= htmlspecialchars($hotel_name_en) ?></h2>
                                        <p class="font-prompt text-xs text-gray-500 mt-1"><?= htmlspecialchars(isset($site_settings['address']) ? $site_settings['address'] : '123 Tea Mountain Rd, Chiang Rai') ?></p>
                                        <p class="font-prompt text-[10px] text-gray-400 mt-0.5">
                                            Tel: <?= htmlspecialchars(isset($site_settings['phone']) ? $site_settings['phone'] : '080-342-9396') ?> | Email: <?= htmlspecialchars(isset($site_settings['email']) ? $site_settings['email'] : 'stay@yunchavalley.com') ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-start md:items-end gap-2 text-left md:text-right">
                                    <h1 class="font-cinzel text-2xl md:text-3xl font-bold text-amber-600 tracking-widest uppercase">RECEIPT</h1>
                                    <span class="text-xs text-gray-500 font-mono mt-1">Booking ID: <span class="text-gray-900 font-bold" id="receipt-booking-id"></span></span>
                                    <span class="text-[10px] text-gray-400 font-mono">Date: <span id="receipt-created-at"></span></span>
                                </div>
                            </div>

                            <!-- Guest & Booking Info Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-b-2 border-gray-100 pb-6">
                                <div>
                                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 border-b border-gray-100 pb-1 font-cinzel">Guest Info</h3>
                                    <div class="space-y-1.5 text-xs text-gray-700 font-prompt">
                                        <div class="flex justify-between"><span class="text-gray-500">Name:</span> <span class="font-semibold" id="receipt-guest-name"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Phone:</span> <span class="font-mono" id="receipt-guest-phone"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Email:</span> <span class="font-mono" id="receipt-guest-email"></span></div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 border-b border-gray-100 pb-1 font-cinzel">Reservation Details</h3>
                                    <div class="space-y-1.5 text-xs text-gray-700 font-prompt">
                                        <div class="flex justify-between"><span class="text-gray-500">Check-in:</span> <span class="font-semibold" id="receipt-check-in"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Check-out:</span> <span class="font-semibold" id="receipt-check-out"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Room:</span> <span class="font-semibold" id="receipt-room-name"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Room Code:</span> <span class="font-semibold" id="receipt-room-number">-</span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Guests:</span> <span class="font-semibold" id="receipt-guests"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Nights:</span> <span class="font-semibold"><span id="receipt-nights"></span> Nights</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Itemized Table -->
                            <div class="mb-8 overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs font-prompt min-w-[500px]">
                                    <thead>
                                        <tr class="border-b-2 border-gray-200 text-gray-500 uppercase tracking-widest font-bold font-cinzel pb-2">
                                            <th class="py-3 px-2">Description</th>
                                            <th class="py-3 px-2 text-center">Nights</th>
                                            <th class="py-3 px-2 text-right">Unit Price</th>
                                            <th class="py-3 px-2 text-right">Amount (THB)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="receipt-items-body" class="text-gray-700 divide-y divide-gray-100">
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary Row -->
                            <div class="flex flex-col md:flex-row justify-between items-end gap-6">
                                <div class="w-full md:w-auto">
                                    <h4 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2 font-cinzel">Payment Method</h4>
                                    <div class="border border-gray-200 p-3 rounded-xl flex items-center gap-3 bg-gray-50">
                                        <div class="text-xl">💳</div>
                                        <div class="flex flex-col font-prompt">
                                            <span class="text-xs font-bold text-gray-800">Bank Transfer / PromptPay</span>
                                            <span class="text-[9px] text-emerald-600 font-bold uppercase tracking-wider">Verified & Approved</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2">
                                    <div class="bg-gray-50 border border-gray-200 p-5 rounded-xl space-y-2.5 text-xs font-prompt text-gray-600">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Subtotal</span>
                                            <span class="font-mono text-gray-800" id="receipt-subtotal"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">VAT (7%) Included</span>
                                            <span class="font-mono text-gray-800" id="receipt-vat"></span>
                                        </div>
                                        <div id="receipt-discount-row" class="flex justify-between text-rose-500 hidden">
                                            <span class="text-gray-500">Discount (<span id="receipt-discount-percent"></span>%)</span>
                                            <span class="font-mono" id="receipt-discount-amount"></span>
                                        </div>
                                        <div class="flex justify-between items-center border-t-2 border-gray-200 pt-3 mt-1">
                                            <span class="font-bold text-sm font-cinzel text-gray-800 tracking-wider">Total Amount</span>
                                            <span class="font-mono text-2xl font-bold text-emerald-600" id="receipt-total-price"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="mt-8 border-t-2 border-gray-100 pt-6 text-[9px] text-gray-500">
                                <h4 class="font-bold font-cinzel uppercase tracking-widest text-gray-400 mb-2">Terms & Conditions</h4>
                                <ul class="list-disc pl-4 space-y-1 font-prompt" id="rec_terms">
                                    <?= isset($site_settings['msg_receipt_note']) ? $site_settings['msg_receipt_note'] : "<li>Check-in: 14:00 onwards. Check-out: before 12:00.</li><li>A security deposit of 500 THB is required upon check-in.</li><li>Cancellation Policy: Free cancellation up to 7 days before check-in.</li><li>This is a computer-generated document. No signature is required.</li>" ?>
                                </ul>
                            </div>

                            <!-- Footer -->
                            <div class="text-center mt-8 border-t-2 border-gray-100 pt-4">
                                <p class="font-cinzel text-xs text-gray-400 italic mb-1">"<?= htmlspecialchars(isset($site_settings['msg_receipt_footer']) ? $site_settings['msg_receipt_footer'] : 'Thank you for staying with ' . $hotel_name_en) ?>"</p>
                                <p class="text-[8px] text-gray-400 mt-1 tracking-widest uppercase font-prompt"><?= htmlspecialchars(strtoupper($hotel_name_en)) ?> CO., LTD. | TAX ID: <?= htmlspecialchars(isset($site_settings['tax_id']) ? $site_settings['tax_id'] : '0123456789012') ?></p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4 w-full max-w-2xl mt-2 justify-center">
                            <button type="button" id="btn-download-slip-png" class="flex-1 py-4 rounded-xl font-bold bg-[#d97706] hover:bg-amber-600 text-white uppercase tracking-[0.1em] text-sm hover-target transition-all flex items-center justify-center gap-2 shadow-lg shadow-amber-600/25">
                                <i class="ph-bold ph-image text-xl"></i> ดาวน์โหลดเป็นรูปภาพ (PNG)
                            </button>
                            <button type="button" id="btn-download-slip-pdf" class="flex-1 py-4 rounded-xl font-bold bg-[#059669] hover:bg-emerald-600 text-white uppercase tracking-[0.1em] text-sm hover-target transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/25">
                                <i class="ph-bold ph-file-pdf text-xl"></i> ดาวน์โหลดเป็น PDF
                            </button>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="index.php" class="text-amber-500 hover:text-white font-prompt underline underline-offset-4 text-sm transition-colors">
                                กลับหน้าแรก (Back to Homepage)
                            </a>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Booking Summary Card -->
            <div id="main-right-col" class="col-span-12 lg:col-span-4 sticky top-28">
                <div class="glass-panel p-6 md:p-8 rounded-3xl shadow-2xl relative border border-amber-500/30 shadow-[0_0_20px_rgba(217,119,6,0.1)]">
                    
                    <!-- Step 1 Controls -->
                    <div id="sidebar-step1-controls" class="flex flex-col gap-5">
                        <div class="flex justify-between items-center border-b border-white/10 pb-4 mb-2">
                            <h3 class="font-serif-thai text-xl font-bold text-amber-500 tracking-wide">
                                <?= $lang === 'en' ? 'Options' : 'ตัวเลือกห้องพัก' ?>
                            </h3>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-amber-500 tracking-wider uppercase mb-2"><?= $lang === 'en' ? 'Room Size' : 'ขนาดห้องพัก' ?></label>
                            <select id="room_size_select" class="custom-select-auto hover-target">
                                <option value="" disabled selected><?= $lang === 'en' ? 'Select Size' : 'เลือกขนาด' ?></option>
                                <option value="2"><?= $lang === 'en' ? 'Room for 2' : 'ห้องสำหรับ 2 ท่าน' ?></option>
                                <option value="4"><?= $lang === 'en' ? 'Room for 4' : 'ห้องสำหรับ 4 ท่าน' ?></option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-amber-500 tracking-wider uppercase mb-2"><?= $lang === 'en' ? 'Extra Bed' : 'เตียงเสริม' ?></label>
                            <select id="extra_bed_select" class="custom-select-auto hover-target">
                                <option value="0"><?= $lang === 'en' ? 'No Extra Bed' : 'ไม่รับเตียงเสริม' ?></option>
                                <option value="1"><?= $lang === 'en' ? '1 Extra Bed' : 'เพิ่ม 1 เตียงเสริม' ?></option>
                            </select>
                        </div>
                        
                        <button type="button" id="btn-to-step2" class="w-full mt-4 py-4 rounded-xl font-bold btn-gradient-glow uppercase tracking-[0.2em] text-xs hover-target flex items-center justify-center gap-2">
                            <?= $lang === 'en' ? 'Next Step' : 'ขั้นตอนถัดไป' ?> &rarr;
                        </button>
                    </div>

                    <!-- Pricing Summary (Step 2 & 3) -->
                    <div id="sidebar-pricing" class="hidden flex-col gap-4 text-[13px] font-prompt text-gray-400">
                        <div class="flex justify-between items-center border-b border-white/10 pb-4 mb-2">
                            <h3 class="font-serif-thai text-xl font-bold text-amber-500 tracking-wide">
                                <?= $lang === 'en' ? 'Booking Summary' : 'สรุปการจอง' ?>
                            </h3>
                            <span class="text-white/10 font-cinzel text-4xl border border-white/5 p-2 rounded-xl leading-none">茶</span>
                        </div>
                        
                        <!-- Dynamic Selected Booking Details -->
                        <div class="hidden flex-col gap-3" id="dynamic-summary-details">
                            <div class="flex justify-between items-start">
                                <span class="text-xs uppercase tracking-wider text-amber-500/80"><?= $lang === 'en' ? 'Selected Dates' : 'รายละเอียดการเลือกวัน' ?></span>
                                <span id="summary-dates" class="font-semibold text-white text-right text-xs">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs uppercase tracking-wider text-amber-500/80"><?= $lang === 'en' ? 'Guests' : 'จำนวนคน' ?></span>
                                <span id="summary-guests" class="font-semibold text-white text-right text-xs">-</span>
                            </div>
                            <div class="flex justify-between items-center mt-2 border-b border-white/5 pb-2">
                                <span class="text-xs uppercase tracking-wider text-amber-500/80"><?= $lang === 'en' ? 'Room Type' : 'ประเภทห้อง' ?></span>
                                <span id="summary-room" class="font-semibold text-amber-500 text-right text-xs">-</span>
                            </div>
                            <div id="summary-discount"></div>
                        </div>

                        <!-- Price Breakdown -->
                        <div id="summary-price-breakdown" class="hidden flex-col gap-2 pt-4 mt-2 border-t border-white/5">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-400"><?= $lang === 'en' ? 'Room Price' : 'ราคาห้องพัก' ?></span>
                                <span class="font-cinzel text-sm text-gray-300" id="summary-room-price">฿0</span>
                            </div>
                            <div class="flex justify-between items-center" id="summary-extra-bed-row" style="display: none;">
                                <span class="text-xs text-amber-500/80"><?= $lang === 'en' ? 'Extra Bed' : 'ราคาเตียงเสริม' ?></span>
                                <span class="font-cinzel text-sm text-amber-500" id="summary-extra-bed-price">฿0</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 mt-2 border-t border-white/5">
                            <span class="text-sm font-semibold"><?= $lang === 'en' ? 'Total Price' : 'ราคารวม' ?></span>
                            <span class="font-cinzel text-3xl md:text-4xl text-white font-bold theme-glow-gold drop-shadow-md" id="summary-total-price">฿0</span>
                        </div>
                        

                        <p class="text-[10px] text-gray-500 mt-2 text-center leading-normal">
                            <?= $lang === 'en' ? 'Prices may vary based on actual selected dates.<br>+ 500 THB Security Deposit at Check-in.' : 'ราคาอาจเปลี่ยนแปลงตามวันที่เลือกจริง<br>+ มัดจำประกัน 500 บาท ตอนเช็คอิน' ?>
                        </p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Footer Rights (Fixed Bottom for Clean Layout) -->
    <div class="fixed bottom-4 left-0 w-full text-center z-10 pointer-events-none opacity-40">
        <p class="text-[9px] text-gray-400 tracking-widest uppercase font-cinzel"><?= $text['footer_rights'] ?></p>
    </div>

    <!-- Wizard Javascript & Calendar Logic -->
    <script>
        // -------------------------------------------------------------
        // Core Setup & Lenis Scroll
        // -------------------------------------------------------------
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smooth: true
        });
        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);

        // -------------------------------------------------------------
        // Toast Notifications
        // -------------------------------------------------------------
        const toast = document.getElementById('toast');
        const toastIcon = document.getElementById('toast-icon');
        const toastMsg = document.getElementById('toast-msg');

        function showToast(message, isSuccess = true) {
            toastMsg.textContent = message;
            if (isSuccess) {
                toastIcon.innerHTML = '✓';
                toastIcon.className = 'w-6 h-6 rounded-full flex items-center justify-center text-emerald-500 bg-emerald-500/10 border border-emerald-500/20 text-sm font-bold';
            } else {
                toastIcon.innerHTML = '✕';
                toastIcon.className = 'w-6 h-6 rounded-full flex items-center justify-center text-rose-500 bg-rose-500/10 border border-rose-500/20 text-sm font-bold';
            }
            toast.classList.add('active');
            setTimeout(() => {
                toast.classList.remove('active');
            }, 4500);
        }


        // Floating background particles canvas
        const canvas = document.getElementById('particles-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        class Particle {
            constructor() { this.reset(); }
            reset() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 0.5;
                this.speedX = Math.random() * 0.4 - 0.2;
                this.speedY = Math.random() * 0.3 - 0.5;
                this.opacity = Math.random() * 0.5 + 0.2;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.y < -10 || this.x < -10 || this.x > canvas.width + 10) {
                    this.reset();
                    this.y = canvas.height + 10;
                }
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(217, 119, 6, ${this.opacity})`;
                ctx.fill();
            }
        }
        for (let i = 0; i < 60; i++) particles.push(new Particle());
        function animateParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => { p.update(); p.draw(); });
            requestAnimationFrame(animateParticles);
        }
        animateParticles();

        // -------------------------------------------------------------
        // Wizard Step Controller
        // -------------------------------------------------------------
        let currentStep = 1;

        function goToStep(step) {
            document.querySelectorAll('.step-panel').forEach(p => p.classList.add('hidden'));
            
            if (step === 'success') {
                document.getElementById('step-panel-success').classList.remove('hidden');
                // Hide summary column for successful slip display
                document.querySelector('.lg\\:col-span-4').classList.add('hidden');
                document.querySelector('.lg\\:col-span-8').className = "col-span-12 w-full flex flex-col gap-6";
                return;
            }
            
            document.getElementById(`step-panel-${step}`).classList.remove('hidden');

            // Update Progress Header Line & Circle highlight
            for (let i = 1; i <= 3; i++) {
                const indicator = document.getElementById(`step-indicator-${i}`);
                const circle = indicator.querySelector('span');
                const line = document.getElementById(`step-line-${i-1}`);

                if (i === step) {
                    indicator.className = "flex items-center gap-2 text-amber-500 font-bold";
                    circle.className = "w-8 h-8 rounded-full border border-amber-500 bg-amber-500/10 flex items-center justify-center font-bold shadow-[0_0_10px_rgba(217,119,6,0.3)]";
                } else if (i < step) {
                    indicator.className = "flex items-center gap-2 text-emerald-500 font-bold";
                    circle.className = "w-8 h-8 rounded-full border border-emerald-500 bg-emerald-500/10 flex items-center justify-center font-bold";
                } else {
                    indicator.className = "flex items-center gap-2 text-gray-500";
                    circle.className = "w-8 h-8 rounded-full border border-white/10 bg-white/5 flex items-center justify-center font-bold";
                }

                if (line) {
                    if (i <= step) {
                        line.className = "h-[1px] w-8 md:w-16 bg-amber-500 step-line";
                    } else {
                        line.className = "h-[1px] w-8 md:w-16 bg-white/10 step-line";
                    }
                }
            }
            
            const leftCol = document.getElementById('main-left-col');
            const rightCol = document.getElementById('main-right-col');

            if (step === 1) {
                document.getElementById('sidebar-step1-controls').classList.remove('hidden');
                document.getElementById('sidebar-step1-controls').classList.add('flex');
                document.getElementById('sidebar-pricing').classList.remove('flex');
                document.getElementById('sidebar-pricing').classList.add('hidden');
                
                rightCol.classList.remove('hidden');
                leftCol.className = "col-span-12 lg:col-span-8 flex flex-col gap-6";
            } else if (step === 2) {
                // Hide sidebar completely in Step 2 and expand left column
                rightCol.classList.add('hidden');
                leftCol.className = "col-span-12 w-full flex flex-col gap-6";
            } else {
                // Step 3
                rightCol.classList.remove('hidden');
                leftCol.className = "col-span-12 lg:col-span-8 flex flex-col gap-6";
                
                document.getElementById('sidebar-step1-controls').classList.add('hidden');
                document.getElementById('sidebar-step1-controls').classList.remove('flex');
                document.getElementById('sidebar-pricing').classList.add('flex');
                document.getElementById('sidebar-pricing').classList.remove('hidden');
            }
            
            currentStep = step;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Mobile Menu Toggling
        const mobileMenu = document.getElementById('mobile-menu');
        if(document.getElementById('hamburger-btn')) {
            document.getElementById('hamburger-btn').addEventListener('click', () => {
                mobileMenu.classList.remove('hidden');
                mobileMenu.classList.add('flex');
                setTimeout(() => { mobileMenu.classList.remove('opacity-0'); }, 50);
            });
        }
        if(document.getElementById('close-menu-btn')) {
            document.getElementById('close-menu-btn').addEventListener('click', () => {
                mobileMenu.classList.add('opacity-0');
                setTimeout(() => { mobileMenu.classList.add('hidden'); mobileMenu.classList.remove('flex'); }, 300);
            });
        }

        // -------------------------------------------------------------
        // Custom Calendar System
        // -------------------------------------------------------------
        const calMonthYear = document.getElementById('cal-month-year');
        const calDaysGrid = document.getElementById('cal-days-grid');
        
        let today = new Date();
        let calMonth = today.getMonth();
        let calYear = today.getFullYear();
        
        const initUrlParams = new URLSearchParams(window.location.search);
        let checkInStr = initUrlParams.get('checkin') || "";
        let checkOutStr = initUrlParams.get('checkout') || "";
        let initGuests = initUrlParams.get('guests') || "2";
        
        // If the URL provided dates, adjust the calendar month/year to show the check-in month
        if (checkInStr) {
            const ciDate = new Date(checkInStr);
            if (!isNaN(ciDate)) {
                calMonth = ciDate.getMonth();
                calYear = ciDate.getFullYear();
            }
        }
        
        let selectedRoomId = "";
        let selectedRoomDetails = null;
        let availableRoomsList = [];

        const lang = "<?= $lang ?>";
        const monthNames = {
            'th': ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'],
            'en': ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
        };

        function renderCalendar() {
            calDaysGrid.innerHTML = "";
            calMonthYear.textContent = monthNames[lang][calMonth] + " " + calYear;

            const firstDayIndex = new Date(calYear, calMonth, 1).getDay();
            const totalDays = new Date(calYear, calMonth + 1, 0).getDate();
            const prevTotalDays = new Date(calYear, calMonth, 0).getDate();

            // Previous Month trailing days
            for (let i = firstDayIndex; i > 0; i--) {
                const cell = document.createElement('div');
                cell.className = "cal-cell cal-empty";
                calDaysGrid.appendChild(cell);
            }

            // Current Month days
            for (let d = 1; d <= totalDays; d++) {
                const cell = document.createElement('div');
                cell.className = "cal-cell hover-target";
                cell.textContent = d;
                
                const dStr = `${calYear}-${String(calMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const cellDate = new Date(calYear, calMonth, d);
                
                // Compare only dates
                const todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                
                if (cellDate < todayMidnight) {
                    cell.classList.add('cal-disabled');
                } else {
                    if (cellDate.getTime() === todayMidnight.getTime()) {
                        cell.classList.add('cal-today');
                    }
                    
                    // Highlight selected Check-in and Check-out
                    if (dStr === checkInStr) {
                        cell.classList.add('cal-active');
                    } else if (dStr === checkOutStr) {
                        cell.classList.add('cal-active');
                    } else if (checkInStr && checkOutStr && dStr > checkInStr && dStr < checkOutStr) {
                        cell.classList.add('cal-in-range');
                        
                        // Check if it's start/end of the calendar row or month edge to style corners
                        const dayOfWeek = cellDate.getDay();
                        if (dayOfWeek === 0 || d === 1) {
                            cell.classList.add('cal-range-start');
                        }
                        if (dayOfWeek === 6 || d === totalDays) {
                            cell.classList.add('cal-range-end');
                        }
                    }
                    
                    cell.addEventListener('click', () => handleDateClick(dStr));
                }
                
                calDaysGrid.appendChild(cell);
            }

        }

        function handleDateClick(dateStr) {
            if (!checkInStr || (checkInStr && checkOutStr)) {
                checkInStr = dateStr;
                checkOutStr = "";
            } else {
                if (dateStr > checkInStr) {
                    checkOutStr = dateStr;
                } else if (dateStr === checkInStr) {
                    checkInStr = "";
                    checkOutStr = "";
                } else {
                    checkInStr = dateStr;
                    checkOutStr = "";
                }
            }
            
            // Dates have changed, reset room selection
            selectedRoomId = "";
            selectedRoomDetails = null;
            document.getElementById('btn-to-step3').disabled = true;
            
            renderCalendar();
            updateSummary();
        }

        document.getElementById('cal-prev').addEventListener('click', () => {
            calMonth--;
            if (calMonth < 0) {
                calMonth = 11;
                calYear--;
            }
            renderCalendar();
        });

        document.getElementById('cal-next').addEventListener('click', () => {
            calMonth++;
            if (calMonth > 11) {
                calMonth = 0;
                calYear++;
            }
            renderCalendar();
        });

        // Initialize calendar
        renderCalendar();

        // -------------------------------------------------------------
        // Booking Summary Updater
        // -------------------------------------------------------------
        const summaryDates = document.getElementById('summary-dates');
        const summaryRoom = document.getElementById('summary-room');
        const summaryTotalPrice = document.getElementById('summary-total-price');
        const dynamicSummaryDetails = document.getElementById('dynamic-summary-details');

        function formatDateReadable(isoStr) {
            if (!isoStr) return "-";
            const parts = isoStr.split('-');
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        function calculateNights() {
            if (!checkInStr || !checkOutStr) return 0;
            const inDate = new Date(checkInStr);
            const outDate = new Date(checkOutStr);
            return Math.round((outDate - inDate) / (1000 * 60 * 60 * 24));
        }

        function updateSummary() {
            summaryDates.innerHTML = checkInStr ? `${formatDateReadable(checkInStr)} <br>➔ ${formatDateReadable(checkOutStr || '?')}` : "-";
            
            const nights = calculateNights();

            if (selectedRoomDetails) {
                dynamicSummaryDetails.classList.remove('hidden');
                dynamicSummaryDetails.classList.add('flex');
                
                const priceBreakdown = document.getElementById('summary-price-breakdown');
                if (priceBreakdown) {
                    priceBreakdown.classList.remove('hidden');
                    priceBreakdown.classList.add('flex');
                }
                
                summaryRoom.textContent = lang === 'en' ? selectedRoomDetails.name_en : selectedRoomDetails.name_th;
                document.getElementById('summary-guests').textContent = `${selectedRoomDetails.capacity} ${lang === 'en' ? 'Guests' : 'ท่าน'}`;
                
                if (document.getElementById('summary-room-price')) {
                    document.getElementById('summary-room-price').textContent = `฿${selectedRoomDetails.room_price_total.toLocaleString()}`;
                }
                
                const extraBedRow = document.getElementById('summary-extra-bed-row');
                if (extraBedRow) {
                    if (selectedRoomDetails.extra_bed_price_total > 0) {
                        extraBedRow.style.display = 'flex';
                        document.getElementById('summary-extra-bed-price').textContent = `฿${selectedRoomDetails.extra_bed_price_total.toLocaleString()}`;
                    } else {
                        extraBedRow.style.display = 'none';
                    }
                }
                
                summaryTotalPrice.textContent = `฿${selectedRoomDetails.total_price.toLocaleString()}`;
                
                const summaryDiscount = document.getElementById('summary-discount');
                if (summaryDiscount) {
                    if (selectedRoomDetails.discount_percent > 0) {
                        summaryDiscount.innerHTML = `<div class="flex justify-between items-center mt-2 text-rose-400 text-xs">
                            <span>${lang === 'en' ? 'Discount applied' : 'ส่วนลดที่ได้รับ'} (-${selectedRoomDetails.discount_percent}%)</span>
                            <span>-฿${selectedRoomDetails.discount_amount.toLocaleString()}</span>
                        </div>`;
                    } else {
                        summaryDiscount.innerHTML = "";
                    }
                }
            } else {
                dynamicSummaryDetails.classList.add('hidden');
                if (document.getElementById('summary-price-breakdown')) {
                    document.getElementById('summary-price-breakdown').classList.add('hidden');
                }
                summaryRoom.textContent = "-";
                summaryTotalPrice.textContent = "฿0";
                if (document.getElementById('summary-discount')) document.getElementById('summary-discount').innerHTML = "";
            }
        }

        document.getElementById('room_size_select').addEventListener('change', updateSummary);
        document.getElementById('extra_bed_select').addEventListener('change', updateSummary);

        // -------------------------------------------------------------
        // Step 1 to Step 2 Transition & Availability Check
        // -------------------------------------------------------------
        const btnToStep2 = document.getElementById('btn-to-step2');
        const roomSelectionLoading = document.getElementById('room-selection-loading');
        const roomSelectionList = document.getElementById('room-selection-list');

        const roomSubtitles = {
            'tea-valley': 'ขุนเขาและม่านหมอก / Mountain & Mist',
            'lake-pavilion': 'โคมไฟและเงาสะท้อนของทะเลสาบ / Lanterns & Lake Reflection',
            'peak-residence': 'ท้องฟ้าพาโนรามาและหมู่ดาว / Panoramic Sky & Stargazing'
        };
        const roomSizes = {
            'tea-valley': '32m²',
            'lake-pavilion': '58m²',
            'peak-residence': '85m²'
        };
        const roomViews = {
            'tea-valley': 'Mountain View / วิวภูเขา',
            'lake-pavilion': 'Private Pool & River / สระว่ายน้ำส่วนตัวและแม่น้ำ',
            'peak-residence': 'Panoramic Sky / วิวท้องฟ้าพาโนรามา'
        };

        btnToStep2.addEventListener('click', () => {
            if (!checkInStr || !checkOutStr) {
                showToast(lang === 'en' ? "Please select a complete check-in and check-out range." : "กรุณาเลือกช่วงวันเข้าพักให้สมบูรณ์", false);
                return;
            }
            
            const roomSizeSelect = document.getElementById('room_size_select');
            const extraBedSelect = document.getElementById('extra_bed_select');
            
            if (!roomSizeSelect.value) {
                showToast(lang === 'en' ? "Please select room size." : "กรุณาเลือกขนาดห้อง", false);
                return;
            }

            const roomSize = parseInt(roomSizeSelect.value);
            const extraBed = parseInt(extraBedSelect.value);
            const totalCapacity = roomSize + extraBed;
            
            goToStep(2);
            roomSelectionLoading.classList.remove('hidden');
            roomSelectionList.classList.add('hidden');
            
            // Call API
            fetch(`api/check-availability.php?check_in=${checkInStr}&check_out=${checkOutStr}&room_size=${roomSize}&extra_bed=${extraBed}`)
                .then(res => res.json())
                .then(data => {
                    roomSelectionLoading.classList.add('hidden');
                    if (data.success) {
                        availableRoomsList = data.results;
                        renderRooms();
                    } else {
                        showToast(data.message || "Failed to load rooms.", false);
                        goToStep(1);
                    }
                })
                .catch(err => {
                    showToast("Error connecting to server. Please try again.", false);
                    goToStep(1);
                });
        });

        function renderRooms() {
            roomSelectionList.innerHTML = "";

            availableRoomsList.forEach(room => {
                const isSelected = selectedRoomId === room.room_id;
                const isAvailable = room.available;

                const card = document.createElement('div');
                card.className = `room-card border border-white/10 rounded-2xl overflow-hidden bg-black/40 hover:border-amber-500/30 transition-all duration-300 flex flex-col md:flex-row relative cursor-pointer hover:shadow-lg hover:shadow-amber-500/5 ${isSelected ? 'room-card-selected' : ''}`;
                
                // Show status banners
                let statusOverlay = '';
                if (!room.available) {
                    let altMsg = '';
                    if (room.alternative_message) {
                        altMsg = `<span class="mt-2 px-3 py-1 bg-amber-500/20 border border-amber-500/50 rounded-lg text-amber-500 font-bold text-[10px] text-center max-w-[80%]">${room.alternative_message}</span>`;
                    }
                    statusOverlay = `<div class="absolute inset-0 bg-black/70 backdrop-blur-[2px] flex flex-col items-center justify-center z-10 p-4">
                        <span class="px-4 py-2 border border-rose-500 bg-rose-500/15 rounded-full text-rose-500 font-bold text-xs uppercase tracking-widest">${lang === 'en' ? 'Fully Booked' : 'เต็มแล้ว'}</span>
                        ${altMsg}
                    </div>`;
                }

                let amenitiesHtml = '';
                if (room.amenities && room.amenities.length > 0) {
                    const amenityDict = {
                        'wifi': { icon: 'wifi-high', th: 'ฟรี Wi-Fi', en: 'Free Wi-Fi' },
                        'ac': { icon: 'snowflake', th: 'แอร์', en: 'AC' },
                        'heater': { icon: 'thermometer-hot', th: 'น้ำอุ่น', en: 'Heater' },
                        'bathtub': { icon: 'bathtub', th: 'อ่างอาบน้ำ', en: 'Bathtub' },
                        'nosmoke': { icon: 'warning-circle', th: 'ปลอดบุหรี่', en: 'No Smoking' },
                        'smokearea': { icon: 'cigarette', th: 'สูบบุหรี่ได้', en: 'Smoking Area' },
                        'smarttv': { icon: 'television', th: 'สมาร์ททีวี', en: 'Smart TV' },
                        'minibar': { icon: 'refrigerator', th: 'มินิบาร์', en: 'Mini Bar' },
                        'coffee': { icon: 'coffee', th: 'ชุดชา', en: 'Tea Set' },
                        'hairdryer': { icon: 'wind', th: 'ไดร์เป่าผม', en: 'Hair Dryer' },
                        'teaview': { icon: 'leaf', th: 'วิวไร่ชา', en: 'Tea View' },
                        'mtview': { icon: 'mountains', th: 'วิวภูเขา', en: 'Mountain View' },
                        'rvview': { icon: 'waves', th: 'วิวแม่น้ำ', en: 'River View' },
                        'bfast': { icon: 'cooking-pot', th: 'อาหารเช้า', en: 'Breakfast' },
                        'boat': { icon: 'boat', th: 'ฟรีล่องเรือ', en: 'Free Boat' }
                    };
                    
                    room.amenities.forEach(code => {
                        const am = amenityDict[code];
                        if (am) {
                            const name = lang === 'en' ? am.en : am.th;
                            const fontClass = lang === 'en' ? 'font-prompt' : 'font-serif-thai';
                            amenitiesHtml += `<div class="flex items-center gap-1.5 text-gray-400 hover:text-white transition-colors duration-300 pr-1">
                                <i class="ph ph-${am.icon} text-base"></i>
                                <span class="text-[11px] md:text-[12px] ${fontClass}">${name}</span>
                            </div>`;
                        }
                    });
                }
                
                let sizeVal = 32;
                let viewVal = lang === 'en' ? 'Tea View' : 'วิวไร่ชา';
                if (room.amenities && room.amenities.includes('mtview')) {
                    sizeVal = 45;
                    viewVal = lang === 'en' ? 'Mountain View' : 'วิวภูเขา';
                } else if (room.amenities && room.amenities.includes('rvview')) {
                    sizeVal = 38;
                    viewVal = lang === 'en' ? 'River View' : 'วิวแม่น้ำ';
                }

                let amenitiesGridHtml = amenitiesHtml ? `<div class="grid grid-cols-2 md:grid-cols-4 gap-x-2 gap-y-2 mt-4 pt-4 border-t border-white/5 w-full">${amenitiesHtml}</div>` : '';

                card.innerHTML = `
                    <div class="w-full md:w-1/3 h-56 md:h-auto min-h-[220px] relative overflow-hidden">
                        <img src="${room.image_url}" class="w-full h-full object-cover" alt="${room.name_en}">
                        ${statusOverlay}
                    </div>
                    <div class="w-full md:w-2/3 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-3">
                                <div class="flex-1">
                                    <h3 class="font-cinzel text-xl md:text-2xl font-bold text-amber-500 tracking-wide">${lang === 'en' ? room.name_en : room.name_th}</h3>
                                    <p class="font-prompt text-sm text-gray-400 mt-2 line-clamp-2 leading-relaxed">${lang === 'en' ? (room.details_en || 'Experience luxury and tranquility in our carefully designed room, featuring premium amenities and stunning views.') : (room.details_th || 'สัมผัสความหรูหราและเงียบสงบในห้องพักที่ออกแบบอย่างพิถีพิถัน พร้อมสิ่งอำนวยความสะดวกครบครันและวิวที่งดงาม')}</p>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap items-center justify-start gap-4 mt-5 p-4 rounded-xl bg-[#0a0a0a] border border-white/5 text-sm text-gray-300 font-prompt shadow-inner w-full md:w-fit">
                                <div class="flex items-center gap-2"><i class="ph-fill ph-user text-[#5B42F3]/80 text-lg"></i> Max: ${room.capacity} Guests</div>
                                <div class="w-px h-4 bg-white/10 hidden sm:block"></div>
                                <div class="flex items-center gap-2"><i class="ph-fill ph-triangle text-white/80 text-lg"></i> ${lang === 'en' ? 'Size' : 'ขนาด'}: ${sizeVal} ${lang === 'en' ? 'sq.m.' : 'ตร.ม.'}</div>
                                <div class="w-px h-4 bg-white/10 hidden sm:block"></div>
                                <div class="flex items-center gap-2"><i class="ph-fill ph-bed text-[#5B42F3]/80 text-lg"></i> Bed: ${lang === 'en' ? 'King Bed' : 'เตียงคิงไซส์'}</div>
                                <div class="w-px h-4 bg-white/10 hidden sm:block"></div>
                                <div class="flex items-center gap-2"><i class="ph-fill ph-image text-emerald-500/80 text-lg"></i> ${lang === 'en' ? 'View' : 'วิว'}: ${viewVal}</div>
                            </div>
                            
                            ${amenitiesGridHtml}
                        </div>
                        
                        <div class="flex flex-col md:flex-row justify-between items-stretch md:items-end mt-6 pt-5 border-t border-white/10 font-prompt gap-6">
                            <div class="flex flex-col gap-1.5 flex-1">
                                <div class="text-xl md:text-2xl font-bold ${isAvailable ? 'text-emerald-500' : 'text-gray-500'} flex items-center gap-2">
                                    ${isAvailable ? (lang === 'en' ? `<i class="ph-bold ph-check"></i> ${room.rooms_available} Room(s) Available` : `<i class="ph-bold ph-check"></i> ว่าง ${room.rooms_available} ห้อง`) : (lang === 'en' ? '✕ Unavailable' : '✕ ไม่สามารถจองได้')}
                                </div>
                                ${isAvailable && room.rooms_available > 0 && room.rooms_available <= 2 ? 
                                    `<div class="text-sm text-rose-500 font-bold animate-pulse mt-1">⚡ ${lang === 'en' ? `Only ${room.rooms_available} rooms left!` : `เหลือเพียง ${room.rooms_available} ห้องสุดท้ายสำหรับวันที่เลือก!`}</div>` : 
                                    (isAvailable ? `<div class="text-sm text-amber-500 font-bold mt-1">🔥 ${lang === 'en' ? 'Popular Choice!' : 'ห้องยอดนิยม!'}</div>` : '')
                                }
                            </div>
                            
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-4 sm:gap-6 justify-end">
                                <div class="flex flex-col items-start sm:items-end gap-1 p-4 sm:p-0 bg-white/5 sm:bg-transparent rounded-xl sm:rounded-none">
                                    ${room.discount_percent > 0 ? `<div class="flex items-center gap-1.5 w-full justify-between sm:justify-end"><span class="bg-rose-500 text-white px-2 py-0.5 rounded text-xs font-bold">-${room.discount_percent}%</span><span class="text-xs text-rose-500 line-through font-mono">฿${room.original_price.toLocaleString()}</span></div>` : ''}
                                    
                                    <div class="w-full sm:w-auto flex flex-row sm:flex-col justify-between sm:justify-start items-end sm:items-end mt-1 sm:mt-0">
                                        <div class="text-left sm:text-right hidden sm:block">
                                            <span class="block text-xs text-gray-500 uppercase tracking-widest">${lang === 'en' ? 'Total Price' : 'ราคารวม'}</span>
                                            ${room.nights > 1 ? `<span class="block text-[10px] text-gray-400 mt-0.5 font-medium">(${lang === 'en' ? 'Avg. ฿' : 'เฉลี่ยคืนละ ฿'}${Math.round(room.total_price / room.nights).toLocaleString()}${lang === 'en' ? ' / night' : ''})</span>` : ''}
                                        </div>
                                        <div class="flex items-baseline gap-2">
                                            <span class="block text-xs text-gray-500 uppercase tracking-widest sm:hidden">${lang === 'en' ? 'Total Price' : 'ราคารวม'}</span>
                                            <span class="font-cinzel text-4xl font-bold text-amber-500 drop-shadow-md">฿${room.total_price.toLocaleString()}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-[11px] flex flex-wrap justify-start sm:justify-end gap-1.5 mt-2 w-full">
                                        ${room.weekday_count > 0 ? `<span class="bg-gray-500/10 border border-gray-500/20 px-2 py-0.5 rounded-full text-gray-400">Weekday: ${room.weekday_count}</span>` : ''}
                                        ${room.weekend_count > 0 ? `<span class="bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full text-amber-400">Weekend: ${room.weekend_count}</span>` : ''}
                                        ${room.holiday_count > 0 ? `<span class="bg-rose-500/10 border border-rose-500/20 px-2 py-0.5 rounded-full text-rose-400">Holiday: ${room.holiday_count}</span>` : ''}
                                    </div>
                                </div>
                                
                                <button type="button" class="w-full sm:w-auto h-fit px-8 py-4 sm:py-3 rounded-xl font-bold text-base sm:text-sm uppercase tracking-wider transition-all select-btn ${isAvailable ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-[0_0_15px_rgba(217,119,6,0.3)] hover:shadow-[0_0_25px_rgba(217,119,6,0.5)] hover:-translate-y-0.5' : 'bg-white/5 text-gray-500 cursor-not-allowed'}" ${!isAvailable ? 'disabled' : ''}>
                                    ${isSelected ? (lang === 'en' ? '✓ Selected' : '✓ เลือกอยู่') : (lang === 'en' ? 'Select' : 'เลือก')}
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                if (isAvailable) {
                    card.addEventListener('click', () => {
                        selectedRoomId = room.room_id;
                        selectedRoomDetails = room;
                        document.getElementById('btn-to-step3').disabled = false;
                        
                        renderRooms();
                        updateSummary();
                    });
                }

                roomSelectionList.appendChild(card);
            });

            // If URL had a preselected room, trigger the selection auto-load if available
            const urlParams = new URLSearchParams(window.location.search);
            const preRoom = urlParams.get('room') || urlParams.get('id');
            if (preRoom && !selectedRoomId) {
                const found = availableRoomsList.find(r => r.room_id.toString() === preRoom.toString());
                if (found && found.available) {
                    selectedRoomId = found.room_id;
                    selectedRoomDetails = found;
                    document.getElementById('btn-to-step3').disabled = false;
                    renderRooms();
                    updateSummary();
                }
            }

            roomSelectionList.classList.remove('hidden');

        }

        // -------------------------------------------------------------
        // Step Navigation Buttons
        // -------------------------------------------------------------
        document.getElementById('btn-back-to-step1').addEventListener('click', () => goToStep(1));
        document.getElementById('btn-to-step3').addEventListener('click', () => goToStep(3));

        document.getElementById('btn-back-to-step2').addEventListener('click', () => goToStep(2));

        // -------------------------------------------------------------
        // Booking Submit Handler
        // -------------------------------------------------------------
        const bookingForm = document.getElementById('bookingForm');
        const submitBtn = document.getElementById('btn-submit-booking');

        bookingForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (!checkInStr || !checkOutStr || !selectedRoomId) {
                showToast("Invalid parameters. Please review dates and selected room.", false);
                goToStep(1);
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = "<?= $text['btn_booking'] ?>";
            submitBtn.classList.add('opacity-70');

            const formData = new FormData(bookingForm);
            formData.append('check_in', checkInStr);
            formData.append('check_out', checkOutStr);
            formData.append('room_id', selectedRoomId);
            formData.append('room_size', document.getElementById('room_size_select').value);
            formData.append('extra_bed', document.getElementById('extra_bed_select').value);

            fetch('api/create-booking.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, true);
                    
                    // Populate Receipt Data
                    document.getElementById('receipt-booking-id').innerText = data.booking_id;
                    document.getElementById('receipt-created-at').innerText = data.created_at;
                    document.getElementById('receipt-guest-name').innerText = data.customer_name;
                    document.getElementById('receipt-guest-phone').innerText = data.customer_phone;
                    document.getElementById('receipt-guest-email').innerText = data.customer_email;
                    document.getElementById('receipt-check-in').innerText = data.check_in;
                    document.getElementById('receipt-check-out').innerText = data.check_out;
                    document.getElementById('receipt-room-name').innerText = data.room_name;
                    document.getElementById('receipt-guests').innerText = data.guests;
                    document.getElementById('receipt-nights').innerText = data.nights;
                    document.getElementById('receipt-total-price').innerText = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(data.total_price);
                    
                    const subtotal = data.total_price / 1.07;
                    const vat = data.total_price - subtotal;
                    document.getElementById('receipt-subtotal').innerText = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(subtotal);
                    document.getElementById('receipt-vat').innerText = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(vat);
                    
                    if (data.discount_percent && data.discount_percent > 0) {
                        const discountAmt = Math.round((data.total_price * data.discount_percent) / 100);
                        document.getElementById('receipt-discount-row').classList.remove('hidden');
                        document.getElementById('receipt-discount-percent').innerText = data.discount_percent;
                        document.getElementById('receipt-discount-amount').innerText = '-' + new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(discountAmt);
                    }
                    
                    const tbody = document.getElementById('receipt-items-body');
                    tbody.innerHTML = '';
                    const formatCurrency = (val) => new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(val);
                    
                    if (data.weekday_count > 0) {
                        tbody.innerHTML += `
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-2">
                                    <span class="block font-semibold text-gray-900">${data.room_name} - Weekday</span>
                                    <span class="text-[10px] text-gray-500">Sunday - Thursday</span>
                                </td>
                                <td class="py-3 px-2 text-center text-gray-700">${data.weekday_count}</td>
                                <td class="py-3 px-2 text-right font-mono text-gray-700">${formatCurrency(data.weekday_price)}</td>
                                <td class="py-3 px-2 text-right font-mono text-gray-900">${formatCurrency(data.weekday_count * data.weekday_price)}</td>
                            </tr>
                        `;
                    }
                    if (data.weekend_count > 0) {
                        tbody.innerHTML += `
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-2">
                                    <span class="block font-semibold text-gray-900">${data.room_name} - Weekend</span>
                                    <span class="text-[10px] text-gray-500">Friday - Saturday</span>
                                </td>
                                <td class="py-3 px-2 text-center text-gray-700">${data.weekend_count}</td>
                                <td class="py-3 px-2 text-right font-mono text-gray-700">${formatCurrency(data.weekend_price)}</td>
                                <td class="py-3 px-2 text-right font-mono text-gray-900">${formatCurrency(data.weekend_count * data.weekend_price)}</td>
                            </tr>
                        `;
                    }
                    if (data.holiday_count > 0) {
                        tbody.innerHTML += `
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-2">
                                    <span class="block font-semibold text-gray-900">${data.room_name} - Holiday</span>
                                    <span class="text-[10px] text-gray-500">Public Holidays</span>
                                </td>
                                <td class="py-3 px-2 text-center text-gray-700">${data.holiday_count}</td>
                                <td class="py-3 px-2 text-right font-mono text-gray-700">${formatCurrency(data.holiday_price)}</td>
                                <td class="py-3 px-2 text-right font-mono text-gray-900">${formatCurrency(data.holiday_count * data.holiday_price)}</td>
                            </tr>
                        `;
                    }
                    if (data.extra_beds > 0) {
                        tbody.innerHTML += `
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-2">
                                    <span class="block font-semibold text-gray-900">Extra Bed</span>
                                    <span class="text-[10px] text-gray-500">${data.extra_beds} x ${data.nights} Nights</span>
                                </td>
                                <td class="py-3 px-2 text-center text-gray-700">${data.nights}</td>
                                <td class="py-3 px-2 text-right font-mono text-gray-700">${formatCurrency(data.extra_bed_price)}</td>
                                <td class="py-3 px-2 text-right font-mono text-gray-900">${formatCurrency(data.extra_beds * data.extra_bed_price * data.nights)}</td>
                            </tr>
                        `;
                    }
                    
                    // Hide wizard and sidebar
                    document.querySelectorAll('.step-panel').forEach(p => p.classList.add('hidden'));
                    document.getElementById('main-right-col').classList.add('hidden');
                    
                    // Make left column full width
                    const leftCol = document.getElementById('main-left-col');
                    leftCol.classList.remove('lg:col-span-8');
                    leftCol.classList.add('lg:col-span-12');
                    
                    // Update Step Indicators to Step 4 (Green)
                    document.getElementById('step-indicator-3').classList.remove('text-amber-500');
                    document.getElementById('step-indicator-3').querySelector('span').classList.remove('border-amber-500', 'bg-amber-500/10');
                    document.getElementById('step-indicator-3').querySelector('span').classList.add('border-white/10', 'bg-white/5');
                    document.getElementById('step-line-3').classList.remove('bg-white/10');
                    document.getElementById('step-line-3').classList.add('bg-green-500');
                    
                    const step4 = document.getElementById('step-indicator-4');
                    step4.classList.add('text-green-500');
                    step4.querySelector('span').classList.remove('border-white/10', 'bg-white/5');
                    step4.querySelector('span').classList.add('border-green-500', 'bg-green-500/10');
                    
                    // Show Step 4 (Success Panel)
                    document.getElementById('step-panel-success').classList.remove('hidden');
                    
                    // Generate Dynamic Policy HTML from Database
                    const policies = <?= json_encode($policies) ?>;
                    let policyHtml = '';
                    if (policies && policies.length > 0) {
                        policies.forEach((policy, idx) => {
                            const title = lang === 'en' ? policy.category_name_en : policy.category_name_th;
                            const content = lang === 'en' ? policy.content_en : policy.content_th;
                            // Convert newlines to br tags
                            const formattedContent = content ? content.replace(/\\n/g, '<br>') : '';
                            policyHtml += `<p><strong>${idx + 1}. ${title}:</strong><br><span class="text-sm">${formattedContent}</span></p>`;
                        });
                    } else {
                        // Fallback if db is empty or error
                        policyHtml = `
                            <p><strong>1. เวลาเช็คอิน / Check-in:</strong> 14:00 น. เป็นต้นไป (ไม่สามารถเช็คอินก่อนเวลาได้)</p>
                            <p><strong>2. เวลาเช็คเอาท์ / Check-out:</strong> ก่อน 12:00 น. (หากเกินเวลา จะคิดค่าปรับชั่วโมงละ 500 บาท)</p>
                            <p><strong>3. การยกเลิก / Cancellation:</strong> แจ้งล่วงหน้าอย่างน้อย 7 วันก่อนเข้าพัก คืนเงิน 100% หากแจ้งน้อยกว่า 7 วัน ขอสงวนสิทธิ์ไม่คืนเงินทุกกรณี</p>
                            <p><strong>4. สัตว์เลี้ยง / Pets:</strong> ไม่อนุญาตให้นำสัตว์เลี้ยงทุกชนิดเข้าพัก หากฝ่าฝืนมีค่าปรับ 3,000 บาท และอัญเชิญออกจากที่พักทันที</p>
                            <p><strong>5. ห้ามสูบบุหรี่ / No Smoking:</strong> ห้ามสูบบุหรี่ภายในห้องพัก ระเบียง และพื้นที่ส่วนกลาง (ยกเว้นโซนที่จัดไว้ให้) ฝ่าฝืนปรับ 5,000 บาทตามกฎหมาย</p>
                            <p><strong>6. มัดจำ / Security Deposit:</strong> มีค่ามัดจำกุญแจและคีย์การ์ด 500 บาทตอนเช็คอิน (รับเฉพาะเงินสด) ซึ่งจะคืนให้ตอนเช็คเอาท์หากไม่มีทรัพย์สินเสียหาย</p>
                            <p><strong>7. การประกอบอาหาร:</strong> ไม่อนุญาตให้ประกอบอาหาร หรือนำเตาแก๊ส เตาไฟฟ้า ปิ้งย่าง เข้ามาทำในห้องพักหรือระเบียง</p>
                        `;
                    }
                    
                    // Trigger Full Policy Modal
                    setTimeout(() => {
                        Swal.fire({
                            title: lang === 'en' 
                                ? '<strong>Accommodation Policy <br><span class="text-xs text-gray-500">(Terms & Conditions)</span></strong>' 
                                : '<strong>นโยบายการเข้าพักของหุบเขาหยุนชา <br><span class="text-xs text-gray-500">(Accommodation Policy)</span></strong>',
                            width: 'min(95%, 800px)',
                            html: `
                                <div class="custom-scrollbar text-left text-xs sm:text-sm text-gray-700 font-prompt space-y-3 p-3 sm:p-4 bg-gray-50 border border-gray-200 rounded-lg shadow-inner leading-relaxed max-h-[40vh] overflow-y-auto">
                                    <h4 class="font-bold text-amber-600 border-b border-amber-200 pb-2 mb-2 text-sm sm:text-base">
                                        ${lang === 'en' ? 'Terms & Conditions' : 'ข้อตกลงและเงื่อนไขการเข้าพัก'}
                                    </h4>
                                    <div class="space-y-3">
                                        ${policyHtml}
                                    </div>
                                    <div class="bg-amber-50 text-amber-800 p-3 rounded mt-3 text-[10px] sm:text-xs border border-amber-200">
                                        <p class="font-bold">* ${lang === 'en' ? 'Please click the "I Agree" button below to confirm you have read and accepted all policies, and proceed to download your receipt.' : 'กรุณากดปุ่ม "รับทราบและยอมรับ" ด้านล่าง เพื่อยืนยันว่าคุณได้อ่านและยอมรับนโยบายทั้งหมดแล้ว และสามารถเข้าดูหรือดาวน์โหลดใบเสร็จได้'}</p>
                                    </div>
                                </div>
                            `,
                            showCloseButton: false,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            confirmButtonText: '<i class="ph-bold ph-check text-base sm:text-lg"></i> ' + (lang === 'en' ? 'I have read and agree to all terms' : 'ฉันได้อ่านและยอมรับเงื่อนไขทั้งหมด'),
                            confirmButtonColor: '#d97706',
                            customClass: {
                                confirmButton: 'rounded-xl font-prompt px-4 sm:px-8 py-2 font-bold text-sm sm:text-base w-[90%] sm:w-full max-w-md mx-auto shadow-lg shadow-amber-600/30 mt-2',
                                title: 'font-cinzel text-base sm:text-xl text-gray-800 mb-2',
                                popup: 'rounded-2xl p-4 w-[95%] sm:w-auto mx-auto'
                            }
                        });
                    }, 500);
                    
                } else {
                    showToast(data.message || 'Booking failed.', false);
                }
            })
            .catch(err => {
                showToast('Network error occurred. Please try again.', false);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = "<?= $text['btn_submit'] ?>";
                submitBtn.classList.remove('opacity-70');
            });
        });

        // Trigger form submission on button click since the button is outside the form
        submitBtn.addEventListener('click', () => {
            if (bookingForm.reportValidity()) {
                bookingForm.requestSubmit();
            } else {
                showToast(lang === 'en' ? "Please fill in all required fields and upload the payment slip." : "กรุณากรอกข้อมูลให้ครบถ้วนและอัปโหลดหลักฐานการโอนเงิน", false);
            }
        });

        // Download Receipt as PNG using html2canvas
        document.getElementById('btn-download-slip-png').addEventListener('click', () => {
            const voucher = document.getElementById('receipt-voucher');
            const bookingId = document.getElementById('receipt-booking-id').innerText;
            
            // Force desktop layout for high-quality download
            const originalWidth = voucher.style.width;
            const originalMaxWidth = voucher.style.maxWidth;
            voucher.style.width = '800px';
            voucher.style.maxWidth = '800px';
            
            const opt = {
                backgroundColor: '#ffffff',
                scale: 2,
                useCORS: true,
                allowTaint: true,
                logging: false,
                windowWidth: 800
            };
            
            html2canvas(voucher, opt).then(canvas => {
                const link = document.createElement('a');
                link.download = `yuncha_${bookingId}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                // Restore original styles
                voucher.style.width = originalWidth;
                voucher.style.maxWidth = originalMaxWidth;
            });
        });

        // Download Receipt as PDF using html2pdf
        document.getElementById('btn-download-slip-pdf').addEventListener('click', () => {
            const voucher = document.getElementById('receipt-voucher');
            const bookingId = document.getElementById('receipt-booking-id').innerText;
            
            const originalWidth = voucher.style.width;
            const originalMaxWidth = voucher.style.maxWidth;
            voucher.style.width = '800px';
            voucher.style.maxWidth = '800px';
            
            const opt = {
              margin:       10,
              filename:     `yuncha_${bookingId}.pdf`,
              image:        { type: 'jpeg', quality: 0.98 },
              html2canvas:  { scale: 2, useCORS: true, backgroundColor: '#ffffff', windowWidth: 800 },
              jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(voucher).save().then(() => {
                voucher.style.width = originalWidth;
                voucher.style.maxWidth = originalMaxWidth;
            });
        });
        
        // Custom Select Initialization
        function initCustomSelects() {
            document.querySelectorAll('select.custom-select-auto').forEach(select => {
                select.style.display = 'none';
                const wrapper = document.createElement('div');
                wrapper.className = 'relative w-full custom-select-wrapper';
                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(select);
                
                const trigger = document.createElement('div');
                trigger.className = 'custom-select-trigger hover-target';
                
                const selectedOpt = select.options[select.selectedIndex];
                const textSpan = document.createElement('span');
                textSpan.textContent = selectedOpt ? selectedOpt.text : '';
                textSpan.className = 'truncate select-none';
                
                const icon = document.createElement('div');
                icon.innerHTML = `<svg class="w-4 h-4 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>`;
                
                trigger.appendChild(textSpan);
                trigger.appendChild(icon);
                wrapper.appendChild(trigger);
                
                const optionsList = document.createElement('div');
                optionsList.className = 'custom-select-options';
                
                Array.from(select.options).forEach((opt) => {
                    if (opt.disabled) return;
                    
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'custom-option';
                    optionDiv.textContent = opt.text;
                    
                    optionDiv.addEventListener('click', (e) => {
                        e.stopPropagation();
                        select.value = opt.value;
                        textSpan.textContent = opt.text;
                        select.dispatchEvent(new Event('change'));
                        closeAllSelects();
                    });
                    optionsList.appendChild(optionDiv);
                });
                
                wrapper.appendChild(optionsList);
                
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = wrapper.classList.contains('open');
                    closeAllSelects();
                    if (!isOpen) {
                        wrapper.classList.add('open');
                        icon.querySelector('svg').classList.add('rotate-180', 'text-amber-500');
                        icon.querySelector('svg').classList.remove('text-gray-500');
                    }
                });
            });
            
            function closeAllSelects() {
                document.querySelectorAll('.custom-select-wrapper').forEach(wrapper => {
                    wrapper.classList.remove('open');
                    const iconSvg = wrapper.querySelector('svg');
                    if(iconSvg) {
                        iconSvg.classList.remove('rotate-180', 'text-amber-500');
                        iconSvg.classList.add('text-gray-500');
                    }
                });
            }
            
            document.addEventListener('click', closeAllSelects);
        }
        
        initCustomSelects();
    </script>
    <script src="js/cursor.js?v=<?= time() ?>"></script>
    <script src="js/chatbot.js?v=<?= time() ?>"></script>
</body>
</html>

