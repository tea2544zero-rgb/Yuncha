<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 w-72 h-screen bg-[#050505] border-r border-gray-800 flex flex-col z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <div class="h-24 flex items-center justify-between lg:justify-center px-6 lg:px-0 border-b border-gray-800 bg-black relative z-10 shrink-0">
        <h1 class="text-3xl font-bold tracking-widest text-ycGold drop-shadow-[0_0_15px_rgba(255,204,0,0.8)]">YUNCHA</h1>
        <button id="close-sidebar" class="lg:hidden text-white"><i class="ph-bold ph-x text-2xl"></i></button>
    </div>
    <div class="flex-1 overflow-y-auto relative z-10 bg-[#050505] flex flex-col" id="sidebarScrollBox">
        <nav class="px-4 py-4 space-y-1.5">
            
            <?php if ($is_account): ?>
            <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">ข้อมูลภาพรวมและการเงิน</p>
            <?php endif; ?>
            
            <a href="dashboard.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #00d0ff;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-squares-four text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php') echo 'style="color: #00d0ff;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">แผงควบคุมหลัก</span>
            </a>
            
            <?php if ($is_account): ?>
            <a href="reports.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #b537f2;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'reports.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-chart-polar text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'reports.php') echo 'style="color: #b537f2;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'reports.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">รายงานวิเคราะห์</span>
            </a>
            <a href="finance.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #00e676;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'finance.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-wallet text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'finance.php') echo 'style="color: #00e676;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'finance.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">บัญชี / การเงิน</span>
            </a>
            <?php endif; ?>
            
            <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 mt-3 border-t border-gray-800 pt-3">งานปฏิบัติการ</p>
            
            <a href="bookings.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #c100f1;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'bookings.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-calendar-check text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'bookings.php') echo 'style="color: #c100f1;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'bookings.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">การจองห้องพัก</span>
            </a>
            <a href="frontdesk.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #ff009d;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'frontdesk.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-storefront text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'frontdesk.php') echo 'style="color: #ff009d;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'frontdesk.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">เคาน์เตอร์ต้อนรับ</span>
            </a>
            
            <?php if ($is_counter): ?>
            <a href="rooms.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #00ff41;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'rooms.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-bed text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'rooms.php') echo 'style="color: #00ff41;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'rooms.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">จัดการห้องพัก</span>
            </a>
            <?php endif; ?>
            
            <a href="customers.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #ff3c00;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'customers.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-users-three text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'customers.php') echo 'style="color: #ff3c00;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'customers.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">จัดการลูกค้า</span>
            </a>
            
            <a href="reviews.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #ffd700;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'reviews.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-star text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'reviews.php') echo 'style="color: #ffd700;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'reviews.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">จัดการรีวิว</span>
            </a>
            
            <?php if ($is_manager): ?>
            <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 mt-3 border-t border-gray-800 pt-3">การตั้งค่าและบุคลากร</p>
            
            <a href="employees.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #ffcc00;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'employees.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-identification-card text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'employees.php') echo 'style="color: #ffcc00;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'employees.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">จัดการพนักงาน</span>
            </a>
            <a href="settings.php" class="neon-pro flex items-center gap-3 px-4 py-2.5" style="--neon-color: #ffffff;">
                <div class="neon-pro-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'settings.php') echo 'style="opacity: 1;"'; ?>></div>
                <i class="ph-fill ph-gear text-xl icon-glow" <?php if (basename($_SERVER['PHP_SELF']) === 'settings.php') echo 'style="color: #ffffff;"'; ?>></i>
                <span class="text-sm font-bold <?php echo (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'text-white' : 'text-gray-400 hover:text-white transition'; ?> relative z-10">ตั้งค่าระบบ</span>
            </a>
            <?php endif; ?>
            
        </nav>
    </div>
    <div class="p-4 border-t border-gray-800 bg-[#050505] relative z-10 shrink-0">
        <div class="flex items-center justify-between px-4 py-3 bg-[#0f0f0f] rounded-xl border border-gray-800 shadow-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-ycGold font-bold text-lg border border-gray-700">
                    <?= substr(isset($_SESSION['emp_code']) ? $_SESSION['emp_code'] :  'A', 0, 1) ?>
                </div>
                <div>
                    <p class="text-sm font-bold text-white"><?= isset($_SESSION['emp_name']) ? $_SESSION['emp_name'] :  'Admin' ?></p>
                    <p class="text-xs font-bold text-ycGold mt-0.5"><?= strtoupper(isset($clean_role) ? $clean_role :  'ADMIN') ?></p>
                </div>
            </div>
            <a href="logout.php" class="text-gray-500 hover:text-ycRed transition p-2 bg-[#1a1a1a] rounded-lg border border-gray-800 hover:border-ycRed/50 group" title="ออกจากระบบ">
                <i class="ph-bold ph-sign-out text-xl group-hover:drop-shadow-[0_0_8px_#ff003c]"></i>
            </a>
        </div>
    </div>
</aside>
