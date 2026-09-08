<?php
require_once 'config/security.php';
if (!$is_manager) { $has_access = false; }

require_once 'config/db.php';

// =========================================================================
// 🚀 Auto-Seed Employees (4 roles, 1 each)
// =========================================================================
$roles = [
    ['ADMIN ผู้ดูแลระบบ', 'A001', 'Admin', 'User', 'admin'],
    ['MANAGER ผู้จัดการ', 'M001', 'Manager', 'User', 'manager'],
    ['RECEPTIONIST พนักงานต้อนรับ', 'R001', 'Front', 'Desk', 'receptionist'],
    ['ACCOUNT บัญชี', 'AC01', 'Account', 'User', 'account']
];
foreach($roles as $r) {
    $stmt_chk = $conn->prepare("SELECT COUNT(*) FROM employees WHERE role = ?");
    $stmt_chk->execute([$r[0]]);
    if($stmt_chk->fetchColumn() == 0) {
        $hash = password_hash($r[4], PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO employees (emp_code, first_name, last_name, role, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')")
             ->execute([$r[1], $r[2], $r[3], $r[0], $hash]);
    }
}

// =========================================================================
// 🚀 ระบบซ่อมแซมและปรับโครงสร้างฐานข้อมูล (Auto-Fix DB)
// =========================================================================
$check_cols = $conn->query("PRAGMA table_info(employees)");
$cols = [];
while($col = $check_cols->fetch(PDO::FETCH_ASSOC)) { $cols[] = $col['name']; }

if(!in_array('phone', $cols)) $conn->exec("ALTER TABLE employees ADD COLUMN phone TEXT NULL");
if(!in_array('email', $cols)) $conn->exec("ALTER TABLE employees ADD COLUMN email TEXT NULL");
if(!in_array('status', $cols)) $conn->exec("ALTER TABLE employees ADD COLUMN status TEXT DEFAULT 'active'");
if(!in_array('profile_pic', $cols)) $conn->exec("ALTER TABLE employees ADD COLUMN profile_pic TEXT NULL");


// $conn->exec("ALTER TABLE employees MODIFY COLUMN role VARCHAR(100) NOT NULL DEFAULT 'RECEPTIONIST พนักงานต้อนรับ'");

// =========================================================================
// ðŸš€ ระบบจัดการข้อมูล (Add / Edit / Delete / Restore)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $emp_id = (int)(isset($_POST['emp_id']) ? $_POST['emp_id'] :  0);
    
    if ($action === 'add' || $action === 'edit') {
        $emp_code = $_POST['emp_code'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $final_role = $_POST['role'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $status = $_POST['status'];
        $raw_password = isset($_POST['password']) ? $_POST['password'] :  '';

        if ($action === 'add') {
            $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO employees (emp_code, first_name, last_name, role, phone, email, status, password, password_hash) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$emp_code, $first_name, $last_name, $final_role, $phone, $email, $status, $raw_password, $hashed_password]);
            header("Location: employees.php?success=add");
            exit;
        } else {
            if (!empty($raw_password)) {
                $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
                $sql = "UPDATE employees SET emp_code=?, first_name=?, last_name=?, role=?, phone=?, email=?, status=?, password=?, password_hash=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$emp_code, $first_name, $last_name, $final_role, $phone, $email, $status, $raw_password, $hashed_password, $emp_id]);
            } else {
                $sql = "UPDATE employees SET emp_code=?, first_name=?, last_name=?, role=?, phone=?, email=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$emp_code, $first_name, $last_name, $final_role, $phone, $email, $status, $emp_id]);
            }
            header("Location: employees.php?success=edit");
            exit;
        }
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("UPDATE employees SET status='deleted' WHERE id=?");
        $stmt->execute([$emp_id]);
        header("Location: employees.php?success=delete");
        exit;
    } elseif ($action === 'restore') {
        $stmt = $conn->prepare("UPDATE employees SET status='active' WHERE id=?");
        $stmt->execute([$emp_id]);
        header("Location: employees.php?success=restore");
        exit;
    }
}

// =========================================================================
// ตั้งค่าตำแหน่งมาตรฐาน
// =========================================================================
$standard_roles_list = [
    'MANAGER ผู้จัดการ',
    'ADMIN ผู้ดูแลระบบ',
    'ACCOUNT บัญชี',
    'RECEPTIONIST พนักงานต้อนรับ',
    'HOUSEKEEPER แม่บ้าน',
    'MAINTENANCE ซ่อมบำรุง'
];

// ดึงข้อมูลเพื่อแสดงผล
$sql_employees = "SELECT * FROM employees ORDER BY 
                  CASE WHEN role LIKE '%MANAGER%' THEN 1 
                       WHEN role LIKE '%ADMIN%' THEN 2 
                       WHEN role LIKE '%ACCOUNT%' THEN 3 
                       ELSE 4 END ASC, emp_code ASC";
$stmt_employees = $conn->query($sql_employees);

// เก็บ emp_code ทั้งหมดไว้ให้ JS ใช้คำนวณ ID ถัดไป
$existing_codes = [];
$custom_roles = [];
$count_total = 0; $count_manager = 0; $count_admin = 0; $count_counter = 0;
$employees_data = [];

if($stmt_employees) {
    $employees_data = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);
    $count_total = count($employees_data);
    foreach($employees_data as $row) {
        $existing_codes[] = $row['emp_code'];
        
        $current_status = isset($row['status']) ? $row['status'] :  'active';
        if($current_status != 'deleted') {
            if(stripos($row['role'], 'MANAGER') !== false) $count_manager++;
            elseif(stripos($row['role'], 'ADMIN') !== false) $count_admin++;
            else $count_counter++; 
        }
        
        // รวบรวมตำแหน่งที่แต่งเอง
        if(!in_array($row['role'], $standard_roles_list) && !in_array($row['role'], $custom_roles)) {
            $custom_roles[] = $row['role'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการพนักงาน (Employees) | Yuncha Valley</title>
    
    <script src="https://cdn.tailwindcss.com">
</script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { ycDeep: '#000000', ycSurface: '#0f0f0f', ycGreen: '#00ff41', ycGold: '#ffcc00', ycBlue: '#00d0ff', ycPink: '#ff00ff', ycRed: '#ff003c', ycOrange: '#ff5e00', ycMint: '#00e676', ycPurple: '#b537f2' },
                    fontFamily: { sans: ['Prompt', 'sans-serif'] }
                }
            }
        }
</script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web">
</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js">
</script>

    <style>
        body { background-color: #000000; color: #ffffff; font-family: 'Prompt', sans-serif; overflow-x: hidden; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #000000; }
        ::-webkit-scrollbar-thumb { background: #ffcc00; border-radius: 10px; }

        .neon-pro { position: relative; background: #0f0f0f; border-radius: 1rem; z-index: 1; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 0 0 1px rgba(255,255,255,0.1); }
        .neon-pro-glow { position: absolute; inset: -2px; border-radius: 1.1rem; z-index: -2; overflow: hidden; opacity: 0; transition: opacity 0.3s ease; }
        .neon-pro-glow::before { content: ''; position: absolute; top: 50%; left: 50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 70%, var(--neon-color) 100%); transform: translate(-50%, -50%); animation: spin-border 2s linear infinite; }
        .neon-pro::before { content: ''; position: absolute; inset: 0; background: #0f0f0f; border-radius: 1rem; z-index: -1; }
        @keyframes spin-border { 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        .neon-pro:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 20px var(--neon-color); z-index: 10; }
        .neon-pro:hover .neon-pro-glow { opacity: 1; }
        .icon-glow { transition: all 0.3s; color: #a3a3a3; }
        .neon-pro:hover .icon-glow { color: var(--neon-color) !important; filter: drop-shadow(0 0 8px var(--neon-color)); transform: scale(1.15); }

        .input-dark { background-color: #050505; border: 1px solid #333; color: white; padding: 0.75rem 1rem; border-radius: 0.75rem; outline: none; width: 100%; transition: all 0.3s; appearance: textfield; }
        .input-dark:focus { border-color: var(--neon-color); box-shadow: 0 0 10px rgba(255, 204, 0, 0.2); }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { appearance: none; margin: 0; }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important;}</style>
</head>
<body class="h-screen flex selection:bg-ycGold selection:text-black">

    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden w-full relative bg-[#050505]">
        
                <header class="h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 flex justify-between items-center z-30 sticky top-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg text-ycBlue border border-gray-800"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-black text-white tracking-wide">EMPLOYEE <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00d0ff] to-[#ff00ff]">MANAGEMENT</span></h2>
                    <p class="text-sm text-gray-400 font-medium">ระบบจัดการบุคลากร</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-4">เวลาปัจจุบัน: <span id="clockStatus" style="color: #00d0ff;"><?= date('H:i:s') ?></span></p>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
        <?php if ($has_access): ?>

            <div class="max-w-7xl mx-auto space-y-6 pb-12 gs-anim">
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-white transition">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-white"><i class="ph-fill ph-users text-8xl"></i></div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">พนักงานทั้งหมด</p>
                        <h3 class="text-3xl font-black text-white"><?= $count_total ?> <span class="text-sm font-normal text-gray-500">คน</span></h3>
                    </div>
                    <div class="bg-purple-900/10 border border-purple-500/30 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-ycPurple transition">
                        <p class="text-xs font-bold text-ycPurple uppercase tracking-widest mb-1">ผู้จัดการ (Manager)</p>
                        <h3 class="text-3xl font-black text-white"><?= $count_manager ?> <span class="text-sm font-normal text-gray-500">คน</span></h3>
                    </div>
                    <div class="bg-blue-900/10 border border-blue-500/30 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-ycBlue transition">
                        <p class="text-xs font-bold text-ycBlue uppercase tracking-widest mb-1">แอดมิน (Admin)</p>
                        <h3 class="text-3xl font-black text-white"><?= $count_admin ?> <span class="text-sm font-normal text-gray-500">คน</span></h3>
                    </div>
                    <div class="bg-orange-900/10 border border-orange-500/30 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-ycOrange transition">
                        <p class="text-xs font-bold text-ycOrange uppercase tracking-widest mb-1">ฝ่ายปฏิบัติการ / อื่นๆ</p>
                        <h3 class="text-3xl font-black text-white"><?= $count_counter ?> <span class="text-sm font-normal text-gray-500">คน</span></h3>
                    </div>
                </div>

                <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl overflow-hidden shadow-2xl">
                    <!-- ðŸŒŸ เพิ่มปุ่มค้นหา ID และ ชื่อพนักงาน -->
                    <div class="p-5 border-b border-gray-800 flex flex-col md:flex-row justify-between items-center bg-[#050505] gap-4">
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <h3 class="text-lg font-bold text-white"><i class="ph-fill ph-users-three text-gray-400 mr-2"></i> รายชื่อพนักงาน</h3>
                            <?php if($is_manager): ?>
                            <button onclick="openModal('add')" class="neon-pro px-6 py-2 flex items-center gap-2" style="--neon-color: #ffcc00;">
                                <div class="neon-pro-glow animate-pulse opacity-75"></div>
                                <i class="ph-bold ph-user-plus text-lg relative z-10 animate-pulse" style="color: #ffcc00; filter: drop-shadow(0 0 12px #ffcc00) brightness(1.5);"></i>
                                <span class="font-bold text-white relative z-10 whitespace-nowrap">เพิ่มพนักงาน</span>
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center bg-[#151515] border border-gray-700 rounded-lg overflow-hidden focus-within:border-ycGold transition-all w-full md:w-64 shrink-0">
                            <i class="ph-bold ph-magnifying-glass text-gray-500 pl-3"></i>
                            <input type="text" id="searchInput" onkeyup="searchEmployee()" class="bg-transparent border-none text-white text-sm px-3 py-2 outline-none w-full" placeholder="ค้นหา รหัส (ID) หรือ ชื่อ...">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400 min-w-[1000px]">
                            <thead class="text-xs uppercase bg-[#050505] text-gray-500 font-bold border-b border-gray-800">
                                <tr>
                                    <th class="px-6 py-5">รหัส พนง.</th>
                                    <th class="px-6 py-5">ชื่อ - นามสกุล</th>
                                    <th class="px-6 py-5 text-center">ตำแหน่ง (ROLE)</th>
                                    <th class="px-6 py-5">ข้อมูลติดต่อ</th>
                                    <th class="px-6 py-5 text-center">สถานะ</th>
                                    <th class="px-6 py-5 text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800" id="employeeTableBody">
                                <?php if(!empty($employees_data)): ?>
                                    <?php foreach($employees_data as $row): 
                                        $emp_status = isset($row['status']) ? $row['status'] :  'active';
                                        $emp_pic    = isset($row['profile_pic']) ? $row['profile_pic'] :  null;
                                        $emp_phone  = isset($row['phone']) ? $row['phone'] :  '';
                                        $emp_email  = isset($row['email']) ? $row['email'] :  '';
                                        
                                        $row_opacity = ($emp_status == 'deleted' || $emp_status == 'inactive') ? 'opacity-50' : '';
                                        
                                        // ðŸŒŸ กำหนด Badge ประจำตำแหน่งสวยๆ
                                        $role_badge = '';
                                        $r_upper = strtoupper($row['role']);
                                        if(stripos($r_upper, 'MANAGER') !== false) {
                                            $role_badge = '<span class="bg-purple-900/40 border border-purple-500/50 text-ycPurple px-2 py-1 rounded text-xs font-bold uppercase"><i class="ph-fill ph-crown"></i> '.htmlspecialchars($row['role']).'</span>';
                                        } elseif(stripos($r_upper, 'ADMIN') !== false) {
                                            $role_badge = '<span class="bg-blue-900/40 border border-blue-500/50 text-ycBlue px-2 py-1 rounded text-xs font-bold uppercase"><i class="ph-fill ph-shield-star"></i> '.htmlspecialchars($row['role']).'</span>';
                                        } elseif(stripos($r_upper, 'RECEPTIONIST') !== false || stripos($r_upper, 'FRONT') !== false || stripos($r_upper, 'COUNTER') !== false) {
                                            $role_badge = '<span class="bg-orange-900/40 border border-orange-500/50 text-ycOrange px-2 py-1 rounded text-xs font-bold uppercase"><i class="ph-fill ph-desktop"></i> '.htmlspecialchars($row['role']).'</span>';
                                        } elseif(stripos($r_upper, 'ACCOUNT') !== false || stripos($r_upper, 'บัญชี') !== false) {
                                            $role_badge = '<span class="bg-yellow-900/40 border border-yellow-500/50 text-ycGold px-2 py-1 rounded text-xs font-bold uppercase"><i class="ph-fill ph-calculator"></i> '.htmlspecialchars($row['role']).'</span>';
                                        } elseif(stripos($r_upper, 'HOUSEKEEPER') !== false || stripos($r_upper, 'แม่บ้าน') !== false) {
                                            $role_badge = '<span class="bg-pink-900/40 border border-pink-500/50 text-ycPink px-2 py-1 rounded text-xs font-bold uppercase"><i class="ph-fill ph-broom"></i> '.htmlspecialchars($row['role']).'</span>';
                                        } elseif(stripos($r_upper, 'MAINTENANCE') !== false || stripos($r_upper, 'ซ่อมบำรุง') !== false) {
                                            $role_badge = '<span class="bg-green-900/40 border border-green-500/50 text-ycGreen px-2 py-1 rounded text-xs font-bold uppercase"><i class="ph-fill ph-wrench"></i> '.htmlspecialchars($row['role']).'</span>';
                                        } else {
                                            // ðŸŒŸ Dynamic Color สำหรับตำแหน่งแต่งเอง
                                            $hash = md5($r_upper);
                                            $r = max(hexdec(substr($hash, 0, 2)), 100); 
                                            $g = max(hexdec(substr($hash, 2, 2)), 100); 
                                            $b = max(hexdec(substr($hash, 4, 2)), 100);
                                            $color = "rgb($r, $g, $b)";
                                            $role_badge = "<span style=\"color: $color; border-color: rgba($r,$g,$b,0.5); background-color: rgba($r,$g,$b,0.1);\" class=\"border px-2 py-1 rounded text-xs font-bold uppercase\"><i class=\"ph-fill ph-briefcase\"></i> ".htmlspecialchars($row['role'])."</span>";
                                        }
                                    ?>
                                    <tr class="emp-row hover:bg-[#151515] transition-colors <?= $row_opacity ?>">
                                        <td class="px-6 py-4 font-black text-lg text-ycGold tracking-wider emp-id"><?= htmlspecialchars($row['emp_code']) ?></td>
                                        <td class="px-6 py-4 font-bold text-lg text-white">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-[#111] flex items-center justify-center text-gray-400 border border-gray-700">
                                                    <?= $emp_pic ? "<img src='{$emp_pic}' class='w-full h-full rounded-full object-cover'>" : "<i class='ph-fill ph-user'></i>" ?>
                                                </div>
                                                <div>
                                                    <p class="emp-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center"><?= $role_badge ?></td>
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-gray-300"><i class="ph-fill ph-phone text-gray-500"></i> <?= htmlspecialchars($emp_phone ?: '-') ?></p>
                                            <p class="text-xs text-gray-500"><i class="ph-fill ph-envelope text-gray-600"></i> <?= htmlspecialchars($emp_email ?: '-') ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php 
                                                // ðŸŒŸ เปลี่ยนสีสถานะพักงานเป็นสีเหลืองทอง
                                                if($emp_status == 'active') echo '<span class="px-3 py-1 rounded-full text-xs font-bold bg-green-900/40 text-ycGreen border border-ycGreen">ปกติ (Active)</span>';
                                                elseif($emp_status == 'inactive') echo '<span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-900/30 text-ycGold border border-ycGold">พักงาน (Inactive)</span>';
                                                else echo '<span class="px-3 py-1 rounded-full text-xs font-bold bg-red-900/20 text-red-500 border border-red-500">พ้นสภาพ (Deleted)</span>';
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-center flex justify-center gap-4 mt-2">
                                            <?php if($is_manager || ($is_admin && stripos($row['role'], 'MANAGER') === false)): ?>
                                            <button onclick="openModal('edit', '<?= $row['id'] ?>', '<?= htmlspecialchars($row['emp_code']) ?>', '<?= htmlspecialchars($row['first_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['last_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['role'], ENT_QUOTES) ?>', '<?= htmlspecialchars($emp_phone, ENT_QUOTES) ?>', '<?= htmlspecialchars($emp_email, ENT_QUOTES) ?>', '<?= $emp_status ?>')" class="text-gray-400 hover:text-[#00d0ff] transition transform hover:scale-110" title="แก้ไขข้อมูล">
                                                <i class="ph-bold ph-pencil-simple text-xl"></i>
                                            </button>
                                            
                                            <?php if($emp_status == 'deleted'): ?>
                                                <form action="employees.php" method="POST" onsubmit="event.preventDefault(); Swal.fire({title:'ยืนยัน?', text:'กู้คืนสถานะพนักงานนี้กลับมาใช้งาน?', icon:'warning', showCancelButton:true, confirmButtonColor:'#ff003c', cancelButtonColor:'#333', confirmButtonText:'ยืนยัน', cancelButtonText:'ยกเลิก', background:'#1a1a1a', color:'#fff'}).then((r)=>{if(r.isConfirmed){this.removeAttribute('onsubmit'); this.submit();}})" class="inline">
                                                    <input type="hidden" name="action" value="restore">
                                                    <input type="hidden" name="emp_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="text-gray-400 hover:text-ycGreen transition transform hover:scale-110" title="กู้คืน">
                                                        <i class="ph-bold ph-arrow-counter-clockwise text-xl"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="employees.php" method="POST" onsubmit="event.preventDefault(); Swal.fire({title:'ยืนยัน?', text:'คุณต้องการระงับบัญชี (พ้นสภาพ) พนักงานท่านนี้ใช่หรือไม่? (ผู้ใช้จะไม่สามารถล็อคอินได้)', icon:'warning', showCancelButton:true, confirmButtonColor:'#ff003c', cancelButtonColor:'#333', confirmButtonText:'ยืนยัน', cancelButtonText:'ยกเลิก', background:'#1a1a1a', color:'#fff'}).then((r)=>{if(r.isConfirmed){this.removeAttribute('onsubmit'); this.submit();}})" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="emp_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="text-gray-400 hover:text-ycRed transition transform hover:scale-110" title="ระงับการใช้งาน">
                                                        <i class="ph-bold ph-trash text-xl"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-xs font-bold text-gray-600"><i class="ph-fill ph-lock-key"></i> ไม่มีสิทธิ์</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">ยังไม่มีข้อมูลพนักงานในระบบ</td></tr>
                                <?php endif; ?>
                                <!-- แถวสำหรับเวลาค้นหาไม่เจอ -->
                                <tr id="noSearchResult" class="hidden"><td colspan="6" class="text-center py-8 text-gray-500 font-bold">ไม่พบข้อมูลพนักงานที่ค้นหา</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        
        <?php else: ?>
        <!-- Premium Access Denied Screen (Glassmorphism & Glowing Neon Overlay) -->
        <div class="max-w-md mx-auto my-12 neon-pro p-8 text-center" style="--neon-color: #ff003c;">
            <div class="w-20 h-20 mx-auto bg-red-950/30 border border-red-500/50 rounded-full flex items-center justify-center mb-6 shadow-[0_0_20px_rgba(255,0,60,0.3)]">
                <i class="ph-fill ph-shield-warning text-4xl text-ycRed drop-shadow-[0_0_8px_#ff003c]"></i>
            </div>
            <h3 class="text-2xl font-black text-white mb-2">ปฏิเสธการเข้าถึง</h3>
            <p class="text-sm text-gray-400 mb-6">ขออภัย บัญชีของคุณไม่มีสิทธิ์เข้าใช้งานหน้านี้ เฉพาะเจ้าหน้าที่ระดับบริหารที่มีสิทธิ์เข้าถึงเท่านั้น</p>
            <a href="dashboard.php" class="inline-flex items-center gap-2 bg-[#111] border border-gray-800 hover:border-ycGold hover:text-ycGold text-white px-6 py-3 rounded-xl font-bold transition">
                <i class="ph-bold ph-arrow-left"></i> กลับไปแผงควบคุมหลัก
            </a>
        </div>
        <?php endif; ?>
</main>
    </div>

    <!-- ðŸŒŸ Modal เพิ่ม/แก้ไข: โครงสร้าง Flexbox แก้ปัญหาหัวบัง Scroll -->
    <div id="empModal" class="fixed inset-0 bg-black/90 z-[100] hidden flex items-center justify-center backdrop-blur-sm pt-10 pb-10">
        <div class="bg-[#0f0f0f] w-full max-w-2xl rounded-2xl border border-gray-800 shadow-[0_0_30px_rgba(255,204,0,0.15)] flex flex-col max-h-[90vh]">
            
            <div class="flex justify-between items-center p-6 border-b border-gray-800 bg-[#0a0a0a] rounded-t-2xl shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#ffcc00]/20 rounded-xl flex items-center justify-center text-ycGold shadow-[0_0_10px_rgba(255,204,0,0.3)]">
                        <i class="ph-bold ph-identification-card text-xl" id="modalIcon"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white drop-shadow-md" id="modalTitle">เพิ่มพนักงานใหม่</h3>
                </div>
                <button type="button" onclick="closeModal()" class="w-8 h-8 bg-[#1a1a1a] hover:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 transition">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <!-- ส่วนฟอร์ม เลื่อน Scroll ได้อิสระ ไม่โดนบัง -->
            <form action="employees.php" method="POST" class="flex-1 overflow-y-auto flex flex-col">
                <div class="p-6 space-y-6 flex-1">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="emp_id" id="empId">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 border-b border-gray-800 pb-6">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-gray-400 mb-2">รหัสพนักงาน (ID) <span class="text-ycRed">*</span></label>
                            <input type="text" name="emp_code" id="empCode" required class="input-dark font-black tracking-widest text-ycGold" style="--neon-color: #ffcc00;" placeholder="เช่น M01, A02">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-400 mb-2">ตำแหน่ง (Role) <span class="text-ycRed">*</span></label>
                            <div class="flex gap-2">
                                <div class="relative w-full" id="selectRoleContainer">
                                    <select name="role" id="empRole" class="input-dark appearance-none pr-10" style="--neon-color: #ffcc00;">
                                        <?php foreach($standard_roles_list as $sr): ?>
                                            <option value="<?= htmlspecialchars($sr) ?>"><?= htmlspecialchars($sr) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="ph-bold ph-caret-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                </div>
                                <button type="button" onclick="toggleNewRole()" id="addRoleBtn" class="px-4 bg-[#111] border border-gray-800 hover:border-ycGold hover:text-ycGold text-white rounded-xl transition flex items-center justify-center" title="เพิ่มตำแหน่งใหม่"><i class="ph-bold ph-plus" id="addRoleIcon"></i></button>
                            </div>
                            <input type="text" id="newRoleInput" class="input-dark hidden" style="--neon-color: #ffcc00;" placeholder="พิมพ์ชื่อตำแหน่งใหม่ เช่น HOUSEKEEPER แม่บ้าน" onkeyup="updateIdFromNewRole()">
                            <p class="text-[10px] text-gray-500 mt-2 font-medium" id="roleHint"><i class="ph-fill ph-info"></i> กดปุ่ม ➕ หากต้องการพิมพ์ชื่อตำแหน่งใหม่</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">ชื่อจริง (First Name) <span class="text-ycRed">*</span></label>
                            <input type="text" name="first_name" id="firstName" required class="input-dark" style="--neon-color: #ffcc00;">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">นามสกุล (Last Name) <span class="text-ycRed">*</span></label>
                            <input type="text" name="last_name" id="lastName" required class="input-dark" style="--neon-color: #ffcc00;">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">เบอร์โทรศัพท์ (Phone)</label>
                            <input type="text" name="phone" id="phone" class="input-dark" style="--neon-color: #ffcc00;">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">อีเมล (Email)</label>
                            <input type="email" name="email" id="email" class="input-dark" style="--neon-color: #ffcc00;">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">รหัสผ่าน (Password) <span class="text-ycRed" id="pwdReq">*</span></label>
                            <input type="password" name="password" id="password" class="input-dark font-mono text-xs" style="--neon-color: #ffcc00;" placeholder="ปล่อยว่างหากไม่ต้องการเปลี่ยน">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">สถานะพนักงาน</label>
                            <div class="relative">
                                <select name="status" id="empStatus" class="input-dark appearance-none pr-10" style="--neon-color: #ffcc00;">
                                    <option value="active">ปกติ (Active)</option>
                                    <option value="inactive">พักงานชั่วคราว (Inactive)</option>
                                    <option value="deleted">พ้นสภาพ (Deleted)</option>
                                </select>
                                <i class="ph-bold ph-caret-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-800 shrink-0 flex justify-end bg-[#0a0a0a] rounded-b-2xl">
                    <button type="button" onclick="closeModal()" class="neon-pro px-8 py-3 flex items-center gap-2 mr-4" style="--neon-color: #a3a3a3;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-x text-xl text-gray-300 relative z-10 icon-glow"></i>
                        <span class="font-bold text-gray-300 relative z-10">ยกเลิก</span>
                    </button>
                    <button type="submit" class="neon-pro px-10 py-3 flex items-center gap-2" style="--neon-color: #ffcc00;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-floppy-disk text-xl text-black relative z-10 icon-glow"></i>
                        <span class="font-bold text-white relative z-10">บันทึกข้อมูล</span>
                    </button>
                </div>
            </form>
        </div>
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
        // 🌟 จำตำแหน่ง Scroll ของเมนูและตาราง
        document.addEventListener("DOMContentLoaded", function() { 
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if (sessionStorage.getItem('sidebarScrollPos') && sidebarEl) sidebarEl.scrollTop = sessionStorage.getItem('sidebarScrollPos');
        });
        window.onbeforeunload = function() {
            var sidebarEl = document.getElementById('sidebarScrollBox');
            if(sidebarEl) sessionStorage.setItem('sidebarScrollPos', sidebarEl.scrollTop);
        };

        // 🌟 ฟังก์ชันค้นหาพนักงาน (Search)
        function searchEmployee() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.emp-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const empId = row.querySelector('.emp-id').innerText.toLowerCase();
                const empName = row.querySelector('.emp-name').innerText.toLowerCase();
                
                if(empId.includes(input) || empName.includes(input)) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                }
            });

            const noData = document.getElementById('noSearchResult');
            if(visibleCount === 0) noData.classList.remove('hidden');
            else noData.classList.add('hidden');
        }

        const existingCodes = <?= json_encode($existing_codes) ?>;
        let isEditMode = false;

        function generateNextId(roleName) {
            if(!roleName) return '';
            let firstWord = roleName.trim().split(' ')[0].toUpperCase();
            let prefix = firstWord.charAt(0);
            
            if(firstWord === 'RECEPTIONIST' || firstWord === 'FRONT' || firstWord === 'COUNTER') prefix = 'R';
            if(roleName.toUpperCase().includes('ACCOUNT') || roleName.includes('บัญชี')) prefix = 'B';

            let maxNum = 0;
            existingCodes.forEach(code => {
                if(code.startsWith(prefix)) {
                    let numStr = code.substring(prefix.length);
                    let num = parseInt(numStr);
                    if(!isNaN(num) && num > maxNum) maxNum = num;
                }
            });
            maxNum++;
            return prefix + maxNum.toString().padStart(2, '0');
        }

        function updateIdFromNewRole() {
            if(!isEditMode) {
                const newVal = document.getElementById('newRoleInput').value;
                document.getElementById('empCode').value = generateNextId(newVal);
            }
        }

        document.getElementById('empRole').addEventListener('change', function() {
            document.getElementById('empCode').value = generateNextId(this.value);
        });

        let isAddingNewRole = false;
        function toggleNewRole() {
            isAddingNewRole = !isAddingNewRole;
            const container = document.getElementById('selectRoleContainer');
            const select = document.getElementById('empRole');
            const input = document.getElementById('newRoleInput');
            const icon = document.getElementById('addRoleIcon');
            const hint = document.getElementById('roleHint');

            if(isAddingNewRole) {
                container.classList.add('hidden');
                input.classList.remove('hidden');
                input.setAttribute('required', 'true');
                input.name = 'role'; 
                select.name = '';    
                input.focus();
                icon.classList.remove('ph-plus');
                icon.classList.add('ph-list-dashes');
                hint.innerHTML = '<i class="ph-fill ph-info"></i> พิมพ์ชื่อตำแหน่งใหม่ ระบบจะสุ่ม Prefix ID ให้จากตัวอักษรแรก (พิมพ์ "บัญชี" จะได้รหัส B)';
                hint.classList.replace('text-gray-500', 'text-ycGold');
                if(!isEditMode) document.getElementById('empCode').value = generateNextId(input.value);
            } else {
                container.classList.remove('hidden');
                input.classList.add('hidden');
                input.removeAttribute('required');
                input.value = '';
                input.name = '';      
                select.name = 'role'; 
                icon.classList.remove('ph-list-dashes');
                icon.classList.add('ph-plus');
                hint.innerHTML = '<i class="ph-fill ph-info"></i> กดปุ่ม ➕ หากต้องการพิมพ์ชื่อตำแหน่งใหม่';
                hint.classList.replace('text-ycGold', 'text-gray-500');
                if(!isEditMode) document.getElementById('empCode').value = generateNextId(select.value);
            }
        }

        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('success')) {
            const action = urlParams.get('success');
            let msg = "ดำเนินการเสร็จสิ้นเรียบร้อย";
            let color = "text-ycGreen";
            let bgClass = "bg-ycGreen hover:bg-green-500";
            let shadowClass = "shadow-[0_0_15px_rgba(0,255,65,0.4)]";
            let iconBox = "bg-ycGreen/20 shadow-[0_0_20px_rgba(0,255,65,0.4)]";
            let boxBorder = "border-ycGreen shadow-[0_0_40px_rgba(0,255,65,0.2)]";
            let iconCls = "ph-bold ph-check text-6xl text-ycGreen";
            
            if(action === 'add') msg = "เพิ่มพนักงานใหม่เรียบร้อยแล้ว";
            if(action === 'edit') msg = "อัปเดตข้อมูลพนักงานเรียบร้อย";
            if(action === 'delete') { 
                msg = "ระงับบัญชีพนักงานเรียบร้อย (พนักงานจะไม่สามารถล็อคอินได้)"; 
                color = "text-ycRed"; bgClass = "bg-ycRed hover:bg-red-600";
                shadowClass = "shadow-[0_0_15px_rgba(255,0,60,0.4)]";
                iconBox = "bg-ycRed/20 shadow-[0_0_20px_rgba(255,0,60,0.4)]";
                boxBorder = "border-ycRed shadow-[0_0_40px_rgba(255,0,60,0.2)]";
                iconCls = "ph-bold ph-trash text-6xl text-ycRed";
            }
            if(action === 'restore') msg = "กู้คืนบัญชีพนักงานกลับมาใช้งานเรียบร้อย";
            
            showSuccessAlert(msg, color, bgClass, shadowClass, iconBox, boxBorder, iconCls);
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        function showSuccessAlert(message, colorClass, bgClass, shadowClass, iconBox, boxBorder, iconCls) {
            const alertBox = document.getElementById('successAlert');
            const content = document.getElementById('successAlertContent');
            const btn = document.getElementById('successBtn');
            
            document.getElementById('successMessage').innerText = message;
            document.getElementById('successIcon').className = iconCls;
            document.getElementById('successIcon').parentElement.className = `w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 ${iconBox}`;
            content.className = `bg-[#0f0f0f] p-8 rounded-3xl border text-center transform scale-90 transition-transform duration-300 w-full max-w-sm ${boxBorder}`;
            btn.className = `w-full text-white font-black py-3 rounded-xl transition ${bgClass} ${shadowClass}`;
            
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
        document.getElementById('open-sidebar').addEventListener('click', toggleSidebar);
        document.getElementById('close-sidebar').addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        let idleTime = 0;
        window.onload = resetIdle;
        window.onmousemove = resetIdle;
        window.onkeypress = resetIdle;
        function resetIdle() { idleTime = 0; }
        
        setInterval(function() {
            idleTime += 1;
            const clockEl = document.getElementById('clockStatus');
            if (clockEl) {
                const now = new Date();
                clockEl.innerText = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            if (idleTime >= 60) {
                let isModalOpen = !document.getElementById('empModal').classList.contains('hidden');
                let isAlertOpen = !document.getElementById('successAlert').classList.contains('hidden');
                if(!isModalOpen && !isAlertOpen) location.reload();
            }
        }, 1000);

        const modal = document.getElementById('empModal');
        
        function openModal(mode, id = '', code = '', fname = '', lname = '', role = '', phone = '', email = '', status = 'active') {
            modal.classList.remove('hidden');
            if(isAddingNewRole) toggleNewRole();
            
            isEditMode = (mode === 'edit');
            
            if(isEditMode) {
                document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลพนักงาน';
                document.getElementById('modalIcon').className = 'ph-bold ph-pencil-simple text-xl';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('empId').value = id;
                document.getElementById('empCode').value = code;
                document.getElementById('firstName').value = fname;
                document.getElementById('lastName').value = lname;
                
                let roleSelect = document.getElementById('empRole');
                let optionExists = Array.from(roleSelect.options).some(opt => opt.value === role);
                if (!optionExists) {
                    let newOption = new Option(role, role);
                    roleSelect.add(newOption, undefined);
                }
                roleSelect.value = role; roleSelect.dispatchEvent(new Event('change'));

                document.getElementById('phone').value = phone;
                document.getElementById('email').value = email;
                document.getElementById('empStatus').value = status; document.getElementById('empStatus').dispatchEvent(new Event('change'));
                
                document.getElementById('password').removeAttribute('required');
                document.getElementById('pwdReq').classList.add('hidden');
                document.getElementById('addRoleBtn').classList.add('hidden');
            } else {
                document.getElementById('modalTitle').innerText = 'เพิ่มพนักงานใหม่';
                document.getElementById('modalIcon').className = 'ph-bold ph-user-plus text-xl';
                document.getElementById('formAction').value = 'add';
                document.getElementById('empId').value = '';
                document.getElementById('firstName').value = '';
                document.getElementById('lastName').value = '';
                
                let roleSelect = document.getElementById('empRole');
                roleSelect.selectedIndex = 0; roleSelect.dispatchEvent(new Event('change')); 
                document.getElementById('empCode').value = generateNextId(roleSelect.value);

                document.getElementById('phone').value = '';
                document.getElementById('email').value = '';
                document.getElementById('empStatus').value = 'active'; document.getElementById('empStatus').dispatchEvent(new Event('change'));
                
                document.getElementById('password').setAttribute('required', 'true');
                document.getElementById('pwdReq').classList.remove('hidden');
                document.getElementById('addRoleBtn').classList.remove('hidden');
            }
        }
        
        function closeModal() {
            modal.classList.add('hidden');
        }

        gsap.fromTo(".gs-anim", { y: 30, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, ease: "power3.out" });
</script>

<script>
// [CUSTOM SELECT INJECTION]
    // 🌟 Polyfill for select.value to auto-trigger change event
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
</script>
</body>
</html>
