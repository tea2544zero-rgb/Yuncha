<?php
session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) $_SESSION['lang'] = $_GET['lang'];
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : (isset($_COOKIE['admin_lang']) ? $_COOKIE['admin_lang'] : 'th');
if (!in_array($lang, ['th', 'en'])) $lang = 'th';
setcookie('admin_lang', $lang, time() + (86400 * 30), "/");

// Removed legacy SQLite path
$site_settings = [];
if (true) {
    try {
        require_once __DIR__ . '/dashboard/config/db.php';
        $pdo = $conn;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {}
}
$hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] : 'Yuncha Valley';
$logo_path = !empty($site_settings['hotel_logo']) ? 'dashboard/uploads/' . $site_settings['hotel_logo'] : 'img/logo.png';

$t = [
    'th' => [
        'nav_home' => 'หน้าแรก', 'title_guest' => 'ยินดีต้อนรับ', 'subtitle_guest' => 'เข้าสู่ระบบเพื่อดูข้อมูลการจองของคุณ',
        'welcome_title' => 'สวัสดีค่ะ!', 'welcome_desc' => 'ยินดีต้อนรับสู่ Yuncha Valley สัมผัสความงามเหนือกาลเวลาที่หมู่บ้านรักไทย',
        'booking_id' => 'รหัสการจอง (Booking ID)', 'booking_id_ph' => 'YC-XXXXXX', 'phone' => 'เบอร์โทรศัพท์', 'phone_ph' => '08X-XXX-XXXX',
        'btn_guest' => 'ดูข้อมูลการจอง', 'staff_link' => 'สำหรับพนักงาน',
        'title_staff' => 'YUNCHA OS', 'subtitle_staff' => 'ระบบจัดการรีสอร์ทอัจฉริยะ',
        'staff_welcome' => 'สวัสดีทีมงาน!', 'staff_desc' => 'เข้าสู่ระบบจัดการหลังบ้านของ Yuncha Valley Resort',
        'btn_staff' => 'เข้าสู่ระบบพนักงาน', 'guest_link' => 'กลับหน้าลูกค้า',
        'staff_note' => '* ระบบจะเปลี่ยนหน้าไปยัง Yuncha OS',
    ],
    'en' => [
        'nav_home' => 'Home', 'title_guest' => 'Welcome', 'subtitle_guest' => 'Sign in to access your reservation details.',
        'welcome_title' => 'Hello!', 'welcome_desc' => 'Welcome to Yuncha Valley. Experience timeless beauty at Ban Rak Thai.',
        'booking_id' => 'Booking ID', 'booking_id_ph' => 'YC-XXXXXX', 'phone' => 'Phone', 'phone_ph' => '+66 8X XXX XXXX',
        'btn_guest' => 'Access Booking', 'staff_link' => 'For Staff',
        'title_staff' => 'YUNCHA OS', 'subtitle_staff' => 'Smart Resort Management System',
        'staff_welcome' => 'Hello Team!', 'staff_desc' => 'Access the Yuncha Valley Resort back-office system.',
        'btn_staff' => 'Access Staff Portal', 'guest_link' => 'Back to Guest',
        'staff_note' => '* Redirects to Yuncha OS',
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hotel_name) ?> - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sriracha&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="js/cursor.js?v=<?= time() ?>"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sriracha&display=swap');
        @font-face {
            font-family: 'NP Chinese New Year';
            src: url('fonts/NP-Chinese-New-Year.ttf') format('truetype');
            font-weight: normal; font-style: normal; font-display: swap;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'NP Chinese New Year', 'Sriracha', cursive;
            background: #020202; color: #fff; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        @media (any-pointer: fine) {
            body, * { cursor: none !important; }
        }
        @media not all and (any-pointer: fine) {
            .cursor-dot, .cursor-ring { display: none !important; }
        }
        .cursor-dot { position: fixed; top: 0; left: 0; width: 6px; height: 6px; background: #d97706; border-radius: 50%; pointer-events: none; z-index: 100000; transform: translate(-50%, -50%); transition: opacity 0.2s; }
        .cursor-ring { position: fixed; top: 0; left: 0; width: 40px; height: 40px; border: 1px solid rgba(217,119,6,0.6); border-radius: 50%; pointer-events: none; z-index: 99999; transform: translate(-50%, -50%); transition: width 0.3s, height 0.3s, background 0.3s, border 0.3s; display: flex; justify-content: center; align-items: center; color: transparent; font-size: 9px; font-weight: bold; letter-spacing: 2px; }
        /* Ambient */
        .ambient { position:absolute; width:80vw; height:80vw; background:radial-gradient(circle,rgba(217,119,6,0.08) 0%,transparent 60%); top:50%; left:50%; transform:translate(-50%,-50%); pointer-events:none; z-index:0; }
        /* Sakura */
        .sakuras { position:fixed; top:0; left:0; width:100vw; height:100vh; pointer-events:none; z-index:1; }
        .sakura-petal { position:absolute; background:linear-gradient(135deg,#ffb7c5,#ff8da1); border-radius:15px 0 15px 0; filter:drop-shadow(0 0 5px rgba(255,183,197,0.5)); animation:sakura-fall linear infinite; }
        @keyframes sakura-fall { 0%{transform:translateY(-10vh) translateX(0) rotate(0deg);opacity:0} 10%{opacity:1} 90%{opacity:1} 100%{transform:translateY(110vh) translateX(50px) rotate(360deg);opacity:0} }
        /* Top bar */
        .top-bar { position:absolute; top:0; left:0; right:0; padding:24px 32px; display:flex; justify-content:space-between; align-items:center; z-index:50; }
        .top-bar a { text-decoration:none; }
        .back-link { display:flex; align-items:center; gap:8px; color:rgba(255,255,255,0.5); font-size:12px; letter-spacing:0.15em; text-transform:uppercase; transition:color 0.3s; }
        .back-link:hover { color:#d97706; }
        .lang-toggle { display:flex; align-items:center; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); border-radius:999px; padding:4px; backdrop-filter:blur(10px); }
        .lang-toggle a { padding:6px 16px; border-radius:999px; font-size:10px; font-weight:600; letter-spacing:0.15em; transition:all 0.3s; text-decoration:none; color:rgba(255,255,255,0.5); }
        .lang-toggle a:hover { color:#fff; }
        .lang-toggle a.active { background:#d97706; color:#000; box-shadow:0 4px 15px rgba(217,119,6,0.4); }
        /* Card Flip */
        .flip-perspective { perspective:2000px; z-index:10; width:100%; max-width:860px; height:520px; position:relative; }
        .flip-inner { position:relative; width:100%; height:100%; transition:transform 0.8s cubic-bezier(0.4,0,0.2,1); transform-style:preserve-3d; }
        .flip-inner.flipped { transform:rotateY(180deg); }
        .flip-front, .flip-back { position:absolute; inset:0; backface-visibility:hidden; -webkit-backface-visibility:hidden; border-radius:20px; overflow:hidden; display:flex; box-shadow:0 40px 80px rgba(0,0,0,0.8); border:1px solid rgba(255,255,255,0.05); }
        .flip-back { transform:rotateY(180deg); }
        
        /* Fix for clicking on 3D flipped faces */
        .flip-inner:not(.flipped) .flip-back { pointer-events: none; }
        .flip-inner.flipped .flip-front { pointer-events: none; }
        .flip-inner.flipped .flip-back { pointer-events: auto; }

        /* Panels */
        .panel-form { flex:1; background:rgba(10,10,10,0.85); backdrop-filter:blur(30px); padding:48px 40px; display:flex; flex-direction:column; justify-content:center; position:relative; z-index:2; }
        .panel-deco { flex:1; position:relative; overflow:hidden; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:40px; }
        .panel-deco::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(217,119,6,0.15),rgba(0,0,0,0.9)); z-index:1; }
        .panel-deco img.bg-img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0.4; }
        .panel-deco .deco-content { position:relative; z-index:2; }
        /* Logo */
        .logo-wrap { width:80px; height:80px; border-radius:50%; border:1.5px solid rgba(217,119,6,0.5); background:#0a0a0a; display:flex; align-items:center; justify-content:center; overflow:hidden; box-shadow:0 0 20px rgba(212,175,55,0.15); margin:0 auto 16px; }
        .logo-wrap img { width:100%; height:100%; object-fit:cover; }
        /* Form elements */
        .form-title { font-size:22px; color:#fff; text-align:center; margin-bottom:4px; }
        .form-subtitle { font-size:12px; color:rgba(255,255,255,0.4); text-align:center; margin-bottom:28px; }
        .input-group { margin-bottom:20px; }
        .input-label { display:block; font-size:11px; text-transform:uppercase; letter-spacing:0.1em; color:#d97706; margin-bottom:4px; }
        .input-field { width:100%; background:rgba(255,255,255,0.02); border:none; border-bottom:1px solid rgba(255,255,255,0.2); color:#fff; padding:12px 0; font-size:14px; font-family:'NP Chinese New Year','Sriracha',cursive; transition:all 0.3s; }
        .input-field:focus { outline:none; border-bottom-color:#d97706; background:rgba(212,175,55,0.03); padding-left:8px; }
        .input-field::placeholder { color:rgba(255,255,255,0.2); }
        .phone-wrap { position:relative; }
        .phone-wrap .toggle-vis { position:absolute; right:0; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,0.4); cursor:pointer; padding:8px; transition:color 0.3s; }
        .phone-wrap .toggle-vis:hover { color:#d97706; }
        /* Buttons */
        .btn-gold { width:100%; background:linear-gradient(135deg,#f59e0b,#ea580c); color:#fff; padding:14px; border-radius:10px; font-size:15px; font-family:'NP Chinese New Year','Sriracha',cursive; border:none; cursor:pointer; transition:all 0.3s; margin-top:8px; box-shadow:0 0 15px rgba(245,158,11,0.4); letter-spacing:0.05em; }
        .btn-gold:hover { transform:translateY(-2px); box-shadow:0 10px 30px rgba(245,158,11,0.5); background:linear-gradient(135deg,#fbbf24,#f97316); }
        .btn-outline { width:100%; background:transparent; color:#d97706; padding:14px; border-radius:10px; font-size:15px; font-family:'NP Chinese New Year','Sriracha',cursive; border:1px solid rgba(217,119,6,0.4); cursor:pointer; transition:all 0.3s; text-decoration:none; display:block; text-align:center; }
        .btn-outline:hover { background:rgba(217,119,6,0.1); border-color:#d97706; }
        /* Staff link (hidden trigger) */
        .staff-trigger { font-size:11px; color:rgba(255,255,255,0.25); text-align:center; margin-top:20px; cursor:pointer; transition:color 0.3s; letter-spacing:0.05em; }
        .staff-trigger:hover { color:#d97706; }
        /* Deco text */
        .deco-title { font-size:28px; color:#fff; margin-bottom:12px; text-shadow:0 2px 10px rgba(0,0,0,0.5); }
        .deco-desc { font-size:13px; color:rgba(255,255,255,0.7); line-height:1.8; max-width:260px; margin:0 auto 24px; }
        .deco-flip-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 24px; border:1px solid rgba(255,255,255,0.3); border-radius:999px; color:#fff; font-size:12px; cursor:pointer; background:rgba(255,255,255,0.05); backdrop-filter:blur(10px); transition:all 0.3s; font-family:'NP Chinese New Year','Sriracha',cursive; text-decoration:none; letter-spacing:0.05em; }
        .deco-flip-btn:hover { background:rgba(255,255,255,0.15); border-color:#fff; }
        /* Staff note */
        .staff-note { font-size:10px; color:rgba(255,255,255,0.3); text-align:center; margin-top:16px; letter-spacing:0.1em; text-transform:uppercase; }
        /* Hotel name display */
        .hotel-name { font-size:20px; color:#d97706; letter-spacing:0.2em; text-transform:uppercase; text-align:center; margin-bottom:2px; }
        .hotel-sub { font-size:9px; color:rgba(255,255,255,0.4); letter-spacing:0.3em; text-transform:uppercase; text-align:center; margin-bottom:24px; }
        /* Chinese watermark */
        .cn-watermark { position:absolute; bottom:-20px; right:-10px; font-size:120px; color:rgba(255,255,255,0.03); pointer-events:none; z-index:0; line-height:1; }
        /* SweetAlert */
        .swal2-popup { font-family:'NP Chinese New Year','Sriracha',cursive !important; border-radius:16px !important; }
        /* Responsive */
        @media(max-width:768px) {
            .flip-perspective { max-width:calc(100vw - 32px); height:auto; min-height:480px; }
            .flip-front, .flip-back { flex-direction:column; position:relative; }
            .flip-inner { height:auto; transition: none; }
            .flip-inner.flipped { transform: none; }
            .flip-inner.flipped .flip-front { display:none; }
            .flip-inner.flipped .flip-back { position:relative; transform:none; display:flex; }
            .flip-inner:not(.flipped) .flip-back { display:none; }
            .flip-inner:not(.flipped) .flip-front { position:relative; }
            .panel-deco { min-height:180px; }
            .panel-form { padding:32px 24px; }
            .top-bar { padding:16px 20px; }
        }
    </style>
</head>
<body>
    <!-- Custom Cursor -->
    <div class="cursor-dot"></div>
    <div class="cursor-ring"><span id="cursor-text"></span></div>
    <div class="ambient"></div>

    <!-- Top Bar -->
    <div class="top-bar">
        <a href="index.php" class="back-link">
            <span style="font-size:16px;">←</span>
            <span><?= $t[$lang]['nav_home'] ?></span>
        </a>
        <div class="lang-toggle">
            <a href="?lang=th" class="<?= $lang=='th'?'active':'' ?>">TH</a>
            <a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">EN</a>
        </div>
    </div>

    <!-- Flip Card -->
    <div class="flip-perspective">
        <div class="flip-inner" id="flipCard">

            <!-- FRONT: Guest Login -->
            <div class="flip-front">
                <div class="panel-form">
                    <div class="logo-wrap">
                        <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo">
                    </div>
                    <div class="hotel-name"><?= htmlspecialchars($hotel_name) ?></div>
                    <div class="hotel-sub">Resort & Tea House</div>

                    <div class="form-title"><?= $t[$lang]['title_guest'] ?></div>
                    <div class="form-subtitle"><?= $t[$lang]['subtitle_guest'] ?></div>

                    <form onsubmit="handleLogin(event)">
                        <input type="hidden" name="user_type" value="guest">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="input-group">
                            <label class="input-label"><?= $t[$lang]['booking_id'] ?></label>
                            <input type="text" name="booking_ref" class="input-field" placeholder="<?= $t[$lang]['booking_id_ph'] ?>" required style="text-transform:uppercase;">
                        </div>
                        <div class="input-group">
                            <label class="input-label"><?= $t[$lang]['phone'] ?></label>
                            <div class="phone-wrap">
                                <input type="password" id="phone_input" name="phone" class="input-field" placeholder="<?= $t[$lang]['phone_ph'] ?>" required style="padding-right:40px;">
                                <button type="button" class="toggle-vis" onclick="togglePhone()">
                                    <svg id="eye_icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn-gold"><?= $t[$lang]['btn_guest'] ?></button>
                    </form>
                    <div class="staff-trigger" onclick="flipToStaff()"><?= $t[$lang]['staff_link'] ?> →</div>
                    <div class="cn-watermark">茶</div>
                </div>
                <div class="panel-deco">
                    <img src="img/m11.jpg" alt="" class="bg-img">
                    <div class="deco-content">
                        <div class="deco-title"><?= $t[$lang]['welcome_title'] ?></div>
                        <div class="deco-desc"><?= $t[$lang]['welcome_desc'] ?></div>
                    </div>
                </div>
            </div>

            <!-- BACK: Staff Login -->
            <div class="flip-back">
                <div class="panel-deco">
                    <img src="img/m7.jpg" alt="" class="bg-img">
                    <div class="deco-content">
                        <div class="deco-title"><?= $t[$lang]['staff_welcome'] ?></div>
                        <div class="deco-desc"><?= $t[$lang]['staff_desc'] ?></div>
                    </div>
                </div>
                <div class="panel-form">
                    <div class="logo-wrap">
                        <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo">
                    </div>
                    <div class="hotel-name"><?= htmlspecialchars($hotel_name) ?></div>
                    <div class="hotel-sub">Resort & Tea House</div>

                    <div class="form-title"><?= $t[$lang]['title_staff'] ?></div>
                    <div class="form-subtitle" style="color:#d97706;"><?= $t[$lang]['subtitle_staff'] ?></div>

                    <form onsubmit="handleLogin(event)">
                        <input type="hidden" name="user_type" value="staff">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="input-group">
                            <label class="input-label"><?= $lang == 'th' ? 'รหัสพนักงาน (Emp Code)' : 'Employee Code' ?></label>
                            <input type="text" name="emp_code" class="input-field" placeholder="e.g. M01, A01" style="text-transform:uppercase;" required>
                        </div>
                        <div class="input-group" style="margin-bottom: 24px;">
                            <label class="input-label"><?= $lang == 'th' ? 'รหัสผ่าน (Password)' : 'Password' ?></label>
                            <input type="password" name="password" class="input-field" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn-gold"><?= $t[$lang]['btn_staff'] ?></button>
                    </form>
                    
                    <div class="staff-trigger" onclick="flipToGuest()">← <?= $t[$lang]['guest_link'] ?></div>
                    <div class="cn-watermark">雲</div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function flipToStaff() { document.getElementById('flipCard').classList.add('flipped'); }
        function flipToGuest() { document.getElementById('flipCard').classList.remove('flipped'); }

        function togglePhone() {
            const p = document.getElementById('phone_input');
            const icon = document.getElementById('eye_icon');
            if (p.type === 'password') {
                p.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            } else {
                p.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const form = e.target, btn = form.querySelector('button[type="submit"]');
            const old = btn.innerText; btn.innerText = '...'; btn.disabled = true;
            try {
                const res = await fetch('login_process.php', { method:'POST', body:new FormData(form) });
                const data = await res.json();
                if (data.success) { window.location.href = data.redirect; }
                else {
                    Swal.fire({ icon:'error', title:'<?= $lang=="th"?"ผิดพลาด":"Error" ?>', text:data.message, background:'#0A0A0A', color:'#FFF', confirmButtonColor:'#d97706' });
                    btn.innerText = old; btn.disabled = false;
                }
            } catch(err) { btn.innerText = old; btn.disabled = false; }
        }

        // Sakura
        (function(){
            const c = document.createElement('div'); c.className='sakuras'; document.body.appendChild(c);
            const n = window.innerWidth<768?20:40;
            for(let i=0;i<n;i++){
                let p=document.createElement('div'); p.className='sakura-petal';
                p.style.width=(Math.random()*6+6)+'px'; p.style.height=(Math.random()*4+8)+'px';
                p.style.left=Math.random()*100+'vw';
                p.style.animationDuration=(Math.random()*8+7)+'s';
                p.style.animationDelay='-'+(Math.random()*10)+'s';
                p.style.opacity=Math.random()*0.5+0.3;
                c.appendChild(p);
            }
        })();
    </script>
</body>
</html>
