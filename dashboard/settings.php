<?php
require_once 'config/security.php';
if (!$is_manager) { $has_access = false; }

require_once 'config/db.php';

// ????????????????????????????
$stmt = $conn->query("SELECT * FROM settings");
$settings_db = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$policies = [];
try {
    // Removed legacy SQLite path
    if (true) {
        require_once __DIR__ . '/../dashboard/config/db.php';
        $pdoPolicy = $conn;
        $pdoPolicy->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmtPolicy = $pdoPolicy->query("SELECT * FROM policies ORDER BY id ASC");
        $policies = $stmtPolicy->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

$activities = [];
$act_settings = [];
try {
    // Removed legacy SQLite path
    if (true) {
        require_once __DIR__ . '/../dashboard/config/db.php';
        $pdoAct = $conn;
        $pdoAct->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $activities = $pdoAct->query("SELECT * FROM activities ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $act_settings = $pdoAct->query("SELECT * FROM act_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
} catch (Exception $e) {}

$settings = [
    'hotel_name' => isset($settings_db['hotel_name']) ? $settings_db['hotel_name'] :  'Yuncha Valley Resort',
    'address' => isset($settings_db['address']) ? $settings_db['address'] :  '123 หมู่ 4 ต.แม่ริม อ.แม่ริม จ.เชียงใหม่ 50180',
    'phone' => isset($settings_db['phone']) ? $settings_db['phone'] :  '089-123-4567',
    'email' => isset($settings_db['email']) ? $settings_db['email'] :  '',
    'language' => isset($settings_db['language']) ? $settings_db['language'] :  'th',
    'currency' => isset($settings_db['currency']) ? $settings_db['currency'] :  'THB',
    'check_in_time' => isset($settings_db['check_in_time']) ? $settings_db['check_in_time'] :  '14:00',
    'check_out_time' => isset($settings_db['check_out_time']) ? $settings_db['check_out_time'] :  '12:00',
    'deposit_percent' => isset($settings_db['deposit_percent']) ? $settings_db['deposit_percent'] :  50,
    'cancel_days' => isset($settings_db['cancel_days']) ? $settings_db['cancel_days'] :  7,
    'bank_name' => isset($settings_db['bank_name']) ? $settings_db['bank_name'] :  'กสิกรไทย (KBANK)',
    'bank_account' => isset($settings_db['bank_account']) ? $settings_db['bank_account'] :  '123-4-56789-0',
    'account_name' => isset($settings_db['account_name']) ? $settings_db['account_name'] :  'บจก. หยุนชา วัลเลย์',

    'line_token' => isset($settings_db['line_token']) ? $settings_db['line_token'] :  '',
    'booking_com_id' => isset($settings_db['booking_com_id']) ? $settings_db['booking_com_id'] :  '',
    'agoda_id' => isset($settings_db['agoda_id']) ? $settings_db['agoda_id'] :  '',
    'hotel_logo' => isset($settings_db['hotel_logo']) ? $settings_db['hotel_logo'] :  '',
    'qr_image' => isset($settings_db['qr_image']) ? $settings_db['qr_image'] :  '',
    'tax_id' => isset($settings_db['tax_id']) ? $settings_db['tax_id'] :  '0105555555555',
    'map_url' => isset($settings_db['map_url']) ? $settings_db['map_url'] :  '',
    'auto_cancel_hours' => isset($settings_db['auto_cancel_hours']) ? $settings_db['auto_cancel_hours'] :  24,
    'pet_friendly' => isset($settings_db['pet_friendly']) ? $settings_db['pet_friendly'] :  '0',
    'vat_percent' => isset($settings_db['vat_percent']) ? $settings_db['vat_percent'] :  7,
    'service_charge' => isset($settings_db['service_charge']) ? $settings_db['service_charge'] :  10,
    'default_extra_bed_price' => isset($settings_db['default_extra_bed_price']) ? $settings_db['default_extra_bed_price'] :  500,
    'google_analytics' => isset($settings_db['google_analytics']) ? $settings_db['google_analytics'] :  '',
    'meta_pixel' => isset($settings_db['meta_pixel']) ? $settings_db['meta_pixel'] :  '',
    'msg_booking_success' => isset($settings_db['msg_booking_success']) ? $settings_db['msg_booking_success'] :  '??อ????ุ????ี????อ??ห??อ????ักกั????รา',
    'msg_booking_cancel' => isset($settings_db['msg_booking_cancel']) ? $settings_db['msg_booking_cancel'] :  'การ??อ????อ????บาท??ูกยก??ลิก',
    'msg_receipt_footer' => isset($settings_db['msg_receipt_footer']) ? $settings_db['msg_receipt_footer'] :  '??อ????ุ????ี??????????ริการ หวันวบาทะ??????????กั????หม??',
    'admin_pin' => isset($settings_db['admin_pin']) ? $settings_db['admin_pin'] :  '123456',
    'chatbot_delay' => isset($settings_db['chatbot_delay']) ? $settings_db['chatbot_delay'] :  1500,
    'chatbot_api_key' => isset($settings_db['chatbot_api_key']) ? $settings_db['chatbot_api_key'] :  '',
    'chatbot_system_prompt' => isset($settings_db['chatbot_system_prompt']) ? $settings_db['chatbot_system_prompt'] :  '??ุ????ือ??ู??????วย??สมือ?? (Virtual Assistant) ??อ?? ' . (isset($settings_db['hotel_name']) ? $settings_db['hotel_name'] :  'Yuncha Valley') . ' ??อ????ำ??าม????วย??วามสุภา??และ????????มิ??ร',
    'chatbot_model' => isset($settings_db['chatbot_model']) ? $settings_db['chatbot_model'] :  'gemini-1.5-flash',
    'chatbot_name' => isset($settings_db['chatbot_name']) ? $settings_db['chatbot_name'] :  'Yuncha Bot',
    'chatbot_color' => isset($settings_db['chatbot_color']) ? $settings_db['chatbot_color'] :  '#00d0ff',
    'chatbot_avatar' => isset($settings_db['chatbot_avatar']) ? $settings_db['chatbot_avatar'] :  '',
    'chatbot_bg' => isset($settings_db['chatbot_bg']) ? $settings_db['chatbot_bg'] :  '',
    'holiday_dates' => isset($settings_db['holiday_dates']) ? $settings_db['holiday_dates'] :  '',
];
$stmt_logs = $conn->query("SELECT * FROM security_logs ORDER BY log_time DESC LIMIT 20");
$security_logs_db = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ (Settings) | Yuncha Valley</title>
    
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
        ::-webkit-scrollbar-thumb { background: #ffffff; border-radius: 10px; }

        .neon-pro { position: relative; background: #0f0f0f; border-radius: 1rem; z-index: 1; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 0 0 1px rgba(255,255,255,0.1); }
        .neon-pro-glow { position: absolute; inset: -2px; border-radius: 1.1rem; z-index: -2; overflow: hidden; opacity: 0; transition: opacity 0.3s ease; }
        .neon-pro-glow::before { content: ''; position: absolute; top: 50%; left: 50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 70%, var(--neon-color) 100%); transform: translate(-50%, -50%); animation: spin-border 2s linear infinite; }
        .neon-pro::before { content: ''; position: absolute; inset: 0; background: #0f0f0f; border-radius: 1rem; z-index: -1; }
        @keyframes spin-border { 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        .neon-pro:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 20px var(--neon-color); z-index: 10; }
        .neon-pro:hover .neon-pro-glow { opacity: 1; }
        .icon-glow { transition: all 0.3s; color: #a3a3a3; }
        .neon-pro:hover .icon-glow { color: var(--neon-color) !important; filter: drop-shadow(0 0 8px var(--neon-color)); transform: scale(1.15); }

        .input-dark { background-color: #050505; border: 1px solid #333; color: white; padding: 0.75rem 1rem; border-radius: 0.75rem; outline: none; transition: all 0.3s; appearance: textfield; width: 100%; }
        .input-dark:focus { border-color: #ffffff; box-shadow: 0 0 10px rgba(255, 255, 255, 0.2); }
        
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { appearance: none; margin: 0; }

        /* Custom Tabs */
        .tab-btn { border-bottom: 2px solid transparent; transition: all 0.3s; }
        .tab-btn.active { border-color: #ffffff; color: #ffffff; text-shadow: 0 0 10px rgba(255,255,255,0.5); }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Toggle Switch */
        .neon-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
        .neon-switch input { opacity: 0; width: 0; height: 0; }
        .neon-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #151515; transition: .3s; border-radius: 26px; border: 1px solid #333; }
        .neon-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #555; transition: .3s; border-radius: 50%; }
        input:checked + .neon-slider { background-color: rgba(255, 255, 255, 0.1); border-color: #ffffff; box-shadow: 0 0 10px rgba(255, 255, 255, 0.2); }
        input:checked + .neon-slider:before { transform: translateX(22px); background-color: #ffffff; box-shadow: 0 0 10px #ffffff; }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important;}</style>
</head>
<body class="h-screen flex selection:bg-white selection:text-black">

    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <!-- Sidebar อั????ริยะ ??ิ??????สี 100% -->
    <!-- Sidebar อั????ริยะ ??ิ??????สี 100% -->
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden w-full relative bg-[#050505]">
        
        <header class="h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 flex justify-between items-center z-30 sticky top-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg text-ycGold border border-gray-800"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-black text-white tracking-wide">SYSTEM <span class="text-transparent bg-clip-text bg-gradient-to-r from-gray-200 to-gray-500">SETTINGS</span></h2>
                    <p class="text-sm text-gray-400 font-medium">ตั้งค่าระบบและข้อมูลรีสอร์ท</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-4">เวลาปัจจุบัน: <span id="clockStatus" style="color: #ffffff;"><?= date('H:i:s') ?></span></p>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
        <?php if ($has_access): ?>
            <div class="max-w-7xl mx-auto space-y-6 pb-12 gs-anim">
                
                <div class="flex gap-2 overflow-x-auto pb-4 border-b border-gray-800 scrollbar-hide">
                    <button type="button" onclick="switchTab('general', this)" id="btn-general" class="tab-btn active px-6 py-3 font-bold rounded-xl whitespace-nowrap transition bg-[#111] text-gray-400 border border-gray-800">ข้อมูลทั่วไป</button>
                    <button type="button" onclick="switchTab('booking', this)" id="btn-booking" class="tab-btn px-6 py-3 font-bold rounded-xl whitespace-nowrap transition bg-[#111] text-gray-400 border border-gray-800">นโยบายการจอง</button>
                    <button type="button" onclick="switchTab('activities', this)" id="btn-activities" class="tab-btn px-6 py-3 font-bold rounded-xl whitespace-nowrap transition bg-[#111] text-gray-400 border border-gray-800">หน้ากิจกรรม (Activities)</button>
                    <button type="button" onclick="switchTab('payment', this)" id="btn-payment" class="tab-btn px-6 py-3 font-bold rounded-xl whitespace-nowrap transition bg-[#111] text-gray-400 border border-gray-800">การเงิน / ภาษี</button>
                    <button type="button" onclick="switchTab('chatbot', this)" id="btn-chatbot" class="tab-btn px-6 py-3 font-bold rounded-xl whitespace-nowrap transition bg-[#111] text-gray-400 border border-gray-800">ระบบ AI Chatbot</button>
                    <button type="button" onclick="switchTab('api', this)" id="btn-api" class="tab-btn px-6 py-3 font-bold rounded-xl whitespace-nowrap transition bg-[#111] text-gray-400 border border-gray-800">เทมเพลตข้อความ</button>
                    <button type="button" onclick="switchTab('logs', this)" id="btn-logs" class="tab-btn px-6 py-3 font-bold rounded-xl whitespace-nowrap transition bg-[#111] text-gray-400 border border-gray-800">บันทึกระบบ</button>
                </div>

                <form action="actions/settings_process.php" method="POST" enctype="multipart/form-data" id="settingsForm" class="space-y-6">
                    <input type="hidden" name="active_tab" id="active_tab_input" value="general">

                    <div id="tab-general" class="tab-content active space-y-6">
                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-bold text-white mb-6 border-b border-gray-800 pb-2"><i class="ph-fill ph-buildings"></i> ข้อมูลพื้นฐาน</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ชื่อรีสอร์ท / โรงแรม</label>
                                    <input type="text" name="hotel_name" value="<?= $settings['hotel_name'] ?>" readonly class="input-dark bg-[#111] text-gray-500 cursor-not-allowed border-gray-800" style="--neon-color: #555;">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">เบอร์โทรศัพท์ติดต่อ</label>
                                    <textarea name="phone" rows="3" class="input-dark w-full" style="--neon-color: #ffffff;" required><?= htmlspecialchars($settings['phone']) ?></textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ที่อยู่แบบเต็ม (สำหรับออกใบเสร็จ)</label>
                                    <input type="text" name="address" value="<?= htmlspecialchars(isset($settings['address']) ? $settings['address'] :  '') ?>" class="input-dark" style="--neon-color: #ffffff;">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">อีเมลติดต่อ</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars(isset($settings['email']) ? $settings['email'] :  '') ?>" class="input-dark" style="--neon-color: #ffffff;">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ลิงก์ Google Maps</label>
                                    <input type="text" name="map_url" value="<?= htmlspecialchars(isset($settings['map_url']) ? $settings['map_url'] :  '') ?>" class="input-dark" style="--neon-color: #ffffff;">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">เลขประจำตัวผู้เสียภาษี (Tax ID)</label>
                                    <input type="text" name="tax_id" value="<?= htmlspecialchars(isset($settings['tax_id']) ? $settings['tax_id'] :  '') ?>" class="input-dark" style="--neon-color: #ffffff;">
                                </div>
                            </div>

                            <div class="border-t border-gray-800 pt-6">
                                <h4 class="text-sm font-bold text-white mb-4">โลโก้รีสอร์ท (Resort Logo)</h4>
                                <div class="flex items-center gap-6">
                                    <div class="w-32 h-32 bg-[#050505] border border-gray-700 rounded-xl flex items-center justify-center text-ycGold font-black text-2xl overflow-hidden">
                                        <?php if(!empty($settings['hotel_logo'])): ?>
                                            <img src="uploads/<?= $settings['hotel_logo'] ?>" class="w-full h-full object-contain" alt="Hotel Logo">
                                        <?php else: ?>
                                            YUNCH?? 
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" name="hotel_logo" accept="image/png, image/jpeg" class="block w-full text-sm text-gray-400 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-[#111] file:text-white file:border file:border-gray-800 hover:file:border-ycGold hover:file:text-ycGold cursor-pointer transition">
                                        <p class="text-xs text-gray-500 mt-2">รองรับไฟล์ PNG, JPG แนะนำขนาด 500x500 px (พื้นหลังโปร่งใส)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-bold text-white mb-6 border-b border-gray-800 pb-2"><i class="ph-fill ph-globe"></i> ภาษาและสกุลเงิน (Localization)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ภาษาหลักของระบบ</label>
                                    <div class="relative">
                                        <select name="language" class="input-dark appearance-none pr-10" style="--neon-color: #ffffff;">
                                            <option value="th" <?= ($settings['language'] == 'th') ? 'selected' : '' ?>>ภาษาไทย (TH)</option>
                                            <option value="en" <?= ($settings['language'] == 'en') ? 'selected' : '' ?>>English (EN)</option>
                                        </select>
                                        <i class="ph-bold ph-caret-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">สกุลเงิน (Currency)</label>
                                    <div class="relative">
                                        <select name="currency" class="input-dark appearance-none pr-10" style="--neon-color: #ffffff;">
                                            <option value="THB" <?= ($settings['currency'] == 'THB') ? 'selected' : '' ?>>บาทไทย (THB - ฿)</option>
                                            <option value="USD" <?= ($settings['currency'] == 'USD') ? 'selected' : '' ?>>US Dollar (USD - $)</option>
                                        </select>
                                        <i class="ph-bold ph-caret-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tab-booking" class="tab-content space-y-6">
                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl mt-6">
                            <h3 class="text-lg font-bold text-white mb-6 border-b border-gray-800 pb-2"><i class="ph-fill ph-article text-ycRed"></i> จัดการนโยบายหน้าเว็บ (Website Policies)</h3>
                            <p class="text-xs text-gray-500 mb-6">แก้ไขนโยบายและเงื่อนไขการเข้าพักที่จะแสดงบนหน้าเว็บไซต์ (นโยบายการจอง, การยกเลิก, กฎระเบียบ ฯลฯ)</p>
                            
                            <div class="space-y-6">
                                <?php if (!empty($policies)): ?>
                                    <?php foreach ($policies as $policy): ?>
                                    <div class="border border-gray-800 bg-[#111] rounded-xl p-5 hover:border-gray-600 transition duration-300">
                                        <div class="flex items-center gap-3 mb-4 border-b border-gray-800 pb-3">
                                            <div class="text-2xl"><?= htmlspecialchars($policy['icon_code']) ?></div>
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-1">หัวข้อ (TH)</label>
                                                    <input type="text" name="policy_title_th[<?= $policy['id'] ?>]" value="<?= htmlspecialchars($policy['category_name_th']) ?>" class="input-dark text-sm font-bold text-ycGold w-full bg-transparent border-none p-0 focus:ring-0 outline-none shadow-none ring-0">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-1">หัวข้อ (EN)</label>
                                                    <input type="text" name="policy_title_en[<?= $policy['id'] ?>]" value="<?= htmlspecialchars($policy['category_name_en']) ?>" class="input-dark text-sm font-bold text-ycGold w-full bg-transparent border-none p-0 focus:ring-0 outline-none shadow-none ring-0">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-400 mb-2 uppercase tracking-widest"><i class="ph-bold ph-translate"></i> เนื้อหาภาษาไทย</label>
                                                <textarea name="policy_th[<?= $policy['id'] ?>]" rows="5" class="input-dark text-sm leading-relaxed" style="--neon-color: #ffffff;"><?= htmlspecialchars($policy['content_th']) ?></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-400 mb-2 uppercase tracking-widest"><i class="ph-bold ph-translate"></i> เนื้อหาภาษาอังกฤษ (English Content)</label>
                                                <textarea name="policy_en[<?= $policy['id'] ?>]" rows="5" class="input-dark text-sm leading-relaxed" style="--neon-color: #ffffff;"><?= htmlspecialchars($policy['content_en']) ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-6 text-gray-500">ไม่พบข้อมูลนโยบาย กรุณาตรวจสอบฐานข้อมูล Policy</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="tab-activities" class="tab-content space-y-6">
                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl mt-6">
                            <h3 class="text-lg font-bold text-white mb-6 border-b border-gray-800 pb-2"><i class="ph-fill ph-ticket text-ycOrange"></i> จัดการเนื้อหาหน้ากิจกรรม (Activities Page)</h3>
                            <p class="text-xs text-gray-500 mb-6">ตั้งค่าหัวข้อหลักและกิจกรรมย่อย 5 กิจกรรมที่จะแสดงบนหน้าเว็บไซต์</p>
                            
                            <h4 class="text-sm font-bold text-ycGold mb-4 mt-8">กิจกรรมแนะนำ (5 Activities)</h4>
                            <div class="space-y-6">
                                <?php if (!empty($activities)): ?>
                                    <?php foreach ($activities as $act): ?>
                                    <div class="border border-gray-800 bg-[#111] rounded-xl p-5 hover:border-gray-600 transition duration-300">
                                        <div class="flex items-center gap-3 mb-4 border-b border-gray-800 pb-3">
                                            <div class="text-2xl"><?= htmlspecialchars($act['icon']) ?></div>
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-1">ชื่อกิจกรรม (TH)</label>
                                                    <input type="text" name="act_title_th[<?= $act['id'] ?>]" value="<?= htmlspecialchars($act['title_th']) ?>" class="input-dark text-sm font-bold text-ycGold w-full bg-transparent border-none p-0 focus:ring-0 outline-none shadow-none ring-0">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-1">ชื่อกิจกรรม (EN)</label>
                                                    <input type="text" name="act_title_en[<?= $act['id'] ?>]" value="<?= htmlspecialchars($act['title_en']) ?>" class="input-dark text-sm font-bold text-ycGold w-full bg-transparent border-none p-0 focus:ring-0 outline-none shadow-none ring-0">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-1">คำบรรยายสั้น (Sub - TH)</label>
                                                <input type="text" name="act_sub_th[<?= $act['id'] ?>]" value="<?= htmlspecialchars($act['sub_th']) ?>" class="input-dark text-xs w-full bg-[#151515]">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-1">คำบรรยายสั้น (Sub - EN)</label>
                                                <input type="text" name="act_sub_en[<?= $act['id'] ?>]" value="<?= htmlspecialchars($act['sub_en']) ?>" class="input-dark text-xs w-full bg-[#151515]">
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-400 mb-2 uppercase tracking-widest"><i class="ph-bold ph-translate"></i> รายละเอียดเต็ม (TH)</label>
                                                <textarea name="act_desc_th[<?= $act['id'] ?>]" rows="4" class="input-dark text-xs leading-relaxed"><?= htmlspecialchars($act['desc_th']) ?></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-400 mb-2 uppercase tracking-widest"><i class="ph-bold ph-translate"></i> รายละเอียดเต็ม (EN)</label>
                                                <textarea name="act_desc_en[<?= $act['id'] ?>]" rows="4" class="input-dark text-xs leading-relaxed"><?= htmlspecialchars($act['desc_en']) ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-6 text-gray-500">ไม่พบข้อมูลกิจกรรม</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="tab-payment" class="tab-content space-y-6">
                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl mb-6">
                            <div class="mb-6 border-b border-gray-800 pb-2">
                                <h3 class="text-lg font-bold text-white"><i class="ph-fill ph-qr-code text-ycBlue"></i> คิวอาร์โค้ดรับเงิน (QR PromptPay)</h3>
                            </div>
                            <div class="flex flex-col md:flex-row gap-8 items-start">
                                <div class="w-48 h-48 bg-white p-3 rounded-2xl flex items-center justify-center shadow-[0_0_15px_rgba(255,255,255,0.1)] border-4 border-dashed border-gray-300">
                                    <img id="qr-preview" src="<?= !empty($settings['qr_image']) ? 'uploads/' . $settings['qr_image'] : 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg' ?>" class="w-full h-full object-contain <?= empty($settings['qr_image']) ? 'opacity-50 grayscale' : '' ?>" alt="QR Code">
                                </div>
                                <div class="flex-1 w-full mt-4 md:mt-0">
                                    <label class="block text-sm font-bold text-gray-400 mb-3">อัปโหลดภาพ QR Code ใหม่</label>
                                    <div class="relative group">
                                        <input type="file" name="qr_image" accept="image/*" onchange="document.getElementById('qr-preview').src = window.URL.createObjectURL(this.files[0]); document.getElementById('qr-preview').classList.remove('opacity-50', 'grayscale'); document.getElementById('qr-filename').textContent = this.files[0].name;" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        <div class="border-2 border-dashed border-gray-700 rounded-xl p-6 text-center group-hover:border-ycGold group-hover:bg-ycGold/5 transition-all duration-300">
                                            <i class="ph-duotone ph-upload-simple text-3xl text-gray-500 group-hover:text-ycGold mb-2"></i>
                                            <p class="text-sm text-gray-300 font-medium" id="qr-filename">คลิกเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</p>
                                            <p class="text-xs text-gray-600 mt-1">รองรับ JPG, PNG, WEBP (ขนาดไม่เกิน 2MB)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl mb-6">
                            <div class="mb-6 border-b border-gray-800 pb-2">
                                <h3 class="text-lg font-bold text-white"><i class="ph-fill ph-bank"></i> บัญชีธนาคารรับโอน (Bank Account)</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ธนาคาร (Bank Name)</label>
                                    <input type="text" name="bank_name" value="<?= htmlspecialchars(isset($settings['bank_name']) ? $settings['bank_name'] :  '') ?>" class="input-dark" style="--neon-color: #ffffff;">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ชื่อบัญชี (Account Name)</label>
                                    <input type="text" name="account_name" value="<?= htmlspecialchars(isset($settings['account_name']) ? $settings['account_name'] :  '') ?>" class="input-dark" style="--neon-color: #ffffff;">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-400 mb-2">เลขที่บัญชี (Account Number)</label>
                                    <input type="text" name="bank_account" value="<?= htmlspecialchars(isset($settings['bank_account']) ? $settings['bank_account'] :  '') ?>" class="input-dark font-black tracking-widest text-ycGold" style="--neon-color: #ffffff;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tab-chatbot" class="tab-content space-y-6">
                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl mt-6">
                            <h3 class="text-lg font-bold text-white mb-6 border-b border-gray-800 pb-2"><i class="ph-fill ph-robot text-[#00d0ff]"></i> การตั้งค่าแชทบอท (Chatbot API)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">เวลาหน่วงก่อนตอบกลับ (มิลลิวินาที)</label>
                                    <div class="relative">
                                        <input type="number" name="chatbot_delay" value="<?= $settings['chatbot_delay'] ?>" min="0" step="100" class="input-dark pr-12" style="--neon-color: #00d0ff;">
                                        <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-bold">ms</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">API Key (สำหรับ Google Gemini)</label>
                                    <input type="password" name="chatbot_api_key" value="<?= $settings['chatbot_api_key'] ?>" placeholder="วาง API Key ที่นี่..." class="input-dark font-mono text-xs" style="--neon-color: #00d0ff;">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">Model (รุ่นของ AI)</label>
                                    <input type="text" name="chatbot_model" value="<?= $settings['chatbot_model'] ?>" placeholder="เช่น gemini-3.1-flash-lite หรือ gemini-1.5-flash" class="input-dark font-mono text-xs" style="--neon-color: #00d0ff;">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-400 mb-2">System Prompt (กำหนดบทบาทของ AI)</label>
                                    <textarea name="chatbot_system_prompt" rows="3" class="input-dark text-sm" style="--neon-color: #00d0ff;" placeholder="กำหนดบุคลิกและบทบาทของแชทบอท..."><?= htmlspecialchars($settings['chatbot_system_prompt']) ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl mt-6">
                            <h3 class="text-lg font-bold text-white mb-6 border-b border-gray-800 pb-2"><i class="ph-fill ph-paint-brush text-[#00d0ff]"></i> การตกแต่งแชทบอท (Chatbot UI)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ชื่อแชทบอท (Chatbot Name)</label>
                                    <input type="text" name="chatbot_name" value="<?= htmlspecialchars(isset($settings['chatbot_name']) ? $settings['chatbot_name'] :  'Yuncha Bot') ?>" placeholder="เช่น Yuncha Assistant" class="input-dark font-bold text-sm" style="--neon-color: #00d0ff;">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">สีหลักของแชทบอท (Theme Color)</label>
                                    <div class="flex gap-4 items-center">
                                        <input type="color" name="chatbot_color" value="<?= htmlspecialchars(isset($settings['chatbot_color']) ? $settings['chatbot_color'] :  '#00d0ff') ?>" class="w-12 h-12 cursor-pointer bg-transparent border-0 p-0" style="clip-path: circle(50%);" oninput="this.nextElementSibling.value = this.value">
                                        <input type="text" value="<?= htmlspecialchars(isset($settings['chatbot_color']) ? $settings['chatbot_color'] :  '#00d0ff') ?>" class="input-dark font-mono text-xs flex-1" readonly>
                                    </div>
                                </div>
                                <div class="border-t border-gray-800 pt-6">
                                    <label class="block text-sm font-bold text-gray-400 mb-2">รูปโปรไฟล์แชทบอท (Avatar)</label>
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 rounded-full bg-gray-800 border border-gray-700 overflow-hidden flex items-center justify-center shrink-0">
                                            <?php if(!empty($settings['chatbot_avatar'])): ?>
                                                <img src="uploads/<?= $settings['chatbot_avatar'] ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="ph-bold ph-robot text-2xl text-gray-500"></i>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="chatbot_avatar" accept="image/png, image/jpeg, image/webp" class="block w-full text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#111] file:text-white file:border file:border-gray-800 hover:file:border-[#00d0ff] hover:file:text-[#00d0ff] cursor-pointer transition">
                                    </div>
                                </div>
                                <div class="border-t border-gray-800 pt-6">
                                    <label class="block text-sm font-bold text-gray-400 mb-2">สีพื้นหลังแชทบอท (Background Color)</label>
                                    <div class="flex gap-4 items-center">
                                        <input type="color" name="chatbot_bg" value="<?= htmlspecialchars(isset($settings['chatbot_bg']) ? $settings['chatbot_bg'] :  '#ffffff') ?>" class="w-12 h-12 cursor-pointer bg-transparent border-0 p-0" style="clip-path: circle(50%);" oninput="this.nextElementSibling.value = this.value">
                                        <input type="text" value="<?= htmlspecialchars(isset($settings['chatbot_bg']) ? $settings['chatbot_bg'] :  '#ffffff') ?>" class="input-dark font-mono text-xs flex-1" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tab-api" class="tab-content space-y-6">
                        <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-bold text-white mb-6 border-b border-gray-800 pb-2"><i class="ph-fill ph-envelope-simple text-ycPink"></i> เทมเพลตข้อความ (Message Templates)</h3>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ข้อความยืนยันการจองสำเร็จ (ส่งทางอีเมล/LINE)</label>
                                    <textarea name="msg_booking_success" rows="3" class="input-dark text-sm" style="--neon-color: #ff00ff;"><?= htmlspecialchars($settings['msg_booking_success']) ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ข้อความแจ้งยกเลิกการจอง</label>
                                    <textarea name="msg_booking_cancel" rows="3" class="input-dark text-sm" style="--neon-color: #ff00ff;"><?= htmlspecialchars($settings['msg_booking_cancel']) ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-400 mb-2">ข้อความส่วนท้ายใบเสร็จ (Receipt Footer)</label>
                                    <textarea name="msg_receipt_footer" rows="2" class="input-dark text-sm" style="--neon-color: #ff00ff;"><?= htmlspecialchars($settings['msg_receipt_footer']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tab-logs" class="tab-content space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-blue-900/10 border border-blue-500/30 rounded-2xl p-6 text-center shadow-[0_0_15px_rgba(0,208,255,0.05)] flex flex-col justify-between">
                                <div>
                                    <i class="ph-duotone ph-database text-6xl text-ycBlue mb-4"></i>
                                    <h3 class="text-xl font-bold text-white mb-2">สำรองข้อมูล (Backup Database)</h3>
                                    <p class="text-sm text-gray-400 mb-6">ดาวน์โหลดไฟล์ฐานข้อมูล (.sql) เก็บไว้เพื่อความปลอดภัย ป้องกันข้อมูลสูญหาย</p>
                                </div>
                                <a href="actions/backup_db.php" class="w-full py-3 rounded-xl bg-ycBlue text-black font-black hover:bg-blue-500 transition shadow-[0_0_15px_rgba(0,208,255,0.4)] flex justify-center items-center gap-2">
                                    <i class="ph-bold ph-download-simple text-lg"></i> ดาวน์โหลด Backup
                                </a>
                            </div>

                            <div class="bg-red-900/10 border border-red-500/30 rounded-2xl p-6 text-center shadow-[0_0_15px_rgba(255,0,60,0.05)] flex flex-col justify-between">
                                <div>
                                    <i class="ph-duotone ph-broom text-6xl text-ycRed mb-4"></i>
                                    <h3 class="text-xl font-bold text-white mb-2">ล้างข้อมูลระบบ (Clear Cache)</h3>
                                    <p class="text-sm text-gray-400 mb-6">ล้างไฟล์รูปภาพขยะที่ไม่ได้ใช้งาน และ Session เก่าๆ เพื่อเพิ่มพื้นที่ให้เซิร์ฟเวอร์</p>
                                </div>
                                <button type="button" onclick="clearCache()" class="w-full py-3 rounded-xl bg-transparent border border-ycRed text-ycRed font-bold hover:bg-ycRed hover:text-white transition shadow-[0_0_15px_rgba(255,0,60,0.2)]">
                                    <i class="ph-bold ph-trash"></i> ล้างข้อมูลขยะ
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="mt-8 flex justify-end pb-12">
                    <button type="button" onclick="saveAllSettings()" class="bg-ycPink text-white px-10 py-4 rounded-full font-black shadow-[0_0_20px_rgba(255,0,157,0.5)] hover:bg-pink-600 transition-all flex items-center gap-2 group border border-pink-400">
                        <i class="ph-bold ph-floppy-disk text-xl group-hover:scale-110 transition-transform"></i> บันทึกการตั้งค่า
                    </button>
                </div>
            </div>
        
        <?php else: ?>
        <div class="max-w-md mx-auto my-12 neon-pro p-8 text-center" style="--neon-color: #ff003c;">
            <div class="w-20 h-20 mx-auto bg-red-950/30 border border-red-500/50 rounded-full flex items-center justify-center mb-6 shadow-[0_0_20px_rgba(255,0,60,0.3)]">
                <i class="ph-fill ph-shield-warning text-4xl text-ycRed drop-shadow-[0_0_8px_#ff003c]"></i>
            </div>
            <h3 class="text-2xl font-black text-white mb-2">ปฏิเสธการเข้าถึง</h3>
            <p class="text-sm text-gray-400 mb-6">ขออภัย บัญชีของคุณไม่มีสิทธิ์เข้าใช้งานหน้านี้ เฉพาะเจ้าหน้าที่ระดับบริหารที่มีสิทธิ์เข้าถึงเท่านั้น</p>
            <a href="dashboard.php" class="inline-flex items-center gap-2 bg-[#111] border border-gray-800 hover:border-ycGold hover:text-ycGold text-white px-6 py-3 rounded-xl font-bold transition">
                <i class="ph-bold ph-arrow-left"></i> กลับสู่แผงควบคุมหลัก
            </a>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- 🌟 Alert Modal (Popup แจ้งเตือนเมื่อกระทำสำเร็จ) -->
<div id="successAlert" class="fixed inset-0 bg-black/90 z-[200] hidden flex items-center justify-center backdrop-blur-sm transition-opacity duration-300 opacity-0">
    <div class="bg-[#0f0f0f] p-8 rounded-3xl border border-ycGreen shadow-[0_0_40px_rgba(0,255,65,0.2)] text-center transform scale-90 transition-transform duration-300 w-full max-w-sm" id="successAlertContent">
        <div class="w-24 h-24 bg-ycGreen/20 rounded-full flex items-center justify-center mx-auto mb-6 shadow-[0_0_20px_rgba(0,255,65,0.4)]">
            <i class="ph-bold ph-check text-6xl text-ycGreen" id="successIcon"></i>
        </div>
        <h2 class="text-3xl font-black text-white mb-2" id="successTitle">สำเร็จ!</h2>
        <p class="text-gray-400 font-medium mb-8" id="successMessage">ดำเนินการเสร็จสิ้นเรียบร้อย</p>
        <button onclick="closeSuccessAlert()" class="w-full bg-ycGreen hover:bg-green-500 text-black font-black py-3 rounded-xl transition shadow-[0_0_15px_rgba(0,255,65,0.4)]" id="successBtn">ตกลง</button>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('success') && urlParams.get('success') === 'settings') {
        showSuccessAlert("อัปเดตการตั้งค่าระบบเรียบร้อย", "text-ycGreen", "bg-ycGreen hover:bg-green-500", "shadow-[0_0_15px_rgba(0,255,65,0.4)]", "bg-ycGreen/20 shadow-[0_0_20px_rgba(0,255,65,0.4)]", "border-ycGreen shadow-[0_0_40px_rgba(0,255,65,0.2)]", "ph-bold ph-check text-6xl text-ycGreen");
        window.history.replaceState({}, document.title, window.location.pathname + (urlParams.has('tab') ? "?tab=" + urlParams.get('tab') : ""));
    }

    function showSuccessAlert(message, colorClass, bgClass, shadowClass, iconBox, boxBorder, iconCls) {
        const alertBox = document.getElementById('successAlert');
        const content = document.getElementById('successAlertContent');
        const btn = document.getElementById('successBtn');
        
        document.getElementById('successMessage').innerText = message;
        document.getElementById('successIcon').className = iconCls;
        document.getElementById('successIcon').parentElement.className = `w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 ${iconBox}`;
        content.className = `bg-[#0f0f0f] p-8 rounded-3xl border text-center transform scale-90 transition-transform duration-300 w-full max-w-sm ${boxBorder}`;
        btn.className = `w-full text-black font-black py-3 rounded-xl transition ${bgClass} ${shadowClass}`;
        
        alertBox.classList.remove('hidden');
        setTimeout(() => {
            alertBox.classList.remove('opacity-0');
            content.classList.remove('scale-90');
            content.classList.add('scale-100');
        }, 10);
    }

    function closeSuccessAlert() {
        const alertBox = document.getElementById('successAlert');
        const content = document.getElementById('successAlertContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-90');
        alertBox.classList.add('opacity-0');
        setTimeout(() => { alertBox.classList.add('hidden'); }, 300);
    }

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    function toggleSidebar() { sidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); }
    if(document.getElementById('open-sidebar')) document.getElementById('open-sidebar').addEventListener('click', toggleSidebar);
    if(document.getElementById('close-sidebar')) document.getElementById('close-sidebar').addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);

    if(typeof gsap !== 'undefined') {
        gsap.fromTo(".gs-anim", { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.5 });
    }

    function switchTab(tabId, btnElement) {
        const activeTabInput = document.getElementById('active_tab_input');
        if(activeTabInput) activeTabInput.value = tabId;

        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.getElementById('tab-' + tabId).classList.add('active');

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        btnElement.classList.add('active');
    }

    function clearCache() {
        const pin = prompt('กรุณากรอกรหัส Admin PIN เพื่อยืนยันการล้างข้อมูลขยะ (ค่าเริ่มต้น: 123456):');
        if (!pin) return;

        fetch('actions/clear_cache.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'admin_pin=' + encodeURIComponent(pin)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('â????? ' + data.message);
                } else {
                    alert('â?? ' + data.message);
                }
                // รี??หล??ห??บาทอ????ื??อแส???? Log ??หม??ล??าสุ??
                window.location.reload();
            })
            .catch(error => {
                alert('??กิ??????อ??ิ????ลา??????การเชื่อมต่อ????ิร??????วอร??');
                console.error(error);
            });
        }

        function saveAllSettings() {
            document.getElementById('settingsForm').submit(); 
        }
    
// [CUSTOM SELECT INJECTION]
    // ???? Polyfill for select.value to auto-trigger change event
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

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            const btn = document.getElementById('btn-' + tabParam);
            if (btn) btn.click();
        }
    });
</script>
</body>
</html>



