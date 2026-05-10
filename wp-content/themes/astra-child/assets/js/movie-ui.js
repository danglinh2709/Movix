/**
 * ============================================================
 * Premium Streaming UI JavaScript
 * Handles: Hero slider, carousels, trailer modal, feature modals,
 *          search overlay, notifications, profile dropdown,
 *          favorites, progress bars, all card interactions.
 * ============================================================
 */
(function () {
  'use strict';

  var $  = function (sel, root) { root = root || document; return root.querySelector(sel); };
  var $$ = function (sel, root) { root = root || document; return Array.prototype.slice.call(root.querySelectorAll(sel)); };

  /* ----------------------------------------------------------------
     UTILITIES
  ---------------------------------------------------------------- */
  function debounce(fn, wait) {
    var t;
    return function () {
      var args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(null, args); }, wait);
    };
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  var store = {
    get: function (key, fallback) {
      try {
        var raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
      } catch (e) { return fallback; }
    },
    set: function (key, val) {
      try { localStorage.setItem(key, JSON.stringify(val)); } catch (e) { /* ignore */ }
    }
  };

  var KEY_FAVS       = 'mu_favorites';
  var KEY_FAVS_SYNCED = 'mu_favorites_synced_v1';
  var KEY_PROGRESS   = 'mu_progress';

  /* ================================================================
     HEADER: Sticky transparent -> solid on scroll
  ================================================================ */
  (function initHeader() {
    var header = document.querySelector('[data-mu-header]');
    if (!header) return;
    var onScroll = function () {
      if (window.scrollY > 20) {
        header.classList.add('is-solid');
      } else {
        header.classList.remove('is-solid');
      }
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }());

  /* ================================================================
     MOBILE DRAWER
  ================================================================ */
  (function initDrawer() {
    var drawer = document.querySelector('[data-mu-drawer]');
    var openBtns = $$('[data-mu-open-drawer]');
    var closeBtns = $$('[data-mu-close-drawer]');

    function setDrawer(open) {
      if (!drawer) return;
      if (open) {
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
        var panel = drawer.querySelector('.mu-drawer__panel');
        if (panel && panel.focus) panel.focus();
      } else {
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.documentElement.style.overflow = '';
      }
    }

    for (var i = 0; i < openBtns.length; i++) {
      openBtns[i].addEventListener('click', function () { setDrawer(true); });
    }
    for (var j = 0; j < closeBtns.length; j++) {
      closeBtns[j].addEventListener('click', function () { setDrawer(false); });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setDrawer(false);
    });
  }());

  /* ================================================================
     PROFILE DROPDOWN
  ================================================================ */
  (function initProfileDropdown() {
    var userMenu = document.querySelector('[data-mu-user-menu]');
    var avatarBtn = document.querySelector('[data-mu-open-profile]');
    var dropdown = document.querySelector('[data-mu-user-dropdown]');

    if (!avatarBtn || !dropdown) return;

    function openDropdown() {
      dropdown.hidden = false;
      avatarBtn.setAttribute('aria-expanded', 'true');
      document.addEventListener('click', handleOutsideClick);
      document.addEventListener('keydown', handleEscKey);
    }

    function closeDropdown() {
      dropdown.hidden = true;
      avatarBtn.setAttribute('aria-expanded', 'false');
      document.removeEventListener('click', handleOutsideClick);
      document.removeEventListener('keydown', handleEscKey);
    }

    function handleOutsideClick(e) {
      if (!userMenu || !userMenu.contains(e.target)) closeDropdown();
    }
    function handleEscKey(e) { if (e.key === 'Escape') closeDropdown(); }

    avatarBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = avatarBtn.getAttribute('aria-expanded') === 'true';
      if (isOpen) closeDropdown(); else openDropdown();
    });
  }());

  /* ================================================================
     NOTIFICATIONS DROPDOWN
  ================================================================ */
  (function initNotifications() {
    var bellBtn = document.querySelector('[data-mu-open-notifications]');
    var dropdown = document.querySelector('[data-mu-notifications-dropdown]');
    var backdrop = document.querySelector('.mu-notifications-backdrop');
    var closeBtns = $$('[data-mu-close-notifications]');

    if (!bellBtn || !dropdown) return;

    function openNotifications() {
      dropdown.hidden = false;
      if (backdrop) backdrop.hidden = false;
      bellBtn.setAttribute('aria-expanded', 'true');
      document.addEventListener('click', handleOutsideClick);
      document.addEventListener('keydown', handleEscKey);
    }

    function closeNotifications() {
      dropdown.hidden = true;
      if (backdrop) backdrop.hidden = true;
      bellBtn.setAttribute('aria-expanded', 'false');
      document.removeEventListener('click', handleOutsideClick);
      document.removeEventListener('keydown', handleEscKey);
    }

    function handleOutsideClick(e) {
      if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) closeNotifications();
    }
    function handleEscKey(e) { if (e.key === 'Escape') closeNotifications(); }

    bellBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = bellBtn.getAttribute('aria-expanded') === 'true';
      if (isOpen) closeNotifications(); else openNotifications();
    });

    for (var k = 0; k < closeBtns.length; k++) {
      closeBtns[k].addEventListener('click', closeNotifications);
    }
  }());

  /* ================================================================
     SEARCH OVERLAY (Ctrl/Cmd+K shortcut)
  ================================================================ */
  (function initSearch() {
    var overlay = document.querySelector('[data-mu-search]');
    var openBtns = $$('[data-mu-open-search]');
    var closeBtns = $$('[data-mu-close-search]');
    var input = document.querySelector('[data-mu-search-input]');
    var results = document.querySelector('[data-mu-search-results]');
    var skel = document.querySelector('[data-mu-skel]');
    var suggestWrap = document.querySelector('[data-mu-suggest]');

    if (!overlay) return;

    function openSearch() {
      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      document.documentElement.style.overflow = 'hidden';
      if (input) setTimeout(function () { input.focus(); }, 50);
    }

    function closeSearch() {
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
      document.documentElement.style.overflow = '';
      if (input) input.value = '';
    }

    for (var mi = 0; mi < openBtns.length; mi++) {
      openBtns[mi].addEventListener('click', openSearch);
    }
    for (var mj = 0; mj < closeBtns.length; mj++) {
      closeBtns[mj].addEventListener('click', closeSearch);
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closeSearch(); closeAllDropdowns(); }
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        openSearch();
      }
    });

    /* AJAX search */
    function runSearch(q) {
      if (!results) return;
      if (skel) skel.style.display = 'grid';
      results.innerHTML = '';

      var fd = new FormData();
      fd.append('action', 'movie_ui_search');
      fd.append('nonce', (window.MOVIE_UI && window.MOVIE_UI.nonce) || '');
      fd.append('q', q);

      var ajaxUrl = (window.MOVIE_UI && window.MOVIE_UI.ajaxUrl) || '/wp-admin/admin-ajax.php';
      var xhr = new XMLHttpRequest();
      xhr.open('POST', ajaxUrl, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          if (skel) skel.style.display = 'none';
          if (xhr.status === 200) {
            try {
              var data = JSON.parse(xhr.responseText);
              if (data && data.success && data.data) {
                results.innerHTML = data.data.html || '';
                if (suggestWrap && Array.isArray(data.data.suggestions)) {
                  var chips = data.data.suggestions.map(function (s) {
                    return '<a class="mu-chip" href="' + escapeHtml(s.url) + '">' + escapeHtml(s.title) + '</a>';
                  }).join('');
                  suggestWrap.innerHTML = chips;
                }
                initSwipers();
                syncFavButtons();
                renderProgressBars();
              }
            } catch (ex) {
              results.innerHTML = '<div class="mu-empty">Search failed. Try again.</div>';
            }
          } else {
            results.innerHTML = '<div class="mu-empty">Search failed. Try again.</div>';
          }
        }
      };
      xhr.send(fd);
    }

    if (input) {
      var debouncedSearch = debounce(function () {
        var q = (input.value || '').trim();
        runSearch(q.length >= 2 ? q : '');
      }, 280);
      input.addEventListener('input', debouncedSearch);
      input.addEventListener('focus', function () {
        var q = (input.value || '').trim();
        runSearch(q.length >= 2 ? q : '');
      });
    }
  }());

  /* ================================================================
     HERO SLIDER
  ================================================================ */
  (function initHeroSlider() {
    var shell = document.querySelector('[data-mu-hero-shell]');
    if (!shell) return;

    var slides = $$('[data-mu-hero-slide]');
    var dots = $$('[data-mu-hero-dot]');
    var prevBtn = document.querySelector('[data-mu-hero-prev]');
    var nextBtn = document.querySelector('[data-mu-hero-next]');

    if (slides.length <= 1) {
      if (prevBtn) prevBtn.style.display = 'none';
      if (nextBtn) nextBtn.style.display = 'none';
      return;
    }

    var current = 0;
    var autoplayTimer = null;
    var AUTOPLAY_DELAY = 5000;

    function goTo(idx) {
      for (var si = 0; si < slides.length; si++) {
        if (si === idx) slides[si].classList.add('is-active');
        else slides[si].classList.remove('is-active');
      }
      for (var di = 0; di < dots.length; di++) {
        if (di === idx) {
          dots[di].classList.add('is-active');
          dots[di].setAttribute('aria-selected', 'true');
        } else {
          dots[di].classList.remove('is-active');
          dots[di].setAttribute('aria-selected', 'false');
        }
      }
      current = idx;
    }

    function nextSlide() { goTo((current + 1) % slides.length); }
    function prevSlide() { goTo((current - 1 + slides.length) % slides.length); }

    function startAutoplay() {
      stopAutoplay();
      autoplayTimer = setInterval(nextSlide, AUTOPLAY_DELAY);
    }
    function stopAutoplay() { if (autoplayTimer) clearInterval(autoplayTimer); }

    for (var dii = 0; dii < dots.length; dii++) {
      (function (i) {
        dots[i].addEventListener('click', function () { goTo(i); startAutoplay(); });
      }(dii));
    }
    if (prevBtn) prevBtn.addEventListener('click', function () { prevSlide(); startAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { nextSlide(); startAutoplay(); });

    /* Pause on hover */
    shell.addEventListener('mouseenter', stopAutoplay);
    shell.addEventListener('mouseleave', startAutoplay);

    /* Touch swipe */
    var touchStartX = 0;
    shell.addEventListener('touchstart', function (e) { touchStartX = e.touches[0].clientX; }, { passive: true });
    shell.addEventListener('touchend', function (e) {
      var diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) { if (diff > 0) nextSlide(); else prevSlide(); startAutoplay(); }
    });

    startAutoplay();
  }());

  /* ================================================================
     HERO PLAY / INFO / FAV BUTTONS
     IMPORTANT: Play button ALWAYS goes to Watch page (data-watch-url).
     Never fallback to trailer.
  ================================================================ */
  (function initHeroButtons() {
    /* Play Now button */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-mu-hero-play]');
      if (!btn) return;
      e.preventDefault();

      // Watch URL is always available - Play goes to Watch page
      var watchUrl = btn.getAttribute('data-watch-url') || '';

      if (watchUrl) {
        window.location.href = watchUrl;
      } else {
        openUnavailableModal();
      }
    });

    /* More Info button */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-mu-hero-info]');
      if (!btn) return;
      e.preventDefault();
      var url = btn.getAttribute('data-detail-url') || '';
      if (url) window.location.href = url;
    });

    /* Hero Favorite button */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-mu-fav-hero]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      var id = btn.getAttribute('data-id');
      if (!id) return;
      toggleFavorite(id, btn);
    });
  }());

  /* ================================================================
     TRAILER / VIDEO MODAL - UNIFIED HANDLER
  ================================================================ */

  // Make openTrailer globally accessible
  window.openTrailer = function(movieOrUrl) {
    var trailerUrl = '';

    // Support both string URL and movie object
    if (typeof movieOrUrl === 'string') {
      trailerUrl = movieOrUrl;
    } else if (movieOrUrl && movieOrUrl.trailer) {
      trailerUrl = movieOrUrl.trailer;
    } else if (movieOrUrl && movieOrUrl.trailer_url) {
      trailerUrl = movieOrUrl.trailer_url;
    } else if (movieOrUrl && movieOrUrl.youtube_trailer_url) {
      trailerUrl = movieOrUrl.youtube_trailer_url;
    }

    if (!trailerUrl) {
      openUnavailableModal();
      return;
    }

    openTrailerModal(trailerUrl);
  };

  function openTrailerModal(urlOrEmbed) {
    var modal = document.getElementById('mu-trailer-modal');
    if (!modal) {
      // Fallback: create modal dynamically
      createTrailerModal();
      modal = document.getElementById('mu-trailer-modal');
    }

    if (!modal) return;

    var area = document.getElementById('mu-trailer-video-area');
    if (!area) return;

    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-visible');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';

    var embed = toEmbedUrl(urlOrEmbed);

    if (embed) {
      area.innerHTML = '<iframe src="' + embed + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="width:100%;height:100%;border:none;"></iframe>';
    } else {
      area.innerHTML = '<div style="color:rgba(255,255,255,0.7);font-size:16px;font-weight:600;padding:40px;text-align:center;line-height:1.6;"><div style="font-size:48px;margin-bottom:16px;">&#127916;</div>Trailer not available.<br><span style="font-size:13px;font-weight:400;opacity:0.7;">Please check if the video URL is valid.</span></div>';
    }
  }

  function closeTrailerModal() {
    var modal = document.getElementById('mu-trailer-modal');
    if (!modal) return;

    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('is-visible');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';

    var area = document.getElementById('mu-trailer-video-area');
    if (area) {
      area.innerHTML = '';
    }
  }

  // Create trailer modal if it doesn't exist
  function createTrailerModal() {
    if (document.getElementById('mu-trailer-modal')) return;

    var modal = document.createElement('div');
    modal.id = 'mu-trailer-modal';
    modal.className = 'mu-modal mu-trailer-modal';
    modal.setAttribute('aria-hidden', 'true');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML =
      '<button type="button" class="mu-trailer-modal__close" data-mu-close-trailer aria-label="Close">' +
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<line x1="18" y1="6" x2="6" y2="18"></line>' +
          '<line x1="6" y1="6" x2="18" y2="18"></line>' +
        '</svg>' +
      '</button>' +
      '<div class="mu-modal__backdrop" data-mu-close-trailer></div>' +
      '<div class="mu-modal__content mu-modal__content--video mu-trailer-modal-wrapper">' +
        '<div class="mu-modal__video" id="mu-trailer-video-area"></div>' +
      '</div>';

    document.body.appendChild(modal);
    initTrailerModalEvents();
  }

  // Initialize trailer modal event listeners
  function initTrailerModalEvents() {
    var modal = document.getElementById('mu-trailer-modal');
    if (!modal) return;

    // Close button click
    var closeBtn = modal.querySelector('[data-mu-close-trailer]');
    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        if (e.target.closest('.mu-trailer-modal__close') || e.target.classList.contains('mu-modal__backdrop')) {
          closeTrailerModal();
        }
      });
    }

    // Backdrop click
    modal.addEventListener('click', function(e) {
      if (e.target.classList.contains('mu-modal__backdrop')) {
        closeTrailerModal();
      }
    });

    // ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('is-visible')) {
        closeTrailerModal();
      }
    });
  }

  // Initialize trailer modal events on load
  initTrailerModalEvents();

  function toEmbedUrl(url) {
    if (!url || typeof url !== 'string') return '';
    
    // Normalize URL - add protocol if missing
    var normalizedUrl = url.trim();
    
    // Handle URLs without protocol
    if (normalizedUrl.indexOf('http://') !== 0 && normalizedUrl.indexOf('https://') !== 0 && normalizedUrl.indexOf('//') !== 0) {
      normalizedUrl = 'https://' + normalizedUrl;
    }
    
    // Remove protocol for easier matching
    var urlWithoutProtocol = normalizedUrl.replace(/^https?:\/\//, '');
    
    // YouTube patterns
    // 1. youtube.com/watch?v=...
    // 2. youtu.be/...
    // 3. youtube.com/embed/...
    // 4. youtube.com/v/...
    var ytMatch = null;
    
    // Check for youtu.be short URL
    if (urlWithoutProtocol.indexOf('youtu.be/') !== -1) {
      var shortMatch = urlWithoutProtocol.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/);
      if (shortMatch && shortMatch[1]) {
        return 'https://www.youtube.com/embed/' + shortMatch[1] + '?autoplay=1&rel=0&modestbranding=1';
      }
    }
    
    // Check for youtube.com/watch?v= or youtube.com/embed/ or youtube.com/v/
    if (urlWithoutProtocol.indexOf('youtube.com/') !== -1) {
      // Extract video ID from various YouTube URL formats
      var patterns = [
        /[?&]v=([a-zA-Z0-9_-]{11})/,
        /\/embed\/([a-zA-Z0-9_-]{11})/,
        /\/v\/([a-zA-Z0-9_-]{11})/
      ];
      
      for (var i = 0; i < patterns.length; i++) {
        var match = urlWithoutProtocol.match(patterns[i]);
        if (match && match[1]) {
          return 'https://www.youtube.com/embed/' + match[1] + '?autoplay=1&rel=0&modestbranding=1';
        }
      }
    }
    
    // Vimeo pattern
    if (urlWithoutProtocol.indexOf('vimeo.com/') !== -1) {
      var vimeoMatch = urlWithoutProtocol.match(/vimeo\.com\/(\d+)/);
      if (vimeoMatch && vimeoMatch[1]) {
        return 'https://player.vimeo.com/video/' + vimeoMatch[1] + '?autoplay=1';
      }
    }
    
    // Already an embed URL - return as is with autoplay
    if (urlWithoutProtocol.indexOf('youtube.com/embed/') !== -1) {
      return normalizedUrl.indexOf('autoplay') !== -1 ? normalizedUrl : normalizedUrl + '?autoplay=1&rel=0&modestbranding=1';
    }
    
    if (urlWithoutProtocol.indexOf('player.vimeo.com') !== -1) {
      return normalizedUrl.indexOf('autoplay') !== -1 ? normalizedUrl : normalizedUrl + '?autoplay=1';
    }
    
    // If it's a valid HTTP URL, return it
    if (normalizedUrl.indexOf('http') === 0) {
      return normalizedUrl;
    }
    
    return '';
  }

  /* ================================================================
     UNAVAILABLE MODAL
  ================================================================ */
  function openUnavailableModal() {
    var modal = document.getElementById('mu-unavailable-modal');
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-visible');
    document.documentElement.style.overflow = 'hidden';
  }

  function closeUnavailableModal() {
    var modal = document.getElementById('mu-unavailable-modal');
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('is-visible');
    document.documentElement.style.overflow = '';
  }

  /* ================================================================
     FEATURE MODALS (Coming Soon)
  ================================================================ */
  (function initFeatureModals() {
    var modal = document.getElementById('mu-feature-modal');
    if (!modal) return;

    function openFeature() {
      modal.setAttribute('aria-hidden', 'false');
      modal.classList.add('is-visible');
      document.documentElement.style.overflow = 'hidden';
    }
    function closeFeature() {
      modal.setAttribute('aria-hidden', 'true');
      modal.classList.remove('is-visible');
      document.documentElement.style.overflow = '';
    }

    var featBtns = $$('[data-mu-feature-modal]');
    for (var fi = 0; fi < featBtns.length; fi++) {
      featBtns[fi].addEventListener('click', function (e) {
        e.preventDefault();
        openFeature();
      });
    }

    var closeBtns = $$('[data-mu-close-feature-modal]');
    for (var ci = 0; ci < closeBtns.length; ci++) {
      closeBtns[ci].addEventListener('click', closeFeature);
    }

    var backdrop = modal.querySelector('.mu-modal__backdrop');
    if (backdrop) backdrop.addEventListener('click', closeFeature);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('is-visible')) closeFeature();
    });
  }());

  /* ================================================================
     CARD PLAY / INFO BUTTONS
     IMPORTANT: Play button ALWAYS goes to Watch page (data-watch-url).
     Never fallback to trailer.
  ================================================================ */
  (function initCardInteractions() {
    /* Card Play button - handles both .mu-card and .mu-grid-card */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-mu-card-play]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();

      var card = btn.closest('.mu-card, .mu-grid-card');
      // Watch URL is always available - Play goes to Watch page
      var watchUrl = btn.getAttribute('data-watch-url') || (card && card.getAttribute('data-watch-url')) || '';

      if (watchUrl) {
        window.location.href = watchUrl;
      } else {
        openUnavailableModal();
      }
    });

    /* Smart Play (trailer) button on card */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-mu-smart-play]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();

      var card = btn.closest('.mu-card');
      var trailer = btn.getAttribute('data-trailer') || (card && card.getAttribute('data-trailer')) || '';

      if (trailer) {
        openTrailer(trailer);
      } else {
        openUnavailableModal();
      }
    });

    /* More Info button on card */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-mu-preview]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();

      var card = btn.closest('.mu-card');
      var url = btn.getAttribute('data-url') || (card && card.getAttribute('data-url')) || '';
      if (url) window.location.href = url;
    });

    /* Episode list button on TV show cards */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-mu-show-episodes]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();

      var card = btn.closest('.mu-card');
      var url = btn.getAttribute('data-watch-url') || (card && card.getAttribute('data-watch-url')) || '';
      if (url) window.location.href = url;
    });
  }());

  /* Modal close button listeners */
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-mu-close-trailer]')) closeTrailerModal();
    if (e.target.closest('[data-mu-close-unavailable]')) closeUnavailableModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeTrailerModal();
      closeUnavailableModal();
    }
  });

  /* ================================================================
     FAVORITES (localStorage + AJAX)
  ================================================================ */
  function getFavs() { return new Set(store.get(KEY_FAVS, [])); }
  function setFavs(set) { store.set(KEY_FAVS, Array.from ? Array.from(set) : Object.values(set)); }

  function syncFavButtons() {
    var favs = getFavs();
    var btns = document.querySelectorAll('.mu-fav');
    for (var fi = 0; fi < btns.length; fi++) {
      var btn = btns[fi];
      var id = btn.getAttribute('data-id');
      var on = id && favs.has(String(id));
      if (on) btn.classList.add('is-on');
      else btn.classList.remove('is-on');
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    }
  }
  syncFavButtons();

  function toggleFavoriteServer(movieId, callback) {
    if (!(window.MOVIE_UI && window.MOVIE_UI.isLoggedIn)) { if (callback) callback(null); return; }
    var body = 'action=toggle_favorite&movie_id=' + encodeURIComponent(movieId) +
               '&nonce=' + encodeURIComponent((window.MOVIE_UI && window.MOVIE_UI.favNonce) || '');

    var ajaxUrl = (window.MOVIE_UI && window.MOVIE_UI.ajaxUrl) || '/wp-admin/admin-ajax.php';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (callback) callback((data && data.success && data.data && data.data.status) ? data.data.status : null);
        } catch (e) { if (callback) callback(null); }
      }
    };
    xhr.send(body);
  }

  function toggleFavorite(id, btnEl) {
    if (!id) return;
    var favs = getFavs();
    var isOn = favs.has(String(id));

    toggleFavoriteServer(id, function (serverStatus) {
      /* Mirror to localStorage regardless of server result */
      if (isOn) {
        favs.delete(String(id));
        if (btnEl) btnEl.classList.remove('is-on');
      } else {
        favs.add(String(id));
        if (btnEl) btnEl.classList.add('is-on');
        if (window.MU_TOAST) MU_TOAST.success('', 'Added to My List');
        else if (window.showToast) window.showToast({ type: 'success', title: 'Added to My List' });
      }
      setFavs(favs);
      syncFavButtons();
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.mu-fav[data-mu-fav-toggle]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    var id = btn.getAttribute('data-id');
    if (!id) return;

    btn.disabled = true;
    btn.style.opacity = '0.6';

    toggleFavorite(id, btn);

    btn.disabled = false;
    btn.style.opacity = '';
  });

  /* Sync favorites to DB after login */
  (function syncFavoritesToDb() {
    if (!(window.MOVIE_UI && window.MOVIE_UI.isLoggedIn)) return;
    if (store.get(KEY_FAVS_SYNCED, false)) return;
    var ids = Array.from ? Array.from(getFavs()) : [];
    if (!ids.length) { store.set(KEY_FAVS_SYNCED, true); return; }

    var fd = new FormData();
    fd.append('action', 'movie_ui_sync_favorites');
    fd.append('nonce', (window.MOVIE_UI && window.MOVIE_UI.nonce) || '');
    for (var k = 0; k < ids.length; k++) fd.append('ids[]', ids[k]);

    var ajaxUrl = (window.MOVIE_UI && window.MOVIE_UI.ajaxUrl) || '/wp-admin/admin-ajax.php';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data && data.success) store.set(KEY_FAVS_SYNCED, true);
        } catch (ex) { /* ignore */ }
      }
    };
    xhr.send(fd);
  }());

  /* ================================================================
     PROGRESS BARS
  ================================================================ */
  function setProgress(id, t, d) {
    var all = store.get(KEY_PROGRESS, {});
    all[id] = { t: Math.max(0, Math.floor(t || 0)), d: Math.max(0, Math.floor(d || 0)), updatedAt: Date.now() };
    store.set(KEY_PROGRESS, all);
  }
  function getProgressAll() { return store.get(KEY_PROGRESS, {}); }

  function renderProgressBars() {
    var all = getProgressAll();
    var els = document.querySelectorAll('[data-mu-progress]');
    for (var pi = 0; pi < els.length; pi++) {
      var el = els[pi];
      var id = el.getAttribute('data-mu-progress');
      var p = all[id];
      if (!p || !p.d) continue;
      var pct = Math.max(0, Math.min(100, Math.round((p.t / p.d) * 100)));
      el.style.width = pct + '%';
    }
  }
  renderProgressBars();

  /* ================================================================
     CARD: Touch to open overlay on mobile
  ================================================================ */
  (function initCardTouch() {
    document.addEventListener('click', function (e) {
      var card = e.target.closest('.mu-card');
      if (!card) return;
      var isAction = e.target.closest('.mu-btn, a, button');
      if (isAction) return;

      if (window.matchMedia('(max-width: 768px)').matches) {
        e.preventDefault();
        var already = card.classList.contains('is-open');
        var openCards = document.querySelectorAll('.mu-card.is-open');
        for (var ki = 0; ki < openCards.length; ki++) openCards[ki].classList.remove('is-open');
        if (!already) card.classList.add('is-open');
      }
    });

    document.addEventListener('click', function (e) {
      if (!e.target.closest('.mu-card')) {
        var openCards = document.querySelectorAll('.mu-card.is-open');
        for (var ki = 0; ki < openCards.length; ki++) openCards[ki].classList.remove('is-open');
      }
    });
  }());

  /* ================================================================
     CAROUSEL / SWIPER ROWS
     Each row has unique ID-based navigation buttons.
  ================================================================ */
  function initSwipers() {
    if (typeof Swiper === 'undefined') return;

    var swiperEls = document.querySelectorAll('.mu-swiper[data-mu-swiper="row"]');
    for (var si = 0; si < swiperEls.length; si++) {
      var el = swiperEls[si];
      if (el._muSwiperInited) continue;
      el._muSwiperInited = true;

      var wrap = el.closest('[data-mu-swiper-wrap]');
      var rowId = wrap ? wrap.getAttribute('data-mu-swiper-wrap') : '';
      var prevBtn = wrap ? wrap.querySelector('[data-mu-row-prev="' + rowId + '"]') : null;
      var nextBtn = wrap ? wrap.querySelector('[data-mu-row-next="' + rowId + '"]') : null;

      var navConfig = { disabledClass: 'is-disabled' };
      if (prevBtn) navConfig.prevEl = prevBtn;
      if (nextBtn) navConfig.nextEl = nextBtn;

      /* eslint-disable-next-line no-new */
      new Swiper(el, {
        slidesPerView: 6,
        spaceBetween: 12,
        speed: 650,
        grabCursor: true,
        freeMode: false,
        navigation: navConfig,
        breakpoints: {
          0:   { slidesPerView: 2, spaceBetween: 10 },
          480: { slidesPerView: 3, spaceBetween: 12 },
          720: { slidesPerView: 4, spaceBetween: 14 },
          1024:{ slidesPerView: 5, spaceBetween: 16 },
          1400:{ slidesPerView: 6, spaceBetween: 16 }
        }
      });
    }
  }

  function waitForSwiperAndInit() {
    if (typeof Swiper !== 'undefined') {
      initSwipers();
    } else {
      var checkCount = 0;
      var checkInterval = setInterval(function () {
        checkCount++;
        if (typeof Swiper !== 'undefined') {
          clearInterval(checkInterval);
          initSwipers();
        } else if (checkCount > 50) {
          clearInterval(checkInterval);
        }
      }, 100);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      waitForSwiperAndInit();
    });
  } else {
    waitForSwiperAndInit();
  }

  /* ================================================================
     CONTINUE WATCHING: Hydrate from localStorage
  ================================================================ */
  (function hydrateContinueWatching() {
    var mount = document.querySelector('[data-mu-continue-mount]');
    if (!mount || !(window.MOVIE_UI && window.MOVIE_UI.ajaxUrl)) return;

    var prog = store.get(KEY_PROGRESS, {});
    var ids = Object.keys(prog).filter(function (k) {
      var p = prog[k];
      return p && p.d > 60 && typeof p.t === 'number' && p.t < (p.d || 0) * 0.97;
    }).sort(function (a, b) {
      return (prog[b].updatedAt || 0) - (prog[a].updatedAt || 0);
    }).slice(0, 20);

    var row = mount.closest('.mu-row');
    var msgEl = row && row.querySelector('.mu-continue-msg');

    if (!ids.length) {
      if (row) row.style.display = 'none';
      return;
    }

    var fd = new FormData();
    fd.append('action', 'movie_ui_cards_by_ids');
    fd.append('nonce', (window.MOVIE_UI && window.MOVIE_UI.nonce) || '');
    for (var ki = 0; ki < ids.length; ki++) fd.append('ids[]', ids[ki]);

    var ajaxUrl = window.MOVIE_UI.ajaxUrl;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          var html = (data && data.success && data.data) ? (data.data.html || '') : '';
          if (!html) { if (row) row.style.display = 'none'; return; }
          mount.innerHTML = html;
          if (msgEl) msgEl.hidden = true;
          syncFavButtons();
          renderProgressBars();
          initSwipers();
        } catch (ex) { if (row) row.style.display = 'none'; }
      }
    };
    xhr.send(fd);
  }());

  /* ================================================================
     PAGE SEARCH (dedicated search template)
  ================================================================ */
  (function initPageSearch() {
    var input = document.querySelector('[data-mu-page-search]');
    var out = document.querySelector('[data-mu-page-results]');
    var skel = document.querySelector('[data-mu-page-skel]');
    if (!(input && out && window.MOVIE_UI)) return;

    var RK = 'mu_recent_search_v1';

    function pushRecent(term) {
      var t = (term || '').trim();
      if (t.length < 2) return;
      try {
        var list = JSON.parse(localStorage.getItem(RK) || '[]');
        list = [t].concat(list.filter(function (x) { return x.toLowerCase() !== t.toLowerCase(); })).slice(0, 8);
        localStorage.setItem(RK, JSON.stringify(list));
      } catch (e) { /* ignore */ }
    }

    function runSearch(q) {
      if (skel) skel.style.display = 'grid';
      var fd = new FormData();
      fd.append('action', 'movie_ui_search');
      fd.append('nonce', window.MOVIE_UI.nonce || '');
      fd.append('q', q);
      var ajaxUrl = window.MOVIE_UI.ajaxUrl;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', ajaxUrl, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          if (skel) skel.style.display = 'none';
          if (xhr.status === 200) {
            try {
              var data = JSON.parse(xhr.responseText);
              if (data && data.success && data.data) {
                out.innerHTML = data.data.html || '';
                syncFavButtons();
                renderProgressBars();
                initSwipers();
              } else {
                out.innerHTML = '<div class="mu-empty">No results.</div>';
              }
            } catch (ex) {
              out.innerHTML = '<div class="mu-empty">Error.</div>';
            }
          }
        }
      };
      xhr.send(fd);
    }

    var q0 = (input.value || '').trim();
    runSearch(q0.length >= 2 ? q0 : '');

    var debouncedRun = debounce(function () {
      var v = (input.value || '').trim();
      runSearch(v.length >= 2 ? v : '');
    }, 300);
    input.addEventListener('input', debouncedRun);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        var v = (input.value || '').trim();
        if (v) pushRecent(v);
      }
    });
  }());

  /* ================================================================
     MY LIST PAGE: Hydrate from localStorage for guests
  ================================================================ */
  (function hydrateMyListGuest() {
    var mount = document.querySelector('[data-mu-mylist-mount]');
    if (!mount || !window.MOVIE_UI || window.MOVIE_UI.isLoggedIn) return;

    var ids = Array.from ? Array.from(getFavs()) : [];
    if (!ids.length) return;

    var fd = new FormData();
    fd.append('action', 'movie_ui_cards_by_ids');
    fd.append('layout', 'grid');
    fd.append('nonce', window.MOVIE_UI.nonce || '');
    for (var ki = 0; ki < ids.length; ki++) fd.append('ids[]', ids[ki]);

    var ajaxUrl = window.MOVIE_UI.ajaxUrl;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data && data.success && data.data && data.data.html) {
            mount.innerHTML = data.data.html;
            syncFavButtons();
            renderProgressBars();
          }
        } catch (ex) { /* ignore */ }
      }
    };
    xhr.send(fd);
  }());

  /* ================================================================
     HISTORY PAGE: Local + server
  ================================================================ */
  (function initHistoryPage() {
    var histRoot = document.querySelector('[data-mu-history]');
    if (!histRoot) return;

    var list = histRoot.querySelector('[data-mu-history-list]');
    var btnMore = histRoot.querySelector('[data-mu-history-more]');
    var input = histRoot.querySelector('[data-mu-history-q]');
    var offset = 0;

    function fetchMore(reset) {
      if (reset) { if (list) list.innerHTML = ''; offset = 0; }
      var q = input ? (input.value || '').trim() : '';
      var fd = new FormData();
      fd.append('action', 'movie_ui_history_fetch');
      fd.append('nonce', (window.MOVIE_UI && window.MOVIE_UI.nonce) || '');
      fd.append('offset', reset ? '0' : String(offset));
      fd.append('limit', '24');
      fd.append('q', q);

      var ajaxUrl = (window.MOVIE_UI && window.MOVIE_UI.ajaxUrl) || '/wp-admin/admin-ajax.php';
      var xhr = new XMLHttpRequest();
      xhr.open('POST', ajaxUrl, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data && data.success && data.data) {
              if (list) list.insertAdjacentHTML('beforeend', data.data.html || '');
              offset += data.data.count || 0;
              histRoot.setAttribute('data-offset', String(offset));
              if (btnMore) btnMore.style.display = (data.data.count || 0) < 24 ? 'none' : '';
              initSwipers();
              syncFavButtons();
              renderProgressBars();
            }
          } catch (ex) { /* ignore */ }
        }
      };
      xhr.send(fd);
    }

    if (btnMore) btnMore.addEventListener('click', function () { fetchMore(false); });
    if (input) {
      var debouncedFetch = debounce(function () { fetchMore(true); }, 320);
      input.addEventListener('input', debouncedFetch);
    }

    /* Remove from history */
    document.addEventListener('click', function (e) {
      var rm = e.target.closest('[data-mu-history-remove]');
      if (!rm) return;
      var id = rm.getAttribute('data-mu-history-remove');
      var fd = new FormData();
      fd.append('action', 'movie_ui_history_remove');
      fd.append('nonce', (window.MOVIE_UI && window.MOVIE_UI.nonce) || '');
      fd.append('post_id', id);
      var ajaxUrl = (window.MOVIE_UI && window.MOVIE_UI.ajaxUrl) || '/wp-admin/admin-ajax.php';
      var xhr = new XMLHttpRequest();
      xhr.open('POST', ajaxUrl, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var parent = rm.closest('[data-id]') || rm.closest('.mu-card') || rm.parentElement;
          if (parent && parent.parentElement) parent.parentElement.removeChild(parent);
          if (window.MU_TOAST) MU_TOAST.info('', 'Removed from history');
          else if (window.showToast) window.showToast({ type: 'info', title: 'Removed from history' });
        }
      };
      xhr.send(fd);
    });

    /* Clear history */
    document.addEventListener('click', function (e) {
      var clr = e.target.closest('[data-mu-history-clear]');
      if (!clr) return;
      var fd = new FormData();
      fd.append('action', 'movie_ui_history_clear');
      fd.append('nonce', (window.MOVIE_UI && window.MOVIE_UI.nonce) || '');
      var ajaxUrl = (window.MOVIE_UI && window.MOVIE_UI.ajaxUrl) || '/wp-admin/admin-ajax.php';
      var xhr = new XMLHttpRequest();
      xhr.open('POST', ajaxUrl, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
          if (list) list.innerHTML = '<div class="mu-empty">History cleared.</div>';
          if (window.MU_TOAST) MU_TOAST.success('', 'History cleared');
          else if (window.showToast) window.showToast({ type: 'success', title: 'History cleared' });
        }
      };
      xhr.send(fd);
    });

    if (list && list.children.length === 0) fetchMore(true);
  }());

  /* ================================================================
     UTILITY: Close all dropdowns
  ================================================================ */
  function closeAllDropdowns() {
    var dd = document.querySelector('[data-mu-user-dropdown]');
    if (dd) dd.hidden = true;
    var nd = document.querySelector('[data-mu-notifications-dropdown]');
    if (nd) nd.hidden = true;
    var nb = document.querySelector('.mu-notifications-backdrop');
    if (nb) nb.hidden = true;
    var av = document.querySelector('[data-mu-open-profile]');
    if (av) av.setAttribute('aria-expanded', 'false');
    var bv = document.querySelector('[data-mu-open-notifications]');
    if (bv) bv.setAttribute('aria-expanded', 'false');
  }

  /* ================================================================
     SEARCH backdrop close
  ================================================================ */
  document.addEventListener('click', function (e) {
    var search = document.querySelector('[data-mu-search]');
    if (search && search.classList.contains('is-open') && e.target.classList.contains('mu-search__backdrop')) {
      search.classList.remove('is-open');
      search.setAttribute('aria-hidden', 'true');
      document.documentElement.style.overflow = '';
    }
  });

  /* ================================================================
     TRENDING PAGE - Rank Card Interactions
  ================================================================ */
  (function initTrendingCards() {
    var container = document.querySelector('.mu-trending');
    if (!container) return;

    /* Card click → detail page */
    container.addEventListener('click', function (e) {
      var card = e.target.closest('.rank-card');
      if (!card) return;

      // Ignore if clicking buttons
      if (e.target.closest('.rank-card__btn')) return;

      var detailUrl = card.getAttribute('data-detail-url');
      if (detailUrl) {
        window.location.href = detailUrl;
      }
    });

    /* Play button → Watch page (never trailer) */
    container.addEventListener('click', function (e) {
      var btn = e.target.closest('.rank-card__btn--play');
      if (!btn) return;
      e.stopPropagation();

      var watchUrl = btn.getAttribute('data-watch-url') || '';

      if (watchUrl) {
        window.location.href = watchUrl;
      } else {
        openUnavailableModal();
      }
    });

    /* Favorite button */
    container.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-favorite]');
      if (!btn) return;
      e.stopPropagation();

      var id = btn.getAttribute('data-favorite');
      if (id) {
        toggleFavorite(id, btn);
      }
    });
  }());

}());
