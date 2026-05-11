<?php
/**
 * Template Name: Watch History (Premium)
 * Netflix-style watch history matching premium streaming UI
 */
defined('ABSPATH') or die;

// ============================================================
// GET USER HISTORY DATA
// ============================================================
$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

// Get history data for JavaScript
$history_data = [];

// Database history (for logged-in users)
$db_history = [];
if ($is_logged_in) {
    global $wpdb;
    $table = $wpdb->prefix . 'movie_progress';
    $db_history = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, current_time, duration, progress_percent, updated_at FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 200",
        $user_id
    ));

    if (!empty($db_history)) {
        foreach ($db_history as $row) {
            $post_id = (int) $row->post_id;
            if (get_post_status($post_id) !== 'publish') continue;

            $post_type = get_post_type($post_id);
            $title = get_the_title($post_id);
            $thumb = get_the_post_thumbnail_url($post_id, 'medium') ?: '';
            $year = get_post_meta($post_id, '_release_year', true) ?: '';
            $runtime = get_post_meta($post_id, '_duration', true) ?: '';

            // Get genres
            $genres = [];
            $terms = get_the_terms($post_id, 'genre');
            if ($terms && !is_wp_error($terms)) {
                $genres = array_map(function($t) { return $t->name; }, $terms);
            }

            $watch_url = home_url('/watch/?movie_id=' . $post_id);
            if ($post_type === 'episode') {
                $watch_url = home_url('/watch/?episode_id=' . $post_id);
            }

            $history_data[$post_id] = [
                'id' => $post_id,
                'title' => $title,
                'type' => $post_type,
                'thumb' => $thumb,
                'year' => $year,
                'runtime' => $runtime,
                'genres' => array_slice($genres, 0, 3),
                'watchUrl' => $watch_url,
                'detailUrl' => get_permalink($post_id),
                'progress' => [
                    'currentTime' => (int) $row->current_time,
                    'duration' => (int) $row->duration,
                    'percent' => (int) $row->progress_percent,
                    'updatedAt' => strtotime($row->updated_at) * 1000
                ]
            ];
        }
    }
}

// URL helpers
$movies_url = home_url('/movies/');
$tv_url = home_url('/tv/');
$trending_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('trending') : home_url('/trending/');
$watch_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : home_url('/watch/');

// Nonce for AJAX
$history_nonce = wp_create_nonce('mu_history_nonce');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Watch History', 'astra-child'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/ms-history.css')); ?>">
    <style>
        html { margin-top: 0 !important; }
        body { margin-top: 0 !important; }
        #wpadminbar { display: none !important; }
        * { box-sizing: border-box; }
    </style>
</head>
<body class="hist-page hist-body">
<?php wp_body_open(); ?>

<!-- ============================================================ -->
<!-- HEADER (uses global header from streaming template) -->
<!-- ============================================================ -->
<?php get_template_part('template-parts/streaming/header'); ?>

<!-- ============================================================ -->
<!-- PAGE WRAPPER -->
<!-- ============================================================ -->
<div class="hist-wrap">

    <!-- ============================================================ -->
    <!-- PAGE HEADER -->
    <!-- ============================================================ -->
    <div class="hist-header">
        <div class="hist-header__left">
            <h1 class="hist-header__title">Watch History</h1>
            <p class="hist-header__subtitle">Keep track of what you've watched. Clear individual titles or your entire history anytime.</p>
        </div>
        <div class="hist-header__right">
            <button type="button" class="hist-btn hist-btn--outline" id="histClearAll">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                </svg>
                Clear All History
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- STATS PANEL -->
    <!-- ============================================================ -->
    <div class="hist-stats">
        <div class="hist-stat">
            <span class="hist-stat__value" id="statTotal">0</span>
            <span class="hist-stat__label">Total Watched</span>
        </div>
        <div class="hist-stat">
            <span class="hist-stat__value" id="statMovies">0</span>
            <span class="hist-stat__label">Movies</span>
        </div>
        <div class="hist-stat">
            <span class="hist-stat__value" id="statTV">0</span>
            <span class="hist-stat__label">TV Shows</span>
        </div>
        <div class="hist-stat">
            <span class="hist-stat__value" id="statCompleted">0</span>
            <span class="hist-stat__label">Completed</span>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MAIN LAYOUT: SIDEBAR + CONTENT -->
    <!-- ============================================================ -->
    <div class="hist-layout">

        <!-- ============================================================ -->
        <!-- SIDEBAR FILTERS -->
        <!-- ============================================================ -->
        <aside class="hist-sidebar">

            <!-- FILTERS Group -->
            <h3 class="hist-sidebar__group-title">Filters</h3>
            <ul class="hist-filter-list">
                <li>
                    <button class="hist-filter-btn is-active" data-filter="all">
                        <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span class="hist-filter-btn__label">All History</span>
                        <span class="hist-filter-btn__count" data-count="all">0</span>
                    </button>
                </li>
                <li>
                    <button class="hist-filter-btn" data-filter="movie">
                        <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                            <line x1="7" y1="2" x2="7" y2="22"></line>
                            <line x1="17" y1="2" x2="17" y2="22"></line>
                        </svg>
                        <span class="hist-filter-btn__label">Movies</span>
                        <span class="hist-filter-btn__count" data-count="movie">0</span>
                    </button>
                </li>
                <li>
                    <button class="hist-filter-btn" data-filter="tv_show">
                        <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 7 16 12 23 17 23 7"></polyline>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        <span class="hist-filter-btn__label">TV Shows</span>
                        <span class="hist-filter-btn__count" data-count="tv_show">0</span>
                    </button>
                </li>
            </ul>

            <div class="hist-sidebar__divider"></div>

            <!-- TIME PERIOD Group -->
            <h3 class="hist-sidebar__group-title">Time Period</h3>
            <ul class="hist-filter-list">
                <li>
                    <button class="hist-filter-btn" data-filter="today">
                        <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                        <span class="hist-filter-btn__label">Today</span>
                        <span class="hist-filter-btn__count" data-count="today">0</span>
                    </button>
                </li>
                <li>
                    <button class="hist-filter-btn" data-filter="yesterday">
                        <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 4v6h6"></path>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                        </svg>
                        <span class="hist-filter-btn__label">Yesterday</span>
                        <span class="hist-filter-btn__count" data-count="yesterday">0</span>
                    </button>
                </li>
                <li>
                    <button class="hist-filter-btn" data-filter="last7days">
                        <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span class="hist-filter-btn__label">Last 7 Days</span>
                        <span class="hist-filter-btn__count" data-count="last7days">0</span>
                    </button>
                </li>
                <li>
                    <button class="hist-filter-btn" data-filter="last30days">
                        <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                        </svg>
                        <span class="hist-filter-btn__label">Last 30 Days</span>
                        <span class="hist-filter-btn__count" data-count="last30days">0</span>
                    </button>
                </li>
                <li>
                    <button class="hist-filter-btn" data-filter="older">
                        <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path>
                        </svg>
                        <span class="hist-filter-btn__label">Older</span>
                        <span class="hist-filter-btn__count" data-count="older">0</span>
                    </button>
                </li>
            </ul>

            <div class="hist-sidebar__divider"></div>

            <!-- Settings -->
            <button class="hist-filter-btn hist-settings-btn" data-action="settings">
                <svg class="hist-filter-btn__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span class="hist-filter-btn__label">Settings</span>
            </button>

        </aside>

        <!-- ============================================================ -->
        <!-- MAIN CONTENT -->
        <!-- ============================================================ -->
        <main class="hist-content">

            <!-- Empty State -->
            <div class="hist-empty" id="histEmpty">
                <div class="hist-empty__icon">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h3 class="hist-empty__title">No watch history yet</h3>
                <p class="hist-empty__desc">Start watching movies and TV shows to see your history here. Your progress will be saved automatically.</p>
                <div class="hist-empty__actions">
                    <a href="<?php echo esc_url($movies_url); ?>" class="hist-btn hist-btn--primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                            <line x1="7" y1="2" x2="7" y2="22"></line>
                            <line x1="17" y1="2" x2="17" y2="22"></line>
                        </svg>
                        Explore Movies
                    </a>
                    <a href="<?php echo esc_url($tv_url); ?>" class="hist-btn hist-btn--ghost">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 7 16 12 23 17 23 7"></polyline>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        Explore TV Shows
                    </a>
                    <a href="<?php echo esc_url($trending_url); ?>" class="hist-btn hist-btn--ghost">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                            <polyline points="17 6 23 6 23 12"></polyline>
                        </svg>
                        Trending Now
                    </a>
                </div>
            </div>

            <!-- History List (populated by JS) -->
            <div class="hist-list" id="histList"></div>

            <!-- Load More -->
            <div class="hist-load-more" id="histLoadMore" style="display: none;">
                <button class="hist-btn hist-btn--outline" id="histLoadMoreBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                    Load More
                </button>
                <span class="hist-load-more__info"></span>
            </div>

        </main>
    </div>

</div>

<!-- ============================================================ -->
<!-- CONFIRM CLEAR MODAL -->
<!-- ============================================================ -->
<div class="hist-modal" id="histModal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="hist-modal__backdrop" id="histModalBackdrop"></div>
    <div class="hist-modal__box">
        <div class="hist-modal__icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h3 class="hist-modal__title">Clear All History?</h3>
        <p class="hist-modal__desc">Are you sure you want to clear all watch history? This action cannot be undone.</p>
        <div class="hist-modal__actions">
            <button class="hist-btn hist-btn--ghost" id="histModalCancel">Cancel</button>
            <button class="hist-btn hist-btn--danger" id="histModalConfirm">Clear All</button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ACTION DROPDOWN (single shared) -->
<!-- ============================================================ -->
<div class="hist-dropdown" id="histDropdown" aria-hidden="true">
    <button class="hist-dropdown__item" data-action="continue">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M8 5v14l11-7z"/>
        </svg>
        Continue Watching
    </button>
    <button class="hist-dropdown__item" data-action="details">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        View Details
    </button>
    <button class="hist-dropdown__item" data-action="addlist">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add to My List
    </button>
    <div class="hist-dropdown__divider"></div>
    <button class="hist-dropdown__item hist-dropdown__item--danger" data-action="remove">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
        </svg>
        Remove from History
    </button>
</div>

<!-- ============================================================ -->
<!-- SETTINGS MODAL -->
<!-- ============================================================ -->
<div class="hist-modal hist-settings-modal" id="histSettingsModal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="hist-modal__backdrop" id="histSettingsBackdrop"></div>
    <div class="hist-modal__box hist-settings-modal__box">
        <h3 class="hist-settings-modal__title">History Settings</h3>
        <div class="hist-settings-modal__options">
            <label class="hist-settings-option">
                <div class="hist-settings-option__info">
                    <span class="hist-settings-option__label">Auto-save history</span>
                    <span class="hist-settings-option__desc">Automatically save your watch progress</span>
                </div>
                <div class="hist-settings-toggle">
                    <input type="checkbox" id="settingAutoSave" checked>
                    <span class="hist-settings-toggle__slider"></span>
                </div>
            </label>
            <label class="hist-settings-option">
                <div class="hist-settings-option__info">
                    <span class="hist-settings-option__label">Pause watch history</span>
                    <span class="hist-settings-option__desc">Stop tracking watched content</span>
                </div>
                <div class="hist-settings-toggle">
                    <input type="checkbox" id="settingPause">
                    <span class="hist-settings-toggle__slider"></span>
                </div>
            </label>
        </div>
        <div class="hist-settings-modal__actions">
            <button class="hist-btn hist-btn--ghost" id="histSettingsClose">Close</button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- TOAST NOTIFICATION -->
<!-- ============================================================ -->
<div class="hist-toast" id="histToast" aria-live="polite"></div>

<!-- ============================================================ -->
<!-- DATA + JS -->
<!-- ============================================================ -->
<script>
window.MU_HISTORY_DATA = <?php echo wp_json_encode($history_data); ?>;
window.MU_HISTORY_NONCE = <?php echo wp_json_encode($history_nonce); ?>;
</script>
<script src="<?php echo esc_url(get_theme_file_uri('assets/js/ms-history.js')); ?>"></script>

<?php wp_footer(); ?>
</body>
</html>
