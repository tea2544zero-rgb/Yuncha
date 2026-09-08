// dashboard/assets/js/main.js

// 🌟 จำตำแหน่ง Scroll ของเมนูด้านซ้าย (แก้ปัญหาเด้งกลับ)
document.addEventListener("DOMContentLoaded", function(event) { 
    var scrollpos = sessionStorage.getItem('sidebarScrollPos');
    var sidebarEl = document.getElementById('sidebarScrollBox');
    if (scrollpos && sidebarEl) {
        sidebarEl.scrollTop = scrollpos;
    }
});
window.onbeforeunload = function(e) {
    var sidebarEl = document.getElementById('sidebarScrollBox');
    if(sidebarEl) {
        sessionStorage.setItem('sidebarScrollPos', sidebarEl.scrollTop);
    }
};

// 🌟 สร้างดาวตก Background
function createStars() {
    const box = document.getElementById('starsBox');
    if (!box) return;
    for(let i=0; i<30; i++) {
        let star = document.createElement('div');
        star.className = 'star';
        star.style.left = `${Math.random() * 100}vw`;
        star.style.animationDuration = `${Math.random() * 3 + 2}s`;
        star.style.animationDelay = `${Math.random() * 5}s`;
        box.appendChild(star);
    }
}
createStars();

// 🌟 Idle Check System (รีเฟรชถ้าปล่อยเมาส์ทิ้งไว้ 60 วิ)
let idleTime = 0;
window.onload = resetIdle;
window.onmousemove = resetIdle;
window.onkeypress = resetIdle;
function resetIdle() { idleTime = 0; }

setInterval(function() {
    idleTime += 1;
    const now = new Date();
    const clockEl = document.getElementById('clockStatus');
    if (clockEl) clockEl.innerText = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    if (idleTime >= 60) {
        location.reload();
    }
}, 1000);

// Sidebar Control
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('mobile-overlay');
function toggleSidebar() { 
    if(sidebar) sidebar.classList.toggle('-translate-x-full'); 
    if(overlay) overlay.classList.toggle('hidden'); 
}
const openBtn = document.getElementById('open-sidebar');
const closeBtn = document.getElementById('close-sidebar');
if(openBtn) openBtn.addEventListener('click', toggleSidebar);
if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
if(overlay) overlay.addEventListener('click', toggleSidebar);

// Toast Notification System
function showToast(message, isSuccess = true) {
    const toast = document.getElementById('toast');
    const toastIcon = document.getElementById('toast-icon');
    const toastMsg = document.getElementById('toast-msg');
    if (!toast) return;

    toastMsg.textContent = message;
    if (isSuccess) {
        toastIcon.innerHTML = '<i class="ph-bold ph-check"></i>';
        toastIcon.className = 'w-6 h-6 rounded-full flex items-center justify-center text-emerald-500 bg-emerald-500/10 border border-emerald-500/20 text-sm font-bold';
    } else {
        toastIcon.innerHTML = '<i class="ph-bold ph-x"></i>';
        toastIcon.className = 'w-6 h-6 rounded-full flex items-center justify-center text-rose-500 bg-rose-500/10 border border-rose-500/20 text-sm font-bold';
    }
    
    toast.classList.remove('translate-y-24', 'opacity-0');
    setTimeout(() => {
        toast.classList.add('translate-y-24', 'opacity-0');
    }, 4000);
}
