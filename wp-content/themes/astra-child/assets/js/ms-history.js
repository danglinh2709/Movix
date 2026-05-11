/**
 * ============================================================
 * Watch History Page JavaScript
 * Handles filtering, rendering, modals, interactions
 * ============================================================
 */
(function() {
    'use strict';

    var state = {
        items: [],
        filteredItems: [],
        currentCategory: 'all',
        currentTime: 'all',
        page: 1,
        perPage: 15
    };

    /* ============================================================
       INIT
       ============================================================ */
    function init() {
        var data = window.MU_HISTORY_DATA || {};
        state.items = Object.values(data);

        // Sort newest first
        state.items.sort(function(a, b) {
            var ta = a.progress && a.progress.updatedAt || 0;
            var tb = b.progress && b.progress.updatedAt || 0;
            return tb - ta;
        });

        state.filteredItems = [...state.items];

        updateAllCounts();
        renderHistory();

        bindEvents();
    }

    /* ============================================================
       RENDER HISTORY LIST
       ============================================================ */
    function renderHistory() {
        applyFilters();

        var list = document.getElementById('histList');
        var empty = document.getElementById('histEmpty');
        var loadMore = document.getElementById('histLoadMore');

        if (!list) return;

        if (state.filteredItems.length === 0) {
            list.innerHTML = '';
            if (empty) empty.style.display = 'flex';
            if (loadMore) loadMore.style.display = 'none';
            return;
        }

        if (empty) empty.style.display = 'none';

        // Paginate
        var pageItems = state.filteredItems.slice(0, state.page * state.perPage);
        var groups = groupByDate(pageItems);
        var html = '';

        for (var dateKey in groups) {
            if (!groups.hasOwnProperty(dateKey)) continue;
            html += '<div class="hist-group">';
            html += '<h3 class="hist-group__title">' + escapeHtml(dateKey) + '</h3>';
            groups[dateKey].forEach(function(item) {
                html += renderItem(item);
            });
            html += '</div>';
        }

        list.innerHTML = html;

        // Load more button
        if (loadMore) {
            var total = state.filteredItems.length;
            var showing = Math.min(state.page * state.perPage, total);
            var info = loadMore.querySelector('.hist-load-more__info');
            if (total > state.perPage) {
                loadMore.style.display = 'flex';
                if (info) info.textContent = 'Showing ' + showing + ' of ' + total + ' titles';
            } else {
                loadMore.style.display = 'none';
            }
        }

        bindItemEvents();
    }

    /* ============================================================
       RENDER SINGLE ITEM
       ============================================================ */
    function renderItem(item) {
        var id = item.id;
        var title = escapeHtml(item.title || 'Untitled');
        var thumb = item.thumb || '';
        var type = item.type || 'movie';
        var typeLabel = type === 'movie' ? 'Movie' : (type === 'tv_show' ? 'TV Show' : 'Episode');
        var year = item.year || '';
        var runtime = item.runtime || '';
        var genres = (item.genres || []).slice(0, 2).join(', ');
        var watchUrl = escapeHtml(item.watchUrl || '#');
        var detailUrl = escapeHtml(item.detailUrl || '#');
        var percent = item.progress && item.progress.percent || 0;
        var currentTime = item.progress && item.progress.currentTime || 0;
        var duration = item.progress && item.progress.duration || 0;
        var completed = percent >= 95;

        var typeClass = type === 'movie' ? 'movie' : 'tv';
        var fillClass = completed ? ' is-completed' : '';
        var resumeLabel = completed ? 'Rewatch' : 'Resume';

        var progressText = percent + '%';
        if (currentTime && duration) {
            var watched = formatTime(currentTime);
            var total = formatTime(duration);
            progressText = percent + '% · ' + watched + ' / ' + total;
        }

        var html = '<div class="hist-item" data-id="' + id + '" data-watch="' + watchUrl + '" data-detail="' + detailUrl + '">';

        // Poster
        html += '<a href="' + watchUrl + '" class="hist-item__poster" ' +
                'style="background-image: url(\'' + escapeHtml(thumb) + '\')"' +
                'onclick="event.stopPropagation(); event.preventDefault(); window.location.href=\'' + watchUrl + '\'">';
        html += '<div class="hist-item__poster-overlay">';
        html += '<div class="hist-item__play-icon">';
        html += '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
        html += '</div></div>';
        html += '</a>';

        // Body
        html += '<div class="hist-item__body">';
        html += '<h4 class="hist-item__title">' + title + '</h4>';

        // Meta row
        html += '<div class="hist-item__meta">';
        html += '<span class="hist-item__type-badge' + (type === 'movie' ? ' hist-item__type-badge--movie' : '') + '">' + typeLabel + '</span>';
        if (year) html += '<span class="hist-item__meta-item">' + year + '</span>';
        if (runtime) html += '<span class="hist-item__meta-item">' + runtime + 'm</span>';
        if (genres) {
            html += '<span class="hist-item__meta-item">' + escapeHtml(genres) + '</span>';
        }
        html += '</div>';

        // Progress
        html += '<div class="hist-item__progress-section">';
        html += '<div class="hist-item__progress-bar"><div class="hist-item__progress-fill' + fillClass + '" style="width:' + percent + '%"></div></div>';
        html += '<div class="hist-item__progress-info">';
        html += '<span class="hist-item__progress-text' + (completed ? ' is-completed' : '') + '">' + progressText + '</span>';
        html += '<span class="hist-item__resume-btn">' + resumeLabel + '</span>';
        html += '</div></div>';
        html += '</div>';

        // Actions
        html += '<div class="hist-item__actions">';
        html += '<button class="hist-item__menu-btn" data-menu-id="' + id + '" aria-label="Actions">';
        html += '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>';
        html += '</button></div>';

        html += '</div>';

        return html;
    }

    /* ============================================================
       GROUP BY DATE
       ============================================================ */
    function groupByDate(items) {
        var groups = {};
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
        var yesterday = today - 86400000;
        var lastWeek = today - 7 * 86400000;
        var lastMonth = today - 30 * 86400000;

        items.forEach(function(item) {
            var timestamp = item.progress && item.progress.updatedAt || 0;
            var date = new Date(timestamp);
            var midnight = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();

            var label;
            if (midnight >= today) {
                label = 'Today';
            } else if (midnight >= yesterday) {
                label = 'Yesterday';
            } else if (midnight >= lastWeek) {
                label = 'Last 7 Days';
            } else if (midnight >= lastMonth) {
                label = 'Last 30 Days';
            } else {
                label = 'Older';
            }

            if (!groups[label]) groups[label] = [];
            groups[label].push(item);
        });

        // Order groups
        var ordered = {};
        ['Today', 'Yesterday', 'Last 7 Days', 'Last 30 Days', 'Older'].forEach(function(key) {
            if (groups[key]) ordered[key] = groups[key];
        });

        return ordered;
    }

    /* ============================================================
       APPLY FILTERS
       ============================================================ */
    function applyFilters() {
        var filtered = state.items.filter(function(item) {
            // Category filter
            if (state.currentCategory !== 'all' && item.type !== state.currentCategory) {
                return false;
            }

            // Time filter
            if (state.currentTime !== 'all') {
                var timestamp = item.progress && item.progress.updatedAt || 0;
                var now = Date.now();
                var date = new Date(timestamp);
                var midnight = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
                var today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
                var yesterday = today - 86400000;
                var lastWeek = today - 7 * 86400000;
                var lastMonth = today - 30 * 86400000;

                var match = false;
                switch (state.currentTime) {
                    case 'today':     match = midnight >= today; break;
                    case 'yesterday':  match = midnight >= yesterday && midnight < today; break;
                    case 'last7days': match = midnight >= lastWeek; break;
                    case 'last30days': match = midnight >= lastMonth; break;
                    case 'older':     match = midnight < lastMonth; break;
                }
                if (!match) return false;
            }

            return true;
        });

        state.filteredItems = filtered;
        state.page = 1;
    }

    /* ============================================================
       UPDATE ALL COUNTS
       ============================================================ */
    function updateAllCounts() {
        var counts = {
            all: state.items.length,
            movie: 0,
            tv_show: 0,
            today: 0,
            yesterday: 0,
            last7days: 0,
            last30days: 0,
            older: 0
        };

        var now = Date.now();
        var today = new Date(new Date(now).getFullYear(), new Date(now).getMonth(), new Date(now).getDate()).getTime();
        var yesterday = today - 86400000;
        var lastWeek = today - 7 * 86400000;
        var lastMonth = today - 30 * 86400000;

        state.items.forEach(function(item) {
            var type = item.type || 'movie';
            if (type === 'movie') counts.movie++;
            else counts.tv_show++;

            var timestamp = item.progress && item.progress.updatedAt || 0;
            var date = new Date(timestamp);
            var midnight = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();

            if (midnight >= today) counts.today++;
            else if (midnight >= yesterday) counts.yesterday++;
            else if (midnight >= lastWeek) counts.last7days++;
            else if (midnight >= lastMonth) counts.last30days++;
            else counts.older++;
        });

        // Update sidebar counts
        document.querySelectorAll('.hist-filter-btn__count[data-count]').forEach(function(el) {
            var key = el.dataset.count;
            if (counts.hasOwnProperty(key)) {
                el.textContent = counts[key];
            }
        });

        // Update stats panel
        var total = state.items.length;
        var movies = counts.movie;
        var tv = counts.tv_show;
        var completed = 0;

        state.items.forEach(function(item) {
            var percent = item.progress && item.progress.percent || 0;
            if (percent >= 95) completed++;
        });

        var statTotal = document.getElementById('statTotal');
        var statMovies = document.getElementById('statMovies');
        var statTV = document.getElementById('statTV');
        var statCompleted = document.getElementById('statCompleted');

        if (statTotal) statTotal.textContent = total;
        if (statMovies) statMovies.textContent = movies;
        if (statTV) statTV.textContent = tv;
        if (statCompleted) statCompleted.textContent = completed;
    }

    /* ============================================================
       BIND GLOBAL EVENTS
       ============================================================ */
    function bindEvents() {
        // Sidebar filter buttons
        document.querySelectorAll('.hist-filter-btn[data-filter]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var filter = this.dataset.filter;

                // Determine category vs time
                if (['all', 'movie', 'tv_show'].indexOf(filter) !== -1) {
                    // Category filter
                    state.currentCategory = filter;
                    state.currentTime = 'all';
                } else {
                    // Time filter
                    state.currentTime = filter;
                    // Keep current category
                }

                // Update active states (within same group only)
                var group = this.closest('.hist-filter-list') || this.closest('.hist-sidebar');
                if (group) {
                    group.querySelectorAll('.hist-filter-btn').forEach(function(b) {
                        b.classList.remove('is-active');
                    });
                }
                this.classList.add('is-active');

                renderHistory();
            });
        });

        // Settings button
        var settingsBtn = document.querySelector('[data-action="settings"]');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', function() {
                openModal('histSettingsModal');
            });
        }

        // Clear All button
        var clearBtn = document.getElementById('histClearAll');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                openModal('histModal');
            });
        }

        var cancelBtn = document.getElementById('histModalCancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() { closeModal('histModal'); });
        }

        var settingsClose = document.getElementById('histSettingsClose');
        if (settingsClose) {
            settingsClose.addEventListener('click', function() { closeModal('histSettingsModal'); });
        }

        // Modal: backdrop click to close (for any modal with .hist-modal__backdrop)
        document.addEventListener('click', function(e) {
            var modal = e.target.closest('.hist-modal.is-open');
            if (modal && e.target.classList.contains('hist-modal__backdrop')) {
                var modalId = modal.id;
                closeModal(modalId);
            }
        });

        // Modal: confirm clear
        var confirmBtn = document.getElementById('histModalConfirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                closeModal('histModal');
                clearAllHistory();
            });
        }

        // Load more
        var loadMoreBtn = document.getElementById('histLoadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                state.page++;
                renderHistory();
            });
        }

        // Close dropdown on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.hist-item__menu-btn') && !e.target.closest('.hist-dropdown')) {
                closeDropdown();
            }
        });

        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('histModal');
                closeModal('histSettingsModal');
                closeDropdown();
            }
        });
    }

    /* ============================================================
       BIND ITEM-SPECIFIC EVENTS (re-called after render)
       ============================================================ */
    function bindItemEvents() {
        // Resume button click
        document.querySelectorAll('.hist-item__resume-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var item = this.closest('.hist-item');
                if (item) {
                    var watchUrl = item.dataset.watch;
                    if (watchUrl) window.location.href = watchUrl;
                }
            });
        });

        // Card click -> detail page
        document.querySelectorAll('.hist-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (e.target.closest('.hist-item__menu-btn') || e.target.closest('.hist-item__resume-btn')) return;
                var detailUrl = this.dataset.detail;
                if (detailUrl) window.location.href = detailUrl;
            });
        });

        // Menu button -> dropdown
        document.querySelectorAll('.hist-item__menu-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var id = this.dataset.menuId;
                var rect = this.getBoundingClientRect();
                var dropdown = document.getElementById('histDropdown');

                if (dropdown.classList.contains('is-open') && dropdown.dataset.activeId === id) {
                    closeDropdown();
                    return;
                }

                // Position dropdown
                dropdown.style.top = (rect.bottom + 8) + 'px';
                dropdown.style.right = (window.innerWidth - rect.right) + 'px';
                dropdown.style.left = 'auto';
                dropdown.dataset.activeId = id;
                dropdown.classList.add('is-open');
                dropdown.setAttribute('aria-hidden', 'false');

                // Setup action handlers
                setupDropdownActions(id);
            });
        });
    }

    /* ============================================================
       DROPDOWN ACTIONS
       ============================================================ */
    function setupDropdownActions(id) {
        var dropdown = document.getElementById('histDropdown');

        // Clone to remove old listeners
        var newDropdown = dropdown.cloneNode(true);
        dropdown.parentNode.replaceChild(newDropdown, dropdown);

        newDropdown.querySelectorAll('.hist-dropdown__item').forEach(function(item) {
            item.addEventListener('click', function() {
                var action = this.dataset.action;
                closeDropdown();

                var targetItem = state.items.find(function(i) { return String(i.id) === String(id); });
                if (!targetItem) return;

                switch (action) {
                    case 'continue':
                        if (targetItem.watchUrl) window.location.href = targetItem.watchUrl;
                        break;
                    case 'details':
                        if (targetItem.detailUrl) window.location.href = targetItem.detailUrl;
                        break;
                    case 'addlist':
                        showToast('Added to My List');
                        break;
                    case 'remove':
                        removeItem(id);
                        break;
                }
            });
        });
    }

    function closeDropdown() {
        var dropdown = document.getElementById('histDropdown');
        if (dropdown) {
            dropdown.classList.remove('is-open');
            dropdown.setAttribute('aria-hidden', 'true');
            dropdown.dataset.activeId = '';
        }
    }

    /* ============================================================
       REMOVE SINGLE ITEM
       ============================================================ */
    function removeItem(id) {
        state.items = state.items.filter(function(item) {
            return String(item.id) !== String(id);
        });
        updateAllCounts();
        renderHistory();
        showToast('Removed from history');
    }

    /* ============================================================
       CLEAR ALL HISTORY
       ============================================================ */
    function clearAllHistory() {
        state.items = [];
        state.filteredItems = [];
        updateAllCounts();
        renderHistory();
        showToast('All history cleared');
    }

    /* ============================================================
       MODAL
       ============================================================ */
    function openModal(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    /* ============================================================
       TOAST
       ============================================================ */
    function showToast(msg) {
        var toast = document.getElementById('histToast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('is-visible');
        clearTimeout(toast._timer);
        toast._timer = setTimeout(function() {
            toast.classList.remove('is-visible');
        }, 2800);
    }

    /* ============================================================
       UTILITIES
       ============================================================ */
    function escapeHtml(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function formatTime(seconds) {
        if (!seconds) return '0:00';
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    /* ============================================================
       BOOT
       ============================================================ */
    document.addEventListener('DOMContentLoaded', init);

})();
