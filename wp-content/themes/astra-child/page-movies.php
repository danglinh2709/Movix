<?php
/**
 * Template Name: Movies Page
 * Premium OTT Streaming Platform - Movies Catalog
 * Netflix/VieON/Prime Video Style
 */
defined('ABSPATH') || exit;

get_header();
get_template_part('template-parts/streaming/header');

// ============================================================
// SETUP
// ============================================================
$watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));

// ============================================================
// URL PARAMS
// ============================================================
$search_q = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$genre    = isset($_GET['genre']) ? sanitize_text_field(wp_unslash($_GET['genre'])) : '';
$year     = isset($_GET['year']) ? sanitize_text_field(wp_unslash($_GET['year'])) : '';
$country  = isset($_GET['country']) ? sanitize_text_field(wp_unslash($_GET['country'])) : '';
$rating   = isset($_GET['rating']) ? floatval($_GET['rating']) : 0;
$quality  = isset($_GET['quality']) ? sanitize_text_field(wp_unslash($_GET['quality'])) : '';
$sort     = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'latest';
$page_num = max(1, get_query_var('paged') ?: 1);
$per_page = 24;

$is_filtered = !empty($search_q) || !empty($genre) || !empty($year) || !empty($country) || !empty($rating) || !empty($quality);

// ============================================================
// DYNAMIC DATA
// ============================================================

// All genres
$all_genres = get_terms(['taxonomy' => 'genre', 'hide_empty' => true, 'number' => 50]);
$genre_map = [];
foreach ($all_genres as $g) {
    $genre_map[$g->slug] = $g->name;
}

// All countries
$all_countries = get_terms(['taxonomy' => 'country', 'hide_empty' => true, 'number' => 50]);

// Years from DB
global $wpdb;
$years = $wpdb->get_col(
    "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
     WHERE meta_key = '_release_year' AND meta_value != '' AND meta_value REGEXP '^[0-9]+$'
     ORDER BY meta_value DESC LIMIT 30"
);

// Total count
$total_movies = wp_count_posts('movie')->publish ?? 0;
$latest_year  = !empty($years) ? max($years) : date('Y');
$top_rating   = $wpdb->get_var(
    "SELECT MAX(CAST(meta_value AS DECIMAL(3,1))) FROM {$wpdb->postmeta} 
     WHERE meta_key = '_rating' AND meta_value != '' AND meta_value REGEXP '^[0-9.]+$'"
);

// ============================================================
// BUILD WP_QUERY
// ============================================================
function mu_build_movies_catalog_query($args = []) {
    $defaults = [
        'post_type'      => 'movie',
        'posts_per_page' => 24,
        'paged'          => 1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];
    $args = wp_parse_args($args, $defaults);
    
    $meta_query = [];
    $tax_query  = [];
    
    // Year
    if (!empty($_GET['year'])) {
        $y = sanitize_text_field($_GET['year']);
        if ($y === 'before_2020') {
            $meta_query[] = ['key' => '_release_year', 'value' => '2020', 'compare' => '<', 'type' => 'NUMERIC'];
        } elseif (is_numeric($y)) {
            $meta_query[] = ['key' => '_release_year', 'value' => $y, 'compare' => '=', 'type' => 'NUMERIC'];
        }
    }
    
    // Rating
    if (!empty($_GET['rating']) && is_numeric($_GET['rating'])) {
        $meta_query[] = [
            'key'     => '_rating',
            'value'   => floatval($_GET['rating']),
            'compare' => '>=',
            'type'    => 'DECIMAL(3,1)'
        ];
    }
    
    // Quality
    if (!empty($_GET['quality'])) {
        $meta_query[] = ['key' => '_quality', 'value' => sanitize_text_field($_GET['quality'])];
    }
    
    // Genre
    if (!empty($_GET['genre'])) {
        $tax_query[] = ['taxonomy' => 'genre', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['genre'])];
    }
    
    // Country
    if (!empty($_GET['country'])) {
        $tax_query[] = ['taxonomy' => 'country', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['country'])];
    }
    
    if (!empty($meta_query)) $args['meta_query'] = $meta_query;
    if (!empty($tax_query))  $args['tax_query']  = $tax_query;
    
    // Search
    if (!empty($_GET['s'])) {
        $args['s'] = sanitize_text_field($_GET['s']);
    }
    
    // Sort
    $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'latest';
    switch ($sort) {
        case 'popular':
            $args['meta_key'] = '_view_count';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
            break;
        case 'top_rated':
            $args['meta_key'] = '_rating';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
            break;
        case 'oldest':
            $args['orderby'] = 'date';
            $args['order']   = 'ASC';
            break;
        case 'a_z':
            $args['orderby'] = 'title';
            $args['order']   = 'ASC';
            break;
        case 'z_a':
            $args['orderby'] = 'title';
            $args['order']   = 'DESC';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
    }
    
    return new WP_Query($args);
}

$catalog_q    = mu_build_movies_catalog_query(['paged' => $page_num, 'posts_per_page' => $per_page]);
$found_posts  = $catalog_q->found_posts;
$max_pages    = $catalog_q->max_num_pages;
$showing_from = (($page_num - 1) * $per_page) + 1;
$showing_to   = min($page_num * $per_page, $found_posts);

// ============================================================
// ACTIVE FILTERS
// ============================================================
$active_filters = [];
if ($genre) {
    $term = get_term_by('slug', $genre, 'genre');
    $active_filters['genre'] = ['label' => $term ? $term->name : $genre, 'key' => 'genre'];
}
if ($country) {
    $term = get_term_by('slug', $country, 'country');
    $active_filters['country'] = ['label' => $term ? $term->name : $country, 'key' => 'country'];
}
if ($year) {
    $active_filters['year'] = ['label' => $year === 'before_2020' ? 'Before 2020' : $year, 'key' => 'year'];
}
if ($rating) {
    $active_filters['rating'] = ['label' => $rating . '+ Rating', 'key' => 'rating'];
}
if ($quality) {
    $active_filters['quality'] = ['label' => $quality, 'key' => 'quality'];
}
if ($search_q) {
    $active_filters['s'] = ['label' => '"' . $search_q . '"', 'key' => 's'];
}

$base_url = get_permalink();

// ============================================================
// HERO BACKDROP (optional)
$hero_backdrop = '';
$hero_q = new WP_Query([
    'post_type' => 'movie',
    'posts_per_page' => 1,
    'orderby' => 'rand',
    'meta_key' => '_backdrop_url',
    'meta_compare' => 'EXISTS',
]);
if ($hero_q->have_posts()) {
    $hero_q->the_post();
    $hero_backdrop = movie_ui_meta(get_the_ID(), ['backdrop_url', '_backdrop_url'], '');
    if (!$hero_backdrop) {
        $tid = get_post_thumbnail_id(get_the_ID());
        if ($tid) { $bg = wp_get_attachment_image_src($tid, 'large'); $hero_backdrop = $bg[0] ?? ''; }
    }
    wp_reset_postdata();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Movies - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/ms-movies.css')); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
</head>
<body class="ms-movies-page">

<div class="ms-page">

    <!-- ============================================================ -->
    <!-- HERO / CATALOG HEADER -->
    <!-- ============================================================ -->
    <section class="ms-hero" style="<?php echo $hero_backdrop ? "background-image:url('" . esc_url($hero_backdrop) . "')" : ''; ?>">
        <div class="ms-hero__overlay"></div>
        <div class="ms-hero__content">
            <div class="ms-hero__badge">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                MOVIES
            </div>
            <h1 class="ms-hero__title">Explore Thousands of Movies</h1>
            <p class="ms-hero__subtitle">From action blockbusters to indie gems — stream the best movies, anytime.</p>
            <div class="ms-hero__stats">
                <div class="ms-stat">
                    <span class="ms-stat__num"><?php echo number_format($total_movies); ?></span>
                    <span class="ms-stat__label">Movies</span>
                </div>
                <div class="ms-stat">
                    <span class="ms-stat__num"><?php echo esc_html($latest_year); ?></span>
                    <span class="ms-stat__label">Latest Year</span>
                </div>
                <?php if ($top_rating) : ?>
                <div class="ms-stat">
                    <span class="ms-stat__num"><?php echo number_format($top_rating, 1); ?></span>
                    <span class="ms-stat__label">Top Rating</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- MAIN LAYOUT -->
    <!-- ============================================================ -->
    <div class="ms-layout">

        <!-- SIDEBAR FILTER -->
        <aside class="ms-sidebar" id="ms-sidebar">
            <form method="GET" action="<?php echo esc_url($base_url); ?>" id="ms-filter-form">
                
                <div class="ms-sb-header">
                    <h3>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        Filters
                    </h3>
                    <?php if ($is_filtered) : ?>
                    <a href="<?php echo esc_url($base_url); ?>" class="ms-sb-reset">Clear All</a>
                    <?php endif; ?>
                </div>

                <!-- Search -->
                <div class="ms-sb-section">
                    <h4 class="ms-sb-section__title">Search</h4>
                    <div class="ms-sb-search">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="s" placeholder="Search movies..." value="<?php echo esc_attr($search_q); ?>" id="ms-search-input">
                    </div>
                </div>

                <!-- Genres -->
                <div class="ms-sb-section ms-sb-section--toggle" data-section="genre">
                    <h4 class="ms-sb-section__title" data-toggle>
                        Genres
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </h4>
                    <div class="ms-sb-section__body" id="ms-sb-genre">
                        <label class="ms-sb-radio">
                            <input type="radio" name="genre" value="" <?php checked($genre, ''); ?>>
                            <span class="ms-sb-radio__mark"></span>
                            <span class="ms-sb-radio__label">All Genres</span>
                        </label>
                        <?php $gcount = 0; foreach ($all_genres as $g) : $gcount++; ?>
                            <label class="ms-sb-radio" <?php if ($gcount > 8) echo 'style="display:none" data-hidden="1"'; ?>>
                                <input type="radio" name="genre" value="<?php echo esc_attr($g->slug); ?>" <?php checked($genre, $g->slug); ?>>
                                <span class="ms-sb-radio__mark"></span>
                                <span class="ms-sb-radio__label"><?php echo esc_html($g->name); ?></span>
                                <span class="ms-sb-radio__count"><?php echo esc_html($g->count); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <?php if ($gcount > 8) : ?>
                        <button type="button" class="ms-sb-more" data-show-more="genre">Show <?php echo $gcount - 8; ?> more</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Release Year -->
                <div class="ms-sb-section ms-sb-section--toggle" data-section="year">
                    <h4 class="ms-sb-section__title" data-toggle>
                        Release Year
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </h4>
                    <div class="ms-sb-section__body" id="ms-sb-year">
                        <label class="ms-sb-radio">
                            <input type="radio" name="year" value="" <?php checked($year, ''); ?>>
                            <span class="ms-sb-radio__mark"></span>
                            <span class="ms-sb-radio__label">All Years</span>
                        </label>
                        <?php foreach (array_slice($years, 0, 8) as $y) : ?>
                            <label class="ms-sb-radio">
                                <input type="radio" name="year" value="<?php echo esc_attr($y); ?>" <?php checked($year, $y); ?>>
                                <span class="ms-sb-radio__mark"></span>
                                <span class="ms-sb-radio__label"><?php echo esc_html($y); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <?php if (count($years) > 8) : ?>
                            <label class="ms-sb-radio">
                                <input type="radio" name="year" value="before_2020" <?php checked($year, 'before_2020'); ?>>
                                <span class="ms-sb-radio__mark"></span>
                                <span class="ms-sb-radio__label">Before 2020</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Country -->
                <?php if (!empty($all_countries)) : ?>
                <div class="ms-sb-section ms-sb-section--toggle" data-section="country">
                    <h4 class="ms-sb-section__title" data-toggle>
                        Country
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </h4>
                    <div class="ms-sb-section__body" id="ms-sb-country">
                        <label class="ms-sb-radio">
                            <input type="radio" name="country" value="" <?php checked($country, ''); ?>>
                            <span class="ms-sb-radio__mark"></span>
                            <span class="ms-sb-radio__label">All Countries</span>
                        </label>
                        <?php $ccount = 0; foreach ($all_countries as $c) : $ccount++; ?>
                            <label class="ms-sb-radio" <?php if ($ccount > 6) echo 'style="display:none" data-hidden="1"'; ?>>
                                <input type="radio" name="country" value="<?php echo esc_attr($c->slug); ?>" <?php checked($country, $c->slug); ?>>
                                <span class="ms-sb-radio__mark"></span>
                                <span class="ms-sb-radio__label"><?php echo esc_html($c->name); ?></span>
                                <span class="ms-sb-radio__count"><?php echo esc_html($c->count); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <?php if ($ccount > 6) : ?>
                        <button type="button" class="ms-sb-more" data-show-more="country">Show <?php echo $ccount - 6; ?> more</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Rating -->
                <div class="ms-sb-section ms-sb-section--toggle" data-section="rating">
                    <h4 class="ms-sb-section__title" data-toggle>
                        Rating
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </h4>
                    <div class="ms-sb-section__body" id="ms-sb-rating">
                        <label class="ms-sb-radio">
                            <input type="radio" name="rating" value="" <?php checked($rating, ''); ?>>
                            <span class="ms-sb-radio__mark"></span>
                            <span class="ms-sb-radio__label">Any Rating</span>
                        </label>
                        <?php foreach ([9, 8, 7, 6] as $r) : ?>
                            <label class="ms-sb-radio">
                                <input type="radio" name="rating" value="<?php echo esc_attr($r); ?>" <?php checked($rating, $r); ?>>
                                <span class="ms-sb-radio__mark"></span>
                                <span class="ms-sb-radio__label">
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="#ffd700"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <?php echo esc_html($r); ?>+
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quality -->
                <div class="ms-sb-section ms-sb-section--toggle" data-section="quality">
                    <h4 class="ms-sb-section__title" data-toggle>
                        Quality
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </h4>
                    <div class="ms-sb-section__body" id="ms-sb-quality">
                        <label class="ms-sb-radio">
                            <input type="radio" name="quality" value="" <?php checked($quality, ''); ?>>
                            <span class="ms-sb-radio__mark"></span>
                            <span class="ms-sb-radio__label">All Quality</span>
                        </label>
                        <?php foreach (['4K', '1080p', '720p', '480p', 'CAM'] as $q) : ?>
                            <label class="ms-sb-radio">
                                <input type="radio" name="quality" value="<?php echo esc_attr($q); ?>" <?php checked($quality, $q); ?>>
                                <span class="ms-sb-radio__mark"></span>
                                <span class="ms-sb-radio__label"><?php echo esc_html($q); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="ms-sb-apply">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Apply Filters
                </button>
            </form>
        </aside>

        <!-- MOBILE FILTER DRAWER -->
        <div class="ms-filter-drawer" id="ms-filter-drawer">
            <div class="ms-filter-drawer__backdrop" id="ms-drawer-close"></div>
            <div class="ms-filter-drawer__panel">
                <div class="ms-filter-drawer__header">
                    <h3>Filters</h3>
                    <button type="button" class="ms-filter-drawer__close" id="ms-drawer-close-btn">×</button>
                </div>
                <div class="ms-filter-drawer__body" id="ms-mobile-filters"></div>
                <div class="ms-filter-drawer__footer">
                    <a href="<?php echo esc_url($base_url); ?>" class="ms-filter-drawer__clear">Clear All</a>
                    <button type="button" class="ms-filter-drawer__apply" id="ms-drawer-apply">Apply</button>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <main class="ms-content">

            <!-- TOPBAR -->
            <div class="ms-topbar">
                <div class="ms-topbar__left">
                    <span class="ms-topbar__count">
                        Showing <strong><?php echo number_format($showing_from); ?>–<?php echo number_format($showing_to); ?></strong> of <strong><?php echo number_format($found_posts); ?></strong> Movies
                    </span>
                </div>
                <div class="ms-topbar__right">
                    <!-- Mobile Filter Toggle -->
                    <button type="button" class="ms-topbar__filter-btn" id="ms-filter-toggle">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        Filters
                        <?php if ($is_filtered) : ?>
                            <span class="ms-topbar__filter-badge"><?php echo count($active_filters); ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- Sort -->
                    <div class="ms-sort">
                        <label>Sort:</label>
                        <select name="sort" id="ms-sort-select" class="ms-topbar__select">
                            <option value="latest" <?php selected($sort, 'latest'); ?>>Latest Release</option>
                            <option value="popular" <?php selected($sort, 'popular'); ?>>Most Popular</option>
                            <option value="top_rated" <?php selected($sort, 'top_rated'); ?>>Highest Rated</option>
                            <option value="oldest" <?php selected($sort, 'oldest'); ?>>Oldest</option>
                            <option value="a_z" <?php selected($sort, 'a_z'); ?>>A - Z</option>
                            <option value="z_a" <?php selected($sort, 'z_a'); ?>>Z - A</option>
                        </select>
                    </div>

                    <!-- View Toggle -->
                    <div class="ms-view-toggle">
                        <button type="button" class="ms-view-toggle__btn is-active" data-view="grid" title="Grid View">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                            </svg>
                        </button>
                        <button type="button" class="ms-view-toggle__btn" data-view="list" title="List View">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                                <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                                <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ACTIVE FILTER CHIPS -->
            <?php if (!empty($active_filters)) : ?>
            <div class="ms-active-chips">
                <?php foreach ($active_filters as $key => $filter) : 
                    $remove_url = add_query_arg($filter['key'], '', $base_url);
                    $remove_url = remove_query_arg($filter['key'], $remove_url);
                ?>
                    <a href="<?php echo esc_url($remove_url); ?>" class="ms-chip">
                        <?php echo esc_html($filter['label']); ?>
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- MOVIE GRID -->
            <div class="ms-grid" id="ms-movie-grid" data-view="grid">
                <?php if ($catalog_q->have_posts()) : ?>
                    <?php while ($catalog_q->have_posts()) : $catalog_q->the_post(); ?>
                        <?php
                        $mid = get_the_ID();
                        $title = get_the_title($mid);
                        $poster = get_the_post_thumbnail_url($mid, 'medium');
                        $year_v = movie_ui_meta($mid, ['year', '_release_year'], '');
                        $rating_v = movie_ui_meta($mid, ['rating', '_rating'], '');
                        $quality_v = movie_ui_meta($mid, ['quality', '_quality'], 'HD');
                        $runtime_v = movie_ui_meta($mid, ['duration', '_runtime'], '');
                        $video_url = movie_ui_meta($mid, ['video_url', '_video_url', 'hls_url', '_hls_url', 'embed_url', '_embed_url'], '');
                        $watch_url = add_query_arg('id', $mid, $watch_base);
                        $detail_url = get_permalink($mid);
                        ?>
                        <article class="ms-card" itemscope itemtype="https://schema.org/Movie">
                            <a href="<?php echo esc_url($detail_url); ?>" class="ms-card__poster">
                                <?php if ($poster) : ?>
                                    <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async" itemprop="image">
                                <?php else : ?>
                                    <div class="ms-card__no-poster">
                                        <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="ms-card__overlay">
                                    <div class="ms-card__actions">
                                        <button type="button" class="ms-card__action ms-card__action--play" 
                                                data-action="play" 
                                                data-id="<?php echo esc_attr($mid); ?>"
                                                data-has-video="<?php echo $video_url ? '1' : '0'; ?>"
                                                aria-label="Play">
                                            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </button>
                                        <button type="button" class="ms-card__action ms-card__action--list" 
                                                data-action="add-list"
                                                data-id="<?php echo esc_attr($mid); ?>"
                                                aria-label="Add to My List">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                                        </button>
                                        <button type="button" class="ms-card__action ms-card__action--info" 
                                                data-action="info"
                                                data-id="<?php echo esc_attr($mid); ?>"
                                                aria-label="More Info">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <?php if ($year_v) : ?>
                                <span class="ms-card__year"><?php echo esc_html(substr($year_v, 0, 4)); ?></span>
                                <?php endif; ?>
                                <?php if ($quality_v) : ?>
                                <span class="ms-card__quality"><?php echo esc_html($quality_v); ?></span>
                                <?php endif; ?>
                            </a>
                            
                            <div class="ms-card__info">
                                <h3 class="ms-card__title">
                                    <a href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($title); ?></a>
                                </h3>
                                <div class="ms-card__meta">
                                    <?php if ($rating_v) : ?>
                                    <span class="ms-card__rating">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="#ffd700"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        <?php echo esc_html($rating_v); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($year_v) : ?>
                                    <span><?php echo esc_html(substr($year_v, 0, 4)); ?></span>
                                    <?php endif; ?>
                                    <?php if ($runtime_v) : ?>
                                    <span><?php echo esc_html($runtime_v); ?> min</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="ms-empty">
                        <div class="ms-empty__icon">
                            <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <h2 class="ms-empty__title">No Movies Found</h2>
                        <p class="ms-empty__desc">
                            <?php if ($is_filtered) : ?>
                                We couldn't find any movies matching your filters. Try adjusting your search criteria.
                            <?php else : ?>
                                There are no movies available yet.
                            <?php endif; ?>
                        </p>
                        <?php if ($is_filtered) : ?>
                        <a href="<?php echo esc_url($base_url); ?>" class="ms-empty__btn">Clear All Filters</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($max_pages > 1) : ?>
            <div class="ms-pagination">
                <?php
                $big = 999999999;
                $paginate_args = [
                    'base'    => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format'  => '?paged=%#%',
                    'current' => $page_num,
                    'total'   => $max_pages,
                    'show_all' => false,
                    'mid_size' => 2,
                    'end_size'  => 1,
                    'prev_text' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>',
                    'next_text' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                ];
                echo '<div class="ms-pagination__prev">' . get_previous_posts_link($paginate_args['prev_text'], $max_pages) . '</div>';
                for ($i = 1; $i <= $max_pages; $i++) {
                    if ($i === 1 || $i === $max_pages || ($i >= $page_num - 1 && $i <= $page_num + 1)) {
                        $active = $i === $page_num ? ' is-active' : '';
                        echo '<a href="' . esc_url(get_pagenum_link($i)) . '" class="ms-pagination__btn' . $active . '">' . $i . '</a>';
                    } elseif ($i === $page_num - 2 || $i === $page_num + 2) {
                        echo '<span class="ms-pagination__dots">...</span>';
                    }
                }
                echo '<div class="ms-pagination__next">' . get_next_posts_link($paginate_args['next_text'], $max_pages) . '</div>';
                ?>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- ============================================================ -->
    <!-- FOOTER FEATURE STRIP -->
    <!-- ============================================================ -->
    <section class="ms-features">
        <div class="ms-features__grid">
            <div class="ms-feature">
                <div class="ms-feature__icon">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                </div>
                <h4 class="ms-feature__title">Thousands of Movies</h4>
                <p class="ms-feature__desc">Access an extensive library of movies across all genres</p>
            </div>
            <div class="ms-feature">
                <div class="ms-feature__icon">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/><path d="M9 8l7 4-7 4V8z"/></svg>
                </div>
                <h4 class="ms-feature__title">4K Ultra HD</h4>
                <p class="ms-feature__desc">Crystal clear video quality with HDR support</p>
            </div>
            <div class="ms-feature">
                <div class="ms-feature__icon">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg>
                </div>
                <h4 class="ms-feature__title">Watch Anywhere</h4>
                <p class="ms-feature__desc">Enjoy on TV, tablet, phone or laptop anytime</p>
            </div>
            <div class="ms-feature">
                <div class="ms-feature__icon">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                </div>
                <h4 class="ms-feature__title">No Ads</h4>
                <p class="ms-feature__desc">Experience uninterrupted viewing without advertisements</p>
            </div>
        </div>
    </section>

</div>

<!-- VIDEO UNAVAILABLE MODAL -->
<div class="ms-modal" id="ms-no-video-modal">
    <div class="ms-modal__backdrop"></div>
    <div class="ms-modal__dialog">
        <div class="ms-modal__icon">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <h3>Video Unavailable</h3>
        <p>This title has no video source configured yet. Check back later!</p>
        <button type="button" class="ms-modal__close" id="ms-modal-close">Got it</button>
    </div>
</div>

<?php wp_reset_postdata(); wp_footer(); ?>
<script>
var msMoviesData = {
    ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo wp_create_nonce('ms_movies_nonce'); ?>'
};
</script>
<script src="<?php echo esc_url(get_theme_file_uri('assets/js/ms-movies.js')); ?>"></script>
</body>
</html>
