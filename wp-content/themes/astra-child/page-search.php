<?php
/**
 * Template Name: Search (Premium OTT)
 * Matches reference image exactly
 */
get_header();

// Remove default header and admin bar
remove_all_actions('astra_header');
show_admin_bar(false);

// Get query from URL
$initial_query = isset($_GET['q']) ? sanitize_text_field(wp_unslash((string) $_GET['q'])) : '';

// Get all genres for filters
$all_genres = get_terms([
    'taxonomy' => 'genre',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC'
]);

// Get popular movies for trending
$trending_movies = get_posts([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 12,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
]);

// AJAX nonce
$ajax_nonce = wp_create_nonce('movie_ui_nonce');
$ajax_url = admin_url('admin-ajax.php');
$watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : home_url('/watch');

// Count movies and TV shows
$movie_count = wp_count_posts('movie')->publish;
$tv_count = wp_count_posts('tv_show')->publish;
$total_count = $movie_count + $tv_count;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Search', 'astra-child'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/ms-search.css')); ?>">
    <style>
        /* Hide WordPress Admin Bar completely */
        html { margin-top: 0 !important; }
        body { margin-top: 0 !important; }
        #wpadminbar { display: none !important; }
        * { box-sizing: border-box; }
    </style>
</head>
<body class="mu-search-page-body">
<?php 
wp_body_open(); 

// Include the standard header
get_template_part('template-parts/streaming/header');
?>

<!-- ============================================================ -->
<!-- MAIN CONTAINER -->
<!-- ============================================================ -->
<div class="mu-search-container">
    
    <!-- ============================================================ -->
    <!-- SIDEBAR (Left) -->
    <!-- ============================================================ -->
    <aside class="mu-search-sidebar">
        
        <!-- Suggestions -->
        <section class="mu-sidebar-section mu-sidebar-suggestions" id="mu-suggestions-section">
            <h3 class="mu-sidebar-title">SUGGESTIONS</h3>
            <div class="mu-suggestions-list" id="mu-suggestions-list"></div>
        </section>
        
        <!-- Trending Searches -->
        <section class="mu-sidebar-section">
            <h3 class="mu-sidebar-title">TRENDING SEARCHES</h3>
            <div class="mu-trending-chips">
                <?php if (!empty($trending_movies)) : ?>
                    <?php foreach (array_slice($trending_movies, 0, 10) as $movie) : ?>
                        <button type="button" class="mu-trending-chip" data-term="<?php echo esc_attr($movie->post_title); ?>">
                            <?php esc_html_e($movie->post_title); ?>
                        </button>
                    <?php endforeach; ?>
                <?php else : ?>
                    <button type="button" class="mu-trending-chip" data-term="Action">Action</button>
                    <button type="button" class="mu-trending-chip" data-term="Comedy">Comedy</button>
                    <button type="button" class="mu-trending-chip" data-term="Drama">Drama</button>
                    <button type="button" class="mu-trending-chip" data-term="Sci-Fi">Sci-Fi</button>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Recent Searches -->
        <section class="mu-sidebar-section">
            <h3 class="mu-sidebar-title">RECENT SEARCHES</h3>
            <div class="mu-recent-list" id="mu-recent-list"></div>
            <button type="button" class="mu-clear-all-btn" id="mu-clear-all-recent" style="display: none;">
                Clear All
            </button>
        </section>
        
        <!-- Filters -->
        <section class="mu-sidebar-section">
            <div class="mu-filter-header" id="mu-filter-toggle">
                <h3 class="mu-sidebar-title" style="margin-bottom: 0;">FILTERS</h3>
                <svg class="mu-filter-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
            
            <div class="mu-filter-body" id="mu-filter-body">
                <!-- Type Filter -->
                <div class="mu-filter-group">
                    <div class="mu-filter-label">Type</div>
                    <div class="mu-filter-options" id="mu-type-filter">
                        <label class="mu-filter-option is-active">
                            <input type="radio" name="type" value="all" checked>
                            <span class="mu-filter-radio"></span>
                            <span class="mu-filter-text">All Results</span>
                            <span class="mu-filter-count">(<?php echo esc_html($total_count); ?>)</span>
                        </label>
                        <label class="mu-filter-option">
                            <input type="radio" name="type" value="movies">
                            <span class="mu-filter-radio"></span>
                            <span class="mu-filter-text">Movies</span>
                            <span class="mu-filter-count">(<?php echo esc_html($movie_count); ?>)</span>
                        </label>
                        <label class="mu-filter-option">
                            <input type="radio" name="type" value="tv">
                            <span class="mu-filter-radio"></span>
                            <span class="mu-filter-text">TV Shows</span>
                            <span class="mu-filter-count">(<?php echo esc_html($tv_count); ?>)</span>
                        </label>
                    </div>
                </div>
                
                <!-- Genre Filter -->
                <div class="mu-filter-group">
                    <div class="mu-filter-label">Genre</div>
                    <div class="mu-genre-checks" id="mu-genre-filter">
                        <?php if (!empty($all_genres) && !is_wp_error($all_genres)) : ?>
                            <?php foreach (array_slice($all_genres, 0, 8) as $genre) : ?>
                                <label class="mu-genre-check">
                                    <input type="checkbox" name="genre" value="<?php echo esc_attr($genre->slug); ?>">
                                    <span class="mu-genre-box"></span>
                                    <span class="mu-genre-text"><?php esc_html_e($genre->name); ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <label class="mu-genre-check">
                                <input type="checkbox" name="genre" value="action">
                                <span class="mu-genre-box"></span>
                                <span class="mu-genre-text">Action</span>
                            </label>
                            <label class="mu-genre-check">
                                <input type="checkbox" name="genre" value="comedy">
                                <span class="mu-genre-box"></span>
                                <span class="mu-genre-text">Comedy</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Year Range -->
                <div class="mu-filter-group">
                    <div class="mu-filter-label">Release Year</div>
                    <div class="mu-year-range">
                        <input type="number" class="mu-year-input" id="mu-year-from" placeholder="From" min="1900" max="2030" value="1950">
                        <span class="mu-year-sep">-</span>
                        <input type="number" class="mu-year-input" id="mu-year-to" placeholder="To" min="1900" max="2030" value="2026">
                    </div>
                </div>
                
                <!-- Rating -->
                <div class="mu-filter-group">
                    <div class="mu-filter-label">Rating</div>
                    <div class="mu-rating-options" id="mu-rating-filter">
                        <button type="button" class="mu-rating-btn" data-rating="9">
                            <div class="mu-rating-stars">
                                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </div>
                            <span class="mu-rating-text">9 & Up</span>
                        </button>
                        <button type="button" class="mu-rating-btn" data-rating="7">
                            <div class="mu-rating-stars">
                                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </div>
                            <span class="mu-rating-text">7 & Up</span>
                        </button>
                        <button type="button" class="mu-rating-btn" data-rating="5">
                            <div class="mu-rating-stars">
                                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </div>
                            <span class="mu-rating-text">5 & Up</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </aside>
    
    <!-- ============================================================ -->
    <!-- MAIN CONTENT (Right) -->
    <!-- ============================================================ -->
    <main class="mu-search-main">
        
        <!-- Search Header -->
        <div class="mu-search-main-header">
            <h1 class="mu-search-main-title" id="mu-search-title">Search</h1>
            
            <!-- Large Search Input -->
            <div class="mu-main-search-wrap">
                <div class="mu-main-search-input-wrap">
                    <svg class="mu-main-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="search" 
                           id="mu-main-search-input" 
                           class="mu-main-search-input" 
                           placeholder="Search movies, TV, actors..."
                           value="<?php echo esc_attr($initial_query); ?>"
                           autocomplete="off">
                    <button type="button" class="mu-main-search-clear" id="mu-main-search-clear" style="<?php echo $initial_query ? '' : 'display:none;'; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Results Area -->
        <div class="mu-search-results-area" id="mu-search-results-area">
            
            <!-- Results Header -->
            <div class="mu-results-info" id="mu-results-info" style="display: none;">
                <span class="mu-results-for">Results for "<span id="mu-query-text"></span>"</span>
            </div>
            
            <!-- Initial State -->
            <div class="mu-initial-state" id="mu-initial-state">
                <svg class="mu-initial-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <p class="mu-initial-text">Search for movies, TV shows, and people</p>
            </div>
            
            <!-- Results Tabs -->
            <div class="mu-results-tabs" id="mu-results-tabs" style="display: none;">
                <button type="button" class="mu-results-tab is-active" data-tab="all">All Results</button>
                <button type="button" class="mu-results-tab" data-tab="movies">Movies</button>
                <button type="button" class="mu-results-tab" data-tab="tv">TV Shows</button>
                <button type="button" class="mu-results-tab" data-tab="people">People</button>
            </div>
            
            <!-- Results Container -->
            <div class="mu-results-container" id="mu-results-container" style="display: none;">
                
                <!-- Content Section -->
                <section class="mu-results-section" id="mu-content-section">
                    <div class="mu-results-grid" id="mu-results-grid"></div>
                </section>
                
                <!-- People Section -->
                <section class="mu-results-section" id="mu-people-section" style="display: none;">
                    <h3 class="mu-section-title">People</h3>
                    <div class="mu-people-grid" id="mu-people-grid"></div>
                </section>
            </div>
            
            <!-- Empty State -->
            <div class="mu-empty-state" id="mu-empty-state" style="display: none;">
                <svg class="mu-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <p class="mu-empty-text">No results found. Try different keywords.</p>
                <button type="button" class="mu-empty-btn" id="mu-empty-clear">Clear Search</button>
            </div>
            
            <!-- Loading -->
            <div class="mu-loading-state" id="mu-loading-state" style="display: none;">
                <div class="mu-loading-spinner"></div>
            </div>
            
            <!-- Pagination -->
            <div class="mu-pagination" id="mu-pagination" style="display: none;">
                <div class="mu-pagination-info">
                    <span id="mu-pagination-text"></span>
                </div>
                <div class="mu-pagination-controls">
                    <button type="button" class="mu-page-btn" id="mu-prev-btn" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <div class="mu-page-numbers" id="mu-page-numbers"></div>
                    <button type="button" class="mu-page-btn" id="mu-next-btn" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
window.MOVIE_UI = window.MOVIE_UI || {};
window.MOVIE_UI.ajaxUrl = '<?php echo esc_url($ajax_url); ?>';
window.MOVIE_UI.nonce = '<?php echo esc_attr($ajax_nonce); ?>';
window.MOVIE_UI.watchUrl = '<?php echo esc_url($watch_base); ?>';
window.MOVIE_UI.searchUrl = '<?php echo esc_url(home_url('/search/')); ?>';
</script>
<script src="<?php echo esc_url(get_theme_file_uri('assets/js/ms-search.js')); ?>"></script>

<?php wp_footer(); ?>
</body>
</html>
