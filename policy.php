<?php
session_start();

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] :  'th';

require_once 'dashboard/config/db.php';

$policies = [];
$site_settings = [];

try {
    // Load policies
    $stmt = $conn->query("SELECT * FROM policies ORDER BY id ASC");
    if ($stmt) {
        $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Load dynamic settings
    $stmt_settings = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($stmt_settings) {
        $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
    }
} catch (Exception $e) {
    // Silent catch, errors handled natively by display
}

$hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'Yuncha Valley';
$raw_phone = isset($site_settings['phone']) ? $site_settings['phone'] :  "080-342-9396 ( คุณเต้ )\n063-915-0091 ( คุณมิ้ง )\n053-123-456 ( RESORT )";
$phone = $lang === 'en' ? str_replace(['คุณเต้', 'คุณมิ้ง'], ['Mr. Tae', 'Ms. Ming'], $raw_phone) : $raw_phone;
$email = isset($site_settings['email']) ? $site_settings['email'] :  'stay@yunchavalley.com';
$address = isset($site_settings['address']) ? $site_settings['address'] :  'หมู่บ้านรักไทย ต.หมอกจำแป่ อ.เมือง จ.แม่ฮ่องสอน 58000';

$dict = [
    'th' => [
        'nav_home' => 'หน้าแรก', 'nav_rooms' => 'ห้องพัก', 'nav_exp' => 'กิจกรรม', 'nav_policy' => 'นโยบาย', 'nav_contact' => 'ติดต่อที่พัก', 'nav_book' => 'จองห้องพัก', 'nav_signin' => 'เข้าสู่ระบบ',
        'loc' => 'ที่ตั้ง', 'loc_desc' => $address,
        'contact' => 'ติดต่อ', 'contact_desc' => $phone,
        'email' => 'อีเมล', 'email_desc' => $email,
        'hero_title' => 'นโยบายและเงื่อนไข',
        'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. สงวนลิขสิทธิ์.'
    ],
    'en' => [
        'nav_home' => 'Home', 'nav_rooms' => 'Rooms', 'nav_exp' => 'Experiences', 'nav_policy' => 'Policies', 'nav_contact' => 'Contact', 'nav_book' => 'Booking', 'nav_signin' => 'Sign In',
        'loc' => 'LOCATION', 'loc_desc' => $address,
        'contact' => 'CONTACT', 'contact_desc' => $phone,
        'email' => 'EMAIL', 'email_desc' => $email,
        'hero_title' => 'Policies & Terms',
        'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. ALL RIGHTS RESERVED.'
    ]
];
$t = $dict[$lang];
$body_class = "antialiased selection:bg-amber-600 selection:text-white bg-[#020202]";
$skip_preloader = true;
$extra_head = '<link rel="stylesheet" href="css/policy.css?v=' . time() . '">';
?>
<?php include 'components/header.php'; ?>

    <!-- Sakura Effect Styles -->
    <style>
        .sakuras {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }
        .sakura-petal {
            position: absolute;
            background: linear-gradient(135deg, #ffb7c5, #ff8da1);
            border-radius: 15px 0px 15px 0px;
            filter: drop-shadow(0 0 5px rgba(255, 183, 197, 0.5));
            animation: sakura-fall linear infinite;
        }
        @keyframes sakura-fall {
            0% { transform: translateY(-10vh) translateX(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(110vh) translateX(50px) rotate(360deg); opacity: 0; }
        }
    </style>

    <!-- Hero Section -->
    <section class="policy-hero relative flex flex-col justify-center items-center overflow-hidden !h-[100svh]">
        <!-- Center texts -->
        <div class="z-10 text-center relative pointer-events-none w-full max-w-4xl px-4 animate-fade-in-down -mt-20 md:-mt-32">
            <h1 class="font-cinzel text-4xl md:text-5xl lg:text-7xl tracking-[4px] md:tracking-[8px] font-bold text-white drop-shadow-2xl theme-glow-gold uppercase" style="text-shadow: 0 0 25px rgba(217, 119, 6, 0.6); margin:0;">
                <?= $lang == 'th' ? 'ข้อตกลงและนโยบาย' : 'POLICIES & TERMS' ?>
            </h1>
            <div class="w-24 h-[1px] bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto my-6"></div>
            <p class="font-cinzel text-gray-200 text-sm md:text-lg lg:text-xl font-light tracking-[3px] drop-shadow-md" style="margin:0;">
                <?= $lang == 'th' ? 'เงื่อนไขการให้บริการของ ' . htmlspecialchars($hotel_name) : 'Terms of Service for ' . htmlspecialchars($hotel_name) ?>
            </p>
        </div>

        <!-- Scroll Down Indicator -->
        <div class="absolute bottom-12 md:bottom-16 z-10 flex flex-col items-center gap-2 animate-bounce opacity-70">
            <span class="font-cinzel text-xs md:text-base tracking-[0.2em] md:tracking-[0.3em] text-amber-500 uppercase font-semibold">
                <?= $lang == 'th' ? 'เลื่อนเพื่อดูนโยบายที่พัก' : 'SCROLL TO VIEW POLICIES' ?>
            </span>
            <svg class="w-7 h-7 md:w-9 md:h-9 text-amber-500 mt-1 md:mt-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
    </section>

    <!-- Policies 3D Scrolling Container -->
    <main class="policy-container">
        <?php if (empty($policies)): ?>
            <p style="text-align:center; color:#9ca3af;">No policies found. Please check your database.</p>
        <?php else: ?>
            <?php foreach ($policies as $index => $policy): ?>
                <div class="policy-card">
                    <div class="policy-header">
                        <div class="policy-icon"><?= htmlspecialchars($policy['icon_code']) ?></div>
                        <div class="policy-title-wrapper">
                            <h2 class="policy-title font-cinzel"><?= htmlspecialchars($policy['category_name_' . $lang]) ?></h2>
                            <span class="policy-title-en font-cinzel"><?= htmlspecialchars($policy['category_name_en']) ?></span>
                        </div>
                    </div>
                    <div class="policy-content">
                        <?= nl2br(htmlspecialchars($policy['content_' . $lang])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php include 'components/footer.php'; ?>

    <!-- Scripts via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.19/bundled/lenis.min.js"></script>
    
    <!-- Custom JS -->
    <script src="js/main.js?v=<?= time() ?>"></script>
    <script src="js/policy.js?v=<?= time() ?>"></script>
    <script src="js/chatbot.js?v=<?= time() ?>"></script>
    <script>
        // Sakura Effect
        function createSakuras() {
            const container = document.createElement('div');
            container.className = 'sakuras';
            document.body.appendChild(container);
            
            const count = window.innerWidth < 768 ? 20 : 40;
            
            for(let i=0; i<count; i++) {
                let petal = document.createElement('div');
                petal.className = 'sakura-petal';
                
                let width = Math.random() * 6 + 6; 
                let height = Math.random() * 4 + 8; 
                petal.style.width = width + 'px';
                petal.style.height = height + 'px';
                
                petal.style.left = Math.random() * 100 + 'vw';
                petal.style.animationDuration = (Math.random() * 8 + 7) + 's'; 
                petal.style.animationDelay = '-' + (Math.random() * 10) + 's'; 
                petal.style.opacity = Math.random() * 0.5 + 0.3;
                
                container.appendChild(petal);
            }
        }
        createSakuras();
    </script>
</body>
</html>

