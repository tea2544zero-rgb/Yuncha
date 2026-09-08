<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] :  'th';

if (!isset($_SESSION['customer_email']) || !isset($_SESSION['customer_phone'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['customer_email'];
$phone = $_SESSION['customer_phone'];
$customer_name = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] :  'Customer';
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] :  null;

// Removed legacy SQLite path
$site_settings = [];
if (true) {
    try {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdo = $conn;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) {
            $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    } catch (Exception $e) {}
}
$hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'Yuncha Valley';
$hotel_logo = !empty($site_settings['hotel_logo']) ? 'dashboard/uploads/' . $site_settings['hotel_logo'] : 'img/logo.png';
$hotel_address = isset($site_settings['address']) ? $site_settings['address'] :  '123 Tea Mountain Rd, Chiang Rai, TH';
$hotel_phone = isset($site_settings['phone']) ? $site_settings['phone'] :  '+66 80 342 9396';
$hotel_email = isset($site_settings['email']) ? $site_settings['email'] :  'contact@yunchavalley.com';
$receipt_note = isset($site_settings['receipt_note']) ? $site_settings['receipt_note'] :  "<li><strong>Check-in:</strong> 14:00 onwards. <strong>Check-out:</strong> before 12:00.</li>\n<li>A security deposit of <strong>500 THB</strong> is required upon check-in.</li>\n<li>Cancellation Policy: Free cancellation up to 7 days before check-in.</li>\n<li>This is a computer-generated document. No signature is required.</li>";

$t = [
    'th' => [
        'nav_home' => 'หน้าแรก', 'nav_logout' => 'ออกจากระบบ',
        'title' => 'การจองของฉัน',
        'subtitle' => 'ยินดีต้อนรับกลับ,',
        'lbl_id' => 'รหัสการจอง:',
        'lbl_checkin' => 'เช็คอิน:',
        'lbl_checkout' => 'เช็คเอาท์:',
        'lbl_status' => 'สถานะ:',
        'btn_receipt' => 'ดูใบเสร็จ',
        'no_bookings' => 'ไม่พบข้อมูลการจอง',
        'status_pending' => 'รอตรวจสอบ',
        'status_confirmed' => 'ยืนยันแล้ว',
        'status_cancelled' => 'ยกเลิก',
        'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. สงวนลิขสิทธิ์.'
    ],
    'en' => [
        'nav_home' => 'Home', 'nav_logout' => 'Sign Out',
        'title' => 'My Bookings',
        'subtitle' => 'Welcome back,',
        'lbl_id' => 'Booking ID:',
        'lbl_checkin' => 'Check-in:',
        'lbl_checkout' => 'Check-out:',
        'lbl_status' => 'Status:',
        'btn_receipt' => 'View Receipt',
        'no_bookings' => 'No bookings found',
        'status_pending' => 'Pending',
        'status_confirmed' => 'Confirmed',
        'status_cancelled' => 'Cancelled',
        'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. ALL RIGHTS RESERVED.'
    ]
];

$text = $t[$lang];
$status_map = ['pending' => $text['status_pending'], 'confirmed' => $text['status_confirmed'], 'cancelled' => $text['status_cancelled']];

// Removed legacy SQLite path
$bookings = [];
$customer_data = null;

if (isset($pdo)) {
    try {
        if ($customer_id) {
            $stmt = $pdo->prepare("
                SELECT b.*, t.type_name as room_name_th, t.type_name as room_name_en, t.id as room_type_id,
                r.room_number, r.base_price, r.high_price, r.holiday_price, r.extra_bed_price, r.discount_percent,
                (SELECT image_path FROM room_images WHERE room_id = r.id ORDER BY is_primary DESC, id ASC LIMIT 1) as room_image
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                LEFT JOIN rooms r ON b.room_id = r.id
                LEFT JOIN room_types t ON r.room_type_id = t.id
                WHERE c.id = ?
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$customer_id]);
            
            $stmt_cust = $pdo->prepare("SELECT first_name, last_name, phone, email FROM customers WHERE id = ? LIMIT 1");
            $stmt_cust->execute([$customer_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT b.*, t.type_name as room_name_th, t.type_name as room_name_en, t.id as room_type_id,
                r.room_number, r.base_price, r.high_price, r.holiday_price, r.extra_bed_price, r.discount_percent,
                (SELECT image_path FROM room_images WHERE room_id = r.id ORDER BY is_primary DESC, id ASC LIMIT 1) as room_image
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                LEFT JOIN rooms r ON b.room_id = r.id
                LEFT JOIN room_types t ON r.room_type_id = t.id
                WHERE c.email = ? AND c.phone = ?
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$email, $phone]);
            
            $stmt_cust = $pdo->prepare("SELECT first_name, last_name, phone, email FROM customers WHERE email = ? AND phone = ? LIMIT 1");
            $stmt_cust->execute([$email, $phone]);
        }
        
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $customer_data = $stmt_cust->fetch(PDO::FETCH_ASSOC);
        
        // Fetch Room Types for Review Dropdown
        $rt_stmt = $pdo->query("SELECT id, type_name FROM room_types");
        $room_types_db = $rt_stmt ? $rt_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Activities List
$activities_list = [
    1 => ['th' => 'จิบชา (Tea Tasting)', 'en' => 'Tea Tasting'],
    2 => ['th' => 'นวดสปา (Spa)', 'en' => 'Spa Massage'],
    3 => ['th' => 'เดินเขา (Trekking)', 'en' => 'Mountain Trekking'],
    4 => ['th' => 'กิจกรรมอื่นๆ', 'en' => 'Other Activities']
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $text['title'] ?> | <?= htmlspecialchars(strtoupper($hotel_name)) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Noto+Serif+SC:wght@400;500;600&family=Noto+Serif+Thai:wght@300;400;500;600&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Scripts & CDNs -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chatbot.css?v=<?= time() ?>">

    <style>
        body {
            background-color: #020202;
            color: #ffffff;
            background-image: radial-gradient(circle at 50% 0%, rgba(217,119,6,0.12), transparent 60%);
        }
        .font-cinzel { font-family: 'Cinzel', serif; }
        
        .glass-nav {
            background: rgba(2, 2, 2, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.3s;
        }

        .booking-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 30px -15px rgba(0, 0, 0, 0.7);
        }
        .booking-card:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(245, 158, 11, 0.25);
            box-shadow: 0 20px 40px -10px rgba(245, 158, 11, 0.05);
            transform: translateY(-4px);
        }

        .btn-gradient-glow {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            box-shadow: 0 4px 20px rgba(217, 119, 6, 0.2), inset 0 0 5px rgba(255, 255, 255, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        .btn-gradient-glow:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(217, 119, 6, 0.4) !important;
            background: linear-gradient(135deg, #fbbf24, #ea580c) !important;
        }

        /* Modal scroll hide */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Print Layout for Receipt Modal */
        #receipt-document.print-layout {
            background-color: #ffffff !important;
            color: #111827 !important;
            border-color: transparent !important;
            box-shadow: none !important;
            padding: 10mm 15mm !important;
        }
        #receipt-document.print-layout * {
            color: #111827 !important;
            border-color: #e5e7eb !important;
        }
        #receipt-document.print-layout .text-amber-500,
        #receipt-document.print-layout #rec_total_paid {
            color: #b45309 !important;
        }
        #receipt-document.print-layout .text-gray-400,
        #receipt-document.print-layout .text-gray-500 {
            color: #4b5563 !important;
        }
        #receipt-document.print-layout .bg-white\/\[0\.02\],
        #receipt-document.print-layout .border-white\/5 {
            background-color: #f9fafb !important;
            border-color: #e5e7eb !important;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col">

    <!-- Custom Cursor -->
    <div class="cursor-dot"></div>
    <div class="cursor-ring"><span id="cursor-text"></span></div>

    <!-- Header -->
    <nav class="fixed top-0 left-0 w-full z-50 px-6 py-4 glass-nav">
        <div class="max-w-[1600px] mx-auto flex justify-between items-center w-full">
            <a href="index.php" class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l2.4 7.6L22 12l-7.6 2.4L12 22l-2.4-7.6L2 12l7.6-2.4L12 2z"/></svg>
                <div class="flex flex-col items-center">
                    <span class="font-cinzel text-xl font-bold text-white block leading-none">Yuncha Valley</span>
                    <span class="font-prompt text-[8px] text-gray-400 uppercase tracking-[0.4em] block mt-1">RESORT</span>
                </div>
            </a>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <a href="?lang=th" class="px-4 py-1.5 rounded-full text-[10px] font-bold tracking-widest transition-all duration-300 <?= $lang == 'th' ? 'bg-[#d97706] text-black shadow-[0_4px_15px_rgba(217,119,6,0.4)]' : 'text-white/50 hover:text-white' ?>">TH</a>
                    <a href="?lang=en" class="px-4 py-1.5 rounded-full text-[10px] font-bold tracking-widest transition-all duration-300 <?= $lang == 'en' ? 'bg-[#d97706] text-black shadow-[0_4px_15px_rgba(217,119,6,0.4)]' : 'text-white/50 hover:text-white' ?>">EN</a>
                </div>
                <span class="opacity-50 text-gray-700 hidden md:inline">|</span> 
                <a href="api/logout-customer.php" class="ml-4 border border-rose-500/50 text-rose-400 hover:bg-rose-500 hover:text-white px-4 py-2 rounded-full uppercase transition-colors"><?= $text['nav_logout'] ?></a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-grow max-w-4xl w-full mx-auto p-6 mt-32">
        <div class="mb-10 flex flex-col md:flex-row items-start md:items-end justify-between gap-6 border-b border-white/10 pb-6">
            <div>
                <h1 class="font-cinzel text-3xl font-bold text-amber-500 tracking-widest uppercase mb-2"><?= $text['title'] ?></h1>
                <p class="text-gray-400 tracking-wider font-prompt"><?= $text['subtitle'] ?> <span class="text-white font-semibold"><?= htmlspecialchars((isset($customer_data['first_name']) ? $customer_data['first_name'] :  '') . ' ' . (isset($customer_data['last_name']) ? $customer_data['last_name'] :  '')) ?></span></p>
            </div>
            <?php if($customer_data): ?>
            <div class="text-sm font-prompt text-gray-300 space-y-2 text-left md:text-right bg-white/[0.02] backdrop-blur-md p-5 rounded-2xl border border-white/5 shadow-[0_10px_30px_rgba(0,0,0,0.5)] min-w-[280px]">
                <p class="flex items-center justify-between md:justify-end gap-3"><span class="text-amber-500/80 font-bold uppercase tracking-widest text-[9px]"><?= $lang === 'en' ? 'Name' : 'ชื่อ-นามสกุล' ?></span> <span class="text-white font-semibold"><?= htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']) ?></span></p>
                <p class="flex items-center justify-between md:justify-end gap-3"><span class="text-amber-500/80 font-bold uppercase tracking-widest text-[9px]"><?= $lang === 'en' ? 'Phone' : 'เบอร์โทร' ?></span> <span class="text-white font-mono font-medium"><?= htmlspecialchars($customer_data['phone']) ?></span></p>
                <p class="flex items-center justify-between md:justify-end gap-3"><span class="text-amber-500/80 font-bold uppercase tracking-widest text-[9px]"><?= $lang === 'en' ? 'Email' : 'อีเมล' ?></span> <span class="text-white font-mono font-medium"><?= htmlspecialchars($customer_data['email']) ?></span></p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="bg-rose-500/10 border border-rose-500/20 text-rose-500 p-4 rounded-2xl mb-6 text-sm font-prompt text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="text-center py-20 bg-white/[0.02] rounded-3xl border border-white/5">
                <p class="text-gray-400 font-prompt"><?= $text['no_bookings'] ?></p>
                <a href="booking.php" class="inline-block mt-4 text-amber-500 border-b border-amber-500 hover:text-amber-400 font-prompt transition-colors">Book a Room</a>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($bookings as $b): 
                    $room_name = $lang === 'en' ? $b['room_name_en'] : $b['room_name_th'];
                    $status_class = '';
                    if ($b['status'] == 'pending') $status_class = 'text-amber-500 bg-amber-500/10 border-amber-500/20';
                    elseif ($b['status'] == 'confirmed') $status_class = 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
                    else $status_class = 'text-rose-500 bg-rose-500/10 border-rose-500/20';

                    // --- PRE-CALCULATE DETAILED PRICING FOR MODAL ---
                    $weekday_count = 0;
                    $weekend_count = 0;
                    $holiday_count = 0;

                    $inDate = new DateTime($b['check_in']);
                    $outDate = new DateTime($b['check_out']);
                    $nights = $inDate->diff($outDate)->days;
                    $currentDate = clone $inDate;

                    $holiday_dates_arr = array_map('trim', explode(',', isset($site_settings['holiday_dates']) ? $site_settings['holiday_dates'] : ''));

                    $base_price = (float)(isset($b['base_price']) ? $b['base_price'] : 0);
                    $high_price = (float)(isset($b['high_price']) ? $b['high_price'] : $base_price);
                    $holiday_price = (float)(isset($b['holiday_price']) ? $b['holiday_price'] : $base_price);

                    $extra_beds = (int)(isset($b['extra_bed']) ? $b['extra_bed'] : 0);
                    $extra_bed_price = (float)(isset($b['extra_bed_price']) ? $b['extra_bed_price'] : 0);

                    $discount_percent = (float)(isset($b['discount_percent']) ? $b['discount_percent'] : 0);

                    for ($i = 0; $i < $nights; $i++) {
                        $dateStr = $currentDate->format('Y-m-d');
                        $dayOfWeek = $currentDate->format('N');
                        
                        if (in_array($dateStr, $holiday_dates_arr) && $holiday_price > 0) {
                            $holiday_count++;
                        } elseif ($dayOfWeek == 5 || $dayOfWeek == 6) {
                            $weekend_count++;
                        } else {
                            $weekday_count++;
                        }
                        $currentDate->modify('+1 day');
                    }

                    $total_calculated = ($weekday_count * $base_price) + ($weekend_count * $high_price) + ($holiday_count * $holiday_price);
                    $discount_amount = $total_calculated * ($discount_percent / 100);
                    $extra_bed_total = $nights * $extra_beds * $extra_bed_price;
                    $total_expected = $total_calculated - $discount_amount + $extra_bed_total;
                    
                    $adjustment = (float)$b['total_price'] - $total_expected;

                    $total_paid = $b['total_price'];
                    $subtotal = $total_paid / 1.07;
                    $vat = $total_paid - $subtotal;

                    // Fix room image path
                    $room_img = !empty($b['room_image']) ? 'dashboard/' . ltrim($b['room_image'], '/') : 'dashboard/uploads/rooms/m8.png';
                ?>
                <div class="booking-card rounded-3xl p-6 md:p-8 flex flex-col lg:flex-row justify-between gap-8 border border-white/5 relative overflow-hidden">
                    
                    <!-- Image Area -->
                    <div class="w-full lg:w-64 h-48 lg:h-auto flex-shrink-0 rounded-2xl overflow-hidden relative border border-white/5 group">
                        <img src="<?= htmlspecialchars($room_img) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" onerror="this.src='dashboard/uploads/rooms/m8.png'">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/20 to-transparent"></div>
                        <span class="absolute bottom-4 left-4 text-white text-sm font-bold tracking-widest uppercase font-cinzel text-shadow"><?= $room_name ?></span>
                    </div>
                    
                    <div class="flex-grow w-full flex flex-col justify-center">
                        <div class="flex justify-between items-start mb-4 border-b border-white/5 pb-4">
                            <div>
                                <span class="text-[9px] text-amber-500 uppercase tracking-widest block mb-1"><?= $text['lbl_id'] ?></span>
                                <span class="font-mono text-xl md:text-2xl font-bold text-white"><?= isset($b['booking_ref']) ? $b['booking_ref'] :  $b['id'] ?></span>
                            </div>
                            <span class="text-[9px] border px-4 py-1.5 rounded-full uppercase tracking-widest font-bold <?= $status_class ?> shadow-lg">
                                <?= isset($status_map[$b['status']]) ? $status_map[$b['status']] :  $b['status'] ?>
                            </span>
                        </div>
                        
                        <div class="mb-5 text-sm text-gray-400 font-prompt leading-relaxed">
                            <?php if(!empty($b['description'])): ?>
                            <p class="text-xs md:text-sm line-clamp-2 md:line-clamp-3 text-gray-400/80"><?= htmlspecialchars($b['description']) ?></p>
                            <?php else: ?>
                            <p class="text-xs italic text-gray-500">Luxurious stay at Yuncha Valley Resort.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4 text-sm font-prompt bg-white/[0.01] p-4 md:p-5 rounded-2xl border border-white/5">
                            <div>
                                <span class="text-[9px] text-gray-500 uppercase tracking-widest block mb-1"><?= $text['lbl_checkin'] ?></span>
                                <span class="text-amber-500/90 font-semibold text-xs md:text-sm"><?= (new DateTime($b['check_in']))->format('d M Y') ?></span>
                            </div>
                            <div>
                                <span class="text-[9px] text-gray-500 uppercase tracking-widest block mb-1"><?= $text['lbl_checkout'] ?></span>
                                <span class="text-amber-500/90 font-semibold text-xs md:text-sm"><?= (new DateTime($b['check_out']))->format('d M Y') ?></span>
                            </div>
                            <div class="col-span-2 md:col-span-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[9px] text-gray-500 uppercase tracking-widest block mb-1"><?= $lang === 'en' ? 'Guests' : 'จำนวนผู้เข้าพัก' ?></span>
                                    <span class="text-white font-semibold text-xs md:text-sm"><?= (int)$b['adults'] ?> <?= $lang === 'en' ? 'Persons' : 'ท่าน' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full lg:w-56 flex flex-col gap-3 justify-center border-t lg:border-t-0 lg:border-l border-white/5 pt-6 lg:pt-0 lg:pl-8 mt-4 lg:mt-0 flex-shrink-0">
                        <div class="text-center mb-2 lg:mb-4">
                            <span class="text-[9px] text-gray-500 uppercase tracking-widest block mb-1"><?= $lang === 'en' ? 'Total Paid' : 'ยอดชำระสุทธิ' ?></span>
                            <span class="font-mono text-2xl font-bold text-amber-500">฿<?= number_format($total_paid, 2) ?></span>
                        </div>

                        <!-- Details & Receipt Trigger -->
                        <button type="button"
                            onclick="openReceiptModal(this)"
                            class="w-full text-center py-3 px-4 rounded-xl font-bold btn-gradient-glow uppercase tracking-widest text-xs shadow-lg flex items-center justify-center gap-2 transition-all cursor-pointer"
                            data-booking-ref="<?= htmlspecialchars(isset($b['booking_ref']) ? $b['booking_ref'] : $b['id']) ?>"
                            data-guest-name="<?= htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']) ?>"
                            data-guest-phone="<?= htmlspecialchars($customer_data['phone']) ?>"
                            data-guest-email="<?= htmlspecialchars($customer_data['email']) ?>"
                            data-check-in="<?= (new DateTime($b['check_in']))->format('d M Y') ?>"
                            data-check-out="<?= (new DateTime($b['check_out']))->format('d M Y') ?>"
                            data-room-name="<?= htmlspecialchars($room_name) ?>"
                            data-room-number="<?= htmlspecialchars(isset($b['room_number']) ? $b['room_number'] : 'N/A') ?>"
                            data-guests="<?= (int)$b['adults'] ?> <?= $lang === 'en' ? 'Persons' : 'ท่าน' ?>"
                            data-nights="<?= $nights ?>"
                            data-weekday-count="<?= $weekday_count ?>"
                            data-weekday-price="<?= $base_price ?>"
                            data-weekend-count="<?= $weekend_count ?>"
                            data-weekend-price="<?= $high_price ?>"
                            data-holiday-count="<?= $holiday_count ?>"
                            data-holiday-price="<?= $holiday_price ?>"
                            data-extra-beds="<?= $extra_beds ?>"
                            data-extra-bed-price="<?= $extra_bed_price ?>"
                            data-discount-percent="<?= $discount_percent ?>"
                            data-discount-amount="<?= $discount_amount ?>"
                            data-adjustment="<?= $adjustment ?>"
                            data-subtotal="<?= number_format($subtotal, 2, '.', '') ?>"
                            data-vat="<?= number_format($vat, 2, '.', '') ?>"
                            data-total-paid="<?= number_format($total_paid, 2, '.', '') ?>"
                            data-status="<?= $b['status'] ?>"
                            data-created-at="<?= date('d M Y, H:i', strtotime($b['created_at'])) ?>"
                        >
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span class="whitespace-nowrap"><?= $text['btn_receipt'] ?></span>
                        </button>

                        <?php if ($b['status'] == 'checked_out' || $b['status'] == 'confirmed'): ?>
                        <button type="button" onclick="openReviewModal(<?= $b['id'] ?>)" class="w-full text-center py-3 px-4 rounded-xl font-bold bg-transparent hover:bg-amber-500/10 text-amber-500 border border-amber-500/50 uppercase tracking-widest text-xs transition flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                            <span class="whitespace-nowrap"><?= $lang === 'en' ? 'Review' : 'รีวิวการเข้าพัก' ?></span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="js/cursor.js?v=<?= time() ?>"></script>
    <?php include 'components/footer.php'; ?>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 backdrop-blur-md transition-opacity duration-300 opacity-0">
        <div id="receiptModalContent" class="bg-[#0c0c0c] border border-white/10 rounded-3xl w-full max-w-4xl max-h-[90vh] overflow-y-auto scrollbar-hide relative shadow-[0_25px_60px_-15px_rgba(0,0,0,0.9)] transform scale-95 transition-all duration-300">
            <!-- Modal Toolbar -->
            <div class="sticky top-0 bg-[#0c0c0c]/90 backdrop-blur-md px-6 py-4 border-b border-white/5 flex justify-between items-center z-50 font-prompt">
                <h3 class="font-cinzel text-base text-amber-500 font-bold tracking-widest uppercase flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <?= $lang === 'en' ? 'Receipt Details' : 'รายละเอียดใบเสร็จ' ?>
                </h3>
                <div class="flex items-center gap-2">
                    <button onclick="downloadReceiptPNG()" class="px-4 py-2 bg-amber-600/20 hover:bg-amber-600/35 border border-amber-600/40 text-amber-400 rounded-xl text-xs font-bold uppercase tracking-widest transition flex items-center gap-2 cursor-pointer">
                        🖼️ PNG
                    </button>
                    <button onclick="closeReceiptModal()" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white transition cursor-pointer">
                        ✕
                    </button>
                </div>
            </div>

            <div class="p-6 md:p-10">
                <!-- Receipt Printable Sheet -->
                <div id="receipt-document" class="bg-white border border-gray-200 shadow-xl rounded-lg p-6 md:p-8 text-gray-800 relative transition-all duration-300">
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b-2 border-gray-100 pb-6 mb-6 gap-6">
                        <div class="flex items-center gap-4">
                            <img id="rec_hotel_logo" src="<?= $hotel_logo ?>" alt="Hotel Logo" class="w-16 h-16 object-contain">
                            <div>
                                <h2 class="font-cinzel text-xl md:text-2xl font-bold text-gray-900 tracking-wider uppercase leading-tight" id="rec_hotel_name"><?= htmlspecialchars($hotel_name) ?></h2>
                                <p class="font-prompt text-xs text-gray-500 mt-1"><?= htmlspecialchars($hotel_address) ?></p>
                                <p class="font-prompt text-[10px] text-gray-400 mt-0.5">
                                    Tel: <?= htmlspecialchars($hotel_phone) ?> | Email: <?= htmlspecialchars($hotel_email) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col items-start md:items-end gap-2 text-left md:text-right">
                            <h1 class="font-cinzel text-2xl md:text-3xl font-bold text-amber-600 tracking-widest uppercase">RECEIPT</h1>
                            <span class="text-xs text-gray-500 font-mono mt-1">Ref ID: <span class="text-gray-900 font-bold" id="rec_booking_ref"></span></span>
                            <span class="text-[10px] text-gray-400 font-mono">Date: <span id="rec_created_at"></span></span>
                        </div>
                    </div>

                    <!-- Guest & Booking Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-b-2 border-gray-100 pb-6">
                        <div>
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 border-b border-gray-100 pb-1 font-cinzel">Guest Info</h3>
                            <div class="space-y-1.5 text-xs text-gray-700 font-prompt">
                                <div class="flex justify-between"><span class="text-gray-500">Name:</span> <span class="font-semibold" id="rec_guest_name"></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Phone:</span> <span class="font-mono" id="rec_guest_phone"></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Email:</span> <span class="font-mono" id="rec_guest_email"></span></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 border-b border-gray-100 pb-1 font-cinzel">Reservation Details</h3>
                            <div class="space-y-1.5 text-xs text-gray-700 font-prompt">
                                <div class="flex justify-between"><span class="text-gray-500">Check-in:</span> <span class="font-semibold" id="rec_check_in"></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Check-out:</span> <span class="font-semibold" id="rec_check_out"></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Room:</span> <span class="font-semibold" id="rec_room_name"></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Room Code:</span> <span class="font-semibold" id="rec_room_number"></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Guests:</span> <span class="font-semibold" id="rec_guests"></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Nights:</span> <span class="font-semibold"><span id="rec_nights"></span> Nights</span></div>
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
                            <tbody id="rec_items_body" class="text-gray-700 divide-y divide-gray-100">
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
                                    <span class="font-mono text-gray-800" id="rec_subtotal"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">VAT (7%) Included</span>
                                    <span class="font-mono text-gray-800" id="rec_vat"></span>
                                </div>
                                <div id="rec_discount_row" class="flex justify-between text-rose-500 hidden">
                                    <span class="text-gray-500">Discount (<span id="rec_discount_percent"></span>%)</span>
                                    <span class="font-mono" id="rec_discount_amount"></span>
                                </div>
                                <div id="rec_adjustment_row" class="flex justify-between text-gray-500 hidden">
                                    <span class="text-gray-500">Adjustment</span>
                                    <span class="font-mono text-gray-800" id="rec_adjustment"></span>
                                </div>
                                <div class="flex justify-between items-center border-t-2 border-gray-200 pt-3 mt-1">
                                    <span class="font-bold text-sm font-cinzel text-gray-800 tracking-wider">Total Amount</span>
                                    <span class="font-mono text-2xl font-bold text-emerald-600" id="rec_total_paid"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="mt-8 border-t-2 border-gray-100 pt-6 text-[9px] text-gray-500">
                        <h4 class="font-bold font-cinzel uppercase tracking-widest text-gray-400 mb-2">Terms & Conditions</h4>
                        <ul class="list-disc pl-4 space-y-1 font-prompt" id="rec_terms">
                            <?= $receipt_note ?>
                        </ul>
                    </div>

                    <!-- Footer -->
                    <div class="text-center mt-8 border-t-2 border-gray-100 pt-4">
                        <p class="font-cinzel text-xs text-gray-400 italic mb-1" id="rec_footer_msg">"<?= htmlspecialchars(isset($site_settings['msg_receipt_footer']) ? $site_settings['msg_receipt_footer'] : 'Thank you for staying with ' . $hotel_name) ?>"</p>
                        <p class="text-[8px] text-gray-400 mt-1 tracking-widest uppercase font-prompt" id="rec_tax_info"><?= htmlspecialchars(strtoupper($hotel_name)) ?> CO., LTD. | TAX ID: <?= htmlspecialchars(isset($site_settings['tax_id']) ? $site_settings['tax_id'] : '0123456789012') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 backdrop-blur-md transition-opacity duration-300 opacity-0">
        <div id="reviewModalContent" class="bg-[#0c0c0c] border border-white/10 rounded-3xl w-full max-w-md max-h-[95vh] overflow-y-auto scrollbar-hide relative shadow-[0_20px_50px_rgba(0,0,0,0.8)] transform scale-95 transition-all duration-300 font-prompt">
            <div class="p-6 md:p-8 relative z-10">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="font-cinzel text-2xl text-amber-500 font-bold tracking-wider"><?= $lang === 'en' ? 'Share Your Experience' : 'แบ่งปันประสบการณ์' ?></h2>
                        <p class="text-xs text-gray-400 mt-1"><?= $lang === 'en' ? 'We would love to hear about your stay.' : 'เราอยากรับฟังความประทับใจของคุณ' ?></p>
                    </div>
                    <button type="button" onclick="closeReviewModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white transition-colors cursor-pointer">✕</button>
                </div>
                
                <form id="reviewForm" onsubmit="submitReview(event)" class="space-y-5">
                    <input type="hidden" name="booking_id" id="review_booking_id">
                    <input type="hidden" name="review_id" id="review_id" value="0">
                    <input type="hidden" name="action" value="save_review">
                    <input type="hidden" name="review_type" id="review_type" value="room">
                    <input type="hidden" name="rating" id="review_rating" value="5">
                    
                    <!-- Review Type Toggle -->
                    <div class="flex bg-white/5 p-1 rounded-2xl relative mb-4 border border-white/5">
                        <div id="type_slider" class="absolute top-1 bottom-1 left-1 w-[calc(50%-4px)] bg-amber-600 rounded-xl shadow-sm transition-transform duration-300"></div>
                        <button type="button" onclick="setReviewType('room')" id="btn_type_room" class="relative z-10 flex-1 py-2 text-xs font-bold rounded-xl text-white transition-colors uppercase tracking-wider cursor-pointer">
                            <?= $lang === 'en' ? 'Room Stay' : 'ห้องพัก' ?>
                        </button>
                        <button type="button" onclick="setReviewType('activity')" id="btn_type_activity" class="relative z-10 flex-1 py-2 text-xs font-bold rounded-xl text-gray-400 hover:text-white transition-colors uppercase tracking-wider cursor-pointer">
                            <?= $lang === 'en' ? 'Activities' : 'กิจกรรม' ?>
                        </button>
                    </div>
                    
                    <!-- Selection Dropdown (Only for Room) -->
                    <div id="target_dropdown_container">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 font-cinzel"><?= $lang === 'en' ? 'Select Room' : 'เลือกห้องพักที่ต้องการรีวิว' ?></label>
                        <div class="relative w-full" id="custom_select_wrapper">
                            <input type="hidden" name="target_id" id="target_id" value="">
                            <button type="button" id="custom_select_btn" class="w-full bg-[#141414] border border-white/10 text-white p-3.5 px-4 rounded-2xl focus:border-amber-500 hover:border-white/20 outline-none transition-all text-xs font-semibold cursor-pointer flex justify-between items-center">
                                <span id="custom_select_text" class="truncate"><?= $lang === 'en' ? 'Select a room' : 'เลือกห้องพัก' ?></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" id="custom_select_arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <!-- Custom Dropdown Menu -->
                            <div id="custom_select_menu" class="absolute z-50 w-full mt-2 bg-[#121212] border border-white/10 rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.5)] overflow-hidden opacity-0 pointer-events-none transform -translate-y-2 transition-all duration-200">
                                <ul id="custom_select_list" class="max-h-56 overflow-y-auto py-2 scrollbar-hide">
                                    <!-- Populated by JS -->
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Star Rating -->
                    <div class="flex flex-col items-center py-1">
                        <div class="flex justify-center gap-2 mb-2 w-full text-gray-600" id="star_container">
                            <!-- JS will inject stars here -->
                        </div>
                        <p class="text-amber-500 text-xs font-bold uppercase tracking-widest font-cinzel mt-1" id="rating_text">EXCELLENT</p>
                    </div>
                    
                    <!-- Comment -->
                    <div>
                        <textarea name="comment" id="review_comment" rows="3" class="w-full bg-[#141414] border border-white/10 text-white p-4 rounded-2xl focus:border-amber-500 focus:bg-[#111] outline-none placeholder-gray-500 transition-all resize-none text-sm" placeholder="<?= $lang === 'en' ? 'Tell us what you loved...' : 'เล่าความประทับใจของคุณให้เราฟัง...' ?>"></textarea>
                    </div>
                    
                    <!-- Photo Upload Area (Up to 5) -->
                    <div>
                        <div class="relative group cursor-pointer" onclick="document.getElementById('review_images').click()">
                            <div id="upload_placeholder" class="border-2 border-dashed border-white/10 hover:border-amber-500/30 p-6 rounded-2xl flex flex-col items-center justify-center bg-[#141414] transition-colors gap-2.5">
                                <svg class="w-8 h-8 text-gray-500 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-xs text-gray-400 group-hover:text-amber-500 transition-colors font-medium"><?= $lang === 'en' ? 'Upload up to 5 Photos (Optional)' : 'อัปโหลดรูปภาพสูงสุด 5 รูป (ไม่บังคับ)' ?></span>
                            </div>
                        </div>
                        <div id="review_images_preview_container" class="hidden flex-wrap gap-2 mt-3">
                            <!-- JS will inject previews here -->
                        </div>
                        <p id="review_images_error" class="hidden text-rose-500 text-xs mt-2"></p>
                        <input type="file" name="review_images[]" id="review_images" accept="image/*" multiple class="hidden" onchange="previewImages(this)">
                    </div>
                    
                    <button id="submitReviewBtn" type="submit" class="w-full py-3.5 rounded-xl font-bold btn-gradient-glow text-white uppercase tracking-widest text-xs mt-2 shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all flex justify-center items-center cursor-pointer">
                        <span><?= $lang === 'en' ? 'Submit Review' : 'บันทึกรีวิว' ?></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    const texts_en = ['TERRIBLE', 'POOR', 'AVERAGE', 'VERY GOOD', 'EXCELLENT'];
    const texts_th = ['แย่มาก', 'พอใช้', 'ปานกลาง', 'ดีมาก', 'ยอดเยี่ยม'];
    const texts = <?= $lang === 'en' ? 'texts_en' : 'texts_th' ?>;
    
    const svgStar = `<svg viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 cursor-pointer transition-transform duration-200 hover:scale-110"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" /></svg>`;
    
    function initStars() {
        const container = document.getElementById('star_container');
        container.innerHTML = '';
        for(let i=1; i<=5; i++) {
            const div = document.createElement('div');
            div.innerHTML = svgStar;
            
            const svg = div.firstChild;
            svg.dataset.value = i;
            svg.addEventListener('click', () => setRating(i));
            svg.addEventListener('mouseenter', () => hoverRating(i));
            svg.addEventListener('mouseleave', () => resetHover());
            container.appendChild(svg);
        }
        setRating(5);
    }

    let currentRating = 5;
    
    function updateStarsUI(val) {
        const stars = document.getElementById('star_container').children;
        for(let i=0; i<5; i++) {
            if (i < val) {
                stars[i].classList.add('text-amber-400');
                stars[i].classList.remove('text-gray-200');
            } else {
                stars[i].classList.remove('text-amber-400');
                stars[i].classList.add('text-gray-200');
            }
        }
        document.getElementById('rating_text').innerText = texts[val - 1];
    }
    
    function setRating(val) {
        currentRating = val;
        document.getElementById('review_rating').value = val;
        updateStarsUI(val);
        
        // Add a little pop animation
        const stars = document.getElementById('star_container').children;
        if(stars[val-1]) {
            stars[val-1].style.transform = 'scale(1.2)';
            setTimeout(() => stars[val-1].style.transform = '', 200);
        }
    }
    
    function hoverRating(val) {
        updateStarsUI(val);
    }
    
    function resetHover() {
        updateStarsUI(currentRating);
    }
    
    const roomOptions = [
        <?php foreach ($room_types_db as $rt): ?>
        { id: <?= $rt['id'] ?>, name: <?= json_encode($rt['type_name']) ?> },
        <?php endforeach; ?>
    ];
    
    const activityOptions = [
        <?php foreach ($activities_list as $id => $act): ?>
        { id: <?= $id ?>, name: <?= json_encode($act[$lang]) ?> },
        <?php endforeach; ?>
    ];
    
    const langRoomLabel = '<?= $lang === 'en' ? 'Select Room' : 'เลือกห้องพักที่ต้องการรีวิว' ?>';
    
    function toggleCustomSelect() {
        const menu = document.getElementById('custom_select_menu');
        const arrow = document.getElementById('custom_select_arrow');
        const isOpen = !menu.classList.contains('pointer-events-none');
        if (isOpen) {
            menu.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
            arrow.classList.remove('rotate-180');
        } else {
            menu.classList.remove('opacity-0', 'pointer-events-none', '-translate-y-2');
            arrow.classList.add('rotate-180');
        }
    }
    
    document.getElementById('custom_select_btn').addEventListener('click', toggleCustomSelect);
    
    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('custom_select_wrapper');
        const menu = document.getElementById('custom_select_menu');
        if (wrapper && !wrapper.contains(e.target)) {
            if (!menu.classList.contains('pointer-events-none')) {
                toggleCustomSelect();
            }
        }
    });
    
    function setReviewType(type) {
        document.getElementById('review_type').value = type;
        const slider = document.getElementById('type_slider');
        const btnRoom = document.getElementById('btn_type_room');
        const btnAct = document.getElementById('btn_type_activity');
        const dropdownContainer = document.getElementById('target_dropdown_container');
        
        if (type === 'room') {
            slider.style.transform = 'translateX(0)';
            btnRoom.classList.replace('text-gray-500', 'text-amber-600');
            btnAct.classList.replace('text-amber-600', 'text-gray-500');
            
            dropdownContainer.style.display = 'block';
            
            const list = document.getElementById('custom_select_list');
            list.innerHTML = '';
            roomOptions.forEach((opt, index) => {
                let viewText = '<?= $lang === 'en' ? "View" : "วิวทั่วไป" ?>';
                if (opt.name === 'Tea Valley Room') viewText = '<?= $lang === 'en' ? "Tea Plantation View" : "วิวไร่ชา" ?>';
                else if (opt.name === 'Tea Pavilion') viewText = '<?= $lang === 'en' ? "Mountain View" : "วิวภูเขา" ?>';
                else if (opt.name === 'The Peak Pavilion') viewText = '<?= $lang === 'en' ? "River View" : "วิวแม่น้ำ" ?>';

                const li = document.createElement('li');
                li.className = 'px-4 py-3 text-sm font-prompt font-semibold text-gray-300 hover:bg-white/10 hover:text-amber-400 cursor-pointer transition-colors';
                li.innerHTML = opt.name + ' <span class="text-xs text-gray-500 font-normal ml-1">(' + viewText + ')</span>';
                li.onclick = () => {
                    document.getElementById('target_id').value = opt.id;
                    document.getElementById('custom_select_text').innerText = opt.name + ' (' + viewText + ')';
                    toggleCustomSelect();
                };
                list.appendChild(li);
                
                // Select first by default if none selected
                if (index === 0 && !window.pendingTargetId) {
                    document.getElementById('target_id').value = opt.id;
                    document.getElementById('custom_select_text').innerText = opt.name + ' (' + viewText + ')';
                }
            });
            
            // Restore selection if any
            if (window.pendingTargetId) {
                const selectedOpt = roomOptions.find(o => o.id == window.pendingTargetId);
                if (selectedOpt) {
                    let viewText = '<?= $lang === 'en' ? "View" : "วิวทั่วไป" ?>';
                    if (selectedOpt.name === 'Tea Valley Room') viewText = '<?= $lang === 'en' ? "Tea Plantation View" : "วิวไร่ชา" ?>';
                    else if (selectedOpt.name === 'Tea Pavilion') viewText = '<?= $lang === 'en' ? "Mountain View" : "วิวภูเขา" ?>';
                    else if (selectedOpt.name === 'The Peak Pavilion') viewText = '<?= $lang === 'en' ? "River View" : "วิวแม่น้ำ" ?>';
                    
                    document.getElementById('target_id').value = selectedOpt.id;
                    document.getElementById('custom_select_text').innerText = selectedOpt.name + ' (' + viewText + ')';
                }
                window.pendingTargetId = null;
            }
            
        } else {
            slider.style.transform = 'translateX(100%)';
            slider.style.left = '10px'; // gap adjustment
            btnAct.classList.replace('text-gray-500', 'text-amber-600');
            btnRoom.classList.replace('text-amber-600', 'text-gray-500');
            
            // Hide dropdown entirely for Activities
            dropdownContainer.style.display = 'none';
            document.getElementById('target_id').value = 0; 
        }
    }
    
    let selectedFiles = [];

    function previewImages(input) {
        const err = document.getElementById('review_images_error');
        err.classList.add('hidden');
        err.innerText = '';
        
        if (input.files) {
            if (input.files.length > 5) {
                err.innerText = '<?= $lang === 'en' ? 'You can only upload up to 5 photos.' : 'คุณสามารถอัปโหลดรูปภาพได้สูงสุด 5 รูปเท่านั้น' ?>';
                err.classList.remove('hidden');
                input.value = ''; // clear
                return;
            }
            
            const container = document.getElementById('review_images_preview_container');
            container.innerHTML = '';
            container.classList.remove('hidden');
            document.getElementById('upload_placeholder').classList.add('hidden');
            
            selectedFiles = Array.from(input.files);
            
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative w-20 h-20 rounded-xl overflow-hidden border border-gray-200 shadow-sm';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="object-cover w-full h-full">
                        <div class="absolute inset-0 bg-black/40 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer" onclick="removePreviewImage(${index})">
                            <i class="text-white text-xs">✕</i>
                        </div>
                    `;
                    container.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
            
            // Add a small button to add more if less than 5
            if(selectedFiles.length < 5) {
                const addMore = document.createElement('div');
                addMore.className = 'relative w-20 h-20 rounded-xl overflow-hidden border-2 border-dashed border-gray-300 hover:border-amber-500 cursor-pointer flex items-center justify-center bg-gray-50';
                addMore.innerHTML = '<span class="text-gray-400 text-2xl">+</span>';
                addMore.onclick = () => document.getElementById('review_images').click();
                container.appendChild(addMore);
            }
        }
    }
    
    function removePreviewImage(index) {
        // Since input type=file is immutable for specific files, we just clear and show placeholder
        // A more advanced way would be to use DataTransfer, but clearing is simpler for this scope
        document.getElementById('review_images').value = '';
        selectedFiles = [];
        document.getElementById('review_images_preview_container').innerHTML = '';
        document.getElementById('review_images_preview_container').classList.add('hidden');
        document.getElementById('upload_placeholder').classList.remove('hidden');
    }
    
    function openReviewModal(bookingId) {
        document.getElementById('review_booking_id').value = bookingId;
        document.getElementById('review_id').value = '0';
        document.getElementById('reviewForm').reset();
        
        setReviewType('room');
        initStars();
        document.getElementById('upload_placeholder').classList.remove('hidden');
        document.getElementById('review_images_preview_container').classList.add('hidden');
        document.getElementById('review_images_preview_container').innerHTML = '';
        document.getElementById('review_images').value = '';
        selectedFiles = [];
        
        const modal = document.getElementById('reviewModal');
        const content = document.getElementById('reviewModalContent');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
        
        fetch('api/submit_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'get_review', booking_id: bookingId })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success && data.data) {
                document.getElementById('review_id').value = data.data.id;
                window.pendingTargetId = data.data.target_id;
                setReviewType(data.data.review_type);
                setRating(data.data.rating);
                document.getElementById('review_comment').value = data.data.comment || '';
                
                if (data.data.image_path) {
                    document.getElementById('review_image_preview').src = data.data.image_path;
                    document.getElementById('upload_placeholder').classList.add('hidden');
                    document.getElementById('review_image_preview_container').classList.remove('hidden');
                }
            }
        });
    }
    
    function closeReviewModal() {
        const modal = document.getElementById('reviewModal');
        const content = document.getElementById('reviewModalContent');
        
        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
    
    function submitReview(e) {
        e.preventDefault();
        
        const btn = document.getElementById('submitReviewBtn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="flex items-center justify-center gap-2"><svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> PROCESSING...</span>';
        btn.disabled = true;
        
        const formData = new FormData(e.target);
        fetch('api/submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            if(data.success) {
                closeReviewModal();
                // Show a nice success alert (optional)
                setTimeout(() => {
                    alert('<?= $lang === 'en' ? 'Thank you! Your review has been submitted successfully.' : 'ขอบคุณครับ! บันทึกรีวิวของคุณเรียบร้อยแล้ว' ?>');
                }, 300);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('An error occurred. Please try again.');
        });
    }

    // --- DETAILED RECEIPT MODAL FUNCTIONS ---
    let currentBookingRef = '';
    
    function openReceiptModal(btn) {
        const data = btn.dataset;
        currentBookingRef = data.bookingRef;
        
        document.getElementById('rec_booking_ref').innerText = data.bookingRef;
        document.getElementById('rec_guest_name').innerText = data.guestName;
        document.getElementById('rec_guest_phone').innerText = data.guestPhone;
        document.getElementById('rec_guest_email').innerText = data.guestEmail;
        document.getElementById('rec_check_in').innerText = data.checkIn + ' (14:00)';
        document.getElementById('rec_check_out').innerText = data.checkOut + ' (12:00)';
        document.getElementById('rec_room_name').innerText = data.roomName;
        document.getElementById('rec_nights').innerText = data.nights;
        document.getElementById('rec_created_at').innerText = data.createdAt;
        document.getElementById('rec_room_number').innerText = data.roomNumber || 'N/A';
        document.getElementById('rec_guests').innerText = data.guests || '';

        const tbody = document.getElementById('rec_items_body');
        tbody.innerHTML = '';

        const weekdayCount = parseInt(data.weekdayCount) || 0;
        const weekdayPrice = parseFloat(data.weekdayPrice) || 0;
        const weekendCount = parseInt(data.weekendCount) || 0;
        const weekendPrice = parseFloat(data.weekendPrice) || 0;
        const holidayCount = parseInt(data.holidayCount) || 0;
        const holidayPrice = parseFloat(data.holidayPrice) || 0;
        const extraBeds = parseInt(data.extraBeds) || 0;
        const extraBedPrice = parseFloat(data.extraBedPrice) || 0;
        const nights = parseInt(data.nights) || 0;

        if (weekdayCount > 0) {
            tbody.innerHTML += `
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-2">
                        <span class="block font-semibold text-gray-900">${data.roomName} - Weekday</span>
                        <span class="text-[10px] text-gray-500">Sunday - Thursday</span>
                    </td>
                    <td class="py-3 px-2 text-center text-gray-700">${weekdayCount}</td>
                    <td class="py-3 px-2 text-right font-mono text-gray-700">${formatCurrency(weekdayPrice)}</td>
                    <td class="py-3 px-2 text-right font-mono text-gray-900">${formatCurrency(weekdayCount * weekdayPrice)}</td>
                </tr>
            `;
        }
        if (weekendCount > 0) {
            tbody.innerHTML += `
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-2">
                        <span class="block font-semibold text-gray-900">${data.roomName} - Weekend</span>
                        <span class="text-[10px] text-gray-500">Friday - Saturday</span>
                    </td>
                    <td class="py-3 px-2 text-center text-gray-700">${weekendCount}</td>
                    <td class="py-3 px-2 text-right font-mono text-gray-700">${formatCurrency(weekendPrice)}</td>
                    <td class="py-3 px-2 text-right font-mono text-gray-900">${formatCurrency(weekendCount * weekendPrice)}</td>
                </tr>
            `;
        }
        if (holidayCount > 0) {
            tbody.innerHTML += `
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-2">
                        <span class="block font-semibold text-gray-900">${data.roomName} - Holiday</span>
                        <span class="text-[10px] text-gray-500">Public Holidays</span>
                    </td>
                    <td class="py-3 px-2 text-center text-gray-700">${holidayCount}</td>
                    <td class="py-3 px-2 text-right font-mono text-gray-700">${formatCurrency(holidayPrice)}</td>
                    <td class="py-3 px-2 text-right font-mono text-gray-900">${formatCurrency(holidayCount * holidayPrice)}</td>
                </tr>
            `;
        }
        if (extraBeds > 0) {
            tbody.innerHTML += `
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-2">
                        <span class="block font-semibold text-gray-900">Extra Bed</span>
                        <span class="text-[10px] text-gray-500">${extraBeds} x ${nights} Nights</span>
                    </td>
                    <td class="py-3 px-2 text-center text-gray-700">${nights}</td>
                    <td class="py-3 px-2 text-right font-mono text-gray-700">${formatCurrency(extraBedPrice)}</td>
                    <td class="py-3 px-2 text-right font-mono text-gray-900">${formatCurrency(extraBeds * extraBedPrice * nights)}</td>
                </tr>
            `;
        }

        document.getElementById('rec_subtotal').innerText = formatCurrency(parseFloat(data.subtotal));
        document.getElementById('rec_vat').innerText = formatCurrency(parseFloat(data.vat));
        document.getElementById('rec_total_paid').innerText = '฿' + formatCurrency(parseFloat(data.totalPaid));

        const discountAmount = parseFloat(data.discountAmount) || 0;
        if (discountAmount > 0) {
            document.getElementById('rec_discount_row').classList.remove('hidden');
            document.getElementById('rec_discount_percent').innerText = data.discountPercent;
            document.getElementById('rec_discount_amount').innerText = '-' + formatCurrency(discountAmount);
        } else {
            document.getElementById('rec_discount_row').classList.add('hidden');
        }

        const adjustment = parseFloat(data.adjustment) || 0;
        if (Math.abs(adjustment) > 0.01) {
            document.getElementById('rec_adjustment_row').classList.remove('hidden');
            document.getElementById('rec_adjustment').innerText = (adjustment > 0 ? '+' : '') + formatCurrency(adjustment);
        } else {
            document.getElementById('rec_adjustment_row').classList.add('hidden');
        }

        const modal = document.getElementById('receiptModal');
        const content = document.getElementById('receiptModalContent');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }
    
    function closeReceiptModal() {
        const modal = document.getElementById('receiptModal');
        const content = document.getElementById('receiptModalContent');
        
        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function formatCurrency(num) {
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function downloadReceiptPNG() {
        const btn = document.getElementById('download-png-btn') || document.querySelector('button[onclick="downloadReceiptPNG()"]');
        const originalText = btn ? btn.innerHTML : '';
        if(btn) btn.innerHTML = '⏳ Processing...';
        
        setTimeout(() => {
            const originalDoc = document.getElementById('receipt-document');
            const clone = originalDoc.cloneNode(true);
            
            clone.style.width = '800px';
            clone.style.maxWidth = '800px';
            clone.style.minHeight = '1131px'; // A4 Ratio
            clone.classList.add('flex', 'flex-col');
            clone.lastElementChild.classList.add('mt-auto');
            
            // Append clone behind everything
            clone.style.position = 'absolute';
            clone.style.top = '0';
            clone.style.left = '0';
            clone.style.zIndex = '-9999';
            document.body.appendChild(clone);
            
            const opt = {
                backgroundColor: '#ffffff',
                scale: 2,
                useCORS: true,
                allowTaint: true,
                logging: false,
                scrollY: 0,
                scrollX: 0,
                windowWidth: 1024,
                width: 800
            };
            
            html2canvas(clone, opt).then(canvas => {
                const link = document.createElement('a');
                link.download = `yuncha_${currentBookingRef}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                document.body.removeChild(clone);
                if(btn) btn.innerHTML = originalText;
            }).catch(err => {
                console.error(err);
                document.body.removeChild(clone);
                if(btn) btn.innerHTML = originalText;
                alert("Error generating Image");
            });
        }, 100);
    }
    </script>

    <script src="js/chatbot.js?v=<?= time() ?>"></script>
</body>
</html>

