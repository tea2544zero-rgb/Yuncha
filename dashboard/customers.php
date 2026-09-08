<?php
session_start();
require_once 'config/security.php';
require_once 'config/db.php';

// ดึงข้อมูลลูกค้าทั้งหมด
$sql_customers = "SELECT c.*, (SELECT status FROM bookings b WHERE b.customer_id = c.id ORDER BY created_at DESC LIMIT 1) as latest_booking_status FROM customers c ORDER BY c.created_at DESC";
$stmt_customers = $conn->query($sql_customers);
$customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการลูกค้า (Customers) | Yuncha Valley</title>
    
    <script src="https://cdn.tailwindcss.com">
</script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 
                        ycDeep: '#000000', ycSurface: '#0f0f0f', 
                        ycGreen: '#00ff41', ycGold: '#ffcc00', 
                        ycBlue: '#00d0ff', ycPink: '#ff00ff', ycRed: '#ff003c', ycOrange: '#ff5e00', ycMint: '#00e676', ycPurple: '#b537f2'
                    },
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
        body { background-color: #000000; color: #ffffff; font-family: 'Prompt', sans-serif; overflow-x: hidden; position: relative;}
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #000000; }
        ::-webkit-scrollbar-thumb { background: #ff3c00; border-radius: 10px; }

        .stars-container { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .star { position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0; animation: fall linear infinite; box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.8); }
        @keyframes fall {
            0% { transform: translateY(-10vh) translateX(0) scale(1); opacity: 1; }
            100% { transform: translateY(110vh) translateX(-20vw) scale(0); opacity: 0; }
        }

        .neon-pro { position: relative; background: rgba(15,15,15,0.8); backdrop-filter: blur(10px); border-radius: 1rem; z-index: 1; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 0 0 1px rgba(255,255,255,0.05); }
        .neon-pro-glow { position: absolute; inset: -1px; border-radius: 1.1rem; z-index: -2; overflow: hidden; opacity: 0; transition: opacity 0.3s ease; }
        .neon-pro-glow::before { content: ''; position: absolute; top: 50%; left: 50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 70%, var(--neon-color) 100%); transform: translate(-50%, -50%); animation: spin-border 2s linear infinite; }
        .neon-pro::before { content: ''; position: absolute; inset: 0; background: #0f0f0f; border-radius: 1rem; z-index: -1; }
        @keyframes spin-border { 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        .neon-pro:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 15px var(--neon-color); z-index: 10; }
        .neon-pro:hover .neon-pro-glow { opacity: 1; }
        .icon-glow { transition: all 0.3s; color: #a3a3a3; }
        .neon-pro:hover .icon-glow { color: var(--neon-color) !important; filter: drop-shadow(0 0 8px var(--neon-color)); transform: scale(1.15); }
        
        .input-dark { background-color: #050505; border: 1px solid #333; color: white; padding: 0.75rem 1rem; border-radius: 0.75rem; outline: none; width: 100%; transition: all 0.3s; }
        .input-dark:focus { border-color: var(--neon-color); box-shadow: 0 0 10px var(--neon-color); }
        select.input-dark { appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; }
        
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { appearance: none; margin: 0; }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
</script>
</head>
<body class="h-screen flex selection:bg-ycRed selection:text-black">

    <div class="stars-container" id="starsBox"></div>
    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <!-- Sidebar อัจฉริยะ ซิงค์สี 100% -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative z-10">
        
        <header class="h-24 bg-[#050505]/80 backdrop-blur-md border-b border-gray-800 flex items-center justify-between px-8 shrink-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden text-white hover:text-ycRed transition"><i class="ph-bold ph-list text-3xl"></i></button>
                <div class="gs-anim">
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight flex items-center gap-3">
                        CUSTOMER <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00d0ff] to-[#b537f2]">MANAGEMENT</span>
                    </h2>
                    <p class="text-gray-400 text-sm mt-1">ระบบจัดการฐานข้อมูลลูกค้า</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right hidden md:block">
                    <p class="text-gray-400 text-sm font-bold">เวลาปัจจุบัน</p>
                    <p class="text-xl font-bold text-[#00d0ff]" id="currentTime">00:00:00</p>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8 relative">
            <div class="max-w-7xl mx-auto space-y-8">

                <!-- Alert Messages -->
                <?php if(isset($_SESSION['success'])): ?>
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: '<?= $_SESSION['success'] ?>',
                            background: '#0f0f0f',
                            color: '#fff',
                            confirmButtonColor: '#ff3c00'
                        });
</script>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด!',
                            text: '<?= $_SESSION['error'] ?>',
                            background: '#0f0f0f',
                            color: '#fff',
                            confirmButtonColor: '#ff3c00'
                        });
</script>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="bg-[#0f0f0f] border border-[#ff3c00]/30 rounded-2xl shadow-[0_0_30px_rgba(255,60,0,0.1)] relative overflow-hidden flex flex-col">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#ff3c00] to-transparent opacity-50"></div>
                    
                    <div class="p-6 border-b border-gray-800 bg-[#0a0a0a] flex flex-col md:flex-row md:items-center justify-between gap-4 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#ff3c00]/20 flex items-center justify-center text-[#ff3c00]">
                                <i class="ph-fill ph-users-three text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white">รายชื่อลูกค้าในระบบ</h3>
                        </div>
                        
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <!-- ช่องค้นหาลูกค้าอัจฉริยะ -->
                            <div class="relative w-full md:w-80">
                                <input type="text" id="searchCustomer" placeholder="ค้นหา ชื่อ, เบอร์โทร, สถานะ หรือช่องทาง" class="input-dark pl-10 text-sm" style="--neon-color: #ff3c00;">
                                <i class="ph-bold ph-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left text-sm text-gray-300 min-w-[1000px]">
                            <thead class="bg-[#050505] text-gray-500 font-bold uppercase text-xs tracking-wider border-b border-gray-800">
                                <tr>
                                    <th class="px-6 py-4">ชื่อ - นามสกุล</th>
                                    <th class="px-6 py-4">เบอร์โทรศัพท์</th>
                                    <th class="px-6 py-4">ช่องทางการติดต่อ/จอง</th>
                                    <th class="px-6 py-4 text-center">สถานะปัจจุบัน</th>
                                    <th class="px-6 py-4 text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800" id="customerTableBody">
                                <?php if(!empty($customers)): ?>
                                    <?php foreach($customers as $row): 
                                        $row_opacity = ($row['status'] == 'deleted') ? 'opacity-50' : '';
                                    ?>
                                    <tr class="cus-row hover:bg-[#151515] transition-colors <?= $row_opacity ?>">
                                        <td class="px-6 py-4 font-bold text-lg text-white">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 border border-gray-700">
                                                    <?= (!empty($row['profile_pic'])) ? "<img src='{$row['profile_pic']}' class='w-full h-full rounded-full object-cover'>" : "<i class='ph-fill ph-user'></i>" ?>
                                                </div>
                                                <div>
                                                    <p class="cus-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></p>
                                                    <p class="text-xs font-normal text-gray-500"><?= htmlspecialchars($row['email'] ?: 'ไม่มีอีเมล') ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-medium cus-phone"><?= htmlspecialchars($row['phone']) ?></td>
                                        <td class="px-6 py-4 cus-channel">
                                            <?php 
                                                $auth = strtolower(isset($row['auth_provider']) ? $row['auth_provider'] :  '');
                                                if($auth == 'website' || $auth == 'online') echo '<span class="flex items-center gap-2 text-[#00d0ff]"><i class="ph-bold ph-globe text-lg"></i> เว็บไซต์</span>';
                                                elseif($auth == 'walk-in' || $auth == 'local') echo '<span class="flex items-center gap-2 text-ycGold"><i class="ph-bold ph-storefront text-lg"></i> หน้าเคาน์เตอร์</span>';
                                                elseif($auth == 'line') echo '<span class="flex items-center gap-2 text-[#00B900]"><i class="ph-bold ph-chat-circle-dots text-lg"></i> LINE</span>';
                                                elseif($auth == 'facebook') echo '<span class="flex items-center gap-2 text-[#1877F2]"><i class="ph-bold ph-facebook-logo text-lg"></i> Facebook</span>';
                                                elseif($auth == 'agoda' || $auth == 'booking.com') echo '<span class="flex items-center gap-2 text-ycRed"><i class="ph-bold ph-buildings text-lg"></i> ' . htmlspecialchars($row['auth_provider']) . '</span>';
                                                else echo '<span class="flex items-center gap-2 text-gray-400"><i class="ph-bold ph-question text-lg"></i> ' . htmlspecialchars($row['auth_provider'] ?: 'ไม่ระบุ') . '</span>';
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php 
                                                $lb = isset($row['latest_booking_status']) ? $row['latest_booking_status'] :  '';
                                                if($row['status'] == 'deleted') echo '<span class="cus-status px-3 py-1 rounded-full text-xs font-bold bg-red-900/20 text-red-500 border border-red-500">บัญชีถูกระงับ</span>';
                                                elseif($lb == 'checked_in') echo '<span class="cus-status px-3 py-1 rounded-full text-xs font-bold bg-blue-900/40 text-[#00d0ff] border border-[#00d0ff]">กำลังพัก</span>';
                                                elseif(in_array($lb, ['confirmed', 'pending'])) echo '<span class="cus-status px-3 py-1 rounded-full text-xs font-bold bg-yellow-900/40 text-ycGold border border-ycGold">รอเช็คอิน</span>';
                                                elseif($lb == 'checked_out') echo '<span class="cus-status px-3 py-1 rounded-full text-xs font-bold bg-red-900/20 text-[#ff3c00] border border-[#ff3c00]">เช็คเอาท์</span>';
                                                else echo '<span class="cus-status px-3 py-1 rounded-full text-xs font-bold bg-[#111] text-gray-500 border border-gray-800">ไม่มีการจอง</span>';
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-center flex justify-center gap-4 mt-2">
                                            <button onclick="viewHistory(<?= $row['id'] ?>)" class="text-gray-400 hover:text-ycBlue transition transform hover:scale-110" title="ดูประวัติการเข้าพัก">
                                                <i class="ph-bold ph-clock-counter-clockwise text-xl"></i>
                                            </button>

                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">ยังไม่มีข้อมูลลูกค้าในระบบ</td></tr>
                                <?php endif; ?>
                                <!-- แถวแจ้งเตือนเวลาค้นหาไม่เจอ -->
                                <tr id="noSearchResult" class="hidden"><td colspan="5" class="text-center py-12 text-gray-500 font-bold">ไม่พบข้อมูลลูกค้าที่ค้นหา</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Modal: เพิ่ม/แก้ไข ข้อมูลลูกค้า -->
    <div id="customerModal" class="fixed inset-0 bg-black/90 z-[100] hidden flex items-center justify-center backdrop-blur-sm overflow-y-auto pt-10 pb-10">
        <div class="bg-[#0f0f0f] w-full max-w-2xl rounded-2xl border border-gray-800 shadow-[0_0_30px_rgba(255,60,0,0.15)] relative my-auto flex flex-col max-h-[90vh]">
            
            <div class="flex justify-between items-center p-6 border-b border-gray-800 bg-[#0a0a0a] rounded-t-2xl shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#ff3c00]/20 rounded-xl flex items-center justify-center text-[#ff3c00] shadow-[0_0_10px_rgba(255,60,0,0.3)]">
                        <i class="ph-bold ph-user text-xl" id="modalIcon"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white drop-shadow-md" id="modalTitle">เพิ่มลูกค้าใหม่</h3>
                </div>
                <button type="button" onclick="closeModal()" class="w-8 h-8 bg-[#1a1a1a] hover:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 transition">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <form action="actions/customer_process.php" method="POST" class="flex-1 overflow-y-auto flex flex-col">
                <div class="p-6 space-y-6 flex-1">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="customer_id" id="customerId">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">ชื่อ <span class="text-ycRed">*</span></label>
                            <input type="text" name="first_name" id="firstName" required class="input-dark" style="--neon-color: #ff3c00;" placeholder="ชื่อจริง">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">นามสกุล <span class="text-ycRed">*</span></label>
                            <input type="text" name="last_name" id="lastName" required class="input-dark" style="--neon-color: #ff3c00;" placeholder="นามสกุล">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">เบอร์โทรศัพท์ <span class="text-ycRed">*</span></label>
                            <input type="tel" name="phone" id="phone" required class="input-dark" style="--neon-color: #ff3c00;" placeholder="08X-XXX-XXXX">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">อีเมล</label>
                            <input type="email" name="email" id="email" class="input-dark" style="--neon-color: #ff3c00;" placeholder="example@email.com">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 pt-4 border-t border-gray-800">
                        <div>
                            <label class="block text-sm font-bold text-gray-400 mb-2">ช่องทางการจอง / การติดต่อ</label>
                            <div class="relative">
                                <select name="auth_provider" id="authProvider" class="input-dark appearance-none pr-10" style="--neon-color: #ff3c00;">
                                    <option value="Walk-in">หน้าเคาน์เตอร์ (Walk-in)</option>
                                    <option value="Website">จองผ่านเว็บไซต์ (Website)</option>
                                    <option value="LINE">LINE Official</option>
                                    <option value="Facebook">Facebook Page</option>
                                    <option value="Agoda">Agoda / Booking.com</option>
                                </select>
                                <i class="ph-bold ph-caret-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            </div>
                        </div>
                        <!-- ซ่อนสถานะบัญชีไม่ให้แก้แมนนวล แต่ยังส่งค่าเดิมกลับไป -->
                        <input type="hidden" name="status" id="cusStatus" value="active">
                    </div>
                </div>

                <div class="p-6 border-t border-gray-800 shrink-0 flex justify-end bg-[#0a0a0a] rounded-b-2xl">
                    <button type="button" onclick="closeModal()" class="neon-pro px-8 py-3 flex items-center gap-2 mr-4" style="--neon-color: #a3a3a3;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-x text-xl text-gray-300 relative z-10 icon-glow"></i>
                        <span class="font-bold text-gray-300 relative z-10">ยกเลิก</span>
                    </button>
                    <button type="submit" class="neon-pro px-10 py-3 flex items-center gap-2" style="--neon-color: #ff3c00;">
                        <div class="neon-pro-glow"></div>
                        <i class="ph-bold ph-floppy-disk text-xl text-white relative z-10 icon-glow"></i>
                        <span class="font-bold text-white relative z-10">บันทึกข้อมูล</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 🔥 Modal: ประวัติลูกค้า (History) -->
    <div id="historyModal" class="fixed inset-0 bg-black/90 z-[100] hidden items-center justify-center backdrop-blur-sm overflow-y-auto pt-10 pb-10" style="display: none;">
        <div class="bg-[#0f0f0f] w-full max-w-4xl rounded-2xl border border-gray-800 shadow-[0_0_30px_rgba(0,208,255,0.15)] relative my-auto flex flex-col max-h-[90vh]">
            
            <div class="flex justify-between items-center p-6 border-b border-gray-800 bg-[#0a0a0a] rounded-t-2xl shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-ycBlue/20 rounded-xl flex items-center justify-center text-ycBlue shadow-[0_0_10px_rgba(0,208,255,0.3)]">
                        <i class="ph-bold ph-clock-counter-clockwise text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white drop-shadow-md">ประวัติลูกค้ารายบุคคล <span id="histPhone" class="text-sm font-normal text-gray-400 ml-2"></span></h3>
                </div>
                <button type="button" onclick="closeHistoryModal()" class="w-8 h-8 bg-[#1a1a1a] hover:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 transition">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto flex-1">
                <!-- สรุปข้อมูล -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-[#111] border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-ycGold/10 flex items-center justify-center text-ycGold"><i class="ph-bold ph-calendar-check text-2xl"></i></div>
                        <div><p class="text-xs text-gray-500 font-bold">จำนวนครั้งที่เข้าพัก</p><p class="text-xl font-bold text-white" id="histCount">0 ครั้ง</p></div>
                    </div>
                    <div class="bg-[#111] border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-ycGreen/10 flex items-center justify-center text-ycGreen"><i class="ph-bold ph-money text-2xl"></i></div>
                        <div><p class="text-xs text-gray-500 font-bold">ยอดใช้จ่ายรวม (บาท)</p><p class="text-xl font-bold text-white" id="histTotal">0</p></div>
                    </div>
                    <div class="bg-[#111] border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-ycRed/10 flex items-center justify-center text-ycRed"><i class="ph-bold ph-user-minus text-2xl"></i></div>
                        <div><p class="text-xs text-gray-500 font-bold">ประวัติ No-Show / ยกเลิก</p><p class="text-xl font-bold text-white" id="histCancel">0 ครั้ง</p></div>
                    </div>
                </div>

                <!-- ตารางประวัติ -->
                <div class="border border-gray-800 rounded-xl overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-400 min-w-[800px]">
                        <thead class="bg-[#050505] text-gray-500 uppercase font-bold border-b border-gray-800">
                            <tr>
                                <th class="px-4 py-3">Ref / วันที่จอง</th>
                                <th class="px-4 py-3">ห้องพัก</th>
                                <th class="px-4 py-3">เช็คอิน - เช็คเอาท์</th>
                                <th class="px-4 py-3 text-right">ยอดชำระ</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 bg-[#0a0a0a]" id="historyTableBody">
                            <tr><td colspan="5" class="text-center py-8">กำลังโหลดข้อมูล...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').innerText = now.toLocaleTimeString('th-TH');
        }
        setInterval(updateTime, 1000);
        updateTime();

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        const openBtn = document.getElementById('open-sidebar');
        const closeBtn = document.getElementById('close-sidebar');

        function toggleMenu() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        openBtn.addEventListener('click', toggleMenu);
        closeBtn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);

        // ค้นหา
        document.getElementById('searchCustomer').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.cus-row');
            let found = false;
            
            rows.forEach(row => {
                let name = row.querySelector('.cus-name').innerText.toLowerCase();
                let phone = row.querySelector('.cus-phone').innerText.toLowerCase();
                let channel = row.querySelector('.cus-channel').innerText.toLowerCase();
                let status = row.querySelector('.cus-status').innerText.toLowerCase();
                
                if (name.includes(filter) || phone.includes(filter) || channel.includes(filter) || status.includes(filter)) {
                    row.style.display = '';
                    found = true;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('noSearchResult').classList.toggle('hidden', found);
        });

        // 🌟 สร้างดาวตก (Background Animation)
        function createStars() {
            const container = document.getElementById('starsBox');
            for (let i = 0; i < 30; i++) {
                let star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + 'vw';
                star.style.top = Math.random() * -20 + 'vh';
                star.style.animationDuration = (Math.random() * 3 + 2) + 's';
                star.style.animationDelay = (Math.random() * 5) + 's';
                container.appendChild(star);
            }
        }
        createStars();

        // โหลดประวัติ
        function viewHistory(customerId) {
            // โชว์หน้าโหลดก่อน
            document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="5" class="text-center py-8">กำลังโหลดข้อมูล...</td></tr>';
            document.getElementById('histCount').innerText = '0 ครั้ง';
            document.getElementById('histTotal').innerText = '0';
            document.getElementById('histCancel').innerText = '0 ครั้ง';
            
            const hmodal = document.getElementById('historyModal');
            hmodal.classList.remove('hidden');
            hmodal.style.display = 'flex';
            
            fetch('actions/get_customer_history.php?id=' + customerId)
                .then(response => response.json())
                .then(data => {
                    if (data.success === false) {
                        document.getElementById('historyTableBody').innerHTML = `<tr><td colspan="5" class="text-center py-8 text-red-500">เกิดข้อผิดพลาด: ${data.error || 'ไม่พบข้อมูลลูกค้า'}</td></tr>`;
                        return;
                    }
                    document.getElementById('histCount').innerText = data.total_stays + ' ครั้ง';
                    document.getElementById('histTotal').innerText = parseFloat(data.total_spent).toLocaleString() + ' บาท';
                    document.getElementById('histCancel').innerText = data.total_canceled + ' ครั้ง';
                    
                    document.getElementById('histPhone').innerText = data.customer_phone ? `(เบอร์โทร: ${data.customer_phone})` : '';
                    let tbody = document.getElementById('historyTableBody');
                    tbody.innerHTML = '';
                    if(data.bookings && data.bookings.length > 0) {
                        data.bookings.forEach(b => {
                            let statusBadge = '';
                            if(b.status === 'checked_in') statusBadge = '<span class="text-[#00d0ff] bg-blue-900/40 px-2 py-1 rounded-md text-xs font-bold">กำลังพัก</span>';
                            else if(b.status === 'checked_out') statusBadge = '<span class="text-[#ff3c00] bg-red-900/20 px-2 py-1 rounded-md text-xs font-bold">เช็คเอาท์</span>';
                            else if(b.status === 'canceled' || b.status === 'cancelled') statusBadge = '<span class="text-red-500 bg-red-900/20 px-2 py-1 rounded-md text-xs font-bold">ยกเลิก</span>';
                            else statusBadge = '<span class="text-ycGold bg-yellow-900/40 px-2 py-1 rounded-md text-xs font-bold">รอเช็คอิน</span>';
                            
                            let price = b.final_price ? parseFloat(b.final_price).toLocaleString() : '0';
                            let slipBtn = b.slip_image ? `<br><a href="../${b.slip_image}" target="_blank" class="inline-flex items-center gap-1 mt-1 text-xs text-ycBlue hover:text-white bg-ycBlue/10 px-2 py-1 rounded transition"><i class="ph-bold ph-receipt"></i> ดูสลิป</a>` : '';
                            
                            tbody.innerHTML += `
                                <tr class="hover:bg-[#151515] transition">
                                    <td class="px-4 py-3"><p class="font-bold text-white">#${b.id}</p><p class="text-xs text-gray-500">${b.created_at}</p></td>
                                    <td class="px-4 py-3">${b.room_name}</td>
                                    <td class="px-4 py-3 text-xs"><p>เข้า: ${b.check_in}</p><p>ออก: ${b.check_out}</p></td>
                                    <td class="px-4 py-3 text-right"><span class="font-bold text-ycGreen">฿${price}</span> ${slipBtn}</td>
                                    <td class="px-4 py-3 text-center">${statusBadge}</td>
                                </tr>
                            `;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500">ไม่มีประวัติการจอง</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching history:', error);
                    document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
                });
        }

        function closeHistoryModal() {
            const hmodal = document.getElementById('historyModal');
            hmodal.classList.add('hidden');
            hmodal.style.display = 'none';
        }

        // Auto Refresh & Idle Timer
        let idleTime = 0;
        document.addEventListener('mousemove', () => idleTime = 0);
        document.addEventListener('keypress', () => idleTime = 0);
        setInterval(() => {
            idleTime += 1;
            if (idleTime >= 60) {
                let isModalOpen = !document.getElementById('customerModal').classList.contains('hidden');
                let isHistoryOpen = !document.getElementById('historyModal').classList.contains('hidden');
                if(!isModalOpen && !isHistoryOpen) location.reload();
            }
        }, 1000);

        const modal = document.getElementById('customerModal');
        
        function openModal(mode, id = '', fname = '', lname = '', phone = '', email = '', auth = 'local', status = 'active') {
            modal.classList.remove('hidden');
            // use inline style to force display flex for tailwind conflict issue
            modal.style.display = 'flex';
            
            if(mode === 'edit') {
                document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลลูกค้า';
                document.getElementById('modalIcon').className = 'ph-bold ph-pencil-simple text-xl';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('customerId').value = id;
                document.getElementById('firstName').value = fname;
                document.getElementById('lastName').value = lname;
                document.getElementById('phone').value = phone;
                document.getElementById('email').value = email;
                document.getElementById('authProvider').value = auth;
                document.getElementById('cusStatus').value = status; document.getElementById('cusStatus').dispatchEvent(new Event('change'));
            } else {
                document.getElementById('modalTitle').innerText = 'เพิ่มลูกค้าใหม่';
                document.getElementById('modalIcon').className = 'ph-bold ph-user-plus text-xl';
                document.getElementById('formAction').value = 'add';
                document.getElementById('customerId').value = '';
                document.getElementById('firstName').value = '';
                document.getElementById('lastName').value = '';
                document.getElementById('phone').value = '';
                document.getElementById('email').value = '';
                document.getElementById('authProvider').value = 'local';
                document.getElementById('cusStatus').value = 'active';
            }
        }
        
        function closeModal() {
            modal.classList.add('hidden');
            modal.style.display = 'none';
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
