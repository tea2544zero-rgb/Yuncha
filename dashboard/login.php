<?php
session_start();
require_once 'config/db.php';

// ถ้าล็อคอินอยู่แล้ว ให้เด้งไปหน้า Dashboard ทันที
if(isset($_SESSION['emp_code'])) {
    header("Location: dashboard.php");
    exit();
}

$error_msg = '';

// Removed legacy SQLite path
$site_settings = [];
if (true) {
    try {
        require_once __DIR__ . '/../dashboard/config/db.php';
        $pdoSettings = $conn;
        $pdoSettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt_settings = $pdoSettings->query("SELECT setting_key, setting_value FROM settings");
        if ($stmt_settings) {
            $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    } catch (Exception $e) {}
}
$hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'Yuncha Valley';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_code = strtoupper(trim($_POST['emp_code']));
    $password = $_POST['password'];

    // ค้นหาพนักงานจากรหัสพนักงาน และต้องมีสถานะ active
    $stmt = $conn->prepare("SELECT * FROM employees WHERE emp_code = :emp_code AND status = 'active'");
    $stmt->execute(['emp_code' => $emp_code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        
        // ตรวจสอบรหัสผ่าน (รองรับทั้งแบบ Hash และแบบข้อความธรรมดาเผื่อรหัสเก่า)
        if (password_verify($password, $user['password_hash']) || $password === $user['password']) {
            
            // สร้าง Session เก็บข้อมูลผู้ใช้
            $_SESSION['emp_id'] = $user['id'];
            $_SESSION['emp_code'] = $user['emp_code'];
            $_SESSION['emp_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_pic'] = $user['profile_pic'];

            // สำเร็จแล้วเด้งไปหน้าหลัก
            header("Location: dashboard.php");
            exit();
        } else {
            $error_msg = 'รหัสผ่านไม่ถูกต้อง!';
        }
    } else {
        $error_msg = 'ไม่พบรหัสพนักงานนี้ หรือบัญชีถูกระงับการใช้งาน!';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ (Login) | <?= htmlspecialchars($hotel_name) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 
                        ycDeep: '#000000', ycSurface: '#0f0f0f', 
                        ycGreen: '#00e676', ycRed: '#ff003c', ycGold: '#ffcc00',
                        ycBlue: '#00d0ff'
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
        body { background-color: #000000; color: #ffffff; font-family: 'Prompt', sans-serif; overflow: hidden; }
        
        .glass-card { background: rgba(10, 10, 10, 0.8); border: 1px solid #222; border-radius: 1.5rem; backdrop-filter: blur(20px); box-shadow: 0 10px 40px rgba(0,0,0,0.8); }
        
        .input-clean { background-color: #050505; border: 1px solid #333; color: white; padding: 0.8rem 1.2rem 0.8rem 2.8rem; border-radius: 0.75rem; outline: none; width: 100%; transition: all 0.3s; }
        .input-clean:focus { border-color: #ffcc00; box-shadow: 0 0 0 2px rgba(255, 204, 0, 0.1); }
        
        .btn-gold { background: linear-gradient(135deg, #ffcc00 0%, #d4af37 100%); color: #000; font-weight: 900; padding: 0.8rem; border-radius: 0.75rem; transition: all 0.3s; width: 100%; box-shadow: 0 4px 15px rgba(255, 204, 0, 0.3); text-transform: uppercase; letter-spacing: 0.05em; }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 204, 0, 0.5); }

        /* Background Effects */
        .bg-glow-1 { position: absolute; top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, rgba(255,204,0,0.05) 0%, transparent 70%); z-index: 0; pointer-events: none; }
        .bg-glow-2 { position: absolute; bottom: -20%; right: -10%; width: 60vw; height: 60vw; background: radial-gradient(circle, rgba(0,230,118,0.03) 0%, transparent 70%); z-index: 0; pointer-events: none; }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important;}</style>
</head>
<body class="h-screen flex items-center justify-center relative">

    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <div class="glass-card w-full max-w-md p-8 sm:p-10 relative z-10 gs-anim mx-4">
        
        <!-- โลโก้โรงแรม -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto bg-[#111] rounded-2xl flex items-center justify-center border border-[#333] mb-4 shadow-lg">
                <i class="ph-fill ph-buildings text-3xl text-ycGold"></i>
            </div>
            <h1 class="text-3xl font-black text-ycGold tracking-widest drop-shadow-[0_0_10px_rgba(255,204,0,0.5)]">YUNCHA</h1>
            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Management System</p>
        </div>

        <!-- แจ้งเตือน Error -->
        <?php if($error_msg): ?>
        <div class="bg-red-900/20 border border-red-500/50 text-ycRed px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2 mb-6">
            <i class="ph-fill ph-warning-circle text-lg"></i> <?= $error_msg ?>
        </div>
        <?php endif; ?>

        <!-- ฟอร์มเข้าสู่ระบบ -->
        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wide">รหัสพนักงาน (Emp Code)</label>
                <div class="relative">
                    <i class="ph-bold ph-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-lg"></i>
                    <input type="text" name="emp_code" required class="input-clean font-bold uppercase" placeholder="เช่น M01, A01" autocomplete="off" value="<?= isset($_POST['emp_code']) ? htmlspecialchars($_POST['emp_code']) : '' ?>">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wide">รหัสผ่าน (Password)</label>
                <div class="relative">
                    <i class="ph-bold ph-lock-key absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-lg"></i>
                    <input type="password" name="password" required class="input-clean font-mono" placeholder="••••••••">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="btn-gold flex items-center justify-center gap-2">
                    เข้าสู่ระบบ <i class="ph-bold ph-sign-in"></i>
                </button>
            </div>
        </form>

        <p class="text-center text-[10px] text-gray-600 mt-8 font-mono">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($hotel_name) ?>. All rights reserved.
        </p>
    </div>

    <script>
        gsap.fromTo(".gs-anim", { y: 30, opacity: 0, scale: 0.95 }, { y: 0, opacity: 1, scale: 1, duration: 0.8, ease: "power3.out" });
    </script>
</body>
</html>
