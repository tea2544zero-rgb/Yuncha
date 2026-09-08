<?php
require_once 'config/security.php';
if (!$is_counter) { $has_access = false; }
require_once 'config/db.php';

// ดึงข้อมูลประเภทห้องพักทั้งหมด พร้อมสิ่งอำนวยความสะดวก
$types = $conn->query("
    SELECT rt.*, 
           (SELECT base_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p,
           (SELECT high_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_high,
           (SELECT holiday_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_holiday,
           (SELECT extra_bed_price FROM rooms WHERE room_type_id = rt.id LIMIT 1) as extra_bed_price,
           (SELECT discount_percent FROM rooms WHERE room_type_id = rt.id LIMIT 1) as discount_percent,
           (SELECT GROUP_CONCAT(DISTINCT ra.amenity_code) 
            FROM room_amenities ra 
            JOIN rooms r ON ra.room_id = r.id 
            WHERE r.room_type_id = rt.id) as amenities_list,
           (SELECT r.details 
            FROM rooms r 
            WHERE r.room_type_id = rt.id LIMIT 1) as details
    FROM room_types rt 
    ORDER BY rt.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ดึงห้องพักทั้งหมด
$rooms = $conn->query("SELECT r.*, rt.type_name FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number ASC")->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Yuncha Valley</title>
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

    <style>
        body { background-color: #000000; color: #ffffff; font-family: 'Prompt', sans-serif; overflow-x: hidden; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #000000; }
        ::-webkit-scrollbar-thumb { background: #00ff41; border-radius: 10px; }

        .neon-pro { position: relative; background: #0f0f0f; border-radius: 1rem; z-index: 1; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 0 0 1px rgba(255,255,255,0.1); }
        .neon-pro-glow { position: absolute; inset: -2px; border-radius: 1.1rem; z-index: -2; overflow: hidden; opacity: 0; transition: opacity 0.3s ease; }
        .neon-pro-glow::before { content: ''; position: absolute; top: 50%; left: 50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 70%, var(--neon-color) 100%); transform: translate(-50%, -50%); animation: spin-border 2s linear infinite; }
        .neon-pro::before { content: ''; position: absolute; inset: 0; background: #0f0f0f; border-radius: 1rem; z-index: -1; }
        @keyframes spin-border { 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        .neon-pro:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 20px var(--neon-color); z-index: 10; }
        .neon-pro:hover .neon-pro-glow { opacity: 1; }
        .icon-glow { transition: all 0.3s; color: #a3a3a3; }
        .neon-pro:hover .icon-glow { color: var(--neon-color) !important; filter: drop-shadow(0 0 8px var(--neon-color)); transform: scale(1.15); }
        
        .input-dark { background-color: #050505; border: 1px solid #333; color: white; padding: 0.75rem 1rem; border-radius: 0.75rem; outline: none; transition: all 0.3s; width: 100%; appearance: textfield; }
        .input-dark:focus { border-color: var(--neon-color); box-shadow: 0 0 10px rgba(0, 255, 65, 0.2); }
        
        /* ตัดปัญหาเส้นยักๆ ออกแล้ว */
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { appearance: none; margin: 0; }

        .amenity-checkbox:checked + div { background-color: rgba(0, 255, 65, 0.1); border-color: #00ff41; color: #00ff41; box-shadow: 0 0 10px rgba(0, 255, 65, 0.2); }
        
        .neon-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
        .neon-switch input { opacity: 0; width: 0; height: 0; }
        .neon-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #151515; transition: .3s; border-radius: 26px; border: 1px solid #333; }
        .neon-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #555; transition: .3s; border-radius: 50%; }
        input:checked + .neon-slider { background-color: rgba(0, 255, 65, 0.1); border-color: #00ff41; box-shadow: 0 0 10px rgba(0, 255, 65, 0.2); }
        input:checked + .neon-slider:before { transform: translateX(22px); background-color: #00ff41; box-shadow: 0 0 10px #00ff41; }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important;}</style>
</head>
<body class="h-screen flex selection:bg-ycGreen selection:text-black">

    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden w-full relative bg-[#050505]">
        
                <header class="h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 flex justify-between items-center z-30 sticky top-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg text-ycBlue border border-gray-800"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-black text-white tracking-wide">ROOM <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00d0ff] to-[#ff00ff]">MANAGEMENT</span></h2>
                    <p class="text-sm text-gray-400 font-medium">ระบบจัดการห้องพัก</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-4">เวลาปัจจุบัน: <span id="clockStatus" style="color: #00d0ff;"><?= date('H:i:s') ?></span></p>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
        <?php if ($has_access): ?>

            <div class="max-w-7xl mx-auto space-y-6 pb-12 gs-anim">
                
                <?php if($is_manager): ?>
                <div class="flex justify-between items-end mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2"><i class="ph-bold ph-squares-four text-ycGold"></i> 1. จัดการกลุ่มห้องพัก (Bulk Edit)</h3>
                        <p class="text-xs text-gray-400">ปรับราคาแบบคลุมทั้งกลุ่ม (สามารถกำหนดราคาห้อง 4 คนและเตียงเสริมได้เอง)</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <?php foreach($types as $type): ?>
                    <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-lg hover:border-gray-600 transition">
                        <h4 class="text-lg font-bold text-ycGold mb-2"><?= htmlspecialchars($type['type_name']) ?></h4>
                        <p class="text-sm text-gray-400">ราคาตั้งต้น (2 คน): <span class="text-white font-bold tracking-wider">฿<?= number_format($type['base_price']) ?></span></p>
                        <?php 
                            $raw_details = explode("|||", isset($type['details']) ? $type['details'] :  "");
                            $d_idx = isset($raw_details[0]) ? $raw_details[0] :  '';
                            $d_rm = isset($raw_details[1]) ? $raw_details[1] : (isset($raw_details[0]) ? $raw_details[0] : '');
                            $d_hl = isset($raw_details[2]) ? $raw_details[2] :  '';
                        ?>
                        <button onclick="openBulkModal(<?= isset($type['id']) ? $type['id'] :  0 ?>, <?= htmlspecialchars(json_encode(isset($type['type_name']) ? $type['type_name'] :  ''), ENT_QUOTES) ?>, <?= isset($type['base_price']) ? $type['base_price'] :  0 ?>, <?= isset($type['high_price']) ? $type['high_price'] :  0 ?>, <?= isset($type['holiday_price']) ? $type['holiday_price'] :  0 ?>, <?= htmlspecialchars(json_encode($d_idx), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($d_rm), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($d_hl), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode(isset($type['amenities_list']) ? $type['amenities_list'] :  ''), ENT_QUOTES) ?>, <?= isset($type['price_4p']) ? $type['price_4p'] :  ($type['base_price'] + 900) ?>, <?= isset($type['price_4p_high']) ? $type['price_4p_high'] :  ($type['high_price'] + 900) ?>, <?= isset($type['price_4p_holiday']) ? $type['price_4p_holiday'] :  ($type['holiday_price'] + 900) ?>, <?= isset($type['extra_bed_price']) ? $type['extra_bed_price'] :  500 ?>, <?= isset($type['discount_percent']) ? $type['discount_percent'] :  0 ?>)" class="mt-4 w-full py-2.5 bg-[#151515] hover:bg-[#202020] border border-[#333] hover:border-ycGreen text-ycGreen font-bold rounded-xl transition flex justify-center items-center gap-2 relative z-10 cursor-pointer">
                            <i class="ph-bold ph-pencil-simple text-lg pointer-events-none"></i> <span class="pointer-events-none">จัดการข้อมูล & ตั้งราคา</span>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <h3 class="text-xl font-bold text-white mb-4"><i class="ph-bold ph-list-numbers text-ycBlue"></i> 2. จัดการห้องพักรายห้อง (Quick Edit)</h3>
                
                <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl overflow-x-auto shadow-2xl">
                    <table class="w-full text-left text-sm text-gray-400 min-w-[800px]">
                        <thead class="text-xs uppercase bg-[#050505] text-gray-500 font-bold border-b border-gray-800">
                            <tr>
                                <th class="px-6 py-5">เลขห้อง / ขนาด</th>
                                <th class="px-6 py-5">ประเภทห้อง</th>
                                <th class="px-6 py-5 text-center">สถานะ (Quick Edit)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <?php if(!empty($rooms)): ?>
                                <?php foreach($rooms as $row): 
                                    $row_opacity = ($row['status'] == 'deleted') ? 'opacity-50' : '';
                                ?>
                                <tr class="hover:bg-[#151515] transition-colors <?= $row_opacity ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3 font-bold text-lg text-white">
                                            <i class="ph-duotone ph-door <?= ($row['status'] == 'deleted') ? 'text-gray-600' : 'text-ycGreen' ?> text-2xl"></i>
                                            <?= htmlspecialchars($row['room_number']) ?>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1 pl-9">
                                            สำหรับ <?= $row['max_guests'] ?> ท่าน 
                                            (฿<?= number_format($row['base_price']) ?>)
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['type_name']) ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <select onchange="updateRoomField(<?= $row['id'] ?>, 'status', this.value)" class="bg-[#050505] border border-gray-700 rounded px-3 py-1.5 text-xs font-bold outline-none cursor-pointer <?= $row['status'] == 'available' ? 'text-ycGreen' : 'text-orange-500' ?>" <?= !$is_manager ? 'disabled' : '' ?>>
                                            <option value="available" class="text-ycGreen" <?= $row['status'] == 'available' ? 'selected' : '' ?>>พร้อมให้บริการ</option>
                                            <option value="maintenance" class="text-orange-500" <?= $row['status'] == 'maintenance' ? 'selected' : '' ?>>ซ่อมบำรุง</option>
                                        </select>
                                    </td>

                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-8 text-gray-500">ยังไม่มีข้อมูลห้องพักในระบบ</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

    <!-- 🔥 Modal: จัดการข้อมูลกลุ่มห้องพัก (Bulk Edit) -->
    <div id="bulkModal" class="fixed inset-0 bg-black/90 z-50 hidden flex items-center justify-center backdrop-blur-sm overflow-y-auto pt-10 pb-10">
        <div class="bg-[#0f0f0f] w-full max-w-4xl rounded-2xl border border-gray-800 shadow-[0_0_30px_rgba(255,204,0,0.1)] relative my-auto">
            <div class="flex justify-between items-center p-6 border-b border-gray-800 bg-[#0a0a0a] rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-ycGold/20 rounded-xl flex items-center justify-center text-ycGold shadow-[0_0_10px_rgba(255,204,0,0.3)]">
                        <i class="ph-bold ph-stack text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white drop-shadow-md">จัดการข้อมูลกลุ่มห้องพัก</h3>
                </div>
                <button type="button" onclick="closeBulkModal()" class="w-8 h-8 bg-[#1a1a1a] hover:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 transition">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>
            
            <form action="actions/bulk_room_process.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-8">
                <input type="hidden" name="type_id" id="bulkTypeId">
                <div class="bg-[#1a1a1a] p-4 rounded-xl border border-[#333] mb-4 text-center">
                    <p class="text-lg text-ycGold font-bold tracking-wider" id="bulkTypeName">Tea Valley Room</p>
                    <p class="text-xs text-gray-400 mt-1">ข้อมูลทั้งหมดที่ถูกแก้ในหน้านี้ จะถูกอัปเดตไปยัง "ทุกห้อง" ในกลุ่มนี้</p>
                </div>
                <div>
                    <h4 class="text-lg font-bold text-white inline-block pb-2 border-b-2 border-ycGold mb-4">ตั้งราคา 3 ระดับ (สำหรับ 2 คน)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">วันธรรมดา (อาทิตย์-พฤหัส) <span class="text-ycRed">*</span></label>
                            <input type="number" name="base_price" id="bulkBasePrice" required min="1" class="input-dark font-bold text-ycGold text-lg tracking-wider" style="--neon-color: #ffcc00;" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">สุดสัปดาห์ (ศุกร์-เสาร์) <span class="text-ycRed">*</span></label>
                            <input type="number" name="high_price" id="bulkHighPrice" required min="1" class="input-dark font-bold text-orange-400 text-lg tracking-wider" style="--neon-color: #ff8800;" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">วันหยุดนักขัตฤกษ์ <span class="text-ycRed">*</span></label>
                            <input type="number" name="holiday_price" id="bulkHolidayPrice" required min="1" class="input-dark font-bold text-ycRed text-lg tracking-wider" style="--neon-color: #ff003c;" placeholder="0">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-lg font-bold text-white inline-block pb-2 border-b-2 border-ycGold mb-4">ตั้งราคา 3 ระดับ (สำหรับ 4 คน)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">วันธรรมดา (อาทิตย์-พฤหัส)</label>
                            <input type="number" name="price_4p_base" id="bulkPrice4PBase" min="0" value="0" class="input-dark font-bold text-ycGold text-lg tracking-wider" style="--neon-color: #ffcc00;" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">สุดสัปดาห์ (ศุกร์-เสาร์)</label>
                            <input type="number" name="price_4p_high" id="bulkPrice4PHigh" min="0" value="0" class="input-dark font-bold text-orange-400 text-lg tracking-wider" style="--neon-color: #ff8800;" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">วันหยุดนักขัตฤกษ์</label>
                            <input type="number" name="price_4p_holiday" id="bulkPrice4PHoliday" min="0" value="0" class="input-dark font-bold text-ycRed text-lg tracking-wider" style="--neon-color: #ff003c;" placeholder="0">
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold text-white inline-block pb-2 border-b-2 border-ycBlue mb-4">ส่วนเสริม และ โปรโมชั่น</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">ราคาเตียงเสริม (ต่อคืน) <span class="text-ycRed">*</span></label>
                            <input type="number" name="extra_bed_price" id="bulkExtraBedPrice" required min="0" class="input-dark font-bold text-ycBlue text-lg tracking-wider" style="--neon-color: #00d0ff;" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">ส่วนลดโปร (%)</label>
                            <input type="number" name="discount_percent" id="bulkDiscountPercent" value="0" min="0" max="100" class="input-dark font-bold text-ycPink text-lg tracking-wider" style="--neon-color: #ff009d;" placeholder="0">
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-lg font-bold text-white inline-block pb-2 border-b-2 border-ycGreen mb-4">สิ่งอำนวยความสะดวกพื้นฐานของกลุ่มนี้</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <?php 
                        $amenities = [
                            ['id'=>'wifi', 'icon'=>'wifi-high', 'name'=>'ฟรี Wi-Fi'],
                            ['id'=>'ac', 'icon'=>'snowflake', 'name'=>'แอร์ปรับอากาศ'],
                            ['id'=>'heater', 'icon'=>'thermometer-hot', 'name'=>'เครื่องทำน้ำอุ่น'],
                            ['id'=>'bathtub', 'icon'=>'bathtub', 'name'=>'อ่างแช่น้ำตัว'],
                            ['id'=>'nosmoke', 'icon'=>'warning-circle', 'name'=>'ปลอดบุหรี่'],
                            ['id'=>'smokearea', 'icon'=>'cigarette', 'name'=>'ระเบียงสูบบุหรี่'],
                            ['id'=>'smarttv', 'icon'=>'television', 'name'=>'สมาร์ททีวี'],
                            ['id'=>'minibar', 'icon'=>'refrigerator', 'name'=>'ตู้เย็นมินิบาร์'],
                            ['id'=>'coffee', 'icon'=>'coffee', 'name'=>'ชุดชาอู่หลง'],
                            ['id'=>'hairdryer', 'icon'=>'wind', 'name'=>'ไดร์เป่าผม'],
                            ['id'=>'teaview', 'icon'=>'leaf', 'name'=>'วิวไร่ชา'],
                            ['id'=>'mtview', 'icon'=>'mountains', 'name'=>'วิวภูเขา'],
                            ['id'=>'rvview', 'icon'=>'waves', 'name'=>'วิวแม่น้ำ'],
                            ['id'=>'bfast', 'icon'=>'cooking-pot', 'name'=>'ฟรีอาหารเช้า'],
                            ['id'=>'boat', 'icon'=>'boat', 'name'=>'ฟรีล่องเรือ']
                        ];
                        foreach($amenities as $item): ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="amenities[]" value="<?= $item['id'] ?>" class="bulk-amenity-checkbox sr-only">
                            <div class="px-3 py-3 rounded-xl border border-gray-800 bg-[#050505] text-gray-400 flex items-center justify-center gap-2 transition-all text-sm font-semibold hover:bg-[#111] bulk-amenity-div">
                                <i class="ph-bold ph-<?= $item['icon'] ?> text-lg"></i> <?= $item['name'] ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <h4 class="text-lg font-bold text-white mb-2">รายละเอียดหน้าแรก (TH/EN)</h4>
        <textarea name="details_index" id="bulkDetailsIndex" class="input-dark h-24 resize-none" style="--neon-color: #00ff41;" placeholder="รายละเอียดภาษาไทย&#10;English Description"></textarea>
    </div>
    <div>
        <h4 class="text-lg font-bold text-white mb-2">รายละเอียดหน้าห้องพัก (TH/EN)</h4>
        <textarea name="details_room" id="bulkDetailsRoom" class="input-dark h-24 resize-none" style="--neon-color: #00d0ff;" placeholder="รายละเอียดภาษาไทย&#10;English Description"></textarea>
    </div>
    <div>
        <h4 class="text-lg font-bold text-ycGold mb-2">จุดเด่น (Highlights TH/EN)</h4>
        <textarea name="details_highlights" id="bulkDetailsHighlights" class="input-dark h-24 resize-none" style="--neon-color: #ffcc00;" placeholder="คั่นด้วยลูกน้ำ เช่น อ่างแช่น้ำ, วิวภูเขา&#10;Comma separated e.g. Bathtub, Mountain View"></textarea>
    </div>
</div>

                <?php if($is_manager): ?>
                <div>
                    <h4 class="text-lg font-bold text-white inline-block pb-2 border-b-2 border-ycPink mb-4">แกลเลอรี่ห้องพักของกลุ่มนี้ (สูงสุด 15 รูป)</h4>
                    
                    <div id="image-slots-container" class="grid grid-cols-3 md:grid-cols-5 gap-3 mb-4">
                        <!-- Slots will be rendered here by JavaScript -->
                    </div>

                </div>
                <?php endif; ?>

                <div class="flex justify-end pt-4 border-t border-gray-800">
                    <button type="submit" class="neon-pro w-full py-4 flex items-center justify-center gap-2" style="--neon-color: #ffcc00;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-check-circle text-xl text-white relative z-10 icon-glow"></i>
                        <span class="font-bold text-white relative z-10 tracking-widest text-lg">อัปเดตข้อมูลไปยังทุกห้องในกลุ่มนี้</span>
                    </button>
                </div>
            </form>
        </div>
    </div>



    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        function toggleSidebar() { sidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); }
        document.getElementById('open-sidebar').addEventListener('click', toggleSidebar);
        document.getElementById('close-sidebar').addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Ã°Å¸Å’Å¸ Idle Check System (รีเฟรชถ้าปล่อยเมาส์ทิ้งไว้ 60 วิ และไม่ได้เปิดหน้าต่างอยู่)
        let idleTime = 0;
        window.onload = resetIdle;
        window.onmousemove = resetIdle;
        window.onkeypress = resetIdle;
        function resetIdle() { idleTime = 0; }
        
        setInterval(function() {
            idleTime += 1;
            if (idleTime >= 60) {
                let isModalOpen = !document.getElementById('bulkModal').classList.contains('hidden');
                if(!isModalOpen) {
                    location.reload();
                }
            }
        }, 1000);



        // Add visual toggle for type amenity checkboxes
        document.querySelectorAll('.bulk-amenity-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const div = this.nextElementSibling;
                if(this.checked) {
                    div.classList.replace('border-gray-800', 'border-ycGreen');
                    div.classList.replace('text-gray-400', 'text-ycGreen');
                } else {
                    div.classList.replace('border-ycGreen', 'border-gray-800');
                    div.classList.replace('text-ycGreen', 'text-gray-400');
                }
            });
        });



        const bulkModal = document.getElementById('bulkModal');
        
        function openBulkModal(typeId, typeName, basePrice, highPrice, holidayPrice, detailsIndex, detailsRoom, detailsHighlights, amenitiesStr, price4PBase, price4PHigh, price4PHoliday, extraBedPrice, discountPercent) {
    bulkModal.classList.remove('hidden');
    document.getElementById('bulkTypeId').value = typeId;
    document.getElementById('bulkTypeName').innerText = typeName;
    document.getElementById('bulkBasePrice').value = basePrice;
    document.getElementById('bulkHighPrice').value = highPrice;
    document.getElementById('bulkHolidayPrice').value = holidayPrice;
    document.getElementById('bulkPrice4PBase').value = price4PBase;
    document.getElementById('bulkPrice4PHigh').value = price4PHigh;
    document.getElementById('bulkPrice4PHoliday').value = price4PHoliday;
    document.getElementById('bulkExtraBedPrice').value = extraBedPrice;
    document.getElementById('bulkDiscountPercent').value = discountPercent;
    document.getElementById('bulkDetailsIndex').value = detailsIndex || '';
    document.getElementById('bulkDetailsRoom').value = detailsRoom || '';
    document.getElementById('bulkDetailsHighlights').value = detailsHighlights || '';
            
            // Clear all checkboxes first
            document.querySelectorAll('.bulk-amenity-checkbox').forEach(cb => {
                cb.checked = false;
                cb.nextElementSibling.classList.remove('border-ycGreen', 'text-ycGreen');
                cb.nextElementSibling.classList.add('border-gray-800', 'text-gray-400');
            });
            
            if (amenitiesStr) {
                const amenitiesArray = amenitiesStr.split(',');
                amenitiesArray.forEach(id => {
                    const cb = document.querySelector(`.bulk-amenity-checkbox[value="${id}"]`);
                    if (cb) {
                        cb.checked = true;
                        cb.nextElementSibling.classList.add('border-ycGreen', 'text-ycGreen');
                        cb.nextElementSibling.classList.remove('border-gray-800', 'text-gray-400');
                    }
                });
            }

            // Fetch existing images
            loadRoomTypeImages(typeId);
        }

        function loadRoomTypeImages(typeId) {
            fetch('api/get_room_type_images.php?type_id=' + typeId)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('image-slots-container');
                if(container) {
                    container.innerHTML = '';
                    const existingImages = (data.success && data.images) ? data.images : [];
                    
                    // Render exactly 15 slots
                    for (let i = 0; i < 15; i++) {
                        const slot = document.createElement('div');
                        slot.className = 'relative w-full aspect-square rounded-xl overflow-hidden bg-[#0a0a0a] flex items-center justify-center group transition-all';
                        
                        if (i < existingImages.length) {
                            const img = existingImages[i];
                            slot.classList.add('border', 'border-gray-600');
                            slot.innerHTML = `
                                <img src="../${img.image_path}" class="w-full h-full object-cover">
                                <button type="button" onclick="deleteRoomImage(${img.id}, this)" class="absolute top-1 right-1 bg-red-600 hover:bg-red-700 text-white w-7 h-7 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow-lg z-20 cursor-pointer">
                                    <i class="ph-bold ph-trash text-sm"></i>
                                </button>
                            `;
                        } else {
                            const slotId = `upload-slot-${i}`;
                            slot.classList.add('border-2', 'border-dashed', 'border-gray-700', 'hover:border-ycPink', 'cursor-pointer');
                            slot.innerHTML = `
                                <input type="file" name="room_images[]" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewSlotImage(this, '${slotId}')">
                                <div id="${slotId}-preview" class="absolute inset-0 w-full h-full pointer-events-none hidden">
                                    <img src="" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                        <i class="ph-bold ph-arrows-clockwise text-white text-2xl"></i>
                                    </div>
                                </div>
                                <div id="${slotId}-placeholder" class="text-gray-500 flex flex-col items-center pointer-events-none transition group-hover:text-ycPink z-0">
                                    <i class="ph-bold ph-plus text-3xl mb-1"></i>
                                </div>
                                <button type="button" onclick="clearSlotImage(this, '${slotId}')" class="absolute top-1 right-1 bg-gray-800 hover:bg-red-600 text-white w-7 h-7 rounded-full flex items-center justify-center opacity-0 transition shadow-lg z-20 cursor-pointer hidden">
                                    <i class="ph-bold ph-x text-sm"></i>
                                </button>
                            `;
                        }
                        container.appendChild(slot);
                    }
                }
            });
        }

        function previewSlotImage(input, slotId) {
            const previewDiv = document.getElementById(slotId + '-preview');
            const placeholderDiv = document.getElementById(slotId + '-placeholder');
            const img = previewDiv.querySelector('img');
            const slot = input.parentElement;
            const clearBtn = slot.querySelector('button');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    previewDiv.classList.remove('hidden');
                    placeholderDiv.classList.add('hidden');
                    slot.classList.remove('border-dashed', 'border-gray-700', 'hover:border-ycPink');
                    slot.classList.add('border-solid', 'border-ycGreen');
                    
                    clearBtn.classList.remove('hidden');
                    slot.classList.add('group-hover:opacity-100');
                    clearBtn.classList.add('group-hover:opacity-100');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                clearSlotImage(clearBtn, slotId);
            }
        }

        function clearSlotImage(btn, slotId) {
            const slot = btn.parentElement;
            const input = slot.querySelector('input[type="file"]');
            const previewDiv = document.getElementById(slotId + '-preview');
            const placeholderDiv = document.getElementById(slotId + '-placeholder');
            const img = previewDiv.querySelector('img');
            
            input.value = '';
            img.src = '';
            previewDiv.classList.add('hidden');
            placeholderDiv.classList.remove('hidden');
            slot.classList.add('border-dashed', 'border-gray-700', 'hover:border-ycPink');
            slot.classList.remove('border-solid', 'border-ycGreen', 'group-hover:opacity-100');
            
            btn.classList.remove('group-hover:opacity-100');
            btn.classList.add('hidden');
        }

        function deleteRoomImage(imageId, btnElem) {
            Swal.fire({
                title: 'ยืนยันการลบ',
                text: "ต้องการลบรูปภาพนี้ใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff003c',
                cancelButtonColor: '#333',
                confirmButtonText: 'ลบรูปภาพ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new URLSearchParams();
                    formData.append('image_id', imageId);
                    
                    fetch('api/delete_room_image.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            const typeId = document.getElementById('bulkTypeId').value;
                            loadRoomTypeImages(typeId);
                            // Optional: Alert success
                            const Toast = Swal.mixin({
                                toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true
                            });
                            Toast.fire({ icon: 'success', title: 'ลบรูปภาพสำเร็จ' });
                        } else {
                            Swal.fire('Error', 'ลบรูปภาพไม่สำเร็จ: ' + data.message, 'error');
                        }
                    });
                }
            });
        }

        function closeBulkModal() {
            bulkModal.classList.add('hidden');
        }

        function updateRoomField(roomId, field, value) {
            fetch('api/quick_update_room.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `room_id=${roomId}&field=${field}&value=${encodeURIComponent(value)}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // Update styling if status changed
                    if (field === 'status') {
                        const select = document.querySelector(`select[onchange*="${roomId}"][onchange*="status"]`);
                        select.className = `bg-[#050505] border border-gray-700 rounded px-3 py-1.5 text-xs font-bold outline-none cursor-pointer transition ${value === 'available' ? 'text-ycGreen' : 'text-orange-500'}`;
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            });
        }

        const typeImgInput = document.getElementById('typeImageInput');
        if(typeImgInput) {
            typeImgInput.addEventListener('change', function(e) {
                const container = document.getElementById('typeImagePreviewContainer');
                container.innerHTML = '';
                const files = e.target.files;
                if(files.length > 0) {
                    container.innerHTML = `<span class="bg-ycGreen text-black text-xs font-bold px-4 py-2 rounded-full">เลือกรูปภาพแล้ว ${files.length} ไฟล์</span>`;
                }
            });
        }

        gsap.fromTo(".gs-anim", { y: 30, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, ease: "power3.out" });
    
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
                    const optColorClass = Array.from(opt.classList).find(c => c.startsWith('text-'));
                    item.className = 'px-4 py-3 hover:bg-gray-800 cursor-pointer transition text-sm font-medium border-b border-gray-800 last:border-0 hover:text-[var(--neon-color)] ' + (optColorClass || 'text-white');
                    item.style.setProperty('--neon-color', select.style.getPropertyValue('--neon-color') || '#ffcc00');
                    item.innerText = opt.text;
                    item.onclick = (e) => {
                        select.selectedIndex = idx; // this will now auto-trigger change due to polyfill
                        text.innerText = opt.text;
                        
                        // Update btn color
                        btn.className = btn.className.replace(/text-(ycGreen|orange-500|ycRed|red-500|gray-[0-9]+|white)/g, '').trim();
                        if(optColorClass) btn.classList.add(optColorClass);
                        else btn.classList.add('text-white');

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

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'bulk_updated'): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'บันทึกข้อมูลสำเร็จ',
            text: 'อัปเดตข้อมูลและรูปภาพเรียบร้อยแล้ว',
            confirmButtonColor: '#00ff41',
            background: '#111',
            color: '#fff'
        });
        
        // Remove msg from URL to prevent showing alert again on refresh
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('msg');
            window.history.replaceState({ path: url.href }, '', url.href);
        }
    });
</script>
<?php endif; ?>

</body>
</html>




