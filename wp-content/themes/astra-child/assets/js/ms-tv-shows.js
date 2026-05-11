/**
 * TV Shows Page JavaScript
 * Hero slider, carousels, genre filtering, interactions
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initHeroSlider();
        initSliders();
        initGenreFilter();
        initHeaderScroll();
    });

    // ============================================================
    // HERO SLIDER
    // ============================================================
    function initHeroSlider() {
        var hero = document.getElementById('tvHero');
        if (!hero) return;

        var slides = hero.querySelectorAll('.tv-hero__slide');
        var dots = hero.querySelectorAll('.tv-hero__dot');
        var current = 0;
        var autoplay = setInterval(function() {
            goTo((current + 1) % slides.length);
        }, 6000);

        function goTo(idx) {
            if (slides.length <= 1) return;
            idx = ((idx % slides.length) + slides.length) % slides.length;
            
            if (slides[current]) slides[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');
            
            current = idx;
            
            if (slides[current]) slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');
        }

        dots.forEach(function(dot, i) {
            dot.addEventListener('click', function() {
                clearInterval(autoplay);
                goTo(i);
                autoplay = setInterval(function() {
                    goTo((current + 1) % slides.length);
                }, 6000);
            });
        });

        // Touch swipe for hero
        var startX = 0;
        hero.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        }, { passive: true });

        hero.addEventListener('touchend', function(e) {
            var diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) {
                clearInterval(autoplay);
                if (diff > 0) {
                    goTo(current + 1);
                } else {
                    goTo(current - 1);
                }
                autoplay = setInterval(function() {
                    goTo((current + 1) % slides.length);
                }, 6000);
            }
        }, { passive: true });
    }

    // ============================================================
    // SLIDERS / CAROUSELS
    // ============================================================
    function initSliders() {
        document.querySelectorAll('.tv-slider').forEach(function(slider) {
            var track = slider.querySelector('.tv-slider__track');
            var prevBtn = slider.querySelector('.tv-slider__btn--prev');
            var nextBtn = slider.querySelector('.tv-slider__btn--next');
            
            if (!track || !prevBtn || !nextBtn) return;

            function getCardWidth() {
                var card = track.querySelector('.mu-card, .tv-ep-card');
                return card ? card.offsetWidth + 14 : 214;
            }

            function updateArrows() {
                var maxScroll = track.scrollWidth - track.clientWidth;
                if (prevBtn) {
                    prevBtn.style.opacity = track.scrollLeft > 10 ? '1' : '0';
                    prevBtn.style.pointerEvents = track.scrollLeft > 10 ? 'auto' : 'none';
                }
                if (nextBtn) {
                    nextBtn.style.opacity = track.scrollLeft < maxScroll - 10 ? '1' : '0';
                    nextBtn.style.pointerEvents = track.scrollLeft < maxScroll - 10 ? 'auto' : 'none';
                }
            }

            updateArrows();

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    track.scrollBy({ left: -getCardWidth() * 3, behavior: 'smooth' });
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    track.scrollBy({ left: getCardWidth() * 3, behavior: 'smooth' });
                });
            }

            track.addEventListener('scroll', updateArrows);
            window.addEventListener('resize', updateArrows);

            // Drag support
            var isDown = false;
            var startX = 0;
            var scrollLeft = 0;

            track.addEventListener('mousedown', function(e) {
                isDown = true;
                track.style.cursor = 'grabbing';
                startX = e.pageX - track.offsetLeft;
                scrollLeft = track.scrollLeft;
            });

            track.addEventListener('mouseleave', function() {
                isDown = false;
                track.style.cursor = 'grab';
            });

            track.addEventListener('mouseup', function() {
                isDown = false;
                track.style.cursor = 'grab';
            });

            track.addEventListener('mousemove', function(e) {
                if (!isDown) return;
                e.preventDefault();
                var x = e.pageX - track.offsetLeft;
                var walk = (x - startX) * 2;
                track.scrollLeft = scrollLeft - walk;
            });
        });
    }

    // ============================================================
    // GENRE FILTER
    // ============================================================
    function initGenreFilter() {
        var genres = document.querySelectorAll('.tv-genre');
        var sections = document.querySelectorAll('.tv-section[data-genre]');

        genres.forEach(function(chip) {
            chip.addEventListener('click', function() {
                // Update active state
                genres.forEach(function(g) { g.classList.remove('active'); });
                this.classList.add('active');

                var genre = this.dataset.genre;
                
                // Update URL
                var url = new URL(window.location);
                if (genre) {
                    url.searchParams.set('genre', genre);
                } else {
                    url.searchParams.delete('genre');
                }
                window.history.pushState({}, '', url);

                // Show toast
                if (genre) {
                    showToast('Showing: ' + this.textContent);
                }
            });
        });
    }

    // ============================================================
    // HEADER SCROLL EFFECT
    // ============================================================
    function initHeaderScroll() {
        var header = document.querySelector('.mu-header');
        if (!header) return;

        var lastScroll = 0;
        
        window.addEventListener('scroll', function() {
            var currentScroll = window.pageYOffset;
            
            // Add/remove scrolled class
            if (currentScroll > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        }, { passive: true });
    }

    // ============================================================
    // TOAST NOTIFICATION
    // ============================================================
    function showToast(message) {
        var toast = document.getElementById('tvToast');
        if (!toast) return;
        
        toast.textContent = message;
        toast.classList.add('show');
        
        setTimeout(function() {
            toast.classList.remove('show');
        }, 2500);
    }

    // Expose showToast globally for inline use
    window.tvShowToast = showToast;

})();
