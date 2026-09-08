/* js/policy.js */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Initialize Lenis for Smooth Scrolling
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        smooth: true,
        smoothTouch: false
    });

    // 2. Hook Lenis into GSAP ScrollTrigger
    lenis.on('scroll', ScrollTrigger.update);

    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });

    gsap.ticker.lagSmoothing(0);

    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // 3. 3D Scroll Animations for Policy Cards
    gsap.registerPlugin(ScrollTrigger);

    const cards = document.querySelectorAll('.policy-card');

    cards.forEach((card, index) => {
        // Initial state for 3D effect (pushed back and tilted down)
        gsap.set(card, {
            opacity: 0,
            y: 150,
            z: -300,
            rotateX: -20,
            scale: 0.85
        });

        // Animate to normal state as it enters viewport
        gsap.to(card, {
            scrollTrigger: {
                trigger: card,
                start: 'top 95%',    // Starts when top of card hits 95% of viewport
                end: 'top 40%',      // Reaches full state when top of card hits 40%
                scrub: 1.5,          // Smooth scrubbing linked to scroll speed
                toggleActions: 'play none none reverse'
            },
            opacity: 1,
            y: 0,
            z: 0,
            rotateX: 0,
            scale: 1,
            ease: 'power3.out'
        });
    });

    // 4. Subtle parallax for Hero Section
    gsap.to('.policy-hero', {
        scrollTrigger: {
            trigger: '.policy-hero',
            start: 'top top',
            end: 'bottom top',
            scrub: true
        },
        yPercent: 40,
        opacity: 0,
        scale: 0.95,
        ease: 'none'
    });

    // 5. Sakura Falling Effect
    const sakuraCanvas = document.getElementById('sakura-canvas');
    if (sakuraCanvas) {
        const ctx = sakuraCanvas.getContext('2d');
        let sakuras = [];
        
        function resizeSakuraCanvas() {
            sakuraCanvas.width = window.innerWidth;
            sakuraCanvas.height = window.innerHeight;
        }
        resizeSakuraCanvas();
        window.addEventListener('resize', resizeSakuraCanvas);

        class Sakura {
            constructor() {
                this.reset();
            }
            reset() {
                this.x = Math.random() * sakuraCanvas.width;
                this.y = Math.random() * sakuraCanvas.height - sakuraCanvas.height;
                this.size = Math.random() * 8 + 5; // Petal size (larger)
                this.speedY = Math.random() * 0.7 + 0.3; // Fall much slower
                this.speedX = Math.random() * 1 - 0.5; // Drift much slower
                this.opacity = Math.random() * 0.5 + 0.5; // More opaque
                this.angle = Math.random() * 360;
                this.spin = Math.random() * 0.04 - 0.02; // Spin slower
            }
            update() {
                this.y += this.speedY;
                this.x += this.speedX + Math.sin(this.y * 0.01) * 0.5; // Fluttering effect
                this.angle += this.spin;
                
                if (this.y > sakuraCanvas.height + 50 || this.x < -50 || this.x > sakuraCanvas.width + 50) {
                    this.y = -50;
                    this.x = Math.random() * sakuraCanvas.width;
                }
            }
            draw() {
                ctx.save();
                ctx.translate(this.x, this.y);
                ctx.rotate(this.angle);
                
                // Draw a simple petal shape using quadratic curves
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.quadraticCurveTo(this.size, -this.size, this.size * 2, 0);
                ctx.quadraticCurveTo(this.size, this.size, 0, 0);
                
                // Sakura pink color
                ctx.fillStyle = `rgba(255, 183, 197, ${this.opacity})`;
                ctx.fill();
                
                ctx.restore();
            }
        }

        for (let i = 0; i < 50; i++) {
            sakuras.push(new Sakura());
        }

        function animateSakura() {
            ctx.clearRect(0, 0, sakuraCanvas.width, sakuraCanvas.height);
            sakuras.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animateSakura);
        }
        animateSakura();
    }
});
