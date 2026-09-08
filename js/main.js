history.scrollRestoration = "manual";
window.scrollTo(0, 0);

gsap.registerPlugin(ScrollTrigger);

const lenis = new Lenis({ duration: 1.2, easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), smooth: true });
function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
requestAnimationFrame(raf);
lenis.on('scroll', ScrollTrigger.update);
gsap.ticker.add((time) => { lenis.raf(time * 1000); });

const isTouchDevice = () => !window.matchMedia('(any-pointer: fine)').matches;

const navItems = document.querySelectorAll('.nav-item');
navItems.forEach(item => {
    item.addEventListener('click', function() {
        navItems.forEach(nav => nav.classList.remove('active'));
        this.classList.add('active');
    });
});

const hamBtn = document.getElementById('hamburger-btn');
const mobileMenu = document.getElementById('mobile-menu');
const closeMenuBtn = document.getElementById('close-menu-btn');
const menuLinks = document.querySelectorAll('.mobile-link');

function openMenu() {
    mobileMenu.classList.remove('hidden');
    mobileMenu.classList.add('flex');
    setTimeout(() => { mobileMenu.classList.remove('opacity-0'); }, 10);
}

function closeMenu() {
    mobileMenu.classList.add('opacity-0');
    setTimeout(() => {
        mobileMenu.classList.add('hidden');
        mobileMenu.classList.remove('flex');
    }, 300);
}

if(hamBtn) hamBtn.addEventListener('click', openMenu);
if(closeMenuBtn) closeMenuBtn.addEventListener('click', closeMenu);
menuLinks.forEach(link => link.addEventListener('click', closeMenu));

// ==========================================
// 5. Preloader & GSAP
// ==========================================
let pl = document.getElementById('preloader');
if (pl) pl.style.display = 'none';
gsap.set("#hero-img", { filter: "brightness(0.6)" });
gsap.to(".hero-text", { y: "0%", duration: 1.5, stagger: 0.1, ease: "power4.out" });

// ==========================================
// 6. Scroll Animations 
// ==========================================
gsap.utils.toArray('.img-mask-wrapper').forEach(wrapper => {
    gsap.to(wrapper, { scrollTrigger: { trigger: wrapper, start: "top 90%", toggleActions: "play reverse play reverse" }, clipPath: "inset(0% 0 0 0)", duration: 1.2, ease: "power3.inOut" });
    const img = wrapper.querySelector('.parallax-img');
    if(img && !isTouchDevice()) { gsap.to(img, { scrollTrigger: { trigger: wrapper, start: "top bottom", end: "bottom top", scrub: true }, y: "10%", ease: "none" }); }
});

// ==========================================
// แอนิเมชันห้องพัก (VILLAS) 
// ==========================================

// ห้องที่ 1: ปาดม่านเปิดจากซ้ายไปขวา + รูปสไลด์ซ้าย
if (document.querySelector(".villa-card-1")) {
    gsap.to(".villa-mask-1", {
        scrollTrigger: { trigger: ".villa-card-1", start: "top 80%", toggleActions: "play reverse play reverse" },
        clipPath: "inset(0% 0% 0% 0%)",
        webkitClipPath: "inset(0% 0% 0% 0%)",
        duration: 1.5,
        ease: "power3.inOut"
    });
    gsap.fromTo(".villa-reveal-img-1",
        { opacity: 0, filter: "blur(10px)", x: "-15%" },
        { scrollTrigger: { trigger: ".villa-card-1", start: "top 80%", toggleActions: "play reverse play reverse" },
          opacity: 1, filter: "blur(0px)", x: "0%", duration: 1.5, ease: "power3.out" }
    );
    if(!isTouchDevice()) {
        gsap.to(".villa-parallax-wrap-1", {
            scrollTrigger: { trigger: ".villa-card-1", start: "top bottom", end: "bottom top", scrub: true },
            y: "15%", ease: "none"
        });
    }
}

// ห้องที่ 2: ปาดม่านจากขวามาซ้าย + รูปสไลด์เข้านุ่มๆ
if (document.querySelector(".villa-card-2")) {
    gsap.to(".villa-mask-2", {
        scrollTrigger: { trigger: ".villa-card-2", start: "top 80%", toggleActions: "play reverse play reverse" },
        clipPath: "inset(0% 0% 0% 0%)",
        webkitClipPath: "inset(0% 0% 0% 0%)",
        duration: 1.5,
        ease: "power3.inOut"
    });
    gsap.fromTo(".villa-reveal-img-2",
        { opacity: 0, filter: "blur(10px)", x: "15%" },
        { scrollTrigger: { trigger: ".villa-card-2", start: "top 80%", toggleActions: "play reverse play reverse" },
          opacity: 1, filter: "blur(0px)", x: "0%", duration: 1.5, ease: "power3.out" }
    );
    if(!isTouchDevice()) {
        gsap.to(".villa-parallax-wrap-2", {
            scrollTrigger: { trigger: ".villa-card-2", start: "top bottom", end: "bottom top", scrub: true },
            y: "15%", ease: "none"
        });
    }
}

// ห้องที่ 3: Vertical Reveal รูปลอยลงมา และไหลกลับขึ้นไปเมื่อเลื่อนกลับ
if (document.querySelector(".villa-card-3")) {
    gsap.to(".villa-mask-3", {
        scrollTrigger: { trigger: ".villa-card-3", start: "top 80%", toggleActions: "play reverse play reverse" },
        clipPath: "inset(0% 0% 0% 0%)",
        webkitClipPath: "inset(0% 0% 0% 0%)",
        duration: 1.5,
        ease: "power3.inOut"
    });
    gsap.fromTo(".villa-reveal-img-3",
        { opacity: 0, filter: "blur(10px)", y: "-15%" },
        { scrollTrigger: { trigger: ".villa-card-3", start: "top 80%", toggleActions: "play reverse play reverse" },
          opacity: 1, filter: "blur(0px)", y: "0%", duration: 1.5, ease: "power3.out" }
    );
    if(!isTouchDevice()) {
        gsap.to(".villa-parallax-wrap-3", {
            scrollTrigger: { trigger: ".villa-card-3", start: "top bottom", end: "bottom top", scrub: true },
            y: "15%", ease: "none"
        });
    }
}

gsap.utils.toArray('.mask-wrap').forEach(mask => {
    const text = mask.querySelector('.scroll-txt');
    if(text) { gsap.to(text, { scrollTrigger: { trigger: mask, start: "top 95%", toggleActions: "play reverse play reverse" }, y: "0%", duration: 1, ease: "power3.out" }); }
});

// ==========================================
// [แก้ไข] ลบแอนิเมชันปักหมุด (Pin) และเลื่อนแนวนอน (Horizontal) ออก
// เพิ่มแอนิเมชันการ์ดเด้งขึ้นมาแทน สำหรับ Activities Section
// ==========================================
gsap.utils.toArray('.activity-card').forEach((card, i) => {
    gsap.fromTo(card,
        { y: 50, opacity: 0 },
        { 
            scrollTrigger: { 
                trigger: card, 
                start: "top 85%", 
                toggleActions: "play reverse play reverse" 
            }, 
            y: 0, 
            opacity: 1, 
            duration: 1, 
            ease: "power3.out", 
            delay: (i % 3) * 0.1 // ทำให้การ์ดเรียงตัวค่อยๆ เด้งขึ้นมาทีละใบ
        }
    );
});

window.addEventListener('scroll', () => {
    const nav = document.getElementById('navbar');
    if (window.pageYOffset > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});