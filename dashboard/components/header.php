<?php
// Ensure this file is loaded within a valid session context
$top_title = isset($top_title) ? $top_title :  'EXECUTIVE';
$top_subtitle = isset($top_subtitle) ? $top_subtitle :  'DASHBOARD';
$top_desc = isset($top_desc) ? $top_desc :  'ศูนย์บัญชาการข้อมูลระดับผู้บริหาร';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title :  'Yuncha Valley | Dashboard' ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { ycDeep: '#000000', ycSurface: '#0f0f0f', ycGreen: '#00ff41', ycGold: '#ffcc00', ycBlue: '#00d0ff', ycPink: '#ff00ff', ycRed: '#ff003c', ycOrange: '#ff5e00', ycMint: '#00e676' }, fontFamily: { sans: ['Prompt', 'sans-serif'] } } }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal2-popup { font-family: 'Prompt', sans-serif !important; border-radius: 1rem !important; background: #1a1a1a !important; color: #fff !important; border: 1px solid #333 !important; box-shadow: 0 0 20px rgba(0,0,0,0.5) !important; }
        .swal2-title { color: #fff !important; }
        .swal2-html-container { color: #aaa !important; }
        .swal2-confirm { border-radius: 0.5rem !important; font-weight: 600 !important; }
        .swal2-cancel { border-radius: 0.5rem !important; font-weight: 600 !important; }
    </style>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <?= isset($extra_head) ? $extra_head :  '' ?>
</head>
<body class="h-screen flex selection:<?= isset($selection_color) ? $selection_color :  'bg-ycGold' ?> selection:text-black">
    <div class="stars-container" id="starsBox"></div>
    <div id="mobile-overlay" class="fixed inset-0 bg-black/90 z-40 hidden lg:hidden"></div>
    
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden w-full relative z-10 bg-transparent">
        <?php if (!isset($skip_topbar)): ?>
        <header class="h-24 bg-black/50 backdrop-blur-md border-b border-gray-800 px-4 lg:px-8 flex justify-between items-center z-30 sticky top-0">
            <div class="flex items-center gap-4">
                <button id="open-sidebar" class="lg:hidden p-2 bg-[#0f0f0f] rounded-lg text-ycBlue border border-gray-800"><i class="ph-bold ph-list text-2xl"></i></button>
                <div>
                    <h2 class="text-2xl font-black text-white tracking-wide"><?= $top_title ?> <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00d0ff] to-[#ff00ff]"><?= $top_subtitle ?></span></h2>
                    <p class="text-sm text-gray-400 font-medium"><?= $top_desc ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <?= isset($top_right_content) ? $top_right_content :  '' ?>
                <p class="text-sm font-bold text-gray-400 hidden lg:block mr-4">เวลาปัจจุบัน: <span id="clockStatus" style="color: #00d0ff;"><?= date('H:i:s') ?></span></p>
            </div>
        </header>
        <?php endif; ?>
        <main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
            <div class="max-w-[1500px] mx-auto space-y-6 pb-12">
