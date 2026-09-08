<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$skip_preloader = true;

// ==========================================
// 1. ระบบจัดการภาษา (TH / EN)
// ==========================================
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] :  'th';

// ==========================================
// 1.5 โหลดตั้งค่าจากฐานข้อมูล
// ==========================================
// Removed legacy SQLite path
$site_settings = [];
if (true) {
    try {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdoSettings = $conn;
        $pdoSettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt_settings = $pdoSettings->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) {
            $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    } catch (Exception $e) {}
}
$hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'Yuncha Valley';
$raw_phone = isset($site_settings['phone']) ? $site_settings['phone'] :  "080-342-9396 ( คุณเต้ )\n063-915-0091 ( คุณมิ้ง )\n053-123-456 ( RESORT )";
$display_phone = $lang === 'en' ? str_replace(['คุณเต้', 'คุณมิ้ง'], ['Mr. Tae', 'Ms. Ming'], $raw_phone) : $raw_phone;

// ==========================================
// 2. คลังข้อความ (Dictionary)
// ==========================================
$text = [
    'th' => [],
    'en' => []
];

// Fallback default dictionary
$default_text = [
    'th' => [
        'nav_home' => 'หน้าแรก', 'nav_rooms' => 'ห้องพัก', 'nav_exp' => 'กิจกรรม', 'nav_policy' => 'นโยบาย', 'nav_contact' => 'ติดต่อที่พัก', 'nav_book' => 'จองห้องพัก',
        'nav_signin' => 'เข้าสู่ระบบ',
        'hero_title' => 'YUNCHA VALLEY', 
        'hero_desc' => 'สัมผัสความงามเหนือกาลเวลาที่หมู่บ้านรักไทย สถาปัตยกรรมจีนยูนนานที่ผสานกับความหรูหราอย่างสมบูรณ์แบบ',
        'about_sub' => 'Our Story & Heritage', 'about_title' => 'The Legend of', 'about_title2' => 'Ban Rak Thai',
        'about_desc1' => 'หมู่บ้านรักไทย ถูกก่อตั้งขึ้นโดยอดีตทหารจีนคณะชาติ (กองพล 93) ท่ามกลางหุบเขาสูงกว่า 1,200 เมตร YUNCHA VALLEY สร้างขึ้นด้วยความตั้งใจที่จะรักษาเอกลักษณ์ "บ้านดินเหนียว" และวัฒนธรรมการดื่มชาอันเก่าแก่',
        'about_desc2' => 'ที่นี่ไม่ใช่แค่ที่พัก แต่คือการหลีกหนีความวุ่นวาย เพื่อกลับคืนสู่ความสงบ คุณจะได้ตื่นมาพบกับสายหมอกที่ลอยเหนือทะเลสาบ และสัมผัสการบริการระดับ 5 ดาว',
        'villa_sub' => '✦ ROOMS', 'villa_title' => 'ห้องพักที่คัดสรรเพื่อคุณ', 'villa_desc' => 'ห้องพัก ออกแบบเพื่อให้คุณดื่มด่ำกับธรรมชาติ',
        'exp_sub' => '✦ Experiences', 'exp_title' => 'กิจกรรมระหว่างพัก', 'exp_desc' => 'ประสบการณ์ที่จะทำให้คุณหลงรักหมู่บ้านรักไทย',
        'loc_sub' => 'FIND US', 'loc_title' => 'หาเราเจอได้ที่', 'loc_desc' => 'หมู่บ้านรักไทย หมู่บ้านชาวจีนยูนนานใจกลางหุบเขาแม่ฮ่องสอน',
        'loc_address_title' => 'ที่อยู่', 'loc_address' => isset($site_settings['address']) ? $site_settings['address'] :  'หมู่บ้านรักไทย ต.หมอกจำแป่ อ.เมือง จ.แม่ฮ่องสอน 58000',
        'loc_phone_title' => 'เบอร์โทรศัพท์', 'loc_phone' => $display_phone,
        'loc_email_title' => 'อีเมล', 'loc_email' => isset($site_settings['email']) ? $site_settings['email'] :  'stay@yunchavalley.com',
        'loc_direction_title' => 'การเดินทาง', 'loc_dir_car' => 'รถยนต์ส่วนตัว: 45 กม. จากตัวเมืองแม่ฮ่องสอน (1 ชม. 15 นาที)', 'loc_dir_plane' => 'เครื่องบิน: สนามบินแม่ฮ่องสอน + เช่ารถ 1 ชม.',
        'btn_map' => 'เปิดใน Google Maps', 'per_night' => 'ราคาเริ่มต้น / คืน', 'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. ALL RIGHTS RESERVED.', 'btn_details' => 'ดูรายละเอียด'
    ],
    'en' => [
        'nav_home' => 'Home', 'nav_rooms' => 'Rooms', 'nav_exp' => 'Experiences', 'nav_policy' => 'Policies', 'nav_contact' => 'Contact', 'nav_book' => 'Booking',
        'nav_signin' => 'Sign In',
        'hero_title' => 'YUNCHA VALLEY', 
        'hero_desc' => 'Immerse yourself in luxury above the mist at Ban Rak Thai. Authentic Yunnan architecture blended with modern elegance.',
        'about_sub' => 'Our Story & Heritage', 'about_title' => 'The Legend of', 'about_title2' => 'Ban Rak Thai',
        'about_desc1' => 'Founded by Kuomintang soldiers (93rd Division) at 1,200 meters altitude, YUNCHA VALLEY preserves the unique heritage of earthen houses and ancient tea culture.',
        'about_desc2' => 'More than just a stay, it is an escape to tranquility. Wake up to the mist over the lake and experience authentic 5-star Yunnan hospitality.',
        'villa_sub' => '✦ ROOMS', 'villa_title' => 'Sanctuaries handpicked for you', 'villa_desc' => 'Three room styles, each designed to immerse you in nature',
        'exp_sub' => '✦ Experiences', 'exp_title' => 'Things to do during your stay', 'exp_desc' => 'experiences that make Ban Rak Thai unforgettable',
        'loc_sub' => 'FIND US', 'loc_title' => 'Find us here', 'loc_desc' => 'Ban Rak Thai, a Yunnan-Chinese village in the heart of Mae Hong Son\'s mountains.',
        'loc_address_title' => 'Address', 'loc_address' => isset($site_settings['address']) ? $site_settings['address'] :  'Ban Rak Thai, Mok Champae, Mueang, Mae Hong Son 58000',
        'loc_phone_title' => 'Phone', 'loc_phone' => isset($site_settings['phone']) ? $site_settings['phone'] :  '+66 80 342 9396',
        'loc_email_title' => 'Email', 'loc_email' => isset($site_settings['email']) ? $site_settings['email'] :  'stay@yunchavalley.com',
        'loc_direction_title' => 'Directions', 'loc_dir_car' => 'By Car: 45 km from Mae Hong Son city (1 hr 15 mins)', 'loc_dir_plane' => 'By Air: Mae Hong Son Airport + 1 hr drive',
        'btn_map' => 'Open in Google Maps', 'per_night' => 'Per Night', 'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. ALL RIGHTS RESERVED.', 'btn_details' => 'View Details'
    ]
];

// Try to load dynamic settings and rooms from backend
// Removed legacy SQLite path
$site_settings = [];
$room_types_db = [];
$db_images = [];
$db_amenities = [];
$villas = [];

try {
    if (true) {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdo = $conn;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // removed sql_mode
        
        // Load Settings
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) {
            $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Load Room Types with max_guests and details from related rooms
        $stmt_rooms = $pdo->query("
            SELECT rt.id, rt.type_name, rt.base_price, rt.high_price, rt.holiday_price,
                   (SELECT base_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_base,
                   (SELECT high_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_high,
                   (SELECT holiday_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_holiday,
                   MAX(r.max_guests) as max_guests, 
                   MAX(r.extra_bed_price) as extra_bed_price,
                   MAX(r.details) as details
            FROM room_types rt
            LEFT JOIN rooms r ON rt.id = r.room_type_id
            GROUP BY rt.id
            ORDER BY rt.id ASC
        ");
        if ($stmt_rooms) {
            $room_types_db = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);
        }

        // Load Room Images
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
                $db_images[$tid][] = ltrim($img['image_path'], '/');
            }
        }

        // Load Room Amenities
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
        // Load Approved Reviews
        $stmt_reviews = $pdo->query("
            SELECT r.rating, r.comment, r.english_comment, r.created_at, r.image_path, c.first_name, c.last_name, r.review_type, r.target_id, rt.type_name as room_name, p.name as activity_name
            FROM reviews r
            JOIN customers c ON r.customer_id = c.id
            LEFT JOIN room_types rt ON r.review_type = 'room' AND r.target_id = rt.id
            LEFT JOIN packages p ON r.review_type = 'activity' AND r.target_id = p.id
            WHERE r.is_approved = 1 AND r.rating >= 4
            ORDER BY r.created_at DESC LIMIT 10
        ");
        if ($stmt_reviews) {
            $approved_reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // Ignore DB errors
}

// ==========================================
// Fallback Reviews if database is empty
// ==========================================
if (empty($approved_reviews)) {
    $approved_reviews = [
        [
            'first_name' => 'John', 'last_name' => 'Doe', 'created_at' => '2026-05-15 10:00:00',
            'rating' => 5, 'comment' => 'บรรยากาศดีมาก ห้องพักสะอาด พนักงานบริการประทับใจสุดๆ จะกลับมาอีกแน่นอนครับ', 'english_comment' => 'Great atmosphere, clean rooms, and very impressive service. Will definitely come back.',
            'image_path' => json_encode(['img/m5.jpg', 'img/m6.jpg']), 'review_type' => 'room', 'target_id' => 3, 'room_name' => 'Peak Residence', 'activity_name' => null
        ],
        [
            'first_name' => 'สมหญิง', 'last_name' => 'ใจดี', 'created_at' => '2026-06-05 14:30:00',
            'rating' => 5, 'comment' => 'กิจกรรมล่องเรือตอนเช้าหมอกสวยมากค่ะ เหมือนหลุดไปในซีรีส์จีนเลย ประทับใจมาก', 'english_comment' => 'The morning boat ride had beautiful mist. It felt like stepping into a Chinese series. Very impressive.',
            'image_path' => json_encode(['img/m5.jpg']), 'review_type' => 'activity', 'target_id' => 1, 'room_name' => null, 'activity_name' => 'ล่องเรือกลางหมอก'
        ],
        [
            'first_name' => 'Michael', 'last_name' => 'Smith', 'created_at' => '2026-06-10 09:15:00',
            'rating' => 5, 'comment' => 'อาหารอร่อยมาก ชุดชาที่จัดเตรียมไว้ให้ก็หอมและรสชาติดี ห้องพักตกแต่งสวยงามลงตัว', 'english_comment' => 'The food was delicious, and the tea set provided was fragrant and tasty. The room decoration is perfectly beautiful.',
            'image_path' => json_encode(['img/m3.jpg', 'img/m4.jpg']), 'review_type' => 'room', 'target_id' => 4, 'room_name' => 'Lake Pavilion', 'activity_name' => null
        ],
        [
            'first_name' => 'ณัฐพล', 'last_name' => 'รักไทย', 'created_at' => '2026-06-12 16:45:00',
            'rating' => 4, 'comment' => 'วิวจากห้องพักสวยมาก เงียบสงบ เหมาะกับการพักผ่อนอย่างแท้จริง เตียงนอนนุ่มสบาย', 'english_comment' => 'The view from the room is very beautiful. Quiet and perfect for a real relaxation. The bed is very soft and comfortable.',
            'image_path' => json_encode(['img/m2.jpg', 'img/m6.jpg']), 'review_type' => 'room', 'target_id' => 5, 'room_name' => 'Tea Valley', 'activity_name' => null
        ]
    ];
}

// Merge missing keys from fallback just in case
$text['th'] = array_merge($default_text['th'], $text['th']);
$text['en'] = array_merge($default_text['en'], $text['en']);

// Override contact info with dynamic settings if exist
if (!empty($site_settings['phone'])) {
    $text['th']['loc_phone'] = $site_settings['phone'];
    $text['en']['loc_phone'] = $site_settings['phone'];
}
if (!empty($site_settings['email'])) {
    $text['th']['loc_email'] = $site_settings['email'];
    $text['en']['loc_email'] = $site_settings['email'];
}
if (!empty($site_settings['address'])) {
    $text['th']['loc_address'] = $site_settings['address'];
    $text['en']['loc_address'] = $site_settings['address'];
}
$hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  "YUNCHA VALLEY";
if (!empty($site_settings['hotel_name'])) {
    $text['th']['hero_title'] = strtoupper($hotel_name);
    $text['en']['hero_title'] = strtoupper($hotel_name);
}
$raw_map_url = !empty($site_settings['map_url']) ? trim($site_settings['map_url']) : "";

// Default embed map for Rak Thai Village
$default_embed = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15024.1234!2d97.931!3d19.584!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTnCsDM1JzAyLjQiTiA5N8KwNTUnNTEuNiJF!5e0!3m2!1sen!2sth!4v1600000000000!5m2!1sen!2sth";
$map_iframe_url = $default_embed;
$map_link_url = "https://www.google.com/maps/search/?api=1&query=หมู่บ้านรักไทย+แม่ฮ่องสอน";

if (!empty($raw_map_url)) {
    if (strpos($raw_map_url, '<iframe') !== false && preg_match('/src=["\']([^"\']+)["\']/', $raw_map_url, $matches)) {
        $map_iframe_url = $matches[1];
        $map_link_url = str_replace('/embed', '', $map_iframe_url);
    } else if (strpos($raw_map_url, '/embed') !== false) {
        $map_iframe_url = $raw_map_url;
        $map_link_url = str_replace('/embed', '', $raw_map_url);
    } else {
        // User pasted a raw short link (e.g., maps.app.goo.gl)
        $map_link_url = $raw_map_url;
        // Cannot iframe a short link, use default embed
        $map_iframe_url = $default_embed;
    }
}

$t = $text[$lang];

// ==========================================
// 3. ข้อมูลเนื้อหา (ดึงราคาและชื่อจากฐานข้อมูล)
// ==========================================
foreach ($room_types_db as $rt) {
    $tid = $rt['id'];
    $img = !empty($db_images[$tid]) ? $db_images[$tid][0] : "img/m8.png";
    $am_list = isset($db_amenities[$tid]) ? $db_amenities[$tid] :  ["wifi", "tv", "air", "bath", "breakfast"];
    
    // Split details
    $raw_details = explode("|||", isset($rt['details']) ? $rt['details'] :  "");
    $index_details = isset($raw_details[0]) ? $raw_details[0] :  "";
    $details_parts = explode("\n", $index_details);
    $sub_th = trim(isset($details_parts[0]) ? $details_parts[0] :  "รายละเอียดห้องพัก");
    $sub_en = trim(isset($details_parts[1]) ? $details_parts[1] :  $sub_th);
    $sub = ($lang == 'th') ? $sub_th : $sub_en;
    
    $max_g = (int)(isset($rt['max_guests']) ? $rt['max_guests'] :  2);
    $guests_str = ($lang == 'th') ? "พักได้ 2-{$max_g} คน" : "Sleeps 2-{$max_g} Guests";
    if ($max_g == 2 || $max_g == 0) {
        $guests_str = ($lang == 'th') ? "พักได้ 2 คน" : "Sleeps 2 Guests";
    }
    
    // Determine bed, size and view from amenities
    $size_val = 32;
    $view_th = 'วิวไร่ชา';
    $view_en = 'Tea View';
    if (in_array('mtview', isset($db_amenities[$tid]) ? $db_amenities[$tid] :  [])) {
        $size_val = 45;
        $view_th = 'วิวภูเขา';
        $view_en = 'Mountain View';
    } else if (in_array('rvview', isset($db_amenities[$tid]) ? $db_amenities[$tid] :  [])) {
        $size_val = 38;
        $view_th = 'วิวแม่น้ำ';
        $view_en = 'River View';
    }
    
    $size = "{$size_val} ตร.ม.";
    $view = $view_th;
    
    // Adjust bed text
    $bed = 'เตียงคิงไซส์';
    
    if ($lang == 'en') {
        $size = "{$size_val} sq.m.";
        $view = $view_en;
        $bed = 'King Bed';
    }
    
    $villas[] = [
        "id" => $tid, 
        "name" => $rt['type_name'],
        "sub" => $sub,
        "price" => number_format($rt['base_price']),
        "base_price_raw" => (int)$rt['base_price'],
        "extra_bed_price" => (int)($rt['extra_bed_price'] ?: 500),
        "max_guests" => $max_g,
        "guests" => $guests_str,
        "size" => $size,
        "view" => $view,
        "bed" => $bed,
        "rating" => "5.0 (42 Reviews)",
        "img" => $img,
        "gallery" => !empty($db_images[$tid]) ? $db_images[$tid] : [$img],
        "amenities" => array_slice($am_list, 0, 12) // Show up to 12 amenities
    ];
}

// ==========================================
// [แก้ไข] ข้อมูลกิจกรรม 5 อย่าง
// ==========================================
// Removed legacy SQLite path
$db_activities = [];
if (true) {
    try {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdoAct = $conn;
        $pdoAct->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_activities = $pdoAct->query("SELECT * FROM activities ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$activities = [
    [
        "title_th" => !empty($db_activities[0]['title_th']) ? $db_activities[0]['title_th'] : "ล่องเรือกลางหมอก", 
        "title_en" => !empty($db_activities[0]['title_en']) ? $db_activities[0]['title_en'] : "Misty Lake Cruise", 
        "desc_th" => !empty($db_activities[0]['sub_th']) ? $db_activities[0]['sub_th'] : "ชมวิวทะเลสาบและถ่ายรูปบรรยากาศยามเช้าในม่านหมอกหนา",
        "desc_en" => !empty($db_activities[0]['sub_en']) ? $db_activities[0]['sub_en'] : "Enjoy the lake view and capture the morning atmosphere in thick mist.",
        "img" => "img/m5.jpg"
    ],
    [
        "title_th" => !empty($db_activities[1]['title_th']) ? $db_activities[1]['title_th'] : "ชุดชาจีนยูนนานพรีเมียม", 
        "title_en" => !empty($db_activities[1]['title_en']) ? $db_activities[1]['title_en'] : "Premium Yunnan Tea Set", 
        "desc_th" => !empty($db_activities[1]['sub_th']) ? $db_activities[1]['sub_th'] : "เซ็ตชาอู่หลงพร้อมขนมและมุมจิบชาวิวภูเขา",
        "desc_en" => !empty($db_activities[1]['sub_en']) ? $db_activities[1]['sub_en'] : "Oolong tea set with snacks and a mountain-view sipping corner.",
        "img" => "img/m3.jpg"
    ],
    [
        "title_th" => !empty($db_activities[2]['title_th']) ? $db_activities[2]['title_th'] : "เดินชมไร่ชา", 
        "title_en" => !empty($db_activities[2]['title_en']) ? $db_activities[2]['title_en'] : "Tea Plantation Walk", 
        "desc_th" => !empty($db_activities[2]['sub_th']) ? $db_activities[2]['sub_th'] : "พักผ่อน ถ่ายรูป และชมธรรมชาติรอบที่พัก",
        "desc_en" => !empty($db_activities[2]['sub_en']) ? $db_activities[2]['sub_en'] : "Relax, take photos, and admire the nature around the resort.",
        "img" => "img/m6.jpg"
    ],
    [
        "title_th" => !empty($db_activities[3]['title_th']) ? $db_activities[3]['title_th'] : "ชุดถ่ายรูปจีนยูนนาน", 
        "title_en" => !empty($db_activities[3]['title_en']) ? $db_activities[3]['title_en'] : "Yunnan Photo Shoot", 
        "desc_th" => !empty($db_activities[3]['sub_th']) ? $db_activities[3]['sub_th'] : "อุปกรณ์ครบ ร่ม พัด โคม เลือกชุดเดี่ยว/คู่/พร้อมช่างภาพ",
        "desc_en" => !empty($db_activities[3]['sub_en']) ? $db_activities[3]['sub_en'] : "Full props: umbrella, fan, lantern. Choose solo/couple outfits with a photographer.",
        "img" => "img/m2.jpg"
    ],
    [
        "title_th" => !empty($db_activities[4]['title_th']) ? $db_activities[4]['title_th'] : "หม้อไฟยูนนาน / ปิ้งย่าง", 
        "title_en" => !empty($db_activities[4]['title_en']) ? $db_activities[4]['title_en'] : "Yunnan Hotpot / BBQ", 
        "desc_th" => !empty($db_activities[4]['sub_th']) ? $db_activities[4]['sub_th'] : "ชุดอาหารสำหรับคู่รักหรือครอบครัว สูตรต้นตำรับยูนนาน",
        "desc_en" => !empty($db_activities[4]['sub_en']) ? $db_activities[4]['sub_en'] : "Meal sets for couples or families, authentic Yunnan recipe.",
        "img" => "img/m4.jpg"
    ]
];

function getRoomAmenityHTML($type, $lang) {
    // Map DB amenity codes to Phosphor Icon names and Text
    $map = [
        'wifi' => ['icon' => 'wifi-high', 'th' => 'ฟรี Wi-Fi', 'en' => 'Free Wi-Fi'],
        'ac' => ['icon' => 'snowflake', 'th' => 'แอร์', 'en' => 'AC'],
        'heater' => ['icon' => 'thermometer-hot', 'th' => 'น้ำอุ่น', 'en' => 'Heater'],
        'bathtub' => ['icon' => 'bathtub', 'th' => 'อ่างอาบน้ำ', 'en' => 'Bathtub'],
        'nosmoke' => ['icon' => 'warning-circle', 'th' => 'ปลอดบุหรี่', 'en' => 'No Smoking'],
        'smokearea' => ['icon' => 'cigarette', 'th' => 'สูบบุหรี่ได้', 'en' => 'Smoking Area'],
        'smarttv' => ['icon' => 'television', 'th' => 'สมาร์ททีวี', 'en' => 'Smart TV'],
        'minibar' => ['icon' => 'refrigerator', 'th' => 'มินิบาร์', 'en' => 'Mini Bar'],
        'coffee' => ['icon' => 'coffee', 'th' => 'ชุดชา', 'en' => 'Tea Set'],
        'hairdryer' => ['icon' => 'wind', 'th' => 'ไดร์เป่าผม', 'en' => 'Hair Dryer'],
        'teaview' => ['icon' => 'leaf', 'th' => 'วิวไร่ชา', 'en' => 'Tea View'],
        'mtview' => ['icon' => 'mountains', 'th' => 'วิวภูเขา', 'en' => 'Mountain View'],
        'rvview' => ['icon' => 'waves', 'th' => 'วิวแม่น้ำ', 'en' => 'River View'],
        'bfast' => ['icon' => 'cooking-pot', 'th' => 'อาหารเช้า', 'en' => 'Breakfast'],
        'boat' => ['icon' => 'boat', 'th' => 'ฟรีล่องเรือ', 'en' => 'Free Boat'],
        'kingbed' => ['icon' => 'bed', 'th' => 'เตียงคิงไซส์', 'en' => 'King Bed'],
        'twinbed' => ['icon' => 'bed', 'th' => 'เตียงแฝด', 'en' => 'Twin Bed']
    ];
    $type = trim($type);
    $data = isset($map[$type]) ? $map[$type] :  ['icon' => 'check', 'th' => 'มีบริการ', 'en' => 'Available'];

    return '<div class="flex items-center gap-1 md:gap-1.5 text-gray-400 hover:text-white transition-colors duration-300 pr-1">
                <i class="ph ph-' . $data['icon'] . ' text-base"></i>
                <span class="text-[11px] md:text-[12px] ' . ($lang == 'th' ? 'font-serif-thai' : 'font-prompt') . '">' . $data[$lang] . '</span>
            </div>';
}
?>
<?php include 'components/header.php'; ?>

    <section id="home" class="relative w-full h-[100svh] flex flex-col justify-center items-center bg-black px-4 overflow-hidden">
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <video autoplay loop muted playsinline id="hero-img" class="w-full h-full object-cover filter brightness-50">
                <source src="img/vi1.mp4" type="video/mp4">
            </video>
            <div class="sunlight-rays z-0"></div>
            <div class="fog-container z-0"><div class="fog-img-1"></div><div class="fog-img-2"></div></div>
            <div class="absolute bottom-0 left-0 w-full h-48 md:h-64 bg-gradient-to-b from-transparent to-[#020202] z-10"></div>
        </div>
        
        <div class="z-10 text-center relative pointer-events-none w-full mt-[10vh]">
            <div class="mask-wrap mb-4 md:mb-6 flex justify-center items-center gap-3">
                <span class="text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">✦</span>
                <p class="slide-txt hero-text text-white tracking-[0.3em] md:tracking-[0.4em] text-[10px] md:text-sm uppercase font-cinzel font-semibold drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                    Ban Rak Thai • Mae Hong Son
                </p>
                <span class="text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">✦</span>
            </div>
            <br>
            
            <div class="mask-wrap mb-4 flex flex-col items-center">
                <h1 class="slide-txt hero-text font-cinzel text-5xl md:text-7xl xl:text-8xl leading-tight text-white drop-shadow-[0_4px_15px_rgba(0,0,0,0.9)] uppercase tracking-wider">
                    <?= $t['hero_title'] ?>
                </h1>
                <span class="slide-txt hero-text font-prompt text-[10px] md:text-xs text-gray-300 uppercase tracking-[0.6em] mt-3 drop-shadow-md">RESORT</span>
            </div>
            <br>
            
            <div class="mask-wrap max-w-3xl mx-auto px-4 mt-2">
                <p class="slide-txt hero-text text-gray-200 font-light tracking-wide md:tracking-widest text-lg md:text-xl leading-relaxed md:leading-loose drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>">
                    <?= $t['hero_desc'] ?>
                </p>
            </div>
        </div>
    </section>

    <section id="about" class="pt-24 md:pt-32 pb-24 md:pb-32 px-6 md:px-12 max-w-7xl mx-auto relative z-10">
        <div class="flex flex-col md:flex-row items-center gap-12 md:gap-20">
            <div class="w-full md:w-1/2 flex flex-col items-center md:items-start text-center md:text-left">
                <div class="mask-wrap mb-4"><p class="slide-txt scroll-txt text-amber-600 tracking-[0.3em] text-xs uppercase font-cinzel"><?= $t['about_sub'] ?></p></div>
                <div class="mask-wrap mb-2"><h2 class="slide-txt scroll-txt font-cinzel text-3xl md:text-5xl"><?= $t['about_title'] ?></h2></div>
                <div class="mask-wrap mb-8 md:mb-10"><h2 class="slide-txt scroll-txt font-cinzel text-outline text-3xl md:text-5xl"><?= $t['about_title2'] ?></h2></div>
                
                <div class="mask-wrap mb-6"><p class="slide-txt scroll-txt text-gray-400 leading-relaxed text-sm md:text-base <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>"><?= $t['about_desc1'] ?></p></div>
                <div class="mask-wrap mb-8"><p class="slide-txt scroll-txt text-gray-400 leading-relaxed text-sm md:text-base <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>"><?= $t['about_desc2'] ?></p></div>
            </div>
            <div class="w-full md:w-1/2 relative h-[40vh] md:h-[60vh] mt-10 md:mt-0 rounded-[20px] overflow-hidden border-[1px] border-amber-500/20 shadow-[0_0_40px_rgba(217,119,6,0.1)] group">
                <div class="absolute inset-0 bg-[#020202]/20 z-10 group-hover:bg-transparent transition-colors duration-700 pointer-events-none"></div>
                <video autoplay loop muted playsinline class="w-full h-full object-cover parallax-img scale-105 transition-transform duration-1000 group-hover:scale-100">
                    <source src="img/Create_a_seamless_luxury_logo.mp4" type="video/mp4">
                </video>
            </div>
        </div>
    </section>

    <section id="villas" class="py-16 md:py-24 px-6 max-w-[1400px] w-full mx-auto border-t border-white/5 overflow-hidden">
        <div class="mb-16 md:mb-24 text-center flex flex-col items-center">
            <div class="mask-wrap mb-4"><p class="slide-txt scroll-txt text-amber-600 tracking-[0.3em] text-xs uppercase font-cinzel font-bold"><?= $t['villa_sub'] ?></p></div>
            <div class="mask-wrap mb-4"><h2 class="slide-txt scroll-txt <?= $lang == 'th' ? 'font-serif-thai font-semibold' : 'font-cinzel font-bold' ?> text-3xl md:text-4xl lg:text-5xl leading-normal py-2 text-white"><?= $t['villa_title'] ?></h2></div>
            <div class="mask-wrap"><p class="slide-txt scroll-txt text-gray-300 text-base md:text-lg <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>"><?= $t['villa_desc'] ?></p></div>
        </div>

        <div class="flex flex-col gap-12 md:gap-16 relative">
            <?php foreach($villas as $index => $v): 
                $isRev = ($index % 2 !== 0);
                $cardClass = "villa-card-" . ($index + 1);
                $maskClass = "villa-mask-" . ($index + 1);
                $parallaxClass = "villa-parallax-wrap-" . ($index + 1);
                $imgClass = "villa-reveal-img-" . ($index + 1);

                $clipPath = "";
                if ($index == 0) $clipPath = "clip-path: inset(0 100% 0 0); -webkit-clip-path: inset(0 100% 0 0);"; 
                else if ($index == 1) $clipPath = "clip-path: inset(0 0 0 100%); -webkit-clip-path: inset(0 0 0 100%);"; 
                else if ($index == 2) $clipPath = "clip-path: inset(100% 0 0 0); -webkit-clip-path: inset(100% 0 0 0);"; 
            ?>
            <div class="<?= $cardClass ?> flex flex-col <?= $isRev ? 'lg:flex-row-reverse' : 'lg:flex-row' ?> bg-[#050505] rounded-[20px] md:rounded-[24px] border border-amber-600/80 shadow-[0_0_15px_rgba(217,119,6,0.4)] overflow-hidden group transition-colors duration-500">
                
                <div class="w-full lg:w-[45%] relative flex flex-col p-6 md:p-8 lg:p-10 bg-[#050505] <?= $maskClass ?>" style="<?= $clipPath ?>">
                    <!-- Main Image container -->
                    <div id="gallery-container-<?= $v['id'] ?>" data-gallery='<?= json_encode($v['gallery']) ?>' data-current-index="0" class="relative w-full aspect-[4/3] lg:aspect-auto lg:flex-1 rounded-[16px] overflow-hidden mb-4 cursor-explore hover-target block group">
                        <a href="<?= $v['img'] ?>" data-fancybox="gallery-<?= $v['id'] ?>" id="main-link-<?= $v['id'] ?>" class="block w-full h-full">
                            <img id="main-img-<?= $v['id'] ?>" src="<?= $v['img'] ?>" class="absolute inset-0 w-full h-full object-cover transition-all duration-1000 group-hover:scale-[1.03] <?= $imgClass ?>" style="opacity: 0;">
                        </a>
                        
                        <!-- Nav Buttons -->
                        <button onclick="event.preventDefault(); event.stopPropagation(); navigateGallery('<?= $v['id'] ?>', -1)" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/40 hover:bg-black/70 backdrop-blur-sm rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300 z-30">
                            <i class="ph-bold ph-caret-left text-xl"></i>
                        </button>
                        <button onclick="event.preventDefault(); event.stopPropagation(); navigateGallery('<?= $v['id'] ?>', 1)" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/40 hover:bg-black/70 backdrop-blur-sm rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300 z-30">
                            <i class="ph-bold ph-caret-right text-xl"></i>
                        </button>
                        
                        <!-- Best Seller Badge -->
                        <div class="absolute top-4 left-4 bg-[#111]/80 backdrop-blur-md border border-white/5 px-4 py-2 rounded-full flex items-center gap-2 text-[10px] md:text-xs text-amber-500 font-bold uppercase tracking-widest z-20 shadow-lg pointer-events-none">
                            <i class="ph-fill ph-star"></i> Best Seller
                        </div>
                        
                        <!-- Expand Icon -->
                        <div class="absolute top-4 right-4 bg-black/50 backdrop-blur-sm border border-white/10 w-10 h-10 rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300 z-20 pointer-events-none">
                            <i class="ph ph-arrows-out-simple text-lg"></i>
                        </div>
                        
                        <!-- Image Count Badge -->
                        <div class="absolute bottom-4 left-4 bg-[#111]/80 backdrop-blur-md border border-white/5 px-4 py-2 rounded-full flex items-center gap-2 z-20 shadow-lg transition-colors pointer-events-none">
                            <i class="ph-bold ph-plus text-gray-400 text-[10px]"></i>
                            <span id="counter-<?= $v['id'] ?>" class="text-white text-[10px] md:text-xs font-bold tracking-wider">1 / <?= count($v['gallery']) ?></span>
                            <i class="ph-bold ph-aperture text-gray-400 ml-1 text-[10px]"></i>
                        </div>
                    </div>
                    
                    <!-- Hidden gallery images for Fancybox so they can be swiped through -->
                    <div style="display: none;">
                        <?php foreach($v['gallery'] as $gi => $g_img): 
                            if($gi == 0) continue; // Skip first image as it's already the main link
                        ?>
                        <a href="<?= $g_img ?>" data-fancybox="gallery-<?= $v['id'] ?>"></a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Thumbnails -->
                    <div id="thumbnails-<?= $v['id'] ?>" class="flex gap-2 md:gap-3 h-[60px] md:h-[75px] shrink-0 overflow-x-auto hide-scrollbar">
                        <?php foreach($v['gallery'] as $i => $t_img): ?>
                        <div onclick="changeMainImage('<?= $v['id'] ?>', '<?= $t_img ?>', <?= $i ?>)" class="thumbnail-item flex-shrink-0 w-[calc(25%-6px)] md:w-[calc(20%-9.6px)] rounded-[10px] md:rounded-[12px] overflow-hidden border-2 <?= $i === 0 ? 'border-amber-500/80 opacity-100' : 'border-transparent opacity-60 hover:opacity-100' ?> cursor-pointer transition-all relative group">
                            <img src="<?= $t_img ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php if($i === 4 && count($v['gallery']) > 5): ?>
                            <div class="absolute inset-0 bg-black/60 flex items-center justify-center hover:bg-black/40 transition-colors">
                                <i class="ph-bold ph-caret-right text-white"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php 
                        // Show max 5 thumbnails for clean layout, though they can scroll
                        if($i === 4) break; 
                        endforeach; 
                        ?>
                    </div>
                </div>
                
                <div class="w-full lg:w-[55%] flex flex-col justify-start p-6 md:p-8 lg:p-10 relative z-20 overflow-hidden bg-[#050505]">
                    <?php 
                    $room_chars = [
                        'tea-valley' => '茶',
                        'lake-pavilion' => '阁',
                        'peak-residence' => '云'
                    ];
                    $char = isset($room_chars[$v['id']]) ? $room_chars[$v['id']] :  '印';
                    ?>
                    <div class="absolute -bottom-6 -right-6 text-9xl font-cn text-white/5 pointer-events-none select-none z-0 transform translate-x-2 translate-y-2"><?= $char ?></div>
                    

                    
                    <div class="mb-4 border-b border-white/10 pb-4 w-full relative z-10">
                        <!-- 1. ชื่อห้อง -->
                        <div class="mask-wrap mb-2 w-full flex justify-center md:justify-start"><h3 class="slide-txt scroll-txt font-cinzel text-3xl md:text-4xl text-white uppercase whitespace-nowrap overflow-hidden text-ellipsis w-full text-center md:text-left"><?= $v['name'] ?></h3></div>
                        
                        <!-- 2. ข้อมูลห้องพัก (Size/Bed/Guests/View) -->
                        <div class="mask-wrap w-full flex justify-center md:justify-start">
                            <div class="slide-txt scroll-txt flex flex-wrap items-center justify-center md:justify-start gap-2 md:gap-4 text-[13px] md:text-sm text-amber-500 <?= $lang == 'th' ? 'font-serif-thai' : '' ?>">
                                <div class="flex items-center gap-1.5"><i class="ph ph-selection-background text-lg"></i> <span class="text-gray-300"><?= $v['size'] ?></span></div>
                                <span class="text-white/10">|</span>
                                <div class="flex items-center gap-1.5"><i class="ph ph-bed text-lg"></i> <span class="text-gray-300"><?= $v['bed'] ?></span></div>
                                <span class="text-white/10">|</span>
                                <div class="flex items-center gap-1.5"><i class="ph ph-users text-lg"></i> <span class="text-gray-300"><?= $v['guests'] ?></span></div>
                                <span class="text-white/10">|</span>
                                <div class="flex items-center gap-1.5"><i class="ph ph-mountains text-lg"></i> <span class="text-gray-300"><?= $v['view'] ?></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. คำบรรยาย (Subtitle) -->
                    <?php if(!empty($v['sub'])): ?>
                    <div class="mb-4 pb-4 border-b border-white/10 w-full relative z-10">
                        <div class="mask-wrap w-full"><p class="slide-txt scroll-txt text-[13px] md:text-[14px] text-gray-400 leading-relaxed <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?> text-center md:text-left line-clamp-2"><?= nl2br(htmlspecialchars($v['sub'])) ?></p></div>
                    </div>
                    <?php endif; ?>

                    <!-- 4. สิ่งอำนวยความสะดวก -->
                    <div class="flex flex-col gap-3 relative z-10 mb-4 w-full">
                        <div class="mask-wrap flex justify-center md:justify-start"><span class="slide-txt scroll-txt text-[13px] text-amber-500 <?= $lang == 'th' ? 'font-serif-thai font-bold' : '' ?>"><?= $lang == 'th' ? 'สิ่งอำนวยความสะดวก' : 'Amenities' ?></span></div>
                        <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-x-2 gap-y-1.5 content-start">
                            <?php foreach($v['amenities'] as $amenity): ?>
                            <div class="mask-wrap">
                                <div class="slide-txt scroll-txt group">
                                    <?= getRoomAmenityHTML($amenity, $lang) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 5. Booking Box (Form) -->
                    <div class="mask-wrap w-full relative z-10 p-4 -m-4 mt-2">
                        <form action="booking.php" method="GET" class="slide-txt scroll-txt block bg-[#0a0a0a]/80 backdrop-blur-md border border-amber-600/80 shadow-[0_0_15px_rgba(217,119,6,0.4)] rounded-2xl p-3 md:p-4 relative group w-full transition-all duration-500">
                            <input type="hidden" name="room" value="<?= $v['id'] ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 items-start">
                                <!-- Price -->
                                <div class="md:border-r border-white/5 flex flex-col justify-start text-center md:text-left">
                                    <span class="text-[10px] md:text-xs text-gray-500 mb-1 <?= $lang == 'th' ? 'font-serif-thai' : '' ?>"><?= $lang == 'th' ? 'เริ่มต้นเพียง' : 'Starting from' ?></span>
                                    <div class="flex items-baseline justify-center md:justify-start gap-1.5">
                                        <span id="price-<?= $v['id'] ?>" data-base-price="<?= $v['base_price_raw'] ?>" class="font-cinzel text-xl md:text-2xl text-white font-bold price-counter">0</span>
                                        <span class="text-[10px] md:text-xs text-gray-500 <?= $lang == 'th' ? 'font-serif-thai' : '' ?>"><?= $lang == 'th' ? 'บาท / คืน' : 'THB / Night' ?></span>
                                    </div>
                                </div>
                                <!-- Extra Bed -->
                                <div class="md:border-r border-white/5 md:px-2 flex flex-col justify-start text-center md:text-left">
                                    <span class="text-[10px] md:text-xs text-gray-500 mb-1 <?= $lang == 'th' ? 'font-serif-thai' : '' ?>"><?= $lang == 'th' ? 'เตียงเสริม' : 'Extra Bed' ?></span>
                                    <div class="flex items-baseline justify-center md:justify-start gap-1.5">
                                        <span class="text-[10px] text-amber-500 font-bold">+</span>
                                        <span data-base-price="<?= $v['extra_bed_price'] ?>" class="font-cinzel text-lg md:text-xl text-amber-500 font-bold price-counter">0</span>
                                        <span class="text-[10px] md:text-xs text-gray-500 <?= $lang == 'th' ? 'font-serif-thai' : '' ?>"><?= $lang == 'th' ? 'บาท / คืน' : 'THB / Night' ?></span>
                                    </div>
                                </div>
                                <div class="md:pl-2 flex flex-col justify-start text-center md:text-left items-center md:items-start">
                                    <span class="text-[10px] md:text-xs text-gray-500 mb-1 <?= $lang == 'th' ? 'font-serif-thai' : '' ?>"><?= $lang == 'th' ? 'ผู้เข้าพัก' : 'Guests' ?></span>
                                    <div class="flex items-center gap-1 p-1 bg-white/5 rounded-full border border-white/10 w-fit <?= $lang == 'th' ? 'font-serif-thai' : '' ?>">
                                        <label class="cursor-pointer relative mb-0">
                                            <input type="radio" name="guests_select_<?= $v['id'] ?>" value="2" class="peer sr-only" onchange="updatePrice(this, '<?= $v['id'] ?>')" checked>
                                            <div class="px-3 py-1 rounded-full text-[11px] md:text-xs font-bold text-gray-400 peer-checked:bg-amber-600 peer-checked:text-white peer-checked:shadow-[0_0_10px_rgba(217,119,6,0.4)] transition-all">
                                                <?= $lang == 'th' ? '2 คน' : '2 Guests' ?>
                                            </div>
                                        </label>
                                        <?php if($v['max_guests'] >= 4): ?>
                                        <label class="cursor-pointer relative mb-0">
                                            <input type="radio" name="guests_select_<?= $v['id'] ?>" value="4" class="peer sr-only" onchange="updatePrice(this, '<?= $v['id'] ?>')">
                                            <div class="px-3 py-1 rounded-full text-[11px] md:text-xs font-bold text-gray-400 peer-checked:bg-amber-600 peer-checked:text-white peer-checked:shadow-[0_0_10px_rgba(217,119,6,0.4)] transition-all">
                                                <?= $lang == 'th' ? '4 คน' : '4 Guests' ?>
                                            </div>
                                        </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-4 mt-4">
                                <button type="submit" class="relative group flex-1 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white py-2.5 md:py-3 rounded-xl text-[12px] md:text-[13px] tracking-widest uppercase flex items-center justify-center gap-2 overflow-hidden shadow-[0_0_15px_rgba(217,119,6,0.4)] hover:shadow-[0_0_25px_rgba(217,119,6,0.6)] transition-all duration-500 <?= $lang == 'th' ? 'font-serif-thai font-bold' : 'font-cinzel font-bold' ?>">
                                    <span class="absolute top-0 left-0 w-[150%] h-full bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-in-out skew-x-12"></span>
                                    <i class="ph-bold ph-calendar-check text-lg relative z-10"></i>
                                    <span class="relative z-10"><?= $lang == 'th' ? 'จองตอนนี้' : 'Book Now' ?></span>
                                </button>
                                <a href="room/room.php?id=<?= $v['id'] ?>" class="group flex-1 bg-[#0a0a0a]/80 hover:bg-amber-600/10 text-gray-300 hover:text-amber-500 border border-white/10 hover:border-amber-500/50 py-2.5 md:py-3 rounded-xl text-[12px] md:text-[13px] tracking-widest uppercase flex items-center justify-center gap-2 transition-all duration-300 <?= $lang == 'th' ? 'font-serif-thai font-bold' : 'font-cinzel font-bold' ?>">
                                    <span><?= $t['btn_details'] ?></span>
                                    <i class="ph-bold ph-arrow-right text-lg transform group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Pricing Table Section -->
    <section id="pricing" class="py-16 md:py-24 px-6 max-w-[1200px] w-full mx-auto border-t border-white/5 overflow-hidden">
        <div class="mb-12 md:mb-16 text-center flex flex-col items-center">
            <div class="mask-wrap mb-4"><p class="slide-txt scroll-txt text-amber-600 tracking-[0.3em] text-xs uppercase font-cinzel font-bold">✦ ROOM RATES</p></div>
            <div class="mask-wrap mb-4"><h2 class="slide-txt scroll-txt <?= $lang == 'th' ? 'font-serif-thai font-semibold' : 'font-cinzel font-bold' ?> text-3xl md:text-4xl lg:text-5xl leading-normal py-2 text-white"><?= $lang == 'th' ? 'ตารางราคาห้องพัก' : 'Room Rates' ?></h2></div>
            <div class="mask-wrap"><p class="slide-txt scroll-txt text-gray-300 text-base md:text-lg <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>"><?= $lang == 'th' ? 'ราคาอาจมีการเปลี่ยนแปลงในวันหยุดนักขัตฤกษ์ (ไม่รวมเตียงเสริม)' : 'Rates may vary on public holidays (Extra bed not included)' ?></p></div>
        </div>

        <div class="glass-panel rounded-[20px] md:rounded-[24px] border border-amber-600/80 shadow-[0_0_15px_rgba(217,119,6,0.4)] overflow-hidden bg-[#050505] p-2 md:p-6 relative">
            <div class="overflow-x-auto rounded-xl">
                <table class="w-full text-left text-white border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-amber-600/10 border-b border-amber-500/30">
                            <th class="py-5 px-6 font-bold text-amber-500 uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'ประเภทห้องพัก' : 'Room Type' ?></th>
                            <th class="py-5 px-6 font-bold text-gray-200 uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'วันธรรมดา (จ.-พฤ.)' : 'Weekday (Mon-Thu)' ?></th>
                            <th class="py-5 px-6 font-bold text-gray-200 uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'วันหยุด (ศ.-อา.)' : 'Weekend (Fri-Sun)' ?></th>
                            <th class="py-5 px-6 font-bold text-rose-400 uppercase tracking-widest text-sm <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $lang == 'th' ? 'หยุดนักขัตฤกษ์' : 'Public Holiday' ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php 
                        $view_map = array(
                            3 => ($lang == 'th' ? 'วิวภูเขา' : 'Mountain View'),
                            4 => ($lang == 'th' ? 'วิวแม่น้ำ' : 'River View'),
                            5 => ($lang == 'th' ? 'วิวไร่ชา' : 'Tea Farm View')
                        );
                        foreach ($room_types_db as $rt): 
                            $view_text = isset($view_map[$rt['id']]) ? $view_map[$rt['id']] : '';
                            $cap_text = ($lang == 'th' ? 'รองรับ 2-4 ท่าน' : 'Supports 2-4 pax');
                            $full_subtext = $view_text . ($view_text ? ' | ' : '') . $cap_text;
                        ?>
                        <tr class="hover:bg-white/5 transition-colors duration-300">
                            <td class="py-5 px-6 font-bold text-white <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>">
                                <?= htmlspecialchars($rt['type_name']) ?>
                                <span class="block text-xs text-amber-500 font-normal mt-1 <?= $lang == 'th' ? 'font-serif-thai' : 'font-light tracking-widest' ?>">(<?= $full_subtext ?>)</span>
                            </td>
                            <td class="py-5 px-6 font-light text-gray-300 tracking-wider">
                                <div class="mb-1">฿<?= number_format($rt['base_price']) ?> <span class="text-xs text-gray-500">(2 <?= $lang == 'th' ? 'ท่าน' : 'pax' ?>)</span></div>
                                <?php if(!empty($rt['price_4p_base'])): ?>
                                <div>฿<?= number_format($rt['price_4p_base']) ?> <span class="text-xs text-amber-500/70">(4 <?= $lang == 'th' ? 'ท่าน' : 'pax' ?>)</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-5 px-6 font-light text-gray-300 tracking-wider">
                                <div class="mb-1">฿<?= number_format($rt['high_price']) ?> <span class="text-xs text-gray-500">(2 <?= $lang == 'th' ? 'ท่าน' : 'pax' ?>)</span></div>
                                <?php if(!empty($rt['price_4p_high'])): ?>
                                <div>฿<?= number_format($rt['price_4p_high']) ?> <span class="text-xs text-amber-500/70">(4 <?= $lang == 'th' ? 'ท่าน' : 'pax' ?>)</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-5 px-6 font-medium text-rose-300 tracking-wider">
                                <div class="mb-1">฿<?= number_format($rt['holiday_price']) ?> <span class="text-xs text-rose-300/50">(2 <?= $lang == 'th' ? 'ท่าน' : 'pax' ?>)</span></div>
                                <?php if(!empty($rt['price_4p_holiday'])): ?>
                                <div>฿<?= number_format($rt['price_4p_holiday']) ?> <span class="text-xs text-amber-500/70">(4 <?= $lang == 'th' ? 'ท่าน' : 'pax' ?>)</span></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <section id="resort-map" class="py-16 md:py-24 px-6 max-w-[1400px] w-full mx-auto border-t border-white/5 overflow-hidden">
        <div class="mb-12 md:mb-16 text-center flex flex-col items-center">
            <div class="mask-wrap mb-4"><p class="slide-txt scroll-txt text-amber-600 tracking-[0.3em] text-xs uppercase font-cinzel font-bold">✦ RESORT MAP</p></div>
            <div class="mask-wrap mb-4"><h2 class="slide-txt scroll-txt <?= $lang == 'th' ? 'font-serif-thai font-semibold' : 'font-cinzel font-bold' ?> text-3xl md:text-4xl lg:text-5xl leading-normal py-2 text-white"><?= $lang == 'th' ? 'แผนผังภายในที่พัก' : 'Resort Map' ?></h2></div>
            <div class="mask-wrap"><p class="slide-txt scroll-txt text-gray-300 text-base md:text-lg <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>"><?= $lang == 'th' ? 'คลิกที่รูปเพื่อดูภาพเต็ม' : 'Click the image to view in full size' ?></p></div>
        </div>

        <div class="max-w-4xl mx-auto relative rounded-[20px] md:rounded-[24px] border border-amber-600/80 shadow-[0_0_15px_rgba(217,119,6,0.4)] overflow-hidden group bg-[#050505]">
            <a href="img/map1.png" data-fancybox="resort-map" data-caption="<?= $lang == 'th' ? 'แผนผังภายในที่พัก Yuncha Valley' : 'Yuncha Valley Resort Map' ?>" class="block w-full relative cursor-zoom-in hover-target">
                <img src="img/map1.png" alt="Resort Map" class="w-full h-auto block transition-transform duration-700 group-hover:scale-[1.02]">
                <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors duration-500 pointer-events-none"></div>
                
                <!-- Expand Icon -->
                <div class="absolute bottom-4 right-4 md:bottom-6 md:right-6 bg-black/60 backdrop-blur-sm border border-white/20 w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center text-white opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all duration-300 z-20 pointer-events-none shadow-lg">
                    <i class="ph ph-arrows-out-simple text-lg md:text-xl"></i>
                </div>
            </a>
        </div>
    </section>

    <section id="activities" class="py-24 md:py-32 px-6 max-w-7xl mx-auto border-t border-white/5 relative z-10">
        
         <div class="mb-16 md:mb-24 text-center flex flex-col items-center">
            <div class="mask-wrap mb-4"><p class="slide-txt scroll-txt text-amber-600 tracking-[0.3em] text-xs uppercase font-cinzel font-bold"><?= $t['exp_sub'] ?></p></div>
            <div class="mask-wrap mb-4"><h2 class="slide-txt scroll-txt <?= $lang == 'th' ? 'font-serif-thai font-semibold' : 'font-cinzel font-bold' ?> text-3xl md:text-4xl lg:text-5xl leading-normal py-2 text-white"><?= $t['exp_title'] ?></h2></div>
            <div class="mask-wrap"><p class="slide-txt scroll-txt text-gray-300 text-base md:text-lg <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>"><?= $t['exp_desc'] ?></p></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 md:gap-6">
            <?php foreach($activities as $index => $act): 
                // เรียงสัดส่วนบนหน้าจอคอม: 2 อันแรกใหญ่หน่อย (ครึ่งจอ), 3 อันหลังเล็กหน่อย (1/3 จอ)
                $colClass = ($index < 2) ? 'lg:col-span-3' : 'lg:col-span-2';
            ?>
            <a href="activities.php#act-<?= $index + 1 ?>" class="block activity-card <?= $colClass ?> relative h-[350px] md:h-[400px] rounded-[20px] md:rounded-[24px] overflow-hidden group cursor-explore hover-target bg-[#050505] shadow-lg border border-white/5">
                
                <img src="<?= $act['img'] ?>" class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-[1.05] filter group-hover:brightness-[0.9]">
                
                <div class="absolute inset-0 bg-gradient-to-t from-[#020202] via-[#020202]/50 to-transparent opacity-65 group-hover:opacity-80 transition-opacity duration-500"></div>
                
                <div class="absolute bottom-0 left-0 w-full p-6 md:p-8 z-10 flex flex-col justify-end">
                    <h3 class="text-xl md:text-2xl text-white mb-2 <?= $lang == 'th' ? 'font-serif-thai font-semibold' : 'font-cinzel font-bold' ?>">
                        <?= $lang == 'th' ? $act['title_th'] : $act['title_en'] ?>
                    </h3>
                    <p class="text-gray-300 text-sm md:text-[15px] leading-relaxed <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?> line-clamp-2">
                        <?= $lang == 'th' ? $act['desc_th'] : $act['desc_en'] ?>
                    </p>
                    <div class="mt-4 text-xs font-bold text-amber-500 uppercase tracking-widest flex items-center gap-2">
                        <?= $lang == 'th' ? 'อ่านรายละเอียดเพิ่มเติม' : 'Read More' ?>
                        <i class="ph-bold ph-arrow-right transition-transform duration-300 group-hover:translate-x-2"></i>
                    </div>
                </div>

            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if(!empty($approved_reviews)): ?>
    <section class="py-24 md:py-32 border-t border-white/5 relative z-10 overflow-hidden">
        <div class="mb-16 md:mb-24 text-center flex flex-col items-center px-6 max-w-7xl mx-auto">
            <div class="mask-wrap mb-4"><p class="slide-txt scroll-txt text-amber-600 tracking-[0.3em] text-xs uppercase font-cinzel font-bold">✦ GUEST REVIEWS</p></div>
            <div class="mask-wrap mb-4"><h2 class="slide-txt scroll-txt <?= $lang == 'th' ? 'font-serif-thai font-semibold' : 'font-cinzel font-bold' ?> text-3xl md:text-4xl lg:text-5xl leading-normal py-2 text-white"><?= $lang == 'th' ? 'เสียงตอบรับจากผู้เข้าพัก' : 'What Our Guests Say' ?></h2></div>
        </div>

        <div class="marquee-wrapper">
            <div class="marquee-track flex gap-8">
                <?php 
                $count = count($approved_reviews);
                $total_items = max(6, $count * 2); // Ensure enough items for continuous scroll
                for($i=0; $i<$total_items; $i++): 
                    $r = $approved_reviews[$i % $count];
                    $target = $r['review_type'] == 'room' ? (isset($r['room_name']) ? $r['room_name'] :  '') : (isset($r['activity_name']) ? $r['activity_name'] :  '');
                    $link = $r['review_type'] == 'room' ? "room/room.php?id=" . $r['target_id'] : "activities.php#act-" . $r['target_id'];
                ?>
                <a href="<?= $link ?>" class="flex flex-col h-[380px] md:h-[420px] flex-shrink-0 w-[350px] md:w-[450px] bg-gradient-to-br from-[#1c1c1c] to-[#0a0a0a] p-10 theme-border-always rounded-2xl shadow-2xl relative review-card transition-all duration-300 hover:shadow-[0_0_25px_rgba(217,119,6,0.3)]">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> font-bold text-sm text-amber-500 uppercase tracking-widest"><?= htmlspecialchars($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1)) ?>.</h4>
                        <span class="text-[10px] text-gray-500 uppercase tracking-widest font-prompt"><?= date('M Y', strtotime($r['created_at'])) ?></span>
                    </div>
                    <div class="flex gap-1 text-sm text-amber-500 mb-6 theme-text-glow tracking-widest">
                        <?php for($star=1; $star<=5; $star++) { echo $star <= $r['rating'] ? '★' : '<span class="text-gray-700">★</span>'; } ?>
                    </div>
                    <?php 
                    $display_comment = ($lang == 'en' && !empty($r['english_comment'])) ? $r['english_comment'] : $r['comment']; 
                    $is_long = mb_strlen($display_comment) > 100;
                    $short_comment = $is_long ? mb_substr($display_comment, 0, 100) . '...' : $display_comment;
                    ?>
                    <p class="<?= $lang == 'th' ? 'font-prompt font-light' : 'font-cinzel italic' ?> text-gray-200 text-sm md:text-base leading-relaxed whitespace-normal mb-4 cursor-pointer" onclick="event.preventDefault(); event.stopPropagation(); Swal.fire({title: '<?= $lang == 'th' ? 'รีวิวจากลูกค้า' : 'Guest Review' ?>', text: '<?= htmlspecialchars($display_comment, ENT_QUOTES) ?>', confirmButtonColor: '#d97706', confirmButtonText: '<?= $lang == 'th' ? 'ปิด' : 'Close' ?>'})">
                        "<?= htmlspecialchars($short_comment) ?>"
                        <?php if($is_long): ?>
                        <br><span class="text-amber-500 text-[10px] md:text-xs font-bold uppercase mt-2 inline-block hover:underline"><?= $lang == 'th' ? 'อ่านเพิ่มเติม' : 'Read more' ?></span>
                        <?php endif; ?>
                    </p>
                    
                    <?php 
                    $images = [];
                    if (!empty($r['image_path'])) {
                        $decoded = json_decode($r['image_path'], true);
                        if (is_array($decoded)) {
                            $images = $decoded;
                        } else {
                            $images = [$r['image_path']];
                        }
                    }
                    if (!empty($images)): 
                    ?>
                    <div class="flex gap-1 z-10 relative mt-4 w-full overflow-hidden rounded-xl border border-white/10">
                        <?php foreach($images as $idx => $img): ?>
                            <?php if($idx < 3): ?>
                            <div class="flex-1 min-w-0 cursor-pointer group/img relative" onclick="event.preventDefault(); event.stopPropagation(); openLightbox('<?= htmlspecialchars($img) ?>')">
                                <div class="h-24 md:h-32 w-full relative overflow-hidden bg-black/50">
                                    <img src="<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover opacity-80 group-hover/img:opacity-100 group-hover/img:scale-110 transition-all duration-500">
                                    <?php if($idx === 2 && count($images) > 3): ?>
                                    <div class="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-[2px]">
                                        <span class="text-white font-bold text-xl">+<?= count($images) - 3 ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="mt-4"></div>
                    <?php endif; ?>
                    <div class="mt-auto pt-4 border-t border-white/10 z-10 relative">
                        <p class="text-gray-500 text-xs">
                            <?= $lang == 'th' ? 'รีวิวจาก:' : 'Reviewed:' ?> 
                            <span class="text-amber-500 font-bold uppercase tracking-widest"><?= htmlspecialchars($target) ?></span>
                        </p>
                    </div>
                    <div class="absolute -bottom-4 -right-4 text-8xl font-cn text-white/5 pointer-events-none">印</div>
                </a>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <style>
        /* INFINITE MARQUEE (REVIEWS) */
        .marquee-wrapper {
            width: 100%;
            overflow: hidden;
            position: relative;
        }


        .marquee-track {
            display: flex;
            width: max-content;
            animation: marqueeSlide 120s linear infinite;
        }
        .marquee-track:hover { animation-play-state: paused; }
        @keyframes marqueeSlide {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-50%, 0, 0); }
        }

        .theme-border-always {
            border: 1px solid #d97706 !important;
            box-shadow: 0 0 15px rgba(217, 119, 6, 0.4) !important;
        }
    </style>
    <?php endif; ?>

    <section id="contact" class="py-24 md:py-32 px-6 bg-[#faf9f6] text-[#2c2825]">
        <div class="max-w-7xl mx-auto">
            
            <div class="mb-16 md:mb-24 text-center flex flex-col items-center">
                <div class="mask-wrap mb-4 flex items-center justify-center gap-3">
                    <svg class="w-4 h-4 text-[#d97706]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L9.5 8.5 3 11l6.5 2.5L12 20l2.5-6.5L21 11l-6.5-2.5z"/></svg>
                    <p class="slide-txt scroll-txt text-[#d97706] tracking-[0.3em] text-xs uppercase font-cinzel font-bold"><?= $t['loc_sub'] ?></p>
                </div>
                <div class="mask-wrap mb-6"><h2 class="slide-txt scroll-txt font-cinzel text-5xl md:text-7xl font-bold text-[#1a1816] pt-2 pb-1 leading-tight"><?= $t['loc_title'] ?></h2></div>
                <div class="mask-wrap"><p class="slide-txt scroll-txt text-lg text-gray-600 <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>"><?= $t['loc_desc'] ?></p></div>
            </div>

            <div class="flex flex-col lg:flex-row gap-10 lg:gap-16 items-start max-w-5xl mx-auto">
                <div class="w-full lg:w-1/2 flex flex-col justify-center">
                    <div class="flex flex-col gap-8 mb-12">
                        <div class="flex gap-6 items-start">
                            <div class="w-12 h-12 rounded-full border border-gray-300 flex items-center justify-center flex-shrink-0 text-[#d97706]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-1 <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $t['loc_address_title'] ?></h4>
                                <p class="text-gray-600 leading-relaxed <?= $lang == 'th' ? 'font-serif-thai' : '' ?>"><?= $t['loc_address'] ?></p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div class="w-12 h-12 rounded-full border border-gray-300 flex items-center justify-center flex-shrink-0 text-[#d97706]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-1 <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $t['loc_phone_title'] ?></h4>
                                <p class="text-gray-600 leading-relaxed font-light"><?= nl2br($t['loc_phone']) ?></p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div class="w-12 h-12 rounded-full border border-gray-300 flex items-center justify-center flex-shrink-0 text-[#d97706]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-1 <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $t['loc_email_title'] ?></h4>
                                <p class="text-gray-600 leading-relaxed font-light"><?= $t['loc_email'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="w-full h-px bg-gray-200 mb-10"></div>

                    <h3 class="font-bold text-2xl mb-6 <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $t['loc_direction_title'] ?></h3>
                    <div class="flex flex-col gap-4 mb-10">
                        <div class="flex items-center gap-3 text-gray-600 <?= $lang == 'th' ? 'font-serif-thai' : '' ?>">
                            <svg class="w-5 h-5 text-[#d97706]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            <?= $t['loc_dir_car'] ?>
                        </div>
                        <div class="flex items-center gap-3 text-gray-600 <?= $lang == 'th' ? 'font-serif-thai' : '' ?>">
                            <svg class="w-5 h-5 text-[#d97706]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <?= $t['loc_dir_plane'] ?>
                        </div>
                    </div>

                    <a href="<?= $map_link_url ?>" target="_blank" class="hover-target self-start inline-flex items-center justify-center gap-3 btn-gradient-glow px-8 py-3.5 rounded-lg text-sm font-bold <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel tracking-widest' ?>">
                        <?= $t['btn_map'] ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <div class="w-full lg:w-1/2 rounded-2xl overflow-hidden bg-white shadow-xl border border-gray-100 p-2 h-[350px] lg:h-[450px]">
                    <iframe src="<?= $map_iframe_url ?>" width="100%" height="100%" style="border:0; border-radius: 12px;" allowfullscreen="" loading="lazy"></iframe>
                </div>

            </div>
        </div>
    </section>
    <?php include 'components/footer.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/chatbot.js?v=<?= time() ?>"></script>
    <!-- Fancybox -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        Fancybox.bind("[data-fancybox]", {
            // Options
            Thumbs: {
                autoStart: true
            },
            Toolbar: {
                display: {
                    left: ["infobar"],
                    middle: ["zoomIn", "zoomOut", "toggle1to1"],
                    right: ["slideshow", "fullscreen", "close"],
                }
            },
            Carousel: {
                transition: "slide",
                friction: 0.8, // Slow down drag/slide transition
            },
            Slideshow: {
                timeout: 8000, // Slow down the slideshow speed (8 seconds)
                autoStart: false
            }
        });

        // Function to change main image when clicking a thumbnail
        function changeMainImage(roomId, imgSrc, index) {
            // Update main image src
            document.getElementById('main-img-' + roomId).src = imgSrc;
            document.getElementById('main-link-' + roomId).href = imgSrc;
            
            // Update index state
            const container = document.getElementById('gallery-container-' + roomId);
            container.dataset.currentIndex = index;
            
            // Update counter
            const gallery = JSON.parse(container.dataset.gallery);
            const counter = document.getElementById('counter-' + roomId);
            if(counter) counter.innerText = (index + 1) + ' / ' + gallery.length;
            
            // Remove active state from all thumbnails in this room
            const thumbContainer = document.getElementById('thumbnails-' + roomId);
            if(thumbContainer) {
                const siblings = thumbContainer.querySelectorAll('.thumbnail-item');
                siblings.forEach(el => {
                    el.classList.remove('border-amber-500/80', 'opacity-100');
                    el.classList.add('border-transparent', 'opacity-60');
                });
                
                // Add active state to clicked thumbnail
                if(siblings[index]) {
                    siblings[index].classList.remove('border-transparent', 'opacity-60');
                    siblings[index].classList.add('border-amber-500/80', 'opacity-100');
                    // Ensure it's in view
                    siblings[index].scrollIntoView({behavior: 'smooth', block: 'nearest', inline: 'center'});
                }
            }
        }

        // Function to handle left/right arrows on main image
        function navigateGallery(roomId, direction) {
            const container = document.getElementById('gallery-container-' + roomId);
            const gallery = JSON.parse(container.dataset.gallery);
            let currentIndex = parseInt(container.dataset.currentIndex);
            
            let nextIndex = currentIndex + direction;
            if(nextIndex < 0) nextIndex = gallery.length - 1;
            if(nextIndex >= gallery.length) nextIndex = 0;
            
            const nextImgSrc = gallery[nextIndex];
            changeMainImage(roomId, nextImgSrc, nextIndex);
        }
        // ----------------------------------------
        // Price Animation Logic
        // ----------------------------------------
        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                // easeOutQuart
                const easeProgress = 1 - Math.pow(1 - progress, 4);
                obj.innerHTML = Math.floor(start + (end - start) * easeProgress).toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    obj.innerHTML = end.toLocaleString();
                }
            };
            window.requestAnimationFrame(step);
        }

        function updatePrice(selectElem, roomId) {
            const guests = parseInt(selectElem.value);
            const priceElem = document.getElementById('price-' + roomId);
            const basePrice = parseInt(priceElem.dataset.basePrice);
            
            let targetPrice = basePrice;
            if (guests >= 4) {
                targetPrice = basePrice + 900;
            }
            
            const currentPriceStr = priceElem.innerText.replace(/,/g, '');
            const currentPrice = parseInt(currentPriceStr) || 0;
            
            if (currentPrice !== targetPrice) {
                animateValue(priceElem, currentPrice, targetPrice, 800);
            }
        }

        // Trigger animation when scrolling into view
        document.addEventListener('DOMContentLoaded', () => {
            const priceCounters = document.querySelectorAll('.price-counter');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        const targetPrice = parseInt(el.dataset.basePrice);
                        // Check if guests is already 4
                        const guestSelect = el.closest('form').querySelector('input[type="radio"]:checked');
                        let finalPrice = targetPrice;
                        if(guestSelect && parseInt(guestSelect.value) >= 4) {
                            finalPrice += 900;
                        }
                        
                        animateValue(el, 0, finalPrice, 1500);
                        observer.unobserve(el); // Animate only once when it appears
                    }
                });
            }, { threshold: 0.1 });
            
            priceCounters.forEach(el => observer.observe(el));
        });
    </script>
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
</body>
</html>
