/**
 * ============================================================
 * Premium OTT Profile Page JavaScript
 * Interactive components and data management
 * ============================================================
 */

(function() {
  'use strict';

  // ============================================================
  // INITIALIZATION
  // ============================================================
  document.addEventListener('DOMContentLoaded', function() {
    initProfilePage();
  });

  function initProfilePage() {
    initStatCounters();
    initCarousel();
    initGenreChips();
    initSettingsMenu();
    initAddToList();
    initProfileSwitching();
    initAvatarEdit();
    initContinueWatching();
  }

  // ============================================================
  // ANIMATED STAT COUNTERS
  // ============================================================
  function initStatCounters() {
    const counters = document.querySelectorAll('.mp-stat-value[data-count]');
    
    counters.forEach(counter => {
      const target = parseInt(counter.dataset.count, 10);
      const duration = 1500;
      const step = target / (duration / 16);
      let current = 0;
      
      const updateCounter = () => {
        current += step;
        if (current < target) {
          counter.textContent = Math.floor(current).toLocaleString();
          requestAnimationFrame(updateCounter);
        } else {
          counter.textContent = target.toLocaleString();
        }
      };
      
      // Start animation when in view
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            updateCounter();
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });
      
      observer.observe(counter);
    });
  }

  // ============================================================
  // CAROUSEL / SWIPER INITIALIZATION
  // ============================================================
  function initCarousel() {
    const carousels = document.querySelectorAll('.mp-carousel');
    
    carousels.forEach(carousel => {
      if (typeof Swiper !== 'undefined') {
        new Swiper(carousel.querySelector('.swiper'), {
          slidesPerView: 'auto',
          spaceBetween: 16,
          grabCursor: true,
          keyboard: {
            enabled: true,
          },
          navigation: {
            nextEl: carousel.querySelector('.swiper-button-next'),
            prevEl: carousel.querySelector('.swiper-button-prev'),
          },
          breakpoints: {
            320: { slidesPerView: 2, spaceBetween: 12 },
            480: { slidesPerView: 3, spaceBetween: 12 },
            768: { slidesPerView: 4, spaceBetween: 16 },
            1024: { slidesPerView: 5, spaceBetween: 16 },
            1280: { slidesPerView: 6, spaceBetween: 20 },
          },
        });
      }
    });
  }

  // ============================================================
  // GENRE CHIPS SELECTION
  // ============================================================
  function initGenreChips() {
    const chips = document.querySelectorAll('.mp-genre-chip');
    const saveBtn = document.querySelector('[data-action="save-genres"]');
    
    chips.forEach(chip => {
      chip.addEventListener('click', function() {
        this.classList.toggle('selected');
        updateSelectedGenres();
      });
    });
    
    if (saveBtn) {
      saveBtn.addEventListener('click', saveGenres);
    }
    
    // Load saved genres from localStorage
    loadSavedGenres();
  }

  function updateSelectedGenres() {
    const selected = document.querySelectorAll('.mp-genre-chip.selected');
    const count = selected.length;
    const counter = document.querySelector('.mp-genres-count');
    if (counter) {
      counter.textContent = count;
    }
  }

  function loadSavedGenres() {
    const saved = localStorage.getItem('mp_preferred_genres');
    if (saved) {
      const genres = JSON.parse(saved);
      genres.forEach(genreId => {
        const chip = document.querySelector(`.mp-genre-chip[data-genre="${genreId}"]`);
        if (chip) chip.classList.add('selected');
      });
      updateSelectedGenres();
    }
  }

  function saveGenres() {
    const selected = document.querySelectorAll('.mp-genre-chip.selected');
    const genres = Array.from(selected).map(chip => chip.dataset.genre);
    localStorage.setItem('mp_preferred_genres', JSON.stringify(genres));
    
    // Show toast
    if (window.MovieUIToast) {
      MovieUIToast.show('Preferences saved!', 'success');
    }
  }

  // ============================================================
  // SETTINGS MENU INTERACTIONS
  // ============================================================
  function initSettingsMenu() {
    const menuItems = document.querySelectorAll('.mp-settings-menu__item');
    
    menuItems.forEach(item => {
      item.addEventListener('click', function(e) {
        const action = this.dataset.action;
        if (!action) return;
        
        // Update active state
        menuItems.forEach(mi => mi.classList.remove('active'));
        this.classList.add('active');
        
        // Handle different actions
        handleSettingsAction(action, this);
      });
    });
  }

  function handleSettingsAction(action, element) {
    const contentArea = document.querySelector('.mp-settings-content');
    
    switch(action) {
      case 'edit-profile':
        showEditProfileModal();
        break;
      case 'change-password':
        showChangePasswordModal();
        break;
      case 'notifications':
        showNotificationSettings();
        break;
      case 'playback':
        showPlaybackSettings();
        break;
      case 'logout':
        if (confirm('Are you sure you want to sign out?')) {
          window.location.href = element.dataset.href || '/wp-login.php?action=logout';
        }
        break;
      default:
        if (contentArea) {
          contentArea.innerHTML = `<div class="mp-empty-state">
            <h3>Coming Soon</h3>
            <p>This feature is under development.</p>
          </div>`;
        }
    }
  }

  // ============================================================
  // ADD TO LIST FUNCTIONALITY
  // ============================================================
  function initAddToList() {
    document.addEventListener('click', function(e) {
      const addBtn = e.target.closest('.mp-movie-card__add');
      if (!addBtn) return;
      
      e.preventDefault();
      e.stopPropagation();
      
      const card = addBtn.closest('.mp-movie-card');
      const itemId = card.dataset.id;
      const itemType = card.dataset.type || 'movie';
      
      toggleFavorite(itemId, itemType, addBtn);
    });
    
    // Load saved states
    loadFavoriteStates();
  }

  function toggleFavorite(id, type, btn) {
    const key = `mp_favorite_${type}_${id}`;
    const isFavorited = localStorage.getItem(key) === 'true';
    
    if (isFavorited) {
      localStorage.removeItem(key);
      btn.classList.remove('added');
      btn.innerHTML = getPlusIcon();
      showToast('Removed from My List');
    } else {
      localStorage.setItem(key, 'true');
      btn.classList.add('added');
      btn.innerHTML = getCheckIcon();
      showToast('Added to My List');
    }
    
    // Update count in stats
    updateFavoriteCount();
  }

  function loadFavoriteStates() {
    const cards = document.querySelectorAll('.mp-movie-card');
    cards.forEach(card => {
      const id = card.dataset.id;
      const type = card.dataset.type || 'movie';
      const key = `mp_favorite_${type}_${id}`;
      const btn = card.querySelector('.mp-movie-card__add');
      
      if (localStorage.getItem(key) === 'true' && btn) {
        btn.classList.add('added');
        btn.innerHTML = getCheckIcon();
      }
    });
  }

  function updateFavoriteCount() {
    let count = 0;
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith('mp_favorite_') && localStorage.getItem(key) === 'true') {
        count++;
      }
    }
    const counter = document.querySelector('[data-stat="favorites"]');
    if (counter) {
      counter.textContent = count;
    }
  }

  // ============================================================
  // PROFILE SWITCHING
  // ============================================================
  function initProfileSwitching() {
    const profiles = document.querySelectorAll('.mp-profile-item:not(.mp-add-profile)');
    const addBtn = document.querySelector('.mp-add-profile');
    
    profiles.forEach(profile => {
      profile.addEventListener('click', function() {
        const profileId = this.dataset.profileId;
        switchProfile(profileId);
      });
    });
    
    if (addBtn) {
      addBtn.addEventListener('click', showAddProfileModal);
    }
  }

  function switchProfile(profileId) {
    localStorage.setItem('mp_active_profile', profileId);
    
    // Update UI
    document.querySelectorAll('.mp-profile-item').forEach(p => {
      p.classList.remove('active');
    });
    document.querySelector(`[data-profile-id="${profileId}"]`).classList.add('active');
    
    // Reload content for this profile
    loadProfileContent(profileId);
    
    showToast('Profile switched');
  }

  function loadProfileContent(profileId) {
    // In a real app, this would fetch data for the selected profile
    console.log('Loading content for profile:', profileId);
  }

  // ============================================================
  // AVATAR EDIT
  // ============================================================
  function initAvatarEdit() {
    const editBtn = document.querySelector('.mp-avatar-overlay');
    if (editBtn) {
      editBtn.addEventListener('click', showAvatarUploadModal);
    }
  }

  function showAvatarUploadModal() {
    const modal = createModal(`
      <h2>Change Avatar</h2>
      <p>Upload a new profile picture</p>
      <div class="mp-avatar-options">
        ${generateAvatarOptions()}
      </div>
    `);
    document.body.appendChild(modal);
  }

  function generateAvatarOptions() {
    const colors = ['#e50914', '#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#00acc1'];
    let html = '<div class="mp-avatar-colors">';
    colors.forEach((color, i) => {
      html += `<button class="mp-avatar-color" style="background:${color}" data-color="${color}"></button>`;
    });
    html += '</div>';
    return html;
  }

  // ============================================================
  // CONTINUE WATCHING
  // ============================================================
  function initContinueWatching() {
    const cards = document.querySelectorAll('.mp-continue-card');
    
    cards.forEach(card => {
      card.addEventListener('click', function() {
        const itemId = this.dataset.id;
        const progress = localStorage.getItem(`mp_progress_${itemId}`) || 0;
        const watchUrl = this.dataset.watchUrl;
        
        // Navigate with resume parameter
        const url = new URL(watchUrl, window.location.origin);
        url.searchParams.set('id', itemId);
        if (progress > 0) {
          url.searchParams.set('t', progress);
        }
        window.location.href = url.toString();
      });
    });
  }

  // ============================================================
  // MODALS
  // ============================================================
  function createModal(content) {
    const modal = document.createElement('div');
    modal.className = 'mp-modal';
    modal.innerHTML = `
      <div class="mp-modal__backdrop"></div>
      <div class="mp-modal__content">
        ${content}
        <button class="mp-modal__close">&times;</button>
      </div>
    `;
    
    modal.querySelector('.mp-modal__backdrop').addEventListener('click', () => modal.remove());
    modal.querySelector('.mp-modal__close').addEventListener('click', () => modal.remove());
    
    return modal;
  }

  function showEditProfileModal() {
    const modal = createModal(`
      <h2>Edit Profile</h2>
      <form id="edit-profile-form">
        <div class="mp-form-group">
          <label>Display Name</label>
          <input type="text" name="display_name" value="${getCurrentUserName()}" />
        </div>
        <div class="mp-form-group">
          <label>Bio</label>
          <textarea name="bio" placeholder="Tell us about yourself..."></textarea>
        </div>
        <button type="submit" class="mp-btn mp-btn--primary">Save Changes</button>
      </form>
    `);
    document.body.appendChild(modal);
    
    document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
      e.preventDefault();
      saveProfile(this);
      modal.remove();
    });
  }

  function showChangePasswordModal() {
    const modal = createModal(`
      <h2>Change Password</h2>
      <form id="change-password-form">
        <div class="mp-form-group">
          <label>Current Password</label>
          <input type="password" name="current_password" required />
        </div>
        <div class="mp-form-group">
          <label>New Password</label>
          <input type="password" name="new_password" required />
        </div>
        <div class="mp-form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" required />
        </div>
        <button type="submit" class="mp-btn mp-btn--primary">Update Password</button>
      </form>
    `);
    document.body.appendChild(modal);
  }

  function showNotificationSettings() {
    const modal = createModal(`
      <h2>Notification Settings</h2>
      <div class="mp-toggle-group">
        <label class="mp-toggle">
          <input type="checkbox" checked />
          <span>Email notifications</span>
        </label>
        <label class="mp-toggle">
          <input type="checkbox" checked />
          <span>New content alerts</span>
        </label>
        <label class="mp-toggle">
          <input type="checkbox" />
          <span>Marketing emails</span>
        </label>
      </div>
      <button class="mp-btn mp-btn--primary">Save Preferences</button>
    `);
    document.body.appendChild(modal);
  }

  function showPlaybackSettings() {
    const modal = createModal(`
      <h2>Playback Settings</h2>
      <div class="mp-form-group">
        <label>Video Quality</label>
        <select name="quality">
          <option value="auto">Auto</option>
          <option value="1080p">1080p HD</option>
          <option value="720p">720p</option>
          <option value="480p">480p</option>
        </select>
      </div>
      <div class="mp-form-group">
        <label>Subtitle Language</label>
        <select name="subtitles">
          <option value="en">English</option>
          <option value="vi">Tiếng Việt</option>
        </select>
      </div>
      <button class="mp-btn mp-btn--primary">Save Settings</button>
    `);
    document.body.appendChild(modal);
  }

  function showAddProfileModal() {
    const modal = createModal(`
      <h2>Add Profile</h2>
      <form id="add-profile-form">
        <div class="mp-form-group">
          <label>Profile Name</label>
          <input type="text" name="name" placeholder="Enter name" required />
        </div>
        <div class="mp-form-group">
          <label>Profile Type</label>
          <select name="type">
            <option value="normal">Normal</option>
            <option value="kids">Kids</option>
          </select>
        </div>
        <button type="submit" class="mp-btn mp-btn--primary">Create Profile</button>
      </form>
    `);
    document.body.appendChild(modal);
    
    document.getElementById('add-profile-form').addEventListener('submit', function(e) {
      e.preventDefault();
      modal.remove();
      showToast('Profile created!');
    });
  }

  // ============================================================
  // UTILITY FUNCTIONS
  // ============================================================
  function getCurrentUserName() {
    const el = document.querySelector('.mp-username');
    return el ? el.textContent : '';
  }

  function showToast(message, type = 'info') {
    if (window.MovieUIToast) {
      MovieUIToast.show(message, type);
    } else {
      alert(message);
    }
  }

  function saveProfile(form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // In a real app, save via AJAX
    console.log('Saving profile:', data);
    showToast('Profile updated!');
    
    // Update UI
    const nameEl = document.querySelector('.mp-username');
    if (nameEl && data.display_name) {
      nameEl.textContent = data.display_name;
    }
  }

  // ============================================================
  // SVG ICONS
  // ============================================================
  function getPlusIcon() {
    return `<svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`;
  }

  function getCheckIcon() {
    return `<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`;
  }

  // ============================================================
  // DATA MANAGEMENT
  // ============================================================
  const ProfileData = {
    // Get watch history
    getHistory() {
      const data = localStorage.getItem('mp_watch_history');
      return data ? JSON.parse(data) : [];
    },
    
    // Add to watch history
    addToHistory(item) {
      const history = this.getHistory();
      const existing = history.findIndex(h => h.id === item.id);
      
      if (existing >= 0) {
        history[existing] = { ...history[existing], ...item, watchedAt: Date.now() };
      } else {
        history.unshift({ ...item, watchedAt: Date.now() });
      }
      
      // Keep only last 50 items
      if (history.length > 50) {
        history.length = 50;
      }
      
      localStorage.setItem('mp_watch_history', JSON.stringify(history));
    },
    
    // Get progress for an item
    getProgress(itemId) {
      const key = `mp_progress_${itemId}`;
      return parseInt(localStorage.getItem(key) || '0', 10);
    },
    
    // Save progress
    saveProgress(itemId, seconds) {
      localStorage.setItem(`mp_progress_${itemId}`, seconds.toString());
    },
    
    // Get favorites
    getFavorites() {
      const data = localStorage.getItem('mp_favorites');
      return data ? JSON.parse(data) : [];
    },
    
    // Toggle favorite
    toggleFavorite(id, type) {
      const favorites = this.getFavorites();
      const index = favorites.findIndex(f => f.id === id && f.type === type);
      
      if (index >= 0) {
        favorites.splice(index, 1);
      } else {
        favorites.push({ id, type, addedAt: Date.now() });
      }
      
      localStorage.setItem('mp_favorites', JSON.stringify(favorites));
      return index < 0;
    },
  };

  // Expose globally
  window.ProfileData = ProfileData;

})();
