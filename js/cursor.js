document.addEventListener('DOMContentLoaded', () => {
    const cursorDot = document.querySelector('.cursor-dot');
    const cursorRing = document.querySelector('.cursor-ring');
    const cursorText = document.getElementById('cursor-text');
    const isTouchDevice = () => !window.matchMedia('(any-pointer: fine)').matches;

    if (cursorDot && cursorRing && !isTouchDevice()) {
        // Move cursor elements
        window.addEventListener('mousemove', (e) => {
            gsap.to(cursorDot, { x: e.clientX, y: e.clientY, duration: 0 });
            gsap.to(cursorRing, { x: e.clientX, y: e.clientY, duration: 0.15, ease: "power2.out" });
        });

        // Event delegation for hover states
        document.body.addEventListener('mouseover', (e) => {
            const el = e.target.closest('.hover-target');
            if (el) {
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
                    if(cursorText) cursorText.innerHTML = `<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`;
                    
                    if(cursorText && cursorText.firstChild) {
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
                    }
                } else {
                    if(cursorText) cursorText.innerHTML = '';
                }
                gsap.to(cursorDot, { opacity: 0, duration: 0.2 });
            }
        });

        document.body.addEventListener('mouseout', (e) => {
            const el = e.target.closest('.hover-target');
            if (el) {
                gsap.to(cursorRing, {
                    width: 40,
                    height: 40,
                    backgroundColor: 'transparent',
                    borderColor: 'rgba(217, 119, 6, 0.6)',
                    duration: 0.3,
                    ease: "power2.out"
                });
                if (cursorText && cursorText.firstChild) {
                    gsap.killTweensOf(cursorText.firstChild);
                }
                if(cursorText) cursorText.innerHTML = "";
                gsap.to(cursorDot, { opacity: 1, duration: 0.2 });
            }
        });
    }
});
