<?php
session_start();
require_once 'config/security.php';
require_once 'config/db.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $review_id = (int)(isset($_POST['review_id']) ? $_POST['review_id'] :  0);
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success'] = "อนุมัติรีวิวเรียบร้อยแล้ว";
        header("Location: reviews.php"); exit;
    } elseif ($action === 'hide') {
        $stmt = $conn->prepare("UPDATE reviews SET is_approved = 0 WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success'] = "ซ่อนรีวิวเรียบร้อยแล้ว";
        header("Location: reviews.php"); exit;
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success'] = "ลบรีวิวเรียบร้อยแล้ว";
        header("Location: reviews.php"); exit;
    }
}

// ดึงข้อมูลรีวิวทั้งหมด
$sql_reviews = "
    SELECT r.*, c.first_name, c.last_name, 
           rt.type_name as room_name,
           p.name as package_name
    FROM reviews r 
    JOIN customers c ON r.customer_id = c.id
    LEFT JOIN room_types rt ON r.review_type = 'room' AND r.target_id = rt.id
    LEFT JOIN packages p ON r.review_type = 'activity' AND r.target_id = p.id
    ORDER BY r.created_at DESC
";
$stmt_reviews = $conn->query($sql_reviews);
$reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$total_reviews = count($reviews);
$pending_reviews = count(array_filter($reviews, function($r) { return $r['is_approved'] == 0; }));
$approved_reviews = count(array_filter($reviews, function($r) { return $r['is_approved'] == 1; }));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรีวิว (Reviews) | Yuncha Valley</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
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
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #000000; color: #ffffff; font-family: 'Prompt', sans-serif; overflow-x: hidden; position: relative;}
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #000000; }
        ::-webkit-scrollbar-thumb { background: #ffd700; border-radius: 10px; }

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
    </style>
</head>
<body class="h-screen flex selection:bg-ycGold selection:text-black">

    <div class="stars-container" id="starsBox"></div>
    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>

    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative z-10">
        
        <header class="h-24 bg-[#050505]/80 backdrop-blur-md border-b border-gray-800 flex items-center justify-between px-8 shrink-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden text-white hover:text-ycGold transition"><i class="ph-bold ph-list text-3xl"></i></button>
                <div class="gs-anim">
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight flex items-center gap-3">
                        REVIEW <span class="text-transparent bg-clip-text bg-gradient-to-r from-ycGold to-[#ff8c00]">MANAGEMENT</span>
                    </h2>
                    <p class="text-gray-400 text-sm mt-1">ระบบจัดการรีวิวและคะแนนจากลูกค้า</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right hidden md:block">
                    <p class="text-gray-400 text-sm font-bold">เวลาปัจจุบัน</p>
                    <p class="text-xl font-bold text-ycGold" id="currentTime">00:00:00</p>
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
                            confirmButtonColor: '#ffd700'
                        });
                    </script>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 gs-anim" style="animation-delay: 0.1s;">
                    <div class="bg-[#0f0f0f] border border-gray-800 rounded-2xl p-6 flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500"><i class="ph-fill ph-chats text-3xl"></i></div>
                        <div><p class="text-sm font-bold text-gray-500">รีวิวทั้งหมด</p><p class="text-3xl font-black text-white"><?= $total_reviews ?></p></div>
                    </div>
                    <div class="bg-[#0f0f0f] border border-[#ff5e00]/30 shadow-[0_0_15px_rgba(255,94,0,0.1)] rounded-2xl p-6 flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-[#ff5e00]/20 flex items-center justify-center text-[#ff5e00]"><i class="ph-fill ph-clock text-3xl"></i></div>
                        <div><p class="text-sm font-bold text-gray-400">รอการตรวจสอบ</p><p class="text-3xl font-black text-[#ff5e00]"><?= $pending_reviews ?></p></div>
                    </div>
                    <div class="bg-[#0f0f0f] border border-[#00ff41]/30 shadow-[0_0_15px_rgba(0,255,65,0.1)] rounded-2xl p-6 flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-[#00ff41]/20 flex items-center justify-center text-[#00ff41]"><i class="ph-fill ph-check-circle text-3xl"></i></div>
                        <div><p class="text-sm font-bold text-gray-400">อนุมัติแล้ว</p><p class="text-3xl font-black text-[#00ff41]"><?= $approved_reviews ?></p></div>
                    </div>
                </div>

                <div class="bg-[#0f0f0f] border border-[#ffd700]/30 rounded-2xl shadow-[0_0_30px_rgba(255,215,0,0.1)] relative overflow-hidden flex flex-col gs-anim" style="animation-delay: 0.2s;">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#ffd700] to-transparent opacity-50"></div>
                    
                    <div class="p-6 border-b border-gray-800 bg-[#0a0a0a] flex flex-col md:flex-row md:items-center justify-between gap-4 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#ffd700]/20 flex items-center justify-center text-[#ffd700]">
                                <i class="ph-fill ph-star text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white">รายการรีวิวจากลูกค้า</h3>
                        </div>
                        
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <!-- Filters -->
                            <select id="statusFilter" class="input-dark w-full md:w-40 text-sm" style="--neon-color: #ffd700;">
                                <option value="all">สถานะทั้งหมด</option>
                                <option value="pending">รอการตรวจสอบ</option>
                                <option value="approved">อนุมัติแล้ว</option>
                            </select>
                            <select id="starFilter" class="input-dark w-full md:w-40 text-sm" style="--neon-color: #ffd700;">
                                <option value="all">ทุกระดับดาว</option>
                                <option value="5">5 ดาว</option>
                                <option value="4">4 ดาว</option>
                                <option value="3">3 ดาว</option>
                                <option value="2">2 ดาว</option>
                                <option value="1">1 ดาว</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto flex-1 p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="reviewContainer">
                            <?php if(!empty($reviews)): ?>
                                <?php foreach($reviews as $r): 
                                    $target_name = $r['review_type'] == 'room' ? (isset($r['room_name']) ? $r['room_name'] :  'ไม่ระบุห้อง') : (isset($r['package_name']) ? $r['package_name'] :  'ไม่ระบุกิจกรรม');
                                    $type_label = $r['review_type'] == 'room' ? 'ห้องพัก' : 'กิจกรรม';
                                    $status_class = $r['is_approved'] ? 'border-[#00ff41]/50' : 'border-[#ff5e00]/50';
                                    $bg_class = $r['is_approved'] ? 'bg-[#00ff41]/5' : 'bg-[#ff5e00]/5';
                                    
                                    // Generate stars HTML
                                    $stars_html = '';
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $r['rating']) {
                                            $stars_html .= '<i class="ph-fill ph-star text-ycGold"></i>';
                                        } else {
                                            $stars_html .= '<i class="ph ph-star text-gray-600"></i>';
                                        }
                                    }
                                ?>
                                <div class="review-card border <?= $status_class ?> <?= $bg_class ?> rounded-2xl p-5 relative transition hover:-translate-y-1 hover:shadow-[0_10px_20px_rgba(0,0,0,0.5)]" 
                                     data-status="<?= $r['is_approved'] ? 'approved' : 'pending' ?>" 
                                     data-stars="<?= $r['rating'] ?>">
                                    
                                    <!-- Status Badge -->
                                    <div class="absolute top-4 right-4">
                                        <?php if($r['is_approved']): ?>
                                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest bg-[#00ff41]/20 text-[#00ff41] border border-[#00ff41]/50">Approved</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest bg-[#ff5e00]/20 text-[#ff5e00] border border-[#ff5e00]/50 animate-pulse">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Header -->
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-10 h-10 rounded-full bg-gray-800 border border-gray-700 flex items-center justify-center text-gray-400 font-bold">
                                            <?= mb_substr($r['first_name'], 0, 1) ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></p>
                                            <p class="text-[10px] text-gray-500 font-mono"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-400 bg-black/40 px-2 py-1 rounded border border-gray-800">
                                            <?= $type_label ?>: <strong class="text-white"><?= htmlspecialchars($target_name) ?></strong>
                                        </span>
                                    </div>
                                    
                                    <!-- Stars -->
                                    <div class="flex gap-1 text-lg mb-3">
                                        <?= $stars_html ?>
                                    </div>
                                    
                                    <!-- Comment -->
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-300 italic mb-1">"<?= nl2br(htmlspecialchars($r['comment'])) ?>"</p>
                                        <?php if(!empty($r['english_comment']) && $r['english_comment'] !== $r['comment']): ?>
                                            <p class="text-xs text-ycGold italic">EN: "<?= nl2br(htmlspecialchars($r['english_comment'])) ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Photos -->
                                    <?php 
                                    $images = [];
                                    if (!empty($r['image_path'])) {
                                        $decoded = json_decode($r['image_path'], true);
                                        if (is_array($decoded)) {
                                            $images = $decoded;
                                        } else {
                                            $images = [$r['image_path']];
                                        }
                                    }
                                    if (!empty($images)): 
                                    ?>
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <?php foreach($images as $img): ?>
                                            <div onclick="openLightbox('../<?= htmlspecialchars($img) ?>')" class="w-12 h-12 rounded-lg overflow-hidden border border-gray-700 hover:border-ycGold transition-all cursor-pointer">
                                                <img src="../<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Actions -->
                                    <div class="flex justify-between items-center pt-4 border-t border-gray-800/50 mt-auto">
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                            <?php if($r['is_approved']): ?>
                                                <button type="submit" name="action" value="hide" class="text-xs font-bold text-gray-400 hover:text-[#ff5e00] transition flex items-center gap-1">
                                                    <i class="ph-bold ph-eye-slash"></i> ซ่อนรีวิว
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="approve" class="text-xs font-bold text-[#00ff41] hover:text-white transition flex items-center gap-1">
                                                    <i class="ph-bold ph-check-circle"></i> อนุมัติโชว์
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        
                                        <form action="" method="POST" class="inline" onsubmit="event.preventDefault(); Swal.fire({title:'ยืนยัน?', text:'ยืนยันการลบรีวิวนี้?', icon:'warning', showCancelButton:true, confirmButtonColor:'#ff003c', cancelButtonColor:'#333', confirmButtonText:'ยืนยัน', cancelButtonText:'ยกเลิก', background:'#1a1a1a', color:'#fff'}).then((r)=>{if(r.isConfirmed){this.removeAttribute('onsubmit'); this.submit();}})">
                                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                            <button type="submit" name="action" value="delete" class="text-xs font-bold text-red-500 hover:text-red-400 transition flex items-center gap-1">
                                                <i class="ph-bold ph-trash"></i> ลบ
                                            </button>
                                        </form>
                                    </div>
                                    
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-span-full text-center py-12">
                                    <i class="ph-fill ph-chat-teardrop-slash text-6xl text-gray-700 mb-4 inline-block"></i>
                                    <p class="text-gray-500 font-bold">ยังไม่มีรีวิวจากลูกค้า</p>
                                </div>
                            <?php endif; ?>
                            <!-- No Results message -->
                            <div id="noReviewsMsg" class="col-span-full text-center py-12 hidden">
                                <p class="text-gray-500 font-bold">ไม่พบรีวิวที่ตรงกับเงื่อนไข</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
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

        // Filters
        const statusFilter = document.getElementById('statusFilter');
        const starFilter = document.getElementById('starFilter');
        const cards = document.querySelectorAll('.review-card');
        const noMsg = document.getElementById('noReviewsMsg');

        function filterReviews() {
            const statusVal = statusFilter.value;
            const starVal = starFilter.value;
            let visibleCount = 0;

            cards.forEach(card => {
                const cardStatus = card.dataset.status;
                const cardStars = card.dataset.stars;

                const matchStatus = (statusVal === 'all' || cardStatus === statusVal);
                const matchStar = (starVal === 'all' || cardStars === starVal);

                if (matchStatus && matchStar) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (visibleCount === 0 && cards.length > 0) {
                noMsg.classList.remove('hidden');
            } else {
                noMsg.classList.add('hidden');
            }
        }

        statusFilter.addEventListener('change', filterReviews);
        starFilter.addEventListener('change', filterReviews);

        // Stars animation
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

        gsap.fromTo(".gs-anim", { y: 30, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, ease: "power3.out", stagger: 0.1 });
    </script>
    
    <!-- FULLSCREEN LIGHTBOX POPUP -->
    <div id="lightbox-modal" class="fixed inset-0 z-[10000] bg-black/95 backdrop-blur-xl hidden flex items-center justify-center p-4 transition-opacity duration-300" onclick="closeLightbox(event)">
        <button id="lightbox-close" class="absolute top-6 right-6 text-white hover:text-amber-500 transition-colors p-2 text-4xl font-bold z-[10001]">&times;</button>
        <img id="lightbox-img" src="" class="max-w-full max-h-[90vh] object-contain rounded-xl border border-white/10 shadow-2xl scale-95 transition-transform duration-300" alt="Enlarged View">
    </div>

    <script>
        function openLightbox(src) {
            const modal = document.getElementById('lightbox-modal');
            const img = document.getElementById('lightbox-img');
            img.src = src;
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            setTimeout(() => {
                img.classList.remove('scale-95');
                img.classList.add('scale-100');
            }, 10);
        }
        function closeLightbox(e) {
            const modal = document.getElementById('lightbox-modal');
            if (e && e.target.id === 'lightbox-img') return;
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    </script>
</body>
</html>
