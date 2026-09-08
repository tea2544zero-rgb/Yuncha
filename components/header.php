<?php
$base_path = (strpos($_SERVER['PHP_SELF'], '/room/') !== false) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="<?= isset($lang) ? $lang : 'th' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars(isset($hotel_name) ? $hotel_name : 'Yuncha Valley') ?> | Ultra Luxury Rak Thai</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.19/bundled/lenis.min.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <link rel="stylesheet" href="<?= $base_path ?>css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $base_path ?>css/chatbot.css?v=<?= time() ?>">
    
    <!-- Fancybox -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <script src="<?= $base_path ?>js/cursor.js?v=<?= time() ?>"></script>
    <?= isset($extra_head) ? $extra_head : '' ?>
</head>
<body class="antialiased selection:bg-amber-600 selection:text-white bg-[#020202] <?= isset($body_class) ? $body_class : '' ?>">

    <div class="cursor-dot"></div>
    <div class="cursor-ring"><span id="cursor-text"></span></div>

    <?php if (!isset($skip_preloader) || !$skip_preloader): ?>
    <div id="preloader" class="fixed inset-0 z-[99999] flex flex-col justify-center items-center bg-[#020202]">
        <div class="shutter" id="shutter-top"></div>
        <div class="shutter" id="shutter-bottom"></div>
        <div id="loader-content" class="relative z-10 text-center">
            <div class="progress-number"><span id="prog-bg">0</span><span class="progress-fill" id="prog-fill">0</span></div>
            <p class="text-amber-600 tracking-[0.4em] text-[10px] md:text-xs uppercase mt-6 opacity-80 animate-pulse font-cinzel">Entering The Mist</p>
        </div>
    </div>
    <?php endif; ?>

    <nav class="fixed top-0 left-0 w-full z-50 px-6 py-5 glass-nav transition-all duration-500" id="navbar">
        <div class="max-w-[1600px] mx-auto flex justify-between items-center w-full">
            
            <div class="flex items-center gap-2 hover-target cursor-pointer drop-shadow-md" onclick="window.location.href='<?= $base_path ?>index.php'">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l2.4 7.6L22 12l-7.6 2.4L12 22l-2.4-7.6L2 12l7.6-2.4L12 2z"/></svg>
                <div class="flex flex-col items-center">
                    <span class="font-cinzel text-xl md:text-3xl font-bold text-white block leading-none"><?= htmlspecialchars(isset($hotel_name) ? $hotel_name : 'Yuncha Valley') ?></span>
                    <span class="font-prompt text-[8px] md:text-[10px] text-gray-400 uppercase tracking-[0.4em] block mt-1.5">RESORT & TEA HOUSE</span>
                </div>
            </div>
            
            <div class="hidden xl:flex gap-10 items-center text-[13px] md:text-sm font-semibold tracking-widest text-white uppercase drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                <a href="<?= $base_path ?>index.php" class="nav-item group flex items-center gap-2 hover-target <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= isset($t['nav_home']) ? $t['nav_home'] : 'หน้าแรก' ?></span>
                </a>
                <a href="<?= $base_path ?>room/room.php" class="nav-item group flex items-center gap-2 hover-target <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= isset($t['nav_rooms']) ? $t['nav_rooms'] : 'ห้องพัก' ?></span>
                </a>
                <a href="<?= $base_path ?>activities.php" class="nav-item group flex items-center gap-2 hover-target <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= isset($t['nav_exp']) ? $t['nav_exp'] : 'กิจกรรม' ?></span>
                </a>
                <a href="<?= $base_path ?>policy.php" class="nav-item group flex items-center gap-2 hover-target <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= isset($t['nav_policy']) ? $t['nav_policy'] : 'นโยบาย' ?></span>
                </a>
                <a href="<?= $base_path ?>contact.php" class="nav-item group flex items-center gap-2 hover-target <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-base' : '' ?>">
                    <span class="nav-text relative pb-1 whitespace-nowrap"><?= isset($t['nav_contact']) ? $t['nav_contact'] : 'ติดต่อที่พัก' ?></span>
                </a>
            </div>

            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-2 text-sm font-bold tracking-widest">
                    <div class="flex items-center bg-black/20 border border-white/10 rounded-full p-1 backdrop-blur-md">
                        <a href="?lang=th" class="px-4 py-1.5 rounded-full text-[10px] font-bold tracking-widest transition-all duration-300 <?= (isset($lang) ? $lang : 'th') == 'th' ? 'bg-[#d97706] text-black shadow-[0_4px_15px_rgba(217,119,6,0.4)]' : 'text-white/50 hover:text-white' ?>">
                            TH
                        </a>
                        <a href="?lang=en" class="px-4 py-1.5 rounded-full text-[10px] font-bold tracking-widest transition-all duration-300 <?= (isset($lang) ? $lang : 'th') == 'en' ? 'bg-[#d97706] text-black shadow-[0_4px_15px_rgba(217,119,6,0.4)]' : 'text-white/50 hover:text-white' ?>">
                            EN
                        </a>
                    </div>
                    
                    <span class="opacity-50 text-gray-700 ml-2">|</span> 
                    
                    <a href="<?= $base_path ?>login.php" class="nav-item group cursor-pointer hover-target text-white hover:text-amber-500 uppercase <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai font-bold text-base' : 'font-cinzel font-bold' ?> ml-2">
                        <span class="nav-text relative pb-1 whitespace-nowrap"><?= isset($t['nav_signin']) ? $t['nav_signin'] : 'เข้าสู่ระบบ' ?></span>
                    </a>
                </div>
                
                <a href="<?= $base_path ?>booking.php" class="hidden md:flex items-center gap-2 hover-target text-sm tracking-[0.2em] btn-gradient-glow px-8 py-3.5 rounded-full uppercase <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai font-bold' : '' ?>">
                    <span class="whitespace-nowrap font-semibold"><?= isset($t['nav_book']) ? $t['nav_book'] : 'จองห้องพัก' ?></span>
                </a>
                <button id="hamburger-btn" class="xl:hidden text-white p-2 drop-shadow-md"><svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
            </div>
        </div>
    </nav>

    <div id="mobile-menu" class="fixed inset-0 bg-[#020202]/95 backdrop-blur-2xl z-[60] hidden flex-col items-center justify-center opacity-0 transition-opacity duration-300">
        <button id="close-menu-btn" class="absolute top-6 right-6 text-white p-2"><svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        <div class="flex flex-col items-center gap-6 w-full px-6 mt-10">
            <a href="<?= $base_path ?>index.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= isset($t['nav_home']) ? $t['nav_home'] : 'หน้าแรก' ?></span></a>
            <a href="<?= $base_path ?>room/room.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= isset($t['nav_rooms']) ? $t['nav_rooms'] : 'ห้องพัก' ?></span></a>
            <a href="<?= $base_path ?>activities.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= isset($t['nav_exp']) ? $t['nav_exp'] : 'กิจกรรม' ?></span></a>
            <a href="<?= $base_path ?>policy.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= isset($t['nav_policy']) ? $t['nav_policy'] : 'นโยบาย' ?></span></a>
            <a href="<?= $base_path ?>contact.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= isset($t['nav_contact']) ? $t['nav_contact'] : 'ติดต่อที่พัก' ?></span></a>
            <a href="<?= $base_path ?>login.php" class="mobile-link nav-item text-gray-300 hover:text-amber-500 <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-3xl' : 'font-cinzel text-2xl tracking-widest' ?>"><span class="nav-text relative pb-1"><?= isset($t['nav_signin']) ? $t['nav_signin'] : 'เข้าสู่ระบบ' ?></span></a>
            <a href="<?= $base_path ?>booking.php" class="mobile-link mt-8 bg-[#c2410c] text-white w-full py-4 rounded-full text-center <?= (isset($lang) ? $lang : 'th') == 'th' ? 'font-serif-thai text-2xl font-bold' : 'font-cinzel text-xl tracking-widest font-bold' ?>"><?= isset($t['nav_book']) ? $t['nav_book'] : 'จองห้องพัก' ?></a>
        </div>
        <div class="flex items-center bg-black/20 border border-white/10 rounded-full p-1 backdrop-blur-md mt-12">
            <a href="?lang=th" class="px-6 py-2 rounded-full text-xs font-bold tracking-widest transition-all duration-300 <?= (isset($lang) ? $lang : 'th') == 'th' ? 'bg-[#d97706] text-black shadow-[0_4px_15px_rgba(217,119,6,0.4)]' : 'text-white/50 hover:text-white' ?>">TH</a>
            <a href="?lang=en" class="px-6 py-2 rounded-full text-xs font-bold tracking-widest transition-all duration-300 <?= (isset($lang) ? $lang : 'th') == 'en' ? 'bg-[#d97706] text-black shadow-[0_4px_15px_rgba(217,119,6,0.4)]' : 'text-white/50 hover:text-white' ?>">EN</a>
        </div>
    </div>
