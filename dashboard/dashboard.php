<?php
require_once 'config/security.php';
require_once 'config/db.php';
$view_mode = isset($_GET['view']) ? $_GET['view'] :  'daily';
$today = date('Y-m-d');
$start_date = $today;
$end_date = $today;
$label_suffix = 'วันนี้';

if ($view_mode == 'weekly') {
    $start_date = date('Y-m-d', strtotime('monday this week'));
    $end_date = date('Y-m-d', strtotime('sunday this week'));
    $label_suffix = 'สัปดาห์นี้';
} elseif ($view_mode == 'monthly') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    $label_suffix = 'เดือนนี้';
} elseif ($view_mode == 'yearly') {
    $start_date = date('Y-01-01');
    $end_date = date('Y-12-31');
    $label_suffix = 'ปีนี้';
}

// ==========================================
// 1. คิวรี่ข้อมูล KPIs รวม (อิงตาม Filter)
// ==========================================
$sql_kpi = "SELECT 
    SUM(CASE WHEN check_in BETWEEN '$start_date' AND '$end_date' AND status NOT IN ('cancelled', 'no_show') THEN 1 ELSE 0 END) as wait_in,
    SUM(CASE WHEN check_out BETWEEN '$start_date' AND '$end_date' AND status NOT IN ('cancelled', 'no_show') THEN 1 ELSE 0 END) as wait_out,
    SUM(CASE WHEN check_in <= '$today' AND check_out > '$today' AND status IN ('confirmed', 'checked_in') THEN 1 ELSE 0 END) as occ_rooms
    FROM bookings";
$res_kpi = $conn->query($sql_kpi);
$kpi = $res_kpi->fetch();

// ดึงจำนวนห้องทั้งหมดที่ไม่ได้ถูกลบ
$res_total_rooms = $conn->query("SELECT COUNT(id) as c FROM rooms WHERE status != 'deleted'");
$total_rooms_fetch = $res_total_rooms->fetch();
$total_hotel_rooms = isset($total_rooms_fetch['c']) ? $total_rooms_fetch['c'] : 0;
$occ_rooms = isset($kpi['occ_rooms']) ? $kpi['occ_rooms'] :  0;
$occ_rate = $total_hotel_rooms > 0 ? ($occ_rooms / $total_hotel_rooms) * 100 : 0;

// คำนวณรายได้ตามช่วงเวลา จากตารางธุรกรรมการเงิน (Transactions) ที่เป็นรายรับ
$sql_rev_today = "SELECT SUM(amount) as daily_rev FROM transactions WHERE transaction_date BETWEEN '$start_date' AND '$end_date' AND transaction_type = 'income'";
$daily_rev_res = $conn->query($sql_rev_today)->fetchColumn();
$daily_rev = $daily_rev_res ? $daily_rev_res : 0;

// คำนวณห้องว่างพร้อมขายจริง (ต้อง status = available และไม่มีจองของวันนี้)
$sql_avail = "SELECT COUNT(r.id) as avail FROM rooms r 
              WHERE r.status = 'available' 
              AND r.id NOT IN (
                  SELECT room_id FROM bookings 
                  WHERE check_in <= '$today' AND check_out > '$today' AND status IN ('pending', 'confirmed', 'checked_in')
              )";
$avail_rooms_res = $conn->query($sql_avail)->fetchColumn();
$avail_rooms = $avail_rooms_res ? $avail_rooms_res : 0;

// ==========================================
// 2. คิวรี่ข้อมูล สถานะห้องพักแยกตามประเภท (Real-time DB)
// ==========================================
$sql_type_stats = "
    SELECT 
        t.type_name,
        COUNT(r.id) as total_rooms,
        SUM(CASE WHEN r.status = 'available' AND b.id IS NULL THEN 1 ELSE 0 END) as available_rooms,
        SUM(CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END) as booked_rooms
    FROM room_types t
    LEFT JOIN rooms r ON t.id = r.room_type_id AND r.status != 'deleted'
    LEFT JOIN bookings b ON r.id = b.room_id 
        AND b.check_in <= '$today' 
        AND b.check_out > '$today' 
        AND b.status IN ('pending', 'confirmed', 'checked_in')
    GROUP BY t.id
";
$res_type_stats = $conn->query($sql_type_stats);
$room_type_stats = [];
if($res_type_stats) {
    while($row = $res_type_stats->fetch()) {
        $room_type_stats[] = $row;
    }
}
// ==========================================
// 3. คิวรี่ข้อมูล สัดส่วนรายได้แยกตามห้องพักจริง (อิงตาม Filter)
// ==========================================
$sql_rev = "SELECT t.type_name, IFNULL(SUM(b.total_price), 0) as rev 
            FROM room_types t 
            LEFT JOIN rooms r ON t.id = r.room_type_id 
            LEFT JOIN bookings b ON r.id = b.room_id AND b.status != 'cancelled' AND b.created_at >= '$start_date 00:00:00' AND b.created_at <= '$end_date 23:59:59'
            GROUP BY t.id";
$res_rev = $conn->query($sql_rev);
$rev_labels = []; $rev_data = [];
if($res_rev) {
    while($r = $res_rev->fetch()) {
        $rev_labels[] = "'" . addslashes($r['type_name']) . "'";
        $rev_data[] = $r['rev'];
    }
}
$sum_rev = array_sum($rev_data);
if ($sum_rev == 0) {
    $rev_labels_js = "['ยังไม่มีข้อมูลรายได้']";
    $rev_data_js = "[1]";
    $rev_colors_js = "['#222222']"; // Dark grey empty ring
} else {
    $rev_labels_js = "[" . implode(',', $rev_labels) . "]";
    $rev_data_js = "[" . implode(',', $rev_data) . "]";
    $rev_colors_js = "['#00d0ff', '#00ff41', '#ffcc00', '#ff00ff', '#ff5e00']";
}

// ==========================================
// 4. คิวรี่ข้อมูล Live Booking Feed จากระบบจริง
// ==========================================
$sql_feed = "SELECT b.booking_ref, b.created_at, b.status, c.first_name, c.last_name, r.room_number, t.type_name, c.auth_provider 
             FROM bookings b 
             LEFT JOIN customers c ON b.customer_id = c.id 
             LEFT JOIN rooms r ON b.room_id = r.id 
             LEFT JOIN room_types t ON r.room_type_id = t.id 
             ORDER BY b.created_at DESC LIMIT 5";
$feed_res = $conn->query($sql_feed);
$live_feeds = [];
if($feed_res) while($f = $feed_res->fetch()) $live_feeds[] = $f;

// ==========================================
// 5. ข้อมูลกราฟเส้น แนวโน้มรายได้และเข้าพัก (Real Data)
// ==========================================
$chart_labels = []; $chart_rev = []; $chart_occ = [];

if ($view_mode == 'yearly') {
    $thai_months = ['01'=>'ม.ค.', '02'=>'ก.พ.', '03'=>'มี.ค.', '04'=>'เม.ย.', '05'=>'พ.ค.', '06'=>'มิ.ย.', '07'=>'ก.ค.', '08'=>'ส.ค.', '09'=>'ก.ย.', '10'=>'ต.ค.', '11'=>'พ.ย.', '12'=>'ธ.ค.'];
    $sql_trend_rev = "SELECT strftime('%m', transaction_date) as m, SUM(amount) as rev FROM transactions WHERE transaction_date BETWEEN '$start_date' AND '$end_date' AND transaction_type = 'income' GROUP BY m";
    $res_trend_rev = $conn->query($sql_trend_rev);
    $rev_map = [];
    if($res_trend_rev) {
        while($r = $res_trend_rev->fetch()) $rev_map[$r['m']] = $r['rev'];
    }

    $sql_trend_occ = "SELECT check_in, check_out FROM bookings WHERE check_in <= '$end_date' AND check_out > '$start_date' AND status IN ('confirmed', 'checked_in', 'checked_out')";
    $res_trend_occ = $conn->query($sql_trend_occ);
    $bookings_occ = $res_trend_occ ? $res_trend_occ->fetchAll() : [];

    for ($m = 1; $m <= 12; $m++) {
        $m_str = str_pad($m, 2, '0', STR_PAD_LEFT);
        $chart_labels[] = "'" . $thai_months[$m_str] . "'";
        $chart_rev[] = isset($rev_map[$m_str]) ? $rev_map[$m_str] :  0;
        
        $m_start = date('Y-') . $m_str . '-01';
        $days_in_month = date('t', strtotime($m_start));
        $total_occ_days = 0;
        
        for ($d = 1; $d <= $days_in_month; $d++) {
            $day_date = date('Y-') . $m_str . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
            $daily_count = 0;
            foreach ($bookings_occ as $b) {
                if ($b['check_in'] <= $day_date && $b['check_out'] > $day_date) $daily_count++;
            }
            $total_occ_days += $daily_count;
        }
        $occ_percent = $total_hotel_rooms > 0 ? ($total_occ_days / ($total_hotel_rooms * $days_in_month)) * 100 : 0;
        $chart_occ[] = round($occ_percent, 1);
    }
} else {
    $chart_start = $start_date;
    $chart_end = $end_date;
    if ($view_mode == 'daily') {
        $chart_start = date('Y-m-d', strtotime('-6 days'));
        $chart_end = $today;
    }
    $period = new DatePeriod(new DateTime($chart_start), new DateInterval('P1D'), (new DateTime($chart_end))->modify('+1 day'));

    $sql_trend_rev = "SELECT transaction_date, SUM(amount) as rev FROM transactions WHERE transaction_date BETWEEN '$chart_start' AND '$chart_end' AND transaction_type = 'income' GROUP BY transaction_date";
    $res_trend_rev = $conn->query($sql_trend_rev);
    $rev_map = [];
    if($res_trend_rev) {
        while($r = $res_trend_rev->fetch()) $rev_map[$r['transaction_date']] = $r['rev'];
    }

    $sql_trend_occ = "SELECT check_in, check_out FROM bookings WHERE check_in <= '$chart_end' AND check_out > '$chart_start' AND status IN ('confirmed', 'checked_in', 'checked_out')";
    $res_trend_occ = $conn->query($sql_trend_occ);
    $bookings_occ = $res_trend_occ ? $res_trend_occ->fetchAll() : [];

    $thai_days = ['Sun'=>'อา.', 'Mon'=>'จ.', 'Tue'=>'อ.', 'Wed'=>'พ.', 'Thu'=>'พฤ.', 'Fri'=>'ศ.', 'Sat'=>'ส.'];
    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        if ($view_mode == 'monthly') {
            $chart_labels[] = "'" . $dt->format('d') . "'";
        } else {
            $chart_labels[] = "'" . $thai_days[$dt->format('D')] . " " . $dt->format('d') . "'";
        }
        
        $chart_rev[] = isset($rev_map[$d]) ? $rev_map[$d] :  0;
        
        $occ_count = 0;
        foreach ($bookings_occ as $b) {
            if ($b['check_in'] <= $d && $b['check_out'] > $d) $occ_count++;
        }
        $occ_percent = $total_hotel_rooms > 0 ? ($occ_count / $total_hotel_rooms) * 100 : 0;
        $chart_occ[] = round($occ_percent, 1);
    }
}

$trend_labels_js = "[" . implode(",", $chart_labels) . "]";
$trend_rev_js = "[" . implode(",", $chart_rev) . "]";
$trend_occ_js = "[" . implode(",", $chart_occ) . "]";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuncha Valley | Executive Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { ycDeep: '#000000', ycSurface: '#0f0f0f', ycGreen: '#00ff41', ycGold: '#ffcc00', ycBlue: '#00d0ff', ycPink: '#ff00ff', ycRed: '#ff003c', ycOrange: '#ff5e00', ycMint: '#00e676' }, fontFamily: { sans: ['Prompt', 'sans-serif'] } } }
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
        ::-webkit-scrollbar-thumb { background: #00d0ff; border-radius: 10px; }
        .stars-container { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .star { position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0; animation: fall linear infinite; box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.8); }
        @keyframes fall {
            0% { transform: translateY(-10vh) translateX(0) scale(1); opacity: 1; }
            100% { transform: translateY(110vh) translateX(-20vw) scale(0); opacity: 0; }
        }
        .neon-pro { position: relative; background: rgba(15,15,15,0.8); backdrop-filter: blur(10px); border-radius: 1rem; z-index: 1; transition: all 0.3s; box-shadow: 0 0 0 1px rgba(255,255,255,0.05); }
        .neon-pro-glow { position: absolute; inset: -1px; border-radius: 1.1rem; z-index: -2; overflow: hidden; opacity: 0; transition: opacity 0.3s ease; }
        .neon-pro-glow::before { content: ''; position: absolute; top: 50%; left: 50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 70%, var(--neon-color) 100%); transform: translate(-50%, -50%); animation: spin-border 3s linear infinite; }
        .neon-pro::before { content: ''; position: absolute; inset: 0; background: #0f0f0f; border-radius: 1rem; z-index: -1; }
        @keyframes spin-border { 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        .neon-pro:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 15px var(--neon-color); }
        .neon-pro:hover .neon-pro-glow { opacity: 1; }
        .icon-glow { transition: all 0.3s; color: #a3a3a3; }
        .neon-pro:hover .icon-glow { color: var(--neon-color) !important; filter: drop-shadow(0 0 8px var(--neon-color)); transform: scale(1.15); }
        .chart-container { position: relative; width: 100%; height: 280px; }
        .kpi-text { font-size: 2.2rem; line-height: 1.1; font-weight: 900; }
        
        select { appearance: none; -webkit-appearance: none; -moz-appearance: none; }
        .desc-tip { font-size: 0.65rem; color: #6b7280; font-weight: 500; margin-top: 4px; display: block; border-top: 1px dashed #333; padding-top: 4px; }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important;}</style>
</head>
<body class="h-screen flex selection:bg-ycGold selection:text-black">
    <div class="stars-container" id="starsBox"></div>
    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <?php include 'components/sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden w-full relative z-10 bg-transparent">
        <header class="h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 flex justify-between items-center z-30 sticky top-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg text-ycBlue border border-gray-800"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-black text-white tracking-wide">EXECUTIVE <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00d0ff] to-[#ff00ff]">DASHBOARD</span></h2>
                    <p class="text-sm text-gray-400 font-medium">ศูนย์บัญชาการข้อมูลระดับผู้บริหาร</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-4">เวลาปัจจุบัน: <span id="clockStatus" style="color: #00d0ff;"><?= date('H:i:s') ?></span></p>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
            <div class="max-w-[1500px] mx-auto space-y-6 pb-12">
                
                <!-- 🌟 ตัวเลือกสลับมุมมอง (รายวัน / รายสัปดาห์ / รายเดือน / รายปี) -->
                <div class="flex justify-center mb-6">
                    <div class="bg-[#0a0a0a] p-1 rounded-xl border border-gray-800 inline-flex">
                        <a href="dashboard.php?view=daily" class="<?= $view_mode == 'daily' ? 'bg-[#151515] text-white border border-gray-700 shadow-md' : 'text-gray-500 hover:text-gray-300' ?> px-6 py-2 rounded-lg text-sm font-bold transition">รายวัน</a>
                        <a href="dashboard.php?view=weekly" class="<?= $view_mode == 'weekly' ? 'bg-[#151515] text-white border border-gray-700 shadow-md' : 'text-gray-500 hover:text-gray-300' ?> px-6 py-2 rounded-lg text-sm font-bold transition">รายสัปดาห์</a>
                        <a href="dashboard.php?view=monthly" class="<?= $view_mode == 'monthly' ? 'bg-[#151515] text-white border border-gray-700 shadow-md' : 'text-gray-500 hover:text-gray-300' ?> px-6 py-2 rounded-lg text-sm font-bold transition">รายเดือน</a>
                        <a href="dashboard.php?view=yearly" class="<?= $view_mode == 'yearly' ? 'bg-[#151515] text-white border border-gray-700 shadow-md' : 'text-gray-500 hover:text-gray-300' ?> px-6 py-2 rounded-lg text-sm font-bold transition">รายปี</a>
                    </div>
                </div>

                <!-- 🚀 1. KPI ตัวเลขด่วน (Real-time KPI) ทุก Role เห็น -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 gs-anim">
                    <div class="neon-pro p-5 flex flex-col justify-between" style="--neon-color: #00d0ff;">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-1">อัตราเข้าพัก (เรียลไทม์)</p>
                        <h3 class="kpi-text text-white"><?= number_format($occ_rate, 1) ?><span class="text-xl text-gray-500">%</span></h3>
                        <div class="mt-2 w-full bg-gray-900 rounded-full h-1.5"><div class="bg-[#00d0ff] h-1.5 rounded-full" style="width: <?= $occ_rate ?>%"></div></div>
                        <span class="desc-tip">ห้องพักทั้งหมด: <?= $total_hotel_rooms ?> ห้อง</span>
                    </div>
                    <div class="neon-pro p-5 flex flex-col justify-between" style="--neon-color: #ffcc00;">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-1">เช็คอิน (<?= $label_suffix ?>)</p>
                        <h3 class="kpi-text text-ycGold drop-shadow-[0_0_8px_#ffcc00]"><?= number_format(isset($kpi['wait_in']) ? $kpi['wait_in'] :  0) ?> <span class="text-xl text-gray-500">ห้อง</span></h3>
                        <span class="desc-tip text-ycGold"><i class="ph-fill ph-info"></i> <?= $view_mode == 'daily' ? 'เตรียมกุญแจให้พร้อม' : 'ยอดเช็คอินรวม' ?></span>
                    </div>
                    <div class="neon-pro p-5 flex flex-col justify-between" style="--neon-color: #ff003c;">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-1">เช็คเอาท์ (<?= $label_suffix ?>)</p>
                        <h3 class="kpi-text text-ycRed drop-shadow-[0_0_8px_#ff003c]"><?= number_format(isset($kpi['wait_out']) ? $kpi['wait_out'] :  0) ?> <span class="text-xl text-gray-500">ห้อง</span></h3>
                        <span class="desc-tip text-ycRed"><i class="ph-fill ph-clock"></i> <?= $view_mode == 'daily' ? 'ภายในเวลา 12:00 น.' : 'ยอดเช็คเอาท์รวม' ?></span>
                    </div>
                    <div class="neon-pro p-5 flex flex-col justify-between" style="--neon-color: #00ff41;">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-1">รายได้ (<?= $label_suffix ?>)</p>
                        <h3 class="kpi-text text-ycGreen drop-shadow-[0_0_8px_#00ff41]"><span class="text-lg">฿</span><?= number_format($daily_rev) ?></h3>
                        <span class="desc-tip text-[#00ff41]"><i class="ph-bold ph-check-circle"></i> ยอดโอนและเงินสด</span>
                    </div>
                    <div class="neon-pro p-5 flex flex-col justify-between bg-purple-900/10 border border-purple-500/30" style="--neon-color: #b537f2;">
                        <p class="text-xs text-[#b537f2] font-bold uppercase tracking-widest mb-1">ห้องว่าง (เรียลไทม์)</p>
                        <h3 class="kpi-text text-white drop-shadow-[0_0_8px_rgba(181,55,242,0.8)]"><?= $avail_rooms ?> <span class="text-xl text-gray-500">ห้อง</span></h3>
                        <span class="desc-tip">รองรับ Walk-in ได้ทันที</span>
                    </div>
                </div>
                <!-- 🚀 2. สถานะห้องพักวันนี้แบบแยกประเภท (Real-time DB) -->
                <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-5 shadow-2xl gs-anim">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-md font-bold text-white"><i class="ph-fill ph-door-open text-ycBlue mr-2"></i> สถานะห้องพักแยกตามประเภท (วันนี้)</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach($room_type_stats as $ts): 
                            $tcolor = '#ffffff'; 
                            if(stripos($ts['type_name'], 'Tea Valley') !== false) $tcolor = '#00ff41';
                            elseif(stripos($ts['type_name'], 'Tea Pavilion') !== false) $tcolor = '#00d0ff';
                            elseif(stripos($ts['type_name'], 'Peak') !== false) $tcolor = '#ffcc00';
                            elseif(stripos($ts['type_name'], 'Garden') !== false) $tcolor = '#ff00ff';
                        ?>
                        <div class="bg-[#111] border border-gray-800 rounded-xl p-4 relative overflow-hidden group hover:border-[<?= $tcolor ?>] transition-colors shadow-lg">
                            <div class="absolute top-0 left-0 w-1.5 h-full transition-all duration-300 group-hover:w-2" style="background-color: <?= $tcolor ?>; box-shadow: 0 0 10px <?= $tcolor ?>;"></div>
                            <h4 class="text-md font-bold mb-4 pl-2 truncate" style="color: <?= $tcolor ?>;" title="<?= htmlspecialchars($ts['type_name']) ?>"><?= htmlspecialchars($ts['type_name']) ?></h4>
                            <div class="grid grid-cols-2 gap-3 pl-2">
                                <div class="bg-[#0a0a0a] rounded-lg p-3 text-center border border-gray-800 group-hover:border-ycRed/30 transition-colors shadow-inner">
                                    <p class="text-[10px] text-gray-500 font-bold mb-1 uppercase tracking-wider">จองแล้ว</p>
                                    <p class="text-3xl font-black text-ycRed drop-shadow-[0_0_8px_rgba(255,0,60,0.5)]"><?= $ts['booked_rooms'] ?></p>
                                </div>
                                <div class="bg-[#0a0a0a] rounded-lg p-3 text-center border border-gray-800 group-hover:border-ycGreen/30 transition-colors shadow-inner">
                                    <p class="text-[10px] text-gray-500 font-bold mb-1 uppercase tracking-wider">ว่างพร้อมขาย</p>
                                    <p class="text-3xl font-black text-ycGreen drop-shadow-[0_0_8px_rgba(0,255,65,0.5)]"><?= $ts['available_rooms'] ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- 🚀 3. กราฟวิเคราะห์ต่างๆ -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 gs-anim mt-6">
                    <!-- Revenue & Occupancy Trend -->
                    <div class="neon-pro p-6 lg:col-span-2 border border-gray-800" style="--neon-color: #00ff41;">
                        <div class="flex justify-between items-start md:items-center flex-col md:flex-row mb-4 gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-white"><i class="ph-fill ph-chart-line-up text-ycGreen mr-2"></i> แนวโน้มรายได้ และ อัตราการเข้าพัก</h3>
                                <span class="desc-tip border-none pt-0 mt-0">ข้อมูลจริงประมวลผลตามช่วงเวลาที่เลือก</span>
                            </div>
                        </div>
                        <div class="chart-container"><canvas id="revenueTrendChart"></canvas></div>
                    </div>
                    <!-- Revenue by Room Type (ดึงจากยอดจริง) -->
                    <div class="neon-pro p-6 border border-gray-800" style="--neon-color: #ffcc00;">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-white"><i class="ph-fill ph-chart-pie-slice text-ycGold mr-2"></i> สัดส่วนรายได้แยกตามประเภทห้อง</h3>
                            <span class="desc-tip border-none pt-0 mt-0">คำนวณจากยอดการจองจริงทั้งหมดในระบบ</span>
                        </div>
                        <div class="chart-container" style="height: 250px;"><canvas id="roomTypeChart"></canvas></div>
                    </div>
                </div>
                <!-- Live Booking Feed -->
                <div class="neon-pro p-6 border border-gray-800 flex flex-col gs-anim mt-6" style="--neon-color: #ff5e00;">
                    <div class="mb-4 flex justify-between items-center border-b border-gray-800 pb-2">
                        <div>
                            <h3 class="text-lg font-bold text-white"><i class="ph-fill ph-activity text-ycOrange mr-2 animate-pulse"></i> Live Booking Feed</h3>
                            <span class="desc-tip border-none pt-0 mt-0">การจองที่เพิ่งเข้ามาล่าสุดจากระบบจริง</span>
                        </div>
                    </div>
                    <ul class="space-y-3 flex-1 overflow-y-auto pr-2" id="liveFeedList">
                        <?php if(empty($live_feeds)): ?>
                            <li class="text-center text-gray-500 py-4 text-sm">ยังไม่มีข้อมูลการจองในระบบ</li>
                        <?php else: ?>
                            <?php foreach($live_feeds as $idx => $feed): 
                                $is_new = ($idx === 0);
                                $border_color = '#00d0ff';
                                if($feed['auth_provider'] == 'google') $border_color = '#EA4335';
                                elseif($feed['auth_provider'] == 'line') $border_color = '#00B900';
                                elseif($feed['auth_provider'] == 'facebook') $border_color = '#1877F2';
                                
                                $time_ago = date('d/m/Y H:i', strtotime($feed['created_at']));
                                $channel = ucfirst($feed['auth_provider']);
                                if($channel == 'Local') $channel = 'Direct (Website)';
                            ?>
                            <li class="bg-[#111] p-3 rounded-lg border-l-[3px] text-sm hover:bg-[#1a1a1a] transition" style="border-left-color: <?= $border_color ?>;">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="font-bold text-white"><?= htmlspecialchars($feed['first_name'] . ' ' . $feed['last_name']) ?></span>
                                    <?php if($is_new): ?>
                                    <span class="text-[10px] bg-ycOrange text-black px-1.5 rounded font-bold animate-pulse">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-gray-400">จองห้องพักประเภท <span class="font-bold" style="color: <?= $border_color ?>;"><?= htmlspecialchars($feed['type_name']) ?></span> <?= $feed['room_number'] ? "(ห้อง {$feed['room_number']})" : "" ?></p>
                                <div class="flex justify-between mt-2">
                                    <p class="text-[10px] text-gray-500"><i class="ph-fill ph-clock"></i> <?= $time_ago ?></p>
                                    <p class="text-[10px] text-gray-500 font-bold bg-[#222] px-2 py-0.5 rounded">ช่องทาง: <?= $channel ?></p>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    <script>
        // 🌟 จำตำแหน่ง Scroll ของเมนูด้านซ้าย (แก้ปัญหาเด้งกลับ)
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
        // 🌟 Idle Check System (รีเฟรชถ้าปล่อยเมาส์ทิ้งไว้ 60 วิ)
        let idleTime = 0;
        window.onload = resetIdle;
        window.onmousemove = resetIdle;
        window.onkeypress = resetIdle;
        function resetIdle() { idleTime = 0; }
        
        setInterval(function() {
            idleTime += 1;
            const now = new Date();
            document.getElementById('clockStatus').innerText = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            if (idleTime >= 60) {
                location.reload();
            }
        }, 1000);
        // Sidebar Control
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        function toggleSidebar() { sidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); }
        document.getElementById('open-sidebar').addEventListener('click', toggleSidebar);
        document.getElementById('close-sidebar').addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
        // 🌟 Chart.js Configurations
        Chart.defaults.color = '#9ca3af';
        Chart.defaults.font.family = 'Prompt';
        window.onload = function() {
            gsap.fromTo(".gs-anim", { y: 40, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: "power3.out" });
            const glowPlugin = {
                id: 'glow',
                beforeDatasetsDraw: (chart) => {
                    const c = chart.ctx; c.save(); c.shadowColor = '#00ff41'; c.shadowBlur = 15; c.shadowOffsetX = 0; c.shadowOffsetY = 0;
                },
                afterDatasetsDraw: (chart) => { chart.ctx.restore(); }
            };
            // 1. Revenue & Occupancy Trend
            const ctxTrend = document.getElementById('revenueTrendChart');
            if(ctxTrend) {
                let grad = ctxTrend.getContext('2d').createLinearGradient(0, 0, 0, 300);
                grad.addColorStop(0, 'rgba(0, 255, 65, 0.3)'); 
                grad.addColorStop(1, 'rgba(0, 255, 65, 0.0)');
                new Chart(ctxTrend.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?= $trend_labels_js ?>,
                        datasets: [
                            { label: 'รายได้ (฿)', data: <?= $trend_rev_js ?>, borderColor: '#00ff41', backgroundColor: grad, borderWidth: 4, pointBackgroundColor: '#000', pointBorderColor: '#00ff41', pointBorderWidth: 3, pointRadius: 5, fill: true, yAxisID: 'y', tension: 0.4 },
                            { label: 'อัตราเข้าพัก (%)', data: <?= $trend_occ_js ?>, type: 'bar', backgroundColor: 'rgba(0, 208, 255, 0.2)', borderColor: '#00d0ff', borderWidth: 1, borderRadius: 5, yAxisID: 'y1' }
                        ]
                    },
                    options: { 
                        responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, 
                        scales: { 
                            x: { grid: { display: false } },
                            y: { type: 'linear', display: true, position: 'left', grid: {color: '#222', borderDash: [5, 5]}, ticks: { callback: v => '฿' + (v/1000) + 'k' } }, 
                            y1: { type: 'linear', display: true, position: 'right', grid: {display: false}, min: 0, max: 100, ticks: { callback: v => v + '%' } } 
                        },
                        plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 12, usePointStyle: true } } }
                    },
                    plugins: [glowPlugin]
                });
            }
            // 2. Revenue by Room Type (Real DB)
            const ctxRoom = document.getElementById('roomTypeChart');
            if(ctxRoom) {
                const rLabels = <?= $rev_labels_js ?>;
                const rData = <?= $rev_data_js ?>;
                const rColors = <?= $rev_colors_js ?>;
                
                new Chart(ctxRoom.getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: rLabels, datasets: [{ data: rData, backgroundColor: rColors, borderWidth: 0, hoverOffset: 10 }] },
                    options: { 
                        responsive: true, maintainAspectRatio: false, cutout: '75%', 
                        plugins: { 
                            legend: { display: true, position: 'right', labels: { color: '#9ca3af', usePointStyle: true, boxWidth: 8, padding: 15, font: { size: 11, family: 'Prompt' } } },
                            tooltip: { callbacks: { label: function(context) { 
                                if (context.label === 'ยังไม่มีข้อมูลรายได้') return ' ฿ 0';
                                return ' ฿ ' + Number(context.raw).toLocaleString(); 
                            } } }
                        } 
                    }
                });
            }
        };
    </script>
</body>
</html>
