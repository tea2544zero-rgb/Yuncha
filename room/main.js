history.scrollRestoration = "manual";
window.scrollTo(0, 0);

gsap.registerPlugin(ScrollTrigger);

// 1. Lenis Smooth Scroll (Ultra Premium Feel)
const lenis = new Lenis({ 
    duration: 1.5, 
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), 
    smooth: true 
});

function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
requestAnimationFrame(raf);

lenis.on('scroll', ScrollTrigger.update);
gsap.ticker.add((time) => { lenis.raf(time * 1000); });

// 2. Custom Cursor Logic
const cursorDot = document.querySelector('.cursor-dot');
const cursorRing = document.querySelector('.cursor-ring');
const cursorText = document.getElementById('cursor-text');
const isTouchDevice = () => !window.matchMedia('(any-pointer: fine)').matches;

if (!isTouchDevice()) {
    window.addEventListener('mousemove', (e) => {
        gsap.to(cursorDot, { x: e.clientX, y: e.clientY, duration: 0 });
        gsap.to(cursorRing, { x: e.clientX, y: e.clientY, duration: 0.15, ease: "power2.out" });
    });
    
    document.querySelectorAll('.hover-target').forEach(el => {
        el.addEventListener('mouseenter', () => {
            let isExplore = el.classList.contains('cursor-explore');
            
            gsap.to(cursorRing, {
                width: isExplore ? 70 : 50,
                height: isExplore ? 70 : 50,
                backgroundColor: isExplore ? 'rgba(217, 119, 6, 0.95)' : 'rgba(217, 119, 6, 0.2)',
                borderColor: isExplore ? '#fbbf24' : 'rgba(217, 119, 6, 0.6)',
                duration: 0.3,
                ease: "power2.out"
            });

            if (isExplore) {
                cursorText.innerHTML = `<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`;
                
                gsap.fromTo(cursorText.firstChild, 
                    { scale: 0, opacity: 0 }, 
                    { scale: 1, opacity: 1, duration: 0.3, ease: "back.out(1.7)" }
                );
                gsap.to(cursorText.firstChild, {
                    scale: 1.15,
                    duration: 0.8,
                    repeat: -1,
                    yoyo: true,
                    ease: "power1.inOut",
                    delay: 0.3
                });
            } else {
                cursorText.innerHTML = '';
            }
            gsap.to(cursorDot, { opacity: 0, duration: 0.2 });
        });

        el.addEventListener('mouseleave', () => {
            gsap.to(cursorRing, {
                width: 40,
                height: 40,
                backgroundColor: 'transparent',
                borderColor: 'rgba(217, 119, 6, 0.6)',
                duration: 0.3,
                ease: "power2.out"
            });
            if (cursorText.firstChild) {
                gsap.killTweensOf(cursorText.firstChild);
            }
            cursorText.innerHTML = "";
            gsap.to(cursorDot, { opacity: 1, duration: 0.2 });
        });
    });
}

// 3. Cinematic Entrance (Room Hero Reveal)
window.addEventListener('load', () => {
    const tl = gsap.timeline();
    
    tl.to(".hero-clip-reveal", { clipPath: "inset(0% 0% 0% 0%)", duration: 1.8, ease: "power4.inOut" })
      .to(".hero-text-reveal", { y: "0%", opacity: 1, duration: 1.2, stagger: 0.1, ease: "power3.out" }, "-=1.2");
    if (document.querySelector(".fade-up-anim")) {
        tl.to(".fade-up-anim", { y: 0, opacity: 1, duration: 1, stagger: 0.15, ease: "power3.out" }, "-=0.8");
    }
});

// 4. Parallax Effect for Hero Image (Gentle Deep Zoom + Fade to dark)
if (document.querySelector(".hero-deep-zoom")) {
    gsap.to(".hero-deep-zoom", {
        scale: 5,
        ease: "none",
        scrollTrigger: {
            trigger: ".hero-section-trigger",
            start: "top top",
            end: "bottom top",
            scrub: true
        }
    });
}
if (document.querySelector(".hero-overlay-dark")) {
    gsap.to(".hero-overlay-dark", {
        opacity: 1,
        ease: "none",
        scrollTrigger: {
            trigger: ".hero-section-trigger",
            start: "top top",
            end: "bottom top",
            scrub: true
        }
    });
}

// 5. Scroll Fade Up for all standard sections
gsap.utils.toArray('.gs-fade-up').forEach(elem => {
    gsap.fromTo(elem, 
        { y: 60, opacity: 0 },
        {
            y: 0, opacity: 1, duration: 1.4, ease: "power3.out",
            scrollTrigger: {
                trigger: elem,
                start: "top 85%",
                toggleActions: "play none none reverse"
            }
        }
    );
});

// 5.5 Horizontal Scroll for Amenities
const amenitiesTrack = document.querySelector('.horizontal-scroll-container');
if(amenitiesTrack) {
    let scrollWidth = Math.max(0, amenitiesTrack.scrollWidth - window.innerWidth + 150);
    if(scrollWidth > 0) {
        gsap.to(amenitiesTrack, {
            x: -scrollWidth,
            ease: "none",
            scrollTrigger: {
                trigger: "#amenities-trigger",
                pin: true,
                scrub: 1,
                start: "center center",
                end: () => "+=" + scrollWidth
            }
        });
    }
}

// 6. Infinite Review Marquee Clone
const marqueeTrack = document.querySelector('.marquee-track');
if(marqueeTrack) {
    marqueeTrack.innerHTML += marqueeTrack.innerHTML;
}

// 7. Navbar Scrolled State
window.addEventListener('scroll', () => {
    const nav = document.getElementById('navbar');
    if (window.pageYOffset > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

// Mobile Hamburger Menu logic
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

// 8. 3D Scroll & Mouse Interactions
// Add 3D rotation to gallery items on scroll
gsap.utils.toArray('#gallery .group').forEach(item => {
    gsap.fromTo(item, 
        { rotationX: 15, rotationY: -10, transformPerspective: 1000, opacity: 0, y: 100 },
        { 
            rotationX: 0, rotationY: 0, opacity: 1, y: 0, 
            duration: 1.5, ease: "power3.out",
            scrollTrigger: {
                trigger: item,
                start: "top 85%",
                toggleActions: "play none none reverse"
            }
        }
    );
});
