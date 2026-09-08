<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] :  'th';

$dict = [
    'th' => [
        'nav_home' => 'หน้าแรก', 'nav_rooms' => 'ห้องพัก', 'nav_exp' => 'กิจกรรม', 'nav_policy' => 'นโยบาย', 'nav_contact' => 'ติดต่อที่พัก', 'nav_book' => 'จองห้องพัก',
        'sel_sanc' => 'เลือกห้องพัก', 'guests' => 'ผู้เข้าพัก', 'space' => 'ขนาดห้อง', 'view' => 'วิว', 'included' => 'รวมในแพ็คเกจ',
        'expand' => 'ขยายภาพ', 'amenities' => 'สิ่งอำนวยความสะดวกในห้องพัก', 'facilities' => 'บริการ',
        'guest_exp' => 'ประสบการณ์จากผู้เข้าพัก', 'reserve_stay' => 'สำรองที่พักของคุณ', 'book' => 'จอง',
        'checkin' => 'เช็คอิน', 'checkout' => 'เช็คเอาท์', 'live_pricing' => 'ราคาปัจจุบัน',
        'weekday' => 'ราคาวันธรรมดา (อาทิตย์-พฤหัส)', 'weekend' => 'ราคาวันหยุดสุดสัปดาห์ (ศุกร์-เสาร์)', 'holiday' => 'ราคาวันหยุดนักขัตฤกษ์',
        'est_total' => 'ราคารวม', 'check_avail' => 'เช็คห้องว่าง',
        'loc' => 'ที่ตั้ง', 'loc_desc' => 'หมู่บ้านรักไทย ต.หมอกจำแป่<br>อ.เมือง จ.แม่ฮ่องสอน',
        'contact' => 'ติดต่อ', 'contact_desc' => "080-342-9396 ( คุณเต้ )\n063-915-0091 ( คุณมิ้ง )\n053-123-456 ( RESORT )",
        'email' => 'อีเมล', 'nav_signin' => 'เข้าสู่ระบบ', 'rights' => '© 2026 ' . strtoupper(isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'YUNCHA VALLEY') . '. สงวนลิขสิทธิ์.'
    ],
    'en' => [
        'nav_home' => 'Home', 'nav_rooms' => 'Rooms', 'nav_exp' => 'Experiences', 'nav_policy' => 'Policies', 'nav_contact' => 'Contact', 'nav_book' => 'Booking',
        'sel_sanc' => 'Select Sanctuary', 'guests' => 'Guests', 'space' => 'Space', 'view' => 'View', 'included' => 'Included',
        'expand' => 'EXPAND', 'amenities' => 'Sanctuary Amenities', 'facilities' => 'Facilities',
        'guest_exp' => 'GUEST EXPERIENCES', 'reserve_stay' => 'Reserve Your Stay', 'book' => 'Booking',
        'checkin' => 'Check-In', 'checkout' => 'Check-Out', 'live_pricing' => 'Live Pricing',
        'weekday' => 'Weekday Rate', 'weekend' => 'Weekend Rate', 'holiday' => 'Holiday Rate',
        'est_total' => 'Estimated Total', 'check_avail' => 'Check Availability',
        'loc' => 'LOCATION', 'loc_desc' => 'Ban Rak Thai, Mok Champae<br>Mueang, Mae Hong Son',
        'contact' => 'CONTACT', 'contact_desc' => 'Tae · +66 80 342 9396<br>Ming · +66 63 915 0091',
        'email' => 'EMAIL', 'nav_signin' => 'Sign In', 'rights' => '© 2026 ' . strtoupper(isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'YUNCHA VALLEY') . '. ALL RIGHTS RESERVED.'
    ]
];
$t = $dict[$lang];

// Load dynamic settings and room prices
// Removed legacy SQLite path
$site_settings = [];
$db_rooms = [];
try {
    if (true) {
        require_once __DIR__ . '/../dashboard/config/db.php';
        $pdo = $conn;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // removed sql_mode
        
        // Load Settings
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) {
            $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Load Room Types with max_guests and details
        $stmt_rooms = $pdo->query("
            SELECT rt.id, rt.type_name, rt.base_price, rt.high_price, rt.holiday_price, 
                   MAX(r.max_guests) as max_guests, MAX(r.details) as details,
                   (SELECT base_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_base,
                   (SELECT high_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_high,
                   (SELECT holiday_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_holiday,
                   (SELECT extra_bed_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 2 LIMIT 1) as extra_bed_price,
                   (SELECT extra_bed_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as extra_bed_price_4p
            FROM room_types rt 
            LEFT JOIN rooms r ON rt.id = r.room_type_id 
            GROUP BY rt.id
            ORDER BY rt.id ASC
        ");
        if ($stmt_rooms) {
            $room_types_db = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);
        }

        // Load Room Images
        $db_images = [];
        $stmt_img = $pdo->query("
            SELECT r.room_type_id, ri.image_path, ri.slot_index 
            FROM room_images ri 
            JOIN rooms r ON ri.room_id = r.id 
            GROUP BY r.room_type_id, ri.image_path
            ORDER BY r.room_type_id, ri.slot_index ASC, ri.id ASC
        ");
        if ($stmt_img) {
            foreach ($stmt_img->fetchAll(PDO::FETCH_ASSOC) as $img) {
                $tid = $img['room_type_id'];
                if (!isset($db_images[$tid])) $db_images[$tid] = [];
                $db_images[$tid][] = '../' . ltrim($img['image_path'], '/');
            }
        }
        // Load all room amenities for dynamic view mapping
        $db_amenities = [];
        $stmt_am = $pdo->query("
            SELECT r.room_type_id, a.amenity_code
            FROM room_amenities a
            JOIN rooms r ON a.room_id = r.id
            GROUP BY r.room_type_id, a.amenity_code
        ");
        if ($stmt_am) {
            foreach ($stmt_am->fetchAll(PDO::FETCH_ASSOC) as $am) {
                $tid = $am['room_type_id'];
                if (!isset($db_amenities[$tid])) $db_amenities[$tid] = [];
                $db_amenities[$tid][] = $am['amenity_code'];
            }
        }
    }
} catch (Exception $e) {}

$raw_phone = isset($site_settings['phone']) ? $site_settings['phone'] :  "080-342-9396 ( คุณเต้ )\n063-915-0091 ( คุณมิ้ง )\n053-123-456 ( RESORT )";
$phone = $lang === 'en' ? str_replace(['คุณเต้', 'คุณมิ้ง'], ['Mr. Tae', 'Ms. Ming'], $raw_phone) : $raw_phone;
$email = isset($site_settings['email']) ? $site_settings['email'] :  'stay@yunchavalley.com';
$address = isset($site_settings['address']) ? $site_settings['address'] :  'หมู่บ้านรักไทย ต.หมอกจำแป่ อ.เมือง จ.แม่ฮ่องสอน 58000';
$hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'Yuncha Valley';

$dict['th']['contact_desc'] = $phone;
$dict['en']['contact_desc'] = $phone;
$dict['th']['loc_desc'] = $address;
$dict['en']['loc_desc'] = $address;
$dict['th']['email_desc'] = $email;
$dict['en']['email_desc'] = $email;

$t = $dict[$lang];

// คลังข้อมูลห้องพักทั้ง 3 รูปแบบ (แยกระบบ Mood & Tone ชัดเจน)
function getGuestString($max_guests, $lang) {
    if ($max_guests > 2) {
        return $lang == 'th' ? "ผู้เข้าพัก 2-{$max_guests} ท่าน" : "2-{$max_guests} Guests";
    }
    return $lang == 'th' ? 'ผู้เข้าพัก 2 ท่าน' : '2 Guests';
}

$rooms = [];
foreach ($room_types_db as $index => $rt) {
    $tid = $rt['id'];
    
    // Assign themes dynamically based on ID or fallback
    $themes = ['theme-tea', 'theme-lake', 'theme-peak'];
    $theme_class = $themes[$index % 3];
    
    // Split details
    $raw_details = explode("|||", isset($rt['details']) ? $rt['details'] :  "");
    $room_details = isset($raw_details[1]) ? $raw_details[1] : (isset($raw_details[0]) ? $raw_details[0] : "");
    $room_highlights = isset($raw_details[2]) ? $raw_details[2] :  "";
    $details_parts = explode("\n", $room_details);
    $desc_th = trim(isset($details_parts[0]) ? $details_parts[0] :  "รายละเอียดห้องพัก");
    $desc_en = trim(isset($details_parts[1]) ? $details_parts[1] :  $desc_th);
    
    $hl_parts = explode("%%%", $room_highlights);
    $hl_th = trim(isset($hl_parts[0]) ? $hl_parts[0] :  "");
    $hl_en = trim(isset($hl_parts[1]) ? $hl_parts[1] :  $hl_th);
    
    $max_guests = (int)(isset($rt['max_guests']) ? $rt['max_guests'] :  2);
    if ($max_guests == 0) $max_guests = 2;
    
    // Determine view and size from amenities
    $view_th = 'วิวไร่ชา';
    $view_en = 'Tea View';
    $size_val = 32;
    
    if (in_array('mtview', isset($db_amenities[$tid]) ? $db_amenities[$tid] :  [])) {
        $view_th = 'วิวภูเขา'; $view_en = 'Mountain View';
        $size_val = 45;
    } else if (in_array('rvview', isset($db_amenities[$tid]) ? $db_amenities[$tid] :  [])) {
        $view_th = 'วิวแม่น้ำ'; $view_en = 'River View';
        $size_val = 38;
    }

    $rooms[$tid] = [
        'id' => $tid,
        'theme_class' => $theme_class,
        'name_en' => $rt['type_name'],
        'name_cn' => '云顶别苑', // Placeholder
        'sub_en' => 'Mountain & Mist',
        'sub_th' => 'ขุนเขาและม่านหมอก',
        'desc_en' => $desc_en,
        'desc_th' => $desc_th,
        'price_weekday' => number_format($rt['base_price']),
        'price_weekend' => number_format($rt['high_price']),
        'price_holiday' => number_format($rt['holiday_price']),
        'price_4p_base' => number_format(isset($rt['price_4p_base']) ? $rt['price_4p_base'] :  ($rt['base_price'] + 900)),
        'price_4p_high' => number_format(isset($rt['price_4p_high']) ? $rt['price_4p_high'] :  ($rt['high_price'] + 900)),
        'price_4p_holiday' => number_format(isset($rt['price_4p_holiday']) ? $rt['price_4p_holiday'] :  $rt['holiday_price'] + 900),
        'extra_bed_price' => number_format(isset($rt['extra_bed_price']) ? $rt['extra_bed_price'] :  0),
        'extra_bed_price_4p' => number_format(isset($rt['extra_bed_price_4p']) ? $rt['extra_bed_price_4p'] : (isset($rt['extra_bed_price']) ? $rt['extra_bed_price'] : 0)),
        'max_guests' => $max_guests,
        'guests_en' => getGuestString($max_guests, 'en'),
        'guests_th' => getGuestString($max_guests, 'th'),
        'size_en' => "{$size_val} sq.m.",
        'size_th' => "{$size_val} ตร.ม.",
        'view_en' => $view_en,
        'view_th' => $view_th,
        'breakfast_en' => 'Breakfast Included',
        'breakfast_th' => 'รวมอาหารเช้า',
        'exp_title_en' => 'Awaken above the mist.',
        'exp_title_th' => 'ตื่นรับความสงบ เหนือม่านหมอก',
        'exp_desc_en' => $desc_en,
        'exp_desc_th' => $desc_th,
        'hl_en' => $hl_en,
        'hl_th' => $hl_th,
        'img' => (!empty($db_images[$tid])) ? $db_images[$tid][0] : '../img/m8.png',
        'gallery' => (!empty($db_images[$tid])) ? $db_images[$tid] : ['../img/m8.png']
    ];
}

$first_room_id = !empty($room_types_db) ? $room_types_db[0]['id'] : 3;
$type = isset($_GET['id']) ? $_GET['id'] :  (isset($_GET['type']) ? $_GET['type'] :  $first_room_id);

// Convert old string type to ID if they came from an old link
if ($type === 'tea-valley') $type = 3;
if ($type === 'lake-pavilion') $type = 4;
if ($type === 'peak-residence') $type = 5;

if (!array_key_exists($type, $rooms)) $type = $first_room_id;
$r = $rooms[$type];
$t['hero_title'] = $r['name_en'];

// Load Reviews for this room
$room_reviews = [];
try {
    if (isset($pdo)) {
        $stmt_rv = $pdo->prepare("
            SELECT r.id, r.rating, r.comment, r.english_comment, r.created_at, r.image_path, c.first_name, c.last_name 
            FROM reviews r
            JOIN customers c ON r.customer_id = c.id
            WHERE r.is_approved = 1 AND r.review_type = 'room' AND r.target_id = ?
            ORDER BY r.created_at DESC LIMIT 6
        ");
        $stmt_rv->execute([$type]);
        $room_reviews = $stmt_rv->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(Exception $e) {}

if (empty($room_reviews)) {
    $room_reviews = [
        [
            'id' => 1, 'first_name' => 'Anna', 'last_name' => 'W.', 'created_at' => '2026-05-12 10:00:00',
            'rating' => 5, 'comment' => 'ห้องพักกว้างขวางและสะอาดมาก วิวตอนเช้าสวยจนลืมหายใจ แนะนำเลยค่ะ', 'english_comment' => 'The room was very spacious and clean. The morning view was breathtaking. Highly recommended.',
            'image_path' => json_encode(['../img/m2.jpg', '../img/m4.jpg'])
        ],
        [
            'id' => 2, 'first_name' => 'ธนกฤต', 'last_name' => 'ม.', 'created_at' => '2026-06-05 14:30:00',
            'rating' => 5, 'comment' => 'บริการระดับ 5 ดาวจริงๆ พนักงานดูแลดีมาก สิ่งอำนวยความสะดวกครบครัน', 'english_comment' => 'Truly 5-star service. The staff took great care of us. Full amenities provided.',
            'image_path' => json_encode(['../img/m6.jpg'])
        ],
        [
            'id' => 3, 'first_name' => 'David', 'last_name' => 'L.', 'created_at' => '2026-06-15 09:15:00',
            'rating' => 5, 'comment' => 'เตียงนอนสบายสุดๆ หลับสนิทตลอดคืน บรรยากาศเงียบสงบเหมาะกับการชาร์จแบต', 'english_comment' => 'The bed was extremely comfortable. Slept soundly all night. The peaceful atmosphere is perfect for recharging.',
            'image_path' => json_encode(['../img/m3.jpg', '../img/m5.jpg', '../img/m7.jpg'])
        ]
    ];
}

$extra_head = '
    <link rel="stylesheet" href="style.css?v=' . time() . '">
    <style>
        .premium-price-box {
            background: linear-gradient(145deg, rgba(20,20,20,0.8), rgba(5,5,5,0.9));
            border: 1px solid rgba(255, 204, 0, 0.2);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5), inset 0 0 15px rgba(255, 204, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        .premium-price-box::before {
            content: \'\'; position: absolute; top: 0; left: 0; width: 2px; height: 100%;
            background: linear-gradient(to bottom, transparent, #ffcc00, transparent);
        }
        .btn-book-now {
            background: linear-gradient(90deg, #d97706, #ea580c, #d97706);
            background-size: 200% auto;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            box-shadow: 0 10px 30px -5px rgba(217, 119, 6, 0.6);
            animation: gradientFlow 3s ease infinite;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-book-now:hover { box-shadow: 0 15px 35px -5px rgba(217, 119, 6, 0.8); }
        @keyframes gradientFlow { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
    </style>
    <script>var API_BASE = \'../api/\';</script>
';
$body_class = "overflow-x-hidden " . $r['theme_class'];
$skip_preloader = true;
?>
<?php include '../components/header.php'; ?>

    <div class="fixed inset-0 pointer-events-none z-0 ambient-bg"></div>
    <div class="fixed inset-0 pointer-events-none z-0 cinematic-fog"></div>
    <div class="fixed inset-0 pointer-events-none z-0 gold-grain-overlay"></div>

    <section class="relative w-full min-h-[100svh] flex flex-col justify-end pb-12 pt-32 px-4 md:px-12 z-10 scene-3d hero-section-trigger">
        
        <div class="absolute inset-0 z-[-1] hero-clip-reveal">
            <div class="w-full h-full absolute hero-deep-zoom">
                <img src="<?= $r['img'] ?>" class="w-full h-full object-cover filter brightness-50" alt="Room Background">
            </div>
            <div class="absolute bottom-0 left-0 w-full h-48 md:h-64 bg-gradient-to-b from-transparent to-[#020202] z-10"></div>
        </div>

        <div class="watermark-cn top-1/4 left-0 opacity-20"><?= $r['name_cn'] ?></div>

        <div class="max-w-[1400px] mx-auto w-full flex flex-col relative z-10 pb-20 md:pb-32">
            
            <div class="w-full lg:w-2/3 flex flex-col gap-4">
                <div class="overflow-hidden"><span class="font-cn text-4xl md:text-6xl text-white/90 hero-text-reveal block drop-shadow-lg leading-normal pb-2"><?= $r['name_cn'] ?></span></div>
                <div class="overflow-hidden"><h1 class="font-cinzel text-5xl md:text-7xl lg:text-8xl font-bold tracking-wider theme-text-glow hero-text-reveal block drop-shadow-xl"><?= $r['name_en'] ?></h1></div>
                <div class="overflow-hidden"><p class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-gray-300 text-sm md:text-base tracking-widest uppercase mt-4 hero-text-reveal block max-w-xl"><?= $r['desc_'.$lang] ?></p></div>
                <div class="flex gap-4 mt-8 overflow-hidden"></div>
            </div>

        </div>
    </section>

    <div class="relative z-20 max-w-[1200px] mx-auto px-6 -mt-24 md:-mt-40 mb-12">
        <div class="flex flex-col gap-2 mb-6 gs-fade-up">
            <div class="flex flex-col sm:flex-row gap-4 room-selector-tabs">
                <?php foreach($rooms as $key => $roomData): ?>
                <a href="?type=<?= $key ?>" class="hover-target relative glass-panel p-6 md:p-8 flex-1 flex flex-col items-center justify-center text-center transition-all duration-500 hover:-translate-y-2 theme-border-always rounded-2xl <?= $type == $key ? 'transform scale-[1.02] bg-white/10 shadow-2xl' : 'opacity-70 hover:opacity-100 bg-black/40' ?>">
                    <span class="font-cn text-3xl md:text-4xl mb-3 text-white drop-shadow-md"><?= $roomData['name_cn'] ?></span>
                    <span class="font-cinzel text-sm md:text-base tracking-widest font-bold text-white uppercase mb-2"><?= $roomData['name_en'] ?></span>
                    <span class="<?= $lang == 'th' ? 'font-serif-thai text-sm' : 'font-prompt text-[10px]' ?> text-gray-400"><?= $roomData['sub_'.$lang] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="glassmorphism p-6 md:p-8 flex flex-wrap md:flex-nowrap justify-between items-center theme-border-always shadow-2xl rounded-2xl gs-fade-up">
            <div class="flex flex-col items-center px-4 md:px-8 w-1/2 md:w-auto mb-4 md:mb-0 border-r border-white/10">
                <span class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-white text-lg md:text-2xl font-bold"><?= $r['guests_'.$lang] ?></span>
                <span class="font-cinzel text-gray-400 text-[10px] uppercase tracking-[0.2em] mt-2"><?= $t['guests'] ?></span>
            </div>
            <div class="flex flex-col items-center px-4 md:px-8 w-1/2 md:w-auto mb-4 md:mb-0 md:border-r border-white/10">
                <span class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-white text-lg md:text-2xl font-bold"><?= $r['size_'.$lang] ?></span>
                <span class="font-cinzel text-gray-400 text-[10px] uppercase tracking-[0.2em] mt-2"><?= $t['space'] ?></span>
            </div>
            <div class="flex flex-col items-center px-4 md:px-8 w-1/2 md:w-auto border-r border-white/10">
                <span class="font-serif-thai text-white text-sm md:text-base font-medium theme-text-glow"><?= $r['view_'.$lang] ?></span>
                <span class="font-cinzel text-gray-400 text-[10px] uppercase tracking-[0.2em] mt-2"><?= $t['view'] ?></span>
            </div>
            <div class="flex flex-col items-center px-4 md:px-8 w-1/2 md:w-auto">
                <span class="<?= $lang == 'th' ? 'font-serif-thai text-xs' : 'font-cinzel text-sm' ?> text-white md:text-base font-medium"><?= $r['breakfast_'.$lang] ?></span>
                <span class="font-cinzel text-gray-400 text-[10px] uppercase tracking-[0.2em] mt-2"><?= $t['included'] ?></span>
            </div>
        </div>
    </div>

    <section class="py-32 px-6 max-w-[1400px] mx-auto flex flex-col lg:flex-row gap-20 items-start relative z-10 scene-3d">
        <div class="watermark-cn top-0 right-[-10%] opacity-10">体验</div>
        <div class="w-full lg:w-1/2 flex flex-col h-auto lg:h-[450px] gs-fade-up">
            <h2 class="<?= $lang == 'th' ? 'font-serif-thai text-4xl lg:text-5xl' : 'font-cinzel text-4xl md:text-5xl lg:text-6xl' ?> theme-text-glow leading-tight mb-6 mt-0 lg:mt-4 shrink-0">
                <?= $r['exp_title_'.$lang] ?>
            </h2>
            <p class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-prompt' ?> text-gray-400 text-lg leading-relaxed mb-6 shrink-0 line-clamp-3">
                <?= $r['exp_desc_'.$lang] ?>
            </p>
            <?php if (!empty($r['hl_'.$lang])): ?>
            <div class="glass-panel p-6 rounded-2xl theme-border-always bg-[#050505]/80 border border-ycGold/20 flex flex-col overflow-hidden">
                <h4 class="font-cinzel text-ycGold text-sm tracking-widest uppercase mb-4 shrink-0"><?= $lang == 'th' ? 'จุดเด่นห้องพัก' : 'Room Highlights' ?></h4>
                <ul class="list-none space-y-2">
                    <?php 
                        $highlights = array_filter(array_map('trim', preg_split('/[\n,]+/', $r['hl_'.$lang])));
                        $highlights = array_slice($highlights, 0, 5);
                        foreach($highlights as $hl):
                    ?>
                    <li class="flex items-start text-gray-300 <?= $lang == 'th' ? 'font-serif-thai' : 'font-prompt' ?>">
                        <span><?= htmlspecialchars($hl) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <div class="w-full lg:w-1/2 relative h-[350px] md:h-[450px] overflow-hidden rounded-2xl theme-border-glow group hover-target cursor-pointer gs-fade-up">
            <?php
            $exp_images = [
                3 => '../img/mk1/mk2.png',
                4 => '../img/tp/tp2.png',
                5 => '../img/ls/ls3.png'
            ];
            $exp_img = isset($exp_images[$type]) ? $exp_images[$type] : $r['img'];
            ?>
            <a href="javascript:void(0)" onclick="document.querySelectorAll('[data-fancybox=\'room-gallery\']')[0].click()" class="block w-full h-full relative">
                <img src="<?= $exp_img ?>" class="absolute inset-0 w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-105 filter brightness-90">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex items-center justify-center z-20">
                    <span class="font-cinzel text-white tracking-[0.3em] uppercase text-sm border border-white/30 px-6 py-2 backdrop-blur-md">ขยายภาพ</span>
                </div>
                <div class="absolute inset-0 ambient-particles pointer-events-none mix-blend-screen z-10"></div>
            </a>
        </div>
    </section>
 
    <section id="gallery" class="py-12 px-0 w-full relative z-10 scene-3d overflow-hidden mt-20">
        <div class="marquee-wrapper mb-8">
            <div class="marquee-track flex gap-4 h-[250px] md:h-[400px]">
                <?php 
                $gallery_images = isset($r['gallery']) ? $r['gallery'] :  [$r['img']];
                
                $base_images = $gallery_images;
                // Ensure there are enough images to fill the marquee width seamlessly
                while(count($base_images) < 10) {
                    $base_images = array_merge($base_images, $gallery_images);
                }
                
                // For a seamless CSS marquee (translateX -50%), duplicate the base images
                $display_images = array_merge($base_images, $base_images);
                $total_base = count($base_images);
                
                foreach($display_images as $index => $current_img): 
                    $is_clone = $index >= $total_base;
                    $gallery_index = $index % count($gallery_images);
                ?>
                <div class="relative overflow-hidden rounded-2xl group cursor-pointer w-[250px] md:w-[500px] shrink-0 gs-fade-up">
                    <?php if(!$is_clone): ?>
                    <a href="<?= htmlspecialchars($current_img) ?>" data-fancybox="room-gallery" class="block w-full h-full">
                    <?php else: ?>
                    <a href="javascript:void(0)" onclick="document.querySelectorAll('[data-fancybox=\'room-gallery\']')[<?= $gallery_index ?>].click()" class="block w-full h-full">
                    <?php endif; ?>
                        <img src="<?= htmlspecialchars($current_img) ?>" class="w-full h-full object-cover transition-transform duration-[1.5s] group-hover:scale-110">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex items-center justify-center">
                            <span class="font-cinzel text-white tracking-[0.3em] uppercase text-sm border border-white/30 px-6 py-2 backdrop-blur-md">ขยายภาพ</span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-24 relative z-10 border-t border-white/5 bg-[#0a0a0a] mt-12">
        <div class="max-w-[1400px] mx-auto px-6 mb-12 flex justify-between items-end gs-fade-up">
            <div>
                <h4 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cn' ?> text-2xl theme-text-glow mb-2"><?= $t['facilities'] ?></h4>
                <h3 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-3xl md:text-4xl text-white tracking-widest"><?= $t['amenities'] ?></h3>
            </div>
        </div>
        
        <div class="max-w-[1400px] mx-auto px-6 md:px-12 pb-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 gs-fade-up">
            <?php 
            $current_type_id = $type;
            // Fetch distinct amenities for this room type
            $stmt_amenities = $pdo->prepare("
                SELECT DISTINCT a.amenity_code 
                FROM room_amenities a
                JOIN rooms r ON a.room_id = r.id
                WHERE r.room_type_id = ?
            ");
            $stmt_amenities->execute([$current_type_id]);
            $db_amenities = $stmt_amenities->fetchAll(PDO::FETCH_COLUMN);
            
            // Map the codes to the display array
            $all_amenities_def = [
                'wifi' => ['icon' => '🛜', 'title_en' => 'Wi-Fi', 'title_th' => 'ฟรี Wi-Fi', 'desc_en' => 'High-Speed 5G', 'desc_th' => 'อินเทอร์เน็ตความเร็วสูง'],
                'ac' => ['icon' => '❄️', 'title_en' => 'Air Conditioning', 'title_th' => 'แอร์ปรับอากาศ', 'desc_en' => 'Climate Control', 'desc_th' => 'เย็นสบายตลอดวัน'],
                'heater' => ['icon' => '🌡️', 'title_en' => 'Water Heater', 'title_th' => 'เครื่องทำน้ำอุ่น', 'desc_en' => 'Hot Shower', 'desc_th' => 'อาบน้ำอุ่นสบาย'],
                'bathtub' => ['icon' => '🛁', 'title_en' => 'Bathtub', 'title_th' => 'อ่างแช่น้ำตัว', 'desc_en' => 'Luxury Soaking', 'desc_th' => 'แช่ตัวอย่างหรูหรา'],
                'kingbed' => ['icon' => '🛏️', 'title_en' => 'King Size Bed', 'title_th' => 'เตียงคิงไซส์', 'desc_en' => 'Ultra Comfort', 'desc_th' => 'นอนหลับสบายสูงสุด'],
                'twinbed' => ['icon' => '🛏️', 'title_en' => 'Twin Bed', 'title_th' => 'เตียงแฝด', 'desc_en' => 'Two Single Beds', 'desc_th' => 'เตียงเดี่ยว 2 เตียง'],
                'nosmoke' => ['icon' => '🚭', 'title_en' => 'No Smoking', 'title_th' => 'ปลอดบุหรี่', 'desc_en' => 'Clean Air', 'desc_th' => 'ห้องพักไร้กลิ่นบุหรี่'],
                'smokearea' => ['icon' => '🚬', 'title_en' => 'Smoking Area', 'title_th' => 'ระเบียงสูบบุหรี่', 'desc_en' => 'Private Balcony', 'desc_th' => 'พื้นที่สูบบุหรี่ส่วนตัว'],
                'smarttv' => ['icon' => '📺', 'title_en' => 'Smart TV', 'title_th' => 'สมาร์ททีวี', 'desc_en' => 'Netflix Premium', 'desc_th' => 'ดูเน็ตฟลิกซ์ได้เต็มอิ่ม'],
                'minibar' => ['icon' => '🧊', 'title_en' => 'Mini Bar', 'title_th' => 'ตู้เย็นมินิบาร์', 'desc_en' => 'Cold Drinks', 'desc_th' => 'เครื่องดื่มเย็นชื่นใจ'],
                'coffee' => ['icon' => '☯', 'title_en' => 'Tea Set', 'title_th' => 'ชุดชาอู่หลง', 'desc_en' => 'Premium Oolong', 'desc_th' => 'ชาพรีเมียมจากยอดเขา'],
                'hairdryer' => ['icon' => '💨', 'title_en' => 'Hair Dryer', 'title_th' => 'ไดร์เป่าผม', 'desc_en' => 'Easy Styling', 'desc_th' => 'จัดทรงผมได้ง่ายดาย'],
                'teaview' => ['icon' => '🍃', 'title_en' => 'Tea Plantation View', 'title_th' => 'วิวไร่ชา', 'desc_en' => 'Green Fields', 'desc_th' => 'มองเห็นไร่ชาไกลสุดตา'],
                'mtview' => ['icon' => '⛰️', 'title_en' => 'Mountain View', 'title_th' => 'วิวภูเขา', 'desc_en' => 'Scenic Ridge', 'desc_th' => 'ทัศนียภาพขุนเขา'],
                'rvview' => ['icon' => '🌊', 'title_en' => 'River View', 'title_th' => 'วิวแม่น้ำ', 'desc_en' => 'Flowing Stream', 'desc_th' => 'ทัศนียภาพริมน้ำ'],
                'bfast' => ['icon' => '🍳', 'title_en' => 'Free Breakfast', 'title_th' => 'ฟรีอาหารเช้า', 'desc_en' => 'Morning Meal', 'desc_th' => 'อาหารเช้าแสนอร่อย'],
                'boat' => ['icon' => '🚣', 'title_en' => 'Free Boat Ride', 'title_th' => 'ฟรีล่องเรือ', 'desc_en' => 'Lake Tour', 'desc_th' => 'สัมผัสบรรยากาศทะเลสาบ']
            ];

            $display_amenities = [];
            foreach ($db_amenities as $code) {
                $code = trim($code);
                if (isset($all_amenities_def[$code])) {
                    $display_amenities[] = $all_amenities_def[$code];
                }
            }
            
            // Fallback if no amenities found in DB
            if (empty($display_amenities)) {
                $display_amenities = [
                    $all_amenities_def['coffee'], $all_amenities_def['wifi'], $all_amenities_def['bathtub'],
                    $all_amenities_def['kingbed'], $all_amenities_def['minibar'], $all_amenities_def['smarttv']
                ];
            }

            // Show all amenities in room page
            // $display_amenities = array_slice($display_amenities, 0, 8);

            foreach($display_amenities as $item): ?>
            <div class="glassmorphism border border-white/20 theme-border-glow p-5 rounded-xl hover:-translate-y-2 transition-transform duration-300 theme-hover-glow cursor-pointer relative overflow-hidden flex flex-row items-start gap-4 bg-black/40">
                <div class="text-3xl theme-text-glow font-cn leading-none mt-1"><?= $item['icon'] ?></div>
                <div>
                    <h4 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-base text-white font-bold tracking-wider mb-1"><?= $item['title_'.$lang] ?></h4>
                    <p class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-prompt' ?> text-gray-400 text-xs font-light tracking-wide"><?= $item['desc_'.$lang] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </section>



    <?php if(!empty($room_reviews)): ?>
    <section class="py-20 relative z-10 gs-fade-up overflow-hidden">
        <div class="text-center mb-12 px-6 max-w-7xl mx-auto">
            <h2 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-3xl md:text-4xl theme-text-glow uppercase tracking-widest"><?= $lang == 'th' ? 'รีวิวจากผู้เข้าพัก' : 'Guest Reviews' ?></h2>
        </div>
        <div class="marquee-wrapper">
            <div class="marquee-track flex gap-8">
                <?php 
                $count = count($room_reviews);
                $total_items = max(6, $count * 2); // Ensure enough items for continuous scroll
                for($i=0; $i<$total_items; $i++): 
                    $rv = $room_reviews[$i % $count];
                ?>
                <div class="flex flex-col h-[380px] md:h-[420px] flex-shrink-0 w-[350px] md:w-[450px] bg-gradient-to-br from-[#1c1c1c] to-[#0a0a0a] p-10 theme-border-always rounded-2xl shadow-2xl relative review-card">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> font-bold text-sm text-amber-500 uppercase tracking-widest"><?= htmlspecialchars($rv['first_name'] . ' ' . mb_substr($rv['last_name'], 0, 1)) ?>.</h4>
                        <span class="text-[10px] text-gray-500 uppercase tracking-widest font-prompt"><?= date('M Y', strtotime($rv['created_at'])) ?></span>
                    </div>
                    <div class="flex gap-1 text-sm text-amber-500 mb-6 theme-text-glow tracking-widest">
                        <?php for($star=1; $star<=5; $star++) { echo $star <= $rv['rating'] ? '★' : '<span class="text-gray-700">★</span>'; } ?>
                    </div>
                    <?php $display_comment = ($lang == 'en' && !empty($rv['english_comment'])) ? $rv['english_comment'] : $rv['comment']; ?>
                    <p class="<?= $lang == 'th' ? 'font-serif-thai font-light' : 'font-cinzel italic' ?> text-white text-lg md:text-xl leading-relaxed whitespace-normal mb-4 line-clamp-3">"<?= htmlspecialchars($display_comment) ?>"</p>
                    
                    <?php 
                    $images = [];
                    if (!empty($rv['image_path'])) {
                        $decoded = json_decode($rv['image_path'], true);
                        if (is_array($decoded)) {
                            $images = $decoded;
                        } else {
                            $images = [$rv['image_path']];
                        }
                    }
                    if (!empty($images)): 
                    ?>
                    <div class="flex gap-1 z-10 relative mt-auto w-full overflow-hidden rounded-xl border border-white/10">
                        <?php foreach($images as $idx => $img): 
                            $img_src = (strpos($img, '../') === 0) ? $img : '../' . $img;
                        ?>
                            <?php if($idx < 3): ?>
                            <a href="<?= htmlspecialchars($img_src) ?>" data-fancybox="review-<?= $rv['id'] ?>" class="flex-1 min-w-0 cursor-pointer group/img relative block">
                                <div class="h-24 md:h-32 w-full relative overflow-hidden bg-black/50">
                                    <img src="<?= htmlspecialchars($img_src) ?>" class="w-full h-full object-cover opacity-80 group-hover/img:opacity-100 group-hover/img:scale-110 transition-all duration-500">
                                    <?php if($idx === 2 && count($images) > 3): ?>
                                    <div class="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-[2px]">
                                        <span class="text-white font-bold text-xl">+<?= count($images) - 3 ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php else: ?>
                            <a href="<?= htmlspecialchars($img_src) ?>" data-fancybox="review-<?= $rv['id'] ?>" style="display:none;"></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="mt-4"></div>
                    <?php endif; ?>

                    <div class="absolute -bottom-4 -right-4 text-8xl font-cn text-white/5 pointer-events-none">印</div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section id="booking" class="py-24 px-6 max-w-4xl mx-auto relative z-10 gs-fade-up mb-20">
        <div class="watermark-cn top-10 left-[-5%] opacity-10">预订</div>
        <div class="w-full relative z-10 bg-[#0a0a0a] theme-border-always rounded-3xl p-6 md:p-10 shadow-2xl glass-panel overflow-hidden">
            <div class="absolute top-8 right-10 text-6xl text-red-700/40 font-cn border-2 border-red-700/40 p-2 opacity-30 transform rotate-[-15deg]">茶</div>
            
            <div class="text-center mb-8">
                <h2 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-3xl md:text-4xl theme-text-glow uppercase tracking-widest"><?= $t['live_pricing'] ?></h2>
            </div>
            
            <div class="glass-panel rounded-[20px] md:rounded-[24px] border border-amber-600/80 shadow-[0_0_15px_rgba(217,119,6,0.4)] overflow-hidden bg-[#050505] p-2 md:p-6 relative mb-10 w-full max-w-5xl mx-auto">
                <div class="overflow-x-auto rounded-xl">
                    <table class="w-full text-left text-white border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-amber-600/10 border-b border-amber-500/30">
                                <th class="py-5 px-6 font-bold text-amber-500 uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'จำนวนผู้เข้าพัก' : 'Capacity' ?></th>
                                <th class="py-5 px-6 font-bold text-gray-200 uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'วันธรรมดา (จ.-พฤ.)' : 'Weekday (Mon-Thu)' ?></th>
                                <th class="py-5 px-6 font-bold text-gray-200 uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'วันหยุด (ศ.-อา.)' : 'Weekend (Fri-Sun)' ?></th>
                                <th class="py-5 px-6 font-bold text-rose-400 uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'หยุดนักขัตฤกษ์' : 'Public Holiday' ?></th>
                                <th class="py-5 px-6 font-bold text-[#00d0ff] uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'เตียงเสริม' : 'Extra Bed' ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <!-- 2 Guests -->
                            <tr class="hover:bg-white/5 transition-colors duration-300">
                                <td class="py-5 px-6 font-bold text-white <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>">
                                    <div class="flex items-center gap-3">
                                        <i class="ph-fill ph-users text-xl text-amber-500/70"></i>
                                        <span><?= $lang == 'th' ? 'สำหรับ 2 ท่าน' : 'For 2 Guests' ?></span>
                                    </div>
                                </td>
                                <td class="py-5 px-6 font-light text-gray-300 tracking-wider">฿<?= $r['price_weekday'] ?></td>
                                <td class="py-5 px-6 font-light text-gray-300 tracking-wider">฿<?= $r['price_weekend'] ?></td>
                                <td class="py-5 px-6 font-medium text-rose-300 tracking-wider">฿<?= $r['price_holiday'] ?></td>
                                <td class="py-5 px-6 font-light text-[#00d0ff]/80 tracking-wider">฿<?= $r['extra_bed_price'] ?></td>
                            </tr>
                            
                            <!-- 4 Guests -->
                            <?php if($r['max_guests'] >= 4): ?>
                            <tr class="hover:bg-white/5 transition-colors duration-300">
                                <td class="py-5 px-6 font-bold text-white <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>">
                                    <div class="flex items-center gap-3">
                                        <i class="ph-fill ph-users-four text-xl text-amber-500/70"></i>
                                        <span><?= $lang == 'th' ? 'สำหรับ 4 ท่าน' : 'For 4 Guests' ?></span>
                                    </div>
                                </td>
                                <td class="py-5 px-6 font-light text-gray-300 tracking-wider">฿<?= $r['price_4p_base'] ?></td>
                                <td class="py-5 px-6 font-light text-gray-300 tracking-wider">฿<?= $r['price_4p_high'] ?></td>
                                <td class="py-5 px-6 font-medium text-rose-300 tracking-wider">฿<?= $r['price_4p_holiday'] ?></td>
                                <td class="py-5 px-6 font-light text-[#00d0ff]/80 tracking-wider">฿<?= $r['extra_bed_price_4p'] ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="max-w-md mx-auto">
                <button onclick="window.location.href='../booking.php?room=<?= $type ?>'" class="w-full btn-book-now <?= $lang == 'th' ? 'font-serif-thai font-bold text-xl' : 'font-cinzel font-bold tracking-[0.2em] text-xl' ?> py-6 uppercase rounded-2xl transition-all flex justify-center items-center gap-3">
                    <span><?= $lang == 'th' ? 'จองห้องพักเลย' : 'BOOK NOW' ?></span>
                    <i class="ph-bold ph-arrow-right text-2xl"></i>
                </button>
            </div>
            
        </div>
    </section>

    <?php include '../components/footer.php'; ?>

    <div class="fixed bottom-0 left-0 w-full z-50 md:hidden glass-panel border-t border-white/10 flex">
        <?php foreach($rooms as $key => $roomData): ?>
        <a href="?type=<?= $key ?>&lang=<?= $lang ?>" class="flex-1 py-4 text-center border-r border-white/5 last:border-0 <?= $type == $key ? 'bg-white/10' : '' ?>">
            <span class="block font-cn text-xs text-white"><?= $roomData['name_cn'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <script src="main.js"></script>
    <script src="../js/chatbot.js?v=<?= time() ?>"></script>
    
    <!-- FULLSCREEN LIGHTBOX POPUP -->
    <div id="lightbox-modal" class="fixed inset-0 z-[10000] bg-black/95 backdrop-blur-xl hidden flex items-center justify-center p-4 transition-opacity duration-300" onclick="closeLightbox(event)">
        <button id="lightbox-close" class="absolute top-6 right-6 text-white hover:text-amber-500 transition-colors p-2 text-4xl font-bold z-[10001]">&times;</button>
        <img id="lightbox-img" src="" class="max-w-full max-h-[90vh] object-contain rounded-xl border border-white/10 shadow-2xl scale-95 transition-transform duration-300" alt="Enlarged View">
    </div>

    <script>
        function openLightbox(src) {
            const modal = document.getElementById('lightbox-modal');
            const img = document.getElementById('lightbox-img');
            img.src = src;
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            setTimeout(() => {
                img.classList.remove('scale-95');
                img.classList.add('scale-100');
            }, 10);
            if(typeof lenis !== 'undefined') lenis.stop();
        }
        function closeLightbox(e) {
            const modal = document.getElementById('lightbox-modal');
            if (e && e.target.id === 'lightbox-img') return;
            modal.classList.add('hidden');
            modal.style.display = 'none';
            if(typeof lenis !== 'undefined') lenis.start();
        }
    </script>

    <!-- Fancybox JS -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        Fancybox.bind("[data-fancybox]", {
            Thumbs: {
                autoStart: true
            },
            Toolbar: {
                display: {
                    left: ["infobar"],
                    middle: ["zoomIn", "zoomOut", "toggle1to1"],
                    right: ["slideshow", "fullscreen", "close"],
                }
            }
        });
    </script>
</body>
</html>
