<?php
$base_path = (strpos($_SERVER['PHP_SELF'], '/room/') !== false) ? '../' : '';
$logo_path = !empty($site_settings['hotel_logo']) ? $base_path . 'dashboard/uploads/' . $site_settings['hotel_logo'] : $base_path . 'img/logo.png';
$hotel_name_display = htmlspecialchars(isset($hotel_name) ? $hotel_name : (isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] : 'Yuncha Valley'));

$footer_address = htmlspecialchars(isset($site_settings['address']) ? $site_settings['address'] : '123 Tea Mountain Rd, Chiang Rai, TH');
$raw_phone = htmlspecialchars(isset($site_settings['phone']) ? $site_settings['phone'] : '+66 80 342 9396');
$footer_phone = $lang === 'en' ? str_replace(['คุณเต้', 'คุณมิ้ง'], ['Mr. Tae', 'Ms. Ming'], $raw_phone) : $raw_phone;
$footer_email = htmlspecialchars(isset($site_settings['email']) ? $site_settings['email'] : 'contact@yunchavalley.com');

$menu_home = isset($t['nav_home']) ? $t['nav_home'] : ($lang == 'th' ? 'หน้าแรก' : 'Home');
$menu_rooms = isset($t['nav_rooms']) ? $t['nav_rooms'] : ($lang == 'th' ? 'ห้องพัก' : 'Rooms');
$menu_exp = isset($t['nav_exp']) ? $t['nav_exp'] : ($lang == 'th' ? 'กิจกรรม' : 'Activities');
$menu_contact = isset($t['nav_contact']) ? $t['nav_contact'] : ($lang == 'th' ? 'ติดต่อเรา' : 'Contact');
$menu_policy = isset($t['nav_policy']) ? $t['nav_policy'] : ($lang == 'th' ? 'นโยบาย' : 'Policy');

$text_menu = $lang == 'th' ? 'เมนู' : 'Menu';
$text_contact_title = $lang == 'th' ? 'ติดต่อเรา' : 'Contact Us';
$text_follow = $lang == 'th' ? 'ติดตามฉัน' : 'Follow Us';
$text_back_top = $lang == 'th' ? 'กลับสู่ด้านบน' : 'Back to Top';
$text_desc = $lang == 'th' ? 'พักผ่อนอย่างเหนือระดับ ท่ามกลางธรรมชาติและไร่ชาที่หมู่บ้านรักไทย' : 'Experience ultra luxury amidst nature and tea plantations in Ban Rak Thai.';

$footer_rights = isset($t['footer_rights']) ? $t['footer_rights'] : '© 2026 ' . strtoupper($hotel_name_display) . '. ALL RIGHTS RESERVED.';
?>
<footer class="pt-16 pb-8 bg-[#020202] border-t border-white/10 mt-auto">
    <div class="max-w-[1400px] mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8 border-b border-white/10 pb-12">
            <!-- Col 1: Logo & Info -->
            <div class="flex flex-col items-center text-center">
                <div class="flex flex-col items-center mb-6">
                    <div class="w-24 h-24 bg-white border border-white/10 rounded-full flex items-center justify-center overflow-hidden mb-4 shadow-[0_0_15px_rgba(255,255,255,0.05)]">
                        <img src="<?= $logo_path ?>" class="w-full h-full object-cover">
                    </div>
                    <span class="font-cinzel text-xl md:text-2xl font-bold text-amber-500 tracking-widest uppercase text-center"><?= $hotel_name_display ?></span>
                    <span class="font-prompt text-[9px] text-gray-500 tracking-[0.3em] uppercase mt-1 text-center">Resort & Tea House</span>
                </div>
                <p class="text-sm text-gray-400 leading-relaxed max-w-xs text-center <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>">
                    <?= $text_desc ?>
                </p>
            </div>

            <!-- Col 2: Menu -->
            <div class="flex flex-col items-center md:items-start text-center md:text-left lg:pl-12">
                <h4 class="font-bold text-white mb-6 tracking-widest uppercase <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $text_menu ?></h4>
                <ul class="space-y-4 text-sm text-gray-400 <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?>">
                    <li><a href="<?= $base_path ?>index.php" class="hover:text-amber-500 transition-colors"><?= $menu_home ?></a></li>
                    <li><a href="<?= $base_path ?>room/room.php" class="hover:text-amber-500 transition-colors"><?= $menu_rooms ?></a></li>
                    <li><a href="<?= $base_path ?>activities.php" class="hover:text-amber-500 transition-colors"><?= $menu_exp ?></a></li>
                    <li><a href="<?= $base_path ?>policy.php" class="hover:text-amber-500 transition-colors"><?= $menu_policy ?></a></li>
                    <li><a href="<?= $base_path ?>contact.php" class="hover:text-amber-500 transition-colors"><?= $menu_contact ?></a></li>
                </ul>
            </div>

            <!-- Col 3: Contact Info -->
            <div class="flex flex-col items-center md:items-start text-center md:text-left">
                <h4 class="font-bold text-white mb-6 tracking-widest uppercase <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $text_contact_title ?></h4>
                <ul class="space-y-4 text-sm text-gray-400 <?= $lang == 'th' ? 'font-serif-thai' : 'font-light' ?> max-w-xs">
                    <li class="flex items-start gap-3 justify-center md:justify-start">
                        <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-left"><?= nl2br($footer_address) ?></span>
                    </li>
                    <li class="flex items-center gap-3 justify-center md:justify-start">
                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span><?= nl2br($footer_phone) ?></span>
                    </li>
                    <li class="flex items-center gap-3 justify-center md:justify-start">
                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:<?= $footer_email ?>" class="hover:text-amber-500 transition-colors break-all"><?= $footer_email ?></a>
                    </li>
                </ul>
            </div>

            <!-- Col 4: Social & Back to Top -->
            <div class="flex flex-col items-center md:items-start justify-start h-full">
                <div class="flex flex-col items-center md:items-start w-full mb-10">
                    <h4 class="font-bold text-white mb-6 tracking-widest uppercase text-center md:text-left <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $text_follow ?></h4>
                    <div class="flex justify-center md:justify-start gap-4 w-full">
                        <a href="#" class="w-10 h-10 rounded-full bg-[#1877F2] flex items-center justify-center text-white hover:-translate-y-1 hover:shadow-[0_4px_12px_rgba(24,119,242,0.4)] transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-[#00C300] flex items-center justify-center text-white hover:-translate-y-1 hover:shadow-[0_4px_12px_rgba(0,195,0,0.4)] transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.035 2 11c0 2.82 1.488 5.342 3.824 7.027.354.238.835.736.681 1.432-.15.68-.456 1.638-.538 1.93-.162.584.288.583.56.386.377-.272 2.37-1.748 3.32-2.392.68.217 1.41.332 2.153.332 5.523 0 10-4.035 10-9s-4.477-9-10-9z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#f9ce34] via-[#ee2a7b] to-[#6228d7] flex items-center justify-center text-white hover:-translate-y-1 hover:shadow-[0_4px_12px_rgba(238,42,123,0.4)] transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-[#FF0000] flex items-center justify-center text-white hover:-translate-y-1 hover:shadow-[0_4px_12px_rgba(255,0,0,0.4)] transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 0 1-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 0 1-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 0 1 1.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418zM10 15l5-3-5-3v6z" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                </div>

                <div class="flex flex-col items-center md:items-start w-full">
                    <h4 class="font-bold text-white mb-6 tracking-widest uppercase text-center md:text-left <?= $lang == 'th' ? 'font-serif-thai' : 'font-cinzel' ?>"><?= $text_back_top ?></h4>
                    <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="w-14 h-14 rounded-full border border-gray-600 flex items-center justify-center text-gray-400 hover:text-white hover:border-amber-500 hover:shadow-[0_0_15px_rgba(217,119,6,0.5)] transition-all duration-500 group">
                        <svg class="w-6 h-6 group-hover:-translate-y-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="pt-8 text-center flex flex-col items-center">
            <p class="text-[10px] text-gray-500 tracking-widest uppercase font-cinzel"><?= $footer_rights ?></p>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
