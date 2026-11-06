document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-coverflow]').forEach(initCoverflow);
});

function initCoverflow(root) {
    const stage = root.querySelector('[data-coverflow-stage]');
    const slides = Array.from(root.querySelectorAll('[data-coverflow-slide]'));
    const dotsContainer = root.querySelector('[data-coverflow-dots]');
    const counter = root.querySelector('[data-coverflow-count]');
    const prevButton = root.querySelector('[data-coverflow-prev]');
    const nextButton = root.querySelector('[data-coverflow-next]');

    if (!stage || slides.length === 0) return;

    let current = slides.findIndex((slide) => slide.classList.contains('is-active'));
    if (current < 0) current = 0;

    let dragging = false;
    let moved = false;
    let startX = 0;
    let startY = 0;
    let autoTimer = null;
    let paused = false;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    slides.forEach((slide, index) => {
        if (!dotsContainer) return;

        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'coverflow-dot';
        dot.setAttribute('aria-label', `Show ${slide.dataset.name || 'slide ' + (index + 1)}`);
        dot.addEventListener('click', () => {
            goTo(index);
            restartAuto();
        });
        dotsContainer.appendChild(dot);
    });

    const dots = Array.from(dotsContainer?.querySelectorAll('.coverflow-dot') || []);

    function layout() {
        const width = stage.clientWidth;
        const distance = width < 576 ? 150 : width < 768 ? 170 : width < 992 ? 205 : 255;
        const length = slides.length;

        slides.forEach((slide, index) => {
            let offset = index - current;

            if (offset > length / 2) offset -= length;
            if (offset < -length / 2) offset += length;

            const absolute = Math.abs(offset);
            const translateX = offset * distance;
            const translateZ = -absolute * 145;
            const rotateY = offset * -34;
            const scale = Math.max(0.7, 1 - absolute * 0.11);
            const opacity = absolute > 2 ? 0 : 1 - absolute * 0.22;
            const active = offset === 0;

            slide.style.transform = `translate(-50%, -50%) translateX(${translateX}px) translateZ(${translateZ}px) rotateY(${rotateY}deg) scale(${scale})`;
            slide.style.opacity = opacity.toFixed(2);
            slide.style.zIndex = String(100 - absolute);
            slide.style.pointerEvents = absolute > 2 ? 'none' : 'auto';
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            slide.tabIndex = active ? 0 : -1;
        });

        dots.forEach((dot, index) => {
            dot.classList.toggle('is-active', index === current);
            dot.setAttribute('aria-current', index === current ? 'true' : 'false');
        });

        if (counter) {
            counter.textContent = String(current + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
        }
    }

    function goTo(index) {
        current = (index + slides.length) % slides.length;
        layout();
    }

    function next() {
        goTo(current + 1);
    }

    function previous() {
        goTo(current - 1);
    }

    function stopAuto() {
        if (autoTimer) {
            window.clearInterval(autoTimer);
            autoTimer = null;
        }
    }

    function startAuto() {
        if (prefersReducedMotion || paused || slides.length < 2) return;

        stopAuto();
        autoTimer = window.setInterval(() => {
            if (!document.hidden && !paused) next();
        }, 7000);
    }

    function restartAuto() {
        stopAuto();
        startAuto();
    }

    prevButton?.addEventListener('click', () => {
        previous();
        restartAuto();
    });

    nextButton?.addEventListener('click', () => {
        next();
        restartAuto();
    });

    slides.forEach((slide, index) => {
        slide.addEventListener('click', (event) => {
            if (moved) {
                event.preventDefault();
                moved = false;
                return;
            }

            if (index !== current) {
                event.preventDefault();
                goTo(index);
                restartAuto();
            }
        });
    });

    stage.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 && event.pointerType === 'mouse') return;

        dragging = true;
        moved = false;
        startX = event.clientX;
        startY = event.clientY;
        paused = true;
        stopAuto();
        stage.classList.add('is-dragging');
    });

    window.addEventListener('pointermove', (event) => {
        if (!dragging) return;

        const deltaX = event.clientX - startX;
        const deltaY = event.clientY - startY;

        if (Math.abs(deltaX) > 8 || Math.abs(deltaY) > 8) {
            moved = true;
        }
    }, { passive: true });

    window.addEventListener('pointerup', (event) => {
        if (!dragging) return;

        const deltaX = event.clientX - startX;
        const deltaY = event.clientY - startY;
        const horizontal = Math.abs(deltaX) > Math.abs(deltaY);

        dragging = false;
        stage.classList.remove('is-dragging');

        if (horizontal && Math.abs(deltaX) > 48) {
            if (deltaX < 0) {
                next();
            } else {
                previous();
            }
        }

        paused = false;
        restartAuto();
    });

    window.addEventListener('pointercancel', () => {
        dragging = false;
        paused = false;
        stage.classList.remove('is-dragging');
        startAuto();
    });

    stage.addEventListener('mouseenter', () => {
        paused = true;
        stopAuto();
    });

    stage.addEventListener('mouseleave', () => {
        paused = false;
        startAuto();
    });

    root.addEventListener('focusin', () => {
        paused = true;
        stopAuto();
    });

    root.addEventListener('focusout', (event) => {
        if (!root.contains(event.relatedTarget)) {
            paused = false;
            startAuto();
        }
    });

    root.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            previous();
            restartAuto();
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            next();
            restartAuto();
        }
    });

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(layout, 120);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAuto();
        } else {
            startAuto();
        }
    });

    layout();
    startAuto();
}
