/**
 * ============================================================
 * Premium OTT Search Page JavaScript
 * Matches reference image exactly
 * ============================================================
 */
(function () {
  'use strict';

  // ============================================================
  // UTILITIES
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

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function localStorageGet(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) { return fallback; }
  }

  function localStorageSet(key, val) {
    try { localStorage.setItem(key, JSON.stringify(val)); } catch (e) {}
  }

  function updateUrlParam(key, value) {
    const url = new URL(window.location.href);
    if (value) {
      url.searchParams.set(key, value);
    } else {
      url.searchParams.delete(key);
    }
    window.history.replaceState({}, '', url);
  }

  // ============================================================
  // CONFIG
  // ============================================================
  const CONFIG = {
    ajaxUrl: window.MOVIE_UI?.ajaxUrl || '/wp-admin/admin-ajax.php',
    nonce: window.MOVIE_UI?.nonce || '',
    watchUrl: window.MOVIE_UI?.watchUrl || '/watch',
    searchUrl: window.MOVIE_UI?.searchUrl || '/search',
    RECENT_KEY: 'mu_recent_searches_v3',
    perPage: 20
  };

  // ============================================================
  // STATE
  // ============================================================
  const state = {
    query: '',
    type: 'all',
    activeTab: 'all',
    page: 1,
    totalResults: 0,
    sortBy: 'relevance',
    filters: {
      genres: [],
      yearFrom: '',
      yearTo: '',
      rating: ''
    },
    results: {
      movies: [],
      tv: [],
      people: []
    }
  };

  // ============================================================
  // ELEMENTS
  // ============================================================
  let els = {};

  function initElements() {
    els = {
      // Main search input
      mainInput: $('#mu-main-search-input'),
      mainClear: $('#mu-main-search-clear'),
      searchTitle: $('#mu-search-title'),
      
      // Sidebar
      suggestionsSection: $('#mu-suggestions-section'),
      suggestionsList: $('#mu-suggestions-list'),
      recentList: $('#mu-recent-list'),
      clearAllRecent: $('#mu-clear-all-recent'),
      trendingChips: $$('.mu-trending-chip'),
      
      // Filters
      filterToggle: $('#mu-filter-toggle'),
      filterBody: $('#mu-filter-body'),
      typeFilter: $$('.mu-filter-option input[name="type"]'),
      genreFilter: $$('.mu-genre-check input[name="genre"]'),
      yearFrom: $('#mu-year-from'),
      yearTo: $('#mu-year-to'),
      ratingFilter: $$('.mu-rating-btn'),
      
      // Results
      resultsArea: $('#mu-search-results-area'),
      resultsInfo: $('#mu-results-info'),
      queryText: $('#mu-query-text'),
      initialState: $('#mu-initial-state'),
      resultsTabs: $('#mu-results-tabs'),
      resultsContainer: $('#mu-results-container'),
      resultsGrid: $('#mu-results-grid'),
      peopleSection: $('#mu-people-section'),
      peopleGrid: $('#mu-people-grid'),
      emptyState: $('#mu-empty-state'),
      loadingState: $('#mu-loading-state'),
      emptyClear: $('#mu-empty-clear'),
      
      // Pagination
      pagination: $('#mu-pagination'),
      paginationText: $('#mu-pagination-text'),
      pageNumbers: $('#mu-page-numbers'),
      prevBtn: $('#mu-prev-btn'),
      nextBtn: $('#mu-next-btn')
    };
  }

  // ============================================================
  // RECENT SEARCHES
  // ============================================================
  function getRecentSearches() {
    return localStorageGet(CONFIG.RECENT_KEY, []);
  }

  function addRecentSearch(term) {
    const t = (term || '').trim();
    if (t.length < 2) return;
    
    let list = getRecentSearches();
    list = [t].concat(list.filter(x => x.toLowerCase() !== t.toLowerCase())).slice(0, 8);
    localStorageSet(CONFIG.RECENT_KEY, list);
    renderRecentSearches();
  }

  function removeRecentSearch(term) {
    let list = getRecentSearches();
    list = list.filter(x => x.toLowerCase() !== term.toLowerCase());
    localStorageSet(CONFIG.RECENT_KEY, list);
    renderRecentSearches();
  }

  function clearAllRecentSearches() {
    localStorageSet(CONFIG.RECENT_KEY, []);
    renderRecentSearches();
  }

  function renderRecentSearches() {
    const list = getRecentSearches();
    
    if (!els.recentList) return;
    
    if (list.length === 0) {
      els.recentList.innerHTML = '<div style="padding: 8px 0; color: #737373; font-size: 12px;">No recent searches</div>';
      if (els.clearAllRecent) els.clearAllRecent.style.display = 'none';
      return;
    }

    els.recentList.innerHTML = list.map(term => `
      <div class="mu-recent-item" data-term="${escapeHtml(term)}">
        <svg class="mu-recent-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
        <span class="mu-recent-text">${escapeHtml(term)}</span>
        <button type="button" class="mu-recent-remove" data-term="${escapeHtml(term)}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    `).join('');

    els.recentList.querySelectorAll('.mu-recent-item').forEach(item => {
      item.addEventListener('click', (e) => {
        if (!e.target.closest('.mu-recent-remove')) {
          const term = item.dataset.term;
          setSearchQuery(term);
          performSearch();
        }
      });
    });

    els.recentList.querySelectorAll('.mu-recent-remove').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        removeRecentSearch(btn.dataset.term);
      });
    });

    if (els.clearAllRecent) els.clearAllRecent.style.display = 'block';
  }

  // ============================================================
  // SUGGESTIONS
  // ============================================================
  let suggestXhr = null;

  const fetchSuggestions = debounce((query) => {
    if (query.length < 2) {
      hideSuggestions();
      return;
    }

    if (suggestXhr) suggestXhr.abort();

    const fd = new FormData();
    fd.append('action', 'mu_search_suggestions');
    fd.append('nonce', CONFIG.nonce);
    fd.append('q', query);
    fd.append('limit', 6);

    suggestXhr = new XMLHttpRequest();
    suggestXhr.open('POST', CONFIG.ajaxUrl);
    suggestXhr.onreadystatechange = () => {
      if (suggestXhr.readyState === 4 && suggestXhr.status === 200) {
        try {
          const data = JSON.parse(suggestXhr.responseText);
          if (data.success && data.data) {
            renderSuggestions(data.data);
          }
        } catch (e) {}
      }
    };
    suggestXhr.send(fd);
  }, 250);

  function renderSuggestions(suggestions) {
    if (!suggestions || suggestions.length === 0) {
      hideSuggestions();
      return;
    }

    if (els.suggestionsList) {
      els.suggestionsList.innerHTML = suggestions.slice(0, 6).map(item => `
        <div class="mu-suggestion-item" data-term="${escapeHtml(item.title)}">
          <div class="mu-suggestion-thumb">
            ${item.thumb ? `<img src="${escapeHtml(item.thumb)}" alt="">` : '<div style="width:100%;height:100%;background:#1a1a1a;"></div>'}
          </div>
          <div class="mu-suggestion-info">
            <div class="mu-suggestion-name">${escapeHtml(item.title)}</div>
            <div class="mu-suggestion-meta">${item.type || ''} ${item.year ? '• ' + item.year : ''}</div>
          </div>
          <svg class="mu-suggestion-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </div>
      `).join('');

      els.suggestionsList.querySelectorAll('.mu-suggestion-item').forEach(item => {
        item.addEventListener('click', () => {
          const term = item.dataset.term;
          setSearchQuery(term);
          addRecentSearch(term);
          hideSuggestions();
          performSearch();
        });
      });
    }

    if (els.suggestionsSection) els.suggestionsSection.style.display = 'block';
  }

  function hideSuggestions() {
    if (els.suggestionsSection) els.suggestionsSection.style.display = 'none';
  }

  // ============================================================
  // MAIN SEARCH INPUT
  // ============================================================
  function setSearchQuery(query) {
    state.query = query;
    if (els.mainInput) {
      els.mainInput.value = query;
    }
    if (els.mainClear) {
      els.mainClear.style.display = query ? 'flex' : 'none';
    }
    if (els.searchTitle) {
      if (query) {
        els.searchTitle.textContent = 'Search';
      } else {
        els.searchTitle.textContent = 'Search';
      }
    }
  }

  function initMainInput() {
    if (!els.mainInput) return;

    els.mainInput.addEventListener('input', (e) => {
      state.query = e.target.value;
      els.mainClear.style.display = state.query ? 'flex' : 'none';
      
      if (state.query.length >= 2) {
        fetchSuggestions(state.query);
      } else {
        hideSuggestions();
      }
    });

    els.mainInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (state.query.trim()) {
          addRecentSearch(state.query);
          hideSuggestions();
          performSearch();
        }
      }
      if (e.key === 'Escape') {
        hideSuggestions();
      }
    });

    els.mainInput.addEventListener('focus', () => {
      if (state.query.length >= 2) {
        fetchSuggestions(state.query);
      }
    });

    if (els.mainClear) {
      els.mainClear.addEventListener('click', () => {
        setSearchQuery('');
        hideSuggestions();
        resetResults();
        els.mainInput.focus();
      });
    }

    // Close suggestions on outside click
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.mu-main-search-wrap') && !e.target.closest('.mu-sidebar-suggestions')) {
        hideSuggestions();
      }
    });
  }

  // ============================================================
  // TRENDING CHIPS
  // ============================================================
  function initTrendingChips() {
    els.trendingChips.forEach(chip => {
      chip.addEventListener('click', () => {
        const term = chip.dataset.term || chip.textContent.trim();
        setSearchQuery(term);
        addRecentSearch(term);
        hideSuggestions();
        performSearch();
      });
    });
  }

  // ============================================================
  // TABS
  // ============================================================
  function initTabs() {
    const tabs = els.resultsTabs?.querySelectorAll('.mu-results-tab');
    if (!tabs) return;
    
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('is-active'));
        tab.classList.add('is-active');
        state.activeTab = tab.dataset.tab;
        filterResultsByTab();
      });
    });
  }

  function filterResultsByTab() {
    const tab = state.activeTab;
    
    if (tab === 'all' || tab === 'movies' || tab === 'tv') {
      els.peopleSection.style.display = 'none';
      els.resultsGrid.parentElement.style.display = 'block';
    } else if (tab === 'people') {
      els.peopleSection.style.display = 'block';
      els.resultsGrid.parentElement.style.display = 'none';
    }
  }

  // ============================================================
  // FILTERS
  // ============================================================
  function initFilters() {
    // Ensure filter body starts open if it has is-open class (default: open)
    if (els.filterBody && els.filterBody.classList.contains('is-open')) {
      if (els.filterToggle) els.filterToggle.classList.add('is-open');
    }
    
    // Toggle filter body on click
    if (els.filterToggle) {
      els.filterToggle.addEventListener('click', () => {
        els.filterToggle.classList.toggle('is-open');
        els.filterBody.classList.toggle('is-open');
      });
    }

    // Type filter
    els.typeFilter.forEach(input => {
      input.addEventListener('change', () => {
        state.type = input.value;
      });
    });

    // Genre filter
    els.genreFilter.forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        state.filters.genres = Array.from(els.genreFilter)
          .filter(cb => cb.checked)
          .map(cb => cb.value);
      });
    });

    // Year filter
    if (els.yearFrom) {
      els.yearFrom.addEventListener('change', () => {
        state.filters.yearFrom = els.yearFrom.value;
      });
    }
    if (els.yearTo) {
      els.yearTo.addEventListener('change', () => {
        state.filters.yearTo = els.yearTo.value;
      });
    }

    // Rating filter
    els.ratingFilter.forEach(btn => {
      btn.addEventListener('click', () => {
        const rating = btn.dataset.rating;
        els.ratingFilter.forEach(b => b.classList.remove('is-active'));
        
        if (state.filters.rating === rating) {
          state.filters.rating = '';
        } else {
          btn.classList.add('is-active');
          state.filters.rating = rating;
        }
      });
    });

    // Clear all recent
    if (els.clearAllRecent) {
      els.clearAllRecent.addEventListener('click', clearAllRecentSearches);
    }
  }

  // ============================================================
  // SEARCH EXECUTION
  // ============================================================
  function performSearch() {
    if (!state.query.trim()) {
      resetResults();
      return;
    }
    
    state.page = 1;
    updateUrlParam('q', state.query);
    showLoading();

    const fd = new FormData();
    fd.append('action', 'mu_search_page');
    fd.append('nonce', CONFIG.nonce);
    fd.append('q', state.query);
    fd.append('type', state.type);
    fd.append('page', state.page);
    fd.append('per_page', CONFIG.perPage);
    fd.append('sort', state.sortBy);

    if (state.filters.yearFrom) fd.append('year_from', state.filters.yearFrom);
    if (state.filters.yearTo) fd.append('year_to', state.filters.yearTo);
    if (state.filters.rating) fd.append('rating', state.filters.rating);
    if (state.filters.genres.length) fd.append('genres', JSON.stringify(state.filters.genres));

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CONFIG.ajaxUrl);
    xhr.onreadystatechange = () => {
      if (xhr.readyState === 4) {
        hideLoading();
        
        if (xhr.status === 200) {
          try {
            const data = JSON.parse(xhr.responseText);
            if (data.success && data.data) {
              handleSearchResults(data.data);
            } else {
              showEmptyState();
            }
          } catch (e) {
            showEmptyState();
          }
        } else {
          showEmptyState();
        }
      }
    };
    xhr.send(fd);
  }

  function handleSearchResults(data) {
    state.results = {
      movies: data.movies || [],
      tv: data.tv || [],
      people: data.people || []
    };
    state.totalResults = data.total || 0;

    updateResultsHeader();
    renderResults();
    updatePagination();
    showResults();
  }

  function showResults() {
    if (els.initialState) els.initialState.style.display = 'none';
    if (els.emptyState) els.emptyState.style.display = 'none';
    if (els.resultsTabs) els.resultsTabs.style.display = 'flex';
    if (els.resultsContainer) els.resultsContainer.style.display = 'block';
    if (els.resultsInfo) els.resultsInfo.style.display = 'block';
  }

  function showEmptyState() {
    if (els.initialState) els.initialState.style.display = 'none';
    if (els.resultsContainer) els.resultsContainer.style.display = 'none';
    if (els.resultsTabs) els.resultsTabs.style.display = 'none';
    if (els.emptyState) els.emptyState.style.display = 'flex';
    if (els.pagination) els.pagination.style.display = 'none';
    if (els.resultsInfo) els.resultsInfo.style.display = 'block';
  }

  function resetResults() {
    if (els.initialState) els.initialState.style.display = 'flex';
    if (els.emptyState) els.emptyState.style.display = 'none';
    if (els.resultsContainer) els.resultsContainer.style.display = 'none';
    if (els.resultsTabs) els.resultsTabs.style.display = 'none';
    if (els.pagination) els.pagination.style.display = 'none';
    if (els.resultsInfo) els.resultsInfo.style.display = 'none';
    updateUrlParam('q', '');
  }

  function showLoading() {
    if (els.resultsContainer) els.resultsContainer.style.display = 'none';
    if (els.initialState) els.initialState.style.display = 'none';
    if (els.emptyState) els.emptyState.style.display = 'none';
    if (els.loadingState) els.loadingState.style.display = 'flex';
  }

  function hideLoading() {
    if (els.loadingState) els.loadingState.style.display = 'none';
  }

  function updateResultsHeader() {
    if (els.queryText) els.queryText.textContent = state.query;
  }

  // ============================================================
  // RENDER RESULTS
  // ============================================================
  function renderResults() {
    const movies = state.results.movies || [];
    const tv = state.results.tv || [];
    const people = state.results.people || [];

    // All content items
    const allContent = [...movies, ...tv];

    // Render content grid
    if (els.resultsGrid) {
      if (allContent.length > 0) {
        els.resultsGrid.innerHTML = allContent.slice(0, CONFIG.perPage).map(item => renderCard(item)).join('');
        els.resultsGrid.parentElement.style.display = 'block';
      } else {
        els.resultsGrid.innerHTML = '';
        els.resultsGrid.parentElement.style.display = 'none';
      }
    }

    // Render people grid
    if (els.peopleGrid) {
      if (people.length > 0) {
        els.peopleGrid.innerHTML = people.slice(0, 12).map(person => renderPeopleCard(person)).join('');
        els.peopleSection.style.display = 'block';
      } else {
        els.peopleGrid.innerHTML = '';
        els.peopleSection.style.display = 'none';
      }
    }

    // Apply tab filter
    filterResultsByTab();
    
    // Init card events
    initCardEvents();
  }

  function renderCard(item) {
    const thumb = item.thumb || '';
    const title = escapeHtml(item.title || 'Untitled');
    const year = item.year || '';
    const type = item.type === 'tv_show' ? 'TV' : 'Movie';
    const rating = item.rating || '';
    const url = item.url || `${CONFIG.watchUrl}?id=${item.id}`;

    return `
      <div class="mu-result-card" data-id="${item.id}" data-url="${escapeHtml(url)}">
        <div class="mu-card-poster">
          <img src="${escapeHtml(thumb)}" alt="${title}" loading="lazy" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 24%22%3E%3Crect fill=%22%230a0a0a%22 width=%2216%22 height=%2224%22/%3E%3C/svg%3E'">
          <div class="mu-card-overlay">
            <div class="mu-card-play-btn">
              <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
          </div>
          <span class="mu-card-badge">${type}</span>
          ${rating ? `<span class="mu-card-rating"><svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>${rating}</span>` : ''}
        </div>
        <div class="mu-card-info">
          <h4 class="mu-card-title">${title}</h4>
          <div class="mu-card-meta">${year}</div>
        </div>
      </div>
    `;
  }

  function renderPeopleCard(person) {
    const thumb = person.thumb || '';
    const name = escapeHtml(person.name || 'Unknown');
    const role = escapeHtml(person.role || '');

    return `
      <div class="mu-people-card" data-id="${person.id}" data-name="${name}">
        <div class="mu-people-avatar">
          ${thumb ? `<img src="${escapeHtml(thumb)}" alt="${name}" loading="lazy">` : `
            <div class="mu-people-avatar-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <circle cx="12" cy="8" r="5"/>
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              </svg>
            </div>
          `}
        </div>
        <h4 class="mu-people-name">${name}</h4>
        ${role ? `<p class="mu-people-role">${role}</p>` : ''}
      </div>
    `;
  }

  function initCardEvents() {
    // Movie/TV cards
    $$('.mu-result-card[data-id]').forEach(card => {
      card.addEventListener('click', (e) => {
        if (e.target.closest('.mu-card-fav')) return;
        
        const id = card.dataset.id;
        const url = card.dataset.url || `${CONFIG.watchUrl}?id=${id}`;
        window.location.href = url;
      });
    });

    // Play button
    $$('.mu-card-play-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const card = btn.closest('.mu-result-card');
        if (card) {
          const id = card.dataset.id;
          window.location.href = `${CONFIG.watchUrl}?id=${id}`;
        }
      });
    });

    // People cards
    $$('.mu-people-card').forEach(card => {
      card.addEventListener('click', () => {
        const name = card.dataset.name;
        if (name) {
          setSearchQuery(name);
          performSearch();
        }
      });
    });
  }

  // ============================================================
  // PAGINATION
  // ============================================================
  function updatePagination() {
    if (!els.pagination) return;

    const totalPages = Math.ceil(state.totalResults / CONFIG.perPage);
    const start = (state.page - 1) * CONFIG.perPage + 1;
    const end = Math.min(state.page * CONFIG.perPage, state.totalResults);

    if (totalPages <= 1) {
      els.pagination.style.display = 'none';
      return;
    }

    els.pagination.style.display = 'flex';

    if (els.paginationText) {
      els.paginationText.textContent = `Showing ${start}-${end} of ${state.totalResults} results`;
    }

    if (els.prevBtn) {
      els.prevBtn.disabled = state.page <= 1;
      els.prevBtn.onclick = () => {
        if (state.page > 1) {
          state.page--;
          performSearch();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      };
    }

    if (els.nextBtn) {
      els.nextBtn.disabled = state.page >= totalPages;
      els.nextBtn.onclick = () => {
        if (state.page < totalPages) {
          state.page++;
          performSearch();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      };
    }

    if (els.pageNumbers) {
      let html = '';
      const maxVisible = 5;
      let startPage = Math.max(1, state.page - Math.floor(maxVisible / 2));
      let endPage = Math.min(totalPages, startPage + maxVisible - 1);

      if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
      }

      if (startPage > 1) {
        html += `<button type="button" class="mu-page-num" data-page="1">1</button>`;
        if (startPage > 2) html += `<span class="mu-page-ellipsis">...</span>`;
      }

      for (let i = startPage; i <= endPage; i++) {
        html += `<button type="button" class="mu-page-num ${i === state.page ? 'is-active' : ''}" data-page="${i}">${i}</button>`;
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<span class="mu-page-ellipsis">...</span>`;
        html += `<button type="button" class="mu-page-num" data-page="${totalPages}">${totalPages}</button>`;
      }

      els.pageNumbers.innerHTML = html;

      els.pageNumbers.querySelectorAll('.mu-page-num').forEach(btn => {
        btn.addEventListener('click', () => {
          const newPage = parseInt(btn.dataset.page);
          if (newPage !== state.page) {
            state.page = newPage;
            performSearch();
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }
        });
      });
    }
  }

  // ============================================================
  // KEYBOARD SHORTCUTS
  // ============================================================
  function initKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      if (e.key === '/' && document.activeElement !== els.mainInput) {
        e.preventDefault();
        els.mainInput?.focus();
      }
      if (e.key === 'Escape') {
        hideSuggestions();
      }
    });
  }

  // ============================================================
  // INITIALIZE
  // ============================================================
  function init() {
    initElements();
    initMainInput();
    initTrendingChips();
    initFilters();
    initTabs();
    initKeyboardShortcuts();
    renderRecentSearches();

    // Check for initial query from URL
    const urlParams = new URLSearchParams(window.location.search);
    const initialQuery = urlParams.get('q') || '';

    if (initialQuery) {
      setSearchQuery(initialQuery);
      performSearch();
    } else {
      setTimeout(() => els.mainInput?.focus(), 100);
    }

    // Empty state clear button
    if (els.emptyClear) {
      els.emptyClear.addEventListener('click', () => {
        setSearchQuery('');
        hideSuggestions();
        resetResults();
        els.mainInput?.focus();
      });
    }
  }

  // Start when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
