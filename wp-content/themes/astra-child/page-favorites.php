<?php
/**
 * Template Name: My List (Premium OTT)
 * Premium streaming My List page with favorites, continue watching, and filters.
 */
defined('ABSPATH') || exit;
get_header();
get_template_part('template-parts/streaming/header');

// =============================================================================
// GET SAVED IDS (Guest: localStorage via JS, Logged-in: DB)
// =============================================================================
$is_logged_in = is_user_logged_in();
$saved_ids = [];
$watch_history_ids = [];

if ($is_logged_in) {
    global $wpdb;
    $fav_table = $wpdb->prefix . 'movie_favorites';
    $hist_table = $wpdb->prefix . 'movie_watch_history';
    
    // Get favorites
    $saved_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT movie_id FROM {$fav_table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 500",
        get_current_user_id()
    ));
    
    // Get watch history (for continue watching)
    $watch_history_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT movie_id FROM {$hist_table} WHERE user_id = %d GROUP BY movie_id ORDER BY MAX(watched_at) DESC LIMIT 50",
        get_current_user_id()
    ));
}

// Page URLs
$base_u = mu_get_page_url_by_slug('favorites');
$movies_archive = trailingslashit(home_url('movies'));
$tv_archive = trailingslashit(home_url('tv'));
$trending_u = mu_get_page_url_by_slug('trending');
$watch_base = mu_get_page_url_by_slug('watch');

// Get genres for filter sidebar
$all_genres = get_terms([
    'taxonomy' => 'genre',
    'hide_empty' => true,
    'orderby' => 'name',
    'number' => 20,
]);

// Get unique release years
$year_args = [
    'post_type' => ['movie', 'tv_show'],
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
        ['key' => '_release_year', 'compare' => 'EXISTS'],
    ],
];
$all_years_raw = get_posts($year_args);
$unique_years = [];
foreach ($all_years_raw as $pid) {
    $y = get_post_meta($pid, '_release_year', true);
    if ($y && strlen($y) === 4 && is_numeric($y)) {
        $unique_years[(int)$y] = true;
    }
}
krsort($unique_years);
$unique_years = array_keys($unique_years);
if (count($unique_years) > 15) {
    $unique_years = array_slice($unique_years, 0, 15);
}

// Count stats
$movie_count = 0;
$tv_count = 0;
$watch_time = 0;

if (!empty($saved_ids)) {
    foreach ($saved_ids as $pid) {
        $ptype = get_post_type($pid);
        if ($ptype === 'movie') {
            $movie_count++;
            $dur = (int) movie_ui_meta($pid, ['duration', '_duration'], 0);
            $watch_time += $dur;
        } elseif ($ptype === 'tv_show') {
            $tv_count++;
        }
    }
}

// Format watch time
$watch_hours = floor($watch_time / 60);
$watch_mins = $watch_time % 60;
$watch_time_str = $watch_hours > 0 
    ? sprintf(_n('%d hr %d min', '%d hrs %d min', $watch_hours, 'astra-child'), $watch_hours, $watch_mins)
    : sprintf(_n('%d min', '%d mins', $watch_mins, 'astra-child'), $watch_mins);

// AJAX nonce
$ajax_nonce = wp_create_nonce('mu_mylist_ajax');
?>
<style>
/* ============================================
   MY LIST PAGE - PREMIUM OTT LAYOUT
   ============================================ */
.mylist-page {
    background: #08080c;
    min-height: 100vh;
    padding-top: 70px;
}

/* Page Header */
.mylist-page-header {
    position: relative;
    padding: 40px 4% 30px;
    background: linear-gradient(180deg, rgba(20,20,30,0.9) 0%, #08080c 100%);
    margin-bottom: 10px;
}

.mylist-page-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(229,9,20,0.06) 0%, transparent 60%);
    pointer-events: none;
}

.mylist-header-inner {
    max-width: 1600px;
    margin: 0 auto;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 30px;
    flex-wrap: wrap;
}

.mylist-header-text {
    flex: 1;
    min-width: 280px;
}

.mylist-page-title {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 10px;
    letter-spacing: -0.02em;
}

.mylist-page-subtitle {
    font-size: clamp(0.85rem, 1.2vw, 1rem);
    color: rgba(255,255,255,0.5);
    margin: 0;
    line-height: 1.5;
}

/* Stats Cards */
.mylist-stats {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.mylist-stat-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 16px 20px;
    min-width: 120px;
    text-align: center;
}

.mylist-stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
    margin-bottom: 4px;
}

.mylist-stat-label {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Main Layout */
.mylist-main {
    display: flex;
    gap: 30px;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 4% 60px;
}

/* Sidebar */
.mylist-sidebar {
    width: 260px;
    flex-shrink: 0;
}

@media (max-width: 1024px) {
    .mylist-main {
        flex-direction: column;
    }
    .mylist-sidebar {
        width: 100%;
    }
}

.mylist-filter-section {
    margin-bottom: 24px;
}

.mylist-filter-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin: 0 0 12px;
    padding: 0 4px;
}

.mylist-filter-options {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.mylist-filter-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 0.9rem;
    color: rgba(255,255,255,0.7);
}

.mylist-filter-option:hover {
    background: rgba(255,255,255,0.06);
    color: #fff;
}

.mylist-filter-option.is-active {
    background: rgba(229,9,20,0.15);
    color: #e50914;
}

.mylist-filter-option input {
    display: none;
}

.mylist-filter-checkbox {
    width: 16px;
    height: 16px;
    border: 1.5px solid rgba(255,255,255,0.3);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    flex-shrink: 0;
}

.mylist-filter-option.is-active .mylist-filter-checkbox {
    background: #e50914;
    border-color: #e50914;
}

.mylist-filter-checkbox::after {
    content: '✓';
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    display: none;
}

.mylist-filter-option.is-active .mylist-filter-checkbox::after {
    display: block;
}

/* Filter Actions */
.mylist-filter-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.mylist-filter-btn {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    border: none;
}

.mylist-filter-btn--clear {
    background: rgba(255,255,255,0.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.12);
}

.mylist-filter-btn--clear:hover {
    background: rgba(255,255,255,0.12);
}

.mylist-filter-btn--remove {
    background: rgba(229,9,20,0.1);
    color: #e50914;
    border: 1px solid rgba(229,9,20,0.2);
}

.mylist-filter-btn--remove:hover {
    background: rgba(229,9,20,0.2);
}

/* Content */
.mylist-content {
    flex: 1;
    min-width: 0;
}

/* Section */
.mylist-section {
    margin-bottom: 50px;
}

.mylist-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.mylist-section-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.mylist-section-count {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.4);
    margin-left: 10px;
}

.mylist-section-link {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    transition: color 0.2s;
}

.mylist-section-link:hover {
    color: #e50914;
}

/* Horizontal Scroll Row */
.mylist-scroll-row {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    padding-bottom: 15px;
}

.mylist-scroll-row::-webkit-scrollbar {
    display: none;
}

/* Continue Watching Card */
.mylist-contcard {
    flex-shrink: 0;
    width: 220px;
    cursor: pointer;
    transition: transform 0.3s;
}

.mylist-contcard:hover {
    transform: scale(1.03);
}

.mylist-contcard__poster-wrap {
    position: relative;
    aspect-ratio: 16/9;
    border-radius: 10px;
    overflow: hidden;
    background: #1a1a2e;
    margin-bottom: 10px;
}

.mylist-contcard__poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mylist-contcard__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 12px;
}

.mylist-contcard__progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: rgba(255,255,255,0.2);
}

.mylist-contcard__progress-bar {
    height: 100%;
    background: #e50914;
    transition: width 0.3s;
}

.mylist-contcard__actions {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.mylist-contcard__btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,0.8);
    background: rgba(42, 42, 42, 0.9);
    backdrop-filter: blur(6px);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0;
}

.mylist-contcard__btn:hover {
    transform: scale(1.12);
    background: rgba(255, 255, 255, 0.2);
}

.mylist-contcard__btn--play {
    background: #fff;
    border-color: #fff;
    color: #000;
}

.mylist-contcard__btn--play:hover {
    background: rgba(255, 255, 255, 0.9);
}

.mylist-contcard__remaining {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.7);
}

.mylist-contcard__title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mylist-contcard__meta {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
}

/* Saved Card */
.mylist-card {
    flex-shrink: 0;
    width: 160px;
    cursor: pointer;
    transition: transform 0.3s;
}

@media (min-width: 768px) {
    .mylist-card {
        width: 180px;
    }
}

.mylist-card:hover {
    transform: scale(1.05);
}

.mylist-card__poster-wrap {
    position: relative;
    aspect-ratio: 2/3;
    border-radius: 10px;
    overflow: hidden;
    background: #1a1a2e;
    margin-bottom: 10px;
}

.mylist-card__poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mylist-card__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.4) 40%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 12px;
    border-radius: 10px;
}

.mylist-card:hover .mylist-card__overlay {
    opacity: 1;
}

.mylist-card__actions {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.mylist-card__btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,0.8);
    background: rgba(42, 42, 42, 0.9);
    backdrop-filter: blur(6px);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0;
}

.mylist-card__btn:hover {
    transform: scale(1.12);
    background: rgba(255, 255, 255, 0.2);
}

.mylist-card__btn--play {
    background: #fff;
    border-color: #fff;
    color: #000;
}

.mylist-card__btn--play:hover {
    background: rgba(255, 255, 255, 0.9);
}

.mylist-card__btn--remove.is-on {
    background: rgba(229, 9, 20, 0.3);
    border-color: rgba(229, 9, 20, 0.8);
}

.mylist-card__info {
    text-align: left;
}

.mylist-card__title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mylist-card__meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.7rem;
    color: rgba(255,255,255,0.5);
}

.mylist-card__rating {
    color: #ffd700;
}

/* Empty State */
.mylist-empty {
    text-align: center;
    padding: 60px 20px;
}

.mylist-empty__icon {
    font-size: 3rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.mylist-empty__title {
    font-size: 1.3rem;
    color: #fff;
    margin: 0 0 8px;
    font-weight: 700;
}

.mylist-empty__desc {
    color: rgba(255,255,255,0.5);
    margin: 0 0 24px;
}

.mylist-empty__actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.mylist-empty__btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s;
}

.mylist-empty__btn--primary {
    background: #e50914;
    color: #fff;
}

.mylist-empty__btn--primary:hover {
    background: #ff1a25;
}

.mylist-empty__btn--secondary {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
}

.mylist-empty__btn--secondary:hover {
    background: rgba(255,255,255,0.12);
}

/* Guest Notice */
.mylist-guest-notice {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.mylist-guest-notice__text {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.7);
}

.mylist-guest-notice__link {
    color: #e50914;
    text-decoration: none;
}

.mylist-guest-notice__link:hover {
    text-decoration: underline;
}

/* Mobile Filter Toggle */
.mylist-mobile-filter {
    display: none;
    margin-bottom: 20px;
}

@media (max-width: 1024px) {
    .mylist-mobile-filter {
        display: block;
    }
}

.mylist-mobile-filter-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 8px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
}

.mylist-sidebar--mobile {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1000;
    background: rgba(0,0,0,0.8);
}

.mylist-sidebar--mobile.is-open {
    display: flex;
}

.mylist-sidebar--mobile .mylist-sidebar {
    width: 300px;
    max-width: 85%;
    background: #12121a;
    padding: 20px;
    overflow-y: auto;
}

.mylist-sidebar-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Loading */
.mylist-loading {
    display: flex;
    justify-content: center;
    padding: 40px;
}

.mylist-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(255,255,255,0.1);
    border-top-color: #e50914;
    border-radius: 50%;
    animation: mylist-spin 0.8s linear infinite;
}

@keyframes mylist-spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .mylist-stats {
        width: 100%;
        justify-content: space-between;
    }
    
    .mylist-stat-card {
        flex: 1;
        min-width: 80px;
        padding: 12px;
    }
    
    .mylist-stat-value {
        font-size: 1.4rem;
    }
    
    .mylist-contcard {
        width: 200px;
    }
    
    .mylist-card {
        width: 140px;
    }
    
    .mylist-card__title {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .mylist-page-header {
        padding: 25px 4% 20px;
    }
    
    .mylist-stats {
        gap: 10px;
    }
    
    .mylist-stat-card {
        padding: 10px;
    }
    
    .mylist-stat-value {
        font-size: 1.2rem;
    }
    
    .mylist-stat-label {
        font-size: 0.65rem;
    }
}
</style>

<div class="mylist-page">
    <?php // =====================================================================
          // PAGE HEADER
          // ===================================================================== ?>
    <div class="mylist-page-header">
        <div class="mylist-header-inner">
            <div class="mylist-header-text">
                <h1 class="mylist-page-title">My List</h1>
                <p class="mylist-page-subtitle">
                    Your saved movies and TV shows in one place.<br>
                    Access them anytime, anywhere.
                </p>
            </div>
            <div class="mylist-stats">
                <div class="mylist-stat-card">
                    <div class="mylist-stat-value"><?php echo esc_html($movie_count); ?></div>
                    <div class="mylist-stat-label"><?php esc_html_e('Movies', 'astra-child'); ?></div>
                </div>
                <div class="mylist-stat-card">
                    <div class="mylist-stat-value"><?php echo esc_html($tv_count); ?></div>
                    <div class="mylist-stat-label"><?php esc_html_e('TV Shows', 'astra-child'); ?></div>
                </div>
                <div class="mylist-stat-card">
                    <div class="mylist-stat-value"><?php echo esc_html($watch_hours . 'h'); ?></div>
                    <div class="mylist-stat-label"><?php esc_html_e('Watch Time', 'astra-child'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php // =====================================================================
          // MAIN CONTENT
          // ===================================================================== ?>
    <div class="mylist-main">
        
        <?php // Mobile Filter Toggle ?>
        <div class="mylist-mobile-filter">
            <button class="mylist-mobile-filter-btn" onclick="toggleMylistSidebar()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="4" y1="6" x2="20" y2="6"/>
                    <line x1="4" y1="12" x2="20" y2="12"/>
                    <line x1="4" y1="18" x2="20" y2="18"/>
                </svg>
                <?php esc_html_e('Filters & Sort', 'astra-child'); ?>
            </button>
        </div>
        
        <?php // =====================================================================
              // SIDEBAR FILTERS
              // ===================================================================== ?>
        <aside class="mylist-sidebar" id="mylistSidebar">
            
            <?php // Sort By ?>
            <div class="mylist-filter-section">
                <h3 class="mylist-filter-title"><?php esc_html_e('Sort By', 'astra-child'); ?></h3>
                <div class="mylist-filter-options">
                    <label class="mylist-filter-option is-active" data-sort="recent">
                        <input type="radio" name="sort" value="recent" checked>
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('Recently Added', 'astra-child'); ?>
                    </label>
                    <label class="mylist-filter-option" data-sort="rating">
                        <input type="radio" name="sort" value="rating">
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('Highest Rated', 'astra-child'); ?>
                    </label>
                    <label class="mylist-filter-option" data-sort="release">
                        <input type="radio" name="sort" value="release">
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('Latest Release', 'astra-child'); ?>
                    </label>
                    <label class="mylist-filter-option" data-sort="az">
                        <input type="radio" name="sort" value="az">
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('A-Z', 'astra-child'); ?>
                    </label>
                </div>
            </div>
            
            <?php // Type ?>
            <div class="mylist-filter-section">
                <h3 class="mylist-filter-title"><?php esc_html_e('Type', 'astra-child'); ?></h3>
                <div class="mylist-filter-options">
                    <label class="mylist-filter-option is-active" data-type="all">
                        <input type="radio" name="type" value="all" checked>
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('All', 'astra-child'); ?>
                    </label>
                    <label class="mylist-filter-option" data-type="movie">
                        <input type="radio" name="type" value="movie">
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('Movies', 'astra-child'); ?>
                    </label>
                    <label class="mylist-filter-option" data-type="tv_show">
                        <input type="radio" name="type" value="tv_show">
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('TV Shows', 'astra-child'); ?>
                    </label>
                </div>
            </div>
            
            <?php // Genre ?>
            <div class="mylist-filter-section">
                <h3 class="mylist-filter-title"><?php esc_html_e('Genre', 'astra-child'); ?></h3>
                <div class="mylist-filter-options">
                    <label class="mylist-filter-option is-active" data-genre="all">
                        <input type="checkbox" name="genre" value="all" checked>
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('All Genres', 'astra-child'); ?>
                    </label>
                    <?php foreach (array_slice($all_genres, 0, 8) as $genre) : ?>
                        <label class="mylist-filter-option" data-genre="<?php echo esc_attr($genre->slug); ?>">
                            <input type="checkbox" name="genre" value="<?php echo esc_attr($genre->slug); ?>">
                            <span class="mylist-filter-checkbox"></span>
                            <?php echo esc_html($genre->name); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php // Release Year ?>
            <div class="mylist-filter-section">
                <h3 class="mylist-filter-title"><?php esc_html_e('Release Year', 'astra-child'); ?></h3>
                <div class="mylist-filter-options">
                    <label class="mylist-filter-option is-active" data-year="all">
                        <input type="checkbox" name="year" value="all" checked>
                        <span class="mylist-filter-checkbox"></span>
                        <?php esc_html_e('All Years', 'astra-child'); ?>
                    </label>
                    <?php foreach (array_slice($unique_years, 0, 6) as $year) : ?>
                        <label class="mylist-filter-option" data-year="<?php echo esc_attr($year); ?>">
                            <input type="checkbox" name="year" value="<?php echo esc_attr($year); ?>">
                            <span class="mylist-filter-checkbox"></span>
                            <?php echo esc_html($year); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php // Filter Actions ?>
            <div class="mylist-filter-actions">
                <button class="mylist-filter-btn mylist-filter-btn--clear" onclick="clearAllFilters()">
                    <?php esc_html_e('Clear All Filters', 'astra-child'); ?>
                </button>
                <button class="mylist-filter-btn mylist-filter-btn--remove" onclick="removeWatchedItems()">
                    <?php esc_html_e('Remove Watched', 'astra-child'); ?>
                </button>
            </div>
        </aside>
        
        <?php // =====================================================================
              // CONTENT AREA
              // ===================================================================== ?>
        <div class="mylist-content">
            
            <?php // Guest Notice ?>
            <?php if (!$is_logged_in) : ?>
                <div class="mylist-guest-notice">
                    <span class="mylist-guest-notice__text">
                        <?php esc_html_e('Sign in to sync your list across devices.', 'astra-child'); ?>
                    </span>
                    <a href="<?php echo esc_url(wp_login_url(mu_get_page_url_by_slug('favorites'))); ?>" class="mylist-empty__btn mylist-empty__btn--primary" style="padding: 8px 16px; font-size: 0.85rem;">
                        <?php esc_html_e('Sign In', 'astra-child'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php // Continue Watching Section ?>
            <section class="mylist-section" id="continueWatchingSection">
                <div class="mylist-section-header">
                    <h2 class="mylist-section-title">
                        <?php esc_html_e('Continue Watching', 'astra-child'); ?>
                        <span class="mylist-section-count" id="continueCount">(<?php echo count($watch_history_ids); ?>)</span>
                    </h2>
                    <?php if (count($watch_history_ids) > 6) : ?>
                        <a href="<?php echo esc_url(mu_get_page_url_by_slug('history')); ?>" class="mylist-section-link">
                            <?php esc_html_e('View All', 'astra-child'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="mylist-scroll-row" id="continueWatchingRow" data-mylist-contmount>
                    <?php
                    // Display first 6 watch history items
                    $cont_items = array_slice($watch_history_ids, 0, 6);
                    foreach ($cont_items as $pid) :
                        $title = get_the_title($pid);
                        $poster = get_the_post_thumbnail_url($pid, 'medium') ?: '';
                        $ptype = get_post_type($pid);
                        $year = movie_ui_meta($pid, ['year', '_release_year'], '');
                        $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                        $trailer = movie_ui_meta($pid, ['trailer_url', '_trailer_url'], '');
                        $video = movie_ui_meta($pid, ['video_url', '_video_url'], '');
                        $watch_url = add_query_arg('id', $pid, $watch_base);
                        $detail_url = get_permalink($pid);
                        $play_action = 'watch:' . esc_url($watch_url);
                        $progress = 35; // Placeholder - in real implementation, get from localStorage/DB
                        $remaining = '45m remaining';
                    ?>
                        <article class="mylist-contcard"
                                 data-id="<?php echo esc_attr($pid); ?>"
                                 data-url="<?php echo esc_url($detail_url); ?>"
                                 data-watch="<?php echo esc_url($watch_url); ?>"
                                 data-play-action="<?php echo esc_attr($play_action); ?>">
                            <div class="mylist-contcard__poster-wrap">
                                <img class="mylist-contcard__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                                <div class="mylist-contcard__overlay">
                                    <div class="mylist-contcard__actions">
                                        <?php if ($play_action) : ?>
                                            <button class="mylist-contcard__btn mylist-contcard__btn--play" data-action="<?php echo esc_attr($play_action); ?>">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        <button class="mylist-contcard__btn" data-more data-url="<?php echo esc_url($detail_url); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="6" r="1.5"/>
                                                <circle cx="12" cy="12" r="1.5"/>
                                                <circle cx="12" cy="18" r="1.5"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="mylist-contcard__remaining"><?php echo esc_html($remaining); ?></div>
                                </div>
                                <div class="mylist-contcard__progress">
                                    <div class="mylist-contcard__progress-bar" style="width: <?php echo esc_attr($progress); ?>%"></div>
                                </div>
                            </div>
                            <h3 class="mylist-contcard__title"><?php echo esc_html($title); ?></h3>
                            <p class="mylist-contcard__meta">
                                <?php echo esc_html($ptype === 'tv_show' ? __('TV Show', 'astra-child') : __('Movie', 'astra-child')); ?>
                                <?php if ($year) : ?> • <?php echo esc_html($year); endif; ?>
                                <?php if ($rating) : ?> • ★ <?php echo esc_html($rating); endif; ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                    
                    <?php if (empty($cont_items)) : ?>
                        <div class="mylist-empty" style="width: 100%; padding: 40px 20px;">
                            <p class="mylist-empty__desc"><?php esc_html_e('No watch progress yet. Start watching to see your progress here.', 'astra-child'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <?php // Saved Movies Section ?>
            <section class="mylist-section" id="savedMoviesSection">
                <div class="mylist-section-header">
                    <h2 class="mylist-section-title">
                        <?php esc_html_e('Movies', 'astra-child'); ?>
                        <span class="mylist-section-count">(<?php echo esc_html($movie_count); ?>)</span>
                    </h2>
                    <?php if ($movie_count > 6) : ?>
                        <a href="<?php echo esc_url(add_query_arg(['type' => 'movie'], $base_u)); ?>" class="mylist-section-link">
                            <?php esc_html_e('View All', 'astra-child'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="mylist-scroll-row" id="savedMoviesRow">
                    <?php
                    $movie_ids = array_filter($saved_ids, function($pid) {
                        return get_post_type($pid) === 'movie';
                    });
                    foreach (array_slice($movie_ids, 0, 12) as $pid) :
                        $title = get_the_title($pid);
                        $poster = get_the_post_thumbnail_url($pid, 'medium') ?: '';
                        $year = movie_ui_meta($pid, ['year', '_release_year'], '');
                        $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                        $trailer = movie_ui_meta($pid, ['trailer_url', '_trailer_url'], '');
                        $video = movie_ui_meta($pid, ['video_url', '_video_url'], '');
                        $watch_url = add_query_arg('id', $pid, $watch_base);
                        $detail_url = get_permalink($pid);
                        $play_action = 'watch:' . esc_url($watch_url);
                        ?>
                        <article class="mylist-card"
                                 data-id="<?php echo esc_attr($pid); ?>"
                                 data-url="<?php echo esc_url($detail_url); ?>"
                                 data-play-action="<?php echo esc_attr($play_action); ?>">
                            <div class="mylist-card__poster-wrap">
                                <img class="mylist-card__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                                <div class="mylist-card__overlay">
                                    <div class="mylist-card__actions">
                                        <?php if ($play_action) : ?>
                                            <button class="mylist-card__btn mylist-card__btn--play" data-action="<?php echo esc_attr($play_action); ?>">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        <button class="mylist-card__btn mylist-card__btn--remove is-on" data-remove="<?php echo esc_attr($pid); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                        </button>
                                        <button class="mylist-card__btn" data-more data-url="<?php echo esc_url($detail_url); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="6" r="1.5"/>
                                                <circle cx="12" cy="12" r="1.5"/>
                                                <circle cx="12" cy="18" r="1.5"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="mylist-card__info">
                                <h3 class="mylist-card__title"><?php echo esc_html($title); ?></h3>
                                <div class="mylist-card__meta">
                                    <?php if ($year) : ?><span><?php echo esc_html(substr($year, 0, 4)); ?></span><?php endif; ?>
                                    <?php if ($rating) : ?><span class="mylist-card__rating">★ <?php echo esc_html($rating); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    
                    <?php if (empty($movie_ids)) : ?>
                        <div class="mylist-empty" style="width: 100%;">
                            <p class="mylist-empty__desc"><?php esc_html_e('No saved movies yet. Browse our catalog to add some!', 'astra-child'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <?php // Saved TV Shows Section ?>
            <section class="mylist-section" id="savedTvSection">
                <div class="mylist-section-header">
                    <h2 class="mylist-section-title">
                        <?php esc_html_e('TV Shows', 'astra-child'); ?>
                        <span class="mylist-section-count">(<?php echo esc_html($tv_count); ?>)</span>
                    </h2>
                    <?php if ($tv_count > 6) : ?>
                        <a href="<?php echo esc_url(add_query_arg(['type' => 'tv_show'], $base_u)); ?>" class="mylist-section-link">
                            <?php esc_html_e('View All', 'astra-child'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="mylist-scroll-row" id="savedTvRow">
            <?php
                    $tv_ids = array_filter($saved_ids, function($pid) {
                        return get_post_type($pid) === 'tv_show';
                    });
                    foreach (array_slice($tv_ids, 0, 12) as $pid) :
                        $title = get_the_title($pid);
                        $poster = get_the_post_thumbnail_url($pid, 'medium') ?: '';
                        $year = movie_ui_meta($pid, ['year', '_release_year'], '');
                        $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                        $trailer = movie_ui_meta($pid, ['trailer_url', '_trailer_url'], '');
                        $video = movie_ui_meta($pid, ['video_url', '_video_url'], '');
                        $watch_url = add_query_arg('id', $pid, $watch_base);
                        $detail_url = get_permalink($pid);
                        $play_action = 'watch:' . esc_url($watch_url);
                        
                        // Get season count
                        $eps = get_posts([
                            'post_type' => 'episode',
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'meta_query' => [['key' => 'tv_show_id', 'value' => $pid, 'compare' => '=']]
                        ]);
                        $seasons = [];
                        foreach ($eps as $eid) {
                            $sn = (int) get_post_meta($eid, 'season_number', true);
                            if ($sn) $seasons[$sn] = true;
                        }
                        $season_count = count($seasons);
                        ?>
                        <article class="mylist-card"
                                 data-id="<?php echo esc_attr($pid); ?>"
                                 data-url="<?php echo esc_url($detail_url); ?>"
                                 data-play-action="<?php echo esc_attr($play_action); ?>">
                            <div class="mylist-card__poster-wrap">
                                <img class="mylist-card__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                                <div class="mylist-card__overlay">
                                    <div class="mylist-card__actions">
                                        <?php if ($play_action) : ?>
                                            <button class="mylist-card__btn mylist-card__btn--play" data-action="<?php echo esc_attr($play_action); ?>">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        <button class="mylist-card__btn mylist-card__btn--remove is-on" data-remove="<?php echo esc_attr($pid); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                        </button>
                                        <button class="mylist-card__btn" data-more data-url="<?php echo esc_url($detail_url); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="6" r="1.5"/>
                                                <circle cx="12" cy="12" r="1.5"/>
                                                <circle cx="12" cy="18" r="1.5"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="mylist-card__info">
                                <h3 class="mylist-card__title"><?php echo esc_html($title); ?></h3>
                                <div class="mylist-card__meta">
                                    <?php if ($season_count) : ?><span><?php echo esc_html($season_count); ?> <?php esc_html_e('Seasons', 'astra-child'); ?></span><?php endif; ?>
                                    <?php if ($rating) : ?><span class="mylist-card__rating">★ <?php echo esc_html($rating); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    
                    <?php if (empty($tv_ids)) : ?>
                        <div class="mylist-empty" style="width: 100%;">
                            <p class="mylist-empty__desc"><?php esc_html_e('No saved TV shows yet. Browse our catalog to add some!', 'astra-child'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <?php // Main Empty State (when nothing saved) ?>
            <?php if (empty($saved_ids) && empty($watch_history_ids)) : ?>
                <div class="mylist-empty">
                    <div class="mylist-empty__icon">📋</div>
                    <h2 class="mylist-empty__title"><?php esc_html_e('Your list is empty', 'astra-child'); ?></h2>
                    <p class="mylist-empty__desc"><?php esc_html_e('Start adding movies and TV shows to your list by tapping the + button on any title.', 'astra-child'); ?></p>
                    <div class="mylist-empty__actions">
                        <a href="<?php echo esc_url($movies_archive); ?>" class="mylist-empty__btn mylist-empty__btn--primary">
                            <?php esc_html_e('Explore Movies', 'astra-child'); ?>
                        </a>
                        <a href="<?php echo esc_url($tv_archive); ?>" class="mylist-empty__btn mylist-empty__btn--secondary">
                            <?php esc_html_e('Explore TV Shows', 'astra-child'); ?>
                        </a>
                        <a href="<?php echo esc_url($trending_u); ?>" class="mylist-empty__btn mylist-empty__btn--secondary">
                            <?php esc_html_e('Trending Now', 'astra-child'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
            </div>

<?php // =====================================================================
      // TRAILER MODAL - Uses global mu-trailer-modal styles from movie-ui.css
      // ===================================================================== ?>
<div id="mu-trailer-modal" class="mu-modal mu-trailer-modal" aria-hidden="true" role="dialog">
    <button class="mu-trailer-modal__close" data-mu-close-trailer aria-label="<?php esc_attr_e('Close', 'astra-child'); ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </button>
    <div class="mu-modal__backdrop" data-mu-close-trailer></div>
    <div class="mu-modal__content mu-modal__content--video mu-trailer-modal-wrapper">
        <div id="mu-trailer-video-area"></div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    var ajaxNonce = '<?php echo esc_attr($ajax_nonce); ?>';
    
    // =====================================================================
    // FILTER HANDLING
    // =====================================================================
    document.querySelectorAll('.mylist-filter-option').forEach(function(opt) {
        opt.addEventListener('click', function() {
            var group = opt.closest('.mylist-filter-section');
            var input = opt.querySelector('input');
            
            // Handle radio buttons (single select)
            if (input && input.type === 'radio') {
                group.querySelectorAll('.mylist-filter-option').forEach(function(o) {
                    o.classList.remove('is-active');
                });
                opt.classList.add('is-active');
                input.checked = true;
            }
            // Handle checkboxes (multi select)
            else if (input && input.type === 'checkbox') {
                opt.classList.toggle('is-active');
                input.checked = opt.classList.contains('is-active');
            }
            
            applyFilters();
        });
    });
    
    function applyFilters() {
        var sort = document.querySelector('input[name="sort"]:checked')?.value || 'recent';
        var type = document.querySelector('input[name="type"]:checked')?.value || 'all';
        
        // Show/hide sections based on type filter
        var moviesSection = document.getElementById('savedMoviesSection');
        var tvSection = document.getElementById('savedTvSection');
        
        if (moviesSection) {
            moviesSection.style.display = (type === 'all' || type === 'movie') ? 'block' : 'none';
        }
        if (tvSection) {
            tvSection.style.display = (type === 'all' || type === 'tv_show') ? 'block' : 'none';
        }
    }
    
    window.clearAllFilters = function() {
        // Reset radio buttons
        document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
            radio.checked = radio.value === 'recent' || radio.value === 'all';
        });
        // Reset checkboxes
        document.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
            cb.checked = cb.value === 'all';
        });
        // Reset active states
        document.querySelectorAll('.mylist-filter-option').forEach(function(opt) {
            var input = opt.querySelector('input');
            if (input) {
                if (input.type === 'radio') {
                    opt.classList.toggle('is-active', input.checked);
                } else if (input.type === 'checkbox') {
                    opt.classList.toggle('is-active', input.checked);
                }
            }
        });
        applyFilters();
    };
    
    window.removeWatchedItems = function() {
        // Remove watched items from Continue Watching
        var continueSection = document.getElementById('continueWatchingSection');
        if (continueSection) {
            continueSection.style.display = 'none';
        }
        showToast('Watched items removed from Continue Watching', 'info');
    };
    
    // =====================================================================
    // MOBILE SIDEBAR
    // =====================================================================
    window.toggleMylistSidebar = function() {
        var sidebar = document.getElementById('mylistSidebar');
        if (sidebar) {
            sidebar.classList.toggle('is-open');
        }
    };
    
    // =====================================================================
    // CARD CLICK HANDLERS
    // =====================================================================
    function setupCardHandlers(container, cardClass) {
        if (!container) return;
        
        container.addEventListener('click', function(e) {
            var card = e.target.closest(cardClass);
            if (!card) return;
            
            var btn = e.target.closest('button');
            
            if (btn) {
                e.stopPropagation();
                e.preventDefault();
                
                var action = btn.getAttribute('data-action');
                var removeId = btn.getAttribute('data-remove');
                var moreUrl = btn.getAttribute('data-url');
                
                if (action) {
                    handlePlayAction(action);
                } else if (removeId) {
                    handleRemove(removeId, btn);
                } else if (moreUrl) {
                    window.location.href = moreUrl;
                }
                return;
            }
            
            // Card click - go to detail
            var detailUrl = card.getAttribute('data-url');
            if (detailUrl) {
                window.location.href = detailUrl;
            }
        });
    }
    
    setupCardHandlers(document.getElementById('continueWatchingRow'), '.mylist-contcard');
    setupCardHandlers(document.getElementById('savedMoviesRow'), '.mylist-card');
    setupCardHandlers(document.getElementById('savedTvRow'), '.mylist-card');
    
    // =====================================================================
    // PLAY ACTION
    // =====================================================================
    function handlePlayAction(action) {
        if (!action || action === 'unavailable') {
            showToast('<?php esc_attr_e('No video available', 'astra-child'); ?>', 'info');
            return;
        }
        
        if (action.indexOf('trailer:') === 0) {
            var url = action.replace('trailer:', '');
            openTrailerModal(url);
        } else if (action.indexOf('watch:') === 0) {
            var url = action.replace('watch:', '');
            window.location.href = url;
        }
    }
    
    // =====================================================================
    // REMOVE FROM LIST
    // =====================================================================
    function handleRemove(id, btn) {
        if (!id) return;
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        // Remove card from DOM
                        var card = btn.closest('.mylist-card, .mylist-contcard');
                        if (card) {
                            card.style.transition = 'opacity 0.3s, transform 0.3s';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.8)';
                            setTimeout(function() {
                                card.remove();
                                updateCounts();
                                checkEmptyState();
                            }, 300);
                        }
                        showToast(data.message || '<?php esc_attr_e('Removed from My List', 'astra-child'); ?>', 'success');
                    } else {
                        showToast(data.message || '<?php esc_attr_e('Failed to remove', 'astra-child'); ?>', 'error');
                    }
                } catch (e) {
                    // If AJAX fails, still remove visually for guests
                    var card = btn.closest('.mylist-card, .mylist-contcard');
                    if (card) {
                        card.style.transition = 'opacity 0.3s, transform 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(function() {
                            card.remove();
                            updateCounts();
                            checkEmptyState();
                        }, 300);
                    }
                    // Also remove from localStorage for guests
                    if (!isLoggedIn) {
                        var saved = JSON.parse(localStorage.getItem('mu_favorites') || '[]');
                        saved = saved.filter(function(x) { return x !== parseInt(id); });
                        localStorage.setItem('mu_favorites', JSON.stringify(saved));
                    }
                    showToast('<?php esc_attr_e('Removed from My List', 'astra-child'); ?>', 'success');
                }
            }
        };
        
        if (isLoggedIn) {
            xhr.send(
                'action=movie_ui_remove_favorite' +
                '&nonce=' + ajaxNonce +
                '&post_id=' + id
            );
        } else {
            // For guests, just update localStorage
            var saved = JSON.parse(localStorage.getItem('mu_favorites') || '[]');
            saved = saved.filter(function(x) { return x !== parseInt(id); });
            localStorage.setItem('mu_favorites', JSON.stringify(saved));
            
            var card = btn.closest('.mylist-card, .mylist-contcard');
            if (card) {
                card.style.transition = 'opacity 0.3s, transform 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(function() {
                    card.remove();
                    updateCounts();
                    checkEmptyState();
                }, 300);
            }
            showToast('<?php esc_attr_e('Removed from My List', 'astra-child'); ?>', 'success');
        }
    }
    
    function updateCounts() {
        var movies = document.querySelectorAll('#savedMoviesRow .mylist-card').length;
        var tv = document.querySelectorAll('#savedTvRow .mylist-card').length;
        var moviesCount = document.querySelector('#savedMoviesSection .mylist-section-count');
        var tvCount = document.querySelector('#savedTvSection .mylist-section-count');
        if (moviesCount) moviesCount.textContent = '(' + movies + ')';
        if (tvCount) tvCount.textContent = '(' + tv + ')';
    }
    
    function checkEmptyState() {
        var movies = document.querySelectorAll('#savedMoviesRow .mylist-card').length;
        var tv = document.querySelectorAll('#savedTvRow .mylist-card').length;
        
        if (movies === 0) {
            var moviesSection = document.getElementById('savedMoviesSection');
            var moviesRow = document.getElementById('savedMoviesRow');
            if (moviesRow && !moviesRow.querySelector('.mylist-empty')) {
                moviesRow.innerHTML = '<div class="mylist-empty" style="width: 100%;"><p class="mylist-empty__desc">No saved movies yet.</p></div>';
            }
        }
        
        if (tv === 0) {
            var tvRow = document.getElementById('savedTvRow');
            if (tvRow && !tvRow.querySelector('.mylist-empty')) {
                tvRow.innerHTML = '<div class="mylist-empty" style="width: 100%;"><p class="mylist-empty__desc">No saved TV shows yet.</p></div>';
            }
        }
    }
    
    // =====================================================================
    // TRAILER MODAL - Use global openTrailer
    // =====================================================================
    function openTrailerModal(url) {
        if (!url) return;

        // Use global openTrailer function if available
        if (typeof window.openTrailer === 'function') {
            window.openTrailer(url);
            return;
        }

        var modal = document.getElementById('mu-trailer-modal');
        var area = document.getElementById('mu-trailer-video-area');
        if (!modal || !area) return;

        // Convert YouTube URLs
        var embedUrl = url;
        if (url.indexOf('youtube.com/watch') !== -1) {
            var vid = url.match(/[?&]v=([^&]+)/);
            if (vid) embedUrl = 'https://www.youtube.com/embed/' + vid[1] + '?autoplay=1&rel=0&modestbranding=1';
        } else if (url.indexOf('youtu.be/') !== -1) {
            var vid = url.match(/youtu\.be\/([^?]+)/);
            if (vid) embedUrl = 'https://www.youtube.com/embed/' + vid[1] + '?autoplay=1&rel=0&modestbranding=1';
        }

        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-visible');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';

        area.innerHTML = '<iframe src="' + embedUrl + '" ' +
            'frameborder="0" ' +
            'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ' +
            'allowfullscreen ' +
            'style="width:100%;height:100%;border:none;"></iframe>';
    }

    function closeTrailerModal() {
        var modal = document.getElementById('mu-trailer-modal');
        var area = document.getElementById('mu-trailer-video-area');
        if (!modal) return;

        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-visible');
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
        if (area) area.innerHTML = '';
    }

    document.addEventListener('click', function(e) {
        var modal = document.getElementById('mu-trailer-modal');
        if (modal && (e.target.classList.contains('mu-modal__backdrop') || e.target.closest('[data-mu-close-trailer]'))) {
            closeTrailerModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeTrailerModal();
    });
    
    // =====================================================================
    // TOAST
    // =====================================================================
    function showToast(message, type) {
        type = type || 'info';
        
        if (typeof window.muShowToast === 'function') {
            window.muShowToast(message, type);
            return;
        }
        
        var toast = document.createElement('div');
        toast.className = 'mu-toast mu-toast--' + type;
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:12px 20px;background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;z-index:9999;animation:muToastIn 0.3s ease';
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.style.animation = 'muToastIn 0.3s ease reverse';
            setTimeout(function() {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 2000);
    }
    
})();
</script>

<?php get_footer();
