/**
 * ============================================================
 * Premium OTT Watch Player JavaScript
 * Handles video playback, controls, progress, and interactions
 * ============================================================
 */
(function () {
  'use strict';

  // ============================================================
  // UTILITY FUNCTIONS
  // ============================================================
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function formatTime(seconds) {
    if (isNaN(seconds) || seconds < 0) return '0:00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) {
      return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }
    return `${m}:${String(s).padStart(2, '0')}`;
  }

  function debounce(fn, wait) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  function localStorageGet(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function localStorageSet(key, val) {
    try {
      localStorage.setItem(key, JSON.stringify(val));
    } catch (e) {}
  }

  // ============================================================
  // PLAYER STATE
  // ============================================================
  const state = {
    player: null,
    hls: null,
    isPlaying: false,
    isMuted: false,
    volume: 1,
    currentTime: 0,
    duration: 0,
    buffered: 0,
    isFullscreen: false,
    isSettingsOpen: false,
    isInfoVisible: false,
    currentSettings: {
      speed: 1,
      quality: 'auto',
      subtitles: 'off'
    },
    progressSaveInterval: null,
    idleTimeout: null,
    isIdle: false
  };

  // ============================================================
  // DOM ELEMENTS
  // ============================================================
  let elements = {};

  function initElements() {
    elements = {
      wrapper: $('.mu-player-wrapper'),
      container: $('.mu-player-container'),
      video: $('#mu-player-video'),
      iframe: $('.mu-player-iframe'),
      contentPanel: $('.mu-watch-content'),
      playBtn: $('.mu-player-center .mu-player-play-btn'),
      rewindBtn: $('.mu-player-rewind'),
      forwardBtn: $('.mu-player-forward'),
      skipIndicator: $$('.mu-player-skip-indicator'),
      header: $('.mu-player-header'),
      controls: $('.mu-player-controls'),
      progressWrap: $('.mu-player-progress-wrap'),
      progressBar: $('.mu-player-progress-bar'),
      progressBuffered: $('.mu-player-progress-buffered'),
      progressThumb: $('.mu-player-progress-thumb'),
      progressTooltip: $('.mu-player-progress-tooltip'),
      timeCurrent: $('.mu-player-time-current'),
      timeDuration: $('.mu-player-time-duration'),
      playPauseBtn: $('.mu-player-ctrl-btn[data-action="play-pause"]'),
      volumeBtn: $('.mu-player-ctrl-btn[data-action="volume"]'),
      volumeSlider: $('.mu-player-volume-slider input[type="range"]'),
      fullscreenBtn: $('.mu-player-ctrl-btn[data-action="fullscreen"]'),
      subtitlesBtn: $('.mu-player-ctrl-btn[data-action="subtitles"]'),
      settingsBtn: $('.mu-player-ctrl-btn[data-action="settings"]'),
      settingsDropdown: $('.mu-player-settings-dropdown'),
      unavailable: $('.mu-player-unavailable'),
      loading: $('.mu-player-loading'),
      shortcutsToast: $('.mu-player-shortcuts-toast'),
      toastMsg: $('.mu-player-shortcuts-toast .mu-player-toast-msg')
    };
  }

  // ============================================================
  // VIDEO SOURCE DETECTION
  // ============================================================
  function detectVideoType(src) {
    if (!src) return null;
    if (src.includes('.m3u8')) return 'hls';
    if (src.includes('youtube.com') || src.includes('youtu.be')) return 'youtube';
    if (src.includes('vimeo.com')) return 'vimeo';
    if (src.includes('<iframe') || src.startsWith('http') && (src.includes('embed') || src.includes('player'))) return 'iframe';
    return 'mp4';
  }

  function getYouTubeEmbedUrl(url) {
    let videoId = '';
    if (url.includes('watch?v=')) {
      const match = url.match(/[?&]v=([^&]+)/);
      if (match) videoId = match[1];
    } else if (url.includes('youtu.be/')) {
      const match = url.match(/youtu\.be\/([^?]+)/);
      if (match) videoId = match[1];
    }
    if (videoId) {
      return `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1&playsinline=1`;
    }
    return url;
  }

  // ============================================================
  // HLS PLAYER SUPPORT
  // ============================================================
  function initHLS(src) {
    if (!window.Hls || !elements.video) return;

    if (state.hls) {
      state.hls.destroy();
      state.hls = null;
    }

    if (window.Hls.isSupported()) {
      state.hls = new window.Hls({
        enableWorker: true,
        lowLatencyMode: true
      });
      state.hls.loadSource(src);
      state.hls.attachMedia(elements.video);
      state.hls.on(window.Hls.Events.MANIFEST_PARSED, () => {
        hideLoading();
      });
      state.hls.on(window.Hls.Events.ERROR, (event, data) => {
        if (data.fatal) {
          console.error('HLS Fatal Error:', data);
          showUnavailable();
        }
      });
    } else if (elements.video.canPlayType('application/vnd.apple.mpegurl')) {
      elements.video.src = src;
    }
  }

  // ============================================================
  // PLAYER INITIALIZATION
  // ============================================================
  function initPlayer() {
    if (!elements.video && !elements.iframe) return;

    const videoSrc = elements.video?.dataset.src || elements.video?.src || '';
    const videoType = detectVideoType(videoSrc);

    // If iframe embed
    if (elements.iframe) {
      hideLoading();
      return;
    }

    if (!videoSrc || videoSrc === window.location.href) {
      showUnavailable();
      return;
    }

    // HLS stream
    if (videoType === 'hls') {
      initHLS(videoSrc);
      setupVideoEvents();
      return;
    }

    // YouTube
    if (videoType === 'youtube') {
      const embedUrl = getYouTubeEmbedUrl(videoSrc);
      elements.container.innerHTML = `
        <iframe class="mu-player-iframe"
          src="${embedUrl}"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowfullscreen>
        </iframe>
      `;
      elements.iframe = $('.mu-player-iframe');
      hideLoading();
      return;
    }

    // Native video element
    if (elements.video) {
      setupVideoEvents();
      // Resume from saved position
      const savedTime = getSavedProgress();
      if (savedTime > 0 && elements.video.duration > savedTime) {
        elements.video.currentTime = savedTime;
      }
    }

    hideLoading();
  }

  // ============================================================
  // VIDEO EVENTS
  // ============================================================
  function setupVideoEvents() {
    if (!elements.video) return;

    const video = elements.video;

    video.addEventListener('loadedmetadata', () => {
      state.duration = video.duration;
      updateDuration();
      hideLoading();
    });

    video.addEventListener('timeupdate', () => {
      state.currentTime = video.currentTime;
      updateProgress();
    });

    video.addEventListener('progress', () => {
      if (video.buffered.length > 0) {
        state.buffered = (video.buffered.end(video.buffered.length - 1) / video.duration) * 100;
        updateBuffered();
      }
    });

    video.addEventListener('play', () => {
      state.isPlaying = true;
      updatePlayButton();
      startProgressSave();
    });

    video.addEventListener('pause', () => {
      state.isPlaying = false;
      updatePlayButton();
      saveProgress();
    });

    video.addEventListener('ended', () => {
      state.isPlaying = false;
      updatePlayButton();
      saveProgress();
      handleVideoEnded();
    });

    video.addEventListener('waiting', showLoading);
    video.addEventListener('canplay', hideLoading);

    video.addEventListener('volumechange', () => {
      state.volume = video.volume;
      state.isMuted = video.muted;
      updateVolumeUI();
    });

    video.addEventListener('error', () => {
      showUnavailable();
    });
  }

  // ============================================================
  // UI UPDATES
  // ============================================================
  function updateProgress() {
    if (!elements.progressBar || !state.duration) return;
    const percent = (state.currentTime / state.duration) * 100;
    elements.progressBar.style.width = `${percent}%`;
    elements.progressThumb.style.left = `${percent}%`;
    if (elements.timeCurrent) {
      elements.timeCurrent.textContent = formatTime(state.currentTime);
    }
  }

  function updateBuffered() {
    if (elements.progressBuffered) {
      elements.progressBuffered.style.width = `${state.buffered}%`;
    }
  }

  function updateDuration() {
    if (elements.timeDuration) {
      elements.timeDuration.textContent = formatTime(state.duration);
    }
  }

  function updatePlayButton() {
    if (!elements.playPauseBtn || !elements.playPauseBtn.querySelector('svg')) return;
    const svg = elements.playPauseBtn.querySelector('svg');
    if (state.isPlaying) {
      svg.innerHTML = '<rect x="6" y="4" width="4" height="16" fill="currentColor"/><rect x="14" y="4" width="4" height="16" fill="currentColor"/>';
    } else {
      svg.innerHTML = '<path d="M8 5v14l11-7z" fill="currentColor"/>';
    }

    // Update center play button
    if (elements.playBtn) {
      elements.playBtn.style.opacity = state.isPlaying ? '0' : '1';
      elements.playBtn.style.pointerEvents = state.isPlaying ? 'none' : 'auto';
    }
  }

  function updateVolumeUI() {
    if (!elements.volumeBtn) return;
    if (state.isMuted || state.volume === 0) {
      elements.volumeBtn.classList.add('is-active');
    } else {
      elements.volumeBtn.classList.remove('is-active');
    }
    if (elements.volumeSlider) {
      elements.volumeSlider.value = state.isMuted ? 0 : state.volume * 100;
    }
  }

  function showLoading() {
    if (elements.loading) {
      elements.loading.style.display = 'flex';
    }
  }

  function hideLoading() {
    if (elements.loading) {
      elements.loading.style.display = 'none';
    }
  }

  function showUnavailable() {
    if (elements.unavailable) {
      elements.unavailable.style.display = 'flex';
    }
    hideLoading();
  }

  function showToast(message) {
    if (elements.shortcutsToast && elements.toastMsg) {
      elements.toastMsg.textContent = message;
      elements.shortcutsToast.classList.add('is-visible');
      setTimeout(() => {
        elements.shortcutsToast.classList.remove('is-visible');
      }, 2000);
    }
  }

  // Toggle info/content panel
  function toggleContentPanel() {
    if (!elements.contentPanel) return;
    state.isInfoVisible = !state.isInfoVisible;
    elements.contentPanel.classList.toggle('is-visible', state.isInfoVisible);
    showToast(state.isInfoVisible ? 'Showing info' : 'Hiding info');
  }

  // ============================================================
  // PLAYER CONTROLS
  // ============================================================
  function togglePlay() {
    if (!elements.video) return;
    if (state.isPlaying) {
      elements.video.pause();
    } else {
      elements.video.play();
    }
  }

  function toggleMute() {
    if (!elements.video) return;
    elements.video.muted = !elements.video.muted;
    state.isMuted = elements.video.muted;
    updateVolumeUI();
  }

  function setVolume(value) {
    if (!elements.video) return;
    elements.video.volume = value;
    elements.video.muted = value === 0;
    state.volume = value;
    state.isMuted = value === 0;
    updateVolumeUI();
  }

  function seek(time) {
    if (!elements.video) return;
    elements.video.currentTime = Math.max(0, Math.min(state.duration, elements.video.currentTime + time));
  }

  function seekTo(percent) {
    if (!elements.video || !state.duration) return;
    elements.video.currentTime = (percent / 100) * state.duration;
  }

  function toggleFullscreen() {
    if (!elements.container) return;

    if (!document.fullscreenElement) {
      if (elements.container.requestFullscreen) {
        elements.container.requestFullscreen();
      } else if (elements.container.webkitRequestFullscreen) {
        elements.container.webkitRequestFullscreen();
      }
      state.isFullscreen = true;
      elements.container.classList.add('mu-player-container--fullscreen');
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      }
      state.isFullscreen = false;
      elements.container.classList.remove('mu-player-container--fullscreen');
    }
  }

  function toggleSettings() {
    state.isSettingsOpen = !state.isSettingsOpen;
    if (elements.settingsDropdown) {
      elements.settingsDropdown.classList.toggle('is-open', state.isSettingsOpen);
    }
  }

  function setPlaybackSpeed(speed) {
    if (!elements.video) return;
    elements.video.playbackRate = speed;
    state.currentSettings.speed = speed;
    saveSettings();
    showToast(`Playback speed: ${speed}x`);
  }

  function setSubtitles(mode) {
    state.currentSettings.subtitles = mode;
    saveSettings();

    if (!elements.video) return;

    const tracks = elements.video.textTracks;
    for (let i = 0; i < tracks.length; i++) {
      tracks[i].mode = mode;
    }

    if (elements.subtitlesBtn) {
      elements.subtitlesBtn.classList.toggle('is-active', mode !== 'off');
    }

    showToast(mode === 'off' ? 'Subtitles off' : 'Subtitles on');
  }

  // ============================================================
  // PROGRESS TRACKING
  // ============================================================
  function getSavedProgress() {
    const postId = elements.wrapper?.dataset.postId;
    if (!postId) return 0;
    const progress = localStorageGet('mu_progress', {});
    return progress[postId]?.t || 0;
  }

  function saveProgress() {
    const postId = elements.wrapper?.dataset.postId;
    if (!postId || !state.duration) return;

    const progress = localStorageGet('mu_progress', {});
    progress[postId] = {
      t: state.currentTime,
      d: state.duration,
      updatedAt: Date.now()
    };
    localStorageSet('mu_progress', progress);

    // Sync to server if logged in
    syncProgressToServer(postId, state.currentTime, state.duration);
  }

  function startProgressSave() {
    if (state.progressSaveInterval) return;
    state.progressSaveInterval = setInterval(() => {
      if (state.isPlaying) {
        saveProgress();
      }
    }, 5000);
  }

  function syncProgressToServer(postId, currentTime, duration) {
    if (!window.MOVIE_UI?.isLoggedIn) return;

    const fd = new FormData();
    fd.append('action', 'movie_ui_save_progress');
    fd.append('post_id', postId);
    fd.append('t', Math.floor(currentTime));
    fd.append('d', Math.floor(duration));
    fd.append('p', Math.floor((currentTime / duration) * 100));
    fd.append('nonce', window.MOVIE_UI.progressNonce || window.MOVIE_UI.nonce || '');

    fetch(window.MOVIE_UI.ajaxUrl || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    }).catch(() => {});
  }

  // ============================================================
  // SETTINGS PERSISTENCE
  // ============================================================
  function loadSettings() {
    const saved = localStorageGet('mu_player_settings', state.currentSettings);
    state.currentSettings = { ...state.currentSettings, ...saved };
  }

  function saveSettings() {
    localStorageSet('mu_player_settings', state.currentSettings);
  }

  // ============================================================
  // IDLE DETECTION
  // ============================================================
  function resetIdleTimer() {
    if (state.idleTimeout) clearTimeout(state.idleTimeout);

    if (state.isFullscreen) {
      elements.container?.classList.remove('is-idle');
      state.isIdle = false;

      state.idleTimeout = setTimeout(() => {
        if (state.isPlaying) {
          elements.container?.classList.add('is-idle');
          state.isIdle = true;
        }
      }, 3000);
    }
  }

  // ============================================================
  // VIDEO ENDED HANDLER
  // ============================================================
  function handleVideoEnded() {
    const nextHref = elements.wrapper?.dataset.nextHref;
    if (nextHref) {
      showToast('Up next: Loading...');
      setTimeout(() => {
        window.location.href = nextHref;
      }, 3000);
    }
  }

  // ============================================================
  // HEADER FADE ON PLAY
  // ============================================================
  function initHeaderFade() {
    if (!elements.header) return;

    const fadeHeader = debounce(() => {
      if (state.isPlaying) {
        elements.header.classList.add('is-hidden');
      } else {
        elements.header.classList.remove('is-hidden');
      }
    }, 2000);

    const showHeader = () => {
      elements.header.classList.remove('is-hidden');
      fadeHeader();
    };

    elements.container?.addEventListener('mousemove', showHeader);
    elements.container?.addEventListener('touchstart', showHeader);
    elements.container?.addEventListener('click', () => {
      showHeader();
    });

    if (state.isPlaying) {
      fadeHeader();
    }
  }

  // ============================================================
  // EVENT LISTENERS
  // ============================================================
  function setupEventListeners() {
    // Center play button
    elements.playBtn?.addEventListener('click', togglePlay);

    // Rewind/Forward
    elements.rewindBtn?.addEventListener('click', () => seek(-10));
    elements.forwardBtn?.addEventListener('click', () => seek(10));

    // Progress bar click
    elements.progressWrap?.addEventListener('click', (e) => {
      const rect = elements.progressWrap.getBoundingClientRect();
      const percent = ((e.clientX - rect.left) / rect.width) * 100;
      seekTo(Math.max(0, Math.min(100, percent)));
    });

    // Progress hover tooltip
    elements.progressWrap?.addEventListener('mousemove', (e) => {
      if (!elements.progressTooltip || !state.duration) return;
      const rect = elements.progressWrap.getBoundingClientRect();
      const percent = ((e.clientX - rect.left) / rect.width) * 100;
      const time = (percent / 100) * state.duration;
      elements.progressTooltip.textContent = formatTime(time);
      elements.progressTooltip.style.left = `${percent}%`;
    });

    // Control buttons
    elements.playPauseBtn?.addEventListener('click', togglePlay);
    elements.volumeBtn?.addEventListener('click', toggleMute);
    elements.fullscreenBtn?.addEventListener('click', toggleFullscreen);
    elements.settingsBtn?.addEventListener('click', toggleSettings);

    // Volume slider
    elements.volumeSlider?.addEventListener('input', (e) => {
      setVolume(e.target.value / 100);
    });

    // Subtitle toggle
    elements.subtitlesBtn?.addEventListener('click', () => {
      setSubtitles(state.currentSettings.subtitles === 'off' ? 'showing' : 'off');
    });

    // Settings options
    $$('[data-speed]').forEach(btn => {
      btn.addEventListener('click', () => {
        const speed = parseFloat(btn.dataset.speed);
        setPlaybackSpeed(speed);
        toggleSettings();
      });
    });

    // Fullscreen change
    document.addEventListener('fullscreenchange', () => {
      state.isFullscreen = !!document.fullscreenElement;
      if (!document.fullscreenElement) {
        elements.container?.classList.remove('mu-player-container--fullscreen');
      }
    });

    // Click on container to toggle play
    elements.container?.addEventListener('click', (e) => {
      if (e.target.closest('.mu-player-overlay--top') ||
          e.target.closest('.mu-player-overlay--bottom') ||
          e.target.closest('.mu-player-controls') ||
          e.target.closest('button') ||
          e.target.closest('input')) return;
      togglePlay();
    });

    // Mouse move for idle detection
    elements.container?.addEventListener('mousemove', resetIdleTimer);

    // Favorite button
    const favBtn = $('.mu-watch-btn--fav');
    favBtn?.addEventListener('click', () => {
      const postId = elements.wrapper?.dataset.postId;
      if (postId) {
        toggleFavorite(postId, favBtn);
      }
    });

    // Info/More button to toggle content panel
    const infoBtn = $('.mu-player-ctrl-btn[data-action="info"]');
    infoBtn?.addEventListener('click', toggleContentPanel);

    // Initialize favorite state
    initFavoriteState();
  }

  // ============================================================
  // FAVORITES
  // ============================================================
  function initFavoriteState() {
    const postId = elements.wrapper?.dataset.postId;
    const favBtn = $('.mu-watch-btn--fav');
    if (!postId || !favBtn) return;

    const favs = localStorageGet('mu_favorites', []);
    if (favs.includes(String(postId))) {
      favBtn.classList.add('is-on');
    }
  }

  function toggleFavorite(postId, btn) {
    if (!postId) return;

    const favs = new Set(localStorageGet('mu_favorites', []));
    const isAdding = !favs.has(String(postId));

    if (isAdding) {
      favs.add(String(postId));
    } else {
      favs.delete(String(postId));
    }

    localStorageSet('mu_favorites', Array.from(favs));
    btn.classList.toggle('is-on', isAdding);

    // Sync to server
    if (window.MOVIE_UI?.isLoggedIn) {
      syncFavoriteToServer(postId, isAdding);
    }

    showToast(isAdding ? 'Added to My List' : 'Removed from My List');
  }

  function syncFavoriteToServer(postId, isAdding) {
    const fd = new FormData();
    fd.append('action', 'toggle_favorite');
    fd.append('movie_id', postId);
    fd.append('nonce', window.MOVIE_UI.favNonce || window.MOVIE_UI.nonce || '');

    fetch(window.MOVIE_UI.ajaxUrl || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    }).catch(() => {});
  }

  // ============================================================
  // KEYBOARD SHORTCUTS
  // ============================================================
  function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      // Only if not in an input field
      if (e.target.matches('input, textarea')) return;

      switch (e.key) {
        case ' ':
        case 'k':
          e.preventDefault();
          togglePlay();
          showToast(state.isPlaying ? 'Paused' : 'Playing');
          break;

        case 'ArrowLeft':
          e.preventDefault();
          seek(-10);
          showToast('-10s');
          break;

        case 'ArrowRight':
          e.preventDefault();
          seek(10);
          showToast('+10s');
          break;

        case 'f':
        case 'F':
          e.preventDefault();
          toggleFullscreen();
          showToast(state.isFullscreen ? 'Exit fullscreen' : 'Fullscreen');
          break;

        case 'm':
        case 'M':
          e.preventDefault();
          toggleMute();
          showToast(state.isMuted ? 'Muted' : `Volume: ${Math.round(state.volume * 100)}%`);
          break;

        case 'Escape':
          if (state.isSettingsOpen) {
            toggleSettings();
          }
          if (state.isInfoVisible) {
            toggleContentPanel();
          }
          break;

        case 'i':
        case 'I':
          e.preventDefault();
          toggleContentPanel();
          break;

        case 'ArrowUp':
          e.preventDefault();
          setVolume(Math.min(1, state.volume + 0.1));
          showToast(`Volume: ${Math.round(state.volume * 100)}%`);
          break;

        case 'ArrowDown':
          e.preventDefault();
          setVolume(Math.max(0, state.volume - 0.1));
          showToast(`Volume: ${Math.round(state.volume * 100)}%`);
          break;
      }
    });
  }

  // ============================================================
  // EPISODES PANEL
  // ============================================================
  function initEpisodesPanel() {
    const seasonSelect = $('.mu-watch-season-select');
    const episodeCards = $$('.mu-watch-episode-card');

    if (!seasonSelect || episodeCards.length === 0) return;

    // Highlight current episode
    const currentPostId = elements.wrapper?.dataset.postId;
    episodeCards.forEach(card => {
      if (card.dataset.episodeId === currentPostId) {
        card.classList.add('is-active');
      }
    });

    // Season change
    seasonSelect.addEventListener('change', () => {
      const season = seasonSelect.value;
      episodeCards.forEach(card => {
        card.style.display = card.dataset.season === season ? 'flex' : 'none';
      });
    });
  }

  // ============================================================
  // SKIP INDICATOR
  // ============================================================
  function showSkipIndicator(type) {
    const indicator = type === 'rewind'
      ? elements.rewindBtn?.querySelector('.mu-player-skip-indicator')
      : elements.forwardBtn?.querySelector('.mu-player-skip-indicator');

    if (indicator) {
      indicator.classList.add('mu-player-skip-indicator--show');
      setTimeout(() => {
        indicator.classList.remove('mu-player-skip-indicator--show');
      }, 500);
    }
  }

  // ============================================================
  // INITIALIZE
  // ============================================================
  function init() {
    initElements();
    loadSettings();
    initPlayer();
    setupEventListeners();
    setupKeyboardShortcuts();
    initHeaderFade();
    initEpisodesPanel();
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // ============================================================
  // PUBLIC API (for external use)
  // ============================================================
  window.MUPlayer = {
    play: () => elements.video?.play(),
    pause: () => elements.video?.pause(),
    toggle: togglePlay,
    seek: seek,
    fullscreen: toggleFullscreen,
    setSpeed: setPlaybackSpeed,
    setSubtitles: setSubtitles,
    getState: () => ({ ...state })
  };

})();
