<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

// Load dynamic settings
// Removed legacy SQLite path
$site_settings = [];
try {
    if (true) {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdo = $conn;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) {
            $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    }
} catch (Exception $e) {}

$raw_phone = !empty($site_settings['phone']) ? $site_settings['phone'] : "080-342-9396 ( คุณเต้ )\n063-915-0091 ( คุณมิ้ง )\n053-123-456 ( RESORT )";
$phone = $lang === 'en' ? str_replace(['คุณเต้', 'คุณมิ้ง'], ['Mr. Tae', 'Ms. Ming'], $raw_phone) : $raw_phone;
$email = !empty($site_settings['email']) ? $site_settings['email'] : 'stay@yunchavalley.com';
$address = !empty($site_settings['address']) ? $site_settings['address'] : 'หมู่บ้านรักไทย ต.หมอกจำแป่ อ.เมือง จ.แม่ฮ่องสอน 58000';

$raw_map_url = !empty($site_settings['map_url']) ? trim($site_settings['map_url']) : '';

$default_embed = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3765.4194098902517!2d97.90150997598687!3d19.52627993740263!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30c25a07c11fdf1b%3A0xc31cb541fe38096f!2sBan%20Rak%20Thai!5e0!3m2!1sen!2sth!4v1700000000000!5m2!1sen!2sth';
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
        // User pasted a raw short link
        $map_link_url = $raw_map_url;
        $map_iframe_url = $default_embed; // Can't iframe a short link safely
    }
}

$hotel_name = !empty($site_settings['hotel_name']) ? $site_settings['hotel_name'] : 'Yuncha Valley';

$dict = [
    'th' => [
        'nav_home' => 'หน้าแรก', 'nav_rooms' => 'ห้องพัก', 'nav_exp' => 'กิจกรรม', 'nav_policy' => 'นโยบาย', 'nav_contact' => 'ติดต่อที่พัก', 'nav_book' => 'จองห้องพัก',
        'nav_signin' => 'เข้าสู่ระบบ',
        
        'hero_title' => 'ติดต่อเรา',
        'hero_sub' => 'ร่วมสัมผัสประสบการณ์การพักผ่อนเหนือระดับ ณ หุบเขาหยุนชา',
        'hero_scroll' => 'เลื่อนลงเพื่อติดต่อเรา',
        
        'form_title' => 'ส่งข้อความถึงเรา',
        'form_name' => 'ชื่อของคุณ',
        'form_email' => 'อีเมลของคุณ',
        'form_subject' => 'หัวข้อเรื่อง',
        'form_message' => 'ข้อความของคุณ',
        'form_submit' => 'ส่งข้อความ',
        'form_sending' => 'กำลังส่งข้อความ...',
        
        'info_title' => 'ข้อมูลการติดต่อ',
        'info_address_title' => 'ที่อยู่',
        'info_address' => $address,
        'info_phone_title' => 'เบอร์โทรศัพท์',
        'info_phone' => $phone,
        'info_email_title' => 'อีเมล',
        'info_email' => $email,
        
        'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. สงวนลิขสิทธิ์.'
    ],
    'en' => [
        'nav_home' => 'Home', 'nav_rooms' => 'Rooms', 'nav_exp' => 'Experiences', 'nav_policy' => 'Policies', 'nav_contact' => 'Contact', 'nav_book' => 'Booking',
        'nav_signin' => 'Sign In',
        
        'hero_title' => 'GET IN TOUCH',
        'hero_sub' => 'Experience the serenity of Yuncha Valley Resort & Tea House',
        'hero_scroll' => 'SCROLL DOWN TO INQUIRE',
        
        'form_title' => 'SEND A MESSAGE',
        'form_name' => 'Your Name',
        'form_email' => 'Your Email',
        'form_subject' => 'Subject',
        'form_message' => 'Your Message',
        'form_submit' => 'Send Message',
        'form_sending' => 'Sending Message...',
        
        'info_title' => 'CONTACT INFORMATION',
        'info_address_title' => 'Address',
        'info_address' => $address,
        'info_phone_title' => 'Phone',
        'info_phone' => $phone,
        'info_email_title' => 'Email',
        'info_email' => $email,
        
        'footer_rights' => '© 2026 ' . strtoupper($hotel_name) . '. ALL RIGHTS RESERVED.'
    ]
];

$t = $dict[$lang];
$body_class = "antialiased selection:bg-amber-600 selection:text-white bg-[#020202]";
$skip_preloader = true;
?>
<?php include 'components/header.php'; ?>

    <section class="relative w-full h-[60vh] flex flex-col justify-center items-center bg-black px-4 overflow-hidden">
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <video autoplay loop muted playsinline class="w-full h-full object-cover filter brightness-[0.4]">
                <source src="img/vi1.mp4" type="video/mp4">
            </video>
            <div class="sunlight-rays z-0"></div>
            <div class="fog-container z-0"><div class="fog-img-1"></div><div class="fog-img-2"></div></div>
            <div class="absolute bottom-0 left-0 w-full h-32 md:h-48 bg-gradient-to-b from-transparent to-[#020202] z-10"></div>
        </div>
        
        <div class="z-10 text-center relative pointer-events-none w-full mt-[10vh]">
            <div class="mask-wrap mb-4">
                <div class="slide-txt hero-text flex justify-center items-center gap-3">
                    <span class="text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">✦</span>
                    <p class="text-white tracking-[0.3em] md:tracking-[0.4em] text-[10px] md:text-sm uppercase font-cinzel font-semibold drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] m-0">
                        <?= $lang == 'th' ? 'ติดต่อที่พัก' : 'GET IN TOUCH' ?>
                    </p>
                    <span class="text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">✦</span>
                </div>
            </div>
            <div class="mask-wrap mb-4 block w-full text-center">
                <h1 class="slide-txt hero-text font-cinzel text-5xl md:text-7xl xl:text-8xl leading-tight text-white drop-shadow-[0_4px_15px_rgba(0,0,0,0.9)] uppercase tracking-wider m-0">
                    <?= $t['hero_title'] ?>
                </h1>
            </div>
            <div class="mask-wrap max-w-2xl mx-auto px-4 mt-2 block w-full text-center">
                <p class="slide-txt hero-text text-gray-200 font-light tracking-wide text-sm md:text-base md:text-lg leading-relaxed drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] m-0 <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>">
                    <?= $t['hero_sub'] ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <main class="relative z-10 pb-24 md:pb-32 px-6 max-w-[1400px] mx-auto mt-12 md:mt-16">
        
        <div class="max-w-4xl mx-auto flex flex-col gap-12 lg:gap-16 items-stretch">
            <!-- Info & Map -->
            <div class="flex flex-col gap-8 md:gap-12 w-full">
                <div class="glass-panel p-8 md:p-12 rounded-[20px] md:rounded-[24px] border border-amber-600/80 shadow-[0_0_15px_rgba(217,119,6,0.4)] relative overflow-hidden group hover:border-amber-500 transition-colors duration-500 bg-[#050505]">
                    <!-- Watermark -->
                    <div class="absolute -bottom-10 -right-10 text-9xl font-cn text-white/5 pointer-events-none select-none z-0 transform translate-x-2 translate-y-2">茶</div>
                    
                    <div class="mask-wrap mb-8 relative z-10">
                        <h2 class="slide-txt scroll-txt font-cinzel text-2xl md:text-3xl text-white uppercase font-bold">
                            <?= $t['info_title'] ?>
                        </h2>
                    </div>

                    <div class="flex flex-col gap-8 relative z-10">
                        <div class="flex gap-5 items-start group/item">
                            <div class="w-14 h-14 rounded-full border border-amber-500/30 flex items-center justify-center flex-shrink-0 text-amber-500 bg-amber-500/5 group-hover/item:bg-amber-500/20 group-hover/item:scale-110 transition-all duration-300">
                                <i class="ph ph-map-pin text-2xl"></i>
                            </div>
                            <div class="mask-wrap flex-1">
                                <div class="slide-txt scroll-txt">
                                    <h4 class="font-bold text-sm md:text-base text-white mb-2 tracking-widest <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $t['info_address_title'] ?></h4>
                                    <p class="text-gray-300 leading-relaxed text-sm md:text-base <?= $lang == 'th' ? 'font-serif-thai' : '' ?>"><?= $t['info_address'] ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-5 items-start group/item">
                            <div class="w-14 h-14 rounded-full border border-amber-500/30 flex items-center justify-center flex-shrink-0 text-amber-500 bg-amber-500/5 group-hover/item:bg-amber-500/20 group-hover/item:scale-110 transition-all duration-300">
                                <i class="ph ph-phone text-2xl"></i>
                            </div>
                            <div class="mask-wrap flex-1">
                                <div class="slide-txt scroll-txt">
                                    <h4 class="font-bold text-sm md:text-base text-white mb-2 tracking-widest <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $t['info_phone_title'] ?></h4>
                                    <p class="text-gray-300 leading-relaxed text-sm md:text-base font-light"><?= nl2br($t['info_phone']) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-5 items-start group/item">
                            <div class="w-14 h-14 rounded-full border border-amber-500/30 flex items-center justify-center flex-shrink-0 text-amber-500 bg-amber-500/5 group-hover/item:bg-amber-500/20 group-hover/item:scale-110 transition-all duration-300">
                                <i class="ph ph-envelope-simple text-2xl"></i>
                            </div>
                            <div class="mask-wrap flex-1">
                                <div class="slide-txt scroll-txt">
                                    <h4 class="font-bold text-sm md:text-base text-white mb-2 tracking-widest <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $t['info_email_title'] ?></h4>
                                    <p class="text-gray-300 leading-relaxed text-sm md:text-base font-light"><?= $t['info_email'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map -->
                <div class="glass-panel rounded-[20px] md:rounded-[24px] border border-amber-600/80 shadow-[0_0_15px_rgba(217,119,6,0.4)] overflow-hidden bg-[#050505]">
                    <div class="w-full h-[350px] md:h-[450px] relative group">
                        <div class="absolute inset-0 bg-[#020202]/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none z-10"></div>
                        <iframe 
                            src="<?= htmlspecialchars($map_iframe_url) ?>" 
                            class="w-full h-full border-0 transition-all duration-1000" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                    <div class="p-6 flex justify-between items-center border-t border-white/5">
                        <span class="text-gray-300 text-sm md:text-base tracking-widest uppercase font-cinzel font-bold">
                            <?= $lang == 'th' ? 'ที่ตั้งรีสอร์ท' : 'LOCATION MAP' ?>
                        </span>
                        <a href="<?= htmlspecialchars($map_link_url) ?>" target="_blank" class="hover-target text-amber-500 hover:text-amber-400 text-sm md:text-base font-bold tracking-widest flex items-center gap-2 transition-colors <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>">
                            <?= $lang == 'th' ? 'เปิดนำทาง' : 'GET DIRECTIONS' ?> <i class="ph-bold ph-arrow-up-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-10 left-1/2 -translate-x-1/2 bg-[#111]/90 backdrop-blur-md border border-white/10 px-6 py-3 rounded-2xl shadow-2xl z-[9999] flex items-center gap-3 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none">
        <div id="toast-icon"></div>
        <span id="toast-msg" class="text-white text-sm font-light"></span>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/chatbot.js?v=<?= time() ?>"></script>
    <script>
        // Toast Notification for Contact Form
        window.showToast = function(message, isSuccess = true) {
            const toast = document.getElementById('toast');
            const toastIcon = document.getElementById('toast-icon');
            const toastMsg = document.getElementById('toast-msg');
            
            toastMsg.textContent = message;
            if (isSuccess) {
                toastIcon.innerHTML = '<i class="ph-bold ph-check text-emerald-500 text-xl"></i>';
                toastIcon.className = 'w-8 h-8 rounded-full flex items-center justify-center text-emerald-500 bg-emerald-500/10 border border-emerald-500/20';
            } else {
                toastIcon.innerHTML = '<i class="ph-bold ph-x text-rose-500 text-xl"></i>';
                toastIcon.className = 'w-8 h-8 rounded-full flex items-center justify-center text-rose-500 bg-rose-500/10 border border-rose-500/20';
            }
            
            toast.style.opacity = '1';
            toast.style.transform = 'translate(-50%, 0)';
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, 5rem)';
                const form = document.getElementById('contactForm');
                if(form) form.reset();
            }, 3000);
        }
    </script>
</body>
</html>
