<?php
/**
 * Template Name: New Releases (Premium OTT)
 * Premium streaming New Releases page with featured carousel and sections.
 */
defined('ABSPATH') || exit;
get_header();
get_template_part('template-parts/streaming/header');

// =============================================================================
// FILTER SETUP
// =============================================================================
$cat_filter = isset($_GET['cat']) ? sanitize_key((string) $_GET['cat']) : 'all';
if (!in_array($cat_filter, ['all', 'movies', 'tv'], true)) {
    $cat_filter = 'all';
}

$range = isset($_GET['range']) ? sanitize_key((string) $_GET['range']) : 'week';
if (!in_array($range, ['week', 'month', 'year', 'all'], true)) {
    $range = 'week';
}

// Date range
$now = new DateTimeImmutable('now', wp_timezone());
if ($range === 'week') {
    $after = $now->modify('-7 days')->format('Y-m-d 00:00:00');
} elseif ($range === 'month') {
    $after = $now->modify('-30 days')->format('Y-m-d 00:00:00');
} elseif ($range === 'year') {
    $after = $now->modify('-365 days')->format('Y-m-d 00:00:00');
} else {
    $after = null;
}

// Post types
$post_types = ($cat_filter === 'all') ? ['movie', 'tv_show'] : [$cat_filter === 'movies' ? 'movie' : 'tv_show'];

// Base URL
$base_u = mu_get_page_url_by_slug('new-releases');
$movies_archive = trailingslashit(home_url('movies'));
$tv_archive = trailingslashit(home_url('tv'));
$watch_base = mu_get_page_url_by_slug('watch');

// Range options
$range_options = [
    'week'  => __('This Week', 'astra-child'),
    'month' => __('This Month', 'astra-child'),
    'year'  => __('This Year', 'astra-child'),
    'all'   => __('All New', 'astra-child'),
];

// =============================================================================
// QUERIES
// =============================================================================

// Featured: latest 5 with high popularity
$featured_args = [
    'post_type' => $post_types,
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'orderby' => ['date' => 'DESC', 'meta_value_num' => 'DESC'],
    'meta_key' => '_popularity',
    'ignore_sticky_posts' => true,
];
if ($after) {
    $featured_args['date_query'] = [['after' => $after]];
}
$featured_q = new WP_Query($featured_args);

// New Movies (last 20)
$movies_args = [
    'post_type' => 'movie',
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
];
if ($after) {
    $movies_args['date_query'] = [['after' => $after]];
}
$movies_q = new WP_Query($movies_args);

// New TV Shows (last 20)
$tv_args = [
    'post_type' => 'tv_show',
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
];
if ($after) {
    $tv_args['date_query'] = [['after' => $after]];
}
$tv_q = new WP_Query($tv_args);

// Just Added (last 30, mixed)
$just_added_args = [
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 30,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
];
$just_added_q = new WP_Query($just_added_args);

// AJAX nonce
$ajax_nonce = wp_create_nonce('mu_newrel_ajax');
$is_logged_in = is_user_logged_in();

// Get featured items for carousel
$featured_items = [];
if ($featured_q->have_posts()) {
    while ($featured_q->have_posts()) {
        $featured_q->the_post();
        $pid = get_the_ID();
        $item = [
            'id' => $pid,
            'title' => get_the_title(),
            'url' => get_permalink(),
            'backdrop' => movie_ui_backdrop_url($pid) ?: '',
            'poster' => get_the_post_thumbnail_url($pid, 'medium') ?: '',
            'year' => movie_ui_meta($pid, ['year', '_release_year'], ''),
            'rating' => movie_ui_meta($pid, ['rating', '_rating'], ''),
            'duration' => movie_ui_meta($pid, ['duration', '_duration'], ''),
            'genres' => movie_ui_terms_text($pid, 'genre', 3),
            'overview' => wp_trim_words(wp_strip_all_tags(get_the_content() ?: ''), 30),
            'trailer' => movie_ui_meta($pid, ['trailer_url', '_trailer_url'], ''),
            'video' => movie_ui_meta($pid, ['video_url', '_video_url'], ''),
            'type' => get_post_type($pid),
            'ptype' => get_post_type($pid) === 'tv_show' ? __('TV Show', 'astra-child') : __('Movie', 'astra-child'),
        ];
        if (strlen($item['year']) > 4) $item['year'] = substr($item['year'], 0, 4);
        $featured_items[] = $item;
    }
    wp_reset_postdata();
}
?>

<style>
/* ============================================
   NEW RELEASES PAGE - PREMIUM OTT
   ============================================ */
.nr-page {
    background: #08080c;
    min-height: 100vh;
    padding-top: 70px;
}

/* Page Header */
.nr-header {
    position: relative;
    padding: 30px 4% 20px;
    background: linear-gradient(180deg, rgba(20,20,30,0.8) 0%, #08080c 100%);
    margin-bottom: 10px;
}

.nr-header-inner {
    max-width: 1400px;
    margin: 0 auto;
}

.nr-page-title {
    font-size: clamp(2rem, 4vw, 2.8rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 8px;
    letter-spacing: -0.02em;
}

.nr-page-subtitle {
    font-size: clamp(0.85rem, 1.2vw, 1rem);
    color: rgba(255,255,255,0.5);
    margin: 0;
}

/* Filters */
.nr-filters {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 0 4% 24px;
    max-width: 1400px;
    margin: 0 auto;
    flex-wrap: wrap;
}

.nr-filter-tabs {
    display: flex;
    gap: 4px;
    background: rgba(255,255,255,0.08);
    border-radius: 25px;
    padding: 4px;
}

.nr-filter-tab {
    padding: 8px 18px;
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

.nr-filter-tab:hover { color: #fff; }
.nr-filter-tab.is-active { background: #e50914; color: #fff; }

.nr-time-dropdown {
    position: relative;
}

.nr-time-btn {
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
}

.nr-time-btn:hover { background: rgba(255,255,255,0.12); }

.nr-time-btn svg { transition: transform 0.2s; }
.nr-time-dropdown.is-open .nr-time-btn svg { transform: rotate(180deg); }

.nr-time-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #1a1a2e;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 8px 0;
    min-width: 150px;
    z-index: 100;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s;
}

.nr-time-dropdown.is-open .nr-time-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.nr-time-option {
    display: block;
    padding: 10px 16px;
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.15s;
}

.nr-time-option:hover { background: rgba(255,255,255,0.08); color: #fff; }
.nr-time-option.is-active { color: #e50914; }

/* ============================================
   FEATURED CAROUSEL
   ============================================ */
.nr-featured {
    position: relative;
    margin-bottom: 50px;
    overflow: hidden;
}

.nr-featured__track {
    display: flex;
    transition: transform 0.5s ease;
}

.nr-featured__slide {
    flex: 0 0 100%;
    position: relative;
    min-height: 500px;
    display: flex;
    align-items: flex-end;
}

@media (max-width: 768px) {
    .nr-featured__slide { min-height: 400px; }
}

.nr-featured__bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
}

.nr-featured__grad {
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, rgba(8,8,12,0.95) 0%, rgba(8,8,12,0.7) 50%, rgba(8,8,12,0.3) 100%),
                linear-gradient(to top, rgba(8,8,12,1) 0%, transparent 50%);
}

.nr-featured__content {
    position: relative;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
    padding: 0 4% 50px;
    display: flex;
    gap: 40px;
    align-items: flex-end;
}

@media (max-width: 768px) {
    .nr-featured__content { flex-direction: column; align-items: flex-start; }
}

.nr-featured__poster {
    flex-shrink: 0;
    width: 220px;
    border-radius: 12px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.6);
}

@media (max-width: 768px) {
    .nr-featured__poster { width: 140px; }
}

.nr-featured__info {
    flex: 1;
    padding-bottom: 10px;
}

.nr-featured__badge {
    display: inline-block;
    background: #e50914;
    color: #fff;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 12px;
}

.nr-featured__title {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 12px;
    line-height: 1.1;
}

.nr-featured__meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.nr-featured__meta-item {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.7);
}

.nr-featured__rating { color: #ffd700; font-weight: 600; }

.nr-featured__genres {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}

.nr-featured__genre {
    padding: 4px 10px;
    background: rgba(255,255,255,0.1);
    border-radius: 4px;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.7);
}

.nr-featured__desc {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.6);
    max-width: 500px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.nr-featured__actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.nr-featured__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.nr-featured__btn--primary {
    background: #e50914;
    color: #fff;
}

.nr-featured__btn--primary:hover {
    background: #ff1a25;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(229,9,20,0.4);
}

.nr-featured__btn--secondary {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.2);
}

.nr-featured__btn--secondary:hover {
    background: rgba(255,255,255,0.15);
}

/* Carousel Controls */
.nr-featured__arrows {
    position: absolute;
    bottom: 50%;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    padding: 0 20px;
    pointer-events: none;
    z-index: 10;
}

.nr-featured__arrow {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(0,0,0,0.5);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    pointer-events: auto;
    transition: all 0.2s;
}

.nr-featured__arrow:hover {
    background: rgba(255,255,255,0.2);
}

.nr-featured__dots {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 10;
}

.nr-featured__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    cursor: pointer;
    transition: all 0.2s;
}

.nr-featured__dot.is-active {
    background: #e50914;
    transform: scale(1.2);
}

/* ============================================
   SECTIONS
   ============================================ */
.nr-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 4% 60px;
}

.nr-section {
    margin-bottom: 50px;
}

.nr-section__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.nr-section__title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.nr-section__badge {
    background: #e50914;
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}

.nr-section__link {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    transition: color 0.2s;
}

.nr-section__link:hover { color: #e50914; }

/* Scroll Row */
.nr-scroll-row {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    padding-bottom: 15px;
}

.nr-scroll-row::-webkit-scrollbar { display: none; }

/* Card */
.nr-card {
    flex-shrink: 0;
    width: 160px;
    cursor: pointer;
    transition: transform 0.3s;
}

@media (min-width: 768px) { .nr-card { width: 180px; } }

.nr-card:hover { transform: scale(1.05); }

.nr-card__poster-wrap {
    position: relative;
    aspect-ratio: 2/3;
    border-radius: 10px;
    overflow: hidden;
    background: #1a1a2e;
    margin-bottom: 10px;
}

.nr-card__poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.nr-card:hover .nr-card__poster { transform: scale(1.05); }

.nr-card__new {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #e50914;
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
}

.nr-card__rating {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(0,0,0,0.7);
    color: #ffd700;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 2px;
}

.nr-card__overlay {
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

.nr-card:hover .nr-card__overlay { opacity: 1; }

.nr-card__actions {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.nr-card__btn {
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

.nr-card__btn:hover {
    transform: scale(1.12);
    background: rgba(255, 255, 255, 0.2);
}

.nr-card__btn--play {
    background: #fff;
    border-color: #fff;
    color: #000;
}

.nr-card__btn--play:hover { background: rgba(255, 255, 255, 0.9); }

.nr-card__btn--fav.is-on {
    background: rgba(229, 9, 20, 0.3);
    border-color: rgba(229, 9, 20, 0.8);
}

.nr-card__info { text-align: left; }

.nr-card__title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nr-card__meta {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
}

/* ============================================
   NOTIFICATION CTA
   ============================================ */
.nr-notif {
    position: fixed;
    bottom: 20px;
    right: 20px;
    max-width: 380px;
    background: linear-gradient(135deg, #1a1a2e, #16162a);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    z-index: 100;
    animation: nrSlideIn 0.5s ease;
}

@keyframes nrSlideIn {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.nr-notif.is-hidden { display: none; }

.nr-notif__header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.nr-notif__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(229,9,20,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.nr-notif__title {
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 4px;
}

.nr-notif__desc {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.5);
    margin: 0;
    line-height: 1.4;
}

.nr-notif__close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    border: none;
    color: rgba(255,255,255,0.5);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.nr-notif__close:hover { background: rgba(255,255,255,0.1); color: #fff; }

.nr-notif__btn {
    width: 100%;
    padding: 12px;
    background: #e50914;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.nr-notif__btn:hover { background: #ff1a25; }

/* ============================================
   EMPTY STATE
   ============================================ */
.nr-empty {
    text-align: center;
    padding: 80px 20px;
}

.nr-empty__icon { font-size: 3rem; margin-bottom: 20px; opacity: 0.5; }

.nr-empty__title {
    font-size: 1.5rem;
    color: #fff;
    margin: 0 0 12px;
    font-weight: 700;
}

.nr-empty__desc {
    color: rgba(255,255,255,0.5);
    margin: 0 0 24px;
}

.nr-empty__actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.nr-empty__btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s;
}

.nr-empty__btn--primary { background: #e50914; color: #fff; }
.nr-empty__btn--primary:hover { background: #ff1a25; }

.nr-empty__btn--secondary {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
}

.nr-empty__btn--secondary:hover { background: rgba(255,255,255,0.12); }

/* ============================================
   TRAILER MODAL
   ============================================ */
.nr-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,0.9);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.nr-modal.is-open { display: flex; }

.nr-modal__dialog {
    width: 100%;
    max-width: 900px;
    position: relative;
}

.nr-modal__close {
    position: absolute;
    top: -40px;
    right: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nr-modal__close:hover { background: rgba(255,255,255,0.2); }

.nr-modal__video {
    width: 100%;
    aspect-ratio: 16/9;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
}

.nr-modal__video iframe { width: 100%; height: 100%; border: none; }

/* Responsive */
@media (max-width: 768px) {
    .nr-featured__slide { min-height: 450px; }
    .nr-featured__poster { width: 140px; }
    .nr-featured__actions { flex-direction: column; }
    .nr-featured__btn { width: 100%; justify-content: center; }
    .nr-card { width: 140px; }
    .nr-notif { max-width: calc(100% - 40px); }
}

/* FIX icon/button bị phóng to */
.nr-featured__arrow,
.nr-card__btn,
.nr-featured__dot,
.nr-notif__close,
.nr-modal__close,
.mu-trailer-modal__close {
  min-width: unset !important;
  min-height: unset !important;
  max-width: none !important;
  padding: 0 !important;
  line-height: 1 !important;
  aspect-ratio: 1 / 1 !important;
}

.nr-featured__arrow {
  width: 44px !important;
  height: 44px !important;
  border-radius: 50% !important;
}

.nr-card__btn {
  width: 32px !important;
  height: 32px !important;
  border-radius: 50% !important;
}

.nr-featured__dot {
  width: 10px !important;
  height: 10px !important;
  border-radius: 50% !important;
  font-size: 0 !important;
}

.nr-featured__arrow svg,
.nr-card__btn svg,
.nr-featured__btn svg,
.nr-notif__close svg,
.nr-modal__close svg,
.mu-trailer-modal__close svg {
  width: 16px !important;
  height: 16px !important;
  min-width: 16px !important;
  min-height: 16px !important;
  display: block !important;
}
</style>

<div class="nr-page">
    <?php // =====================================================================
          // PAGE HEADER
          // ===================================================================== ?>
    <div class="nr-header">
        <div class="nr-header-inner">
            <h1 class="nr-page-title">New Releases</h1>
            <p class="nr-page-subtitle">Discover the latest movies and TV shows, fresh on MOVIE.</p>
        </div>
    </div>

    <?php // =====================================================================
          // FILTERS
          // ===================================================================== ?>
    <div class="nr-filters">
        <div class="nr-filter-tabs">
            <a href="<?php echo esc_url(add_query_arg(['cat' => 'all', 'range' => $range], $base_u)); ?>" 
               class="nr-filter-tab<?php echo $cat_filter === 'all' ? ' is-active' : ''; ?>">
                <?php esc_html_e('All', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg(['cat' => 'movies', 'range' => $range], $base_u)); ?>" 
               class="nr-filter-tab<?php echo $cat_filter === 'movies' ? ' is-active' : ''; ?>">
                <?php esc_html_e('Movies', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg(['cat' => 'tv', 'range' => $range], $base_u)); ?>" 
               class="nr-filter-tab<?php echo $cat_filter === 'tv' ? ' is-active' : ''; ?>">
                <?php esc_html_e('TV Shows', 'astra-child'); ?>
            </a>
        </div>
        
        <div class="nr-time-dropdown" id="timeDropdown">
            <button class="nr-time-btn" type="button" onclick="toggleNrTimeDropdown()">
                <span id="nrTimeLabel"><?php echo esc_html($range_options[$range]); ?></span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="nr-time-menu">
                <?php foreach ($range_options as $slug => $label) : ?>
                    <a href="<?php echo esc_url(add_query_arg(['range' => $slug, 'cat' => $cat_filter], $base_u)); ?>" 
                       class="nr-time-option<?php echo $range === $slug ? ' is-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php // =====================================================================
          // FEATURED CAROUSEL
          // ===================================================================== ?>
    <?php if (!empty($featured_items)) : ?>
    <div class="nr-featured" id="nrFeatured">
        <div class="nr-featured__track" id="nrTrack">
            <?php foreach ($featured_items as $i => $item) : ?>
                <div class="nr-featured__slide" data-index="<?php echo $i; ?>">
                    <div class="nr-featured__bg" style="background-image: url('<?php echo esc_url($item['backdrop']); ?>')"></div>
                    <div class="nr-featured__grad"></div>
                    <div class="nr-featured__content">
                        <img class="nr-featured__poster" src="<?php echo esc_url($item['poster']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                        <div class="nr-featured__info">
                            <span class="nr-featured__badge"><?php echo esc_html($item['ptype']); ?></span>
                            <h2 class="nr-featured__title"><?php echo esc_html($item['title']); ?></h2>
                            <div class="nr-featured__meta">
                                <span class="nr-featured__meta-item"><?php echo esc_html($item['year']); ?></span>
                                <?php if ($item['duration']) : ?>
                                    <span class="nr-featured__meta-item"><?php echo esc_html($item['duration']); ?> min</span>
                                <?php endif; ?>
                                <?php if ($item['rating']) : ?>
                                    <span class="nr-featured__meta-item nr-featured__rating">★ <?php echo esc_html($item['rating']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="nr-featured__genres">
                                <?php echo wp_kses_post($item['genres']); ?>
                            </div>
                            <p class="nr-featured__desc"><?php echo esc_html($item['overview']); ?></p>
                            <div class="nr-featured__actions">
                                <?php if ($item['trailer'] || $item['video']) : ?>
                                    <button class="nr-featured__btn nr-featured__btn--primary" type="button" 
                                            onclick="openTrailerModal('<?php echo esc_attr($item['trailer'] ?: $item['video']); ?>')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        <?php esc_html_e('Watch Trailer', 'astra-child'); ?>
                                    </button>
                                <?php endif; ?>
                                <button class="nr-featured__btn nr-featured__btn--secondary" type="button" 
                                        onclick="toggleFavNr(<?php echo (int) $item['id']; ?>, this)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line class="fav-plus" x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    <?php esc_html_e('My List', 'astra-child'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="nr-featured__arrows">
            <button class="nr-featured__arrow" onclick="moveSlide(-1)" aria-label="Previous">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button class="nr-featured__arrow" onclick="moveSlide(1)" aria-label="Next">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
        
        <div class="nr-featured__dots" id="nrDots">
            <?php foreach ($featured_items as $i => $item) : ?>
                <button class="nr-featured__dot<?php echo $i === 0 ? ' is-active' : ''; ?>" 
                        onclick="goToSlide(<?php echo $i; ?>)" aria-label="Slide <?php echo $i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php // =====================================================================
          // CONTENT
          // ===================================================================== ?>
    <div class="nr-content">
        
        <?php // NEW MOVIES ?>
        <?php if ($movies_q->have_posts()) : ?>
        <section class="nr-section">
            <div class="nr-section__header">
                <h2 class="nr-section__title">
                    <?php esc_html_e('New Movies', 'astra-child'); ?>
                    <span class="nr-section__badge"><?php esc_html_e('New', 'astra-child'); ?></span>
                </h2>
                <a href="<?php echo esc_url($movies_archive); ?>" class="nr-section__link">
                    <?php esc_html_e('View All', 'astra-child'); ?> →
                </a>
            </div>
            <div class="nr-scroll-row" id="moviesRow">
                <?php 
                while ($movies_q->have_posts()) : $movies_q->the_post();
                    $pid = get_the_ID();
                    $title = get_the_title();
                    $poster = get_the_post_thumbnail_url($pid, 'medium') ?: '';
                    $year = movie_ui_meta($pid, ['year', '_release_year'], '');
                    if ($year && strlen($year) > 4) $year = substr($year, 0, 4);
                    $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                    $duration = movie_ui_meta($pid, ['duration', '_duration'], '');
                    $trailer = movie_ui_meta($pid, ['trailer_url', '_trailer_url'], '');
                    $video = movie_ui_meta($pid, ['video_url', '_video_url'], '');
                    $watch_url = add_query_arg('id', $pid, $watch_base);
                    $detail_url = get_permalink($pid);
                    $play_action = 'watch:' . esc_url($watch_url);
                ?>
                    <article class="nr-card" data-id="<?php echo esc_attr($pid); ?>" data-url="<?php echo esc_url($detail_url); ?>">
                        <div class="nr-card__poster-wrap">
                            <img class="nr-card__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                            <span class="nr-card__new">NEW</span>
                            <?php if ($rating) : ?>
                                <span class="nr-card__rating">★ <?php echo esc_html($rating); ?></span>
                            <?php endif; ?>
                            <div class="nr-card__overlay">
                                <div class="nr-card__actions">
                                    <?php if ($play_action) : ?>
                                        <button class="nr-card__btn nr-card__btn--play" data-action="<?php echo esc_attr($play_action); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </button>
                                    <?php endif; ?>
                                    <button class="nr-card__btn nr-card__btn--fav" data-fav="<?php echo esc_attr($pid); ?>">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line class="fav-plus" x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="nr-card__info">
                            <h3 class="nr-card__title"><?php echo esc_html($title); ?></h3>
                            <p class="nr-card__meta">
                                <?php echo esc_html($year); ?>
                                <?php if ($duration) : ?> • <?php echo esc_html($duration); ?> min<?php endif; ?>
                            </p>
                        </div>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php endif; ?>
        
        <?php // NEW TV SHOWS ?>
        <?php if ($tv_q->have_posts()) : ?>
        <section class="nr-section">
            <div class="nr-section__header">
                <h2 class="nr-section__title">
                    <?php esc_html_e('New TV Shows', 'astra-child'); ?>
                    <span class="nr-section__badge"><?php esc_html_e('New', 'astra-child'); ?></span>
                </h2>
                <a href="<?php echo esc_url($tv_archive); ?>" class="nr-section__link">
                    <?php esc_html_e('View All', 'astra-child'); ?> →
                </a>
            </div>
            <div class="nr-scroll-row" id="tvRow">
                <?php 
                while ($tv_q->have_posts()) : $tv_q->the_post();
                    $pid = get_the_ID();
                    $title = get_the_title();
                    $poster = get_the_post_thumbnail_url($pid, 'medium') ?: '';
                    $year = movie_ui_meta($pid, ['year', '_release_year'], '');
                    if ($year && strlen($year) > 4) $year = substr($year, 0, 4);
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
                    <article class="nr-card" data-id="<?php echo esc_attr($pid); ?>" data-url="<?php echo esc_url($detail_url); ?>">
                        <div class="nr-card__poster-wrap">
                            <img class="nr-card__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                            <span class="nr-card__new">NEW</span>
                            <?php if ($rating) : ?>
                                <span class="nr-card__rating">★ <?php echo esc_html($rating); ?></span>
                            <?php endif; ?>
                            <div class="nr-card__overlay">
                                <div class="nr-card__actions">
                                    <?php if ($play_action) : ?>
                                        <button class="nr-card__btn nr-card__btn--play" data-action="<?php echo esc_attr($play_action); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </button>
                                    <?php endif; ?>
                                    <button class="nr-card__btn nr-card__btn--fav" data-fav="<?php echo esc_attr($pid); ?>">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line class="fav-plus" x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="nr-card__info">
                            <h3 class="nr-card__title"><?php echo esc_html($title); ?></h3>
                            <p class="nr-card__meta">
                                <?php if ($season_count) : ?><?php echo esc_html($season_count); ?> <?php esc_html_e('Seasons', 'astra-child'); endif; ?>
                            </p>
                        </div>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php endif; ?>
        
        <?php // JUST ADDED ?>
        <?php if ($just_added_q->have_posts()) : ?>
        <section class="nr-section">
            <div class="nr-section__header">
                <h2 class="nr-section__title">
                    <?php esc_html_e('Just Added', 'astra-child'); ?>
                    <span class="nr-section__badge"><?php esc_html_e('Fresh', 'astra-child'); ?></span>
                </h2>
            </div>
            <div class="nr-scroll-row" id="justAddedRow">
                <?php 
                while ($just_added_q->have_posts()) : $just_added_q->the_post();
                    $pid = get_the_ID();
                    $title = get_the_title();
                    $poster = get_the_post_thumbnail_url($pid, 'medium') ?: '';
                    $year = movie_ui_meta($pid, ['year', '_release_year'], '');
                    if ($year && strlen($year) > 4) $year = substr($year, 0, 4);
                    $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                    $trailer = movie_ui_meta($pid, ['trailer_url', '_trailer_url'], '');
                    $video = movie_ui_meta($pid, ['video_url', '_video_url'], '');
                    $watch_url = add_query_arg('id', $pid, $watch_base);
                    $detail_url = get_permalink($pid);
                    $ptype = get_post_type($pid);
                    $play_action = 'watch:' . esc_url($watch_url);
                ?>
                    <article class="nr-card" data-id="<?php echo esc_attr($pid); ?>" data-url="<?php echo esc_url($detail_url); ?>">
                        <div class="nr-card__poster-wrap">
                            <img class="nr-card__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                            <span class="nr-card__new"><?php echo $ptype === 'tv_show' ? 'NEW EPISODES' : 'NEW'; ?></span>
                            <?php if ($rating) : ?>
                                <span class="nr-card__rating">★ <?php echo esc_html($rating); ?></span>
                            <?php endif; ?>
                            <div class="nr-card__overlay">
                                <div class="nr-card__actions">
                                    <?php if ($play_action) : ?>
                                        <button class="nr-card__btn nr-card__btn--play" data-action="<?php echo esc_attr($play_action); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </button>
                                    <?php endif; ?>
                                    <button class="nr-card__btn nr-card__btn--fav" data-fav="<?php echo esc_attr($pid); ?>">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line class="fav-plus" x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="nr-card__info">
                            <h3 class="nr-card__title"><?php echo esc_html($title); ?></h3>
                            <p class="nr-card__meta"><?php echo esc_html($year); ?></p>
                        </div>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php endif; ?>
        
        <?php // EMPTY STATE ?>
        <?php if (!$movies_q->have_posts() && !$tv_q->have_posts() && !$just_added_q->have_posts()) : ?>
        <div class="nr-empty">
            <div class="nr-empty__icon">🎬</div>
            <h2 class="nr-empty__title"><?php esc_html_e('No new releases found', 'astra-child'); ?></h2>
            <p class="nr-empty__desc"><?php esc_html_e('Check back soon for new movies and TV shows!', 'astra-child'); ?></p>
            <div class="nr-empty__actions">
                <a href="<?php echo esc_url($movies_archive); ?>" class="nr-empty__btn nr-empty__btn--primary">
                    <?php esc_html_e('Explore Movies', 'astra-child'); ?>
                </a>
                <a href="<?php echo esc_url($tv_archive); ?>" class="nr-empty__btn nr-empty__btn--secondary">
                    <?php esc_html_e('Explore TV Shows', 'astra-child'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php // =====================================================================
      // NOTIFICATION CTA
      // ===================================================================== ?>
<div class="nr-notif" id="nrNotif" hidden>
    <button class="nr-notif__close" onclick="dismissNotif()" aria-label="Close">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </button>
    <div class="nr-notif__header">
        <div class="nr-notif__icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e50914" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <div>
            <h3 class="nr-notif__title"><?php esc_html_e('Never Miss a New Release', 'astra-child'); ?></h3>
            <p class="nr-notif__desc"><?php esc_html_e('Turn on notifications to be the first to know when new movies and TV shows arrive.', 'astra-child'); ?></p>
        </div>
    </div>
    <button class="nr-notif__btn" onclick="enableNotifications()">
        <?php esc_html_e('Turn On Notifications', 'astra-child'); ?>
    </button>
</div>

<?php // =====================================================================
      // TRAILER MODAL - Uses global mu-trailer-modal styles from movie-ui.css
      // ===================================================================== ?>
<div class="nr-modal mu-trailer-modal" id="nrModal" onclick="closeModalOnBackdrop(event)">
    <button class="mu-trailer-modal__close" data-mu-close-trailer aria-label="Close">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </button>
    <div class="mu-modal__backdrop" data-mu-close-trailer></div>
    <div class="nr-modal__dialog mu-modal__content mu-modal__content--video mu-trailer-modal-wrapper">
        <div class="nr-modal__video" id="nrVideoArea"></div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    var ajaxNonce = '<?php echo esc_attr($ajax_nonce); ?>';
    
    // =====================================================================
    // FEATURED CAROUSEL
    // =====================================================================
    var currentSlide = 0;
    var totalSlides = <?php echo count($featured_items); ?>;
    var autoplayInterval;
    
    function moveSlide(dir) {
        currentSlide = (currentSlide + dir + totalSlides) % totalSlides;
        goToSlide(currentSlide);
    }
    
    function goToSlide(index) {
        currentSlide = index;
        var track = document.getElementById('nrTrack');
        if (track) {
            track.style.transform = 'translateX(-' + (index * 100) + '%)';
        }
        document.querySelectorAll('.nr-featured__dot').forEach(function(dot, i) {
            dot.classList.toggle('is-active', i === index);
        });
    }
    
    function startAutoplay() {
        autoplayInterval = setInterval(function() {
            moveSlide(1);
        }, 6000);
    }
    
    function stopAutoplay() {
        clearInterval(autoplayInterval);
    }
    
    var featured = document.getElementById('nrFeatured');
    if (featured) {
        featured.addEventListener('mouseenter', stopAutoplay);
        featured.addEventListener('mouseleave', startAutoplay);
        startAutoplay();
    }
    
    // =====================================================================
    // TIME DROPDOWN
    // =====================================================================
    window.toggleNrTimeDropdown = function() {
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
    // CARD HANDLERS
    // =====================================================================
    document.querySelectorAll('.nr-scroll-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            var card = e.target.closest('.nr-card');
            if (!card) return;
            
            var btn = e.target.closest('.nr-card__btn');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                
                var action = btn.getAttribute('data-action');
                var favId = btn.getAttribute('data-fav');
                
                if (action) {
                    handlePlayAction(action);
                } else if (favId) {
                    toggleFavNr(favId, btn);
                }
                return;
            }
            
            var url = card.getAttribute('data-url');
            if (url) window.location.href = url;
        });
    });
    
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
            window.location.href = action.replace('watch:', '');
        }
    }

    // =====================================================================
    // FAVORITE
    // =====================================================================
    window.toggleFavNr = function(id, btn) {
        if (!id) return;

        var saved = JSON.parse(localStorage.getItem('mu_favorites') || '[]');
        var idx = saved.indexOf(parseInt(id));
        var isAdding = idx === -1;

        if (isAdding) {
            saved.push(parseInt(id));
        } else {
            saved.splice(idx, 1);
        }

        localStorage.setItem('mu_favorites', JSON.stringify(saved));
        btn.classList.toggle('is-on', isAdding);

        var label = btn.closest('.nr-featured__btn, .nr-card');
        if (label) {
            var labelEl = label.querySelector('.fav-label');
            if (labelEl) {
                labelEl.textContent = isAdding ? '<?php esc_attr_e('Added', 'astra-child'); ?>' : '<?php esc_attr_e('My List', 'astra-child'); ?>';
            }
        }

        showToast(isAdding ? '<?php esc_attr_e('Added to My List', 'astra-child'); ?>' : '<?php esc_attr_e('Removed from My List', 'astra-child'); ?>', 'success');
    };

    // =====================================================================
    // TRAILER MODAL - Local fallback functions
    // =====================================================================
    window.openTrailerModal = function(url) {
        if (!url) return;

        var modal = document.getElementById('nrModal');
        var area = document.getElementById('nrVideoArea');
        if (!modal || !area) return;

        var embedUrl = url;
        if (url.indexOf('youtube.com/watch') !== -1) {
            var vid = url.match(/[?&]v=([^&]+)/);
            if (vid) embedUrl = 'https://www.youtube.com/embed/' + vid[1] + '?autoplay=1&rel=0&modestbranding=1';
        } else if (url.indexOf('youtu.be/') !== -1) {
            var vid = url.match(/youtu\.be\/([^?]+)/);
            if (vid) embedUrl = 'https://www.youtube.com/embed/' + vid[1] + '?autoplay=1&rel=0&modestbranding=1';
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        area.innerHTML = '<iframe src="' + embedUrl + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%;height:100%;border:none;"></iframe>';
    };

    window.closeTrailerModal = function() {
        var modal = document.getElementById('nrModal');
        var area = document.getElementById('nrVideoArea');
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
        if (area) area.innerHTML = '';
    };

    window.closeModalOnBackdrop = function(e) {
        if (e.target === e.currentTarget) closeTrailerModal();
    };

    // Close on backdrop click
    document.addEventListener('click', function(e) {
        var modal = document.getElementById('nrModal');
        if (modal && modal.classList.contains('is-open') && e.target.classList.contains('mu-modal__backdrop')) {
            closeTrailerModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeTrailerModal();
    });
    
    // =====================================================================
    // NOTIFICATION CTA
    // =====================================================================
    window.dismissNotif = function() {
        var notif = document.getElementById('nrNotif');
        if (notif) {
            notif.classList.add('is-hidden');
            localStorage.setItem('nr_notif_dismissed', '1');
        }
    };
    
    window.enableNotifications = function() {
        if (!('Notification' in window)) {
            showToast('<?php esc_attr_e('Notifications not supported', 'astra-child'); ?>', 'info');
            return;
        }
        
        Notification.requestPermission().then(function(permission) {
            if (permission === 'granted') {
                localStorage.setItem('nr_notifications_enabled', '1');
                showToast('<?php esc_attr_e('Notifications enabled!', 'astra-child'); ?>', 'success');
                dismissNotif();
            } else {
                showToast('<?php esc_attr_e('Notifications denied', 'astra-child'); ?>', 'info');
            }
        });
    };
    
    // Show notification CTA if not dismissed
    if (!localStorage.getItem('nr_notif_dismissed')) {
        var notif = document.getElementById('nrNotif');
        if (notif) notif.removeAttribute('hidden');
    }
    
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
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:12px 20px;background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;z-index:99999';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 2000);
    }
    
})();
</script>

<?php get_footer();
