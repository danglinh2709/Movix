/**
 * Movies Page - Premium OTT Streaming Platform
 * Netflix/VieON Style Catalog JavaScript
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initSidebarToggle();
        initShowMore();
        initSortSelect();
        initViewToggle();
        initCardActions();
        initMobileDrawer();
        initModal();
    });

    // ============================================================
    // SIDEBAR TOGGLE
    // ============================================================
    function initSidebarToggle() {
        document.querySelectorAll('.ms-sb-section--toggle').forEach(function(section) {
            var title = section.querySelector('[data-toggle]');
            if (title) {
                title.addEventListener('click', function() {
                    section.classList.toggle('is-collapsed');
                });
            }
        });
    }

    // ============================================================
    // SHOW MORE
    // ============================================================
    function initShowMore() {
        document.querySelectorAll('.ms-sb-more').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var target = this.getAttribute('data-show-more');
                var body = document.getElementById('ms-sb-' + target);
                if (body) {
                    body.querySelectorAll('[data-hidden]').forEach(function(el) {
                        el.style.display = '';
                        el.removeAttribute('data-hidden');
                    });
                    this.style.display = 'none';
                }
            });
        });
    }

    // ============================================================
    // SORT SELECT
    // ============================================================
    function initSortSelect() {
        var sortSelect = document.getElementById('ms-sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                var form = document.getElementById('ms-filter-form');
                if (form) {
                    // Create or update sort input
                    var sortInput = form.querySelector('input[name="sort"]');
                    if (!sortInput) {
                        sortInput = document.createElement('input');
                        sortInput.type = 'hidden';
                        sortInput.name = 'sort';
                        form.appendChild(sortInput);
                    }
                    sortInput.value = this.value;
                    form.submit();
                } else {
                    // No filter form, redirect with sort param
                    var url = new URL(window.location.href);
                    url.searchParams.set('sort', this.value);
                    window.location.href = url.toString();
                }
            });
        }
    }

    // ============================================================
    // VIEW TOGGLE (GRID/LIST)
    // ============================================================
    function initViewToggle() {
        var viewBtns = document.querySelectorAll('.ms-view-toggle__btn');
        var grid = document.getElementById('ms-movie-grid');

        if (!viewBtns.length || !grid) return;

        // Load saved view
        var savedView = localStorage.getItem('ms-movies-view') || 'grid';
        setActiveView(savedView);

        viewBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var view = this.getAttribute('data-view');
                setActiveView(view);
                localStorage.setItem('ms-movies-view', view);
            });
        });

        function setActiveView(view) {
            viewBtns.forEach(function(btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-view') === view);
            });
            grid.setAttribute('data-view', view);
        }
    }

    // ============================================================
    // CARD ACTIONS (Play, Add to List, Info)
    // ============================================================
    function initCardActions() {
        var grid = document.getElementById('ms-movie-grid');
        if (!grid) return;

        grid.addEventListener('click', function(e) {
            var actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            e.preventDefault();
            e.stopPropagation();

            var action = actionBtn.getAttribute('data-action');
            var card = actionBtn.closest('.ms-card');
            var postId = actionBtn.getAttribute('data-id');
            var hasVideo = actionBtn.getAttribute('data-has-video') === '1';

            switch (action) {
                case 'play':
                    if (hasVideo) {
                        var playBtn = card.querySelector('.ms-card__action--play');
                        var href = playBtn.getAttribute('data-href') || '/watch/?id=' + postId;
                        window.location.href = href;
                    } else {
                        showNoVideoModal();
                    }
                    break;

                case 'add-list':
                    toggleFavorite(postId, actionBtn);
                    break;

                case 'info':
                    var detailUrl = card.querySelector('.ms-card__poster').getAttribute('href');
                    if (detailUrl) {
                        window.location.href = detailUrl;
                    }
                    break;
            }
        });
    }

    // ============================================================
    // FAVORITE / MY LIST
    // ============================================================
    function toggleFavorite(postId, btn) {
        var nonce = msMoviesData.nonce || '';
        
        fetch(msMoviesData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'ms_toggle_favorite',
                post_id: postId,
                nonce: nonce
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var icon = btn.querySelector('svg');
                var isAdded = data.data.added;
                
                if (isAdded) {
                    btn.classList.add('is-added');
                    icon.setAttribute('fill', '#e50914');
                    showToast('Added to My List');
                } else {
                    btn.classList.remove('is-added');
                    icon.setAttribute('fill', 'none');
                    showToast('Removed from My List');
                }
            }
        })
        .catch(function(error) {
            console.error('Favorite toggle error:', error);
        });
    }

    // ============================================================
    // MOBILE FILTER DRAWER
    // ============================================================
    function initMobileDrawer() {
        var toggleBtn = document.getElementById('ms-filter-toggle');
        var drawer = document.getElementById('ms-filter-drawer');
        var backdrop = document.getElementById('ms-drawer-close');
        var closeBtn = document.getElementById('ms-drawer-close-btn');
        var applyBtn = document.getElementById('ms-drawer-apply');
        var mobileFilters = document.getElementById('ms-mobile-filters');
        var sidebarForm = document.getElementById('ms-filter-form');

        if (!toggleBtn || !drawer) return;

        // Clone sidebar to mobile
        if (mobileFilters && sidebarForm) {
            mobileFilters.innerHTML = sidebarForm.innerHTML;
            // Remove apply button from cloned content
            var clonedApply = mobileFilters.querySelector('.ms-sb-apply');
            if (clonedApply) clonedApply.remove();
        }

        function openDrawer() {
            drawer.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        toggleBtn.addEventListener('click', openDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                var form = mobileFilters.querySelector('form') || mobileFilters.querySelector('#ms-filter-form');
                if (form) {
                    form.submit();
                }
                closeDrawer();
            });
        }

        // Close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });
    }

    // ============================================================
    // MODAL
    // ============================================================
    function initModal() {
        var modal = document.getElementById('ms-no-video-modal');
        var closeBtn = document.getElementById('ms-modal-close');
        var backdrop = modal ? modal.querySelector('.ms-modal__backdrop') : null;

        if (!modal) return;

        function closeModal() {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    }

    function showNoVideoModal() {
        var modal = document.getElementById('ms-no-video-modal');
        if (modal) {
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
    }

    // ============================================================
    // TOAST NOTIFICATION
    // ============================================================
    function showToast(message, duration) {
        duration = duration || 2500;
        
        var existing = document.querySelector('.ms-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.className = 'ms-toast';
        toast.textContent = message;
        toast.innerHTML = '<span class="ms-toast__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span><span>' + message + '</span>';
        
        var style = document.createElement('style');
        style.textContent = 
            '.ms-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#222;color:#fff;padding:12px 24px;border-radius:8px;font-size:0.9rem;z-index:99999;display:flex;align-items:center;gap:8px;animation:ms-toast-in 0.3s ease;box-shadow:0 4px 20px rgba(0,0,0,0.5);}' +
            '.ms-toast__icon{color:#4ade80;}' +
            '@keyframes ms-toast-in{from{opacity:0;transform:translateX(-50%) translateY(20px);}to{opacity:1;transform:translateX(-50%) translateY(0);}}' +
            '@keyframes ms-toast-out{from{opacity:1;transform:translateX(-50%) translateY(0);}to{opacity:0;transform:translateX(-50%) translateY(20px);}}';
        document.head.appendChild(style);
        document.body.appendChild(toast);

        setTimeout(function() {
            toast.style.animation = 'ms-toast-out 0.3s ease forwards';
            setTimeout(function() { toast.remove(); style.remove(); }, 300);
        }, duration);
    }

    // Expose for global use
    window.showNoVideoModal = showNoVideoModal;
    window.showToast = showToast;

})();
