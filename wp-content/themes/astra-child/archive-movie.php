<?php
/**
 * Browse Movies — Premium OTT Interface
 * Full functional Movies Archive Page
 */
get_header();
get_template_part('template-parts/streaming/header');

// =================================================================
// DATA PREPARATION
// =================================================================

// Get all taxonomies
$all_genres   = get_terms(['taxonomy' => 'genre',   'hide_empty' => true]);
$all_countries = get_terms(['taxonomy' => 'country', 'hide_empty' => true]);
$all_types     = get_terms(['taxonomy' => 'movie_type', 'hide_empty' => true]);

// Get available years from movies
global $wpdb;
$year_results = $wpdb->get_results(
    "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
     WHERE meta_key = '_release_year' AND meta_value != '' 
     ORDER BY meta_value DESC LIMIT 20"
);
$available_years = array_column($year_results, 'meta_value');

// Get movie statistics
$total_movies = wp_count_posts('movie')->publish;
$latest_year  = !empty($available_years) ? max($available_years) : date('Y');
$highest_rating = $wpdb->get_var(
    "SELECT MAX(CAST(meta_value AS DECIMAL(3,1))) FROM {$wpdb->postmeta} 
     WHERE meta_key = '_rating' AND meta_value != ''"
);

// =================================================================
// FILTER PARAMETERS
// =================================================================
$current_genre    = isset($_GET['genre']) ? sanitize_text_field($_GET['genre']) : '';
$current_country  = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
$current_year     = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : '';
$current_type     = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$current_rating   = isset($_GET['rating']) ? floatval($_GET['rating']) : 0;
$current_quality  = isset($_GET['quality']) ? sanitize_text_field($_GET['quality']) : '';
$current_sort     = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'latest';
$current_search   = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$current_page     = max(1, get_query_var('paged') ?: 1);

// Build active filters array
$active_filters = [];
if ($current_genre)   $active_filters['genre']   = $current_genre;
if ($current_country) $active_filters['country'] = $current_country;
if ($current_year)    $active_filters['year']     = $current_year;
if ($current_type)    $active_filters['type']     = $current_type;
if ($current_rating)  $active_filters['rating']   = $current_rating;
if ($current_quality) $active_filters['quality'] = $current_quality;

$is_filtered = !empty($active_filters) || !empty($current_search);
$archive_url = trailingslashit(home_url('movies'));

// =================================================================
// WP_QUERY BUILDER
// =================================================================
function mu_build_movies_query($args = []) {
    $defaults = [
        'post_type'      => 'movie',
        'posts_per_page'  => 24,
        'paged'          => 1,
        'post_status'     => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    // Build meta query
    $meta_query = [];
    
    // Year filter
    if (!empty($_GET['year'])) {
        $year = sanitize_text_field($_GET['year']);
        if ($year === 'before_2021') {
            $meta_query[] = [
                'key'     => '_release_year',
                'value'   => '2021',
                'compare' => '<',
                'type'    => 'NUMERIC'
            ];
        } elseif (is_numeric($year)) {
            $meta_query[] = [
                'key'     => '_release_year',
                'value'   => $year,
                'compare' => '=',
                'type'    => 'NUMERIC'
            ];
        }
    }
    
    // Rating filter
    if (!empty($_GET['rating']) && is_numeric($_GET['rating'])) {
        $rating = floatval($_GET['rating']);
        $meta_query[] = [
            'key'     => '_rating',
            'value'   => $rating,
            'compare' => '>=',
            'type'    => 'DECIMAL(3,1)'
        ];
    }
    
    // Quality filter
    if (!empty($_GET['quality'])) {
        $meta_query[] = [
            'key'   => '_quality',
            'value' => sanitize_text_field($_GET['quality']),
        ];
    }
    
    // Add meta query to args
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    
    // Build taxonomy query
    $tax_query = [];
    
    if (!empty($_GET['genre'])) {
        $tax_query[] = [
            'taxonomy' => 'genre',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_GET['genre']),
        ];
    }
    
    if (!empty($_GET['country'])) {
        $tax_query[] = [
            'taxonomy' => 'country',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_GET['country']),
        ];
    }
    
    if (!empty($_GET['type'])) {
        $tax_query[] = [
            'taxonomy' => 'movie_type',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_GET['type']),
        ];
    }
    
    if (!empty($tax_query)) {
        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_query;
    }
    
    // Search
    if (!empty($_GET['s'])) {
        $args['s'] = sanitize_text_field($_GET['s']);
    }
    
    // Sorting
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
        default: // latest
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
    }
    
    return new WP_Query($args);
}

// Get movies
$movies_query = mu_build_movies_query(['paged' => $current_page]);
?>

<div class="mu-page mu-archive-movies">

    <?php // =================================================================
          // HERO SECTION
          // ================================================================= ?>
    <section class="mu-movies-hero">
        <div class="mu-movies-hero__bg"></div>
        <div class="mu-movies-hero__content">
            <h1 class="mu-movies-hero__title">Movies</h1>
            <p class="mu-movies-hero__subtitle">Explore thousands of movies from all genres.</p>
            
            <div class="mu-movies-hero__stats">
                <div class="mu-stat">
                    <span class="mu-stat__icon">🎬</span>
                    <div class="mu-stat__info">
                        <strong><?php echo number_format($total_movies); ?></strong>
                        <span>Movies</span>
                    </div>
                </div>
                <div class="mu-stat">
                    <span class="mu-stat__icon" style="color: #46d369;">4K</span>
                    <div class="mu-stat__info">
                        <strong>Ultra HD</strong>
                        <span>Quality</span>
                    </div>
                </div>
                <div class="mu-stat">
                    <span class="mu-stat__icon">📅</span>
                    <div class="mu-stat__info">
                        <strong><?php echo esc_html($latest_year ?: date('Y')); ?></strong>
                        <span>Latest</span>
                    </div>
                </div>
                <?php if ($highest_rating): ?>
                <div class="mu-stat">
                    <span class="mu-stat__icon" style="color: #ffd700;">★</span>
                    <div class="mu-stat__info">
                        <strong><?php echo number_format($highest_rating, 1); ?></strong>
                        <span>Top Rating</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php // =================================================================
          // MAIN LAYOUT
          // ================================================================= ?>
    <div class="mu-movies-layout">
        
        <?php // =============================================================
              // SIDEBAR FILTERS
              // ============================================================= ?>
        <aside class="mu-movies-sidebar" id="mu-movies-sidebar">
            <form method="GET" action="<?php echo esc_url($archive_url); ?>" id="mu-sidebar-form">
                
                <div class="mu-sb-header">
                    <h3>Filters</h3>
                    <?php if ($is_filtered): ?>
                    <a href="<?php echo esc_url($archive_url); ?>" class="mu-sb-reset">Reset All</a>
                    <?php endif; ?>
                </div>
                
                <?php // Hidden fields for existing filters ?>
                <?php if ($current_page > 1): ?>
                <input type="hidden" name="paged" value="<?php echo esc_attr($current_page); ?>">
                <?php endif; ?>
                
                <?php // Search ?>
                <div class="mu-sb-search">
                    <span class="mu-ico-search"></span>
                    <input type="text" name="s" placeholder="Search movies, actors..." 
                           value="<?php echo esc_attr($current_search); ?>" id="mu-sidebar-search">
                </div>
                
                <?php // Active Filters Chips ?>
                <?php if ($is_filtered): ?>
                <div class="mu-sb-active-filters" id="mu-active-filters">
                    <?php foreach ($active_filters as $key => $value): ?>
                        <?php 
                        $remove_url = remove_query_arg($key);
                        $label = ucfirst($key);
                        if ($key === 'genre' && $current_genre) {
                            $term = get_term_by('slug', $current_genre, 'genre');
                            $label = $term ? $term->name : $current_genre;
                        } elseif ($key === 'country' && $current_country) {
                            $term = get_term_by('slug', $current_country, 'country');
                            $label = $term ? $term->name : $current_country;
                        } elseif ($key === 'year' && $current_year) {
                            $label = $current_year === 'before_2021' ? 'Before 2021' : $current_year;
                        } elseif ($key === 'rating' && $current_rating) {
                            $label = $current_rating . '+ Rating';
                        }
                        ?>
                        <a href="<?php echo esc_url($remove_url); ?>" class="mu-filter-chip">
                            <?php echo esc_html($label); ?>
                            <span class="mu-filter-chip__remove">×</span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php // Genres ?>
                <?php if (!empty($all_genres)): ?>
                <div class="mu-sb-group">
                    <h4 class="mu-sb-title" data-mu-toggle="genre">Genre</h4>
                    <div class="mu-sb-list" id="mu-list-genre">
                        <?php 
                        $genre_count = 0;
                        foreach ($all_genres as $g): 
                            $genre_count++;
                            $is_hidden = $genre_count > 8 ? 'style="display:none;" data-mu-hidden="true"' : '';
                        ?>
                            <label class="mu-sb-checkbox" <?php echo $is_hidden; ?>>
                                <input type="radio" name="genre" value="<?php echo esc_attr($g->slug); ?>" 
                                       <?php checked($current_genre, $g->slug); ?>>
                                <span class="mu-sb-check"></span>
                                <span class="mu-sb-label"><?php echo esc_html($g->name); ?></span>
                                <span class="mu-sb-count"><?php echo esc_html($g->count); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <?php if ($genre_count > 8): ?>
                            <button type="button" class="mu-sb-more" data-mu-show-more="genre">
                                Show <?php echo $genre_count - 8; ?> more ▼
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php // Year ?>
                <div class="mu-sb-group">
                    <h4 class="mu-sb-title" data-mu-toggle="year">Release Year</h4>
                    <div class="mu-sb-list" id="mu-list-year">
                        <?php
                        $quick_years = array_slice($available_years, 0, 5);
                        $quick_years[] = 'before_2021';
                        foreach ($quick_years as $y): 
                            $label = $y === 'before_2021' ? 'Before 2021' : $y;
                            $value = $y;
                        ?>
                            <label class="mu-sb-checkbox">
                                <input type="radio" name="year" value="<?php echo esc_attr($value); ?>" 
                                       <?php checked($current_year, $value); ?>>
                                <span class="mu-sb-check"></span>
                                <span class="mu-sb-label"><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php // Country ?>
                <?php if (!empty($all_countries)): ?>
                <div class="mu-sb-group">
                    <h4 class="mu-sb-title" data-mu-toggle="country">Country</h4>
                    <div class="mu-sb-list" id="mu-list-country">
                        <?php 
                        $country_count = 0;
                        foreach ($all_countries as $c): 
                            $country_count++;
                            $is_hidden = $country_count > 6 ? 'style="display:none;" data-mu-hidden="true"' : '';
                        ?>
                            <label class="mu-sb-checkbox" <?php echo $is_hidden; ?>>
                                <input type="radio" name="country" value="<?php echo esc_attr($c->slug); ?>" 
                                       <?php checked($current_country, $c->slug); ?>>
                                <span class="mu-sb-check"></span>
                                <span class="mu-sb-label"><?php echo esc_html($c->name); ?></span>
                                <span class="mu-sb-count"><?php echo esc_html($c->count); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <?php if ($country_count > 6): ?>
                            <button type="button" class="mu-sb-more" data-mu-show-more="country">
                                Show <?php echo $country_count - 6; ?> more ▼
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php // Rating ?>
                <div class="mu-sb-group">
                    <h4 class="mu-sb-title" data-mu-toggle="rating">Rating</h4>
                    <div class="mu-sb-list" id="mu-list-rating">
                        <?php foreach ([9, 8, 7, 6] as $r): ?>
                            <label class="mu-sb-checkbox">
                                <input type="radio" name="rating" value="<?php echo esc_attr($r); ?>" 
                                       <?php checked($current_rating, $r); ?>>
                                <span class="mu-sb-check"></span>
                                <span class="mu-sb-label">
                                    <?php for ($i = 0; $i < floor($r / 2); $i++): ?>★<?php endfor; ?>
                                    <?php echo esc_html($r); ?>+
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php // Quality ?>
                <div class="mu-sb-group">
                    <h4 class="mu-sb-title" data-mu-toggle="quality">Quality</h4>
                    <div class="mu-sb-list" id="mu-list-quality">
                        <?php foreach (['4K', 'Full HD', 'HD', 'CAM'] as $q): ?>
                            <label class="mu-sb-checkbox">
                                <input type="radio" name="quality" value="<?php echo esc_attr($q); ?>" 
                                       <?php checked($current_quality, $q); ?>>
                                <span class="mu-sb-check"></span>
                                <span class="mu-sb-label"><?php echo esc_html($q); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php // Type ?>
                <?php if (!empty($all_types)): ?>
                <div class="mu-sb-group">
                    <h4 class="mu-sb-title" data-mu-toggle="type">Type</h4>
                    <div class="mu-sb-list" id="mu-list-type">
                        <?php foreach ($all_types as $t): ?>
                            <label class="mu-sb-checkbox">
                                <input type="radio" name="type" value="<?php echo esc_attr($t->slug); ?>" 
                                       <?php checked($current_type, $t->slug); ?>>
                                <span class="mu-sb-check"></span>
                                <span class="mu-sb-label"><?php echo esc_html($t->name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </form>
        </aside>
        
        <?php // =============================================================
              // MOBILE FILTER DRAWER
              // ============================================================= ?>
        <div class="mu-filter-drawer" id="mu-filter-drawer">
            <div class="mu-filter-drawer__backdrop" id="mu-filter-close"></div>
            <div class="mu-filter-drawer__panel">
                <div class="mu-filter-drawer__header">
                    <h3 class="mu-filter-drawer__title">Filters</h3>
                    <button type="button" class="mu-filter-drawer__close" id="mu-filter-drawer-close">×</button>
                </div>
                <div id="mu-mobile-filters"></div>
                <button type="button" class="mu-filter-drawer__apply" id="mu-filter-apply">
                    Apply Filters
                </button>
            </div>
        </div>

        <?php // =============================================================
              // MAIN CONTENT
              // ============================================================= ?>
        <main class="mu-movies-main">
            
            <?php // Topbar Controls ?>
            <div class="mu-movies-topbar">
                <span class="mu-topbar-count">
                    <strong><?php echo number_format($movies_query->found_posts); ?></strong> 
                    <?php echo _n('Movie', 'Movies', $movies_query->found_posts, 'astra-child'); ?> Found
                </span>
                
                <?php // Mobile Filter Toggle ?>
                <button type="button" class="mu-filter-toggle-btn" id="mu-filter-toggle">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filters
                    <?php if ($is_filtered): ?>
                        <span style="background:var(--mu-accent);color:#fff;padding:2px 6px;border-radius:10px;font-size:11px;">
                            <?php echo count($active_filters); ?>
                        </span>
                    <?php endif; ?>
                </button>
                
                <select name="sort" class="mu-topbar-select" id="mu-sort-select">
                    <option value="latest" <?php selected($current_sort, 'latest'); ?>>Latest</option>
                    <option value="popular" <?php selected($current_sort, 'popular'); ?>>Most Popular</option>
                    <option value="top_rated" <?php selected($current_sort, 'top_rated'); ?>>Highest Rated</option>
                    <option value="oldest" <?php selected($current_sort, 'oldest'); ?>>Oldest</option>
                    <option value="a_z" <?php selected($current_sort, 'a_z'); ?>>A - Z</option>
                    <option value="z_a" <?php selected($current_sort, 'z_a'); ?>>Z - A</option>
                </select>
                
                <div class="mu-view-toggle">
                    <button type="button" class="is-active" data-view="grid" title="Grid View">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </button>
                    <button type="button" data-view="list" title="List View">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <?php // Content Area ?>
            <div class="mu-movies-content-area" id="mu-movies-content">
                
                <?php if ($movies_query->have_posts()): ?>
                    
                    <div class="mu-movies-grid" id="mu-movies-grid" data-view="grid">
                        <?php while ($movies_query->have_posts()): $movies_query->the_post(); ?>
                            <?php 
                            $post_id = get_the_ID();
                            $title = get_the_title($post_id);
                            $year = movie_ui_meta($post_id, ['year', '_release_year'], '');
                            $rating = movie_ui_meta($post_id, ['rating', '_rating'], '');
                            $quality = movie_ui_meta($post_id, ['quality', '_quality'], 'HD');
                            $trailer = movie_ui_meta($post_id, ['trailer_url', '_trailer_url'], '');
                            $video_url = movie_ui_meta($post_id, ['video_url', '_video_url'], '');
                            $poster = get_the_post_thumbnail_url($post_id, 'medium');
                            $url = get_permalink($post_id);
                            $ptype = get_post_type($post_id);
                            
                            $play_action = 'unavailable';
                            if ($trailer) {
                                $play_action = 'trailer:' . esc_attr($trailer);
                            } elseif ($video_url) {
                                $play_action = 'watch:' . esc_url($url);
                            }
                            ?>
                            <article class="mu-grid-card"
                                     data-id="<?php echo esc_attr($post_id); ?>"
                                     data-url="<?php echo esc_url($url); ?>"
                                     data-title="<?php echo esc_attr($title); ?>"
                                     data-trailer="<?php echo esc_attr($trailer); ?>"
                                     data-play-action="<?php echo esc_attr($play_action); ?>"
                                     itemscope itemtype="https://schema.org/Movie">
                                
                                <a href="<?php echo esc_url($url); ?>" class="mu-grid-card__poster">
                                    <?php if ($poster): ?>
                                        <img src="<?php echo esc_url($poster); ?>" 
                                             alt="<?php echo esc_attr($title); ?>"
                                             loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;background:var(--mu-bg-2);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.3);">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mu-grid-card__overlay">
                                        <button type="button" class="mu-grid-card__play-btn" data-mu-card-play>
                                            <svg viewBox="0 0 24 24" fill="#111"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        </button>
                                    </div>
                                    
                                    <div class="mu-grid-card__badges">
                                        <span class="mu-badge mu-badge--type">
                                            <?php echo $ptype === 'tv_show' ? 'TV' : 'Movie'; ?>
                                        </span>
                                        <?php if ($quality): ?>
                                        <span class="mu-badge mu-badge--quality"><?php echo esc_html($quality); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($rating): ?>
                                    <span class="mu-badge mu-badge--rating" style="position:absolute;bottom:8px;left:8px;">
                                        ★ <?php echo esc_html($rating); ?>
                                    </span>
                                    <?php endif; ?>
                                </a>
                                
                                <div class="mu-grid-card__info">
                                    <h3 class="mu-grid-card__title"><?php echo esc_html($title); ?></h3>
                                    <div class="mu-grid-card__meta">
                                        <?php if ($year): ?>
                                        <span class="mu-grid-card__meta-item"><?php echo esc_html($year); ?></span>
                                        <?php endif; ?>
                                        <button type="button" class="mu-grid-card__fav-btn" data-mu-fav-toggle data-id="<?php echo esc_attr($post_id); ?>" title="Add to My List">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php // Pagination ?>
                    <?php if ($movies_query->max_num_pages > 1): ?>
                    <div class="mu-movies-pagination">
                        <?php
                        $big = 999999999;
                        $pagination_args = [
                            'base'    => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format'  => '?paged=%#%',
                            'current' => $current_page,
                            'total'   => $movies_query->max_num_pages,
                            'show_all' => false,
                            'mid_size' => 2,
                            'end_size'  => 1,
                            'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>',
                            'next_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                        ];
                        
                        echo '<div class="mu-pagination-btn mu-pagination-btn--prev">' . get_previous_posts_link($pagination_args['prev_text']) . '</div>';
                        
                        for ($i = 1; $i <= $movies_query->max_num_pages; $i++) {
                            $active = $i === $current_page ? ' is-current' : '';
                            if (
                                $i === 1 || 
                                $i === $movies_query->max_num_pages || 
                                ($i >= $current_page - 1 && $i <= $current_page + 1)
                            ) {
                                echo '<a href="' . esc_url(get_pagenum_link($i)) . '" class="mu-pagination-btn' . $active . '">' . $i . '</a>';
                            } elseif ($i === $current_page - 2 || $i === $current_page + 2) {
                                echo '<span class="mu-pagination-btn">...</span>';
                            }
                        }
                        
                        echo '<div class="mu-pagination-btn mu-pagination-btn--next">' . get_next_posts_link($pagination_args['next_text'], $movies_query->max_num_pages) . '</div>';
                        ?>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    
                    <?php // Empty State ?>
                    <div class="mu-movies-empty">
                        <div class="mu-movies-empty__icon">🎬</div>
                        <h2 class="mu-movies-empty__title">No movies found</h2>
                        <p class="mu-movies-empty__desc">
                            <?php if ($is_filtered): ?>
                                We couldn't find any movies matching your filters. Try adjusting your search criteria.
                            <?php else: ?>
                                There are no movies available yet. Import some movies to get started.
                            <?php endif; ?>
                        </p>
                        <div class="mu-movies-empty__actions">
                            <?php if ($is_filtered): ?>
                                <a href="<?php echo esc_url($archive_url); ?>" class="mu-empty-btn mu-empty-btn--primary">
                                    Reset Filters
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(home_url('/trending')); ?>" class="mu-empty-btn mu-empty-btn--secondary">
                                Explore Trending
                            </a>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
            </div>
        </main>
        
    </div>

    <?php // =================================================================
          // FOOTER FEATURE STRIP
          // ================================================================= ?>
    <footer class="mu-movies-footer">
        <div class="mu-movies-footer__inner">
            <div class="mu-movies-footer__grid">
                <div class="mu-movies-footer__item">
                    <div class="mu-movies-footer__icon">🎬</div>
                    <h4 class="mu-movies-footer__title">Thousands of Movies</h4>
                    <p class="mu-movies-footer__desc">Access an extensive library of movies across all genres.</p>
                </div>
                <div class="mu-movies-footer__item">
                    <div class="mu-movies-footer__icon">📺</div>
                    <h4 class="mu-movies-footer__title">High Quality</h4>
                    <p class="mu-movies-footer__desc">Enjoy 4K Ultra HD streaming with crystal clear quality.</p>
                </div>
                <div class="mu-movies-footer__item">
                    <div class="mu-movies-footer__icon">📱</div>
                    <h4 class="mu-movies-footer__title">Watch Everywhere</h4>
                    <p class="mu-movies-footer__desc">Stream on any device, anytime, anywhere.</p>
                </div>
                <div class="mu-movies-footer__item">
                    <div class="mu-movies-footer__icon">🚫</div>
                    <h4 class="mu-movies-footer__title">No Ads</h4>
                    <p class="mu-movies-footer__desc">Experience uninterrupted viewing without advertisements.</p>
                </div>
            </div>
        </div>
    </footer>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter toggle (collapse/expand)
    document.querySelectorAll('.mu-sb-title[data-mu-toggle]').forEach(function(title) {
        title.addEventListener('click', function() {
            var target = this.getAttribute('data-mu-toggle');
            var list = document.getElementById('mu-list-' + target);
            if (list) {
                list.classList.toggle('is-collapsed');
                this.classList.toggle('is-collapsed');
            }
        });
    });
    
    // Show more toggle
    document.querySelectorAll('.mu-sb-more[data-mu-show-more]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = this.getAttribute('data-mu-show-more');
            var list = document.getElementById('mu-list-' + target);
            if (list) {
                list.querySelectorAll('[data-mu-hidden]').forEach(function(item) {
                    item.style.display = '';
                    item.removeAttribute('data-mu-hidden');
                });
                this.style.display = 'none';
            }
        });
    });
    
    // Auto-submit sidebar form on radio change
    document.querySelectorAll('#mu-sidebar-form input[type="radio"]').forEach(function(input) {
        input.addEventListener('change', function() {
            document.getElementById('mu-sidebar-form').submit();
        });
    });
    
    // Sort select change
    document.getElementById('mu-sort-select').addEventListener('change', function() {
        var form = document.getElementById('mu-sidebar-form');
        var sortInput = form.querySelector('input[name="sort"]');
        if (!sortInput) {
            sortInput = document.createElement('input');
            sortInput.type = 'hidden';
            sortInput.name = 'sort';
            form.appendChild(sortInput);
        }
        sortInput.value = this.value;
        form.submit();
    });
    
    // Grid/List view toggle
    document.querySelectorAll('.mu-view-toggle button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var view = this.getAttribute('data-view');
            document.querySelectorAll('.mu-view-toggle button').forEach(function(b) {
                b.classList.remove('is-active');
            });
            this.classList.add('is-active');
            
            var grid = document.getElementById('mu-movies-grid');
            if (grid) {
                grid.setAttribute('data-view', view);
            }
        });
    });
    
    // Mobile filter drawer
    var filterToggle = document.getElementById('mu-filter-toggle');
    var filterDrawer = document.getElementById('mu-filter-drawer');
    var filterClose = document.getElementById('mu-filter-close');
    var filterDrawerClose = document.getElementById('mu-filter-drawer-close');
    var filterApply = document.getElementById('mu-filter-apply');
    var mobileFilters = document.getElementById('mu-mobile-filters');
    var sidebarForm = document.getElementById('mu-sidebar-form');
    
    if (filterToggle && filterDrawer) {
        // Clone sidebar content to mobile drawer
        if (mobileFilters && sidebarForm) {
            mobileFilters.innerHTML = sidebarForm.innerHTML;
        }
        
        filterToggle.addEventListener('click', function() {
            filterDrawer.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        });
        
        function closeDrawer() {
            filterDrawer.classList.remove('is-open');
            document.body.style.overflow = '';
        }
        
        if (filterClose) filterClose.addEventListener('click', closeDrawer);
        if (filterDrawerClose) filterDrawerClose.addEventListener('click', closeDrawer);
        
        if (filterApply) {
            filterApply.addEventListener('click', function() {
                // Submit the mobile form
                var mobileForm = mobileFilters.querySelector('form') || mobileFilters.querySelector('#mu-sidebar-form');
                if (mobileForm) {
                    mobileForm.submit();
                }
                closeDrawer();
            });
        }
        
        // Handle checkbox changes in mobile
        mobileFilters.querySelectorAll('input[type="radio"]').forEach(function(input) {
            input.addEventListener('change', function() {
                // Visual feedback - update checked state
            });
        });
    }
    
    // Card interactions
    document.querySelectorAll('.mu-grid-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            // Don't navigate if clicking play button or favorite
            if (e.target.closest('[data-mu-card-play]') || e.target.closest('[data-mu-fav-toggle]')) {
                return;
            }
        });
    });
});
</script>

<?php 
wp_reset_postdata();
get_footer(); 
