<?php
require_once 'config/security.php';
if (!$is_account) { $has_access = false; }

require_once 'config/db.php';

// ==========================================
// ðŸ“Š เชื่อมต่อข้อมูลจริง (Real-Time Analytics)
// ==========================================
// ðŸŒŸ รับค่าเดือนที่ต้องการดู (ถ้าไม่เลือก จะเป็นเดือนปัจจุบัน)
$selected_month = isset($_GET['month']) ? $_GET['month'] :  date('Y-m');
$current_year = date('Y', strtotime($selected_month));
$current_month = date('m', strtotime($selected_month));
$days_in_month = date('t', strtotime($selected_month));

// 1. ดึงจำนวนห้องทั้งหมด
$res_rooms = $conn->query("SELECT COUNT(id) as c FROM rooms WHERE status != 'deleted'");
$row_rooms = $res_rooms->fetch(PDO::FETCH_ASSOC);
$total_rooms = isset($row_rooms['c']) ? $row_rooms['c'] : 0;
$total_available_nights = $total_rooms * $days_in_month;

// 2. คำนวณรายได้และห้องที่ขายได้ในเดือนที่เลือก
$sql_month_stats = "SELECT 
    SUM(total_price) as rev, 
    SUM((julianday(check_out) - julianday(check_in))) as sold_nights,
    COUNT(id) as total_bookings
    FROM bookings 
    WHERE strftime('%m', check_in) = '$current_month' AND strftime('%Y', check_in) = '$current_year' AND status NOT IN ('cancelled', 'pending')";
$res_month = $conn->query($sql_month_stats);
$m_stats = $res_month->fetch(PDO::FETCH_ASSOC);

$monthly_rev = isset($m_stats['rev']) ? $m_stats['rev'] :  0;
$sold_nights = isset($m_stats['sold_nights']) ? $m_stats['sold_nights'] :  0;
$total_bookings = isset($m_stats['total_bookings']) ? $m_stats['total_bookings'] :  0;

// 3. คำนวณ KPI หลัก
$occ_rate = $total_available_nights > 0 ? ($sold_nights / $total_available_nights) * 100 : 0;
$adr = $sold_nights > 0 ? ($monthly_rev / $sold_nights) : 0;
$revpar = $total_available_nights > 0 ? ($monthly_rev / $total_available_nights) : 0;

// 4. คำนวณอัตรายกเลิกของเดือนที่เลือก
$res_cancel = $conn->query("SELECT COUNT(id) as c FROM bookings WHERE strftime('%m', check_in) = '$current_month' AND strftime('%Y', check_in) = '$current_year' AND status = 'cancelled'");
$row_cancel = $res_cancel->fetch(PDO::FETCH_ASSOC);
$cancel_count = isset($row_cancel['c']) ? $row_cancel['c'] : 0;
$all_bookings_incl_cancel = $total_bookings + $cancel_count;
$cancel_rate = $all_bookings_incl_cancel > 0 ? ($cancel_count / $all_bookings_incl_cancel) * 100 : 0;

// 5. ข้อมูลกราฟช่องทางการจอง (Booking Source) ของเดือนที่เลือก
$sql_source = "SELECT c.auth_provider, COUNT(b.id) as cnt 
               FROM bookings b JOIN customers c ON b.customer_id = c.id 
               WHERE strftime('%m', b.check_in) = '$current_month' AND strftime('%Y', b.check_in) = '$current_year'
               GROUP BY c.auth_provider";
$res_source = $conn->query($sql_source);
$source_labels = []; $source_data = [];
if($res_source) {
    while($s = $res_source->fetch(PDO::FETCH_ASSOC)) {
        $lbl = strtolower($s['auth_provider']) == 'local' ? 'Direct (Website)' : ucfirst($s['auth_provider']);
        $source_labels[] = "'" . addslashes($lbl) . "'";
        $source_data[] = $s['cnt'];
    }
}

// 6. ข้อมูลกราฟกลุ่มลูกค้า (New vs Returning) ของเดือนที่เลือก
$sql_cust = "SELECT c.is_returning, COUNT(b.id) as cnt 
             FROM bookings b JOIN customers c ON b.customer_id = c.id 
             WHERE strftime('%m', b.check_in) = '$current_month' AND strftime('%Y', b.check_in) = '$current_year'
             GROUP BY c.is_returning";
$res_cust = $conn->query($sql_cust);
$cust_data = [0, 0]; // [New, Returning]
if($res_cust) {
    while($c = $res_cust->fetch(PDO::FETCH_ASSOC)) {
        if($c['is_returning'] == 1) $cust_data[1] = $c['cnt'];
        else $cust_data[0] = $c['cnt'];
    }
}

// 7. ตารางเจาะลึกประสิทธิภาพห้องพัก (Room Drill-down) ของเดือนที่เลือก
$sql_drilldown = "SELECT 
    t.type_name, 
    COUNT(DISTINCT r.id) as total_room_count,
    IFNULL(SUM((julianday(b.check_out) - julianday(b.check_in))), 0) as sold_nights, 
    IFNULL(SUM(b.total_price), 0) as rev 
    FROM room_types t 
    LEFT JOIN rooms r ON t.id = r.room_type_id AND r.status != 'deleted'
    LEFT JOIN bookings b ON r.id = b.room_id AND b.status NOT IN ('cancelled', 'pending') 
        AND strftime('%m', b.check_in) = '$current_month' AND strftime('%Y', b.check_in) = '$current_year'
    GROUP BY t.id 
    ORDER BY rev DESC";
$res_drilldown = $conn->query($sql_drilldown);
$drilldown_data = [];
$rank = 1;
if($res_drilldown) {
    while($row = $res_drilldown->fetch(PDO::FETCH_ASSOC)) {
        $max_nights = $row['total_room_count'] * $days_in_month;
        $row['unsold_nights'] = max(0, $max_nights - $row['sold_nights']);
        $row['rank'] = $rank++;
        $drilldown_data[] = $row;
    }
}

require_once 'config/db.php';

// ==========================================
// 📊 เชื่อมต่อข้อมูลจริง (Real-Time Analytics)
// ==========================================
// 🌟 รับค่าเดือนที่ต้องการดู (ถ้าไม่เลือก จะเป็นเดือนปัจจุบัน)
$selected_month = isset($_GET['month']) ? $_GET['month'] :  date('Y-m');
$current_year = date('Y', strtotime($selected_month));
$current_month = date('m', strtotime($selected_month));
$days_in_month = date('t', strtotime($selected_month));

// 1. ดึงจำนวนห้องทั้งหมด
$res_rooms = $conn->query("SELECT COUNT(id) as c FROM rooms WHERE status != 'deleted'");
$row_rooms = $res_rooms->fetch(PDO::FETCH_ASSOC);
$total_rooms = isset($row_rooms['c']) ? $row_rooms['c'] : 0;
$total_available_nights = $total_rooms * $days_in_month;

// 2. คำนวณรายได้และห้องที่ขายได้ในเดือนที่เลือก
$sql_month_stats = "SELECT 
    SUM(total_price) as rev, 
    SUM((julianday(check_out) - julianday(check_in))) as sold_nights,
    COUNT(id) as total_bookings
    FROM bookings 
    WHERE strftime('%m', check_in) = '$current_month' AND strftime('%Y', check_in) = '$current_year' AND status NOT IN ('cancelled', 'pending')";
$res_month = $conn->query($sql_month_stats);
$m_stats = $res_month->fetch(PDO::FETCH_ASSOC);

$monthly_rev = isset($m_stats['rev']) ? $m_stats['rev'] :  0;
$sold_nights = isset($m_stats['sold_nights']) ? $m_stats['sold_nights'] :  0;
$total_bookings = isset($m_stats['total_bookings']) ? $m_stats['total_bookings'] :  0;

// 3. คำนวณ KPI หลัก
$occ_rate = $total_available_nights > 0 ? ($sold_nights / $total_available_nights) * 100 : 0;
$adr = $sold_nights > 0 ? ($monthly_rev / $sold_nights) : 0;
$revpar = $total_available_nights > 0 ? ($monthly_rev / $total_available_nights) : 0;

// 4. คำนวณอัตรายกเลิกของเดือนที่เลือก
$res_cancel = $conn->query("SELECT COUNT(id) as c FROM bookings WHERE strftime('%m', check_in) = '$current_month' AND strftime('%Y', check_in) = '$current_year' AND status = 'cancelled'");
$row_cancel = $res_cancel->fetch(PDO::FETCH_ASSOC);
$cancel_count = isset($row_cancel['c']) ? $row_cancel['c'] : 0;
$all_bookings_incl_cancel = $total_bookings + $cancel_count;
$cancel_rate = $all_bookings_incl_cancel > 0 ? ($cancel_count / $all_bookings_incl_cancel) * 100 : 0;

// 5. ข้อมูลกราฟช่องทางการจอง (Booking Source) ของเดือนที่เลือก
$sql_source = "SELECT 
                 CASE 
                   WHEN b.booking_ref LIKE 'WK%' THEN 'Walk-in'
                   ELSE c.auth_provider 
                 END as booking_source, 
                 COUNT(b.id) as cnt 
               FROM bookings b 
               JOIN customers c ON b.customer_id = c.id 
               WHERE strftime('%m', b.check_in) = '$current_month' AND strftime('%Y', b.check_in) = '$current_year'
               GROUP BY booking_source";
$res_source = $conn->query($sql_source);
$grouped_source = [];
if($res_source) {
    while($s = $res_source->fetch(PDO::FETCH_ASSOC)) {
        $src = $s['booking_source'];
        if (strtolower($src) == 'local' || empty($src)) {
            $lbl = 'Direct (Website)';
        } elseif ($src == 'Walk-in') {
            $lbl = 'Walk-in (หน้าโรงแรม)';
        } else {
            $lbl = ucfirst($src);
        }
        
        if (!isset($grouped_source[$lbl])) {
            $grouped_source[$lbl] = 0;
        }
        $grouped_source[$lbl] += $s['cnt'];
    }
}
$source_labels = []; $source_data = [];
foreach($grouped_source as $lbl => $cnt) {
    $source_labels[] = "'" . addslashes($lbl) . "'";
    $source_data[] = $cnt;
}

// 6. ข้อมูลกราฟกลุ่มลูกค้า (New vs Returning) ของเดือนที่เลือก
$sql_cust = "SELECT c.is_returning, COUNT(b.id) as cnt 
             FROM bookings b JOIN customers c ON b.customer_id = c.id 
             WHERE strftime('%m', b.check_in) = '$current_month' AND strftime('%Y', b.check_in) = '$current_year'
             GROUP BY c.is_returning";
$res_cust = $conn->query($sql_cust);
$cust_data = [0, 0]; // [New, Returning]
if($res_cust) {
    while($c = $res_cust->fetch(PDO::FETCH_ASSOC)) {
        if($c['is_returning'] == 1) $cust_data[1] = $c['cnt'];
        else $cust_data[0] = $c['cnt'];
    }
}

// 7. ตารางเจาะลึกประสิทธิภาพห้องพัก (Room Drill-down) ของเดือนที่เลือก
$sql_drilldown = "SELECT 
    t.type_name, 
    COUNT(DISTINCT r.id) as total_room_count,
    IFNULL(SUM((julianday(b.check_out) - julianday(b.check_in))), 0) as sold_nights, 
    IFNULL(SUM(b.total_price), 0) as rev 
    FROM room_types t 
    LEFT JOIN rooms r ON t.id = r.room_type_id AND r.status != 'deleted'
    LEFT JOIN bookings b ON r.id = b.room_id AND b.status NOT IN ('cancelled', 'pending') 
        AND strftime('%m', b.check_in) = '$current_month' AND strftime('%Y', b.check_in) = '$current_year'
    GROUP BY t.id 
    ORDER BY rev DESC";
$res_drilldown = $conn->query($sql_drilldown);
$drilldown_data = [];
$rank = 1;
if($res_drilldown) {
    while($row = $res_drilldown->fetch(PDO::FETCH_ASSOC)) {
        $max_nights = $row['total_room_count'] * $days_in_month;
        $row['unsold_nights'] = max(0, $max_nights - $row['sold_nights']);
        $row['rank'] = $rank++;
        $drilldown_data[] = $row;
    }
}

// 8. สร้างข้อมูล Trend ล่าสุด 6 เดือน นับจากเดือนที่เลือก (ผสมข้อมูลจริงเดือนนี้กับ Mock ข้อมูลย้อนหลัง)
$trend_labels = []; $trend_rev = []; $trend_adr = [];
for($i=5; $i>=0; $i--) {
    // หาย้อนหลังจากเดือนที่เลือก
    $target_date = date('Y-m-d', strtotime($selected_month . "-01 -$i months"));
    $trend_labels[] = "'" . date('M', strtotime($target_date)) . "'";
    if($i == 0) {
        $trend_rev[] = $revpar;
        $trend_adr[] = $adr;
    } else {
        $trend_rev[] = 0;
        $trend_adr[] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานวิเคราะห์ (Reports) | Yuncha Valley</title>
    
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <style>
        body { background-color: #000000; color: #ffffff; font-family: 'Prompt', sans-serif; overflow-x: hidden; position: relative; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #000000; }
        ::-webkit-scrollbar-thumb { background: #b537f2; border-radius: 10px; }

        /* 🌟 เอฟเฟกต์หิมะ/ดาวตก แบบหน้า Dashboard */
        .stars-container { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .star { position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0; animation: fall linear infinite; box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.8); }
        @keyframes fall {
            0% { transform: translateY(-10vh) translateX(0) scale(1); opacity: 1; }
            100% { transform: translateY(110vh) translateX(-20vw) scale(0); opacity: 0; }
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

        .input-dark { background-color: #050505; border: 1px solid #333; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; outline: none; transition: all 0.3s; appearance: textfield; font-size: 0.875rem;}
        .input-dark:focus { border-color: var(--neon-color); box-shadow: 0 0 10px rgba(181, 55, 242, 0.2); }
        
        /* เปลี่ยนไอคอนปฏิทินให้เป็นสีขาว */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="month"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
        
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { appearance: none; margin: 0; }
        input[type=number] { appearance: textfield; }

        .chart-container { position: relative; width: 100%; height: 260px; }
        .kpi-text { font-size: 2.2rem; line-height: 1.2; font-weight: 900; }
        
        .ai-box { background: linear-gradient(145deg, rgba(181, 55, 242, 0.1) 0%, rgba(0,0,0,0) 100%); border: 1px solid rgba(181, 55, 242, 0.3); }

        /* Print styling for PDF export */
        @media print {
            body { background: white !important; color: black !important; }
            .neon-pro { background: white !important; border: 1px solid #ccc !important; box-shadow: none !important; color: black !important; break-inside: avoid; margin-bottom: 20px;}
            .neon-pro::before, .neon-pro-glow, .stars-container, header button, #sidebar, #mobile-overlay { display: none !important; }
            .text-white { color: black !important; }
            .text-gray-400, .text-gray-500, .text-gray-300 { color: #555 !important; }
            .bg-\[\#0a0a0a\], .bg-\[\#050505\], .bg-\[\#0f0f0f\], .bg-\[\#111\] { background: transparent !important; }
            canvas { filter: invert(1) hue-rotate(180deg); }
            .kpi-text { color: black !important; text-shadow: none !important; }
            table { border-collapse: collapse !important; }
            th, td { border: 1px solid #ddd !important; color: black !important; }
        }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important;}</style>
</head>
<body class="h-screen flex selection:bg-ycPurple selection:text-black">

    <!-- 🌟 คอนเทนเนอร์หิมะ/ดาวตก -->
    <div class="stars-container" id="starsBox"></div>

    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <!-- 🌟 Sidebar อัจฉริยะ ซิงค์เหมือน Dashboard -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden w-full relative z-10 bg-transparent">
        
        <!-- Header พร้อม Filter -->
        <header class="h-auto md:h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 py-4 flex flex-col md:flex-row justify-between items-start md:items-center z-30 sticky top-0 gap-4">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg text-ycPurple border border-gray-800"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-bold text-white flex items-center gap-2"><i class="ph-fill ph-chart-polar text-ycPurple drop-shadow-[0_0_10px_#b537f2]"></i> รายงานวิเคราะห์ (Analytics)</h2>
                    <p class="text-sm text-gray-400 font-medium">ดูแนวโน้ม เปรียบเทียบข้อมูล และคาดการณ์ยอดจองรายเดือน</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-2">เวลาปัจจุบัน: <span id="clockStatus" style="color: #b537f2;"><?= date('H:i:s') ?></span></p>
                
                <!-- 🌟 กล่องเลือกเดือน (อัปเดตอัตโนมัติเมื่อเปลี่ยน) -->
                <div class="flex items-center bg-[#0a0a0a] border border-gray-700 rounded-lg overflow-hidden focus-within:border-ycPurple transition-all">
                    <span class="text-xs text-gray-500 pl-3"><i class="ph-bold ph-calendar"></i> เลือกรอบเดือน:</span>
                    <input type="month" class="bg-transparent border-none text-white text-xs px-2 py-2 outline-none font-bold cursor-pointer" style="--neon-color: #b537f2;" value="<?= $selected_month ?>" onchange="window.location.href='reports.php?month=' + this.value">
                </div>
                
                <button onclick="exportData()" class="neon-pro px-4 py-2 rounded-lg flex items-center gap-2" style="--neon-color: #00d0ff;">
                    <div class="neon-pro-glow"></div>
                    <i class="ph-bold ph-export text-white relative z-10 icon-glow"></i><span class="text-xs font-bold text-white relative z-10">Export PDF</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
        <?php if ($has_access): ?>

            <div class="max-w-[1500px] mx-auto space-y-6 pb-12 gs-anim">
                
                <!-- 🤖 AI FORECAST BOX -->
                <div class="ai-box rounded-2xl p-5 mb-6 flex flex-col md:flex-row items-center justify-between gap-4 shadow-lg">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-ycPurple/20 rounded-full flex items-center justify-center text-ycPurple shadow-[0_0_15px_rgba(181,55,242,0.4)]">
                            <i class="ph-fill ph-robot text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-white text-lg">Yuncha AI Insights</h4>
                            <p class="text-sm text-gray-300">ประสิทธิภาพเดือน <?= date('M Y', strtotime($selected_month)) ?>: <span class="text-ycGreen font-bold">ยอดเข้าพัก <?= number_format($occ_rate, 1) ?>%</span> คำนวณจากข้อมูลจริง แนะนำให้ออกโปรโมชันเพิ่มในช่องทาง Direct Website</p>
                        </div>
                    </div>
                </div>

                <!-- 📊 MAIN KPIs (Real Data) อัปเกรดเป็นไฟนีออน -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="neon-pro p-5 flex flex-col justify-between" style="--neon-color: #00ff41;">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">อัตราเข้าพักเดือนนี้</p>
                        <h3 class="kpi-text text-white drop-shadow-[0_0_8px_#00ff41]"><?= number_format($occ_rate, 1) ?>%</h3>
                        <p class="text-xs font-bold text-ycGreen mt-2">คำนวณจากห้องขายได้ / ห้องทั้งหมด</p>
                    </div>
                    
                    <div class="neon-pro p-5 flex flex-col justify-between" style="--neon-color: #00d0ff;">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1" title="รายได้เฉลี่ยต่อห้องที่มีทั้งหมด">RevPAR</p>
                        <h3 class="kpi-text text-white drop-shadow-[0_0_8px_#00d0ff]"><span class="text-lg">฿</span><?= number_format($revpar) ?></h3>
                        <p class="text-xs font-bold text-ycBlue mt-2">รายได้ต่อจำนวนห้องพักทั้งหมด</p>
                    </div>

                    <div class="neon-pro p-5 flex flex-col justify-between" style="--neon-color: #ffcc00;">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1" title="ราคาขายเฉลี่ยรายวัน">ADR</p>
                        <h3 class="kpi-text text-white drop-shadow-[0_0_8px_#ffcc00]"><span class="text-lg">฿</span><?= number_format($adr) ?></h3>
                        <p class="text-xs font-bold text-ycGold mt-2">รายได้เฉลี่ยต่อห้องที่ขายได้</p>
                    </div>

                    <div class="neon-pro p-5 flex flex-col justify-between" style="--neon-color: #ff003c;">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">อัตรายกเลิก (Cancellation)</p>
                        <h3 class="kpi-text text-ycRed drop-shadow-[0_0_8px_#ff003c]"><?= number_format($cancel_rate, 1) ?>%</h3>
                        <p class="text-xs font-bold text-ycRed mt-2">ยกเลิกไป <?= $cancel_count ?> รายการ</p>
                    </div>
                </div>

                <!-- 📈 กราฟแถวที่ 1 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="neon-pro p-6 border border-gray-800" style="--neon-color: #00d0ff;">
                        <h3 class="text-lg font-bold text-white mb-2"><i class="ph-fill ph-chart-line-up text-ycBlue mr-2"></i> ความสัมพันธ์รายได้ (RevPAR & ADR Trend)</h3>
                        <div class="chart-container"><canvas id="revTrendChart"></canvas></div>
                    </div>
                    
                    <div class="neon-pro p-6 border border-gray-800" style="--neon-color: #ff00ff;">
                        <h3 class="text-lg font-bold text-white mb-2"><i class="ph-fill ph-funnel text-ycPink mr-2"></i> ช่องทางการจอง (Booking Source)</h3>
                        <?php if(empty($source_data)): ?>
                            <div class="h-full flex items-center justify-center text-gray-500 font-bold">ยังไม่มีข้อมูลในเดือนที่เลือก</div>
                        <?php else: ?>
                            <div class="chart-container flex justify-center"><canvas id="sourceChart"></canvas></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 📈 กราฟแถวที่ 2 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <div class="neon-pro p-6 border border-gray-800" style="--neon-color: #ffcc00;">
                        <h3 class="text-lg font-bold text-white mb-2"><i class="ph-fill ph-star text-ycGold mr-2"></i> คะแนนรีวิวความพึงพอใจ (Customer Reviews)</h3>
                        <p class="text-[10px] text-gray-500 mb-2">ข้อมูลจำลอง (รอการเชื่อมต่อระบบรีวิวในอนาคต)</p>
                        <div class="chart-container"><canvas id="reviewChart"></canvas></div>
                    </div>
                    
                    <div class="neon-pro p-6 border border-gray-800" style="--neon-color: #ff5e00;">
                        <h3 class="text-lg font-bold text-white mb-2"><i class="ph-fill ph-users text-ycOrange mr-2"></i> สัดส่วนกลุ่มลูกค้า (New vs Returning)</h3>
                        <?php if(empty($cust_data[0]) && empty($cust_data[1])): ?>
                            <div class="h-full flex items-center justify-center text-gray-500 font-bold">ยังไม่มีข้อมูลในเดือนที่เลือก</div>
                        <?php else: ?>
                            <div class="chart-container flex justify-center"><canvas id="custTypeChart"></canvas></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 📋 ตารางเจาะลึกห้องพัก (Real Data) -->
                <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl overflow-hidden shadow-2xl mt-6 relative z-10">
                    <div class="p-5 border-b border-gray-800 flex justify-between items-center bg-[#050505]">
                        <h3 class="text-lg font-bold text-white"><i class="ph-fill ph-door text-gray-400 mr-2"></i> รายงานเชิงลึก: ประสิทธิภาพรายห้องพักเดือนนี้ (Room Drill-down)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400 min-w-[800px]">
                            <thead class="text-xs uppercase bg-[#0a0a0a] text-gray-500 font-bold border-b border-gray-800">
                                <tr>
                                    <th class="px-6 py-4">ห้องพัก</th>
                                    <th class="px-6 py-4 text-center">คืนที่ขายได้ (Sold)</th>
                                    <th class="px-6 py-4 text-center">คืนที่ว่าง (Unsold)</th>
                                    <th class="px-6 py-4 text-right">รายได้รวม (Rev.)</th>
                                    <th class="px-6 py-4 text-center">ยอดฮิต (Rank)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800">
                                <?php if(empty($drilldown_data)): ?>
                                    <tr><td colspan="5" class="text-center py-6">ยังไม่มีข้อมูลในเดือนนี้</td></tr>
                                <?php else: ?>
                                    <?php foreach($drilldown_data as $dr): 
                                        $badge = '<span class="bg-gray-800 text-gray-300 px-2 py-1 rounded text-xs">#'.$dr['rank'].'</span>';
                                        if($dr['rank'] == 1) $badge = '<span class="bg-yellow-900/30 text-ycGold border border-ycGold/50 px-2 py-1 rounded text-xs">#1 Top</span>';
                                    ?>
                                    <tr class="hover:bg-[#151515] transition-colors bg-[#050505]">
                                        <td class="px-6 py-4 font-bold text-white"><?= htmlspecialchars($dr['type_name']) ?></td>
                                        <td class="px-6 py-4 text-center text-ycGreen font-bold"><?= $dr['sold_nights'] ?> คืน</td>
                                        <td class="px-6 py-4 text-center text-gray-500"><?= $dr['unsold_nights'] ?> คืน</td>
                                        <td class="px-6 py-4 text-right text-ycGold font-bold">฿ <?= number_format($dr['rev']) ?></td>
                                        <td class="px-6 py-4 text-center"><?= $badge ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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

    <script>
        // 🌟 จำตำแหน่ง Scroll ของเมนูด้านซ้าย
        document.addEventListener("DOMContentLoaded", function(event) { 
            var scrollpos = sessionStorage.getItem('sidebarScrollPos');
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if (scrollpos && sidebarEl) {
                sidebarEl.scrollTop = scrollpos;
            }
        });
        window.onbeforeunload = function(e) {
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if(sidebarEl) {
                sessionStorage.setItem('sidebarScrollPos', sidebarEl.scrollTop);
            }
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

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        function toggleSidebar() { sidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); }
        document.getElementById('open-sidebar').addEventListener('click', toggleSidebar);
        document.getElementById('close-sidebar').addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        function exportData() {
            window.print();
        }

        // 🌟 Idle Check System 
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
                location.reload();
            }
        }, 1000);

        // ðŸŒŸ Chart.js Configurations
        Chart.defaults.color = '#9ca3af';
        Chart.defaults.font.family = 'Prompt';

        window.onload = function() {
            gsap.fromTo(".gs-anim", { y: 30, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: "power3.out" });

            // 1. Revenue & ADR Trend (Line)
            const ctxRev = document.getElementById('revTrendChart');
            if(ctxRev) {
                new Chart(ctxRev.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [<?= implode(',', $trend_labels) ?>],
                        datasets: [
                            { label: 'RevPAR (รายได้ต่อห้องว่าง)', data: [<?= implode(',', $trend_rev) ?>], borderColor: '#00d0ff', backgroundColor: 'rgba(0, 208, 255, 0.1)', borderWidth: 3, fill: true, tension: 0.4 },
                            { label: 'ADR (ราคาขายเฉลี่ย)', data: [<?= implode(',', $trend_adr) ?>], borderColor: '#00ff41', borderDash: [5, 5], borderWidth: 2, fill: false, tension: 0.4 }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { grid: { color: '#222' } }, x: { grid: { display: false } } }, plugins: { legend: { position: 'bottom' } } }
                });
            }

            // 2. Booking Source (Doughnut)
            const ctxSource = document.getElementById('sourceChart');
            if(ctxSource && <?= count($source_labels) ?> > 0) {
                new Chart(ctxSource.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: [<?= implode(',', $source_labels) ?>],
                        datasets: [{ data: [<?= implode(',', $source_data) ?>], backgroundColor: ['#1877F2', '#00B900', '#EA4335', '#ff00ff', '#ffcc00'], borderWidth: 0 }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'right' } } }
                });
            }

            // 3. Customer Reviews (Bar)
            const ctxReview = document.getElementById('reviewChart');
            if(ctxReview) {
                new Chart(ctxReview.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['5 ดาว 🌟', '4 ดาว ⭐', '3 ดาว ⭐', '2 ดาว ⭐', '1 ดาว ⭐'],
                        datasets: [{ 
                            label: 'จำนวนรีวิว', 
                            data: [45, 12, 4, 1, 0], 
                            backgroundColor: ['#ffcc00', '#ffaa00', '#ff8800', '#ff5500', '#ff003c'], 
                            borderRadius: 4 
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        scales: { 
                            y: { grid: { color: '#222' }, ticks: { stepSize: 10 } }, 
                            x: { grid: { display: false } } 
                        }, 
                        plugins: { legend: { display: false } } 
                    }
                });
            }

            // 4. Customer Type (Pie)
            const ctxCust = document.getElementById('custTypeChart');
            if(ctxCust && (<?= $cust_data[0] ?> > 0 || <?= $cust_data[1] ?> > 0)) {
                new Chart(ctxCust.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: ['ลูกค้าใหม่ (New)', 'ลูกค้าเก่า (Returning)'],
                        datasets: [{ data: [<?= $cust_data[0] ?>, <?= $cust_data[1] ?>], backgroundColor: ['#ff5e00', '#00d0ff'], borderWidth: 2, borderColor: '#0f0f0f' }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });
            }
        };
    </script>
</body>
</html>
