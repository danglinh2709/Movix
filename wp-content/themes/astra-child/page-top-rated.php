<?php
/**
 * Template Name: Top Rated (Premium OTT)
 * Premium streaming Top Rated page with ranked poster grid.
 */
defined('ABSPATH') || exit;
get_header();
get_template_part('template-parts/streaming/header');

// =============================================================================
// FILTER & QUERY SETUP
// =============================================================================

// Category filter: all, movies, tv
$cat_filter = isset($_GET['cat']) ? sanitize_key((string) $_GET['cat']) : 'all';
if (!in_array($cat_filter, ['all', 'movies', 'tv'], true)) {
    $cat_filter = 'all';
}

// Time range filter: all, year, month, week
$range = isset($_GET['range']) ? sanitize_key((string) $_GET['range']) : 'all';
if (!in_array($range, ['all', 'year', 'month', 'week'], true)) {
    $range = 'all';
}

// Pagination
$page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
$per_page = 24;
$offset = ($page - 1) * $per_page;

// Base URL for filters
$base_u = mu_get_page_url_by_slug('top-rated');
$movies_archive = trailingslashit(home_url('movies'));
$tv_archive = trailingslashit(home_url('tv'));
$watch_base = mu_get_page_url_by_slug('watch');

// Post types based on filter
$post_types = ($cat_filter === 'all') ? ['movie', 'tv_show'] : [$cat_filter === 'movies' ? 'movie' : 'tv_show'];

// =============================================================================
// META KEYS MAPPING
// =============================================================================
// rating (★), tmdb_rating, imdb_rating, popularity, release_year, movie_type

// Build meta query for rating
$meta_query = [];

// Time range filter based on release year
$date_query = [];
$now = new DateTimeImmutable('now', wp_timezone());
if ($range === 'year') {
    $date_query = [['after' => $now->modify('-1 year')->format('Y-m-d') . ' 00:00:00']];
} elseif ($range === 'month') {
    $date_query = [['after' => $now->modify('-1 month')->format('Y-m-d') . ' 00:00:00']];
} elseif ($range === 'week') {
    $date_query = [['after' => $now->modify('-1 week')->format('Y-m-d') . ' 00:00:00']];
}

// Count query for total
$count_args = [
    'post_type'      => $post_types,
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_query'     => $meta_query,
    'date_query'     => $date_query,
    'fields'         => 'ids',
    'ignore_sticky_posts' => true,
];
$total_count = count(get_posts($count_args));
$total_pages = ceil($total_count / $per_page);

// Main query
$toprated_args = [
    'post_type'           => $post_types,
    'posts_per_page'       => $per_page,
    'offset'               => $offset,
    'post_status'          => 'publish',
    'meta_query'           => [
        'relation' => 'OR',
        [
            'key'     => '_rating',
            'compare' => 'EXISTS',
        ],
        [
            'key'     => '_tmdb_rating',
            'compare' => 'EXISTS',
        ],
        [
            'key'     => '_imdb_rating',
            'compare' => 'EXISTS',
        ],
    ],
    'orderby' => [
        '_rating'      => 'DESC',
        '_tmdb_rating' => 'DESC',
        '_imdb_rating' => 'DESC',
        'modified'     => 'DESC',
    ],
    'order'              => 'DESC',
    'date_query'         => $date_query,
    'ignore_sticky_posts' => true,
];

$toprated_q = new WP_Query($toprated_args);

// Calculate current rank offset
$current_rank = $offset + 1;

// Filter labels
$range_options = [
    'all'   => __('All Time', 'astra-child'),
    'year'  => __('This Year', 'astra-child'),
    'month' => __('This Month', 'astra-child'),
    'week'  => __('This Week', 'astra-child'),
];

$showing_start = $total_count > 0 ? $offset + 1 : 0;
$showing_end = min($offset + $per_page, $total_count);

// =============================================================================
// AJAX NONCE
// =============================================================================
$ajax_nonce = wp_create_nonce('mu_toprated_ajax');
?>
<style>
/* ============================================
   TOP RATED PAGE - PREMIUM OTT LAYOUT
   ============================================ */
.toprated-page {
    background: #08080c;
    min-height: 100vh;
    padding-top: 70px;
}

/* Page Header */
.toprated-page-header {
    position: relative;
    padding: 50px 4% 40px;
    background: linear-gradient(180deg, rgba(20,20,30,0.8) 0%, #08080c 100%);
    margin-bottom: 10px;
}

.toprated-page-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 30% 50%, rgba(229,9,20,0.08) 0%, transparent 60%);
    pointer-events: none;
}

.toprated-header-inner {
    max-width: 1400px;
    margin: 0 auto;
}

.toprated-page-title {
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 12px;
    letter-spacing: -0.02em;
    position: relative;
}

.toprated-page-subtitle {
    font-size: clamp(0.9rem, 1.5vw, 1.1rem);
    color: rgba(255,255,255,0.6);
    margin: 0;
    max-width: 500px;
}

/* Filters Bar */
.toprated-filters-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 0 4% 30px;
    max-width: 1400px;
    margin: 0 auto;
    flex-wrap: wrap;
}

/* Category Tabs */
.toprated-filter-tabs {
    display: flex;
    gap: 4px;
    background: rgba(255,255,255,0.08);
    border-radius: 25px;
    padding: 4px;
}

.toprated-filter-tab {
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    background: transparent;
    cursor: pointer;
}

.toprated-filter-tab:hover {
    color: #fff;
    background: rgba(255,255,255,0.05);
}

.toprated-filter-tab.is-active {
    background: #e50914;
    color: #fff;
}

/* Time Dropdown */
.toprated-time-dropdown {
    position: relative;
}

.toprated-time-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 8px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 140px;
    justify-content: space-between;
}

.toprated-time-btn:hover {
    background: rgba(255,255,255,0.12);
}

.toprated-time-btn svg {
    transition: transform 0.2s;
    flex-shrink: 0;
}

.toprated-time-dropdown.is-open .toprated-time-btn svg {
    transform: rotate(180deg);
}

.toprated-time-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #1a1a2e;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 8px 0;
    min-width: 160px;
    z-index: 100;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s;
}

.toprated-time-dropdown.is-open .toprated-time-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.toprated-time-option {
    display: block;
    padding: 10px 16px;
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.15s;
}

.toprated-time-option:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.toprated-time-option.is-active {
    color: #e50914;
}

/* Main Content */
.toprated-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 4% 60px;
}

/* Results Info */
.toprated-results-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.toprated-results-count {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.5);
}

.toprated-results-count strong {
    color: rgba(255,255,255,0.8);
    font-weight: 600;
}

/* Ranked Grid */
.toprated-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

@media (max-width: 1400px) {
    .toprated-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

@media (max-width: 1100px) {
    .toprated-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 800px) {
    .toprated-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
}

@media (max-width: 540px) {
    .toprated-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}

/* Ranked Card */
.toprated-card {
    position: relative;
    cursor: pointer;
    transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

.toprated-card:hover {
    transform: scale(1.05);
}

.toprated-card__poster-wrap {
    position: relative;
    aspect-ratio: 2/3;
    border-radius: 10px;
    overflow: hidden;
    background: #1a1a2e;
    margin-bottom: 10px;
}

.toprated-card__poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.3s ease;
}

.toprated-card:hover .toprated-card__poster {
    transform: scale(1.05);
}

/* Rank Badge */
.toprated-card__rank {
    position: absolute;
    top: -4px;
    left: -4px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, #e50914, #b81d24);
    border-radius: 8px 8px 8px 0;
    box-shadow: 0 4px 12px rgba(229,9,20,0.4);
    z-index: 2;
}

/* Top 3 special styling */
.toprated-card[data-rank="1"] .toprated-card__rank {
    background: linear-gradient(135deg, #ffd700, #ffaa00);
    box-shadow: 0 4px 12px rgba(255,215,0,0.5);
}

.toprated-card[data-rank="2"] .toprated-card__rank {
    background: linear-gradient(135deg, #c0c0c0, #a0a0a0);
    box-shadow: 0 4px 12px rgba(192,192,192,0.4);
}

.toprated-card[data-rank="3"] .toprated-card__rank {
    background: linear-gradient(135deg, #cd7f32, #a66028);
    box-shadow: 0 4px 12px rgba(205,127,50,0.4);
}

/* Card Overlay */
.toprated-card__overlay {
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

.toprated-card:hover .toprated-card__overlay {
    opacity: 1;
}

/* Card Actions */
.toprated-card__actions {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.toprated-card__btn {
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

.toprated-card__btn:hover {
    transform: scale(1.12);
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 1);
}

.toprated-card__btn--play {
    background: #fff;
    border-color: #fff;
    color: #000;
}

.toprated-card__btn--play:hover {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 0.9);
}

.toprated-card__btn svg {
    display: block;
    flex-shrink: 0;
}

/* Favorite active state */
.toprated-card__btn--fav.is-on {
    background: rgba(229, 9, 20, 0.3);
    border-color: rgba(229, 9, 20, 0.8);
}

.toprated-card__btn--fav.is-on .tr-ico-fav-h,
.toprated-card__btn--fav.is-on .tr-ico-plus-h {
    display: none;
}

/* Card Info */
.toprated-card__info {
    text-align: left;
}

.toprated-card__title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.toprated-card__meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
    flex-wrap: wrap;
}

.toprated-card__rating {
    color: #ffd700;
    font-weight: 600;
}

/* Type Badge */
.toprated-card__type {
    display: inline-block;
    font-size: 0.65rem;
    font-weight: 600;
    color: rgba(255,255,255,0.6);
    background: rgba(255,255,255,0.1);
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* Quality Badge */
.toprated-card__quality {
    display: inline-block;
    font-size: 0.65rem;
    font-weight: 700;
    color: #fff;
    background: rgba(255,255,255,0.15);
    padding: 2px 6px;
    border-radius: 4px;
}

/* Load More */
.toprated-loadmore-wrap {
    display: flex;
    justify-content: center;
    padding: 20px 0 40px;
}

.toprated-loadmore-btn {
    padding: 14px 40px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.toprated-loadmore-btn:hover:not(:disabled) {
    background: rgba(255,255,255,0.12);
    border-color: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

.toprated-loadmore-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.toprated-loadmore-btn.is-loading {
    pointer-events: none;
}

.toprated-loadmore-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255,255,255,0.2);
    border-top-color: #fff;
    border-radius: 50%;
    animation: tr-spin 0.8s linear infinite;
    display: none;
}

.toprated-loadmore-btn.is-loading .toprated-loadmore-spinner {
    display: block;
}

/* Empty State */
.toprated-empty {
    text-align: center;
    padding: 80px 20px;
    grid-column: 1 / -1;
}

.toprated-empty__icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.toprated-empty__title {
    font-size: 1.5rem;
    color: #fff;
    margin: 0 0 12px;
    font-weight: 700;
}

.toprated-empty__desc {
    color: rgba(255,255,255,0.5);
    margin: 0 0 30px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.toprated-empty__actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.toprated-empty__btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s;
}

.toprated-empty__btn--primary {
    background: #e50914;
    color: #fff;
}

.toprated-empty__btn--primary:hover {
    background: #ff1a25;
    transform: translateY(-2px);
}

.toprated-empty__btn--secondary {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
}

.toprated-empty__btn--secondary:hover {
    background: rgba(255,255,255,0.12);
    transform: translateY(-2px);
}

/* Loading Skeleton */
.toprated-grid--loading {
    opacity: 0.5;
    pointer-events: none;
}

.toprated-skeleton-card {
    aspect-ratio: 2/3;
    border-radius: 10px;
    background: linear-gradient(90deg, #1a1a2e 0%, #252540 50%, #1a1a2e 100%);
    background-size: 200% 100%;
    animation: tr-shimmer 1.5s infinite;
}

/* Animations */
@keyframes tr-spin {
    to { transform: rotate(360deg); }
}

@keyframes tr-shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Responsive */
@media (max-width: 768px) {
    .toprated-page-header {
        padding: 30px 4% 25px;
    }
    
    .toprated-filters-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .toprated-card__title {
        font-size: 0.8rem;
    }
    
    .toprated-card__meta {
        font-size: 0.7rem;
    }
    
    .toprated-card__rank {
        width: 30px;
        height: 30px;
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {
    .toprated-filter-tabs {
        width: 100%;
        justify-content: center;
    }
    
    .toprated-filter-tab {
        flex: 1;
        text-align: center;
        padding: 8px 12px;
    }
    
    .toprated-results-info {
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
}

/* FIX card phim không đều kích thước */
.toprated-card {
  width: 100% !important;
  min-width: 0 !important;
}

.toprated-card__poster-wrap {
  width: 100% !important;
  aspect-ratio: 2 / 3 !important;
  height: auto !important;
  overflow: hidden !important;
  border-radius: 10px !important;
}

.toprated-card__poster {
  width: 100% !important;
  height: 100% !important;
  aspect-ratio: 2 / 3 !important;
  object-fit: cover !important;
  object-position: center !important;
  display: block !important;
  max-width: none !important;
}
</style>

<div class="toprated-page">
    <?php // =====================================================================
          // PAGE HEADER
          // ===================================================================== ?>
    <div class="toprated-page-header">
        <div class="toprated-header-inner">
            <h1 class="toprated-page-title">Top Rated</h1>
            <p class="toprated-page-subtitle">
                <?php esc_html_e('Explore the highest-rated movies and TV shows as voted by our community.', 'astra-child'); ?>
            </p>
        </div>
    </div>

    <?php // =====================================================================
          // FILTERS BAR
          // ===================================================================== ?>
    <div class="toprated-filters-bar">
        <?php // Category Tabs ?>
        <div class="toprated-filter-tabs">
            <a href="<?php echo esc_url(add_query_arg(['cat' => 'all', 'range' => $range, 'paged' => null], $base_u)); ?>" 
               class="toprated-filter-tab<?php echo $cat_filter === 'all' ? ' is-active' : ''; ?>">
                <?php esc_html_e('All', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg(['cat' => 'movies', 'range' => $range, 'paged' => null], $base_u)); ?>" 
               class="toprated-filter-tab<?php echo $cat_filter === 'movies' ? ' is-active' : ''; ?>">
                <?php esc_html_e('Movies', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg(['cat' => 'tv', 'range' => $range, 'paged' => null], $base_u)); ?>" 
               class="toprated-filter-tab<?php echo $cat_filter === 'tv' ? ' is-active' : ''; ?>">
                <?php esc_html_e('TV Shows', 'astra-child'); ?>
            </a>
        </div>
        
        <?php // Time Dropdown ?>
        <div class="toprated-time-dropdown" id="timeDropdown">
            <button class="toprated-time-btn" type="button" onclick="toggleTimeDropdown()">
                <span id="timeLabel"><?php echo esc_html($range_options[$range]); ?></span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="toprated-time-menu">
                <?php foreach ($range_options as $slug => $label) : ?>
                    <a href="<?php echo esc_url(add_query_arg(['range' => $slug, 'cat' => $cat_filter, 'paged' => null], $base_u)); ?>" 
                       class="toprated-time-option<?php echo $range === $slug ? ' is-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php // =====================================================================
          // MAIN CONTENT
          // ===================================================================== ?>
    <div class="toprated-content">
        
        <?php // Results Info ?>
        <div class="toprated-results-info">
            <span class="toprated-results-count">
                            <?php
                if ($total_count > 0) {
                    printf(
                        esc_html__('Showing %1$d–%2$d of %3$d titles', 'astra-child'),
                        $showing_start,
                        $showing_end,
                        $total_count
                    );
                            } else {
                    esc_html_e('No titles found', 'astra-child');
                            }
                            ?>
            </span>
        </div>

        <?php // Ranked Grid ?>
        <div class="toprated-grid" id="topratedGrid">
            <?php if ($toprated_q->have_posts()) : ?>
        <?php
                $rank = $current_rank;
                while ($toprated_q->have_posts()) : $toprated_q->the_post();
                    $pid = get_the_ID();
                    $title = get_the_title();
                    $ptype = get_post_type();
                    $year = movie_ui_meta($pid, ['year', '_release_year'], '');
                    if ($year && strlen($year) > 4) $year = substr($year, 0, 4);
                    $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                    $quality = movie_ui_meta($pid, ['quality', '_quality'], '');
                    $poster = get_the_post_thumbnail_url($pid, 'medium') ?: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 270"%3E%3Crect fill="%231a1a2e" width="180" height="270"/%3E%3C/svg%3E';
                    $detail_url = get_permalink($pid);
                    $trailer = movie_ui_meta($pid, ['trailer_url', '_trailer_url'], '');
                    $video = movie_ui_meta($pid, ['video_url', '_video_url'], '');
                    $watch_url = add_query_arg('id', $pid, $watch_base);
                    $play_action = 'watch:' . esc_url($watch_url);
                    $type_label = $ptype === 'tv_show' ? __('TV', 'astra-child') : __('Movie', 'astra-child');
                ?>
                    <article class="toprated-card"
                             data-id="<?php echo esc_attr($pid); ?>"
                             data-url="<?php echo esc_url($detail_url); ?>"
                             data-rank="<?php echo esc_attr($rank); ?>"
                             data-trailer="<?php echo $trailer ? esc_attr($trailer) : ''; ?>"
                             data-video="<?php echo $video ? esc_url($watch_url) : ''; ?>"
                             data-play-action="<?php echo esc_attr($play_action); ?>">
                        <div class="toprated-card__poster-wrap">
                            <span class="toprated-card__rank"><?php echo esc_html($rank); ?></span>
                            <img class="toprated-card__poster" 
                                 src="<?php echo esc_url($poster); ?>" 
                                 alt="<?php echo esc_attr($title); ?>" 
                                 loading="lazy"
                                 decoding="async">
                            <div class="toprated-card__overlay">
                                <div class="toprated-card__actions">
                                    <?php if ($play_action) : ?>
                                        <button class="toprated-card__btn toprated-card__btn--play" 
                                                data-action="<?php echo esc_attr($play_action); ?>" 
                                                title="<?php esc_attr_e('Play', 'astra-child'); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                    <button class="toprated-card__btn toprated-card__btn--fav" 
                                            data-favorite="<?php echo esc_attr($pid); ?>" 
                                            title="<?php esc_attr_e('Add to My List', 'astra-child'); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line class="tr-ico-fav-h tr-ico-plus-h" x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                    </button>
                                    <button class="toprated-card__btn" 
                                            data-more 
                                            data-url="<?php echo esc_url($detail_url); ?>"
                                            title="<?php esc_attr_e('More Info', 'astra-child'); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="12" y1="8" x2="12" y2="16"/>
                                            <circle cx="12" cy="5" r="1" fill="currentColor"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="toprated-card__info">
                            <h3 class="toprated-card__title"><?php echo esc_html($title); ?></h3>
                            <div class="toprated-card__meta">
                                <?php if ($year) : ?>
                                    <span><?php echo esc_html($year); ?></span>
                                <?php endif; ?>
                                <?php if ($rating) : ?>
                                    <span class="toprated-card__rating">★ <?php echo esc_html($rating); ?></span>
                                <?php endif; ?>
                                <span class="toprated-card__type"><?php echo esc_html($type_label); ?></span>
                                <?php if ($quality && $quality !== 'HD') : ?>
                                    <span class="toprated-card__quality"><?php echo esc_html($quality); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php
                $rank++;
                endwhile;
                wp_reset_postdata();
                ?>
            <?php else : ?>
                <div class="toprated-empty">
                    <div class="toprated-empty__icon">🏆</div>
                    <h2 class="toprated-empty__title"><?php esc_html_e('No top-rated titles found', 'astra-child'); ?></h2>
                    <p class="toprated-empty__desc"><?php esc_html_e('Try adjusting your filters or check back later for new content.', 'astra-child'); ?></p>
                    <div class="toprated-empty__actions">
                        <a href="<?php echo esc_url($movies_archive); ?>" class="toprated-empty__btn toprated-empty__btn--primary">
                            <?php esc_html_e('Explore Movies', 'astra-child'); ?>
                        </a>
                        <a href="<?php echo esc_url($tv_archive); ?>" class="toprated-empty__btn toprated-empty__btn--secondary">
                            <?php esc_html_e('Explore TV Shows', 'astra-child'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php // Load More Button ?>
        <?php if ($toprated_q->have_posts() && $page < $total_pages) : ?>
            <div class="toprated-loadmore-wrap">
                <button class="toprated-loadmore-btn" 
                        id="loadMoreBtn" 
                        data-page="<?php echo esc_attr($page + 1); ?>"
                        data-cat="<?php echo esc_attr($cat_filter); ?>"
                        data-range="<?php echo esc_attr($range); ?>"
                        data-nonce="<?php echo esc_attr($ajax_nonce); ?>">
                    <span class="toprated-loadmore-text"><?php esc_html_e('Load More', 'astra-child'); ?></span>
                    <span class="toprated-loadmore-spinner"></span>
                </button>
            </div>
        <?php elseif ($total_count > $per_page) : ?>
            <?php // Standard Pagination if AJAX not working ?>
            <div class="toprated-loadmore-wrap">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                    <?php for ($p = 1; $p <= $total_pages; $p++) : ?>
                        <a href="<?php echo esc_url(add_query_arg(['paged' => $p], $base_u)); ?>" 
                           class="toprated-loadmore-btn<?php echo $p === $page ? ' is-active' : ''; ?>"
                           style="padding: 10px 16px; min-width: auto;">
                            <?php echo esc_html($p); ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
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

    var grid = document.getElementById('topratedGrid');
    var loadMoreBtn = document.getElementById('loadMoreBtn');
    var currentPage = <?php echo (int) $page; ?>;
    var totalPages = <?php echo (int) $total_pages; ?>;

    // =====================================================================
    // TIME DROPDOWN
    // =====================================================================
    window.toggleTimeDropdown = function() {
        var dropdown = document.getElementById('timeDropdown');
        dropdown.classList.toggle('is-open');

        document.addEventListener('click', function closeDropdown(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('is-open');
                document.removeEventListener('click', closeDropdown);
            }
        });
    };

    // =====================================================================
    // CARD CLICK HANDLERS
    // =====================================================================
    if (grid) {
        grid.addEventListener('click', function(e) {
            var card = e.target.closest('.toprated-card');
            if (!card) return;

            // Check if clicked on a button
            var btn = e.target.closest('.toprated-card__btn');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();

                var action = btn.getAttribute('data-action');
                var moreUrl = btn.getAttribute('data-url');
                var favId = btn.getAttribute('data-favorite');

                if (action) {
                    handlePlayAction(action);
                } else if (moreUrl) {
                    window.location.href = moreUrl;
                } else if (favId) {
                    handleFavorite(favId, btn);
                }
                return;
            }

            // Card click - go to detail page
            var detailUrl = card.getAttribute('data-url');
            if (detailUrl) {
                window.location.href = detailUrl;
            }
        });
    }

    // =====================================================================
    // PLAY ACTION HANDLER - Use global openTrailer
    // =====================================================================
    function handlePlayAction(action) {
        if (!action || action === 'unavailable') {
            showToast('<?php esc_attr_e('No video available', 'astra-child'); ?>', 'info');
            return;
        }

        if (action.indexOf('trailer:') === 0) {
            var url = action.replace('trailer:', '');
            // Use global openTrailer function
            if (typeof window.openTrailer === 'function') {
                window.openTrailer(url);
            } else {
                openTrailerModal(url);
            }
        } else if (action.indexOf('watch:') === 0) {
            var url = action.replace('watch:', '');
            window.location.href = url;
        }
    }

    // =====================================================================
    // FAVORITE HANDLER
    // =====================================================================
    function handleFavorite(id, btn) {
        if (!id) return;

        btn.classList.toggle('is-on');

        if (typeof toggleFavorite === 'function') {
            toggleFavorite(id, btn);
        }

        var isOn = btn.classList.contains('is-on');
        var message = isOn
            ? '<?php esc_attr_e('Added to My List', 'astra-child'); ?>'
            : '<?php esc_attr_e('Removed from My List', 'astra-child'); ?>';
        showToast(message, isOn ? 'success' : 'info');
    }

    // =====================================================================
    // TRAILER MODAL - Local fallback functions
    // =====================================================================
    function openTrailerModal(url) {
        if (!url) return;

        var modal = document.getElementById('mu-trailer-modal');
        var area = document.getElementById('mu-trailer-video-area');
        if (!modal || !area) return;

        // Convert YouTube URLs to embed
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
            'style="width:100%;height:100%;border:none;">' +
            '</iframe>';
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

    // Close modal handlers
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
    // LOAD MORE (AJAX)
    // =====================================================================
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            var btn = this;
            var nextPage = btn.getAttribute('data-page');
            var cat = btn.getAttribute('data-cat');
            var range = btn.getAttribute('data-range');
            var nonce = btn.getAttribute('data-nonce');
            
            btn.classList.add('is-loading');
            btn.disabled = true;
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                    
                    if (xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data && data.success && data.html) {
                                // Append new cards
                                var temp = document.createElement('div');
                                temp.innerHTML = data.html;
                                var newCards = temp.querySelectorAll('.toprated-card');
                                newCards.forEach(function(card) {
                                    grid.appendChild(card);
                                });
                                
                                // Update button data
                                btn.setAttribute('data-page', data.next_page || (parseInt(nextPage) + 1));
                                
                                // Hide button if no more pages
                                if (!data.has_more) {
                                    btn.style.display = 'none';
                                }
                                
                                // Update results count
                                updateResultsCount(data.total_shown || 0, data.total_count || 0);
                                
                                // Show success toast
                                showToast(newCards.length + ' <?php esc_attr_e('more titles loaded', 'astra-child'); ?>', 'success');
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                            // Fallback to page reload
                            window.location.href = window.location.pathname + '?cat=' + cat + '&range=' + range + '&paged=' + nextPage;
                        }
                    } else {
                        // Fallback to page reload
                        window.location.href = window.location.pathname + '?cat=' + cat + '&range=' + range + '&paged=' + nextPage;
                    }
                }
            };
            
            xhr.send(
                'action=mu_toprated_load_more' +
                '&nonce=' + nonce +
                '&page=' + nextPage +
                '&cat=' + cat +
                '&range=' + range
            );
        });
    }
    
    function updateResultsCount(shown, total) {
        var info = document.querySelector('.toprated-results-count');
        if (info) {
            info.innerHTML = '<?php esc_html_e('Showing', 'astra-child'); ?> <strong>1–' + shown + '</strong> of <strong>' + total + '</strong> <?php esc_html_e('titles', 'astra-child'); ?>';
        }
    }
    
    // =====================================================================
    // TOAST NOTIFICATION
    // =====================================================================
    function showToast(message, type) {
        type = type || 'info';
        
        // Check if toast function exists globally
        if (typeof window.muShowToast === 'function') {
            window.muShowToast(message, type);
            return;
        }
        
        // Create inline toast
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
