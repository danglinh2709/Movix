/**
 * ============================================================
 * TV Shows Archive Page - Premium OTT JavaScript
 * Netflix/HBO Max/Prime Video Quality
 * ============================================================
 */

(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    initTVShows();
  });

  function initTVShows() {
    initHeroSlider();
    initSwipers();
    initGenreFilter();
    initCardActions();
    initInfiniteScroll();
  }

  // ============================================================
  // HERO SLIDER
  // ============================================================
  function initHeroSlider() {
    const slider = document.querySelector('.tv-hero__slider');
    if (!slider) return;

    const slides = slider.querySelectorAll('.tv-hero__slide');
    const dots = document.querySelectorAll('.tv-hero__dot');
    let currentSlide = 0;
    let autoplayInterval;

    if (slides.length <= 1) return;

    function goToSlide(index) {
      slides.forEach((slide, i) => {
        slide.classList.toggle('is-active', i === index);
      });
      dots.forEach((dot, i) => {
        dot.classList.toggle('is-active', i === index);
      });
      currentSlide = index;
    }

    function nextSlide() {
      const next = (currentSlide + 1) % slides.length;
      goToSlide(next);
    }

    function prevSlide() {
      const prev = (currentSlide - 1 + slides.length) % slides.length;
      goToSlide(prev);
    }

    // Auto-rotate every 8 seconds
    function startAutoplay() {
      autoplayInterval = setInterval(nextSlide, 8000);
    }

    function stopAutoplay() {
      clearInterval(autoplayInterval);
    }

    // Dot clicks
    dots.forEach((dot, index) => {
      dot.addEventListener('click', function() {
        stopAutoplay();
        goToSlide(index);
        startAutoplay();
      });
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
      if (!slider.closest('.tv-shows-page')) return;
      if (e.key === 'ArrowRight') {
        stopAutoplay();
        nextSlide();
        startAutoplay();
      } else if (e.key === 'ArrowLeft') {
        stopAutoplay();
        prevSlide();
        startAutoplay();
      }
    });

    // Start autoplay
    startAutoplay();

    // Pause on hover
    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);
  }

  // ============================================================
  // SWIPER CAROUSELS
  // ============================================================
  function initSwipers() {
    const swipers = document.querySelectorAll('.tv-swiper');
    
    swipers.forEach(function(container) {
      const wrapper = container.querySelector('.tv-swiper__wrapper');
      const prevBtn = container.querySelector('.tv-swiper__nav--prev');
      const nextBtn = container.querySelector('.tv-swiper__nav--next');
      const cards = wrapper.querySelectorAll('.tv-card');
      
      if (!wrapper || cards.length === 0) return;

      let scrollAmount = 0;
      const cardWidth = cards[0].offsetWidth + 16; // card width + gap

      // Calculate visible cards
      function getVisibleCards() {
        const containerWidth = container.offsetWidth;
        const padding = 48 * 2; // left + right padding
        return Math.floor((containerWidth - padding) / cardWidth);
      }

      // Scroll functions
      function scrollNext() {
        const visible = getVisibleCards();
        const maxScroll = wrapper.scrollWidth - container.offsetWidth;
        scrollAmount = Math.min(scrollAmount + cardWidth * visible, maxScroll);
        wrapper.style.transform = `translateX(-${scrollAmount}px)`;
        updateButtons();
      }

      function scrollPrev() {
        const visible = getVisibleCards();
        scrollAmount = Math.max(scrollAmount - cardWidth * visible, 0);
        wrapper.style.transform = `translateX(-${scrollAmount}px)`;
        updateButtons();
      }

      function updateButtons() {
        const maxScroll = wrapper.scrollWidth - container.offsetWidth;
        if (prevBtn) {
          prevBtn.style.opacity = scrollAmount > 10 ? '1' : '0';
          prevBtn.style.pointerEvents = scrollAmount > 10 ? 'auto' : 'none';
        }
        if (nextBtn) {
          nextBtn.style.opacity = scrollAmount < maxScroll - 10 ? '1' : '0';
          nextBtn.style.pointerEvents = scrollAmount < maxScroll - 10 ? 'auto' : 'none';
        }
      }

      // Button events
      if (nextBtn) {
        nextBtn.addEventListener('click', scrollNext);
      }
      if (prevBtn) {
        prevBtn.style.opacity = '0';
        prevBtn.addEventListener('click', scrollPrev);
      }

      // Initial state
      updateButtons();

      // Touch/drag support
      let isDown = false;
      let startX;
      let scrollLeft;

      wrapper.addEventListener('mousedown', function(e) {
        isDown = true;
        wrapper.style.cursor = 'grabbing';
        startX = e.pageX - wrapper.offsetLeft;
        scrollLeft = scrollAmount;
      });

      wrapper.addEventListener('mouseleave', function() {
        isDown = false;
        wrapper.style.cursor = 'grab';
      });

      wrapper.addEventListener('mouseup', function() {
        isDown = false;
        wrapper.style.cursor = 'grab';
      });

      wrapper.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - wrapper.offsetLeft;
        const walk = (x - startX) * 1.5;
        const maxScroll = wrapper.scrollWidth - container.offsetWidth;
        scrollAmount = Math.max(0, Math.min(scrollLeft - walk, maxScroll));
        wrapper.style.transform = `translateX(-${scrollAmount}px)`;
        updateButtons();
      });

      // Set cursor style
      wrapper.style.cursor = 'grab';

      // Handle resize
      let resizeTimeout;
      window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(updateButtons, 100);
      });
    });
  }

  // ============================================================
  // GENRE FILTER
  // ============================================================
  function initGenreFilter() {
    const chips = document.querySelectorAll('.tv-genre-chip');
    const tvSections = document.querySelectorAll('.tv-section[data-genre]');
    
    chips.forEach(function(chip) {
      chip.addEventListener('click', function() {
        const genre = this.dataset.genre || '';
        
        // Update active state
        chips.forEach(function(c) {
          c.classList.toggle('is-active', c === this);
        }, this);

        // Filter sections
        tvSections.forEach(function(section) {
          if (!genre || section.dataset.genre === genre || section.dataset.genre === 'all') {
            section.style.display = '';
          } else {
            section.style.display = 'none';
          }
        });

        // Smooth scroll to content
        const content = document.querySelector('.tv-content');
        if (content) {
          content.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  // ============================================================
  // CARD ACTIONS
  // ============================================================
  function initCardActions() {
    // Play button - opens watch page
    document.addEventListener('click', function(e) {
      const playBtn = e.target.closest('[data-action="play"]');
      if (playBtn) {
        e.preventDefault();
        e.stopPropagation();
        const href = playBtn.dataset.href;
        if (href) {
          window.location.href = href;
        }
        return;
      }

      const favBtn = e.target.closest('[data-action="favorites"]');
      if (favBtn) {
        e.preventDefault();
        e.stopPropagation();
        const card = favBtn.closest('.tv-card');
        const id = card ? card.dataset.id : null;
        if (id) {
          toggleFavorite(id, favBtn);
        }
        return;
      }

      const listBtn = e.target.closest('[data-action="add-list"]');
      if (listBtn) {
        e.preventDefault();
        e.stopPropagation();
        const card = listBtn.closest('.tv-card');
        const id = card ? card.dataset.id : null;
        if (id) {
          toggleMyList(id, listBtn);
        }
      }
    });

    // Card click - opens detail page
    document.addEventListener('click', function(e) {
      const card = e.target.closest('.tv-card');
      if (card && !e.target.closest('[data-action]')) {
        const href = card.dataset.href;
        if (href) {
          window.location.href = href;
        }
      }
    });
  }

  function toggleFavorite(id, btn) {
    const key = 'mu_fav_' + id;
    const isFav = localStorage.getItem(key) === 'true';
    localStorage.setItem(key, !isFav ? 'true' : 'false');
    
    const icon = btn.querySelector('svg');
    if (icon) {
      icon.setAttribute('fill', !isFav ? 'currentColor' : 'none');
    }
    
    showToast(!isFav ? 'Added to Favorites' : 'Removed from Favorites');
  }

  function toggleMyList(id, btn) {
    const key = 'mu_list_' + id;
    const isAdded = localStorage.getItem(key) === 'true';
    localStorage.setItem(key, !isAdded ? 'true' : 'false');
    
    btn.classList.toggle('is-active', !isAdded);
    
    showToast(!isAdded ? 'Added to My List' : 'Removed from My List');
  }

  // ============================================================
  // INFINITE SCROLL / LOAD MORE
  // ============================================================
  function initInfiniteScroll() {
    const loadMoreBtn = document.querySelector('[data-action="load-more"]');
    if (!loadMoreBtn) return;

    loadMoreBtn.addEventListener('click', function() {
      const page = parseInt(this.dataset.page) || 1;
      const nextPage = page + 1;
      const genre = this.dataset.genre || '';
      const container = document.querySelector('.tv-grid');

      if (!container) return;

      // Show loading
      this.innerHTML = '<span class="tv-loading">Loading...</span>';
      this.disabled = true;

      // AJAX load
      fetchTVShows(nextPage, genre, container, this);
    });
  }

  function fetchTVShows(page, genre, container, btn) {
    const nonce = document.querySelector('meta[name="tvnonce"]')?.content || '';
    
    fetch(ajaxurl + '?action=mu_load_tv_shows&page=' + page + '&genre=' + encodeURIComponent(genre) + '&nonce=' + nonce, {
      headers: {
        'Accept': 'application/json'
      }
    })
    .then(function(response) {
      return response.json();
    })
    .then(function(data) {
      if (data.html) {
        container.insertAdjacentHTML('beforeend', data.html);
        btn.dataset.page = page;
        btn.innerHTML = 'Load More';
        btn.disabled = false;
        
        // Re-init swipers for new cards
        initSwipers();
      } else {
        btn.style.display = 'none';
      }
    })
    .catch(function(error) {
      console.error('Error loading TV shows:', error);
      btn.innerHTML = 'Load More';
      btn.disabled = false;
    });
  }

  // ============================================================
  // TOAST NOTIFICATION
  // ============================================================
  function showToast(message) {
    let toast = document.querySelector('.tv-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'tv-toast';
      toast.innerHTML = '<span></span>';
      toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);padding:12px 24px;background:rgba(0,0,0,0.9);color:white;border-radius:8px;font-size:14px;font-weight:500;z-index:99999;opacity:0;transition:all 0.3s ease;pointer-events:none;';
      document.body.appendChild(toast);
    }
    
    toast.querySelector('span').textContent = message;
    toast.style.transform = 'translateX(-50%) translateY(0)';
    toast.style.opacity = '1';
    
    setTimeout(function() {
      toast.style.transform = 'translateX(-50%) translateY(100px)';
      toast.style.opacity = '0';
    }, 2500);
  }

  // ============================================================
  // LAZY LOADING
  // ============================================================
  function initLazyLoad() {
    const images = document.querySelectorAll('img[data-src]');
    
    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          observer.unobserve(img);
        }
      });
    }, {
      rootMargin: '100px'
    });

    images.forEach(function(img) {
      observer.observe(img);
    });
  }

  // ============================================================
  // SKELETON LOADING
  // ============================================================
  function showSkeletons(container, count) {
    let html = '';
    for (let i = 0; i < count; i++) {
      html += '<div class="tv-skeleton tv-skeleton--card"></div>';
    }
    container.insertAdjacentHTML('beforeend', html);
  }

  function hideSkeletons(container) {
    container.querySelectorAll('.tv-skeleton').forEach(function(el) {
      el.remove();
    });
  }

})();
