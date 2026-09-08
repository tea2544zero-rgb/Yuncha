<?php
require_once 'config/security.php';
if (!$is_account) { $has_access = false; }

require_once 'config/db.php';
require_once 'config/db.php';

// =========================================================================
// 🚀 ระบบ Auto-Fix DB (เพิ่มคอลัมน์เก็บรูปและเวลา)
// =========================================================================
$check_tx = $conn->query("PRAGMA table_info(transactions)");
$tx_cols = [];
while($c = $check_tx->fetch(PDO::FETCH_ASSOC)) { $tx_cols[] = $c['name']; }
if(!in_array('image_path', $tx_cols)) $conn->exec("ALTER TABLE transactions ADD COLUMN image_path TEXT NULL");
if(!in_array('transaction_time', $tx_cols)) $conn->exec("ALTER TABLE transactions ADD COLUMN transaction_time TEXT NULL DEFAULT '00:00:00'");

// โหลดตั้งค่า
// Removed legacy SQLite path
$site_settings = [];
if (true) {
    try {
        require_once __DIR__ . '/../dashboard/config/db.php';
        $pdoSettings = $conn;
        $pdoSettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt_settings = $pdoSettings->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) {
            $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    } catch (Exception $e) {}
}
$hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'Yuncha Valley Resort';

// 🌟 รับค่า View ว่ากำลังดู "รายวัน" หรือ "รายเดือน"
$view_mode = isset($_GET['view']) ? $_GET['view'] :  'daily';
$selected_month = isset($_GET['month']) ? $_GET['month'] :  date('Y-m');
$today = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] :  $today;

// =========================================================================
// ðŸš€ ระบบจัดการข้อมูล (Add / Edit / Delete / Duplicate) ทำงานจริง 100%
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $tx_id = (int)(isset($_POST['tx_id']) ? $_POST['tx_id'] :  0);
    $return_view = isset($_POST['return_view']) ? $_POST['return_view'] :  'daily';
    $return_month = isset($_POST['return_month']) ? $_POST['return_month'] :  date('Y-m');
    
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id=?");
        $stmt->execute([$tx_id]);
        header("Location: finance.php?view=$return_view&month=$return_month&success=delete"); 
        exit;
    } 
    else if ($action === 'add' || $action === 'edit' || $action === 'duplicate') {
        $type = $_POST['tx_type'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $amount = (float)$_POST['amount'];
        
        // จัดการ VAT
        $net_amount = isset($_POST['net_amount']) && $_POST['net_amount'] > 0 ? (float)$_POST['net_amount'] : $amount;
        $vat_amount = $net_amount - $amount; 
        if(!isset($_POST['include_vat'])) { 
            $vat_amount = 0; $net_amount = $amount; 
        }
        
        $cat = $_POST['category'];
        $method = $_POST['method'];
        $ref = $_POST['ref_id'];
        $note = $_POST['note'];
        
        $guest = isset($_POST['guest_info']) ? $_POST['guest_info'] :  '';
        if(!empty($guest)) { $note = "ผู้จอง/ห้อง: $guest" . (!empty($note) ? " | $note" : ""); }
        
        $emp_code = $_SESSION['emp_code'];
        
        // ðŸ“¸ ระบบอัปโหลดรูปภาพ
        $image_path = "";
        if(isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == 0) {
            $dir = "uploads/slips/";
            if(!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . rand(1000,9999) . '.' . $ext;
            if(move_uploaded_file($_FILES['slip_image']['tmp_name'], $dir . $filename)) {
                $image_path = $dir . $filename;
            }
        }
        
        // ดึงเดือนจากวันที่ทำรายการ เพื่อให้เด้งกลับไปถูกเดือน
        $redirect_month = date('Y-m', strtotime($date));

        if ($action === 'add' || $action === 'duplicate') {
            $sql = "INSERT INTO transactions (transaction_type, category, amount, vat_amount, net_amount, payment_method, reference_no, notes, transaction_date, transaction_time, created_by, image_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$type, $cat, $amount, $vat_amount, $net_amount, $method, $ref, $note, $date, $time, $emp_code, $image_path]);
            header("Location: finance.php?view=$return_view&month=$redirect_month&success=add"); 
            exit;
        } else {
            if ($image_path) {
                $sql = "UPDATE transactions SET transaction_type=?, category=?, amount=?, vat_amount=?, net_amount=?, payment_method=?, reference_no=?, notes=?, transaction_date=?, transaction_time=?, image_path=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$type, $cat, $amount, $vat_amount, $net_amount, $method, $ref, $note, $date, $time, $image_path, $tx_id]);
            } else {
                $sql = "UPDATE transactions SET transaction_type=?, category=?, amount=?, vat_amount=?, net_amount=?, payment_method=?, reference_no=?, notes=?, transaction_date=?, transaction_time=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$type, $cat, $amount, $vat_amount, $net_amount, $method, $ref, $note, $date, $time, $tx_id]);
            }
            header("Location: finance.php?view=$return_view&month=$redirect_month&success=edit"); 
            exit;
        }
    }
}

// ==========================================
// 📊 คำนวณสรุปยอด (รายวัน และ รายเดือน แยกกันชัดเจน)
// ==========================================

// 1. ยอดสรุปของ "วันนี้"
$sql_today = "SELECT 
    SUM(CASE WHEN transaction_type='income' THEN net_amount ELSE 0 END) as today_inc,
    SUM(CASE WHEN transaction_type='expense' THEN net_amount ELSE 0 END) as today_exp
    FROM transactions WHERE transaction_date='$selected_date'";
$res_today = $conn->query($sql_today);
$data_today = $res_today ? $res_today->fetch(PDO::FETCH_ASSOC) : [];
$today_income = isset($data_today['today_inc']) ? $data_today['today_inc'] :  0;
$today_expense = isset($data_today['today_exp']) ? $data_today['today_exp'] :  0;
$today_profit = $today_income - $today_expense;

// 2. ยอดสรุปของ "เดือนที่เลือก"
$current_year = date('Y', strtotime($selected_month));
$current_month_num = date('m', strtotime($selected_month));
$sql_month = "SELECT 
    SUM(CASE WHEN transaction_type='income' THEN net_amount ELSE 0 END) as month_inc,
    SUM(CASE WHEN transaction_type='expense' THEN net_amount ELSE 0 END) as month_exp
    FROM transactions WHERE strftime('%m', transaction_date)='$current_month_num' AND strftime('%Y', transaction_date)='$current_year'";
$res_month = $conn->query($sql_month);
$data_month = $res_month ? $res_month->fetch(PDO::FETCH_ASSOC) : [];
$month_income = isset($data_month['month_inc']) ? $data_month['month_inc'] :  0;
$month_expense = isset($data_month['month_exp']) ? $data_month['month_exp'] :  0;
$month_profit = $month_income - $month_expense;

// 3. กำหนดข้อมูลที่จะเอาไปแสดงในกล่อง KPI ตามโหมด
$display_income = ($view_mode === 'daily') ? $today_income : $month_income;
$display_expense = ($view_mode === 'daily') ? $today_expense : $month_expense;
$display_profit = ($view_mode === 'daily') ? $today_profit : $month_profit;

// 4. ดึงข้อมูลใส่ตาราง ตามโหมด
$transactions = [];
if($view_mode === 'daily') {
    $tx_sql = "SELECT * FROM transactions WHERE transaction_date='$selected_date' ORDER BY transaction_time DESC, id DESC";
} else {
    $tx_sql = "SELECT * FROM transactions WHERE strftime('%m', transaction_date)='$current_month_num' AND strftime('%Y', transaction_date)='$current_year' ORDER BY transaction_date DESC, transaction_time DESC, id DESC";
}

$tx_res = $conn->query($tx_sql);
if($tx_res) {
    $transactions = $tx_res->fetchAll(PDO::FETCH_ASSOC);
    
    // แปลง ID ห้องและลูกค้า เป็นชื่อจริงๆ
    $rooms_map = [];
    $res_rooms = $conn->query("SELECT id, room_number FROM rooms");
    if($res_rooms) {
        while($r = $res_rooms->fetch(PDO::FETCH_ASSOC)) {
            $rooms_map[$r['id']] = $r['room_number'];
        }
    }

    $customers_map = [];
    $res_cust = $conn->query("SELECT id, first_name, last_name, phone FROM customers");
    if($res_cust) {
        while($c = $res_cust->fetch(PDO::FETCH_ASSOC)) {
            $name = trim($c['first_name'] . ' ' . $c['last_name']);
            if(empty($name)) $name = $c['phone'];
            $customers_map[$c['id']] = $name;
        }
    }

    foreach($transactions as &$t) {
        $note = $t['notes'];
        if(preg_match('/ผู้จอง:\s*(\w+|\d+)/u', $note, $m_cust)) {
            $c_id = trim($m_cust[1]);
            if(isset($customers_map[$c_id])) {
                $note = str_replace("ผู้จอง: $c_id", "ลูกค้า: คุณ" . $customers_map[$c_id], $note);
            } else {
                $note = str_replace("ผู้จอง: $c_id", "ลูกค้า: คุณ" . $c_id, $note);
            }
        }
        if(preg_match('/จองห้องพัก ID:\s*(\d+)/u', $note, $m_room)) {
            $r_id = trim($m_room[1]);
            if(isset($rooms_map[$r_id])) {
                $note = str_replace("จองห้องพัก ID: $r_id", "(ห้อง " . $rooms_map[$r_id] . ")", $note);
            }
        }
        // ทำให้สะอาดขึ้น e.g. "ลูกค้า: คุณAnuwat (ห้อง A1)"
        $note = str_replace(" | ", " ", $note);
        $t['notes'] = $note;
    }
}

// --- Chart Data Queries ---
$chart_sql = "SELECT transaction_date, 
                     SUM(CASE WHEN transaction_type='income' THEN amount ELSE 0 END) as inc,
                     SUM(CASE WHEN transaction_type='expense' THEN amount ELSE 0 END) as exp
              FROM transactions 
              WHERE strftime('%m', transaction_date)='$current_month_num' 
              AND strftime('%Y', transaction_date)='$current_year'
              GROUP BY transaction_date ORDER BY transaction_date ASC";
$chart_res = $conn->query($chart_sql);
$chart_dates = [];
$chart_incomes = [];
$chart_expenses = [];
if($chart_res) {
    while($r = $chart_res->fetch(PDO::FETCH_ASSOC)) {
        $chart_dates[] = date('d', strtotime($r['transaction_date']));
        $chart_incomes[] = (float)$r['inc'];
        $chart_expenses[] = (float)$r['exp'];
    }
}

if($view_mode === 'daily') {
    $pie_sql = "SELECT category, SUM(amount) as total FROM transactions WHERE transaction_type='expense' AND transaction_date='$today' GROUP BY category";
} else {
    $pie_sql = "SELECT category, SUM(amount) as total FROM transactions WHERE transaction_type='expense' AND strftime('%m', transaction_date)='$current_month_num' AND strftime('%Y', transaction_date)='$current_year' GROUP BY category";
}
$pie_res = $conn->query($pie_sql);
$pie_labels = [];
$pie_data = [];
if($pie_res) {
    while($r = $pie_res->fetch(PDO::FETCH_ASSOC)) {
        $pie_labels[] = $r['category'] ?: 'อื่นๆ';
        $pie_data[] = (float)$r['total'];
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บัญชีและการเงิน (Finance) | Yuncha Valley</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        /* 🌟 คืนค่า Theme หลักทั้งหมดให้เหมือนเดิม */
        body { background-color: #000000; color: #ffffff; font-family: 'Prompt', sans-serif; overflow-x: hidden; position: relative; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #000000; }
        ::-webkit-scrollbar-thumb { background: #00e676; border-radius: 10px; }

        /* â„ï¸ เอฟเฟกต์หิมะ/ดาวตก */
        .stars-container { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .star { position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0; animation: fall linear infinite; box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.8); }
        @keyframes fall {
            0% { transform: translateY(-10vh) translateX(0) scale(1); opacity: 1; }
            100% { transform: translateY(110vh) translateX(-20vw) scale(0); opacity: 0; }
        }

        .neon-pro { position: relative; background: #0f0f0f; border-radius: 1rem; z-index: 1; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 0 0 1px rgba(255,255,255,0.1); }
        .neon-pro-glow { position: absolute; inset: -2px; border-radius: 1.1rem; z-index: -2; overflow: hidden; opacity: 0; transition: opacity 0.3s ease; }
        .neon-pro-glow::before { content: ''; position: absolute; top: 50%; left: 50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 70%, var(--neon-color) 100%); transform: translate(-50%, -50%); animation: spin-border 2s linear infinite; }
        .neon-pro::before { content: ''; position: absolute; inset: 0; background: #0f0f0f; border-radius: 1rem; z-index: -1; }
        @keyframes spin-border { 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        .neon-pro:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 20px var(--neon-color); z-index: 10; }
        .neon-pro:hover .neon-pro-glow { opacity: 1; }
        .icon-glow { transition: all 0.3s; color: #a3a3a3; }
        .neon-pro:hover .icon-glow { color: var(--neon-color) !important; filter: drop-shadow(0 0 8px var(--neon-color)); transform: scale(1.15); }

        .input-dark { background-color: #050505; border: 1px solid #333; color: white; padding: 0.75rem 1rem; border-radius: 0.75rem; outline: none; width: 100%; transition: all 0.3s; appearance: textfield; }
        .input-dark:focus { border-color: var(--neon-color); box-shadow: 0 0 10px var(--neon-color); }
        
        input[type="date"]::-webkit-calendar-picker-indicator, input[type="time"]::-webkit-calendar-picker-indicator, input[type="month"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { appearance: none; margin: 0; }

        .neon-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
        .neon-switch input { opacity: 0; width: 0; height: 0; }
        .neon-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #151515; transition: .3s; border-radius: 26px; border: 1px solid #333; }
        .neon-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #555; transition: .3s; border-radius: 50%; }
        input:checked + .neon-slider { background-color: rgba(0, 230, 118, 0.1); border-color: #00e676; box-shadow: 0 0 10px rgba(0, 230, 118, 0.2); }
        input:checked + .neon-slider:before { transform: translateX(22px); background-color: #00e676; box-shadow: 0 0 10px #00e676; }
        .radio-btn-group input[type="radio"]:checked + div { background: rgba(0, 230, 118, 0.1); border-color: #00e676; color: #00e676; }
        .radio-btn-group input[type="radio"][value="expense"]:checked + div { background: rgba(255, 0, 60, 0.1); border-color: #ff003c; color: #ff003c; }

        .quick-add-in:hover { border-color: #00ff41; color: #00ff41; background: rgba(0,255,65,0.05); transform: translateY(-2px); }
        .quick-add-out:hover { border-color: #ff003c; color: #ff003c; background: rgba(255,0,60,0.05); transform: translateY(-2px); }

        .table-container { max-height: calc(100vh - 400px); overflow-y: auto; }
        th.sticky-header { position: sticky; top: 0; background-color: #050505; z-index: 10; border-bottom: 1px solid #222; }

    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important;}</style>
</head>
<body class="h-screen flex overflow-hidden selection:bg-ycMint selection:text-black bg-black">

    <div class="stars-container" id="starsBox"></div>
    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <!-- 🌟 Sidebar อัปเดตใหม่ เมนูเรียงเป๊ะแบบ Dashboard ไม่มี if(!$is_counter) ให้รวนใจ -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden w-full relative z-10 bg-[#0a0a0a]">
        
        <!-- Header -->
                <header class="h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 flex justify-between items-center z-30 sticky top-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg text-ycBlue border border-gray-800"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-black text-white tracking-wide">FINANCE & <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00d0ff] to-[#ff00ff]">ACCOUNTING</span></h2>
                    <p class="text-sm text-gray-400 font-medium">ระบบจัดการบัญชีและการเงิน</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-4">เวลาปัจจุบัน: <span id="clockStatus" style="color: #00d0ff;"><?= date('H:i:s') ?></span></p>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
        <?php if ($has_access): ?>

            <div class="max-w-7xl mx-auto space-y-6 pb-12">
                
                <!-- 🌟 สลับมุมมอง (รายวัน / รายเดือน) -->
                <div class="flex justify-center mb-6 border-b border-gray-800 pb-6 gs-anim">
                    <div class="bg-[#0a0a0a] p-1 rounded-xl border border-gray-800 inline-flex">
                        <a href="finance.php?view=daily&month=<?= $selected_month ?>" class="<?= $view_mode == 'daily' ? 'bg-[#151515] text-white border border-gray-700 shadow-md' : 'text-gray-500 hover:text-gray-300' ?> px-6 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2">
                            <i class="ph-bold ph-calendar-blank <?= $view_mode == 'daily' ? 'text-ycGreen' : '' ?>"></i> บัญชีรายวัน
                        </a>
                        <a href="finance.php?view=monthly&month=<?= $selected_month ?>" class="<?= $view_mode == 'monthly' ? 'bg-[#151515] text-white border border-gray-700 shadow-md' : 'text-gray-500 hover:text-gray-300' ?> px-6 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2">
                            <i class="ph-bold ph-calendar-check <?= $view_mode == 'monthly' ? 'text-ycBlue' : '' ?>"></i> บัญชีรายเดือน
                        </a>
                    </div>
                </div>

                <!-- 📊 สรุปยอด (KPI Cards) ใช้ไฟนีออนรอบกรอบ -->
                <div class="flex justify-between items-end mb-3 gap-3 gs-anim">
                    <?php if($view_mode == 'daily'): ?>
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2"><i class="ph-bold ph-calendar-blank text-white"></i> สรุปยอดรายวัน</h3>
                        <div class="flex items-center bg-[#0a0a0a] border border-gray-700 rounded-lg overflow-hidden px-3 py-1">
                            <span class="text-xs text-gray-500 font-bold mr-2">วันที่:</span>
                            <input type="date" class="bg-transparent border-none text-white text-sm outline-none font-bold cursor-pointer" style="--neon-color: #00ff41;" value="<?= $selected_date ?>" onchange="window.location.href='finance.php?view=daily&date=' + this.value">
                        </div>
                    <?php else: ?>
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2"><i class="ph-bold ph-calendar-check text-white"></i> สรุปยอดรายเดือน</h3>
                        <div class="flex items-center bg-[#0a0a0a] border border-gray-700 rounded-lg overflow-hidden px-3 py-1">
                            <span class="text-xs text-gray-500 font-bold mr-2">รอบเดือน:</span>
                            <input type="month" class="bg-transparent border-none text-white text-sm outline-none font-bold cursor-pointer" style="--neon-color: #00ff41;" value="<?= $selected_month ?>" onchange="window.location.href='finance.php?view=monthly&month=' + this.value">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 gs-anim">
                    <div class="neon-pro border border-gray-800 rounded-2xl p-5 shadow-2xl relative overflow-hidden group" style="--neon-color: #00ff41;">
                        <div class="absolute -right-4 -bottom-4 opacity-5 text-ycGreen"><i class="ph-fill ph-arrow-down-left text-8xl"></i></div>
                        <p class="text-xs font-bold text-ycGreen uppercase tracking-widest mb-1"><i class="ph-bold ph-arrow-down-left"></i> รับรวม<?= $view_mode=='daily' ? ($selected_date==$today ? 'วันนี้' : 'รายวัน') : 'เดือนนี้' ?></p>
                        <h3 class="text-3xl font-black text-white">฿<?= number_format($display_income, 2) ?></h3>
                    </div>
                    <div class="neon-pro border border-gray-800 rounded-2xl p-5 shadow-2xl relative overflow-hidden group" style="--neon-color: #ff003c;">
                        <div class="absolute -right-4 -bottom-4 opacity-5 text-ycRed"><i class="ph-fill ph-arrow-up-right text-8xl"></i></div>
                        <p class="text-xs font-bold text-ycRed uppercase tracking-widest mb-1"><i class="ph-bold ph-arrow-up-right"></i> จ่ายรวม<?= $view_mode=='daily' ? ($selected_date==$today ? 'วันนี้' : 'รายวัน') : 'เดือนนี้' ?></p>
                        <h3 class="text-3xl font-black text-white">฿<?= number_format($display_expense, 2) ?></h3>
                    </div>
                    <div class="neon-pro border border-gray-800 rounded-2xl p-5 shadow-2xl relative overflow-hidden group border-b-4 border-b-ycGold" style="--neon-color: #ffcc00;">
                        <div class="absolute -right-4 -bottom-4 opacity-5 text-ycGold"><i class="ph-fill ph-scales text-8xl"></i></div>
                        <p class="text-xs font-bold text-ycGold uppercase tracking-widest mb-1"><i class="ph-fill ph-scales"></i> กำไร<?= $view_mode=='daily' ? ($selected_date==$today ? 'วันนี้' : 'รายวัน') : 'เดือนนี้' ?></p>
                        <h3 class="text-3xl font-black text-ycGold">฿<?= number_format($display_profit, 2) ?></h3>
                    </div>
                </div>

                <!-- 📊 กราฟสถิติ (Charts) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6 gs-anim">
                    <div class="lg:col-span-2 bg-[#0a0a0a] border border-gray-800 rounded-2xl p-5 shadow-2xl relative">
                        <h3 class="text-sm font-bold text-gray-400 mb-4 uppercase tracking-wider"><i class="ph-bold ph-chart-bar text-ycBlue"></i> แนวโน้มรายรับ-รายจ่าย (เดือน <?= date('M Y', strtotime($selected_month)) ?>)</h3>
                        <div class="h-64 w-full relative">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-[#0a0a0a] border border-gray-800 rounded-2xl p-5 shadow-2xl relative">
                        <h3 class="text-sm font-bold text-gray-400 mb-4 uppercase tracking-wider"><i class="ph-bold ph-chart-pie-slice text-ycRed"></i> สัดส่วนรายจ่าย <?= $view_mode=='daily'? ($selected_date==$today ? 'วันนี้' : 'ประจำวัน') :'เดือนนี้' ?></h3>
                        <div class="h-64 w-full relative flex items-center justify-center">
                            <?php if(empty($pie_data)): ?>
                                <p class="text-gray-600 font-bold text-sm">ไม่มีรายจ่าย</p>
                            <?php else: ?>
                                <canvas id="pieChart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ⚡ Quick Add & Big Add Button -->
                <?php if($view_mode == 'daily'): ?>
                <div class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-400 mb-3 uppercase tracking-wider flex items-center gap-2"><i class="ph-fill ph-lightning text-ycGold"></i> เมนูด่วน (Quick Add)</h3>
                        <div class="flex flex-wrap gap-3">
                            <button onclick="openQuickAdd('income', 'ยอดยกมา (เงินทอน)', '')" class="quick-add-in px-4 py-2 bg-[#0f0f0f] border border-gray-800 rounded-xl text-sm font-bold text-gray-300 transition flex items-center gap-2 hover:bg-[#1a1a1a]"><i class="ph-bold ph-coins text-ycGold"></i> ยอดยกมา (เงินทอน)</button>
                            <button onclick="openQuickAdd('income', 'ค่าอาหาร/เครื่องดื่ม', '')" class="quick-add-in px-4 py-2 bg-[#0f0f0f] border border-gray-800 rounded-xl text-sm font-bold text-gray-300 transition flex items-center gap-2 hover:bg-[#1a1a1a]"><i class="ph-bold ph-coffee text-ycGreen"></i> ค่าอาหาร</button>
                            <button onclick="openQuickAdd('income', 'ค่าเสริม (เตียงเสริม / BBQ)', '')" class="quick-add-in px-4 py-2 bg-[#0f0f0f] border border-gray-800 rounded-xl text-sm font-bold text-gray-300 transition flex items-center gap-2 hover:bg-[#1a1a1a]"><i class="ph-bold ph-fire text-ycGreen"></i> ชุด BBQ</button>
                            <button onclick="openQuickAdd('income', 'ค่าชา (Afternoon Tea)', '')" class="quick-add-in px-4 py-2 bg-[#0f0f0f] border border-gray-800 rounded-xl text-sm font-bold text-gray-300 transition flex items-center gap-2 hover:bg-[#1a1a1a]"><i class="ph-bold ph-coffee-bean text-ycGreen"></i> ค่าชา (Afternoon Tea)</button>
                            <span class="border-l border-gray-800 mx-1 hidden md:block"></span>
                            <button onclick="openQuickAdd('expense', 'วัตถุดิบอาหาร', '')" class="quick-add-out px-4 py-2 bg-[#0f0f0f] border border-gray-800 rounded-xl text-sm font-bold text-gray-300 transition flex items-center gap-2 hover:bg-[#1a1a1a]"><i class="ph-bold ph-shopping-cart text-ycRed"></i> ซื้อของเข้าครัว</button>
                            <button onclick="openModal('add', 'expense', 'ซ่อมบำรุง', '')" class="quick-add-out px-4 py-2 bg-[#0f0f0f] border border-gray-800 rounded-xl text-sm font-bold text-gray-300 transition flex items-center gap-2 hover:bg-[#1a1a1a]"><i class="ph-bold ph-wrench text-ycRed"></i> บันทึกซ่อมบำรุง</button>
                        </div>
                    </div>
                    <button onclick="openModal('add', 'income', '', '')" class="neon-pro px-6 py-3 rounded-xl font-bold flex items-center justify-center gap-2 shadow-xl whitespace-nowrap self-start md:self-end mt-4 md:mt-0" style="--neon-color: #00d0ff;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-plus-circle text-xl text-white relative z-10 icon-glow"></i>
                        <span class="text-white relative z-10">เพิ่มรายการบัญชีใหม่</span>
                    </button>
                </div>
                <?php else: ?>
                <div class="mt-8 flex justify-end">
                    <button onclick="openModal('add', 'income', '', '')" class="neon-pro px-6 py-3 rounded-xl font-bold flex items-center justify-center gap-2 shadow-xl whitespace-nowrap" style="--neon-color: #00d0ff;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-plus-circle text-xl text-white relative z-10 icon-glow"></i>
                        <span class="text-white relative z-10">เพิ่มรายการบัญชีใหม่</span>
                    </button>
                </div>
                <?php endif; ?>

                <!-- 📋 ตารางรายการบัญชี (Ledger Real-time) -->
                <div id="ledgerTableContainer" class="bg-[#0a0a0a] border border-gray-800 rounded-2xl overflow-hidden shadow-2xl relative z-10 mt-6">
                    <div class="p-5 border-b border-gray-800 flex flex-wrap gap-4 justify-between items-center bg-[#050505]">
                        <h3 class="text-lg font-bold text-white"><i class="ph-fill ph-list-dashes text-ycBlue mr-2"></i> รายการบัญชี<?= $view_mode=='daily' ? 'ประจำวันที่ '.date('d/m/Y', strtotime($selected_date)) : 'เดือน '.date('M Y', strtotime($selected_month)) ?></h3>
                        <div class="flex gap-2">
                            <!-- 🌟 Export Group -->
                            <div class="flex gap-2 bg-[#0a0a0a] p-1 rounded-xl border border-gray-800">
                                <a href="export_finance.php?view=<?= $view_mode ?>&month=<?= $selected_month ?>" class="bg-[#111] hover:bg-[#151515] text-green-500 px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 transition" title="ดาวน์โหลด Excel">
                                    <i class="ph-bold ph-file-xls text-lg"></i> <span class="hidden md:inline">Excel</span>
                                </a>
                                <button onclick="exportToPDF()" class="bg-[#111] hover:bg-[#151515] text-red-500 px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 transition" title="ดาวน์โหลด PDF">
                                    <i class="ph-bold ph-file-pdf text-lg"></i> <span class="hidden md:inline">PDF</span>
                                </button>
                                <button onclick="exportToPNG()" class="bg-[#111] hover:bg-[#151515] text-blue-500 px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 transition" title="ดาวน์โหลดรูปภาพ (PNG)">
                                    <i class="ph-bold ph-image text-lg"></i> <span class="hidden md:inline">PNG</span>
                                </button>
                                <button onclick="printReport()" class="bg-[#111] hover:bg-[#151515] text-gray-300 px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 transition" title="พิมพ์รายงาน (Print)">
                                    <i class="ph-bold ph-printer text-lg"></i> <span class="hidden md:inline">Print</span>
                                </button>
                            </div>
                            <!-- 🌟 ตัวกรองของจริง -->
                            <select id="filterType" onchange="filterLedger()" class="input-dark py-1.5 px-3 w-auto text-xs font-bold cursor-pointer border-gray-700" style="--neon-color: #00d0ff;">
                                <option value="all">แสดงทั้งหมด (All)</option>
                                <option value="income">🟢 เฉพาะรายรับ (Income)</option>
                                <option value="expense">🔴 เฉพาะรายจ่าย (Expense)</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400 min-w-[1000px]">
                            <thead class="text-[10px] uppercase bg-[#0a0a0a] text-gray-500 font-bold border-b border-gray-800">
                                <tr>
                                    <th class="px-6 py-4 sticky-header w-32">วัน/เวลา</th>
                                    <th class="px-6 py-4 sticky-header">ประเภท / หมวดหมู่</th>
                                    <th class="px-6 py-4 sticky-header">รายละเอียด</th>
                                    <th class="px-6 py-4 sticky-header text-center">ช่องทาง</th>
                                    <th class="px-6 py-4 sticky-header text-center">หลักฐาน</th>
                                    <th class="px-6 py-4 sticky-header text-right">จำนวนเงิน (฿)</th>
                                    <th class="px-6 py-4 sticky-header text-center w-36">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="ledgerBody" class="divide-y divide-gray-800">
                                <?php if(empty($transactions)): ?>
                                    <tr id="emptyRow"><td colspan="7" class="text-center py-12 text-gray-500 font-bold">ไม่มีรายการบัญชี กดปุ่มเพิ่มรายการเพื่อเริ่มต้น</td></tr>
                                <?php else: ?>
                                    <?php foreach($transactions as $t): 
                                        $is_in = ($t['transaction_type'] == 'income');
                                        $color = $is_in ? 'text-ycGreen' : 'text-ycRed';
                                        $sign = $is_in ? '+' : '-';
                                        $bg_icon = $is_in ? 'bg-green-900/30 border border-green-500/50 text-ycGreen' : 'bg-red-900/30 border border-red-500/50 text-ycRed';
                                        $icon_arrow = $is_in ? 'ph-arrow-down-left' : 'ph-arrow-up-right';
                                        $type_text = $is_in ? 'รายรับ' : 'รายจ่าย';
                                        $has_img = !empty($t['image_path']);
                                    ?>
                                    <!-- data-type สำหรับ Filter -->
                                    <tr class="ledger-row hover:bg-[#151515] transition-colors group" data-type="<?= $t['transaction_type'] ?>">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-gray-300"><?= date('d/m/Y', strtotime($t['transaction_date'])) ?></p>
                                            <p class="text-[10px] text-gray-500 font-mono"><?= date('H:i', strtotime($t['transaction_time'])) ?></p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg <?= $bg_icon ?> flex items-center justify-center"><i class="ph-bold <?= $icon_arrow ?> text-lg"></i></div>
                                                <div>
                                                    <p class="font-bold text-white text-sm"><?= htmlspecialchars($t['category']) ?></p>
                                                    <p class="text-[10px] <?= $color ?> font-bold"><?= $type_text ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-gray-300 text-xs truncate max-w-[200px]"><?= htmlspecialchars($t['notes'] ?: '-') ?></p>
                                            <?php if($t['reference_no']): ?><p class="text-[10px] text-gray-500 font-mono mt-0.5">Ref: <?= htmlspecialchars($t['reference_no']) ?></p><?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="bg-gray-800 border border-gray-700 text-gray-300 px-2 py-1 rounded text-[10px] font-bold"><?= htmlspecialchars($t['payment_method']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if($has_img): ?>
                                                <a href="<?= htmlspecialchars($t['image_path']) ?>" target="_blank" class="inline-flex w-8 h-8 rounded-lg bg-gray-800 hover:bg-ycBlue hover:text-black text-gray-300 items-center justify-center transition" title="ดูรูปสลิป/ใบเสร็จ"><i class="ph-bold ph-image text-xl"></i></a>
                                            <?php else: ?>
                                                <span class="text-gray-700">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-lg <?= $color ?> tracking-wide">
                                            <?= $sign . number_format($t['net_amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="openModal('edit', '<?= $t['transaction_type'] ?>', '<?= htmlspecialchars($t['category'], ENT_QUOTES) ?>', <?= $t['amount'] ?>, '<?= htmlspecialchars($t['payment_method'], ENT_QUOTES) ?>', '<?= htmlspecialchars($t['notes'], ENT_QUOTES) ?>', <?= $t['id'] ?>, '<?= $t['transaction_date'] ?>', '<?= $t['transaction_time'] ?>')" class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-ycBlue hover:text-black text-gray-400 flex items-center justify-center transition" title="แก้ไข (Edit)"><i class="ph-bold ph-pencil-simple text-lg"></i></button>
                                                
                                                <button onclick="openModal('duplicate', '<?= $t['transaction_type'] ?>', '<?= htmlspecialchars($t['category'], ENT_QUOTES) ?>', <?= $t['amount'] ?>, '<?= htmlspecialchars($t['payment_method'], ENT_QUOTES) ?>', '<?= htmlspecialchars($t['notes'], ENT_QUOTES) ?>')" class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-ycGold hover:text-black text-gray-400 flex items-center justify-center transition" title="ทำซ้ำ (Duplicate)"><i class="ph-bold ph-copy text-lg"></i></button>
                                                
                                                <form action="finance.php" method="POST" onsubmit="event.preventDefault(); Swal.fire({title:'ยืนยัน?', text:'คุณยืนยันที่จะลบรายการนี้ใช่หรือไม่?', icon:'warning', showCancelButton:true, confirmButtonColor:'#ff003c', cancelButtonColor:'#333', confirmButtonText:'ยืนยัน', cancelButtonText:'ยกเลิก', background:'#1a1a1a', color:'#fff'}).then((r)=>{if(r.isConfirmed){this.removeAttribute('onsubmit'); this.submit();}})" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="tx_id" value="<?= $t['id'] ?>">
                                                    <input type="hidden" name="return_view" value="<?= $view_mode ?>">
                                                    <input type="hidden" name="return_month" value="<?= $selected_month ?>">
                                                    <button type="submit" class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-ycRed hover:text-white text-gray-400 flex items-center justify-center transition" title="ลบ (Delete)"><i class="ph-bold ph-trash text-lg"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <!-- แจ้งเตือนเมื่อค้นหาแล้วไม่เจอ -->
                                <tr id="noDataRow" class="hidden"><td colspan="7" class="text-center py-12 text-gray-500 font-bold">ไม่มีรายการบัญชีในหมวดหมู่นี้</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        
        <?php else: ?>
        <!-- Premium Access Denied Screen (Glassmorphism & Glowing Neon Overlay) -->
        <div class="max-w-md mx-auto my-12 neon-pro p-8 text-center" style="--neon-color: #ff003c;">
            <div class="w-20 h-20 mx-auto bg-red-950/30 border border-red-500/50 rounded-full flex items-center justify-center mb-6 shadow-[0_0_20px_rgba(255,0,60,0.3)]">
                <i class="ph-fill ph-shield-warning text-4xl text-ycRed drop-shadow-[0_0_8px_#ff003c]"></i>
            </div>
            <h3 class="text-2xl font-black text-white mb-2">ปฏิเสธการเข้าถึง</h3>
            <p class="text-sm text-gray-400 mb-6">ขออภัย บัญชีของคุณไม่มีสิทธิ์เข้าใช้งานหน้านี้ เฉพาะเจ้าหน้าที่ระดับบริหารที่มีสิทธิ์เข้าถึงเท่านั้น</p>
            <a href="dashboard.php" class="inline-flex items-center gap-2 bg-[#111] border border-gray-800 hover:border-ycGold hover:text-ycGold text-white px-6 py-3 rounded-xl font-bold transition">
                <i class="ph-bold ph-arrow-left"></i> กลับไปแผงควบคุมหลัก
            </a>
        </div>
        <?php endif; ?>
</main>
    </div>

    <!-- 🔥 MODAL: บันทึกรายการ (Add / Edit / Duplicate) กลับมาใช้ไฟนีออนปุ่มกด & กล่องแนบสลิปดีไซน์เดิม -->
    <div id="financeModal" class="fixed inset-0 bg-black/90 z-[100] hidden flex items-center justify-center backdrop-blur-sm pt-10 pb-10">
        <div class="bg-[#0f0f0f] w-full max-w-2xl rounded-2xl border border-gray-800 shadow-[0_0_30px_rgba(0,255,65,0.15)] flex flex-col max-h-[90vh]">
            
            <div class="flex justify-between items-center p-6 border-b border-gray-800 bg-[#0a0a0a] rounded-t-2xl shrink-0">
                <div class="flex items-center gap-3">
                    <div id="modalIconBox" class="w-10 h-10 bg-green-900/20 rounded-xl flex items-center justify-center text-ycGreen border border-green-500/30">
                        <i class="ph-bold ph-receipt text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white drop-shadow-md" id="modalTitle">บันทึกรายการบัญชี</h3>
                </div>
                <button type="button" onclick="closeModal()" class="w-8 h-8 bg-[#1a1a1a] hover:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 transition">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>
            
            <!-- 🌟 รองรับการอัปโหลดไฟล์ด้วย enctype="multipart/form-data" -->
            <form action="finance.php" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto flex flex-col">
                <div class="p-6 space-y-6 flex-1">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="tx_id" id="txId">
                    <input type="hidden" name="return_view" value="<?= $view_mode ?>">
                    <input type="hidden" name="return_month" value="<?= $selected_month ?>">

                    <!-- เลือกประเภท (Income / Expense) -->
                    <div class="flex gap-4 radio-btn-group mb-2">
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="tx_type" id="radioIncome" value="income" checked class="peer sr-only" onchange="updateCategories('income')">
                            <div class="border-2 border-gray-800 bg-[#0a0a0a] text-gray-600 p-4 rounded-xl text-center font-bold text-sm transition-all duration-300 flex justify-center items-center gap-3 peer-checked:border-[#00ff41] peer-checked:bg-[#00ff41]/10 peer-checked:text-[#00ff41] peer-checked:shadow-[0_0_20px_rgba(0,255,65,0.2)] hover:border-gray-600">
                                <i class="ph-bold ph-trend-up text-2xl"></i>
                                <span>รายรับ (INCOME)</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="tx_type" id="radioExpense" value="expense" class="peer sr-only" onchange="updateCategories('expense')">
                            <div class="border-2 border-gray-800 bg-[#0a0a0a] text-gray-600 p-4 rounded-xl text-center font-bold text-sm transition-all duration-300 flex justify-center items-center gap-3 peer-checked:border-[#ff003c] peer-checked:bg-[#ff003c]/10 peer-checked:text-[#ff003c] peer-checked:shadow-[0_0_20px_rgba(255,0,60,0.2)] hover:border-gray-600">
                                <i class="ph-bold ph-trend-down text-2xl"></i>
                                <span>รายจ่าย (EXPENSE)</span>
                            </div>
                        </label>
                    </div>

                    <!-- วันที่ & เวลา (เปลี่ยนไว) -->
                    <div class="grid grid-cols-2 gap-4 bg-[#151515] p-4 rounded-xl border border-gray-800">
                        <div class="col-span-2 flex justify-between items-center mb-1">
                            <label class="text-xs font-bold text-gray-400 uppercase">วันที่ และ เวลา (Date & Time) <span class="text-ycRed">*</span></label>
                            <div class="flex gap-2">
                                <button type="button" onclick="setQuickDate(0)" class="text-[10px] bg-gray-800 hover:bg-ycBlue hover:text-black text-gray-300 px-2 py-1 rounded font-bold transition">วันนี้</button>
                                <button type="button" onclick="setQuickDate(-1)" class="text-[10px] bg-gray-800 hover:bg-ycBlue hover:text-black text-gray-300 px-2 py-1 rounded font-bold transition">เมื่อวาน</button>
                            </div>
                        </div>
                        <input type="date" name="date" id="txDate" required class="input-dark" style="--neon-color: #00ff41;">
                        <input type="time" name="time" id="txTime" required class="input-dark" style="--neon-color: #00ff41;">
                    </div>

                    <!-- จำนวนเงิน หมวดหมู่ ช่องทาง -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- จำนวนเงิน (ใหญ่ๆ) -->
                        <div class="md:col-span-1 bg-[#151515] border border-gray-800 p-4 rounded-xl flex flex-col justify-center">
                            <label class="block text-xs font-bold text-gray-400 mb-2 uppercase">จำนวนเงิน (บาท) <span class="text-ycRed">*</span></label>
                            <div class="relative mt-2">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-2xl text-gray-500">฿</span>
                                <input type="number" name="amount" id="txAmount" required min="1" step="0.01" class="w-full bg-[#0a0a0a] text-white text-3xl font-black py-4 pl-12 pr-4 rounded-xl border-2 border-gray-700 focus:border-[#00ff41] outline-none transition shadow-inner" placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- หมวดหมู่ & ช่องทาง (Custom Dropdown UI) -->
                        <div class="md:col-span-2 bg-[#151515] border border-gray-800 p-4 rounded-xl space-y-5">
                            <div class="relative custom-dropdown-group">
                                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase">หมวดหมู่ (Category) <span class="text-ycRed">*</span></label>
                                <div id="catDropdownTrigger" class="w-full bg-[#0a0a0a] text-white text-base font-bold py-3 pl-4 pr-4 rounded-xl border-2 border-gray-700 hover:border-[#00ff41] outline-none cursor-pointer transition shadow-inner flex items-center justify-between" onclick="toggleCustomDropdown('catList')">
                                    <span id="catSelectedText" class="truncate text-gray-400">เลือกหมวดหมู่...</span>
                                    <i class="ph-bold ph-caret-down text-gray-400"></i>
                                </div>
                                <div id="catList" class="absolute top-full left-0 w-full mt-2 bg-[#0f0f0f] border border-gray-700 rounded-xl shadow-2xl hidden z-[200] max-h-60 overflow-y-auto dropdown-menu">
                                    <!-- Injected by JS -->
                                </div>
                                <input type="hidden" name="category" id="catSelectHidden" required>
                            </div>

                            <div class="relative custom-dropdown-group mt-5">
                                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase">ช่องทางรับ/จ่าย <span class="text-ycRed">*</span></label>
                                <div id="methodDropdownTrigger" class="w-full bg-[#0a0a0a] text-white text-base font-bold py-3 pl-4 pr-4 rounded-xl border-2 border-gray-700 hover:border-[#00ff41] outline-none cursor-pointer transition shadow-inner flex items-center justify-between" onclick="toggleCustomDropdown('methodList')">
                                    <span id="methodSelectedText" class="truncate">💸 เงินสด (Cash)</span>
                                    <i class="ph-bold ph-caret-down text-gray-400"></i>
                                </div>
                                <div id="methodList" class="absolute top-full left-0 w-full mt-2 bg-[#0f0f0f] border border-gray-700 rounded-xl shadow-2xl hidden z-[200] max-h-60 overflow-y-auto dropdown-menu">
                                    <div class="p-3 hover:bg-[#1a1a1a] hover:text-[#00ff41] cursor-pointer transition border-b border-gray-800 font-bold" onclick="selectOption('method', 'เงินสด', '💸 เงินสด (Cash)')">💸 เงินสด (Cash)</div>
                                    <div class="p-3 hover:bg-[#1a1a1a] hover:text-[#00ff41] cursor-pointer transition border-b border-gray-800 font-bold" onclick="selectOption('method', 'โอนเงิน', '🏦 โอนเงิน (Transfer)')">🏦 โอนเงิน (Transfer)</div>
                                    <div class="p-3 hover:bg-[#1a1a1a] hover:text-[#00ff41] cursor-pointer transition border-b border-gray-800 font-bold" onclick="selectOption('method', 'QR', '📱 สแกน QR (PromptPay)')">📱 สแกน QR (PromptPay)</div>
                                    <div class="p-3 hover:bg-[#1a1a1a] hover:text-[#00ff41] cursor-pointer transition font-bold" onclick="selectOption('method', 'บัตรเครดิต', '💳 บัตรเครดิต (Credit Card)')">💳 บัตรเครดิต (Credit Card)</div>
                                </div>
                                <input type="hidden" name="method" id="methodSelectHidden" value="เงินสด" required>
                            </div>
                        </div>
                    </div>

                    <!-- รายละเอียด / หมายเหตุ (ยุบรวมให้ง่าย) -->
                    <div class="bg-[#151515] p-4 rounded-xl border border-gray-800">
                        <label class="block text-xs font-bold text-gray-500 mb-2">รายละเอียด / หมายเหตุ (ถ้ามี)</label>
                        <input type="text" name="note" id="txNote" class="w-full bg-[#0a0a0a] text-white text-sm py-3 px-4 rounded-xl border-2 border-gray-700 focus:border-ycBlue outline-none transition shadow-inner" placeholder="เช่น อ้างอิง Booking, ชื่อผู้จอง, หรือรายละเอียดอื่นๆ">
                    </div>

                    <!-- 🌟 หลักฐาน / รูปถ่าย (ดีไซน์กล่องรอยปะเดิม แต่ใช้งานไฟล์ได้จริง) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">หลักฐาน (สลิป / บิล / ใบเสร็จ)</label>
                        <label class="block border-2 border-dashed border-gray-700 hover:border-ycBlue rounded-xl p-4 text-center cursor-pointer transition bg-[#0a0a0a] relative overflow-hidden group">
                            <!-- Input File ซ่อนไว้หลัง UI -->
                            <input type="file" name="slip_image" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewSlip(this)">
                            
                            <!-- กล่อง UI สวยๆ -->
                            <div id="slipPreviewContainer" class="flex flex-col items-center justify-center pointer-events-none">
                                <i id="slipCameraIcon" class="ph-duotone ph-camera text-2xl text-gray-500 mb-1 group-hover:text-ycBlue transition"></i>
                                <p id="slipFileName" class="text-xs text-gray-400 font-bold group-hover:text-ycBlue transition">แนบรูปถ่าย หรือ สลิปโอนเงิน</p>
                            </div>
                        </label>
                    </div>

                </div>

                <!-- Footer ปุ่มนีออนสวยๆ -->
                <div class="p-6 border-t border-gray-800 bg-[#0a0a0a] shrink-0 flex justify-end gap-3 rounded-b-2xl">
                    <button type="button" onclick="closeModal()" class="neon-pro px-8 py-3 flex items-center gap-2 mr-4" style="--neon-color: #a3a3a3;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-x text-xl text-gray-300 relative z-10 icon-glow"></i>
                        <span class="font-bold text-gray-300 relative z-10">ยกเลิก</span>
                    </button>
                    <button type="submit" id="btnSubmitModal" class="neon-pro px-10 py-3 flex items-center gap-2" style="--neon-color: #00ff41;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-floppy-disk text-xl text-black relative z-10 icon-glow"></i>
                        <span class="font-bold text-white relative z-10">บันทึกรายการ</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 🌟 Alert แจ้งเตือนเมื่อกระทำสำเร็จ -->
    <?php if(isset($_GET['success'])): ?>
    <div id="successAlert" class="fixed top-5 right-5 z-[200] bg-[#0a0a0a] border <?= $_GET['success']=='delete' ? 'border-ycRed text-ycRed shadow-[0_0_15px_rgba(255,0,60,0.3)]' : 'border-ycGreen text-ycGreen shadow-[0_0_15px_rgba(0,255,65,0.3)]' ?> px-6 py-4 rounded-xl font-bold flex items-center gap-3 gs-anim-alert">
        <i class="ph-bold <?= $_GET['success']=='delete' ? 'ph-trash' : 'ph-check-circle' ?> text-2xl"></i>
        <span>ดำเนินการเสร็จสิ้นเรียบร้อย</span>
    </div>
    <script>
        setTimeout(() => { document.getElementById('successAlert').style.display = 'none'; }, 3000);
    </script>
    <?php endif; ?>

    <script>
        // 🌟 จำตำแหน่ง Scroll ของเมนูด้านซ้าย
        document.addEventListener("DOMContentLoaded", function() { 
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if (sessionStorage.getItem('sidebarScrollPos') && sidebarEl) sidebarEl.scrollTop = sessionStorage.getItem('sidebarScrollPos');
        });
        window.onbeforeunload = function() {
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if(sidebarEl) sessionStorage.setItem('sidebarScrollPos', sidebarEl.scrollTop);
        };

        // 🌟 สร้างดาวตก Background
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

        // 🌟 UI แนบสลิป
        function previewSlip(input) {
            if (input.files && input.files[0]) {
                document.getElementById('slipFileName').innerText = "ไฟล์ที่เลือก: " + input.files[0].name;
                document.getElementById('slipCameraIcon').classList.replace('text-gray-500', 'text-ycGreen');
                document.getElementById('slipFileName').classList.replace('text-gray-400', 'text-ycGreen');
            } else {
                document.getElementById('slipFileName').innerText = 'แนบรูปถ่าย หรือ สลิปโอนเงิน';
                document.getElementById('slipCameraIcon').classList.replace('text-ycGreen', 'text-gray-500');
                document.getElementById('slipFileName').classList.replace('text-ycGreen', 'text-gray-400');
            }
        }

        // 🌟 1. ระบบ Filter ตารางบัญชี (ทำงานทันทีที่กดเลือก)
        function filterLedger() {
            const filterVal = document.getElementById('filterType').value;
            const rows = document.querySelectorAll('.ledger-row');
            let visibleCount = 0;

            rows.forEach(row => {
                if(filterVal === 'all' || row.getAttribute('data-type') === filterVal) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                }
            });

            const noData = document.getElementById('noDataRow');
            const emptyRow = document.getElementById('emptyRow');
            if(visibleCount === 0 && !emptyRow) noData.classList.remove('hidden');
            else if(noData) noData.classList.add('hidden');
        }

        
        // Custom Dropdown UI Logic
        function toggleCustomDropdown(id) {
            document.querySelectorAll(".dropdown-menu").forEach(el => {
                if(el.id !== id) el.classList.add("hidden");
            });
            document.getElementById(id).classList.toggle("hidden");
        }

        function selectOption(type, value, text) {
            if(type === "method") {
                document.getElementById("methodSelectHidden").value = value;
                document.getElementById("methodSelectedText").innerText = text;
                document.getElementById("methodList").classList.add("hidden");
            } else if (type === "cat") {
                document.getElementById("catSelectHidden").value = value;
                document.getElementById("catSelectedText").innerText = text;
                document.getElementById("catSelectedText").classList.replace("text-gray-400", "text-white");
                document.getElementById("catList").classList.add("hidden");
            }
        }

        document.addEventListener("click", (e) => {
            if(!e.target.closest(".custom-dropdown-group")) {
                document.querySelectorAll(".dropdown-menu").forEach(el => el.classList.add("hidden"));
            }
        });

        // 🌟 2. ระบบหมวดหมู่ (Categories) แบ่ง Optgroup
        
        
        

        function updateCategories(type, preselectCat = null) {
            const catList = document.getElementById("catList");
            const iconBox = document.getElementById("modalIconBox");
            const btnSubmit = document.getElementById("btnSubmitModal");
            const btnIcon = btnSubmit.querySelector("i.ph-floppy-disk");
            const trigger = document.getElementById("catDropdownTrigger");
            
            let html = "";
            let neonColor = "";
            
            if(type === "income") {
                neonColor = "#00ff41";
                iconBox.className = "w-10 h-10 bg-green-900/20 rounded-xl flex items-center justify-center text-ycGreen border border-green-500/30";
                iconBox.innerHTML = '<i class="ph-bold ph-arrow-down-left text-xl"></i>';
                
                document.documentElement.style.setProperty("--neon-color", "#00ff41"); 
                btnSubmit.style.setProperty("--neon-color", "#00ff41");
                if(btnIcon) btnIcon.classList.replace("text-white", "text-black");
                trigger.className = "w-full bg-[#0a0a0a] text-white text-base font-bold py-3 pl-4 pr-4 rounded-xl border-2 border-gray-700 hover:border-[#00ff41] outline-none cursor-pointer transition shadow-inner flex items-center justify-between";
                
                const items = [
                    "ค่าห้องพัก", "แพ็กเกจ", "ค่าอาหาร/เครื่องดื่ม", "ค่าชา (Afternoon Tea)",
                    "ค่าเสริม (เตียงเสริม / BBQ)", "ลูกค้า Walk-in", "ยอดยกมา (เงินทอน)", "รายได้อื่น ๆ"
                ];
                items.forEach(item => {
                    html += `<div class="p-3 hover:bg-[#1a1a1a] hover:text-[${neonColor}] cursor-pointer transition border-b border-gray-800 font-bold" onclick="selectOption('cat', '${item}', '${item}')">${item}</div>`;
                });
                
            } else {
                neonColor = "#ff003c";
                iconBox.className = "w-10 h-10 bg-red-900/20 rounded-xl flex items-center justify-center text-ycRed border border-red-500/30";
                iconBox.innerHTML = '<i class="ph-bold ph-arrow-up-right text-xl"></i>';
                
                document.documentElement.style.setProperty("--neon-color", "#ff003c");
                btnSubmit.style.setProperty("--neon-color", "#ff003c");
                if(btnIcon) btnIcon.classList.replace("text-black", "text-white");
                trigger.className = "w-full bg-[#0a0a0a] text-white text-base font-bold py-3 pl-4 pr-4 rounded-xl border-2 border-gray-700 hover:border-[#ff003c] outline-none cursor-pointer transition shadow-inner flex items-center justify-between";
                
                const groups = {
                    "🔧 ค่าใช้จ่ายประจำ": ["เงินเดือน", "ค่าน้ำ", "ค่าไฟ", "อินเทอร์เน็ต"],
                    "🍽️ วัตถุดิบ": ["อาหาร", "เครื่องดื่ม", "ชา"],
                    "🛠️ ซ่อม/ดูแล": ["ซ่อมบำรุง", "ทำความสะอาด", "อุปกรณ์"],
                    "🚗 อื่น ๆ": ["ค่าน้ำมัน", "ค่าเดินทาง", "ค่าใช้จ่ายเบ็ดเตล็ด"]
                };
                for(let [group, items] of Object.entries(groups)) {
                    html += `<div class="px-3 py-2 bg-[#111] text-gray-400 text-xs font-black uppercase tracking-wider">${group}</div>`;
                    items.forEach(item => {
                        let val = item === "อาหาร" ? "วัตถุดิบอาหาร" : (item === "ค่าใช้จ่ายเบ็ดเตล็ด" ? "เบ็ดเตล็ด" : item);
                        html += `<div class="p-3 pl-6 hover:bg-[#1a1a1a] hover:text-[${neonColor}] cursor-pointer transition border-b border-gray-800 font-bold" onclick="selectOption('cat', '${val}', '${item}')">${item}</div>`;
                    });
                }
            }

            catList.innerHTML = html;
            
            // Auto-select if requested
            if(preselectCat) {
                let displayTxt = preselectCat;
                if(preselectCat === "วัตถุดิบอาหาร") displayTxt = "อาหาร";
                if(preselectCat === "เบ็ดเตล็ด") displayTxt = "ค่าใช้จ่ายเบ็ดเตล็ด";
                selectOption("cat", preselectCat, displayTxt);
            } else {
                document.getElementById("catSelectedText").innerText = "เลือกหมวดหมู่...";
                document.getElementById("catSelectedText").classList.replace("text-white", "text-gray-400");
                document.getElementById("catSelectHidden").value = "";
            }
        }

        // 🌟 4. ปุ่มเปลี่ยนวันไว (Quick Date)
        function setQuickDate(daysOffset) {
            const dateInput = document.getElementById('txDate');
            let d = new Date(); d.setDate(d.getDate() + daysOffset);
            
            let year = d.getFullYear();
            let month = (d.getMonth() + 1).toString().padStart(2, '0');
            let day = d.getDate().toString().padStart(2, '0');
            dateInput.value = `${year}-${month}-${day}`;
        }

        function setTimeNow() {
            const timeInput = document.getElementById('txTime');
            let d = new Date();
            timeInput.value = `${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
        }

        // 🌟 5. การเปิด Modal อัจฉริยะ (Add/Edit/Duplicate)
        const modal = document.getElementById('financeModal');
        
        function openQuickAdd(type, category, amount) {
            openModal('add', type, category, amount);
        }

        function openModal(mode, type = 'income', cat = '', amount = '', method = 'โอนเงิน', note = '', id = '', dateStr = '', timeStr = '') {
            modal.classList.remove('hidden');
            
            // ล้างชื่อไฟล์รูปที่โชว์อยู่เวลาเปิดหน้าต่างใหม่
            document.querySelector('input[type="file"]').value = "";
            previewSlip(document.querySelector('input[type="file"]'));
            
            if(mode === 'add') {
                document.getElementById('modalTitle').innerText = 'บันทึกรายการบัญชีใหม่';
                document.getElementById('formAction').value = 'add';
                document.getElementById('txId').value = '';
                
                setQuickDate(0);
                setTimeNow();

                if(type === 'income') document.getElementById('radioIncome').checked = true;
                else document.getElementById('radioExpense').checked = true;
                
                updateCategories(type, cat);
                
                document.getElementById('txAmount').value = amount > 0 ? amount : '';
                selectOption("method", method, method === "เงินสด" ? "💸 เงินสด (Cash)" : (method === "โอนเงิน" ? "🏦 โอนเงิน (Transfer)" : (method === "QR" ? "📱 สแกน QR (PromptPay)" : "💳 บัตรเครดิต (Credit Card)")));
                document.getElementById('txNote').value = '';

            } else if (mode === 'edit' || mode === 'duplicate') {
                document.getElementById('modalTitle').innerText = mode === 'edit' ? 'แก้ไขรายการบัญชี' : 'คัดลอกรายการ (Duplicate)';
                document.getElementById('formAction').value = mode;
                document.getElementById('txId').value = id;
                
                if(type === 'income') document.getElementById('radioIncome').checked = true;
                else document.getElementById('radioExpense').checked = true;
                
                updateCategories(type, cat);
                document.getElementById('txAmount').value = amount;
                
                selectOption("method", method, method === "เงินสด" ? "💸 เงินสด (Cash)" : (method === "โอนเงิน" ? "🏦 โอนเงิน (Transfer)" : (method === "QR" ? "📱 สแกน QR (PromptPay)" : "💳 บัตรเครดิต (Credit Card)")));
                
                // แยก Guest กับ Note ออกจากกันถ้ามี (ซัพพอร์ตข้อมูลเก่า)
                let cleanNote = note;
                if(note.includes('ผู้จอง/ห้อง:')) {
                    let parts = note.split('|');
                    let guest = parts[0].replace('ผู้จอง/ห้อง:', '').trim();
                    cleanNote = (guest ? "ผู้จอง: " + guest + " " : "") + (parts[1] ? parts[1].trim() : '');
                }
                document.getElementById('txNote').value = cleanNote;

                if(mode === 'duplicate') {
                    setQuickDate(0);
                    setTimeNow();
                } else {
                    document.getElementById('txDate').value = dateStr;
                    document.getElementById('txTime').value = timeStr;
                }
            }
        }
        
        function closeModal() { modal.classList.add('hidden'); }

        // 🌟 Export Functions (Professional Hotel Report)
        function prepareExportTemplate() {
            const filter = document.getElementById('filterType').value;
            const rows = document.querySelectorAll('#exportTemplate .export-row');
            rows.forEach(r => {
                if (filter === 'all') r.style.display = '';
                else if (r.dataset.type === filter) r.style.display = '';
                else r.style.display = 'none';
            });
            const now = new Date();
            const dateStr = now.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
            const timeStr = now.toLocaleTimeString('th-TH');
            document.getElementById('exportTimestampText').innerText = dateStr + ' เวลา ' + timeStr;
            return document.getElementById("exportTemplate");
        }

        function getFilenameBase() {
            const today = new Date();
            return String(today.getDate()).padStart(2, '0') + '-' + String(today.getMonth()+1).padStart(2, '0') + '-' + (today.getFullYear() + 543);
        }

        async function capturePagesAsCanvases() {
            const el = prepareExportTemplate();
            el.style.display = 'block';
            
            const tbody = el.querySelector('tbody');
            const allVisibleRows = Array.from(tbody.querySelectorAll('tr.export-row')).filter(r => r.style.display !== 'none');
            
            const ROWS_PER_PAGE = 22; // กำหนด 22 บรรทัดต่อ 1 หน้า A4 จะไม่ตกขอบและไม่ทับซ้อน
            const canvases = [];
            
            // Add page number span
            let pageNumEl = document.getElementById('exportPageNum');
            if (!pageNumEl) {
                const tsEl = document.getElementById('exportTimestampText');
                if(tsEl) {
                    pageNumEl = document.createElement('span');
                    pageNumEl.id = 'exportPageNum';
                    pageNumEl.style.marginLeft = '10px';
                    pageNumEl.style.fontWeight = 'bold';
                    tsEl.parentNode.appendChild(pageNumEl);
                }
            }
            
            if (allVisibleRows.length === 0) {
                const canvas = await html2canvas(el, { backgroundColor: "#ffffff", scale: 2, useCORS: true });
                canvases.push(canvas);
            } else {
                // Remove all rows to chunk them
                allVisibleRows.forEach(r => r.remove());
                const totalPages = Math.ceil(allVisibleRows.length / ROWS_PER_PAGE);
                
                for (let i = 0; i < allVisibleRows.length; i += ROWS_PER_PAGE) {
                    const chunk = allVisibleRows.slice(i, i + ROWS_PER_PAGE);
                    chunk.forEach(r => tbody.appendChild(r)); // Append chunk
                    
                    if(pageNumEl) pageNumEl.innerText = `| หน้า ${Math.floor(i/ROWS_PER_PAGE)+1} / ${totalPages}`;
                    
                    const canvas = await html2canvas(el, { backgroundColor: "#ffffff", scale: 2, useCORS: true });
                    canvases.push(canvas);
                    
                    chunk.forEach(r => r.remove()); // Clear chunk
                }
                
                // Restore all rows back
                allVisibleRows.forEach(r => tbody.appendChild(r));
                if(pageNumEl) pageNumEl.innerText = '';
            }
            
            el.style.display = '';
            return canvases;
        }

        async function exportToPNG() {
            const btn = event.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = `<i class="ph-bold ph-spinner animate-spin text-lg"></i> โหลด...`;
            
            try {
                await document.fonts.ready;
                const canvases = await capturePagesAsCanvases();
                
                for (let i = 0; i < canvases.length; i++) {
                    const link = document.createElement("a");
                    link.download = getFilenameBase() + (canvases.length > 1 ? `_Page_${i+1}` : '') + ".png";
                    link.href = canvases[i].toDataURL("image/png");
                    link.click();
                    await new Promise(r => setTimeout(r, 300));
                }
            } catch (e) { 
                Swal.fire({ title: 'ข้อผิดพลาด', text: 'ไม่สามารถสร้างไฟล์รูปภาพได้', icon: 'error', confirmButtonColor: '#ff003c', customClass: { popup: 'border border-gray-800' }, background: '#1a1a1a', color: '#fff' }); 
            }
            
            btn.innerHTML = originalHTML;
        }

        async function exportToPDF() {
            const btn = event.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = `<i class="ph-bold ph-spinner animate-spin text-lg"></i> โหลด...`;
            
            try {
                await document.fonts.ready;
                const canvases = await capturePagesAsCanvases();
                
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF("p", "mm", "a4");
                const pdfWidth = 210;
                
                for (let i = 0; i < canvases.length; i++) {
                    if (i > 0) pdf.addPage();
                    const imgData = canvases[i].toDataURL("image/png");
                    const imgHeight = (canvases[i].height * pdfWidth) / canvases[i].width;
                    pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, imgHeight);
                }
                
                pdf.save(getFilenameBase() + ".pdf");
            } catch (e) { 
                Swal.fire({ title: 'ข้อผิดพลาด', text: 'ไม่สามารถสร้างไฟล์ PDF ได้', icon: 'error', confirmButtonColor: '#ff003c', customClass: { popup: 'border border-gray-800' }, background: '#1a1a1a', color: '#fff' }); 
            }
            
            btn.innerHTML = originalHTML;
        }

        function printReport() {
            const el = prepareExportTemplate();
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html><head><title>Print Report - Yuncha</title>
                <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;700;900&display=swap" rel="stylesheet">
                <style>
                    body { font-family: 'Prompt', sans-serif; background: #fff; color: #000; margin: 0; padding: 20px; }
                    @media print { 
                        body { padding: 0; } 
                        tr { page-break-inside: avoid; break-inside: avoid; } 
                    }
                </style>
                </head><body>
            `);
            el.style.display = 'block';
            el.style.position = 'static';
            el.style.width = '100%';
            printWindow.document.write(el.outerHTML);
            el.style.display = '';
            el.style.position = 'absolute';
            el.style.width = '210mm';
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); printWindow.close(); }, 1000);
        }


        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        function toggleSidebar() { sidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); }
        document.getElementById('open-sidebar').addEventListener('click', toggleSidebar);
        document.getElementById('close-sidebar').addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Idle Refresh
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
            if (idleTime >= 300) { // Changed from 60 to 300 (5 minutes)
                let isModalOpen = !document.getElementById('financeModal').classList.contains('hidden');
                if(!isModalOpen) location.reload();
            }
        }, 1000);

        gsap.fromTo(".gs-anim", { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.6, ease: "power2.out" });
        if(document.querySelector('.gs-anim-alert')) {
            gsap.fromTo(".gs-anim-alert", { x: 50, opacity: 0 }, { x: 0, opacity: 1, duration: 0.5, ease: "back.out(1.7)" });
        }
    </script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartDates = <?= json_encode($chart_dates) ?>;
    const chartIncomes = <?= json_encode($chart_incomes) ?>;
    const chartExpenses = <?= json_encode($chart_expenses) ?>;

    if(document.getElementById('barChart') && chartDates.length > 0) {
        const ctxBar = document.getElementById('barChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: chartDates.map(d => d + ' / ' + <?= date('m', strtotime($selected_month)) ?>),
                datasets: [
                    {
                        label: 'รายรับ (Income)',
                        data: chartIncomes,
                        backgroundColor: 'rgba(0, 255, 65, 0.8)',
                        borderColor: '#00ff41',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'รายจ่าย (Expense)',
                        data: chartExpenses,
                        backgroundColor: 'rgba(255, 0, 60, 0.8)',
                        borderColor: '#ff003c',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#a3a3a3', font: { family: 'Prompt' } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { grid: { color: '#222' }, ticks: { color: '#777' }, beginAtZero: true },
                    x: { grid: { display: false }, ticks: { color: '#777' } }
                }
            }
        });
    }

    const pieLabels = <?= json_encode($pie_labels) ?>;
    const pieData = <?= json_encode($pie_data) ?>;
    if(document.getElementById('pieChart')) {
        const ctxPie = document.getElementById('pieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: ['#ff003c', '#ff5e00', '#ffcc00', '#b537f2', '#00d0ff', '#ffffff'],
                    borderWidth: 2,
                    borderColor: '#0a0a0a',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'right', labels: { color: '#a3a3a3', font: { family: 'Prompt', size: 10 }, boxWidth: 12 } }
                }
            }
        });
    }
});
</script>
<!-- 🌟 Professional Report Template (Hidden) -->
<div id="exportTemplate" style="position: absolute; left: -9999px; top: 0; width: 210mm; background: #ffffff; padding: 20px; color: #000000; font-family: 'Prompt', sans-serif; z-index: -1; box-sizing: border-box;">
    <div style="border-bottom: 2px solid #000000; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="font-size: 28px; font-weight: 900; color: #000000; margin: 0;">YUNCHA VALLEY</h1>
            <p style="font-size: 14px; color: #000000; letter-spacing: 2px; text-transform: uppercase; margin: 0;">Resort & Retreat</p>
        </div>
        <div style="text-align: right;">
            <h2 style="font-size: 20px; font-weight: 700; color: #000000; margin: 0;">รายงานสรุปบัญชี</h2>
            <p style="font-size: 14px; color: #000000; margin: 0;">
                <?= $view_mode=='daily' ? 'ประจำวันที่: ' . date('d/m/Y', strtotime($selected_date)) : 'ประจำเดือน: ' . date('M Y', strtotime($selected_month)) ?>
            </p>
        </div>
    </div>

    <!-- KPIs -->
    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
        <div style="flex: 1; background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #000000;">
            <p style="font-size: 12px; color: #000000; font-weight: bold; margin: 0 0 5px 0; text-transform: uppercase;">รายรับรวม</p>
            <h3 style="font-size: 22px; font-weight: 900; color: #000000; margin: 0;">฿<?= number_format($display_income, 2) ?></h3>
        </div>
        <div style="flex: 1; background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #000000;">
            <p style="font-size: 12px; color: #000000; font-weight: bold; margin: 0 0 5px 0; text-transform: uppercase;">รายจ่ายรวม</p>
            <h3 style="font-size: 22px; font-weight: 900; color: #000000; margin: 0;">฿<?= number_format($display_expense, 2) ?></h3>
        </div>
        <div style="flex: 1; background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #000000;">
            <p style="font-size: 12px; color: #000000; font-weight: bold; margin: 0 0 5px 0; text-transform: uppercase;">กำไรสุทธิ</p>
            <h3 style="font-size: 22px; font-weight: 900; color: #000000; margin: 0;">฿<?= number_format($display_profit, 2) ?></h3>
        </div>
    </div>

    <!-- Table -->
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #ffffff; color: #000000;">
                <th style="padding: 10px; border-bottom: 2px solid #000000; border-top: 2px solid #000000; text-align: left; font-weight: bold;">วันที่/เวลา</th>
                <th style="padding: 10px; border-bottom: 2px solid #000000; border-top: 2px solid #000000; text-align: left; font-weight: bold;">หมวดหมู่</th>
                <th style="padding: 10px; border-bottom: 2px solid #000000; border-top: 2px solid #000000; text-align: left; font-weight: bold; width: 30%;">รายละเอียด</th>
                <th style="padding: 10px; border-bottom: 2px solid #000000; border-top: 2px solid #000000; text-align: center; font-weight: bold;">ช่องทาง</th>
                <th style="padding: 10px; border-bottom: 2px solid #000000; border-top: 2px solid #000000; text-align: right; font-weight: bold;">รายรับ (฿)</th>
                <th style="padding: 10px; border-bottom: 2px solid #000000; border-top: 2px solid #000000; text-align: right; font-weight: bold;">รายจ่าย (฿)</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($transactions)): ?>
            <tr><td colspan="6" style="text-align: center; padding: 20px; color: #000000; font-weight: bold; border-bottom: 1px solid #000000;">ไม่มีรายการบัญชี</td></tr>
            <?php else: foreach($transactions as $t): 
                $is_in = ($t['transaction_type'] == 'income');
                $inc_text = $is_in ? '+'.number_format($t['amount'], 2) : '-';
                $exp_text = !$is_in ? '-'.number_format($t['amount'], 2) : '-';
            ?>
            <tr class="export-row" data-type="<?= $t['transaction_type'] ?>">
                <td style="padding: 10px; border-bottom: 1px solid #000000;">
                    <span style="font-weight: bold; color: #000000;"><?= date('d/m/Y', strtotime($t['transaction_date'])) ?></span><br>
                    <span style="font-size: 11px; color: #000000;"><?= date('H:i', strtotime($t['transaction_time'])) ?></span>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid #000000; font-weight: bold; color: #000000;"><?= htmlspecialchars($t['category']) ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #000000; color: #000000; max-width: 250px; word-wrap: break-word;">
                    <?= htmlspecialchars($t['notes'] ?: '-') ?>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid #000000; text-align: center; color: #000000; font-weight: bold;">
                    <?= htmlspecialchars($t['payment_method']) ?>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid #000000; text-align: right; font-weight: 900; color: #000000;">
                    <?= $inc_text ?>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid #000000; text-align: right; font-weight: 900; color: #000000;">
                    <?= $exp_text ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 20px;">
        เอกสารนี้สร้างโดยระบบอัตโนมัติ <?= htmlspecialchars($hotel_name) ?><br>
        วันที่พิมพ์: <span id="exportTimestampText"></span>
    </div>
</div>
<script>
// [CUSTOM SELECT INJECTION]
    // 🌟 Polyfill for select.value to auto-trigger change event
    if(!window.customSelectPolyfilled) {
        window.customSelectPolyfilled = true;
        const origVal = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        Object.defineProperty(HTMLSelectElement.prototype, 'value', {
            get() { return origVal.get.call(this); },
            set(val) {
                origVal.set.call(this, val);
                this.dispatchEvent(new Event('change'));
            }
        });
        const origSelectedIndex = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
        Object.defineProperty(HTMLSelectElement.prototype, 'selectedIndex', {
            get() { return origSelectedIndex.get.call(this); },
            set(val) {
                origSelectedIndex.set.call(this, val);
                this.dispatchEvent(new Event('change'));
            }
        });
    }

    function initCustomSelects() {
        document.querySelectorAll('select:not([multiple])').forEach(select => {
            if(select.dataset.customized === "true") return;
            if(select.nextElementSibling && select.nextElementSibling.classList.contains('custom-select-ui')) return;
            
            // Only apply to styled selects
            if(!select.className.includes('input-dark') && !select.className.includes('bg-')) return;
            
            select.dataset.customized = "true";
            select.style.display = 'none';
            
            if(select.nextElementSibling && select.nextElementSibling.tagName.toLowerCase() === 'i') {
                select.nextElementSibling.style.display = 'none';
            }
            
            const wrapper = document.createElement('div');
            wrapper.className = 'relative w-full custom-select-ui';
            
            const btn = document.createElement('div');
            btn.className = select.className.replace('appearance-none', '') + ' flex justify-between items-center cursor-pointer';
            btn.style.cssText = select.style.cssText;
            btn.style.display = 'flex'; // override none
            btn.className = btn.className.replace(/rounded-[a-zA-Z0-9]+/g, '') + ' !rounded-2xl';
            
            const text = document.createElement('span');
            text.className = 'flex-1 truncate pr-2';
            text.innerText = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : 'เลือกข้อมูล';
            
            const icon = document.createElement('i');
            icon.className = 'ph-bold ph-caret-down text-gray-400 transition-transform duration-300 shrink-0';
            
            btn.appendChild(text);
            btn.appendChild(icon);
            wrapper.appendChild(btn);
            
            const list = document.createElement('div');
            list.className = 'absolute z-[9999] w-full mt-2 bg-[#151515] border border-[#333] rounded-2xl overflow-hidden shadow-2xl hidden flex-col max-h-60 overflow-y-auto';
            
            const renderOptions = () => {
                list.innerHTML = '';
                Array.from(select.options).forEach((opt, idx) => {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-3 hover:bg-gray-800 cursor-pointer transition text-sm text-white font-medium border-b border-gray-800 last:border-0 hover:text-[var(--neon-color)]';
                    item.style.setProperty('--neon-color', select.style.getPropertyValue('--neon-color') || '#ffcc00');
                    item.innerText = opt.text;
                    item.onclick = (e) => {
                        select.selectedIndex = idx; // this will now auto-trigger change due to polyfill
                        text.innerText = opt.text;
                        list.classList.add('hidden');
                        icon.classList.remove('rotate-180');
                        e.stopPropagation();
                    };
                    list.appendChild(item);
                });
            };
            
            renderOptions();
            
            wrapper.appendChild(list);
            select.parentNode.insertBefore(wrapper, select.nextSibling);
            
            btn.onclick = (e) => {
                document.querySelectorAll('.custom-select-ui > div:nth-child(2)').forEach(l => {
                    if(l !== list) {
                        l.classList.add('hidden');
                        l.previousElementSibling.querySelector('i').classList.remove('rotate-180');
                    }
                });
                list.classList.toggle('hidden');
                if(list.classList.contains('hidden')) {
                    icon.classList.remove('rotate-180');
                } else {
                    icon.classList.add('rotate-180');
                }
                e.stopPropagation();
            };
            
            select.addEventListener('change', () => {
                if(select.selectedIndex >= 0) {
                    text.innerText = select.options[select.selectedIndex].text;
                }
            });
            
            const observer = new MutationObserver(() => {
                renderOptions();
                if(select.selectedIndex >= 0) {
                    text.innerText = select.options[select.selectedIndex].text;
                }
            });
            observer.observe(select, { childList: true });
        });
        
        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select-ui > div:nth-child(2)').forEach(l => l.classList.add('hidden'));
            document.querySelectorAll('.custom-select-ui > div:nth-child(1) i').forEach(i => i.classList.remove('rotate-180'));
        });
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        initCustomSelects();
    });
    
    const bodyObserver = new MutationObserver((mutations) => {
        let shouldInit = false;
        mutations.forEach(m => {
            if(m.addedNodes.length > 0) {
                m.addedNodes.forEach(node => {
                    if(node.nodeType === 1) {
                        if(node.tagName === 'SELECT' || node.querySelector('select')) shouldInit = true;
                    }
                });
            }
        });
        if(shouldInit) setTimeout(initCustomSelects, 50);
    });
    if(document.body) bodyObserver.observe(document.body, { childList: true, subtree: true });
    // [/CUSTOM SELECT INJECTION]
</script>
</body>
</html>
