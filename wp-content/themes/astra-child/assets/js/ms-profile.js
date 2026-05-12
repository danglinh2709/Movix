/**
 * ============================================================
 * Premium OTT Profile Page JavaScript - Redesigned 2024
 * Dark Cinematic Theme - Netflix Style
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
    initAccountMenu();
    initMovieCards();
    initGenreChips();
    initPreferences();
    initProfiles();
    initSubscription();
    initHeroButtons();
    initModals();
    initToast();
    initScrollBehavior();
  }

  // ============================================================
  // ACCOUNT MENU
  // ============================================================
  function initAccountMenu() {
    const menuItems = document.querySelectorAll('.pp-account-menu__item');
    
    menuItems.forEach(item => {
      item.addEventListener('click', function() {
        const action = this.dataset.action;
        const href = this.dataset.href;
        
        // Update active state
        menuItems.forEach(mi => mi.classList.remove('pp-account-menu__item--active'));
        this.classList.add('pp-account-menu__item--active');
        
        // Handle different actions
        handleAccountAction(action, href, this);
      });
    });
  }

  function handleAccountAction(action, href, element) {
    switch(action) {
      case 'edit-profile':
        showEditProfileModal();
        break;
      case 'change-password':
        showChangePasswordModal();
        break;
      case 'email-preferences':
        showEmailPreferencesModal();
        break;
      case 'playback-settings':
        showPlaybackSettingsModal();
        break;
      case 'parental-controls':
        showParentalControlsModal();
        break;
      case 'payment-methods':
        showPaymentMethodsModal();
        break;
      case 'subscription-plan':
        scrollToElement('subscription');
        break;
      case 'manage-devices':
        showManageDevicesModal();
        break;
      case 'notification-settings':
        showNotificationSettingsModal();
        break;
      case 'privacy-settings':
        showPrivacySettingsModal();
        break;
      case 'language':
        showLanguageModal();
        break;
      case 'logout':
        showLogoutConfirm(href);
        break;
      default:
        showToast('Feature coming soon!', 'info');
    }
  }

  // ============================================================
  // MOVIE CARDS
  // ============================================================
  function initMovieCards() {
    const cards = document.querySelectorAll('.pp-movie-card');
    
    cards.forEach(card => {
      card.addEventListener('click', function(e) {
        // Ignore if clicking play button
        if (e.target.closest('.pp-movie-card__play')) {
          e.preventDefault();
          e.stopPropagation();
          handlePlay(this);
          return;
        }
        
        // Handle card click
        const id = this.dataset.id;
        const type = this.dataset.type;
        
        if (type === 'tv') {
          showToast('Navigating to TV show...', 'info');
        } else {
          showToast('Navigating to movie...', 'info');
        }
      });
    });
  }

  function handlePlay(card) {
    const id = card.dataset.id;
    const type = card.dataset.type || 'movie';
    const title = card.querySelector('.pp-movie-card__title')?.textContent || 'Content';
    
    // In a real app, this would navigate to the watch page
    showToast('Playing: ' + title, 'success');
    
    // Example navigation:
    // window.location.href = '/watch?id=' + id + '&type=' + type;
  }

  // ============================================================
  // GENRE CHIPS
  // ============================================================
  function initGenreChips() {
    const chips = document.querySelectorAll('.pp-tag[data-action="toggle-genre"]');
    const addGenreBtn = document.querySelector('.pp-tag[data-action="add-genre"]');
    
    chips.forEach(chip => {
      chip.addEventListener('click', function() {
        this.classList.toggle('pp-tag--active');
        saveGenres();
      });
    });
    
    if (addGenreBtn) {
      addGenreBtn.addEventListener('click', showAddGenreModal);
    }
    
    // Load saved genres
    loadSavedGenres();
  }

  function saveGenres() {
    const selected = document.querySelectorAll('.pp-tag[data-action="toggle-genre"].pp-tag--active');
    const genres = Array.from(selected).map(chip => chip.dataset.genre);
    localStorage.setItem('pp_preferred_genres', JSON.stringify(genres));
    showToast('Preferences saved!', 'success');
  }

  function loadSavedGenres() {
    const saved = localStorage.getItem('pp_preferred_genres');
    if (saved) {
      const genres = JSON.parse(saved);
      genres.forEach(genreId => {
        const chip = document.querySelector(`.pp-tag[data-genre="${genreId}"]`);
        if (chip) chip.classList.add('pp-tag--active');
      });
    } else {
      // Default genres from PHP
      const defaultChips = document.querySelectorAll('.pp-tag.pp-tag--active');
      defaultChips.forEach(chip => {
        const genreId = chip.dataset.genre;
        const genres = Array.from(defaultChips).map(c => c.dataset.genre);
        localStorage.setItem('pp_preferred_genres', JSON.stringify(genres));
      });
    }
  }

  // ============================================================
  // PREFERENCES
  // ============================================================
  function initPreferences() {
    const contentLang = document.getElementById('content-language');
    const subtitleLang = document.getElementById('subtitle-language');
    
    if (contentLang) {
      contentLang.addEventListener('change', function() {
        localStorage.setItem('pp_content_language', this.value);
        showToast('Content language updated!', 'success');
      });
      
      // Load saved
      const saved = localStorage.getItem('pp_content_language');
      if (saved) contentLang.value = saved;
    }
    
    if (subtitleLang) {
      subtitleLang.addEventListener('change', function() {
        localStorage.setItem('pp_subtitle_language', this.value);
        showToast('Subtitle language updated!', 'success');
      });
      
      // Load saved
      const saved = localStorage.getItem('pp_subtitle_language');
      if (saved) subtitleLang.value = saved;
    }
  }

  // ============================================================
  // PROFILES
  // ============================================================
  function initProfiles() {
    const profileItems = document.querySelectorAll('.pp-profile-item[data-action="edit-profile-item"]');
    const addProfileBtn = document.querySelector('.pp-add-profile-btn');
    
    profileItems.forEach(item => {
      item.addEventListener('click', function(e) {
        if (e.target.closest('.pp-profile-item__edit')) {
          e.stopPropagation();
          const profileId = this.dataset.profileId;
          showEditProfileItemModal(profileId);
        } else {
          switchProfile(this.dataset.profileId);
        }
      });
    });
    
    if (addProfileBtn) {
      addProfileBtn.addEventListener('click', showAddProfileModal);
    }
  }

  function switchProfile(profileId) {
    const profileName = document.querySelector(`.pp-profile-item[data-profile-id="${profileId}"] .pp-profile-item__name`)?.textContent;
    
    // Update UI
    document.querySelectorAll('.pp-profile-item').forEach(p => {
      p.classList.remove('pp-profile-item--active');
    });
    document.querySelector(`.pp-profile-item[data-profile-id="${profileId}"]`)?.classList.add('pp-profile-item--active');
    
    showToast('Switched to: ' + profileName, 'success');
    localStorage.setItem('pp_active_profile', profileId);
  }

  // ============================================================
  // SUBSCRIPTION
  // ============================================================
  function initSubscription() {
    const manageBtn = document.querySelector('.pp-subscription-card__btn');
    
    if (manageBtn) {
      manageBtn.addEventListener('click', showManageSubscriptionModal);
    }
  }

  // ============================================================
  // HERO BUTTONS
  // ============================================================
  function initHeroButtons() {
    const editProfileBtn = document.querySelector('.pp-hero__edit-btn');
    const editAvatarBtn = document.querySelector('.pp-hero__avatar-edit');
    
    if (editProfileBtn) {
      editProfileBtn.addEventListener('click', showEditProfileModal);
    }
    
    if (editAvatarBtn) {
      editAvatarBtn.addEventListener('click', showEditAvatarModal);
    }
  }

  // ============================================================
  // MODALS
  // ============================================================
  function initModals() {
    const backdrop = document.getElementById('pp-modal-backdrop');
    
    // Close on backdrop click
    if (backdrop) {
      backdrop.addEventListener('click', function(e) {
        if (e.target === backdrop) {
          closeModal();
        }
      });
    }
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeModal();
      }
    });
  }

  function showModal(title, content) {
    const backdrop = document.getElementById('pp-modal-backdrop');
    const modalTitle = document.getElementById('pp-modal-title');
    const modalBody = document.getElementById('pp-modal-body');
    
    if (backdrop && modalTitle && modalBody) {
      modalTitle.textContent = title;
      modalBody.innerHTML = content;
      backdrop.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  window.closeModal = function() {
    const backdrop = document.getElementById('pp-modal-backdrop');
    if (backdrop) {
      backdrop.classList.remove('active');
      document.body.style.overflow = '';
    }
  };

  // Edit Profile Modal
  function showEditProfileModal() {
    const currentName = document.querySelector('.pp-hero__name')?.textContent || '';
    const currentBio = document.querySelector('.pp-hero__bio')?.textContent || '';
    
    showModal('Edit Profile', `
      <form id="edit-profile-form">
        <div class="pp-form-group">
          <label>Display Name</label>
          <input type="text" class="pp-form-input" name="display_name" value="${escapeHtml(currentName)}" required>
        </div>
        <div class="pp-form-group">
          <label>Email</label>
          <input type="email" class="pp-form-input" name="email" placeholder="your@email.com">
        </div>
        <div class="pp-form-group">
          <label>Bio</label>
          <textarea class="pp-form-input pp-form-textarea" name="bio" placeholder="Tell us about yourself...">${escapeHtml(currentBio)}</textarea>
        </div>
        <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
          <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="pp-btn pp-btn--primary">Save Changes</button>
        </div>
      </form>
    `);
    
    document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      saveProfile(formData);
      closeModal();
    });
  }

  function saveProfile(formData) {
    const data = Object.fromEntries(formData);
    
    // Update UI
    if (data.display_name) {
      const nameEl = document.querySelector('.pp-hero__name');
      if (nameEl) nameEl.textContent = data.display_name;
      
      const accountNameEl = document.querySelector('.pp-account-card__name');
      if (accountNameEl) accountNameEl.textContent = data.display_name;
    }
    
    if (data.bio) {
      const bioEl = document.querySelector('.pp-hero__bio');
      if (bioEl) bioEl.textContent = data.bio;
    }
    
    // Save to localStorage
    localStorage.setItem('pp_profile', JSON.stringify(data));
    
    showToast('Profile updated successfully!', 'success');
  }

  // Change Password Modal
  function showChangePasswordModal() {
    showModal('Change Password', `
      <form id="change-password-form">
        <div class="pp-form-group">
          <label>Current Password</label>
          <input type="password" class="pp-form-input" name="current_password" required>
        </div>
        <div class="pp-form-group">
          <label>New Password</label>
          <input type="password" class="pp-form-input" name="new_password" required minlength="8">
        </div>
        <div class="pp-form-group">
          <label>Confirm New Password</label>
          <input type="password" class="pp-form-input" name="confirm_password" required>
        </div>
        <div id="password-error" class="pp-form-error" style="display: none;"></div>
        <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
          <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="pp-btn pp-btn--primary">Update Password</button>
        </div>
      </form>
    `);
    
    document.getElementById('change-password-form').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const newPass = formData.get('new_password');
      const confirmPass = formData.get('confirm_password');
      
      if (newPass !== confirmPass) {
        document.getElementById('password-error').textContent = 'Passwords do not match!';
        document.getElementById('password-error').style.display = 'block';
        return;
      }
      
      closeModal();
      showToast('Password updated successfully!', 'success');
    });
  }

  // Email Preferences Modal
  function showEmailPreferencesModal() {
    showModal('Email Preferences', `
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">New Releases</div>
          <div class="pp-toggle__desc">Get notified about new movies and TV shows</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Recommendations</div>
          <div class="pp-toggle__desc">Personalized content recommendations</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Account Updates</div>
          <div class="pp-toggle__desc">Important changes to your account</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Marketing Emails</div>
          <div class="pp-toggle__desc">Special offers and promotions</div>
        </div>
        <div class="pp-toggle__switch" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
        <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="pp-btn pp-btn--primary" onclick="closeModal(); showToast('Email preferences saved!', 'success');">Save</button>
      </div>
    `);
  }

  // Playback Settings Modal
  function showPlaybackSettingsModal() {
    const savedQuality = localStorage.getItem('pp_playback_quality') || 'auto';
    const savedAutoPlay = localStorage.getItem('pp_autoplay') || 'true';
    
    showModal('Playback Settings', `
      <div class="pp-form-group">
        <label>Video Quality</label>
        <select class="pp-select" id="playback-quality">
          <option value="auto" ${savedQuality === 'auto' ? 'selected' : ''}>Auto (Recommended)</option>
          <option value="4k" ${savedQuality === '4k' ? 'selected' : ''}>4K Ultra HD</option>
          <option value="1080p" ${savedQuality === '1080p' ? 'selected' : ''}>1080p HD</option>
          <option value="720p" ${savedQuality === '720p' ? 'selected' : ''}>720p HD</option>
          <option value="480p" ${savedQuality === '480p' ? 'selected' : ''}>480p</option>
        </select>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Auto-play</div>
          <div class="pp-toggle__desc">Automatically play next episode</div>
        </div>
        <div class="pp-toggle__switch ${savedAutoPlay === 'true' ? 'active' : ''}" id="autoplay-toggle" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
        <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="pp-btn pp-btn--primary" onclick="savePlaybackSettings()">Save</button>
      </div>
    `);
  }

  window.savePlaybackSettings = function() {
    const quality = document.getElementById('playback-quality').value;
    const autoplay = document.getElementById('autoplay-toggle').classList.contains('active');
    
    localStorage.setItem('pp_playback_quality', quality);
    localStorage.setItem('pp_autoplay', autoplay.toString());
    
    closeModal();
    showToast('Playback settings saved!', 'success');
  };

  // Parental Controls Modal
  function showParentalControlsModal() {
    const pinEnabled = localStorage.getItem('pp_parental_pin') || '';
    
    showModal('Parental Controls', `
      <div class="pp-form-group">
        <label>PIN Protection</label>
        <input type="password" class="pp-form-input" id="parental-pin" placeholder="Enter 4-digit PIN" maxlength="4" pattern="[0-9]*" inputmode="numeric" value="${pinEnabled}">
        <p class="pp-select-hint">Set a PIN to restrict mature content</p>
      </div>
      <div class="pp-form-group">
        <label>Content Restriction</label>
        <select class="pp-select" id="content-restriction">
          <option value="all">All Content</option>
          <option value="pg">PG / TV-PG</option>
          <option value="pg13">PG-13 / TV-13</option>
          <option value="kids">Kids Only</option>
        </select>
      </div>
      <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
        <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="pp-btn pp-btn--primary" onclick="saveParentalControls()">Save</button>
      </div>
    `);
  }

  window.saveParentalControls = function() {
    const pin = document.getElementById('parental-pin').value;
    const restriction = document.getElementById('content-restriction').value;
    
    localStorage.setItem('pp_parental_pin', pin);
    localStorage.setItem('pp_content_restriction', restriction);
    
    closeModal();
    showToast('Parental controls saved!', 'success');
  };

  // Payment Methods Modal
  function showPaymentMethodsModal() {
    showModal('Payment Methods', `
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--pp-bg-2); border-radius: 12px; border: 1px solid var(--pp-border);">
          <div style="width: 48px; height: 32px; background: linear-gradient(135deg, #1a1f71, #0d47a1); border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px; font-weight: bold;">VISA</div>
          <div style="flex: 1;">
            <div style="font-weight: 600;">•••• •••• •••• 4242</div>
            <div style="font-size: 12px; color: var(--pp-muted);">Expires 12/26</div>
          </div>
          <div style="padding: 4px 12px; background: var(--pp-accent-green); color: #000; border-radius: 4px; font-size: 11px; font-weight: 700;">DEFAULT</div>
        </div>
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--pp-bg-2); border-radius: 12px; border: 1px solid var(--pp-border);">
          <div style="width: 48px; height: 32px; background: #f7931e; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px; font-weight: bold;">AMEX</div>
          <div style="flex: 1;">
            <div style="font-weight: 600;">•••• •••••• •3827</div>
            <div style="font-size: 12px; color: var(--pp-muted);">Expires 08/25</div>
          </div>
        </div>
        <button style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; background: transparent; border: 2px dashed var(--pp-border); border-radius: 12px; color: var(--pp-text-secondary); cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--pp-accent)'; this.style.color='var(--pp-accent)'" onmouseout="this.style.borderColor='var(--pp-border)'; this.style.color='var(--pp-text-secondary)'">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Payment Method
        </button>
      </div>
    `);
  }

  // Manage Devices Modal
  function showManageDevicesModal() {
    showModal('Manage Devices', `
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--pp-bg-2); border-radius: 12px; border: 1px solid var(--pp-border);">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--pp-accent);"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          <div style="flex: 1;">
            <div style="font-weight: 600;">MacBook Pro</div>
            <div style="font-size: 12px; color: var(--pp-muted);">San Francisco, CA • Last active now</div>
          </div>
          <div style="padding: 6px 14px; background: var(--pp-accent-green); color: #000; border-radius: 6px; font-size: 12px; font-weight: 700;">THIS DEVICE</div>
        </div>
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--pp-bg-2); border-radius: 12px; border: 1px solid var(--pp-border);">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--pp-muted);"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
          <div style="flex: 1;">
            <div style="font-weight: 600;">iPhone 15</div>
            <div style="font-size: 12px; color: var(--pp-muted);">San Francisco, CA • Last active 2 hours ago</div>
          </div>
          <button style="padding: 8px 14px; background: transparent; border: 1px solid var(--pp-accent); color: var(--pp-accent); border-radius: 6px; font-size: 12px; cursor: pointer;">Sign Out</button>
        </div>
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--pp-bg-2); border-radius: 12px; border: 1px solid var(--pp-border);">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--pp-muted);"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>
          <div style="flex: 1;">
            <div style="font-weight: 600;">Samsung Smart TV</div>
            <div style="font-size: 12px; color: var(--pp-muted);">Living Room • Last active yesterday</div>
          </div>
          <button style="padding: 8px 14px; background: transparent; border: 1px solid var(--pp-accent); color: var(--pp-accent); border-radius: 6px; font-size: 12px; cursor: pointer;">Sign Out</button>
        </div>
      </div>
    `);
  }

  // Notification Settings Modal
  function showNotificationSettingsModal() {
    showModal('Notification Settings', `
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Push Notifications</div>
          <div class="pp-toggle__desc">Receive push notifications on this device</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">New Content Alerts</div>
          <div class="pp-toggle__desc">Get notified when new content is available</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Recommendation Alerts</div>
          <div class="pp-toggle__desc">Personalized content suggestions</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Download Complete</div>
          <div class="pp-toggle__desc">Notify when downloads finish</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
        <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="pp-btn pp-btn--primary" onclick="closeModal(); showToast('Notification settings saved!', 'success');">Save</button>
      </div>
    `);
  }

  // Privacy Settings Modal
  function showPrivacySettingsModal() {
    showModal('Privacy Settings', `
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Viewing Activity</div>
          <div class="pp-toggle__desc">Show your viewing activity on your profile</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Profile Visibility</div>
          <div class="pp-toggle__desc">Make your profile visible to others</div>
        </div>
        <div class="pp-toggle__switch" onclick="this.classList.toggle('active')"></div>
      </div>
      <div class="pp-toggle">
        <div>
          <div class="pp-toggle__label">Personalized Recommendations</div>
          <div class="pp-toggle__desc">Use your watch history for recommendations</div>
        </div>
        <div class="pp-toggle__switch active" onclick="this.classList.toggle('active')"></div>
      </div>
      <div style="padding: 16px; background: var(--pp-bg-2); border-radius: 12px; margin-top: 16px;">
        <button style="width: 100%; padding: 12px; background: transparent; border: 1px solid var(--pp-accent); color: var(--pp-accent); border-radius: 8px; cursor: pointer; font-weight: 600;">Clear Watch History</button>
      </div>
      <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
        <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="pp-btn pp-btn--primary" onclick="closeModal(); showToast('Privacy settings saved!', 'success');">Save</button>
      </div>
    `);
  }

  // Language Modal
  function showLanguageModal() {
    const languages = ['English', 'Español', 'Français', 'Deutsch', 'Italiano', 'Português', 'Tiếng Việt', '中文', '日本語', '한국어'];
    const currentLang = localStorage.getItem('pp_content_language') || 'english';
    
    let optionsHtml = languages.map(lang => {
      const value = lang.toLowerCase();
      const selected = currentLang === value ? 'selected' : '';
      return `<option value="${value}" ${selected}>${lang}</option>`;
    }).join('');
    
    showModal('Language Settings', `
      <div class="pp-form-group">
        <label>Display Language</label>
        <select class="pp-select" id="display-language-select">
          ${optionsHtml}
        </select>
        <p class="pp-select-hint">This changes the language of the interface</p>
      </div>
      <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
        <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="pp-btn pp-btn--primary" onclick="saveLanguageSettings()">Save</button>
      </div>
    `);
  }

  window.saveLanguageSettings = function() {
    const lang = document.getElementById('display-language-select').value;
    localStorage.setItem('pp_content_language', lang);
    closeModal();
    showToast('Language updated!', 'success');
  };

  // Logout Confirmation
  function showLogoutConfirm(href) {
    showModal('Sign Out', `
      <div style="text-align: center; padding: 20px 0;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--pp-accent)" stroke-width="1.5" style="margin-bottom: 20px;">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        <h3 style="margin-bottom: 12px; font-size: 20px;">Are you sure you want to sign out?</h3>
        <p style="color: var(--pp-muted); margin-bottom: 24px;">You will need to sign in again to access your account.</p>
        <div style="display: flex; gap: 12px;">
          <button class="pp-btn pp-btn--secondary" onclick="closeModal()" style="flex: 1;">Cancel</button>
          <button class="pp-btn pp-btn--primary" onclick="confirmLogout('${href}')" style="flex: 1; background: var(--pp-accent);">Sign Out</button>
        </div>
      </div>
    `);
  }

  window.confirmLogout = function(href) {
    // Clear local storage
    localStorage.removeItem('pp_profile');
    localStorage.removeItem('pp_active_profile');
    
    // Redirect to logout
    window.location.href = href || '/wp-login.php?action=logout';
  };

  // Edit Avatar Modal
  function showEditAvatarModal() {
    const colors = ['#e50914', '#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#00acc1', '#ff5722', '#607d8b'];
    const currentAvatar = document.querySelector('.pp-hero__avatar')?.src || '';
    
    let colorsHtml = colors.map((color, i) => `
      <button style="width: 48px; height: 48px; border-radius: 50%; background: ${color}; border: 3px solid transparent; cursor: pointer; transition: all 0.2s;" onclick="this.style.borderColor='white'; changeAvatarColor('${color}')"></button>
    `).join('');
    
    showModal('Change Avatar', `
      <div style="text-align: center;">
        <img src="${currentAvatar}" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 24px; border: 4px solid var(--pp-border-hover);" id="avatar-preview">
        <p style="color: var(--pp-muted); margin-bottom: 16px;">Or choose a color:</p>
        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-bottom: 24px;">
          ${colorsHtml}
        </div>
        <div style="display: flex; gap: 12px;">
          <button class="pp-btn pp-btn--secondary" onclick="closeModal()" style="flex: 1;">Cancel</button>
          <button class="pp-btn pp-btn--primary" onclick="saveAvatar()" style="flex: 1;">Save</button>
        </div>
      </div>
    `);
  }

  window.changeAvatarColor = function(color) {
    // Generate avatar with new color
    const name = document.querySelector('.pp-hero__name')?.textContent || 'User';
    const newAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&size=280&background=${color.replace('#', '')}&color=fff&bold=true`;
    document.getElementById('avatar-preview').src = newAvatar;
  };

  window.saveAvatar = function() {
    const newAvatar = document.getElementById('avatar-preview').src;
    
    // Update all avatars
    document.querySelectorAll('.pp-hero__avatar, .pp-account-card__avatar').forEach(el => {
      el.src = newAvatar;
    });
    
    localStorage.setItem('pp_avatar', newAvatar);
    closeModal();
    showToast('Avatar updated!', 'success');
  };

  // Add Genre Modal
  function showAddGenreModal() {
    const allGenres = ['Action', 'Adventure', 'Comedy', 'Crime', 'Documentary', 'Drama', 'Fantasy', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Thriller', 'Western'];
    const selectedGenres = Array.from(document.querySelectorAll('.pp-tag[data-action="toggle-genre"].pp-tag--active')).map(t => t.dataset.genre);
    const availableGenres = allGenres.filter(g => !selectedGenres.includes(g.toLowerCase()));
    
    if (availableGenres.length === 0) {
      showToast('All genres have been added!', 'info');
      return;
    }
    
    let optionsHtml = availableGenres.map(genre => 
      `<option value="${genre.toLowerCase()}">${genre}</option>`
    ).join('');
    
    showModal('Add Genre', `
      <div class="pp-form-group">
        <label>Select a genre to add:</label>
        <select class="pp-select" id="new-genre-select">
          ${optionsHtml}
        </select>
      </div>
      <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
        <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="pp-btn pp-btn--primary" onclick="addNewGenre()">Add</button>
      </div>
    `);
  }

  window.addNewGenre = function() {
    const genre = document.getElementById('new-genre-select').value;
    const addBtn = document.querySelector('.pp-tag[data-action="add-genre"]');
    
    // Add the new genre tag
    const newTag = document.createElement('button');
    newTag.className = 'pp-tag pp-tag--active';
    newTag.dataset.genre = genre;
    newTag.dataset.action = 'toggle-genre';
    newTag.textContent = genre.charAt(0).toUpperCase() + genre.slice(1);
    newTag.addEventListener('click', function() {
      this.classList.toggle('pp-tag--active');
      saveGenres();
    });
    
    addBtn.parentNode.insertBefore(newTag, addBtn);
    
    saveGenres();
    closeModal();
    showToast('Genre added!', 'success');
  };

  // Edit Profile Item Modal
  function showEditProfileItemModal(profileId) {
    const profileItem = document.querySelector(`.pp-profile-item[data-profile-id="${profileId}"]`);
    const name = profileItem?.querySelector('.pp-profile-item__name')?.textContent || '';
    const avatar = profileItem?.querySelector('.pp-profile-item__avatar')?.src || '';
    
    showModal('Edit Profile', `
      <div style="text-align: center;">
        <img src="${avatar}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 16px;">
      </div>
      <form id="edit-profile-item-form">
        <div class="pp-form-group">
          <label>Profile Name</label>
          <input type="text" class="pp-form-input" name="name" value="${escapeHtml(name)}" required>
        </div>
        <div class="pp-form-group">
          <label>Profile Type</label>
          <select class="pp-select" name="type">
            <option value="normal">Normal</option>
            <option value="kids">Kids</option>
          </select>
        </div>
        <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
          <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="pp-btn pp-btn--primary">Save</button>
        </div>
      </form>
    `);
    
    document.getElementById('edit-profile-item-form').addEventListener('submit', function(e) {
      e.preventDefault();
      const newName = this.querySelector('[name="name"]').value;
      const profileNameEl = document.querySelector(`.pp-profile-item[data-profile-id="${profileId}"] .pp-profile-item__name`);
      if (profileNameEl) profileNameEl.textContent = newName;
      closeModal();
      showToast('Profile updated!', 'success');
    });
  }

  // Add Profile Modal
  function showAddProfileModal() {
    showModal('Add Profile', `
      <form id="add-profile-form">
        <div style="text-align: center; margin-bottom: 24px;">
          <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--pp-bg-3); border: 2px dashed var(--pp-border); margin: 0 auto; display: flex; align-items: center; justify-content: center;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--pp-muted)" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          </div>
        </div>
        <div class="pp-form-group">
          <label>Profile Name</label>
          <input type="text" class="pp-form-input" name="name" placeholder="Enter profile name" required>
        </div>
        <div class="pp-form-group">
          <label>Profile Type</label>
          <select class="pp-select" name="type">
            <option value="normal">Normal</option>
            <option value="kids">Kids</option>
          </select>
        </div>
        <div class="pp-modal__footer" style="padding: 0; border: none; margin-top: 24px;">
          <button type="button" class="pp-btn pp-btn--secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="pp-btn pp-btn--primary">Create</button>
        </div>
      </form>
    `);
    
    document.getElementById('add-profile-form').addEventListener('submit', function(e) {
      e.preventDefault();
      closeModal();
      showToast('Profile created!', 'success');
    });
  }

  // Manage Subscription Modal
  function showManageSubscriptionModal() {
    showModal('Manage Subscription', `
      <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid var(--pp-border); margin-bottom: 20px;">
        <div style="font-size: 14px; color: var(--pp-muted); margin-bottom: 8px;">Current Plan</div>
        <div style="font-size: 28px; font-weight: 800;">Premium</div>
        <div style="font-size: 16px; color: var(--pp-text-secondary);">$14.99/month</div>
      </div>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <button style="padding: 14px; background: var(--pp-bg-2); border: 1px solid var(--pp-border); border-radius: 10px; color: var(--pp-text); text-align: left; cursor: pointer; display: flex; align-items: center; gap: 12px;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pp-accent)" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          <div style="flex: 1;">
            <div style="font-weight: 600;">Update Payment Method</div>
            <div style="font-size: 12px; color: var(--pp-muted);">•••• •••• •••• 4242</div>
          </div>
        </button>
        <button style="padding: 14px; background: var(--pp-bg-2); border: 1px solid var(--pp-border); border-radius: 10px; color: var(--pp-text); text-align: left; cursor: pointer; display: flex; align-items: center; gap: 12px;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pp-accent)" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <div style="flex: 1;">
            <div style="font-weight: 600;">Billing History</div>
            <div style="font-size: 12px; color: var(--pp-muted);">View past invoices</div>
          </div>
        </button>
        <button style="padding: 14px; background: transparent; border: 1px solid var(--pp-accent); border-radius: 10px; color: var(--pp-accent); cursor: pointer; font-weight: 600;" onclick="closeModal(); showCancelSubscriptionModal();">
          Cancel Subscription
        </button>
      </div>
    `);
  }

  function showCancelSubscriptionModal() {
    showModal('Cancel Subscription', `
      <div style="text-align: center; padding: 20px 0;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--pp-accent)" stroke-width="1.5" style="margin-bottom: 20px;">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <h3 style="margin-bottom: 12px;">Are you sure you want to cancel?</h3>
        <p style="color: var(--pp-muted); margin-bottom: 24px;">You'll lose access to Premium features at the end of your billing period.</p>
        <div style="display: flex; gap: 12px;">
          <button class="pp-btn pp-btn--secondary" onclick="closeModal()" style="flex: 1;">Keep Subscription</button>
          <button class="pp-btn pp-btn--primary" onclick="closeModal(); showToast('Subscription cancellation scheduled', 'info');" style="flex: 1; background: var(--pp-accent);">Cancel</button>
        </div>
      </div>
    `);
  }

  // ============================================================
  // SCROLL BEHAVIOR
  // ============================================================
  function initScrollBehavior() {
    const scrollItems = document.querySelectorAll('[data-scroll]');
    
    scrollItems.forEach(item => {
      item.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.dataset.scroll;
        scrollToElement(targetId);
      });
    });
  }

  function scrollToElement(id) {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  // ============================================================
  // TOAST NOTIFICATIONS
  // ============================================================
  function initToast() {
    // Toast is handled by showToast function
  }

  function showToast(message, type = 'info') {
    const toast = document.getElementById('pp-toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = 'pp-toast';
    
    if (type === 'success') {
      toast.classList.add('pp-toast--success');
    } else if (type === 'error') {
      toast.classList.add('pp-toast--error');
    }
    
    toast.classList.add('show');
    
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }

  window.showToast = showToast;

  // ============================================================
  // UTILITY FUNCTIONS
  // ============================================================
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // ============================================================
  // DATA MANAGEMENT
  // ============================================================
  const ProfileData = {
    getProfile() {
      const data = localStorage.getItem('pp_profile');
      return data ? JSON.parse(data) : null;
    },
    
    saveProfile(data) {
      localStorage.setItem('pp_profile', JSON.stringify(data));
    },
    
    getActiveProfile() {
      return localStorage.getItem('pp_active_profile') || '1';
    },
    
    setActiveProfile(id) {
      localStorage.setItem('pp_active_profile', id);
    },
    
    getWatchHistory() {
      const data = localStorage.getItem('pp_watch_history');
      return data ? JSON.parse(data) : [];
    },
    
    addToHistory(item) {
      const history = this.getWatchHistory();
      const existing = history.findIndex(h => h.id === item.id);
      
      if (existing >= 0) {
        history[existing] = { ...history[existing], ...item, watchedAt: Date.now() };
      } else {
        history.unshift({ ...item, watchedAt: Date.now() });
      }
      
      if (history.length > 50) {
        history.length = 50;
      }
      
      localStorage.setItem('pp_watch_history', JSON.stringify(history));
    },
    
    getFavorites() {
      const data = localStorage.getItem('pp_favorites');
      return data ? JSON.parse(data) : [];
    },
    
    toggleFavorite(id, type) {
      const favorites = this.getFavorites();
      const index = favorites.findIndex(f => f.id === id && f.type === type);
      
      if (index >= 0) {
        favorites.splice(index, 1);
      } else {
        favorites.push({ id, type, addedAt: Date.now() });
      }
      
      localStorage.setItem('pp_favorites', JSON.stringify(favorites));
      return index < 0;
    },
  };

  // Expose globally
  window.ProfileData = ProfileData;

})();
