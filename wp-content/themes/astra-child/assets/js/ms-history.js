/**
 * ============================================================
 * Premium OTT Watch History Page JavaScript
 * Handles history items, filters, actions, and modals
 * ============================================================
 */
(function () {
  'use strict';

  // ============================================================
  // UTILITY FUNCTIONS
  // ============================================================
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

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

  function localStorageRemove(key) {
    try {
      localStorage.removeItem(key);
    } catch (e) {}
  }

  // ============================================================
  // STATE
  // ============================================================
  const state = {
    currentFilter: 'all',
    currentType: 'all',
    currentDateFilter: 'all',
    page: 1,
    perPage: 10,
    totalItems: 0,
    isLoading: false,
    historyData: []
  };

  // ============================================================
  // ELEMENTS
  // ============================================================
  let elements = {};

  function initElements() {
    elements = {
      container: $('.mu-history-container'),
      filterList: $$('.mu-history-filter-link'),
      contentArea: $('.mu-history-content-area'),
      clearAllBtn: $('#mu-history-clear-all'),
      modal: $('#mu-history-modal'),
      modalBackdrop: $('.mu-history-modal-backdrop'),
      modalCancel: $('#mu-history-modal-cancel'),
      modalConfirm: $('#mu-history-modal-confirm'),
      modalTitle: $('.mu-history-modal-title'),
      modalDesc: $('.mu-history-modal-desc'),
      emptyState: $('.mu-history-empty'),
      emptyStateBtns: $$('.mu-history-empty-btn'),
      loadMoreBtn: $('#mu-history-load-more'),
      loadMoreInfo: $('.mu-history-load-more-info'),
      statsItems: $('.mu-history-stat-value'),
      historyList: $('.mu-history-list')
    };
  }

  // ============================================================
  // DATA MANAGEMENT
  // ============================================================
  function getHistoryFromStorage() {
    return localStorageGet('mu_progress', {});
  }

  function removeFromHistory(postId) {
    const progress = getHistoryFromStorage();
    delete progress[postId];
    localStorageSet('mu_progress', progress);
  }

  function clearAllHistory() {
    localStorageRemove('mu_progress');
  }

  function getHistoryItems() {
    const progress = getHistoryFromStorage();
    const items = Object.entries(progress).map(([postId, data]) => ({
      postId: parseInt(postId),
      currentTime: data.t || 0,
      duration: data.d || 0,
      percent: data.d > 0 ? Math.round((data.t / data.d) * 100) : 0,
      lastWatched: data.updatedAt ? new Date(data.updatedAt) : new Date()
    }));

    // Sort by last watched (newest first)
    items.sort((a, b) => b.lastWatched - a.lastWatched);

    return items;
  }

  function groupItemsByDate(items) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today.getTime() - 86400000);
    const weekAgo = new Date(today.getTime() - 7 * 86400000);
    const monthAgo = new Date(today.getTime() - 30 * 86400000);

    const groups = {
      today: [],
      yesterday: [],
      last7days: [],
      last30days: [],
      older: []
    };

    items.forEach(item => {
      const itemDate = new Date(item.lastWatched.getFullYear(), item.lastWatched.getMonth(), item.lastWatched.getDate());

      if (itemDate.getTime() === today.getTime()) {
        groups.today.push(item);
      } else if (itemDate.getTime() === yesterday.getTime()) {
        groups.yesterday.push(item);
      } else if (itemDate > weekAgo) {
        groups.last7days.push(item);
      } else if (itemDate > monthAgo) {
        groups.last30days.push(item);
      } else {
        groups.older.push(item);
      }
    });

    return groups;
  }

  // ============================================================
  // FILTER FUNCTIONS
  // ============================================================
  function setActiveFilter(filter) {
    state.currentFilter = filter;

    // Update filter UI
    $$('.mu-history-filter-link').forEach(link => {
      link.classList.toggle('is-active', link.dataset.filter === filter);
    });

    // Reset pagination
    state.page = 1;
    renderHistory();
  }

  function getFilteredItems() {
    let items = getHistoryItems();

    // Filter by type (all, movie, tv)
    if (state.currentFilter === 'movies') {
      items = items.filter(item => getPostType(item.postId) === 'movie');
    } else if (state.currentFilter === 'tv') {
      items = items.filter(item => getPostType(item.postId) === 'tv_show');
    }

    // Filter by date
    const groups = groupItemsByDate(items);

    switch (state.currentDateFilter) {
      case 'today':
        return groups.today;
      case 'yesterday':
        return groups.yesterday;
      case 'last7days':
        return [...groups.today, ...groups.yesterday, ...groups.last7days];
      case 'last30days':
        return [...groups.today, ...groups.yesterday, ...groups.last7days, ...groups.last30days];
      case 'older':
        return groups.older;
      default:
        return items;
    }
  }

  function getPostType(postId) {
    // Try to get from cached data
    const cached = window.MU_HISTORY_DATA?.[postId];
    if (cached) return cached.type;

    // Default to movie
    return 'movie';
  }

  // ============================================================
  // RENDER FUNCTIONS
  // ============================================================
  function formatTimeAgo(date) {
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 7) {
      return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    } else if (days > 0) {
      return days === 1 ? 'Yesterday' : `${days} days ago`;
    } else if (hours > 0) {
      return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
    } else if (minutes > 0) {
      return minutes === 1 ? '1 minute ago' : `${minutes} minutes ago`;
    } else {
      return 'Just now';
    }
  }

  function formatDuration(seconds) {
    if (!seconds || seconds < 60) return '';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) {
      return `${h}h ${m}m`;
    }
    return `${m}m`;
  }

  function renderHistoryItem(item) {
    const movieData = window.MU_HISTORY_DATA?.[item.postId];
    if (!movieData) return '';

    const title = movieData.title || 'Untitled';
    const type = movieData.type === 'tv_show' ? 'TV Show' : 'Movie';
    const year = movieData.year || '';
    const runtime = movieData.runtime || '';
    const genres = movieData.genres || [];
    const thumb = movieData.thumb || '';
    const watchUrl = movieData.watchUrl || '#';
    const detailUrl = movieData.detailUrl || '#';

    const isCompleted = item.percent >= 95;
    const progressText = isCompleted ? 'Completed' : `${item.percent}%`;

    return `
      <div class="mu-history-item" data-post-id="${item.postId}">
        <a href="${watchUrl}" class="mu-history-item-thumb">
          <img src="${thumb}" alt="${title}" loading="lazy" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 140 80%22%3E%3Crect fill=%22%231a1a1a%22 width=%22140%22 height=%2280%22/%3E%3C/svg%3E'">
          <span class="mu-history-item-badge">${type}</span>
          <div class="mu-history-item-thumb-overlay">
            <div class="mu-history-item-play-icon">
              <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
          </div>
        </a>
        <div class="mu-history-item-info">
          <a href="${detailUrl}" class="mu-history-item-title">${title}</a>
          <div class="mu-history-item-meta">
            ${year ? `<span class="mu-history-item-meta-item">${year}</span>` : ''}
            ${runtime ? `<span class="mu-history-item-meta-dot"></span><span class="mu-history-item-meta-item">${runtime}</span>` : ''}
          </div>
          ${genres.length > 0 ? `
            <div class="mu-history-item-genres">
              ${genres.slice(0, 3).map(g => `<span class="mu-history-item-genre">${g}</span>`).join('')}
            </div>
          ` : ''}
          <div class="mu-history-item-progress-wrap">
            <div class="mu-history-item-progress-bar">
              <div class="mu-history-item-progress-fill ${isCompleted ? 'is-completed' : ''}" style="width: ${item.percent}%"></div>
            </div>
            <span class="mu-history-item-progress-text ${isCompleted ? 'is-completed' : ''}">${progressText}</span>
          </div>
          <span class="mu-history-item-time">Watched ${formatTimeAgo(item.lastWatched)}</span>
        </div>
        <div class="mu-history-item-actions">
          <button type="button" class="mu-history-item-menu-btn" aria-label="Actions" data-menu-toggle>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
          </button>
          <div class="mu-history-item-menu" data-menu>
            <a href="${watchUrl}" class="mu-history-item-menu-item" data-action="resume">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              Resume
            </a>
            <a href="${detailUrl}" class="mu-history-item-menu-item" data-action="details">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              View Details
            </a>
            <button type="button" class="mu-history-item-menu-item" data-action="fav" data-post-id="${item.postId}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
              Add to My List
            </button>
            <button type="button" class="mu-history-item-menu-item is-danger" data-action="remove" data-post-id="${item.postId}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
              Remove from History
            </button>
          </div>
        </div>
      </div>
    `;
  }

  function renderHistoryGroup(title, items) {
    if (!items || items.length === 0) return '';

    return `
      <div class="mu-history-group">
        <h3 class="mu-history-group-title">${title}</h3>
        <div class="mu-history-group-items">
          ${items.map(item => renderHistoryItem(item)).join('')}
        </div>
      </div>
    `;
  }

  function renderHistory() {
    if (!elements.historyList) return;

    const filteredItems = getFilteredItems();
    state.totalItems = filteredItems.length;

    // Group by date
    const groups = groupItemsByDate(filteredItems);

    // Calculate pagination
    const start = (state.page - 1) * state.perPage;
    const end = start + state.perPage;

    // Get items for current page
    let paginatedItems = [];
    let current = 0;
    const allGroups = [
      { key: 'today', items: groups.today, label: 'Today' },
      { key: 'yesterday', items: groups.yesterday, label: 'Yesterday' },
      { key: 'last7days', items: groups.last7days, label: 'Last 7 Days' },
      { key: 'last30days', items: groups.last30days, label: 'Last 30 Days' },
      { key: 'older', items: groups.older, label: 'Older' }
    ];

    // Build paginated groups
    let html = '';
    for (const group of allGroups) {
      if (group.items.length === 0) continue;

      const groupItems = [];
      for (const item of group.items) {
        if (current >= start && current < end) {
          groupItems.push(item);
        }
        current++;
        if (current >= end) break;
      }

      if (groupItems.length > 0) {
        html += renderHistoryGroup(group.label, groupItems);
      }

      if (current >= end) break;
    }

    // Show empty state or history
    if (filteredItems.length === 0) {
      elements.historyList.innerHTML = '';
      if (elements.emptyState) {
        elements.emptyState.style.display = 'flex';
      }
      if (elements.loadMoreBtn) {
        elements.loadMoreBtn.style.display = 'none';
      }
    } else {
      if (elements.emptyState) {
        elements.emptyState.style.display = 'none';
      }
      if (state.page === 1) {
        elements.historyList.innerHTML = html;
      } else {
        elements.historyList.innerHTML += html;
      }

      // Update load more
      if (elements.loadMoreBtn) {
        const showing = Math.min(end, state.totalItems);
        elements.loadMoreBtn.style.display = current < state.totalItems ? 'inline-flex' : 'none';
        if (elements.loadMoreInfo) {
          elements.loadMoreInfo.textContent = `Showing ${start + 1}–${showing} of ${state.totalItems}`;
        }
      }

      // Reattach menu events
      initMenuEvents();
    }

    // Update filter counts
    updateFilterCounts();
  }

  function updateFilterCounts() {
    const allItems = getHistoryItems();
    const counts = {
      all: allItems.length,
      movies: allItems.filter(i => getPostType(i.postId) === 'movie').length,
      tv: allItems.filter(i => getPostType(i.postId) === 'tv_show').length,
      today: groupItemsByDate(allItems).today.length,
      yesterday: groupItemsByDate(allItems).yesterday.length
    };

    $$('[data-count]').forEach(el => {
      const key = el.dataset.count;
      if (counts[key] !== undefined) {
        el.textContent = counts[key];
      }
    });
  }

  // ============================================================
  // MENU HANDLING
  // ============================================================
  function initMenuEvents() {
    $$('[data-menu-toggle]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        // Close other menus
        $$('.mu-history-item-menu.is-open').forEach(menu => {
          if (menu !== btn.nextElementSibling) {
            menu.classList.remove('is-open');
          }
        });

        // Toggle this menu
        btn.nextElementSibling?.classList.toggle('is-open');
      });
    });

    // Menu item actions
    $$('[data-action]').forEach(item => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        const action = item.dataset.action;
        const postId = item.dataset.postId;

        // Close menu
        item.closest('.mu-history-item-menu')?.classList.remove('is-open');

        switch (action) {
          case 'resume':
            window.location.href = item.closest('.mu-history-item')?.querySelector('.mu-history-item-thumb')?.href || '#';
            break;
          case 'details':
            window.location.href = item.href || '#';
            break;
          case 'remove':
            removeHistoryItem(postId);
            break;
          case 'fav':
            toggleFavorite(postId);
            break;
        }
      });
    });
  }

  function removeHistoryItem(postId) {
    removeFromHistory(postId);

    // Animate out
    const itemEl = document.querySelector(`.mu-history-item[data-post-id="${postId}"]`);
    if (itemEl) {
      itemEl.style.opacity = '0';
      itemEl.style.transform = 'translateX(-20px)';
      itemEl.style.transition = 'all 0.3s ease';

      setTimeout(() => {
        renderHistory();
      }, 300);
    }
  }

  function toggleFavorite(postId) {
    const favs = new Set(localStorageGet('mu_favorites', []));
    const isAdding = !favs.has(String(postId));

    if (isAdding) {
      favs.add(String(postId));
    } else {
      favs.delete(String(postId));
    }

    localStorageSet('mu_favorites', Array.from(favs));

    // Update button text
    const menuItem = document.querySelector(`[data-action="fav"][data-post-id="${postId}"]`);
    if (menuItem) {
      const span = menuItem.querySelector('span') || document.createElement('span');
      span.textContent = isAdding ? 'Remove from My List' : 'Add to My List';
      menuItem.innerHTML = menuItem.innerHTML.replace(/Add to My List|Remove from My List/, isAdding ? 'Remove from My List' : 'Add to My List');
    }

    // Sync to server if logged in
    if (window.MOVIE_UI?.isLoggedIn) {
      syncFavoriteToServer(postId, isAdding);
    }
  }

  function syncFavoriteToServer(postId, isAdding) {
    const fd = new FormData();
    fd.append('action', 'toggle_favorite');
    fd.append('movie_id', postId);
    fd.append('nonce', window.MOVIE_UI.favNonce || '');

    fetch(window.MOVIE_UI.ajaxUrl || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    }).catch(() => {});
  }

  // ============================================================
  // MODAL HANDLING
  // ============================================================
  function openModal(title, desc, onConfirm) {
    if (!elements.modal) return;

    elements.modalTitle.textContent = title;
    elements.modalDesc.textContent = desc;

    elements.modalConfirm.onclick = () => {
      if (onConfirm) onConfirm();
      closeModal();
    };

    elements.modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!elements.modal) return;
    elements.modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  // ============================================================
  // EVENT LISTENERS
  // ============================================================
  function setupEventListeners() {
    // Filter clicks
    $$('.mu-history-filter-link').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const filter = link.dataset.filter;
        if (filter) {
          setActiveFilter(filter);
        }
      });
    });

    // Clear all button
    elements.clearAllBtn?.addEventListener('click', () => {
      const count = state.totalItems;
      openModal(
        'Clear All History?',
        `Are you sure you want to clear all ${count} items from your watch history? This cannot be undone.`,
        () => {
          clearAllHistory();
          renderHistory();
        }
      );
    });

    // Modal close on backdrop
    elements.modalBackdrop?.addEventListener('click', closeModal);

    // Modal cancel button
    elements.modalCancel?.addEventListener('click', closeModal);

    // Load more
    elements.loadMoreBtn?.addEventListener('click', () => {
      state.page++;
      renderHistory();
    });

    // Empty state buttons
    $$('.mu-history-empty-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        window.location.href = btn.dataset.href || '/';
      });
    });

    // Close menus on outside click
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.mu-history-item-menu') && !e.target.closest('[data-menu-toggle]')) {
        $$('.mu-history-item-menu.is-open').forEach(menu => {
          menu.classList.remove('is-open');
        });
      }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeModal();
      }
    });
  }

  // ============================================================
  // INITIALIZE
  // ============================================================
  function init() {
    initElements();
    setupEventListeners();
    renderHistory();
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
