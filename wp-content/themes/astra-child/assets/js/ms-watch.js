/**
 * ============================================================
 * Premium Watch Page JavaScript
 * Video player controls and interactions
 * ============================================================
 */

(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    initWatchPage();
  });

  function initWatchPage() {
    const video = document.getElementById('mu-player-video');
    const container = document.querySelector('.mu-player-container');
    
    if (video) {
      initVideoPlayer(video);
    }
    
    initQualitySelector();
    initActions();
    initKeyboardShortcuts();
  }

  // ============================================================
  // VIDEO QUALITY SELECTOR
  // ============================================================
  function initQualitySelector() {
    const container = document.querySelector('.mu-player-container');
    if (!container) return;
    
    const sourcesData = container.dataset.sources;
    if (!sourcesData || sourcesData === '[]') return;
    
    const sourceBtns = document.querySelectorAll('.mu-player-source-btn');
    const video = document.getElementById('mu-player-video');
    
    sourceBtns.forEach(function(btn) {
      btn.addEventListener('click', function() {
        const newSrc = btn.dataset.src;
        const quality = btn.dataset.quality;
        
        if (!newSrc || !video) return;
        
        // Update UI
        sourceBtns.forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        
        // Save current time
        const currentTime = video.currentTime || 0;
        const wasPaused = video.paused;
        
        // Switch source
        video.src = newSrc;
        video.currentTime = currentTime;
        
        if (!wasPaused) {
          video.play().catch(function() {});
        }
      });
    });
  }

  // ============================================================
  // VIDEO PLAYER
  // ============================================================
  function initVideoPlayer(video) {
    const container = video.closest('.mu-player-container');
    const playBtn = document.querySelector('.mu-watch-poster__play-btn');
    const infoPlayBtn = document.querySelector('.mu-btn-play');
    let hideTimer;

    // Load video source
    const src = video.dataset.src;
    if (src) {
      video.src = src;
    }

    // Play buttons
    if (playBtn) {
      playBtn.addEventListener('click', function() {
        video.play();
      });
    }

    if (infoPlayBtn) {
      infoPlayBtn.addEventListener('click', function() {
        video.play();
        video.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }

    // Save progress
    setInterval(function() {
      if (video.duration && !video.paused) {
        const postId = container.dataset.postId;
        if (postId) {
          localStorage.setItem('mu_progress_' + postId, Math.floor(video.currentTime));
        }
      }
    }, 10000);

    // Load saved progress
    const postId = container.dataset.postId;
    if (postId) {
      const saved = localStorage.getItem('mu_progress_' + postId);
      if (saved && parseInt(saved) > 30) {
        video.currentTime = parseInt(saved);
      }
    }
  }

  // ============================================================
  // ACTIONS
  // ============================================================
  function initActions() {
    // Favorites
    const favBtns = document.querySelectorAll('[data-action="favorites"], .mu-btn-fav');
    const postId = document.querySelector('.mu-player-wrapper')?.dataset.postId;
    
    if (postId) {
      const isFavorited = localStorage.getItem('mu_fav_' + postId) === 'true';
      updateFavUI(isFavorited);
    }

    favBtns.forEach(function(btn) {
      btn.addEventListener('click', function() {
        if (!postId) return;
        
        const current = localStorage.getItem('mu_fav_' + postId) === 'true';
        const newState = !current;
        localStorage.setItem('mu_fav_' + postId, newState ? 'true' : 'false');
        updateFavUI(newState);
        showToast(newState ? 'Added to My List' : 'Removed from My List');
      });
    });

    function updateFavUI(isFavorited) {
      favBtns.forEach(function(btn) {
        if (isFavorited) {
          btn.classList.add('is-active');
          const svg = btn.querySelector('svg');
          if (svg) svg.setAttribute('fill', 'currentColor');
        } else {
          btn.classList.remove('is-active');
          const svg = btn.querySelector('svg');
          if (svg) svg.setAttribute('fill', 'none');
        }
      });
    }

    // Share button
    const shareBtn = document.querySelector('[data-action="share"]');
    if (shareBtn) {
      shareBtn.addEventListener('click', function() {
        if (navigator.share) {
          navigator.share({
            title: document.title,
            url: window.location.href
          });
        } else {
          navigator.clipboard.writeText(window.location.href);
          showToast('Link copied to clipboard!');
        }
      });
    }

    // Episode season filter
    const seasonSelect = document.querySelector('.mu-watch-episodes__select');
    if (seasonSelect) {
      seasonSelect.addEventListener('change', function() {
        const season = this.value;
        const episodes = document.querySelectorAll('.mu-watch-episode');
        episodes.forEach(function(ep) {
          if (season === 'all' || ep.dataset.season === season) {
            ep.style.display = '';
          } else {
            ep.style.display = 'none';
          }
        });
      });
    }
  }

  // ============================================================
  // KEYBOARD SHORTCUTS
  // ============================================================
  function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
      const video = document.getElementById('mu-player-video');
      if (!video || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

      switch(e.code) {
        case 'Space':
        case 'k':
          e.preventDefault();
          if (video.paused) video.play(); else video.pause();
          showToast(video.paused ? 'Paused' : 'Playing');
          break;
        case 'ArrowLeft':
        case 'j':
          e.preventDefault();
          video.currentTime = Math.max(0, video.currentTime - 10);
          showToast('-10s');
          break;
        case 'ArrowRight':
        case 'l':
          e.preventDefault();
          video.currentTime = Math.min(video.duration, video.currentTime + 10);
          showToast('+10s');
          break;
        case 'ArrowUp':
          e.preventDefault();
          video.volume = Math.min(1, video.volume + 0.1);
          showToast('Volume: ' + Math.round(video.volume * 100) + '%');
          break;
        case 'ArrowDown':
          e.preventDefault();
          video.volume = Math.max(0, video.volume - 0.1);
          showToast('Volume: ' + Math.round(video.volume * 100) + '%');
          break;
        case 'm':
          e.preventDefault();
          video.muted = !video.muted;
          showToast(video.muted ? 'Muted' : 'Unmuted');
          break;
        case 'f':
          e.preventDefault();
          if (document.fullscreenElement) {
            document.exitFullscreen();
          } else {
            video.closest('.mu-player-container').requestFullscreen();
          }
          break;
        case 'Escape':
          if (document.fullscreenElement) {
            document.exitFullscreen();
          }
          break;
      }
    });
  }

  // ============================================================
  // TOAST
  // ============================================================
  function showToast(message) {
    let toast = document.querySelector('.mu-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'mu-toast';
      toast.innerHTML = '<span>' + message + '</span>';
      toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);padding:12px 24px;background:rgba(0,0,0,0.9);color:white;border-radius:8px;font-size:14px;font-weight:500;z-index:9999;opacity:0;transition:opacity 0.3s ease;pointer-events:none;';
      document.body.appendChild(toast);
    }
    
    toast.querySelector('span').textContent = message;
    toast.style.opacity = '1';
    
    setTimeout(function() {
      toast.style.opacity = '0';
    }, 2000);
  }

})();
