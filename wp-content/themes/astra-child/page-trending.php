<?php
/**
 * Template Name: Trending
 * Premium OTT Trending Page - Exact Reference Match
 */
defined('ABSPATH') || exit;
get_header();
get_template_part('template-parts/streaming/header');

// Get filter params
$cat_filter = isset($_GET['cat']) ? sanitize_key((string) $_GET['cat']) : 'all';
if (!in_array($cat_filter, ['all', 'movies', 'tv'], true)) {
    $cat_filter = 'all';
}

$range = isset($_GET['range']) ? sanitize_key((string) $_GET['range']) : 'week';
if (!in_array($range, ['today', 'week', 'month'], true)) {
    $range = 'week';
}

// Date range
$d = new DateTimeImmutable('today', wp_timezone());
if ($range === 'today') {
    $after = $d->format('Y-m-d 00:00:00');
} elseif ($range === 'week') {
    $after = $d->modify('-7 days')->format('Y-m-d 00:00:00');
} else {
    $after = $d->modify('-31 days')->format('Y-m-d 00:00:00');
}

// Post types
$post_types = ($cat_filter === 'all') ? ['movie', 'tv_show'] : [$cat_filter];

// Trending query
$trending_q = new WP_Query([
    'post_type'           => $post_types,
    'posts_per_page'      => 48,
    'post_status'         => 'publish',
    'meta_key'            => '_view_count',
    'orderby'            => 'meta_value_num',
    'order'              => 'DESC',
    'date_query'         => [['after' => $after]],
    'ignore_sticky_posts' => true,
]);

if (!$trending_q->have_posts()) {
    wp_reset_postdata();
    $trending_q = movie_ui_query([
        'post_type'      => $post_types,
        'posts_per_page' => 48,
        'meta_key'       => '_view_count',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ]);
}

// URLs
$base_u = mu_get_page_url_by_slug('trending');
$movies_archive = trailingslashit(home_url('movies'));
$tv_archive = trailingslashit(home_url('tv'));
$watch_base = mu_get_page_url_by_slug('watch');

$pills = [
    'today' => __('Today', 'astra-child'),
    'week'  => __('This Week', 'astra-child'),
    'month' => __('This Month', 'astra-child'),
];
?>

<style>
/* ============================================
   TRENDING PAGE - EXACT REFERENCE MATCH
   ============================================ */
.trending-page {
    background: #08080c;
    min-height: 100vh;
    padding-top: 70px;
}

/* Page Header */
.trending-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 30px 4% 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.trending-page-title {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    color: #fff;
    margin: 0;
    letter-spacing: -0.02em;
}

/* Filters */
.trending-filters-bar {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 0 4% 30px;
    max-width: 1400px;
    margin: 0 auto;
}

.trending-filter-tabs {
    display: flex;
    gap: 4px;
    background: rgba(255,255,255,0.1);
    border-radius: 25px;
    padding: 4px;
}

.trending-filter-tab {
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    transition: all 0.2s;
}

.trending-filter-tab:hover {
    color: #fff;
    background: rgba(255,255,255,0.05);
}

.trending-filter-tab.is-active {
    background: #e50914;
    color: #fff;
}

/* Time Dropdown */
.trending-time-dropdown {
    position: relative;
}

.trending-time-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.trending-time-btn:hover {
    background: rgba(255,255,255,0.12);
}

.trending-time-btn svg {
    transition: transform 0.2s;
}

.trending-time-dropdown.is-open .trending-time-btn svg {
    transform: rotate(180deg);
}

.trending-time-menu {
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

.trending-time-dropdown.is-open .trending-time-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.trending-time-option {
    display: block;
    padding: 10px 16px;
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.15s;
}

.trending-time-option:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.trending-time-option.is-active {
    color: #e50914;
}

/* Main Content */
.trending-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 4%;
}

/* Section */
.trending-section {
    margin-bottom: 40px;
}

.trending-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.trending-section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.trending-section-link {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    transition: color 0.2s;
}

.trending-section-link:hover {
    color: #e50914;
}

/* Horizontal Scroll Row */
.trending-scroll-row {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    padding-bottom: 15px;
}

.trending-scroll-row::-webkit-scrollbar {
    display: none;
}

/* Trend Card */
.trend-card {
    flex-shrink: 0;
    width: 160px;
    cursor: pointer;
    transition: transform 0.3s;
}

.trend-card:hover {
    transform: scale(1.05);
}

@media (min-width: 768px) {
    .trend-card {
        width: 180px;
    }
}

.trend-card__poster-wrap {
    position: relative;
    aspect-ratio: 2/3;
    border-radius: 10px;
    overflow: hidden;
    background: #1a1a2e;
    margin-bottom: 10px;
}

.trend-card__poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.trend-card__rank {
    position: absolute;
    top: 0;
    left: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, #e50914, #b81d24);
    border-radius: 0 0 8px 0;
}

.trend-card__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 50%);
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 12px;
}

.trend-card:hover .trend-card__overlay {
    opacity: 1;
}

.trend-card__actions {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}

.trend-card__btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,0.25);
    background: rgba(42, 42, 42, 0.85);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    padding: 0;
}

.trend-card__btn:hover {
    transform: scale(1.12);
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.6);
}

.trend-card__btn:active {
    transform: scale(0.95);
}

.trend-card__btn--play {
    background: #fff;
    border-color: #fff;
    color: #111;
}

.trend-card__btn--play:hover .trend-card__btn--play svg path,
.trend-card__btn--play:hover svg path {
    fill: #111 !important;
}

.trend-card__btn--play:hover {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 0.9);
}

/* Trend card icon styles */
.trend-ico-play,
.trend-ico-fav {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
}

.trend-ico-play svg,
.trend-ico-fav svg {
    width: 16px;
    height: 16px;
    display: block;
}

/* Favorite active state */
.trend-card__btn--fav.is-on,
.mu-fav.is-on {
    background: rgba(229, 9, 20, 0.3) !important;
    border-color: rgba(229, 9, 20, 0.8) !important;
}

.trend-card__btn--fav.is-on .trend-ico-fav-h,
.mu-fav.is-on .trend-ico-fav-h,
.mu-fav.is-on .mu-ico-plus-h {
    display: none;
}

.trend-card__info {
    text-align: left;
}

.trend-card__title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trend-card__meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
}

.trend-card__rating {
    color: #ffd700;
}

/* Trending Today Section */
.trending-today-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

@media (min-width: 768px) {
    .trending-today-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1200px) {
    .trending-today-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

.trend-today-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: rgba(255,255,255,0.04);
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s;
}

.trend-today-item:hover {
    background: rgba(255,255,255,0.08);
}

.trend-today-item__rank {
    font-size: 1.5rem;
    font-weight: 800;
    color: rgba(255,255,255,0.25);
    min-width: 30px;
}

.trend-today-item:nth-child(1) .trend-today-item__rank { color: #ffd700; }
.trend-today-item:nth-child(2) .trend-today-item__rank { color: #c0c0c0; }
.trend-today-item:nth-child(3) .trend-today-item__rank { color: #cd7f32; }

.trend-today-item__thumb {
    width: 45px;
    height: 68px;
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
    background: #1a1a2e;
}

.trend-today-item__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.trend-today-item__info {
    flex: 1;
    min-width: 0;
}

.trend-today-item__title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trend-today-item__meta {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.45);
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Empty State */
.trending-empty {
    text-align: center;
    padding: 80px 20px;
}

.trending-empty__icon {
    font-size: 3rem;
    margin-bottom: 16px;
}

.trending-empty__title {
    font-size: 1.3rem;
    color: #fff;
    margin: 0 0 8px;
}

.trending-empty__desc {
    color: rgba(255,255,255,0.5);
    margin: 0 0 24px;
}

.trending-empty__actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .trending-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .trending-filters-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .trend-card {
        width: 140px;
    }
}
/* FIX: Trending 9-10 bị che bên phải */
.trending-content {
  max-width: none !important;
  width: 100% !important;
  padding-left: 4% !important;
  padding-right: 5% !important;
  overflow: hidden !important;
}

.trending-today-grid {
  grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
  gap: 14px !important;
}

.trend-today-item {
  min-width: 0 !important;
  overflow: hidden !important;
}

.trend-today-item__info {
  min-width: 0 !important;
}

.trend-today-item__title {
  max-width: 100% !important;
}
</style>

<div class="trending-page">
    <!-- Page Header -->
    <div class="trending-page-header">
        <h1 class="trending-page-title">TRENDING</h1>
        
        <div class="trending-filters-bar" style="padding: 0;">
            <!-- Category Tabs -->
            <div class="trending-filter-tabs">
                <a href="<?php echo esc_url(add_query_arg(['cat' => 'all', 'range' => $range], $base_u)); ?>" 
                   class="trending-filter-tab<?php echo $cat_filter === 'all' ? ' is-active' : ''; ?>">
                    All
                </a>
                <a href="<?php echo esc_url(add_query_arg(['cat' => 'movies', 'range' => $range], $base_u)); ?>" 
                   class="trending-filter-tab<?php echo $cat_filter === 'movies' ? ' is-active' : ''; ?>">
                    Movies
                </a>
                <a href="<?php echo esc_url(add_query_arg(['cat' => 'tv', 'range' => $range], $base_u)); ?>" 
                   class="trending-filter-tab<?php echo $cat_filter === 'tv' ? ' is-active' : ''; ?>">
                    TV Shows
                </a>
            </div>
            
            <!-- Time Dropdown -->
            <div class="trending-time-dropdown" id="timeDropdown">
                <button class="trending-time-btn" type="button" onclick="toggleTimeDropdown()">
                    <span id="timeLabel"><?php echo esc_html($pills[$range]); ?></span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="trending-time-menu">
                    <?php foreach ($pills as $slug => $label) : ?>
                        <a href="<?php echo esc_url(add_query_arg(['range' => $slug, 'cat' => $cat_filter], $base_u)); ?>" 
                           class="trending-time-option<?php echo $range === $slug ? ' is-active' : ''; ?>">
                            <?php echo esc_html($label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="trending-content">
        <?php if ($trending_q->have_posts()) : $rank = 0; ?>
            
            <!-- Trending Today Section -->
            <section class="trending-section">
                <div class="trending-section-header">
                    <h2 class="trending-section-title">TRENDING <?php echo strtoupper($range === 'today' ? 'TODAY' : ($range === 'week' ? 'THIS WEEK' : 'THIS MONTH')); ?></h2>
                </div>
                <div class="trending-today-grid">
                    <?php while ($trending_q->have_posts() && $rank < 10) : $trending_q->the_post(); $rank++; ?>
                        <?php
                        $pid = get_the_ID();
                        $title = get_the_title();
                        $year = movie_ui_meta($pid, ['year', '_release_year', 'release_date'], '');
                        if ($year && strlen($year) > 4) $year = substr($year, 0, 4);
                        $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                        $poster = get_the_post_thumbnail_url($pid, 'medium') ?: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 45 68"%3E%3Crect fill="%231a1a2e" width="45" height="68"/%3E%3C/svg%3E';
                        $detail_url = get_permalink($pid);
                        ?>
                        <a href="<?php echo esc_url($detail_url); ?>" class="trend-today-item">
                            <span class="trend-today-item__rank"><?php echo esc_html($rank); ?></span>
                            <div class="trend-today-item__thumb">
                                <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                            </div>
                            <div class="trend-today-item__info">
                                <h3 class="trend-today-item__title"><?php echo esc_html($title); ?></h3>
                                <div class="trend-today-item__meta">
                                    <?php if ($year) : ?><span><?php echo esc_html($year); ?></span><?php endif; ?>
                                    <?php if ($rating) : ?>
                                        <span style="color:#ffd700;">★ <?php echo esc_html($rating); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
            
            <!-- Trending Movies Row (if not filtering TV only) -->
            <?php if ($cat_filter !== 'tv') : ?>
            <?php 
            $movies_q = new WP_Query([
                'post_type'           => ['movie'],
                'posts_per_page'      => 20,
                'post_status'         => 'publish',
                'meta_key'            => '_view_count',
                'orderby'            => 'meta_value_num',
                'order'              => 'DESC',
                'ignore_sticky_posts' => true,
            ]);
            ?>
            <?php if ($movies_q->have_posts()) : $m_rank = 0; ?>
            <section class="trending-section">
                <div class="trending-section-header">
                    <h2 class="trending-section-title">TRENDING MOVIES</h2>
                    <a href="<?php echo esc_url($movies_archive); ?>" class="trending-section-link">View All</a>
                </div>
                <div class="trending-scroll-row">
                    <?php while ($movies_q->have_posts()) : $movies_q->the_post(); $m_rank++; ?>
                        <?php
                        $pid = get_the_ID();
                        $title = get_the_title();
                        $year = movie_ui_meta($pid, ['year', '_release_year', 'release_date'], '');
                        if ($year && strlen($year) > 4) $year = substr($year, 0, 4);
                        $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                        $runtime = movie_ui_meta($pid, ['runtime', '_runtime'], '');
                        $poster = get_the_post_thumbnail_url($pid, 'medium') ?: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 270"%3E%3Crect fill="%231a1a2e" width="180" height="270"/%3E%3C/svg%3E';
                        $trailer = movie_ui_meta($pid, ['trailer_url', '_trailer_url'], '');
                        $video = movie_ui_meta($pid, ['video_url', '_video_url'], '');
                        $detail_url = get_permalink($pid);
                        $watch_url = add_query_arg('id', $pid, $watch_base);
                        $play_action = 'watch:' . esc_url($watch_url);
                        ?>
                        <div class="trend-card" 
                             data-id="<?php echo esc_attr($pid); ?>"
                             data-detail-url="<?php echo esc_url($detail_url); ?>"
                             data-trailer="<?php echo $trailer ? esc_attr($trailer) : ''; ?>"
                             data-video="<?php echo $video ? esc_url($watch_url) : ''; ?>">
                            <div class="trend-card__poster-wrap">
                                <span class="trend-card__rank"><?php echo esc_html($m_rank); ?></span>
                                <img class="trend-card__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                                <div class="trend-card__overlay">
                                    <div class="trend-card__actions">
                                        <button class="trend-card__btn trend-card__btn--play mu-btn mu-btn--play" data-watch-url="<?php echo esc_url($watch_url); ?>" title="Play">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="#000000" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </button>
                                        <button class="trend-card__btn trend-card__btn--fav mu-btn mu-fav" data-favorite="<?php echo esc_attr($pid); ?>" title="Add to My List">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg">
                                                <line x1="12" y1="5" x2="12" y2="19"/>
                                                <line class="trend-ico-fav-h mu-ico-plus-h" x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="trend-card__info">
                                <h3 class="trend-card__title"><?php echo esc_html($title); ?></h3>
                                <div class="trend-card__meta">
                                    <?php if ($year) : ?><span><?php echo esc_html($year); ?></span><?php endif; ?>
                                    <?php if ($runtime) : ?><span><?php echo esc_html($runtime); ?></span><?php endif; ?>
                                    <?php if ($rating) : ?>
                                        <span class="trend-card__rating">★ <?php echo esc_html($rating); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
            <?php endif; ?>
            <?php endif; ?>
            
            <!-- Trending TV Shows Row (if not filtering Movies only) -->
            <?php if ($cat_filter !== 'movies') : ?>
            <?php 
            $tv_q = new WP_Query([
                'post_type'           => ['tv_show'],
                'posts_per_page'      => 20,
                'post_status'         => 'publish',
                'meta_key'            => '_view_count',
                'orderby'            => 'meta_value_num',
                'order'              => 'DESC',
                'ignore_sticky_posts' => true,
            ]);
            ?>
            <?php if ($tv_q->have_posts()) : $t_rank = 0; ?>
            <section class="trending-section">
                <div class="trending-section-header">
                    <h2 class="trending-section-title">TRENDING TV SHOWS</h2>
                    <a href="<?php echo esc_url($tv_archive); ?>" class="trending-section-link">View All</a>
                </div>
                <div class="trending-scroll-row">
                    <?php while ($tv_q->have_posts()) : $tv_q->the_post(); $t_rank++; ?>
                        <?php
                        $pid = get_the_ID();
                        $title = get_the_title();
                        $year = movie_ui_meta($pid, ['year', '_release_year', 'release_date'], '');
                        if ($year && strlen($year) > 4) $year = substr($year, 0, 4);
                        $rating = movie_ui_meta($pid, ['rating', '_rating'], '');
                        $seasons = movie_ui_meta($pid, ['seasons', '_seasons'], '');
                        $poster = get_the_post_thumbnail_url($pid, 'medium') ?: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 270"%3E%3Crect fill="%231a1a2e" width="180" height="270"/%3E%3C/svg%3E';
                        $trailer = movie_ui_meta($pid, ['trailer_url', '_trailer_url'], '');
                        $video = movie_ui_meta($pid, ['video_url', '_video_url'], '');
                        $detail_url = get_permalink($pid);
                        $watch_url = add_query_arg('id', $pid, $watch_base);
                        $play_action = 'watch:' . esc_url($watch_url);
                        ?>
                        <div class="trend-card" 
                             data-id="<?php echo esc_attr($pid); ?>"
                             data-detail-url="<?php echo esc_url($detail_url); ?>"
                             data-trailer="<?php echo $trailer ? esc_attr($trailer) : ''; ?>"
                             data-video="<?php echo $video ? esc_url($watch_url) : ''; ?>">
                            <div class="trend-card__poster-wrap">
                                <span class="trend-card__rank"><?php echo esc_html($t_rank); ?></span>
                                <img class="trend-card__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                                <div class="trend-card__overlay">
                                    <div class="trend-card__actions">
                                        <button class="trend-card__btn trend-card__btn--play mu-btn mu-btn--play" data-watch-url="<?php echo esc_url($watch_url); ?>" title="Play">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="#000000" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </button>
                                        <button class="trend-card__btn trend-card__btn--fav mu-btn mu-fav" data-favorite="<?php echo esc_attr($pid); ?>" title="Add to My List">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg">
                                                <line x1="12" y1="5" x2="12" y2="19"/>
                                                <line class="trend-ico-fav-h mu-ico-plus-h" x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="trend-card__info">
                                <h3 class="trend-card__title"><?php echo esc_html($title); ?></h3>
                                <div class="trend-card__meta">
                                    <?php if ($year) : ?><span><?php echo esc_html($year); ?></span><?php endif; ?>
                                    <?php if ($seasons) : ?><span><?php echo esc_html($seasons); ?> Seasons</span><?php endif; ?>
                                    <?php if ($rating) : ?>
                                        <span class="trend-card__rating">★ <?php echo esc_html($rating); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
            <?php endif; ?>
            <?php endif; ?>
            
        <?php else : ?>
            <div class="trending-empty">
                <div class="trending-empty__icon">🔥</div>
                <h2 class="trending-empty__title">No trending content found</h2>
                <p class="trending-empty__desc">Check back later for the hottest movies and TV shows.</p>
                <div class="trending-empty__actions">
                    <a href="<?php echo esc_url($movies_archive); ?>" class="btn btn--primary">Explore Movies</a>
                    <a href="<?php echo esc_url($tv_archive); ?>" class="btn btn--secondary">Explore TV Shows</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Trailer Modal - Uses global mu-trailer-modal from movie-ui.css -->
<div id="mu-trailer-modal" class="mu-modal mu-trailer-modal" aria-hidden="true" role="dialog">
    <button class="mu-trailer-modal__close" data-mu-close-trailer aria-label="Close">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="mu-modal__backdrop" data-mu-close-trailer></div>
    <div class="mu-modal__content mu-modal__content--video mu-trailer-modal-wrapper">
        <div id="mu-trailer-video-area"></div>
    </div>
</div>

<script>
// Trend card interactions - use global openTrailer from movie-ui.js
(function() {
    var container = document.querySelector('.trending-page');
    if (!container) return;

    // Card click
    container.addEventListener('click', function(e) {
        var card = e.target.closest('.trend-card');
        if (!card || e.target.closest('.trend-card__btn')) return;

        var detailUrl = card.getAttribute('data-detail-url');
        if (detailUrl) window.location.href = detailUrl;
    });

    // Play button - use global openTrailer function
    container.addEventListener('click', function(e) {
        var btn = e.target.closest('.trend-card__btn--play');
        if (!btn) return;

        var card = btn.closest('.trend-card');
        var action = btn.getAttribute('data-action') || '';

        if (action.indexOf('trailer:') === 0) {
            var url = action.replace('trailer:', '');
            if (typeof window.openTrailer === 'function') {
                window.openTrailer(url);
            } else {
                openTrailerModal(url);
            }
        } else if (action.indexOf('watch:') === 0) {
            var url = action.replace('watch:', '');
            window.location.href = url;
        }
    });

    // Favorite button
    container.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-favorite]');
        if (!btn) return;

        var id = btn.getAttribute('data-favorite');
        if (!id) return;

        btn.classList.toggle('is-on');

        if (typeof toggleFavorite === 'function') {
            toggleFavorite(id, btn);
        }
    });
})();

// Local fallback trailer modal functions (used if global openTrailer is not available)
function openTrailerModal(url) {
    var modal = document.getElementById('mu-trailer-modal');
    var area = document.getElementById('mu-trailer-video-area');
    if (!modal || !area) return;

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
    area.innerHTML = '<iframe src="' + embedUrl + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="width:100%;height:100%;border:none;"></iframe>';
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

window.toggleTimeDropdown = function () {
  const dropdown = document.getElementById('timeDropdown');
  if (!dropdown) return;

  dropdown.classList.toggle('is-open');
};

document.addEventListener('click', function (e) {
  const dropdown = document.getElementById('timeDropdown');
  if (!dropdown) return;

  const clickedInside = dropdown.contains(e.target);
  const clickedBtn = e.target.closest('.trending-time-btn');

  if (!clickedInside && !clickedBtn) {
    dropdown.classList.remove('is-open');
  }
});
</script>

<?php get_footer(); ?>
