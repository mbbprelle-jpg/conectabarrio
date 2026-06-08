(function () {
    'use strict';

    var deck = document.getElementById('presDeck');
    if (!deck) return;

    var track = deck.querySelector('.pres-track');
    var slides = deck.querySelectorAll('.pres-slide');
    var btnPrev = document.getElementById('presPrev');
    var btnNext = document.getElementById('presNext');
    var dotsContainer = document.getElementById('presDots');
    var counter = document.getElementById('presCounter');
    var progress = document.getElementById('presProgress');
    var btnFullscreen = document.getElementById('presFullscreen');
    var total = slides.length;
    var current = 0;
    var touchStartX = 0;

    function buildDots() {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = '';
        for (var i = 0; i < total; i++) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'pres-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('aria-label', 'Ir a lámina ' + (i + 1));
            dot.dataset.index = String(i);
            dot.addEventListener('click', function () {
                goTo(parseInt(this.dataset.index, 10));
            });
            dotsContainer.appendChild(dot);
        }
    }

    function goTo(index) {
        if (index < 0 || index >= total) return;
        current = index;
        track.style.transform = 'translateX(-' + (current * 100) + '%)';

        if (counter) {
            counter.textContent = (current + 1) + ' / ' + total;
        }
        if (progress) {
            progress.style.width = (((current + 1) / total) * 100) + '%';
        }
        if (btnPrev) btnPrev.disabled = current === 0;
        if (btnNext) btnNext.disabled = current === total - 1;

        var dots = dotsContainer ? dotsContainer.querySelectorAll('.pres-dot') : [];
        dots.forEach(function (dot, i) {
            dot.classList.toggle('active', i === current);
        });

        slides.forEach(function (slide, i) {
            slide.setAttribute('aria-hidden', i === current ? 'false' : 'true');
        });
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    if (btnPrev) btnPrev.addEventListener('click', prev);
    if (btnNext) btnNext.addEventListener('click', next);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') {
            e.preventDefault();
            next();
        } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
            e.preventDefault();
            prev();
        } else if (e.key === 'Home') {
            e.preventDefault();
            goTo(0);
        } else if (e.key === 'End') {
            e.preventDefault();
            goTo(total - 1);
        }
    });

    deck.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    deck.addEventListener('touchend', function (e) {
        var diff = e.changedTouches[0].screenX - touchStartX;
        if (Math.abs(diff) < 50) return;
        if (diff < 0) next();
        else prev();
    }, { passive: true });

    if (btnFullscreen) {
        btnFullscreen.addEventListener('click', function () {
            var el = document.documentElement;
            if (!document.fullscreenElement) {
                if (el.requestFullscreen) el.requestFullscreen();
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        });
    }

    buildDots();
    goTo(0);
})();
