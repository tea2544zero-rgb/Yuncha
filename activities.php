<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] :  'th';

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
$raw_phone = isset($site_settings['phone']) ? $site_settings['phone'] :  "080-342-9396 ( คุณเต้ )\n063-915-0091 ( คุณมิ้ง )\n053-123-456 ( RESORT )";
$phone = $lang === 'en' ? str_replace(['คุณเต้', 'คุณมิ้ง'], ['Mr. Tae', 'Ms. Ming'], $raw_phone) : $raw_phone;
$email = isset($site_settings['email']) ? $site_settings['email'] :  'stay@yunchavalley.com';
$address = isset($site_settings['address']) ? $site_settings['address'] :  'หมู่บ้านรักไทย ต.หมอกจำแป่ อ.เมือง จ.แม่ฮ่องสอน 58000';

// Removed legacy SQLite path
$activities = [];
$act_settings = [];
if (true) {
    try {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdoAct = $conn;
        $pdoAct->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $activities = $pdoAct->query("SELECT * FROM activities ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $act_settings = $pdoAct->query("SELECT * FROM act_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {}
}

$dict = [
    'th' => [
        'nav_home' => 'หน้าแรก', 'nav_rooms' => 'ห้องพัก', 'nav_exp' => 'กิจกรรม', 'nav_policy' => 'นโยบาย', 'nav_contact' => 'ติดต่อที่พัก', 'nav_book' => 'จองห้องพัก', 'nav_signin' => 'เข้าสู่ระบบ',
        'hero_title' => isset($act_settings['hero_title_th']) ? $act_settings['hero_title_th'] :  (isset($act_settings['hero_title']) ? $act_settings['hero_title'] :  'YUNCHA VALLEY EXPERIENCES'),
        'hero_sub' => isset($act_settings['hero_sub_th']) ? $act_settings['hero_sub_th'] :  (isset($act_settings['hero_sub']) ? $act_settings['hero_sub'] :  'สัมผัสประสบการณ์แห่งหมอก ชา และวัฒนธรรมจีนยูนนาน'),
        'gallery_title' => isset($act_settings['gallery_title_th']) ? $act_settings['gallery_title_th'] :  (isset($act_settings['gallery_title']) ? $act_settings['gallery_title'] :  'ภาพบรรยากาศกิจกรรม'),
        'reviews_title' => isset($act_settings['reviews_title_th']) ? $act_settings['reviews_title_th'] :  (isset($act_settings['reviews_title']) ? $act_settings['reviews_title'] :  'ความประทับใจจากผู้ร่วมกิจกรรม'),
        'act1_title' => isset($activities[0]['title_th']) ? $activities[0]['title_th'] :  'ล่องเรือโบราณชมทะเลสาบ',
        'act1_sub' => isset($activities[0]['sub_th']) ? $activities[0]['sub_th'] :  'Misty Lake Cruise',
        'act1_desc' => isset($activities[0]['desc_th']) ? $activities[0]['desc_th'] :  'ล่องเรือไม้สไตล์ยูนนานโบราณชมวิวทะเลสาบ Ban Rak Thai — รองรับสูงสุด 4 ท่านต่อลำ ใช้เวลาประมาณ 30-40 นาที แนะนำช่วงพระอาทิตย์ขึ้น 06:00-07:30 น. ท่ามกลางทะเลหมอกสีขาวลอยเหนือผิวน้ำ พร้อมเพลิดเพลินกับการจิบชาและถ่ายรูปสไตล์ภาพยนตร์',
        'act2_title' => isset($activities[1]['title_th']) ? $activities[1]['title_th'] :  'ดื่มด่ำวัฒนธรรมชาและเวิร์กชอปชงชา',
        'act2_sub' => isset($activities[1]['sub_th']) ? $activities[1]['sub_th'] :  'Tea Experience',
        'act2_desc' => isset($activities[1]['desc_th']) ? $activities[1]['desc_th'] :  'สัมผัสวัฒนธรรมชาจีนโบราณ ลิ้มลองรสชาติชากลุ่มพรีเมียม 5 ชนิด (ชาอู่หลง ชากุหลาบ ชาพลัม ชามะลิ และชาเขียว) เรียนรู้ขั้นตอนการชงชาและการเบลนด์ชารสชาติที่เป็นเอกลักษณ์เฉพาะของตัวเอง ใช้เวลาประมาณ 45-90 นาที',
        'act3_title' => isset($activities[2]['title_th']) ? $activities[2]['title_th'] :  'เดินชมไร่ชาและสัมผัสไอหมอก',
        'act3_sub' => isset($activities[2]['sub_th']) ? $activities[2]['sub_th'] :  'Tea Plantation Walk',
        'act3_desc' => isset($activities[2]['desc_th']) ? $activities[2]['desc_th'] :  'เดินทอดน่องสัมผัสธรรมชาติอันเงียบสงบในไร่ชาเขียวขจีที่โอบล้อมรีสอร์ท ถ่ายรูปเช็คอินมุมสวย ๆ ท่ามกลางเนินเขาและสายหมอกยามเช้า เรียนรู้วิถีชีวิตการเก็บใบชาแบบดั้งเดิม',
        'act4_title' => isset($activities[3]['title_th']) ? $activities[3]['title_th'] :  'เช่าชุดจีนยูนนานถ่ายรูป',
        'act4_sub' => isset($activities[3]['sub_th']) ? $activities[3]['sub_th'] :  'Yunnan Costume Shoot',
        'act4_desc' => isset($activities[3]['desc_th']) ? $activities[3]['desc_th'] :  'เก็บภาพความทรงจำแสนพิเศษด้วยบริการเช่าชุดโบราณฮั่นฝูระดับพรีเมียม พร้อมอุปกรณ์ตกแต่งครบครัน เช่น ร่มกระดาษ พัดโบราณ และโคมไฟจีนสีแดง ถ่ายรูปคู่กับสถาปัตยกรรมบ้านดินสไตล์ยูนนานอันเป็นเอกลักษณ์',
        'act5_title' => isset($activities[4]['title_th']) ? $activities[4]['title_th'] :  'หม้อไฟยูนนานและหมูกระทะปิ้งย่าง',
        'act5_sub' => isset($activities[4]['sub_th']) ? $activities[4]['sub_th'] :  'Yunnan Hotpot & BBQ',
        'act5_desc' => isset($activities[4]['desc_th']) ? $activities[4]['desc_th'] :  'อิ่มอร่อยกับชุดอาหารมื้อค่ำสไตล์จีนยูนนานต้นตำรับ ลิ้มลองซุปไก่ดำสมุนไพรรสเลิศ และเซ็ตหมูกระทะ/หมาล่าปิ้งย่างร้อน ๆ ท่ามกลางอุณหภูมิเย็นสบายและการตกแต่งด้วยโคมไฟสีแดงสไตล์ยูนนาน',
        'features_title' => isset($act_settings['features_title_th']) ? $act_settings['features_title_th'] :  (isset($act_settings['features_title']) ? $act_settings['features_title'] :  'บริการเหนือระดับที่เราขอมอบให้'),
        
        'loc_direction_title' => 'การเดินทาง',
        'loc_dir_car' => 'ขับรถจากตัวเมืองเชียงใหม่ ใช้เวลาประมาณ 5-6 ชั่วโมง',
        'loc_dir_plane' => 'มีเที่ยวบินตรงจากดอนเมือง/สุวรรณภูมิ มายังสนามบินแม่ฮ่องสอน',
        'btn_map' => 'ดูแผนที่ Google Maps',
        'loc' => 'ที่ตั้ง', 
        'loc_desc' => $address,
        'contact' => 'ติดต่อ', 
        'contact_desc' => $phone,
        'email' => 'อีเมล', 
        'email_desc' => $email,

        'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. สงวนลิขสิทธิ์.'
    ],
    'en' => [
        'nav_home' => 'Home', 'nav_rooms' => 'Rooms', 'nav_exp' => 'Experiences', 'nav_policy' => 'Policies', 'nav_contact' => 'Contact', 'nav_book' => 'Booking', 'nav_signin' => 'Sign In',
        'hero_title' => isset($act_settings['hero_title_en']) ? $act_settings['hero_title_en'] :  (isset($act_settings['hero_title']) ? $act_settings['hero_title'] :  'YUNCHA VALLEY EXPERIENCES'),
        'hero_sub' => isset($act_settings['hero_sub_en']) ? $act_settings['hero_sub_en'] :  (isset($act_settings['hero_sub']) ? $act_settings['hero_sub'] :  'Embrace the Mist, Tea, and Yunnan Heritage'),
        'gallery_title' => isset($act_settings['gallery_title_en']) ? $act_settings['gallery_title_en'] :  (isset($act_settings['gallery_title']) ? $act_settings['gallery_title'] :  'Misty Moments Gallery'),
        'reviews_title' => isset($act_settings['reviews_title_en']) ? $act_settings['reviews_title_en'] :  (isset($act_settings['reviews_title']) ? $act_settings['reviews_title'] :  'Guest Experiences & Stories'),
        'act1_title' => isset($activities[0]['title_en']) ? $activities[0]['title_en'] :  'Lake Boat Cruise',
        'act1_sub' => isset($activities[0]['sub_en']) ? $activities[0]['sub_en'] :  'Lake Boat Cruise',
        'act1_desc' => isset($activities[0]['desc_en']) ? $activities[0]['desc_en'] :  'Wooden boat on the lake — up to 4 guests, 30–40 min. Best at sunrise 06:00–07:30 with cinematic photos & floating tea set',
        'act2_title' => isset($activities[1]['title_en']) ? $activities[1]['title_en'] :  'Tea Experience',
        'act2_sub' => isset($activities[1]['sub_en']) ? $activities[1]['sub_en'] :  'Tea Experience',
        'act2_desc' => isset($activities[1]['desc_en']) ? $activities[1]['desc_en'] :  'Taste 5 teas (oolong, rose, plum, jasmine, green), learn brewing & make your own tea blend. 45–90 min',
        'act3_title' => isset($activities[2]['title_en']) ? $activities[2]['title_en'] :  'Tea Plantation Walk',
        'act3_sub' => isset($activities[2]['sub_en']) ? $activities[2]['sub_en'] :  'Tea Plantation Walk',
        'act3_desc' => isset($activities[2]['desc_en']) ? $activities[2]['desc_en'] :  'Stroll through the lush green tea plantation surrounding our resort. Take beautiful photos amidst the hills and morning mist while learning the traditional art of tea leaf picking.',
        'act4_title' => isset($activities[3]['title_en']) ? $activities[3]['title_en'] :  'Yunnan Costume Shoot',
        'act4_sub' => isset($activities[3]['sub_en']) ? $activities[3]['sub_en'] :  'Yunnan Costume Shoot',
        'act4_desc' => isset($activities[3]['desc_en']) ? $activities[3]['desc_en'] :  'Capture special memories with our premium Hanfu traditional costume rental. Complete with props including paper umbrellas, vintage fans, and red Chinese lanterns against the unique clay houses.',
        'act5_title' => isset($activities[4]['title_en']) ? $activities[4]['title_en'] :  'Yunnan Hotpot & BBQ',
        'act5_sub' => isset($activities[4]['sub_en']) ? $activities[4]['sub_en'] :  'Yunnan Dining',
        'act5_desc' => isset($activities[4]['desc_en']) ? $activities[4]['desc_en'] :  'Savor an authentic Yunnan-style dinner featuring premium black chicken herbal soup, and hot spicy Mala BBQ or hotpot, served under the warm glow of red lanterns in the cool mountain breeze.',
        'features_title' => isset($act_settings['features_title_en']) ? $act_settings['features_title_en'] :  (isset($act_settings['features_title']) ? $act_settings['features_title'] :  'Exclusive Resort Offerings'),
        
        'loc_direction_title' => 'Directions',
        'loc_dir_car' => 'Approx. 5-6 hours drive from Chiang Mai',
        'loc_dir_plane' => 'Direct flights available from Bangkok to Mae Hong Son',
        'btn_map' => 'View on Google Maps',
        'loc' => 'LOCATION', 
        'loc_desc' => $address,
        'contact' => 'CONTACT', 
        'contact_desc' => $phone,
        'email' => 'EMAIL', 
        'email_desc' => $email,

        'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. ALL RIGHTS RESERVED.'
    ]
];
$t = $dict[$lang];

$reviews_th = [
    ['name' => 'คุณกิตติศักดิ์ พ.', 'date' => 'ม.ค. 2026', 'text' => 'การล่องเรือโบราณตอนเช้าท่ามกลางหมอกเหนือทะเลสาบรักไทยมันคุ้มค่ามาก หมอกหนาตึ๊บสวยงามราวกับภาพวาดจีนเลยครับ ชาอู่หลงร้อน ๆ ที่เสิร์ฟบนเรือก็หอมชื่นใจมาก', 'rating' => 5, 'image_path' => json_encode(['img/k2.jpg'])],
    ['name' => 'คุณพิมพ์นารา ส.', 'date' => 'ก.พ. 2026', 'text' => 'ชุดชาพรีเมียมจัดแต่งโต๊ะไม้ได้หรูหรามากค่ะ ถ่ายรูปออกมาสวยสุด ๆ และได้ความรู้เรื่องการชงชาจีนโบราณแบบต้นตำรับแท้ ๆ ประทับใจการบริการของน้อง ๆ พนักงานมาก', 'rating' => 5, 'image_path' => json_encode(['img/k1.jpg', 'img/k3.jpg'])],
    ['name' => 'คุณธนภัทร ร.', 'date' => 'ก.พ. 2026', 'text' => 'วิวไร่ชาสวยงามตระการตา อากาศหนาวเย็นตลอดทั้งปี ได้เดินเก็บใบชาสด ๆ และสูดไอหมอกยามบ่ายฟินมาก แนะนำห้องพักและบริการของที่นี่ ดีมากครับ', 'rating' => 5, 'image_path' => json_encode(['img/k4.webp'])],
    ['name' => 'คุณพรรณทิพา ว.', 'date' => 'มี.ค. 2026', 'text' => 'เช่าชุดจีนฮั่นฝูเดินถ่ายรูปคู่กับแฟน โคมแดงและกำแพงบ้านดินให้ฟีลเหมือนหลุดไปอยู่ในซีรีส์จีนโบราณเลยค่ะ มีมุมถ่ายภาพสวย ๆ เยอะมากจริง ๆ', 'rating' => 5, 'image_path' => json_encode(['img/k3.jpg', 'img/k1.jpg'])],
    ['name' => 'คุณณภัทร ม.', 'date' => 'มี.ค. 2026', 'text' => 'หม้อไฟยูนนานขาหมูหมั่นโถวคือที่สุด! ทานท่ามกลางอากาศหนาว 15 องศากับสายหมอกตอนค่ำ ซุปรสชาติเข้มข้นอร่อยมาก ปิ้งย่างหม่าล่าก็เด็ด แนะนำห้ามพลาดเลย', 'rating' => 5, 'image_path' => json_encode(['img/k2.jpg', 'img/k4.webp'])]
];

$reviews_en = [
    ['name' => 'Kittisak P.', 'date' => 'Jan 2026', 'text' => 'The morning wooden boat cruise amidst the thick mist over Ban Rak Thai lake was absolutely worth it! The scenery looked like a classic Chinese painting. The hot oolong tea served on board was so fragrant.', 'rating' => 5, 'image_path' => json_encode(['img/k2.jpg'])],
    ['name' => 'Pimnara S.', 'date' => 'Feb 2026', 'text' => 'The premium tea set table arrangement is incredibly elegant. The photos turned out beautiful! We learned a lot about authentic traditional Chinese tea brewing. Excellent service from the staff.', 'rating' => 5, 'image_path' => json_encode(['img/k1.jpg', 'img/k3.jpg'])],
    ['name' => 'Thanaphat R.', 'date' => 'Feb 2026', 'text' => 'Breathtaking tea plantation views and freezing weather all year round. Walking through the fresh tea leaves and breathing in the afternoon mist was pure bliss. Highly recommend the resort and its services!', 'rating' => 5, 'image_path' => json_encode(['img/k4.webp'])],
    ['name' => 'Phatthipa W.', 'date' => 'Mar 2026', 'text' => 'Rented the Hanfu Chinese traditional costumes for photos with my partner. The red lanterns and clay houses made us feel like we traveled back in time into a historical Chinese drama. There are so many beautiful photo spots!', 'rating' => 5, 'image_path' => json_encode(['img/k3.jpg', 'img/k1.jpg'])],
    ['name' => 'Naphat M.', 'date' => 'Mar 2026', 'text' => 'The Yunnan Hotpot and steamed buns are the absolute best! Eating in the 15°C cold winter air with night mist rolling in was incredible. The broth was rich and delicious, and the Mala BBQ was great. Don\'t miss it!', 'rating' => 5, 'image_path' => json_encode(['img/k2.jpg', 'img/k4.webp'])]
];

$reviews = $lang == 'th' ? $reviews_th : $reviews_en;

// Load real Activity Reviews from DB
try {
    if (isset($pdo)) {
        $stmt_rv = $pdo->query("
            SELECT r.rating, r.comment, r.english_comment, r.created_at, r.image_path, c.first_name, c.last_name, p.name as activity_name
            FROM reviews r
            JOIN customers c ON r.customer_id = c.id
            LEFT JOIN packages p ON r.target_id = p.id
            WHERE r.is_approved = 1 AND r.review_type = 'activity'
            ORDER BY r.created_at DESC
        ");
        if ($stmt_rv) {
            $db_reviews = $stmt_rv->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($db_reviews)) {
                $real_reviews = [];
                foreach ($db_reviews as $r) {
                    $display_comment = ($lang == 'en' && !empty($r['english_comment'])) ? $r['english_comment'] : $r['comment'];
                    $real_reviews[] = [
                        'name' => htmlspecialchars($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1) . '.'),
                        'date' => date('M Y', strtotime($r['created_at'])),
                        'text' => htmlspecialchars($display_comment) . ($r['activity_name'] ? " (Reviewed: {$r['activity_name']})" : ''),
                        'rating' => (int)$r['rating'],
                        'image_path' => $r['image_path']
                    ];
                }
                // Prepend real reviews to hardcoded ones
                $reviews = array_merge($real_reviews, $reviews);
            }
        }
    }
} catch(Exception $e) {}

$body_class = "antialiased selection:bg-amber-600 selection:text-white bg-[#020202]";
$skip_preloader = true;
?>
<?php include 'components/header.php'; ?>

    <!-- Global Sakura Canvas -->
    <canvas id="particles-canvas" class="fixed inset-0 z-[5] pointer-events-none opacity-80" style="width: 100vw; height: 100vh;"></canvas>

    <!-- HERO SECTION -->
    <section class="relative h-[100svh] flex flex-col justify-center items-center overflow-hidden bg-black px-4">
        <!-- Parallax Background Image -->
        <div class="absolute inset-0 z-0" id="hero-img-container">
            <img src="img/m11.jpg" class="w-full h-full object-cover filter brightness-50 ken-burns" id="hero-img" alt="Yuncha Valley Heritage">
        </div>

        <!-- Cinematic Fog layer -->
        <div class="fog-container">
            <div class="fog-layer-1"></div>
            <div class="fog-layer-2"></div>
        </div>
        
        <!-- Center texts -->
        <div class="z-10 text-center relative pointer-events-none w-full max-w-4xl px-4 animate-fade-in-down -mt-16 md:-mt-24">
            <h1 class="font-cinzel text-4xl md:text-5xl lg:text-7xl tracking-[4px] md:tracking-[8px] font-bold text-white drop-shadow-2xl theme-glow-gold uppercase" id="hero-title">
                <?= $t['hero_title'] ?>
            </h1>
            <div class="w-24 h-[1px] bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto my-6"></div>
            <p class="<?= $lang == 'th' ? 'font-serif-thai text-lg md:text-[1.2rem] tracking-[3px]' : 'font-cinzel text-sm md:text-[1.2rem] tracking-[3px]' ?> text-gray-300 font-light drop-shadow-lg" id="hero-sub">
                <?= $t['hero_sub'] ?>
            </p>
        </div>

        <!-- Scroll Down Indicator -->
        <div class="absolute bottom-12 md:bottom-16 z-10 flex flex-col items-center gap-2 animate-bounce opacity-70">
            <span class="font-cinzel text-xs md:text-base tracking-[0.2em] md:tracking-[0.3em] text-amber-500 uppercase font-semibold">
                <?= $lang == 'th' ? 'เลื่อนเพื่อดูกิจกรรมที่พัก' : 'SCROLL TO VIEW EXPERIENCES' ?>
            </span>
            <svg class="w-7 h-7 md:w-9 md:h-9 text-amber-500 mt-1 md:mt-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
        
        <!-- Bottom Fade Gradient -->
        <div class="absolute bottom-0 left-0 w-full h-48 bg-gradient-to-t from-[#000000] to-transparent z-10 pointer-events-none"></div>
    </section>

    <!-- ACTIVITY SHOWCASE SECTION -->
    <section class="py-24 md:py-36 px-6 md:px-12 max-w-7xl mx-auto z-10 relative border-t border-white/5" id="showcase-section">
        <div class="flex flex-col gap-20 md:gap-28">

            <!-- ACTIVITY 1 — ล่องเรือกลางหมอก (Misty Lake Cruise) -->
            <div class="flex flex-col lg:flex-row items-center gap-8 lg:gap-12 reveal-card glass-panel p-6 md:p-8 lg:p-10 rounded-[24px] md:rounded-[32px] border border-white/10 hover:border-amber-500/30 hover:shadow-[0_0_30px_rgba(217,119,6,0.15)] transition-all duration-500 shadow-2xl relative overflow-hidden group/card" id="act-1">
                <div class="w-full lg:w-1/2 relative h-[300px] lg:h-[400px] overflow-hidden rounded-2xl glass-panel sweep-effect group hover-target cursor-explore">
                    <div class="ripple-effect"></div>
                    <img src="img/m5.jpg" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110 filter brightness-90" alt="Misty Lake Cruise">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-xs text-amber-500 font-cinzel tracking-widest font-semibold flex items-center gap-2">
                        <span>🛶</span> <span>LAKE CRUISE</span>
                    </div>
                </div>
                <div class="w-full lg:w-1/2 flex flex-col justify-center">
                    <h2 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-2xl md:text-[1.8rem] font-medium text-amber-400 drop-shadow-[0_0_10px_rgba(217,119,6,0.3)] mb-2"><?= $t['act1_title'] ?></h2>
                    <div class="text-[0.85rem] text-gray-400 uppercase tracking-[2px] font-cinzel mb-6"><?= $t['act1_sub'] ?></div>
                    <p class="<?= $lang == 'th' ? 'font-serif-thai text-base md:text-lg leading-relaxed' : 'text-sm md:text-base leading-loose font-light' ?> text-gray-300 mb-8">
                        <?= nl2br($t['act1_desc']) ?>
                    </p>
                    <div class="flex flex-wrap gap-4 text-xs text-gray-300 font-semibold mb-8">
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🌅 Morning Light</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🍵 Warm Tea Serving</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🛶 Traditional Wooden Boat</span>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY 2 — ชุดชาจีนยูนนานพรีเมียม (Premium Yunnan Tea Set) -->
            <div class="flex flex-col lg:flex-row-reverse items-center gap-8 lg:gap-12 reveal-card glass-panel p-6 md:p-8 lg:p-10 rounded-[24px] md:rounded-[32px] border border-white/10 hover:border-amber-500/30 hover:shadow-[0_0_30px_rgba(217,119,6,0.15)] transition-all duration-500 shadow-2xl relative overflow-hidden group/card" id="act-2">
                <div class="w-full lg:w-1/2 relative h-[300px] lg:h-[400px] overflow-hidden rounded-2xl glass-panel sweep-effect group hover-target cursor-explore">
                    <!-- Steam animation -->
                    <div class="steam-container">
                        <span class="steam-line"></span>
                        <span class="steam-line"></span>
                        <span class="steam-line"></span>
                    </div>
                    <img src="img/m3.jpg" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110 filter brightness-90" alt="Premium Yunnan Tea Set">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-xs text-amber-500 font-cinzel tracking-widest font-semibold flex items-center gap-2">
                        <span>🍵</span> <span>TEA HOUSE</span>
                    </div>
                </div>
                <div class="w-full lg:w-1/2 flex flex-col justify-center">
                    <h2 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-2xl md:text-[1.8rem] font-medium text-amber-400 drop-shadow-[0_0_10px_rgba(217,119,6,0.3)] mb-2"><?= $t['act2_title'] ?></h2>
                    <div class="text-[0.85rem] text-gray-400 uppercase tracking-[2px] font-cinzel mb-6"><?= $t['act2_sub'] ?></div>
                    <p class="<?= $lang == 'th' ? 'font-serif-thai text-base md:text-lg leading-relaxed' : 'text-sm md:text-base leading-loose font-light' ?> text-gray-300 mb-8">
                        <?= nl2br($t['act2_desc']) ?>
                    </p>
                    <div class="flex flex-wrap gap-4 text-xs text-gray-300 font-semibold mb-8">
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🍃 Premium Oolong</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🪵 Classical Wooden Table</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🥮 Chinese Pastries</span>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY 3 — เดินชมไร่ชา (Tea Plantation Walk) -->
            <div class="flex flex-col lg:flex-row items-center gap-8 lg:gap-12 reveal-card glass-panel p-6 md:p-8 lg:p-10 rounded-[24px] md:rounded-[32px] border border-white/10 hover:border-amber-500/30 hover:shadow-[0_0_30px_rgba(217,119,6,0.15)] transition-all duration-500 shadow-2xl relative overflow-hidden group/card" id="act-3">
                <div class="w-full lg:w-1/2 relative h-[300px] lg:h-[400px] overflow-hidden rounded-2xl glass-panel sweep-effect group hover-target cursor-explore">
                    <img src="img/m6.jpg" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110 filter brightness-90" alt="Tea Plantation Walk">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-xs text-amber-500 font-cinzel tracking-widest font-semibold flex items-center gap-2">
                        <span>🏞</span> <span>PLANTATION</span>
                    </div>
                </div>
                <div class="w-full lg:w-1/2 flex flex-col justify-center">
                    <h2 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-2xl md:text-[1.8rem] font-medium text-amber-400 drop-shadow-[0_0_10px_rgba(217,119,6,0.3)] mb-2"><?= $t['act3_title'] ?></h2>
                    <div class="text-[0.85rem] text-gray-400 uppercase tracking-[2px] font-cinzel mb-6"><?= $t['act3_sub'] ?></div>
                    <p class="<?= $lang == 'th' ? 'font-serif-thai text-base md:text-lg leading-relaxed' : 'text-sm md:text-base leading-loose font-light' ?> text-gray-300 mb-8">
                        <?= nl2br($t['act3_desc']) ?>
                    </p>
                    <div class="flex flex-wrap gap-4 text-xs text-gray-300 font-semibold mb-8">
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🌄 360 Valley View</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🧗 Mountain Ridge Path</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🧺 Leaf Picking</span>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY 4 — ชุดถ่ายรูปจีนยูนนาน (Yunnan Photo Shoot) -->
            <div class="flex flex-col lg:flex-row-reverse items-center gap-8 lg:gap-12 reveal-card glass-panel p-6 md:p-8 lg:p-10 rounded-[24px] md:rounded-[32px] border border-white/10 hover:border-amber-500/30 hover:shadow-[0_0_30px_rgba(217,119,6,0.15)] transition-all duration-500 shadow-2xl relative overflow-hidden group/card" id="act-4">
                <div class="w-full lg:w-1/2 relative h-[300px] lg:h-[400px] overflow-hidden rounded-2xl glass-panel sweep-effect fabric-sway group hover-target cursor-explore">
                    <img src="img/m2.jpg" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110 filter brightness-90" alt="Yunnan Photo Shoot">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-xs text-amber-500 font-cinzel tracking-widest font-semibold flex items-center gap-2">
                        <span>⛩</span> <span>ORIENTAL STUDY</span>
                    </div>
                </div>
                <div class="w-full lg:w-1/2 flex flex-col justify-center">
                    <h2 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-2xl md:text-[1.8rem] font-medium text-amber-400 drop-shadow-[0_0_10px_rgba(217,119,6,0.3)] mb-2"><?= $t['act4_title'] ?></h2>
                    <div class="text-[0.85rem] text-gray-400 uppercase tracking-[2px] font-cinzel mb-6"><?= $t['act4_sub'] ?></div>
                    <p class="<?= $lang == 'th' ? 'font-serif-thai text-base md:text-lg leading-relaxed' : 'text-sm md:text-base leading-loose font-light' ?> text-gray-300 mb-8">
                        <?= nl2br($t['act4_desc']) ?>
                    </p>
                    <div class="flex flex-wrap gap-4 text-xs text-gray-300 font-semibold mb-8">
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">👘 Hanfu Traditional Costume</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🏮 Props Included</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">📸 Photography Spots</span>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY 5 — หม้อไฟยูนนาน / ปิ้งย่าง (Yunnan Hotpot / BBQ) -->
            <div class="flex flex-col lg:flex-row items-center gap-8 lg:gap-12 reveal-card glass-panel p-6 md:p-8 lg:p-10 rounded-[24px] md:rounded-[32px] border border-white/10 hover:border-amber-500/30 hover:shadow-[0_0_30px_rgba(217,119,6,0.15)] transition-all duration-500 shadow-2xl relative overflow-hidden group/card" id="act-5">
                <div class="w-full lg:w-1/2 relative h-[300px] lg:h-[400px] overflow-hidden rounded-2xl glass-panel sweep-effect group hover-target cursor-explore">
                    <div class="fire-glow top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>
                    <img src="img/m4.jpg" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110 filter brightness-90" alt="Yunnan Hotpot & BBQ">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-xs text-amber-500 font-cinzel tracking-widest font-semibold flex items-center gap-2">
                        <span>🍲</span> <span>DINING</span>
                    </div>
                </div>
                <div class="w-full lg:w-1/2 flex flex-col justify-center">
                    <h2 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> text-2xl md:text-[1.8rem] font-medium text-amber-400 drop-shadow-[0_0_10px_rgba(217,119,6,0.3)] mb-2"><?= $t['act5_title'] ?></h2>
                    <div class="text-[0.85rem] text-gray-400 uppercase tracking-[2px] font-cinzel mb-6"><?= $t['act5_sub'] ?></div>
                    <p class="<?= $lang == 'th' ? 'font-serif-thai text-base md:text-lg leading-relaxed' : 'text-sm md:text-base leading-loose font-light' ?> text-gray-300 mb-8">
                        <?= nl2br($t['act5_desc']) ?>
                    </p>
                    <div class="flex flex-wrap gap-4 text-xs text-gray-300 font-semibold mb-8">
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🍲 Black Chicken Soup</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🍢 Hot & Spicy Mala BBQ</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-2 rounded-full flex items-center gap-2">🏮 Red Lantern Atmosphere</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ICON SECTION (FEATURES) -->
    <section class="py-20 bg-[#080808]/40 backdrop-blur-md border-y border-white/5 px-6 md:px-12 text-center relative z-10">
        <h3 class="font-cinzel text-xs text-amber-500 tracking-[0.3em] uppercase mb-12"><?= $t['features_title'] ?></h3>
        <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8">
            <div class="glass-panel p-8 rounded-2xl flex flex-col items-center gap-4 hover-target transition duration-300 hover:-translate-y-2 hover:shadow-[0_0_20px_rgba(217,119,6,0.3)]">
                <span class="text-4xl text-amber-500">🛶</span>
                <h4 class="font-bold text-sm tracking-wide">Misty Boat</h4>
            </div>
            <div class="glass-panel p-8 rounded-2xl flex flex-col items-center gap-4 hover-target transition duration-300 hover:-translate-y-2 hover:shadow-[0_0_20px_rgba(217,119,6,0.3)]">
                <span class="text-4xl text-amber-500">🍵</span>
                <h4 class="font-bold text-sm tracking-wide">Yunnan Tea</h4>
            </div>
            <div class="glass-panel p-8 rounded-2xl flex flex-col items-center gap-4 hover-target transition duration-300 hover:-translate-y-2 hover:shadow-[0_0_20px_rgba(217,119,6,0.3)]">
                <span class="text-4xl text-amber-500">☁️</span>
                <h4 class="font-bold text-sm tracking-wide">Cinematic Mist</h4>
            </div>
            <div class="glass-panel p-8 rounded-2xl flex flex-col items-center gap-4 hover-target transition duration-300 hover:-translate-y-2 hover:shadow-[0_0_20px_rgba(217,119,6,0.3)]">
                <span class="text-4xl text-amber-500">👘</span>
                <h4 class="font-bold text-sm tracking-wide">Photo Shoot</h4>
            </div>
            <div class="glass-panel p-8 rounded-2xl flex flex-col items-center gap-4 hover-target transition duration-300 hover:-translate-y-2 hover:shadow-[0_0_20px_rgba(217,119,6,0.3)]">
                <span class="text-4xl text-amber-500">🍲</span>
                <h4 class="font-bold text-sm tracking-wide">Yunnan Dining</h4>
            </div>
        </div>
    </section>

    <!-- GALLERY SECTION (MASONRY) -->
    <section class="py-24 md:py-36 px-6 max-w-7xl mx-auto border-t border-white/5 relative z-10">
        <h2 class="font-cinzel text-center text-3xl md:text-5xl font-bold tracking-widest mb-16 uppercase text-white">
            <?= $t['gallery_title'] ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="masonry-gallery">
            <div class="overflow-hidden rounded-2xl h-[300px] relative glass-panel group hover-target cursor-explore border border-white/10">
                <img src="img/k1.jpg" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" alt="Gallery 1">
            </div>
            <div class="overflow-hidden rounded-2xl h-[450px] relative glass-panel group hover-target cursor-explore border border-white/10 md:row-span-2">
                <img src="img/k2.jpg" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" alt="Gallery 2">
            </div>
            <div class="overflow-hidden rounded-2xl h-[350px] relative glass-panel group hover-target cursor-explore border border-white/10">
                <img src="img/k3.jpg" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" alt="Gallery 3">
            </div>
            <div class="overflow-hidden rounded-2xl h-[400px] relative glass-panel group hover-target cursor-explore border border-white/10 md:row-span-2">
                <img src="img/k4.webp" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" alt="Gallery 4">
            </div>
        </div>
    </section>

    <!-- FULLSCREEN LIGHTBOX POPUP -->
    <div id="lightbox-modal" class="fixed inset-0 z-[100] bg-black/90 backdrop-blur-xl hidden flex items-center justify-center p-4">
        <button id="lightbox-close" class="absolute top-6 right-6 text-white hover:text-amber-500 transition-colors p-2 text-2xl font-bold">&times;</button>
        <img id="lightbox-img" src="" class="max-w-full max-h-[85vh] object-contain rounded-xl border border-white/10 shadow-2xl" alt="Enlarged View">
    </div>

    <!-- REVIEW SECTION (AUTO-SCROLL MARQUEE) -->
    <section class="py-20 md:py-32 bg-[#050505]/40 backdrop-blur-md border-t border-white/5 overflow-hidden relative z-10">
        <h2 class="font-cinzel text-center text-3xl font-bold tracking-widest mb-16 text-white uppercase"><?= $t['reviews_title'] ?></h2>
        
        <!-- Marquee container -->
        <div class="relative w-full flex items-center overflow-x-hidden py-4" id="reviews-marquee-container">
            <div class="flex gap-8 whitespace-nowrap animate-marquee" id="reviews-marquee">
                <?php for ($i = 0; $i < 3; $i++): // Duplicate rows for infinite scroll ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="bg-gradient-to-br from-[#1c1c1c] to-[#0a0a0a] p-10 flex flex-col h-[380px] md:h-[420px] w-[350px] md:w-[450px] shrink-0 text-left hover-target hover:border-amber-500/40 transition duration-300 theme-border-always shadow-2xl rounded-2xl relative overflow-hidden">
                            <div class="flex items-center justify-between mb-6">
                                <h4 class="<?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?> font-bold text-sm text-amber-500 uppercase tracking-widest"><?= $rev['name'] ?></h4>
                                <span class="text-[10px] text-gray-500 uppercase tracking-widest font-prompt"><?= $rev['date'] ?></span>
                            </div>
                            <div class="flex gap-1 text-sm text-amber-500 mb-6 theme-text-glow tracking-widest">
                                <?php for($star=1; $star<=5; $star++) { echo $star <= (isset($rev['rating']) ? $rev['rating'] :  5) ? '★' : '<span class="text-gray-700">★</span>'; } ?>
                            </div>
                            <?php 
                            $is_long = mb_strlen($rev['text']) > 100;
                            $short_text = $is_long ? mb_substr($rev['text'], 0, 100) . '...' : $rev['text'];
                            ?>
                            <p class="<?= $lang == 'th' ? 'font-prompt font-light' : 'font-cinzel italic' ?> text-gray-200 text-sm md:text-base leading-relaxed whitespace-normal mb-4 cursor-pointer" onclick="event.preventDefault(); event.stopPropagation(); Swal.fire({title: '<?= $lang == 'th' ? 'ความประทับใจ' : 'Experience Story' ?>', text: '<?= htmlspecialchars($rev['text'], ENT_QUOTES) ?>', confirmButtonColor: '#d97706', confirmButtonText: '<?= $lang == 'th' ? 'ปิด' : 'Close' ?>'})">
                                "<?= htmlspecialchars($short_text) ?>"
                                <?php if($is_long): ?>
                                <br><span class="text-amber-500 text-[10px] md:text-xs font-bold uppercase mt-2 inline-block hover:underline"><?= $lang == 'th' ? 'อ่านเพิ่มเติม' : 'Read more' ?></span>
                                <?php endif; ?>
                            </p>
                            <?php 
                            $images = [];
                            if (!empty($rev['image_path'])) {
                                $decoded = json_decode($rev['image_path'], true);
                                if (is_array($decoded)) {
                                    $images = $decoded;
                                } else {
                                    $images = [$rev['image_path']];
                                }
                            }
                            if (!empty($images)): 
                            ?>
                            <div class="flex gap-1 z-10 relative mt-auto w-full overflow-hidden rounded-xl border border-white/10">
                                <?php foreach($images as $idx => $img): ?>
                                    <?php if($idx < 3): ?>
                                    <div class="flex-1 min-w-0 cursor-pointer group/img relative" onclick="if(window.openLightbox) window.openLightbox('<?= htmlspecialchars($img) ?>')">
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
                            <div class="absolute -bottom-4 -right-4 text-8xl font-cn text-white/5 pointer-events-none">印</div>
                        </div>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>

        <style>
            .animate-marquee {
                display: flex;
                animation: marqueeRun 40s linear infinite;
            }
            .animate-marquee:hover {
                animation-play-state: paused;
            }
            @keyframes marqueeRun {
                0% { transform: translateX(0); }
                100% { transform: translateX(-33.33%); }
            }
            
            .theme-border-always {
                border: 1px solid #d97706 !important;
                box-shadow: 0 0 15px rgba(217, 119, 6, 0.4) !important;
            }
            .theme-text-glow {
                color: #d97706 !important;
                text-shadow: 0 0 20px rgba(217, 119, 6, 0.4) !important;
            }
        </style>
    </section>


    </section>

    <?php include 'components/footer.php'; ?>

    <!-- Scripts -->
    <script>
        history.scrollRestoration = "manual";
        window.scrollTo(0, 0);

        // 1. Lenis Smooth Scroll
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smooth: true
        });
        
        lenis.on('scroll', ScrollTrigger.update);
        
        gsap.ticker.add((time) => {
            lenis.raf(time * 1000);
        });
        gsap.ticker.lagSmoothing(0);

        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);


        // 3. Sakura Falling Effect (Replacing Gold Particles)
        const canvas = document.getElementById('particles-canvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            let sakuras = [];
            
            function resizeSakuraCanvas() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            resizeSakuraCanvas();
            window.addEventListener('resize', resizeSakuraCanvas);

            class Sakura {
                constructor() {
                    this.reset();
                }
                reset() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height - canvas.height;
                    this.size = Math.random() * 8 + 5; // Petal size
                    this.speedY = Math.random() * 0.7 + 0.3; // Slower falling down
                    this.speedX = Math.random() * 1 - 0.5; // Drift left/right
                    this.opacity = Math.random() * 0.5 + 0.5;
                    this.angle = Math.random() * 360;
                    this.spin = Math.random() * 0.04 - 0.02; // Slower spinning
                }
                update() {
                    this.y += this.speedY;
                    this.x += this.speedX + Math.sin(this.y * 0.01) * 0.5; // Fluttering effect
                    this.angle += this.spin;
                    
                    if (this.y > canvas.height + 50 || this.x < -50 || this.x > canvas.width + 50) {
                        this.y = -50;
                        this.x = Math.random() * canvas.width;
                    }
                }
                draw() {
                    ctx.save();
                    ctx.translate(this.x, this.y);
                    ctx.rotate(this.angle);
                    
                    // Draw petal shape
                    ctx.beginPath();
                    ctx.moveTo(0, 0);
                    ctx.quadraticCurveTo(this.size, -this.size, this.size * 2, 0);
                    ctx.quadraticCurveTo(this.size, this.size, 0, 0);
                    
                    ctx.fillStyle = `rgba(255, 183, 197, ${this.opacity})`;
                    ctx.fill();
                    
                    ctx.restore();
                }
            }

            for (let i = 0; i < 50; i++) {
                sakuras.push(new Sakura());
            }

            function animateSakura() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                sakuras.forEach(p => {
                    p.update();
                    p.draw();
                });
                requestAnimationFrame(animateSakura);
            }
            animateSakura();
        }

        // 4. GSAP / ScrollTrigger Reveal Animation
        gsap.registerPlugin(ScrollTrigger);

        // 3D Scroll Animations (Like Policy Page)
        gsap.utils.toArray('.reveal-card').forEach((card) => {
            // Apply 3D perspective to parent container for depth effect
            if (card.parentElement) {
                card.parentElement.style.perspective = '1500px';
            }
            
            // Initial state (pushed back and tilted down)
            gsap.set(card, {
                opacity: 0,
                y: 50,
                z: -100,
                rotateX: -10,
                scale: 0.95
            });

            // Animate to normal state as it enters viewport
            gsap.to(card, {
                scrollTrigger: {
                    trigger: card,
                    start: 'top 85%',
                    end: 'top 50%',
                    scrub: 1,
                    toggleActions: 'play none none reverse'
                },
                opacity: 1,
                y: 0,
                z: 0,
                rotateX: 0,
                scale: 1,
                ease: 'power3.out'
            });
        });

        // Navbar Logic
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            const currentScroll = window.pageYOffset;
            if (currentScroll > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
            lastScroll = currentScroll;
        });

        // Hamburger / Mobile Menu logic
        const hamBtn = document.getElementById('hamburger-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const closeMenuBtn = document.getElementById('close-menu-btn');
        const menuLinks = document.querySelectorAll('.mobile-link');

        function openMenu() {
            mobileMenu.classList.remove('hidden');
            mobileMenu.classList.add('flex');
            setTimeout(() => { mobileMenu.classList.remove('opacity-0'); }, 10);
        }

        function closeMenu() {
            mobileMenu.classList.add('opacity-0');
            setTimeout(() => {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('flex');
            }, 300);
        }

        if(hamBtn) hamBtn.addEventListener('click', openMenu);
        if(closeMenuBtn) closeMenuBtn.addEventListener('click', closeMenu);
        menuLinks.forEach(link => link.addEventListener('click', closeMenu));

        // 5. Masonry Gallery Lightbox logic
        const lightbox = document.getElementById('lightbox-modal');
        const lightboxImg = document.getElementById('lightbox-img');
        const lightboxClose = document.getElementById('lightbox-close');

        document.querySelectorAll('#masonry-gallery img').forEach(img => {
            img.addEventListener('click', () => {
                lightboxImg.src = img.src;
                lightbox.style.display = 'flex';
                lenis.stop(); // Stop scroll when lightbox is open
                if (window.resetCustomCursor) window.resetCustomCursor();
            });
        });

        lightboxClose.addEventListener('click', () => {
            lightbox.style.display = 'none';
            lenis.start(); // Resume scroll
            if (window.resetCustomCursor) window.resetCustomCursor();
        });

        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                lightbox.style.display = 'none';
                lenis.start();
                if (window.resetCustomCursor) window.resetCustomCursor();
            }
        });

        window.openLightbox = function(src) {
            lightboxImg.src = src;
            lightbox.style.display = 'flex';
            lenis.stop(); // Stop scroll when lightbox is open
            if (window.resetCustomCursor) window.resetCustomCursor();
        };



    </script>
    <script src="js/chatbot.js?v=<?= time() ?>"></script>
</body>
</html>

