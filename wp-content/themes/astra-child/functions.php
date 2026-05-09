<?php
// Nạp CSS của theme cha
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
});

/**
 * ============================================================
 * STREAMING PLATFORM CORE (Astra Child)
 * Paste this entire file as `functions.php` in your child theme.
 * ============================================================
 */

require_once __DIR__ . '/inc/movie-system/routing-helpers.php';

// Include Movie Importer System
require_once __DIR__ . '/inc/movie-system/importer-api.php';
require_once __DIR__ . '/inc/movie-system/importer-media.php';
require_once __DIR__ . '/inc/movie-system/importer-actions.php';
require_once __DIR__ . '/inc/movie-system/importer-admin.php';
require_once __DIR__ . '/inc/movie-system/auto-pages.php';

// Theme supports
add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
});

// ============================================================
// BODY CLASS: add movie-ui to all streaming pages
// This ensures Astra default header is hidden via CSS.
// ============================================================
add_filter('body_class', function (array $classes): array {
    $app_slugs = mu_streaming_page_slugs();

    // Always apply on movie/tv_show archives and singles
    if (
        is_singular(['movie', 'tv_show', 'episode']) ||
        is_post_type_archive(['movie', 'tv_show']) ||
        is_tax(['genre', 'country', 'quality', 'language', 'actor', 'director']) ||
        is_front_page() ||
        is_404()
    ) {
        $classes[] = 'movie-ui';
        $classes[] = 'movie-ui--no-sidebar';
        return $classes;
    }

    if (is_page()) {
        $slug = get_post_field('post_name', get_queried_object_id());
        if (in_array((string) $slug, $app_slugs, true)) {
            $classes[] = 'movie-ui';
            $classes[] = 'movie-ui--no-sidebar';
        }
    }

    return $classes;
});

// ============================================================
// FLUSH REWRITE RULES once after theme activation / CPT registration
// Fixes 404 on /movies/, /tv/, etc.
// ============================================================
add_action('after_switch_theme', function () {
    flush_rewrite_rules();
    update_option('mu_needs_flush', 1);
});
// Safety net: flush rewrite rules to ensure /movies/ path is recognized
add_action('init', function () {
    static $flushed = false;
    if (!$flushed && (get_option('mu_needs_flush') || isset($_GET['flush_rewrites']))) {
        flush_rewrite_rules();
        delete_option('mu_needs_flush');
        $flushed = true;
    }
}, 99);

/**
 * FORCE CPT ARCHIVE: Fix 404 on /movies/ and /tv/ by intercepting early
 * Also auto-deletes any conflicting pages with same slug.
 */
// Run only once to clean up conflicting pages
$mu_cleaned_up = get_option('mu_cleanup_done', false);
if (!$mu_cleaned_up) {
    $conflicting_slugs = ['movies', 'tv'];
    foreach ($conflicting_slugs as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page instanceof WP_Post) {
            wp_delete_post($page->ID, true);
        }
    }
    update_option('mu_cleanup_done', true);
}

add_action('parse_request', function ($wp) {
    $path = isset($wp->request) ? trim($wp->request, '/') : '';
    $path = preg_replace('#/page/\d+#', '', $path);
    
    // Handle /movies/ → movie CPT archive
    if ($path === 'movies' || $path === 'movies/') {
        $wp->query_vars = [
            'post_type' => 'movie',
            'posts_per_page' => 24,
        ];
        $wp->matched_rule = 'movies/?$';
    }
    // Handle /tv/ → tv_show CPT archive  
    elseif ($path === 'tv' || $path === 'tv/') {
        $wp->query_vars = [
            'post_type' => 'tv_show',
            'posts_per_page' => 24,
        ];
        $wp->matched_rule = 'tv/?$';
    }
}, 1);

add_filter('template_include', function ($template) {
    // Check by post type archive or by direct path
    $is_movie_archive = is_post_type_archive('movie');
    $is_tv_archive = is_post_type_archive('tv_show');
    
    // Fallback: check request path directly
    if (!$is_movie_archive && !$is_tv_archive) {
        $path = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';
        $path = preg_replace('#/page/\d+#', '', $path);
        $is_movie_archive = ($path === 'movies' || $path === 'movies');
        $is_tv_archive = ($path === 'tv' || $path === 'tv');
    }
    
    if ($is_movie_archive) {
        $new_template = get_stylesheet_directory() . '/archive-movie.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    if ($is_tv_archive) {
        $new_template = get_stylesheet_directory() . '/archive-tv_show.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
}, 99);

// Trigger a flush whenever a CPT is registered for the first time
add_action('init', function () {
    $stored = get_option('mu_registered_cpts', []);
    $current = ['movie', 'tv_show', 'episode'];
    if (array_diff($current, $stored)) {
        update_option('mu_registered_cpts', $current);
        update_option('mu_needs_flush', 1);
    }
}, 100);

/**
 * Register Custom Post Types:
 * - movie
 * - tv_show
 * - episode
 */
add_action('init', function () {
    register_post_type('movie', [
        'labels' => [
            'name' => 'Movies',
            'singular_name' => 'Movie',
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'movies'],
        'menu_icon' => 'dashicons-video-alt2',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'comments'],
        'show_in_rest' => true,
    ]);

    register_post_type('tv_show', [
        'labels' => [
            'name' => 'TV Shows',
            'singular_name' => 'TV Show',
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'tv'],
        'menu_icon' => 'dashicons-format-video',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'comments'],
        'show_in_rest' => true,
    ]);

    register_post_type('episode', [
        'labels' => [
            'name' => 'Episodes',
            'singular_name' => 'Episode',
        ],
        'public' => true,
        'has_archive' => false,
        'rewrite' => ['slug' => 'episode'],
        'menu_icon' => 'dashicons-playlist-video',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'comments'],
        'show_in_rest' => true,
    ]);
}, 0);

/**
 * Register taxonomies:
 * - genre
 * - country
 * - quality
 * - language
 *
 * Attach to movie + tv_show (+ episode where helpful).
 */
add_action('init', function () {
    $post_types = ['movie', 'tv_show', 'episode'];

    register_taxonomy('genre', $post_types, [
        'labels' => ['name' => 'Genres', 'singular_name' => 'Genre'],
        'public' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'genre'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('country', $post_types, [
        'labels' => ['name' => 'Countries', 'singular_name' => 'Country'],
        'public' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'country'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('quality', $post_types, [
        'labels' => ['name' => 'Quality', 'singular_name' => 'Quality'],
        'public' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'quality'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('language', $post_types, [
        'labels' => ['name' => 'Languages', 'singular_name' => 'Language'],
        'public' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'language'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('actor', $post_types, [
        'labels' => ['name' => 'Actors', 'singular_name' => 'Actor'],
        'public' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'actor'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('director', $post_types, [
        'labels' => ['name' => 'Directors', 'singular_name' => 'Director'],
        'public' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'director'],
        'show_in_rest' => true,
    ]);
}, 1);

/**
 * Enqueue UI assets + Swiper CDN
 * Files:
 * - assets/css/movie-ui.css
 * - assets/js/movie-ui.js
 */
add_action('wp_enqueue_scripts', function () {
    $uri = get_stylesheet_directory_uri();
    $dir = get_stylesheet_directory();
    $ver = filemtime($dir . '/assets/css/movie-ui.css') ?: wp_get_theme()->get('Version');

    wp_enqueue_style('movie-ui', $uri . '/assets/css/movie-ui.css', ['parent-style'], $ver);
    wp_enqueue_style('movie-ui-toast', $uri . '/assets/css/toast.css', ['movie-ui'], $ver);
    
    // Movies Page CSS
    $movies_ver = filemtime($dir . '/assets/css/movies-page.css') ?: $ver;
    wp_enqueue_style('movies-page', $uri . '/assets/css/movies-page.css', ['movie-ui'], $movies_ver);

    // Swiper
    wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11');
    wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11', true);

    // HLS.js for .m3u8 playback (player)
    wp_enqueue_script('hls', 'https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js', [], '1.5.18', true);

    // Modular UI scripts — all in one bundle
    wp_enqueue_script('movie-ui-toast', $uri . '/assets/js/toast.js', [], $ver, true);
    wp_enqueue_script('movie-ui', $uri . '/assets/js/movie-ui.js', ['swiper', 'hls', 'movie-ui-toast'], $ver, true);

    // Premium OTT Player assets (only on watch page)
    $player_ver = filemtime($dir . '/assets/css/ms-player.css') ?: $ver;
    wp_enqueue_style('ms-player', $uri . '/assets/css/ms-player.css', ['movie-ui'], $player_ver);
    wp_enqueue_script('ms-player', $uri . '/assets/js/ms-player.js', ['movie-ui'], $player_ver, true);

    // Premium OTT History page assets
    $history_ver = filemtime($dir . '/assets/css/ms-history.css') ?: $ver;
    wp_enqueue_style('ms-history', $uri . '/assets/css/ms-history.css', ['movie-ui'], $history_ver);
    wp_enqueue_script('ms-history', $uri . '/assets/js/ms-history.js', ['movie-ui'], $history_ver, true);

    wp_localize_script('movie-ui', 'MOVIE_UI', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('movie_ui_nonce'),
        // If your DB favorites AJAX exists, we reuse it
        'favNonce' => wp_create_nonce('fav_nonce'),
        'progressNonce' => wp_create_nonce('movie_ui_progress'),
        'isLoggedIn' => is_user_logged_in(),
        'homeUrl' => home_url('/'),
        'watchUrl' => mu_get_page_url_by_slug('watch'),
        'searchUrl' => mu_get_page_url_by_slug('search'),
        'myListUrl' => mu_get_page_url_by_slug('favorites'),
        'historyUrl' => mu_get_page_url_by_slug('history'),
        'profileUrl' => mu_get_page_url_by_slug('profile'),
        'vipUrl' => mu_get_page_url_by_slug('vip'),
        'topRatedUrl' => mu_get_page_url_by_slug('top-rated'),
        'trendingUrl' => mu_get_page_url_by_slug('trending'),
        'newReleasesUrl' => mu_get_page_url_by_slug('new-releases'),
        'moviesArchiveUrl' => trailingslashit(home_url('movies')),
        'tvArchiveUrl' => trailingslashit(home_url('tv')),
    ]);
}, 30);

/**
 * Mark streaming pages so CSS can remove Astra header/footer/sidebar.
 */
function movie_ui_is_app_page() : bool {
    if (is_front_page()) return true;
    if (is_post_type_archive(['movie', 'tv_show'])) return true;
    if (is_singular(['movie', 'tv_show', 'episode'])) return true;
    if (is_tax(['genre', 'country', 'quality', 'language', 'actor', 'director'])) return true;

    if (is_page()) {
        $slug = get_post_field('post_name', get_queried_object_id());
        return in_array((string) $slug, mu_streaming_page_slugs(), true);
    }

    return false;
}

add_action('wp_footer', function () : void {
    if (!movie_ui_is_app_page() && !is_404()) {
        return;
    }
    get_template_part('template-parts/streaming/search-overlay');
}, 35);

// Redundant body_class filter removed to avoid conflicts


/**
 * Helpers (meta/tax/placeholder)
 */
function movie_ui_meta(int $post_id, array $keys, string $fallback = '') : string {
    foreach ($keys as $k) {
        $val = get_post_meta($post_id, $k, true);
        if ($val !== '' && $val !== null) return (string) $val;
    }
    return $fallback;
}

function movie_ui_backdrop_url(int $post_id) : string {
    $url = movie_ui_meta($post_id, ['backdrop_url', '_backdrop_url'], '');
    if ($url) return esc_url_raw($url);
    $thumb = get_the_post_thumbnail_url($post_id, 'full');
    return $thumb ? $thumb : '';
}

function movie_ui_terms_text(int $post_id, string $tax, int $limit = 2) : string {
    $terms = get_the_terms($post_id, $tax);
    if (!$terms || is_wp_error($terms)) return '';
    $names = array_slice(wp_list_pluck($terms, 'name'), 0, max(1, $limit));
    return implode(', ', array_map('esc_html', $names));
}

function movie_ui_placeholder_poster() : string {
    // Pure CSS placeholder (no external image required)
    return '<div class="mu-poster mu-poster--placeholder" aria-hidden="true"></div>';
}

/**
 * Component render: movie card
 * Use: get_template_part('template-parts/streaming/movie-card', null, ['post_id' => get_the_ID()]);
 */
function movie_ui_render_movie_card(int $post_id) : void {
    get_template_part('template-parts/streaming/movie-card', null, ['post_id' => $post_id]);
}

/**
 * Home page carousel row (horizontal swiper).
 */
function mu_front_render_carousel(string $label, WP_Query $q, string $suffix) : void {
    ?>
    <?php if ($q->have_posts()) : ?>
        <section class="mu-row" id="<?php echo esc_attr($suffix); ?>">
            <div class="mu-row-inner">
                <div class="mu-row__head">
                    <h2 class="mu-row__title"><?php echo esc_html($label); ?></h2>
                    <div class="mu-row__nav">
                        <button class="mu-navbtn mu-navbtn--prev" type="button" aria-label="<?php esc_attr_e('Previous', 'astra-child'); ?>" data-mu-row-prev="<?php echo esc_attr($suffix); ?>"></button>
                        <button class="mu-navbtn mu-navbtn--next" type="button" aria-label="<?php esc_attr_e('Next', 'astra-child'); ?>" data-mu-row-next="<?php echo esc_attr($suffix); ?>"></button>
                    </div>
                </div>
                <div class="mu-swiper-wrap" data-mu-swiper-wrap="<?php echo esc_attr($suffix); ?>">
                    <div class="swiper mu-swiper" data-mu-swiper="row">
                        <div class="swiper-wrapper">
                            <?php
                            while ($q->have_posts()) :
                                $q->the_post();
                                ?>
                                <div class="swiper-slide mu-slide"><?php movie_ui_render_movie_card(get_the_ID()); ?></div>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php else : ?>
        <?php wp_reset_postdata(); ?>
        <?php
    endif;
}

/**
 * Continue watching row — hydrated client-side from localStorage + optional server progress.
 */
function mu_front_continue_row(string $label, string $html_id = '_mu_continue') : void {
    ?>
        <section class="mu-row" id="<?php echo esc_attr($html_id); ?>">
        <div class="mu-row-inner">
                <div class="mu-row__head">
                    <h2 class="mu-row__title"><?php echo esc_html($label); ?></h2>
                    <div class="mu-row__nav">
                        <button class="mu-navbtn mu-navbtn--prev" type="button" aria-label="<?php esc_attr_e('Previous', 'astra-child'); ?>" data-mu-row-prev="<?php echo esc_attr($html_id); ?>"></button>
                        <button class="mu-navbtn mu-navbtn--next" type="button" aria-label="<?php esc_attr_e('Next', 'astra-child'); ?>" data-mu-row-next="<?php echo esc_attr($html_id); ?>"></button>
                    </div>
                </div>
                <div class="mu-swiper-wrap" data-mu-swiper-wrap="<?php echo esc_attr($html_id); ?>">
                    <div class="swiper mu-swiper" data-mu-swiper="row" data-mu-continue-swiper>
                        <div class="swiper-wrapper" data-mu-continue-mount></div>
                    </div>
                </div>
                <p class="mu-continue-msg mu-muted" hidden><?php esc_html_e('Start watching — progress is remembered on this device.', 'astra-child'); ?></p>
            </div>
        </section>
    <?php
}

/**
 * Home “Top 10” numbered rail.
 */
function mu_front_render_top10(string $label, WP_Query $q, string $suffix) : void {
    if (!$q->have_posts()) {
        return;
    }
    $i = 0;
    ?>
    <section class="mu-row mu-top10-row" id="<?php echo esc_attr($suffix); ?>">
        <div class="mu-row-inner">
            <div class="mu-row__head">
                <h2 class="mu-row__title"><?php echo esc_html($label); ?></h2>
                <div class="mu-row__nav">
                    <button class="mu-navbtn mu-navbtn--prev" type="button" aria-label="<?php esc_attr_e('Previous', 'astra-child'); ?>" data-mu-row-prev="<?php echo esc_attr($suffix); ?>"></button>
                    <button class="mu-navbtn mu-navbtn--next" type="button" aria-label="<?php esc_attr_e('Next', 'astra-child'); ?>" data-mu-row-next="<?php echo esc_attr($suffix); ?>"></button>
                </div>
            </div>
            <div class="mu-swiper-wrap" data-mu-swiper-wrap="<?php echo esc_attr($suffix); ?>">
                <div class="swiper mu-swiper" data-mu-swiper="row">
                    <div class="swiper-wrapper">
                        <?php
                        while ($q->have_posts()) :
                            $q->the_post();
                            ++$i;
                            ?>
                            <div class="swiper-slide mu-slide mu-top10-slide">
                                <span class="mu-top10-num" aria-hidden="true"><?php echo (string) max(1, min(99, $i)); ?></span>
                                <?php movie_ui_render_movie_card(get_the_ID()); ?>
                            </div>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
}

/**
 * WP_Query helpers for rows
 */
function movie_ui_query(array $args = []) : WP_Query {
    $base = [
        'post_status' => 'publish',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ];
    if (!isset($args['post_type'])) {
        $args['post_type'] = ['movie', 'tv_show'];
    }
    return new WP_Query(array_merge($base, $args));
}

/**
 * AJAX: Search suggestions/results (used on Search page + overlay)
 */
add_action('wp_ajax_movie_ui_search', 'movie_ui_ajax_search');
add_action('wp_ajax_nopriv_movie_ui_search', 'movie_ui_ajax_search');
function movie_ui_ajax_search() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    $q = isset($_POST['q']) ? sanitize_text_field((string) $_POST['q']) : '';
    // Short query: return trending-ish grid so Search page always has discovery UI.
    if (mb_strlen($q) < 2) {
        $disc = movie_ui_query([
            'post_type' => ['movie', 'tv_show'],
            'posts_per_page' => 12,
            'meta_key' => '_view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        ]);
        ob_start();
        if ($disc->have_posts()) {
            echo '<div class="mu-grid">';
            while ($disc->have_posts()) {
                $disc->the_post();
                movie_ui_render_movie_card(get_the_ID());
            }
            wp_reset_postdata();
            echo '</div>';
        } else {
            echo '<p class="mu-muted">' . esc_html__('Import or publish titles to populate suggestions.', 'astra-child') . '</p>';
        }
        $fallback = ob_get_clean();
        $sugg = [];
        $sq = movie_ui_query(['post_type' => ['movie', 'tv_show'], 'posts_per_page' => 8, 'orderby' => 'date', 'order' => 'DESC']);
        foreach ($sq->posts as $p) {
            $sugg[] = ['id' => (int) $p->ID, 'title' => get_the_title($p), 'url' => get_permalink($p)];
        }
        wp_reset_postdata();

        wp_send_json_success(['html' => $fallback, 'suggestions' => $sugg]);
        return;
    }

    $query = movie_ui_query([
        'post_type' => ['movie', 'tv_show'],
        'posts_per_page' => 18,
        's' => $q,
    ]);

    ob_start();
    if ($query->have_posts()) {
        echo '<div class="mu-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            movie_ui_render_movie_card(get_the_ID());
        }
        wp_reset_postdata();
        echo '</div>';
    } else {
        echo '<div class="mu-empty">No results.</div>';
    }
    $html = ob_get_clean();

    // Basic suggestions: top 6 titles
    $suggestions = [];
    foreach ($query->posts as $p) {
        $suggestions[] = ['id' => (int) $p->ID, 'title' => get_the_title($p->ID), 'url' => get_permalink($p->ID)];
        if (count($suggestions) >= 6) {
            break;
        }
    }

    wp_send_json_success(['html' => $html, 'suggestions' => $suggestions]);
}

/**
 * AJAX: Render movie cards HTML for Continue Watching / My List hydration.
 */
add_action('wp_ajax_movie_ui_cards_by_ids', 'movie_ui_ajax_cards_by_ids');
add_action('wp_ajax_nopriv_movie_ui_cards_by_ids', 'movie_ui_ajax_cards_by_ids');

function movie_ui_ajax_cards_by_ids() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    $raw = isset($_POST['ids']) ? wp_unslash($_POST['ids']) : '';
    if (!is_array($raw)) {
        $raw = [];
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $raw))));
    if (!$ids) {
        wp_send_json_success(['html' => '']);
    }

    $layout = isset($_POST['layout']) ? sanitize_key((string) $_POST['layout']) : 'swiper';

    $q = movie_ui_query([
        'post__in' => $ids,
        'orderby' => 'post__in',
        'posts_per_page' => count($ids),
    ]);
    ob_start();
    if ($q->have_posts()) {
        if ($layout === 'grid') {
            while ($q->have_posts()) {
                $q->the_post();
                movie_ui_render_movie_card(get_the_ID());
            }
        } else {
            while ($q->have_posts()) {
                $q->the_post();
                echo '<div class="swiper-slide mu-slide">';
                movie_ui_render_movie_card(get_the_ID());
                echo '</div>';
            }
        }
        wp_reset_postdata();
    }
    wp_send_json_success(['html' => ob_get_clean()]);
}

/**
 * ============================================================
 * Advanced intelligent search (title/original/actor/director/tax/year/tags)
 * AJAX: movie_ui_search_advanced
 * ============================================================
 */
add_action('wp_ajax_movie_ui_search_advanced', 'movie_ui_ajax_search_advanced');
add_action('wp_ajax_nopriv_movie_ui_search_advanced', 'movie_ui_ajax_search_advanced');
function movie_ui_ajax_search_advanced() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    $q = isset($_POST['q']) ? sanitize_text_field((string) $_POST['q']) : '';
    if (mb_strlen($q) < 2) {
        wp_send_json_success(['tokens' => [], 'movies' => [], 'tv' => [], 'anime' => [], 'actors' => [], 'html_full' => '']);
    }

    $tokens = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
    $tokens = array_values(array_filter(array_map(function ($t) {
        $t = trim((string) $t);
        return mb_strlen($t) >= 2 ? $t : '';
    }, $tokens)));

    global $wpdb;
    $like_clauses = [];
    $params = [];
    foreach ($tokens as $t) {
        $like = '%' . $wpdb->esc_like($t) . '%';
        // title + content + excerpt + tags + meta fields
        $like_clauses[] = "(p.post_title LIKE %s OR p.post_excerpt LIKE %s OR p.post_content LIKE %s
          OR (pm_ot.meta_key IN ('original_title','_original_title') AND pm_ot.meta_value LIKE %s)
          OR (pm_ac.meta_key IN ('actors','_actors','cast','_cast') AND pm_ac.meta_value LIKE %s)
          OR (pm_dr.meta_key IN ('director','_director') AND pm_dr.meta_value LIKE %s)
          OR (pm_yr.meta_key IN ('year','_release_year') AND pm_yr.meta_value LIKE %s)
        )";
        // 7 params per token
        array_push($params, $like, $like, $like, $like, $like, $like, $like);
    }
    $where_tokens = $like_clauses ? (' AND ' . implode(' AND ', $like_clauses)) : '';

    // Candidate pool via SQL (fast fuzzy-ish)
    $sql = "
      SELECT DISTINCT p.ID, p.post_type
      FROM {$wpdb->posts} p
      LEFT JOIN {$wpdb->postmeta} pm_ot ON pm_ot.post_id = p.ID
      LEFT JOIN {$wpdb->postmeta} pm_ac ON pm_ac.post_id = p.ID
      LEFT JOIN {$wpdb->postmeta} pm_dr ON pm_dr.post_id = p.ID
      LEFT JOIN {$wpdb->postmeta} pm_yr ON pm_yr.post_id = p.ID
      WHERE p.post_status='publish'
        AND p.post_type IN ('movie','tv_show')
        $where_tokens
      ORDER BY p.post_date DESC
      LIMIT 120
    ";

    $ids = $wpdb->get_results($wpdb->prepare($sql, ...$params));
    $movie_ids = [];
    $tv_ids = [];
    foreach ($ids as $r) {
        if ($r->post_type === 'movie') $movie_ids[] = (int) $r->ID;
        if ($r->post_type === 'tv_show') $tv_ids[] = (int) $r->ID;
    }

    // Anime: subset of movies+tv in genre anime
    $anime_ids = [];
    if ($movie_ids || $tv_ids) {
        $in = array_merge($movie_ids, $tv_ids);
        $in = array_values(array_unique(array_map('intval', $in)));
        $anime_q = new WP_Query([
            'post_type' => ['movie','tv_show'],
            'posts_per_page' => 12,
            'post__in' => $in,
            'orderby' => 'post__in',
            'tax_query' => [[
                'taxonomy' => 'genre',
                'field' => 'slug',
                'terms' => ['anime','hoat-hinh'],
                'operator' => 'IN',
            ]],
            'no_found_rows' => true,
        ]);
        $anime_ids = wp_list_pluck($anime_q->posts, 'ID');
    }

    $pack_item = function (int $id) {
        $thumb = get_the_post_thumbnail_url($id, 'thumbnail');
        $rating = movie_ui_meta($id, ['rating','_rating'], '');
        $year = movie_ui_meta($id, ['year','_release_year'], '');
        $genre = movie_ui_terms_text($id, 'genre', 2);
        $meta = implode(' • ', array_filter([$year, $genre, $rating ? ('★ ' . $rating) : '']));
        return [
            'id' => $id,
            'title' => get_the_title($id),
            'url' => get_permalink($id),
            'thumb' => $thumb ?: '',
            'meta' => $meta,
        ];
    };

    $movies = array_map($pack_item, array_slice($movie_ids, 0, 12));
    $tv = array_map($pack_item, array_slice($tv_ids, 0, 12));
    $anime = array_map($pack_item, array_slice(array_map('intval', $anime_ids), 0, 12));

    // Actors category (simple extraction of matching actor names from meta)
    $actors = [];
    foreach ($ids as $r) {
        $val = (string) get_post_meta((int)$r->ID, 'actors', true);
        if (!$val) $val = (string) get_post_meta((int)$r->ID, '_actors', true);
        if (!$val) continue;
        foreach (preg_split('/,|\\||;|\n/u', $val) as $name) {
            $name = trim($name);
            if (!$name) continue;
            $match = true;
            foreach ($tokens as $t) {
                if (mb_stripos($name, $t) === false) { $match = false; break; }
            }
            if ($match) $actors[$name] = true;
        }
        if (count($actors) >= 10) break;
    }
    $actors = array_slice(array_keys($actors), 0, 10);
    $actors_pack = array_map(function ($name) use ($q) {
        return [
            'name' => $name,
            'url' => home_url('/search/?q=' . rawurlencode($name)),
            'thumb' => '',
            'meta' => 'Actor',
        ];
    }, $actors);

    // Full page HTML (grid) to reuse on page-search.php
    ob_start();
    $all_ids = array_values(array_unique(array_merge($movie_ids, $tv_ids)));
    if ($all_ids) {
        echo '<div class="mu-grid">';
        foreach (array_slice($all_ids, 0, 48) as $pid) {
            movie_ui_render_movie_card((int) $pid);
        }
        echo '</div>';
    } else {
        echo '<div class="mu-empty">No results.</div>';
    }
    $html_full = ob_get_clean();

    wp_send_json_success([
        'tokens' => $tokens ?: preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY),
        'movies' => $movies,
        'tv' => $tv,
        'anime' => $anime,
        'actors' => $actors_pack,
        'html_full' => $html_full,
    ]);
}

/**
 * ============================================================
 * Progress / Continue Watching (DB for logged-in)
 * Table: wp_movie_progress
 * ============================================================
 */
add_action('after_switch_theme', function () {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = $wpdb->prefix . 'movie_progress';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        post_id BIGINT UNSIGNED NOT NULL,
        current_time INT UNSIGNED NOT NULL DEFAULT 0,
        duration INT UNSIGNED NOT NULL DEFAULT 0,
        progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_post (user_id, post_id),
        KEY post_id (post_id),
        KEY updated_at (updated_at)
    ) $charset;";
    dbDelta($sql);
});

add_action('wp_ajax_movie_ui_save_progress', 'movie_ui_save_progress');
function movie_ui_save_progress() : void {
    if (!check_ajax_referer('movie_ui_progress', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'not_logged_in'], 401);
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $current_time = isset($_POST['t']) ? (int) $_POST['t'] : 0;
    $duration = isset($_POST['d']) ? (int) $_POST['d'] : 0;
    $pct = isset($_POST['p']) ? (int) $_POST['p'] : 0;

    if (!$post_id || $current_time < 0 || $duration < 0) {
        wp_send_json_error(['message' => 'bad_input'], 400);
    }

    $pct = max(0, min(100, $pct));
    $user_id = get_current_user_id();

    global $wpdb;
    $table = $wpdb->prefix . 'movie_progress';
    $wpdb->query($wpdb->prepare(
        "INSERT INTO $table (user_id, post_id, current_time, duration, progress_percent, updated_at)
         VALUES (%d, %d, %d, %d, %d, %s)
         ON DUPLICATE KEY UPDATE current_time=VALUES(current_time), duration=VALUES(duration), progress_percent=VALUES(progress_percent), updated_at=VALUES(updated_at)",
        $user_id, $post_id, $current_time, $duration, $pct, current_time('mysql')
    ));

    wp_send_json_success(['ok' => true]);
}

function movie_ui_get_continue_watching_ids(int $limit = 18) : array {
    if (!is_user_logged_in()) return [];
    global $wpdb;
    $table = $wpdb->prefix . 'movie_progress';
    $uid = get_current_user_id();
    // Remove completed (>= 95%)
    $ids = $wpdb->get_col($wpdb->prepare("
        SELECT post_id
        FROM $table
        WHERE user_id = %d AND progress_percent < 95
        ORDER BY updated_at DESC
        LIMIT %d
    ", $uid, $limit));
    return array_map('intval', $ids ?: []);
}

/**
 * ============================================================
 * Watch History AJAX handlers
 * ============================================================
 */
// Get user history
add_action('wp_ajax_mu_get_history', 'mu_ajax_get_history');
function mu_ajax_get_history() : void {
    if (!check_ajax_referer('mu_history_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'not_logged_in'], 401);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'movie_progress';
    $user_id = get_current_user_id();

    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, current_time, duration, progress_percent, updated_at
         FROM {$table}
         WHERE user_id = %d
         ORDER BY updated_at DESC
         LIMIT 100",
        $user_id
    ));

    $result = [];
    foreach ($history as $row) {
        $post_id = (int) $row->post_id;
        if (get_post_status($post_id) !== 'publish') continue;

        $result[] = [
            'post_id' => $post_id,
            'current_time' => (int) $row->current_time,
            'duration' => (int) $row->duration,
            'percent' => (int) $row->progress_percent,
            'updated_at' => strtotime($row->updated_at) * 1000
        ];
    }

    wp_send_json_success(['history' => $result]);
}

// Remove single item from history
add_action('wp_ajax_mu_remove_history', 'mu_ajax_remove_history');
function mu_ajax_remove_history() : void {
    if (!check_ajax_referer('mu_history_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'not_logged_in'], 401);
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id) {
        wp_send_json_error(['message' => 'bad_input'], 400);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'movie_progress';
    $user_id = get_current_user_id();

    $wpdb->delete($table, [
        'user_id' => $user_id,
        'post_id' => $post_id
    ]);

    wp_send_json_success(['ok' => true]);
}

// Clear all history
add_action('wp_ajax_mu_clear_history', 'mu_ajax_clear_history');
function mu_ajax_clear_history() : void {
    if (!check_ajax_referer('mu_history_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'not_logged_in'], 401);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'movie_progress';
    $user_id = get_current_user_id();

    $wpdb->delete($table, ['user_id' => $user_id]);

    wp_send_json_success(['ok' => true, 'message' => 'History cleared']);
}

/**
 * Episode sidebar AJAX
 * - movie_ui_get_seasons(tv_show_id) -> seasons list
 * - movie_ui_get_episodes(tv_show_id, season) -> episode cards html
 */
add_action('wp_ajax_movie_ui_get_episodes', 'movie_ui_ajax_get_episodes');
add_action('wp_ajax_nopriv_movie_ui_get_episodes', 'movie_ui_ajax_get_episodes');
function movie_ui_ajax_get_episodes() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    $tv_show_id = isset($_POST['tv_show_id']) ? (int) $_POST['tv_show_id'] : 0;
    $season = isset($_POST['season']) ? (int) $_POST['season'] : 1;
    $current_id = isset($_POST['current_id']) ? (int) $_POST['current_id'] : 0;
    if (!$tv_show_id) wp_send_json_error(['message' => 'missing_tv_show'], 400);

    $eps = new WP_Query([
        'post_type' => 'episode',
        'posts_per_page' => 200,
        'meta_query' => [
            ['key' => 'tv_show_id', 'value' => $tv_show_id, 'compare' => '='],
            ['key' => 'season_number', 'value' => $season, 'compare' => '=', 'type' => 'NUMERIC'],
        ],
        'orderby' => 'meta_value_num',
        'meta_key' => 'episode_number',
        'order' => 'ASC',
        'no_found_rows' => true,
    ]);

    ob_start();
    if ($eps->have_posts()) {
        while ($eps->have_posts()) {
            $eps->the_post();
            $eid = get_the_ID();
            $epn = movie_ui_meta($eid, ['episode_number'], '');
            $dur = movie_ui_meta($eid, ['duration','_duration'], '');
            $still = movie_ui_backdrop_url($eid);
            $wbase = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : home_url('/watch/');
            $watch = add_query_arg('id', $eid, $wbase);
            $cls = 'mu-ep' . (($eid === $current_id) ? ' is-current' : '');
            ?>
            <a class="<?php echo esc_attr($cls); ?>" href="<?php echo esc_url($watch); ?>" data-ep-id="<?php echo esc_attr((string)$eid); ?>">
              <div class="mu-ep__thumb"><?php if ($still): ?><div style="background-image:url('<?php echo esc_url($still); ?>');"></div><?php endif; ?></div>
              <div style="min-width:0;flex:1;">
                <div class="mu-ep__title"><?php echo $epn ? ('E' . esc_html($epn) . ' • ') : ''; ?><?php the_title(); ?></div>
                <div class="mu-ep__meta"><?php echo esc_html(trim(($dur ? ($dur . 'm') : '') . ' ' . wp_trim_words(wp_strip_all_tags(get_the_excerpt() ?: ''), 10, '…'))); ?></div>
                <div class="mu-ep__bar"><div data-mu-progress="<?php echo esc_attr((string)$eid); ?>"></div></div>
              </div>
            </a>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<div class="mu-empty" style="padding:0 12px 12px;">No episodes.</div>';
    }
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_movie_ui_get_seasons', 'movie_ui_ajax_get_seasons');
add_action('wp_ajax_nopriv_movie_ui_get_seasons', 'movie_ui_ajax_get_seasons');
function movie_ui_ajax_get_seasons() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    $tv_show_id = isset($_POST['tv_show_id']) ? (int) $_POST['tv_show_id'] : 0;
    if (!$tv_show_id) wp_send_json_error(['message' => 'missing_tv_show'], 400);

    $ids = get_posts([
        'post_type' => 'episode',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            ['key' => 'tv_show_id', 'value' => $tv_show_id, 'compare' => '='],
        ],
    ]);
    $seasons = [];
    foreach ($ids as $eid) {
        $sn = (int) get_post_meta((int)$eid, 'season_number', true);
        if ($sn) $seasons[$sn] = true;
    }
    $out = array_keys($seasons);
    sort($out);
    if (!$out) $out = [1];
    wp_send_json_success(['seasons' => array_values($out)]);
}

/**
 * Watch history API (remove/clear/fetch grouped)
 */
add_action('wp_ajax_movie_ui_history_remove', 'movie_ui_ajax_history_remove');
function movie_ui_ajax_history_remove() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'not_logged_in'], 401);
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id) wp_send_json_error(['message' => 'bad_input'], 400);
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'movie_watch_history', [
        'user_id' => get_current_user_id(),
        'movie_id' => $post_id,
    ]);
    wp_send_json_success(['ok' => true]);
}

/**
 * AJAX: Remove from My List / Favorites
 */
add_action('wp_ajax_movie_ui_remove_favorite', 'movie_ui_ajax_remove_favorite');
function movie_ui_ajax_remove_favorite() : void {
    if (!check_ajax_referer('mu_mylist_ajax', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'not_logged_in'], 401);
    }
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id) wp_send_json_error(['message' => 'bad_input'], 400);
    
    global $wpdb;
    $table = $wpdb->prefix . 'movie_favorites';
    $user_id = get_current_user_id();
    
    // Check if exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id=%d AND movie_id=%d",
        $user_id, $post_id
    ));
    
    if ($exists) {
        $wpdb->delete($table, [
            'user_id' => $user_id,
            'movie_id' => $post_id,
        ]);
        wp_send_json_success(['message' => __('Removed from My List', 'astra-child')]);
    } else {
        wp_send_json_success(['message' => __('Already removed', 'astra-child')]);
    }
}

add_action('wp_ajax_movie_ui_history_clear', 'movie_ui_ajax_history_clear');
function movie_ui_ajax_history_clear() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'not_logged_in'], 401);
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'movie_watch_history', [
        'user_id' => get_current_user_id(),
    ]);
    wp_send_json_success(['ok' => true]);
}

add_action('wp_ajax_movie_ui_history_fetch', 'movie_ui_ajax_history_fetch');
function movie_ui_ajax_history_fetch() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'not_logged_in'], 401);
    $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? max(1, min(60, (int) $_POST['limit'])) : 24;
    $q = isset($_POST['q']) ? sanitize_text_field((string) $_POST['q']) : '';

    global $wpdb;
    $uid = get_current_user_id();
    $table = $wpdb->prefix . 'movie_watch_history';
    $posts = $wpdb->posts;

    if ($q) {
        $like = '%' . $wpdb->esc_like($q) . '%';
        $rows = $wpdb->get_results($wpdb->prepare("
            SELECT h.movie_id, MAX(h.watched_at) as last_watch, p.post_title
            FROM $table h
            LEFT JOIN $posts p ON p.ID = h.movie_id
            WHERE h.user_id = %d AND p.post_title LIKE %s
            GROUP BY h.movie_id
            ORDER BY last_watch DESC
            LIMIT %d OFFSET %d
        ", $uid, $like, $limit, $offset));
    } else {
        $rows = $wpdb->get_results($wpdb->prepare("
            SELECT h.movie_id, MAX(h.watched_at) as last_watch, p.post_title
            FROM $table h
            LEFT JOIN $posts p ON p.ID = h.movie_id
            WHERE h.user_id = %d
            GROUP BY h.movie_id
            ORDER BY last_watch DESC
            LIMIT %d OFFSET %d
        ", $uid, $limit, $offset));
    }

    $now = current_time('timestamp');
    $today_start = strtotime('today', $now);
    $yesterday_start = strtotime('yesterday', $now);
    $week_start = strtotime('monday this week', $now);

    ob_start();
    $last_group = null;
    foreach ($rows as $r) {
        $ts = strtotime($r->last_watch);
        $group = 'Earlier';
        if ($ts >= $today_start) $group = 'Today';
        elseif ($ts >= $yesterday_start) $group = 'Yesterday';
        elseif ($ts >= $week_start) $group = 'This Week';

        if ($group !== $last_group) {
            echo '<div style="font-weight:900;margin:18px 0 10px;">' . esc_html($group) . '</div>';
            $last_group = $group;
        }

        $pid = (int) $r->movie_id;
        echo '<div style="position:relative;">';
        movie_ui_render_movie_card($pid);
        echo '<button class="mu-btn mu-btn--ghost" type="button" data-mu-history-remove="' . esc_attr((string)$pid) . '" style="position:absolute;top:10px;right:10px;">Remove</button>';
        echo '</div>';
    }
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html, 'count' => count($rows)]);
}
/**
 * Favorites sync: localStorage -> DB after login (AJAX)
 */
add_action('wp_ajax_movie_ui_sync_favorites', 'movie_ui_sync_favorites');
function movie_ui_sync_favorites() : void {
    if (!check_ajax_referer('movie_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'not_logged_in'], 401);
    }
    $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
    $ids = array_values(array_filter($ids));
    if (!$ids) wp_send_json_success(['synced' => 0]);

    global $wpdb;
    $table = $wpdb->prefix . 'movie_favorites';
    $uid = get_current_user_id();
    $synced = 0;
    foreach ($ids as $movie_id) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE user_id=%d AND movie_id=%d", $uid, $movie_id));
        if ($exists) continue;
        $ok = $wpdb->insert($table, [
            'user_id' => $uid,
            'movie_id' => $movie_id,
            'created_at' => current_time('mysql'),
        ]);
        if ($ok) $synced++;
    }
    wp_send_json_success(['synced' => $synced]);
}

/**
 * ============================================================
 * Admin meta boxes (rating/year/duration/backdrop/trailer/video/subtitles)
 * and episode relationship fields (tv_show_id/season_number/episode_number)
 * ============================================================
 */
add_action('add_meta_boxes', function () {
    add_meta_box('movie_ui_meta', 'Streaming Fields', 'movie_ui_meta_box_render', ['movie', 'tv_show'], 'normal', 'high');
    add_meta_box('movie_ui_episode_meta', 'Episode Fields', 'movie_ui_episode_meta_box_render', ['episode'], 'normal', 'high');
});

function movie_ui_meta_box_render($post) {
    wp_nonce_field('movie_ui_meta_save', 'movie_ui_meta_nonce');
    $fields = [
        'rating' => 'Rating (IMDb)',
        'year' => 'Year',
        'duration' => 'Duration (minutes)',
        'backdrop_url' => 'Backdrop URL',
        'trailer_url' => 'Trailer URL',
        'video_url' => 'Video URL (MP4/HLS)',
        'subtitle_vtt' => 'Subtitle VTT URL',
        'actors' => 'Actors (text)',
        'director' => 'Director (text)',
    ];
    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">';
    foreach ($fields as $key => $label) {
        $val = get_post_meta($post->ID, $key, true);
        echo '<label style="display:block;">';
        echo '<div style="font-weight:600;margin:0 0 6px;">' . esc_html($label) . '</div>';
        echo '<input type="text" name="movie_ui_' . esc_attr($key) . '" value="' . esc_attr((string)$val) . '" style="width:100%;padding:8px;" />';
        echo '</label>';
    }
    echo '</div>';
}

function movie_ui_episode_meta_box_render($post) {
    wp_nonce_field('movie_ui_meta_save', 'movie_ui_meta_nonce');
    $tv_show_id = (int) get_post_meta($post->ID, 'tv_show_id', true);
    $season = (int) get_post_meta($post->ID, 'season_number', true);
    $ep = (int) get_post_meta($post->ID, 'episode_number', true);
    echo '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">';
    echo '<label><div style="font-weight:600;margin:0 0 6px;">TV Show ID</div><input type="number" name="movie_ui_tv_show_id" value="' . esc_attr((string)$tv_show_id) . '" style="width:100%;padding:8px;" /></label>';
    echo '<label><div style="font-weight:600;margin:0 0 6px;">Season #</div><input type="number" name="movie_ui_season_number" value="' . esc_attr((string)$season) . '" style="width:100%;padding:8px;" /></label>';
    echo '<label><div style="font-weight:600;margin:0 0 6px;">Episode #</div><input type="number" name="movie_ui_episode_number" value="' . esc_attr((string)$ep) . '" style="width:100%;padding:8px;" /></label>';
    echo '</div>';
    echo '<p style="margin-top:10px;color:#666;">Tip: set <code>tv_show_id</code> to the parent TV Show post ID.</p>';
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['movie_ui_meta_nonce']) || !wp_verify_nonce($_POST['movie_ui_meta_nonce'], 'movie_ui_meta_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $map = [
        'rating','year','duration','backdrop_url','trailer_url','video_url','subtitle_vtt','actors','director'
    ];
    foreach ($map as $k) {
        $field = 'movie_ui_' . $k;
        if (!isset($_POST[$field])) continue;
        update_post_meta($post_id, $k, sanitize_text_field((string) $_POST[$field]));
    }

    if (isset($_POST['movie_ui_tv_show_id'])) update_post_meta($post_id, 'tv_show_id', (int) $_POST['movie_ui_tv_show_id']);
    if (isset($_POST['movie_ui_season_number'])) update_post_meta($post_id, 'season_number', (int) $_POST['movie_ui_season_number']);
    if (isset($_POST['movie_ui_episode_number'])) update_post_meta($post_id, 'episode_number', (int) $_POST['movie_ui_episode_number']);
});

// Admin columns (thumbnails + rating/year)
add_filter('manage_movie_posts_columns', function ($cols) {
    $cols = array_slice($cols, 0, 1, true) + ['mu_thumb' => 'Poster'] + array_slice($cols, 1, null, true);
    $cols['mu_rating'] = 'Rating';
    $cols['mu_year'] = 'Year';
    return $cols;
});
add_filter('manage_tv_show_posts_columns', function ($cols) {
    $cols = array_slice($cols, 0, 1, true) + ['mu_thumb' => 'Poster'] + array_slice($cols, 1, null, true);
    $cols['mu_rating'] = 'Rating';
    $cols['mu_year'] = 'Year';
    return $cols;
});
add_action('manage_movie_posts_custom_column', 'movie_ui_admin_cols_render', 10, 2);
add_action('manage_tv_show_posts_custom_column', 'movie_ui_admin_cols_render', 10, 2);
function movie_ui_admin_cols_render($col, $post_id) {
    if ($col === 'mu_thumb') {
        $t = get_the_post_thumbnail($post_id, [52, 78], ['style' => 'border-radius:8px;']);
        echo $t ?: '—';
    }
    if ($col === 'mu_rating') echo esc_html(movie_ui_meta($post_id, ['rating','_rating'], '—'));
    if ($col === 'mu_year') echo esc_html(movie_ui_meta($post_id, ['year','_release_year'], '—'));
}

// Tạo trang đăng nhập riêng (không dùng trang wp-login.php mặc định)
add_shortcode('movie_login_form', function() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        return '<p>Xin chào, <strong>' . esc_html($user->display_name) . '</strong>! 
                <a href="' . wp_logout_url(home_url()) . '">Đăng xuất</a></p>';
    }
    ob_start();
    wp_login_form(['redirect' => home_url()]);
    return ob_get_clean();
});

// Shortcode form đăng ký
add_shortcode('movie_register_form', function() {
    if (is_user_logged_in()) return '<p>Bạn đã đăng nhập rồi.</p>';
    ob_start();
    ?>
    <form method="post" style="max-width:400px;">
        <?php wp_nonce_field('movie_register', 'reg_nonce'); ?>
        <input type="hidden" name="action" value="movie_register">
        <p><input type="text" name="username" placeholder="Tên đăng nhập" required style="width:100%;padding:10px;"/></p>
        <p><input type="email" name="email" placeholder="Email" required style="width:100%;padding:10px;"/></p>
        <p><input type="password" name="password" placeholder="Mật khẩu" required style="width:100%;padding:10px;"/></p>
        <p><button type="submit" style="padding:10px 30px;background:#e50914;color:white;border:none;border-radius:4px;">
            Đăng ký
        </button></p>
        <?php if (isset($_GET['reg_error'])): ?>
            <p style="color:red;"><?php echo esc_html(urldecode($_GET['reg_error'])); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['reg_success'])): ?>
            <p style="color:green;">Đăng ký thành công! <a href="<?php echo wp_login_url(); ?>">Đăng nhập</a></p>
        <?php endif; ?>
    </form>
    <?php
    return ob_get_clean();
});

// Xử lý form đăng ký khi submit
add_action('init', function() {
    if (!isset($_POST['action']) || $_POST['action'] !== 'movie_register') return;
    if (!wp_verify_nonce($_POST['reg_nonce'], 'movie_register')) return;

    $username = sanitize_user($_POST['username']);
    $email    = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_redirect(add_query_arg('reg_error', urlencode($user_id->get_error_message()), wp_get_referer()));
    } else {
        wp_redirect(add_query_arg('reg_success', 1, wp_get_referer()));
    }
    exit;
});

function movie_save_watch_history($post_id) {
    // Chỉ chạy với post type 'movie'
    if (get_post_type($post_id) !== 'movie') return;

    global $wpdb;

    $user_id = get_current_user_id(); // 0 nếu chưa đăng nhập

    // Lấy thông tin thiết bị và IP
    $device     = isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 100) : '';
    $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';

    // Chèn bản ghi vào bảng lịch sử xem
    $wpdb->insert(
        $wpdb->prefix . 'movie_watch_history',
        [
            'user_id'    => $user_id,
            'movie_id'   => $post_id,
            'watched_at' => current_time('mysql'), // Thời gian hiện tại theo múi giờ WP
            'device'     => $device,
            'ip_address' => $ip_address,
        ],
        ['%d', '%d', '%s', '%s', '%s'] // Format dữ liệu tương ứng
    );
}

// Móc hàm này vào sự kiện hiển thị trang đơn (single post)
add_action('wp', function() {
    if (is_singular('movie')) {
        movie_save_watch_history(get_the_ID());
    }
});


add_shortcode('movie_watch_history', function() {
    if (!is_user_logged_in()) return '<p>Vui lòng <a href="' . wp_login_url() . '">đăng nhập</a> để xem lịch sử.</p>';

    global $wpdb;
    $user_id = get_current_user_id();

    // Lấy 20 lần xem gần nhất, kèm tên phim
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT h.movie_id, h.watched_at, p.post_title
        FROM {$wpdb->prefix}movie_watch_history h
        LEFT JOIN {$wpdb->posts} p ON p.ID = h.movie_id
        WHERE h.user_id = %d
        ORDER BY h.watched_at DESC
        LIMIT 20
    ", $user_id));

    if (!$rows) return '<p>Bạn chưa xem phim nào.</p>';

    $out = '<ul style="list-style:none;padding:0;">';
    foreach ($rows as $row) {
        $link = get_permalink($row->movie_id);
        $out .= '<li style="padding:10px 0;border-bottom:1px solid #333;">';
        $out .= '<a href="' . esc_url($link) . '">' . esc_html($row->post_title) . '</a>';
        $out .= ' <small style="color:#888;">— ' . date('d/m/Y H:i', strtotime($row->watched_at)) . '</small>';
        $out .= '</li>';
    }
    $out .= '</ul>';
    return $out;
});

// Đăng ký AJAX handler (cho cả user đã đăng nhập)
add_action('wp_ajax_toggle_favorite', 'movie_toggle_favorite');

function movie_toggle_favorite() {
    // Kiểm tra nonce bảo mật
    if (!check_ajax_referer('fav_nonce', 'nonce', false)) {
        wp_send_json_error('Nonce không hợp lệ');
    }

    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }

    global $wpdb;
    $user_id  = get_current_user_id();
    $movie_id = intval($_POST['movie_id']);
    $table    = $wpdb->prefix . 'movie_favorites';

    // Kiểm tra đã yêu thích chưa
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id=%d AND movie_id=%d",
        $user_id, $movie_id
    ));

    if ($exists) {
        // Đã yêu thích → bỏ yêu thích
        $wpdb->delete($table, ['user_id' => $user_id, 'movie_id' => $movie_id]);
        wp_send_json_success(['status' => 'removed']);
    } else {
        // Chưa yêu thích → thêm vào
        $wpdb->insert($table, [
            'user_id'    => $user_id,
            'movie_id'   => $movie_id,
            'created_at' => current_time('mysql'),
        ]);
        wp_send_json_success(['status' => 'added']);
    }
}

add_shortcode('movie_favorites', function() {
    if (!is_user_logged_in()) return '<p>Vui lòng <a href="' . wp_login_url() . '">đăng nhập</a>.</p>';

    global $wpdb;
    $user_id = get_current_user_id();

    $favs = $wpdb->get_results($wpdb->prepare("
        SELECT f.movie_id, p.post_title
        FROM {$wpdb->prefix}movie_favorites f
        LEFT JOIN {$wpdb->posts} p ON p.ID = f.movie_id
        WHERE f.user_id = %d
        ORDER BY f.created_at DESC
    ", $user_id));

    if (!$favs) return '<p>Bạn chưa có phim yêu thích nào.</p>';

    $out = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:15px;">';
    foreach ($favs as $fav) {
        $thumb = get_the_post_thumbnail($fav->movie_id, 'medium', ['style' => 'width:100%;height:240px;object-fit:cover;']);
        $link  = get_permalink($fav->movie_id);
        $out .= '<div style="background:#1a1a1a;border-radius:8px;overflow:hidden;">';
        $out .= '<a href="' . esc_url($link) . '">' . $thumb . '</a>';
        $out .= '<div style="padding:8px;"><a href="' . esc_url($link) . '" style="color:white;text-decoration:none;font-size:13px;">' . esc_html($fav->post_title) . '</a></div>';
        $out .= '</div>';
    }
    $out .= '</div>';
    return $out;
});

// Đăng ký trang admin thống kê
add_action('admin_menu', function() {
    add_menu_page(
        'Thống kê phim',      // Tiêu đề trang
        '📊 Thống kê',         // Tên menu
        'manage_options',     // Quyền: chỉ admin
        'movie-analytics',    // Slug (URL: /wp-admin/admin.php?page=movie-analytics)
        'movie_analytics_page', // Hàm render trang
        'dashicons-chart-bar', // Icon
        25                    // Vị trí trong menu
    );
});

function movie_analytics_page() {
    global $wpdb;

    // ---- Truy vấn dữ liệu ----

    // Top 10 phim được xem nhiều nhất
    $top_movies = $wpdb->get_results("
        SELECT movie_id, COUNT(*) as total, p.post_title
        FROM {$wpdb->prefix}movie_watch_history h
        LEFT JOIN {$wpdb->posts} p ON p.ID = h.movie_id
        GROUP BY movie_id
        ORDER BY total DESC
        LIMIT 10
    ");

    // Top thể loại được xem nhiều nhất
    $top_genres = $wpdb->get_results("
        SELECT t.name, COUNT(*) as total
        FROM {$wpdb->prefix}movie_watch_history h
        JOIN {$wpdb->term_relationships} tr ON tr.object_id = h.movie_id
        JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'genre'
        JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
        GROUP BY t.term_id
        ORDER BY total DESC
        LIMIT 8
    ");

    // Top 10 từ khóa tìm kiếm
    $top_keywords = $wpdb->get_results("
        SELECT keyword, COUNT(*) as total
        FROM {$wpdb->prefix}movie_search_logs
        GROUP BY keyword
        ORDER BY total DESC
        LIMIT 10
    ");

    // Tổng số lượt xem hôm nay
    $today_views = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}movie_watch_history
        WHERE DATE(watched_at) = CURDATE()
    ");

    // Dữ liệu cho Chart.js (chuyển sang JSON)
    $movie_labels  = json_encode(array_column($top_movies, 'post_title'));
    $movie_data    = json_encode(array_column($top_movies, 'total'));
    $genre_labels  = json_encode(array_column($top_genres, 'name'));
    $genre_data    = json_encode(array_column($top_genres, 'total'));
    ?>

    <div class="wrap">
        <h1>📊 Thống kê hành vi người dùng</h1>

        <!-- TỔNG QUAN -->
        <div style="display:flex;gap:20px;margin-bottom:30px;flex-wrap:wrap;">
            <div style="background:#fff;padding:20px;border-radius:8px;border-left:4px solid #e50914;min-width:150px;">
                <div style="font-size:32px;font-weight:bold;"><?php echo esc_html($today_views); ?></div>
                <div style="color:#888;">Lượt xem hôm nay</div>
            </div>
            <div style="background:#fff;padding:20px;border-radius:8px;border-left:4px solid #0073aa;min-width:150px;">
                <?php $total_movies = wp_count_posts('movie')->publish; ?>
                <div style="font-size:32px;font-weight:bold;"><?php echo esc_html($total_movies); ?></div>
                <div style="color:#888;">Tổng số phim</div>
            </div>
            <div style="background:#fff;padding:20px;border-radius:8px;border-left:4px solid #46b450;min-width:150px;">
                <?php $total_users = count(get_users(['role' => 'subscriber'])); ?>
                <div style="font-size:32px;font-weight:bold;"><?php echo esc_html($total_users); ?></div>
                <div style="color:#888;">Thành viên</div>
            </div>
        </div>

        <!-- BIỂU ĐỒ -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap;">

            <div style="background:#fff;padding:20px;border-radius:8px;">
                <h3>🎬 Top phim được xem nhiều nhất</h3>
                <canvas id="chartMovies" height="250"></canvas>
            </div>

            <div style="background:#fff;padding:20px;border-radius:8px;">
                <h3>🎭 Thể loại phổ biến</h3>
                <canvas id="chartGenres" height="250"></canvas>
            </div>
        </div>

        <!-- TOP TỪ KHÓA -->
        <div style="background:#fff;padding:20px;border-radius:8px;margin-top:20px;">
            <h3>🔍 Top từ khóa tìm kiếm</h3>
            <table class="widefat">
                <thead><tr><th>#</th><th>Từ khóa</th><th>Số lần tìm</th></tr></thead>
                <tbody>
                    <?php foreach ($top_keywords as $i => $row): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo esc_html($row->keyword); ?></td>
                        <td><?php echo esc_html($row->total); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Nạp Chart.js từ CDN miễn phí -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    // Biểu đồ thanh: Top phim
    new Chart(document.getElementById('chartMovies'), {
        type: 'bar',
        data: {
            labels: <?php echo $movie_labels; ?>,
            datasets: [{
                label: 'Lượt xem',
                data: <?php echo $movie_data; ?>,
                backgroundColor: '#e50914'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // Biểu đồ tròn: Thể loại
    new Chart(document.getElementById('chartGenres'), {
        type: 'doughnut',
        data: {
            labels: <?php echo $genre_labels; ?>,
            datasets: [{
                data: <?php echo $genre_data; ?>,
                backgroundColor: ['#e50914','#0073aa','#46b450','#f56e28','#8e44ad','#16a085','#e67e22','#2c3e50']
            }]
        },
        options: { responsive: true }
    });
    </script>
    <?php
}


function movie_get_recommendations($limit = 8) {
    global $wpdb;
    $user_id = get_current_user_id();

    // ============ GUEST: chỉ gợi ý phim phổ biến ============
    if (!$user_id) {
        return new WP_Query([
            'post_type'      => 'movie',
            'posts_per_page' => $limit,
            'meta_key'       => '_view_count',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ]);
    }

    // ============ MEMBER: content-based + popularity ============

    // Bước 1: Lấy tất cả phim user đã xem
    $watched_ids = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT movie_id
        FROM {$wpdb->prefix}movie_watch_history
        WHERE user_id = %d
    ", $user_id));

    // Bước 2: Đếm thể loại theo lịch sử xem
    if (!empty($watched_ids)) {
        $ids_placeholder = implode(',', array_map('intval', $watched_ids));

        $genre_counts = $wpdb->get_results("
            SELECT tt.term_id, t.name, COUNT(*) as cnt
            FROM {$wpdb->term_relationships} tr
            JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'genre'
            JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tr.object_id IN ($ids_placeholder)
            GROUP BY tt.term_id
            ORDER BY cnt DESC
            LIMIT 3
        ");

        // Lấy ID của top 3 thể loại ưa thích
        $fav_genre_ids = array_column($genre_counts, 'term_id');
    }

    // Bước 3: Xây dựng query gợi ý
    $args = [
        'post_type'      => 'movie',
        'posts_per_page' => $limit,
        'post__not_in'   => $watched_ids ?: [0], // Loại trừ phim đã xem
        'orderby'        => ['meta_value_num' => 'DESC', 'date' => 'DESC'],
        'meta_key'       => '_view_count',
    ];

    // Nếu có thể loại ưa thích → ưu tiên gợi ý theo thể loại đó
    if (!empty($fav_genre_ids)) {
        $args['tax_query'] = [[
            'taxonomy' => 'genre',
            'field'    => 'term_id',
            'terms'    => $fav_genre_ids,
            'operator' => 'IN',
        ]];
    }

    return new WP_Query($args);
}

// Shortcode hiển thị "Gợi ý cho bạn" trên trang chủ
add_shortcode('movie_recommendations', function() {
    $query = movie_get_recommendations(8);
    if (!$query->have_posts()) return '';

    $out = '<section style="margin:40px 0;">';
    $out .= '<h2 style="margin-bottom:20px;">🎯 ' . (is_user_logged_in() ? 'Gợi ý cho bạn' : 'Phim phổ biến') . '</h2>';
    $out .= '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:15px;">';

    while ($query->have_posts()) {
        $query->the_post();
        $rating = get_post_meta(get_the_ID(), '_rating', true);
        $out .= '<div style="background:#1a1a1a;border-radius:8px;overflow:hidden;">';
        $out .= '<a href="' . get_permalink() . '">';
        $out .= get_the_post_thumbnail(null, 'medium', ['style' => 'width:100%;height:240px;object-fit:cover;']);
        $out .= '</a>';
        $out .= '<div style="padding:8px;">';
        $out .= '<a href="' . get_permalink() . '" style="color:white;text-decoration:none;font-size:13px;">' . get_the_title() . '</a>';
        if ($rating) $out .= '<div style="color:#f5c518;font-size:12px;">⭐ ' . esc_html($rating) . '</div>';
        $out .= '</div></div>';
    }
    wp_reset_postdata();

    $out .= '</div></section>';
    return $out;
});


// BẢNG ÁNH XẠ TỪ KHÓA -> GIÁ TRỊ
function movie_get_keyword_maps() {
    return [
        // Thể loại
        'genres' => [
            'hành động' => 'hanh-dong', 'action'    => 'hanh-dong',
            'tình cảm'  => 'tinh-cam',  'lãng mạn'  => 'tinh-cam', 'romance' => 'tinh-cam',
            'kinh dị'   => 'kinh-di',   'horror'    => 'kinh-di',   'ma'      => 'kinh-di',
            'hài hước'  => 'hai-huoc',  'comedy'    => 'hai-huoc',  'hài'     => 'hai-huoc',
            'khoa học viễn tưởng' => 'khoa-hoc-vien-tuong', 'sci-fi' => 'khoa-hoc-vien-tuong',
            'tâm lý'    => 'tam-ly',    'thriller'  => 'tam-ly',
            'hoạt hình' => 'hoat-hinh', 'anime'     => 'hoat-hinh',
            'võ thuật'  => 'vo-thuat',  'martial arts' => 'vo-thuat',
        ],
        // Quốc gia
        'countries' => [
            'hàn quốc' => 'han-quoc', 'korea'   => 'han-quoc', 'hàn'   => 'han-quoc',
            'nhật bản' => 'nhat-ban', 'japan'   => 'nhat-ban', 'nhật'  => 'nhat-ban',
            'mỹ'       => 'my',       'america' => 'my',       'hollywood' => 'my',
            'trung quốc' => 'trung-quoc', 'china' => 'trung-quoc', 'trung' => 'trung-quoc',
            'việt nam' => 'viet-nam', 'việt'    => 'viet-nam',
            'thái lan' => 'thai-lan', 'thailand'=> 'thai-lan',
        ],
    ];
}

// HÀM PHÂN TÍCH CÂU TÌM KIẾM
function movie_parse_smart_query($query_string) {
    $query_lower = mb_strtolower($query_string, 'UTF-8');
    $maps        = movie_get_keyword_maps();
    $result      = ['genre' => null, 'country' => null, 'year' => null, 'actor' => null, 'keyword' => $query_string];

    // Tìm thể loại
    foreach ($maps['genres'] as $kw => $slug) {
        if (mb_strpos($query_lower, $kw) !== false) {
            $result['genre'] = $slug;
            break;
        }
    }

    // Tìm quốc gia
    foreach ($maps['countries'] as $kw => $slug) {
        if (mb_strpos($query_lower, $kw) !== false) {
            $result['country'] = $slug;
            break;
        }
    }

    // Tìm năm (4 chữ số từ 1900-2099)
    if (preg_match('/\b(19|20)\d{2}\b/', $query_string, $m)) {
        $result['year'] = $m[0];
    }

    // Tìm tên diễn viên (chữ hoa đầu, 2+ từ — heuristic đơn giản)
    if (preg_match('/(?:diễn viên|actor|starring)\s+([A-ZÀÁẢÃẠĂẮẶẴẶÂẤẦẨẪẬĐÈÉẺẼẸÊẾỀỂỄỆÌÍỈĨỊÒÓỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢÙÚỦŨỤƯỨỪỬỮỰỲÝỶỸỴ][a-zàáảãạăắặẵặâấầẩẫậđèéẻẽẹêếềểễệìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳýỷỹỵ]+(?:\s+[A-ZÀÁẢÃẠĂẮẶẴẶÂẤẦẨẪẬĐÈÉẺẼẸÊẾỀỂỄỆÌÍỈĨỊÒÓỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢÙÚỦŨỤƯỨỪỬỮỰỲÝỶỸỴ][a-zàáảãạăắặẵặâấầẩẫậđèéẻẽẹêếềểễệìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳýỷỹỵ]+)*)/u', $query_string, $m)) {
        $result['actor'] = $m[1];
    }

    return $result;
}

//  FORM + KẾT QUẢ TÌM KIẾM
add_shortcode('movie_smart_search', function() {
    global $wpdb;
    ob_start();
    ?>
    <!-- Form tìm kiếm AI-like -->
    <div style="max-width:700px;margin:0 auto;">
        <form method="GET" action="">
            <div style="display:flex;gap:10px;margin-bottom:20px;">
                <input type="text" name="ai_search"
                       placeholder="VD: Phim hành động Hàn Quốc năm 2023, Phim tình cảm nhẹ nhàng..."
                       value="<?php echo isset($_GET['ai_search']) ? esc_attr($_GET['ai_search']) : ''; ?>"
                       style="flex:1;padding:12px;font-size:15px;border-radius:6px;border:2px solid #e50914;"/>
                <button type="submit" style="padding:12px 25px;background:#e50914;color:white;border:none;border-radius:6px;font-size:15px;cursor:pointer;">
                    🔍 Tìm kiếm
                </button>
            </div>
        </form>

        <?php if (!empty($_GET['ai_search'])): ?>
            <?php
            $raw     = sanitize_text_field($_GET['ai_search']);
            $parsed  = movie_parse_smart_query($raw);

            // Lưu log tìm kiếm
            $wpdb->insert($wpdb->prefix . 'movie_search_logs', [
                'user_id'     => get_current_user_id(),
                'keyword'     => $raw,
                'searched_at' => current_time('mysql'),
            ]);

            // Hiển thị những gì hệ thống hiểu được
            echo '<div style="background:#1a1a2e;padding:12px;border-radius:6px;margin-bottom:20px;font-size:13px;color:#aaa;">';
            echo '🤖 <strong style="color:white;">Hệ thống hiểu:</strong> ';
            if ($parsed['genre'])   echo ' Thể loại: <span style="color:#e50914;">' . esc_html($parsed['genre']) . '</span>';
            if ($parsed['country']) echo ' | Quốc gia: <span style="color:#e50914;">' . esc_html($parsed['country']) . '</span>';
            if ($parsed['year'])    echo ' | Năm: <span style="color:#e50914;">' . esc_html($parsed['year']) . '</span>';
            if ($parsed['actor'])   echo ' | Diễn viên: <span style="color:#e50914;">' . esc_html($parsed['actor']) . '</span>';
            echo '</div>';

            // Xây dựng WP_Query từ dữ liệu đã phân tích
            $args = [
                'post_type'      => 'movie',
                'posts_per_page' => 12,
                's'              => $raw, // Tìm kiếm fulltext WordPress
            ];

            if ($parsed['genre']) {
                $args['tax_query'][] = ['taxonomy' => 'genre',   'field' => 'slug', 'terms' => $parsed['genre']];
            }
            if ($parsed['country']) {
                $args['tax_query'][] = ['taxonomy' => 'country', 'field' => 'slug', 'terms' => $parsed['country']];
            }
            if ($parsed['year']) {
                $args['meta_query'][] = ['key' => '_release_year', 'value' => $parsed['year'], 'type' => 'NUMERIC'];
            }
            if ($parsed['actor']) {
                $args['meta_query'][] = ['key' => '_actors', 'value' => $parsed['actor'], 'compare' => 'LIKE'];
            }

            $results = new WP_Query($args);
            ?>

            <p style="color:#888;">Tìm thấy <strong style="color:white;"><?php echo $results->found_posts; ?></strong> phim.</p>

            <?php if ($results->have_posts()): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:15px;">
                    <?php while ($results->have_posts()): $results->the_post(); ?>
                        <div style="background:#1a1a1a;border-radius:8px;overflow:hidden;">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium', ['style' => 'width:100%;height:240px;object-fit:cover;']); ?>
                            </a>
                            <div style="padding:8px;">
                                <a href="<?php the_permalink(); ?>" style="color:white;text-decoration:none;font-size:13px;">
                                    <?php the_title(); ?>
                                </a>
                                <div style="color:#888;font-size:11px;"><?php echo esc_html(get_post_meta(get_the_ID(), '_release_year', true)); ?></div>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else: ?>
                <p style="color:#888;">Không tìm thấy phim nào phù hợp. Thử từ khóa khác nhé!</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * ============================================================
 * Modern streaming UI (Netflix-style) — Astra Child
 * Shortcodes:
 * - [ms_home]
 * - [ms_search]
 * - [ms_favorites_history]
 * - [ms_player] (expects ?movie_id=123 or uses current movie)
 * ============================================================
 */

/**
 * Legacy [ms_*] shortcode assets — load only when needed (avoids duplicate Swiper/nav with movie-ui).
 */
function movie_ui_should_enqueue_ms_assets() : bool {
    if (!is_singular()) {
        return false;
    }
    $post = get_post();
    if (!$post instanceof WP_Post) {
        return false;
    }
    $content = (string) $post->post_content;
    foreach (['ms_home', 'ms_search', 'ms_favorites_history', 'ms_player'] as $sc) {
        if (has_shortcode($content, $sc)) {
            return true;
        }
    }
    return false;
}

add_action('wp_enqueue_scripts', function () {
    if (!movie_ui_should_enqueue_ms_assets()) {
        return;
    }
    $uri = get_stylesheet_directory_uri();
    $ver = wp_get_theme()->get('Version') ?: '1.0.0';

    wp_enqueue_style('ms-ui', $uri . '/assets/ms-ui.css', ['parent-style'], $ver);
    wp_enqueue_script('ms-ui', $uri . '/assets/ms-ui.js', ['swiper'], $ver, true);

    wp_localize_script('ms-ui', 'MS_UI', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ms_ui_nonce'),
        'favNonce' => wp_create_nonce('fav_nonce'),
        'isLoggedIn' => is_user_logged_in(),
        'homeUrl' => home_url('/'),
    ]);
}, 25);

add_filter('body_class', function (array $classes) {
    $has_ms_ui_shortcode = movie_ui_should_enqueue_ms_assets();
    if ($has_ms_ui_shortcode) {
        $classes[] = 'ms-ui';
        $classes[] = 'ms-ui--no-sidebar';
        $classes[] = 'movie-ui';
        $classes[] = 'movie-ui--no-sidebar';
    }
    return $classes;
});

function ms_ui_render_movie_card(int $post_id, array $opts = []) : string {
    $title = get_the_title($post_id);
    $link  = get_permalink($post_id);
    $year  = get_post_meta($post_id, '_release_year', true);
    $rating = get_post_meta($post_id, '_rating', true);

    $thumb = get_the_post_thumbnail($post_id, 'medium_large', [
        'class' => 'ms-card__img',
        'loading' => 'lazy',
        'decoding' => 'async',
    ]);

    if (!$thumb) {
        $thumb = '<div class="ms-card__img ms-card__img--placeholder"></div>';
    }

    $meta_bits = [];
    if ($year) $meta_bits[] = esc_html($year);
    if ($rating) $meta_bits[] = '★ ' . esc_html($rating);
    $meta = $meta_bits ? implode(' • ', $meta_bits) : '';

    $watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));
    $watch_href = add_query_arg('movie_id', $post_id, $watch_base);

    return '<article class="ms-card" data-movie-id="' . esc_attr((string)$post_id) . '">' .
        '<a class="ms-card__link" href="' . esc_url($link) . '" aria-label="' . esc_attr($title) . '">' .
            $thumb .
            '<div class="ms-card__shade"></div>' .
            '<div class="ms-card__overlay" aria-hidden="true">' .
                '<div class="ms-card__title">' . esc_html($title) . '</div>' .
                ($meta ? '<div class="ms-card__meta">' . $meta . '</div>' : '') .
                '<div class="ms-card__actions">' .
                    '<a class="ms-btn ms-btn--icon" href="' . esc_url($watch_href) . '" aria-label="Play">' .
                        '<span class="ms-ico ms-ico--play" aria-hidden="true"></span>' .
                    '</a>' .
                    '<button class="ms-btn ms-btn--ghost ms-fav-toggle" type="button" aria-label="Favorite">' .
                        '<span class="ms-ico ms-ico--plus" aria-hidden="true"></span>' .
                    '</button>' .
                '</div>' .
            '</div>' .
        '</a>' .
    '</article>';
}

function ms_ui_render_slider(string $title, WP_Query $q, string $id) : string {
    if (!$q->have_posts()) return '';

    $out = '<section class="ms-row" id="' . esc_attr($id) . '">';
    $out .= '<div class="ms-row__head">';
    $out .= '<h2 class="ms-row__title">' . esc_html($title) . '</h2>';
    $out .= '<div class="ms-row__nav">';
    $out .= '<button class="ms-nav ms-nav--prev" type="button" aria-label="Previous"></button>';
    $out .= '<button class="ms-nav ms-nav--next" type="button" aria-label="Next"></button>';
    $out .= '</div></div>';

    $out .= '<div class="swiper ms-swiper" data-ms-swiper="row">';
    $out .= '<div class="swiper-wrapper">';
    while ($q->have_posts()) {
        $q->the_post();
        $out .= '<div class="swiper-slide ms-slide">' . ms_ui_render_movie_card(get_the_ID()) . '</div>';
    }
    wp_reset_postdata();
    $out .= '</div></div></section>';
    return $out;
}

function ms_ui_query_movies(array $args = []) : WP_Query {
    $base = [
        'post_type'           => 'movie',
        'post_status'         => 'publish',
        'posts_per_page'      => 12,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ];
    return new WP_Query(array_merge($base, $args));
}

add_shortcode('ms_home', function () {
    $hero_q = ms_ui_query_movies([
        'posts_per_page' => 1,
        'meta_key'       => '_view_count',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ]);

    $hero_id = $hero_q->have_posts() ? (int)$hero_q->posts[0]->ID : 0;
    $hero_bg = $hero_id ? get_the_post_thumbnail_url($hero_id, 'full') : '';
    $hero_title = $hero_id ? get_the_title($hero_id) : 'Featured';
    $hero_desc  = $hero_id ? wp_strip_all_tags(get_post_field('post_content', $hero_id)) : '';
    $hero_desc  = $hero_desc ? wp_trim_words($hero_desc, 22, '…') : '';
    $hero_watch = $hero_id ? add_query_arg('movie_id', $hero_id, home_url('/watch/')) : '#';
    $hero_link  = $hero_id ? get_permalink($hero_id) : '#';

    $trending = ms_ui_query_movies([
        'meta_key' => '_view_count',
        'orderby'  => 'meta_value_num',
        'order'    => 'DESC',
    ]);

    $top_rated = ms_ui_query_movies([
        'meta_key' => '_rating',
        'orderby'  => 'meta_value_num',
        'order'    => 'DESC',
    ]);

    $popular = ms_ui_query_movies([
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);

    // Anime: tries genre slug "anime" or "hoat-hinh" (your mapping uses "hoat-hinh")
    $anime = ms_ui_query_movies([
        'tax_query' => [[
            'taxonomy' => 'genre',
            'field'    => 'slug',
            'terms'    => ['anime', 'hoat-hinh'],
            'operator' => 'IN',
        ]],
    ]);

    // Continue watching (logged-in): last watched unique movies
    $continue_ids = [];
    if (is_user_logged_in()) {
        global $wpdb;
        $uid = get_current_user_id();
        $continue_ids = $wpdb->get_col($wpdb->prepare("
            SELECT movie_id
            FROM {$wpdb->prefix}movie_watch_history
            WHERE user_id = %d
            GROUP BY movie_id
            ORDER BY MAX(watched_at) DESC
            LIMIT 20
        ", $uid));
    }
    $continue = $continue_ids ? ms_ui_query_movies([
        'post__in' => array_map('intval', $continue_ids),
        'orderby'  => 'post__in',
        'posts_per_page' => 20,
    ]) : new WP_Query([]);

    ob_start();
    ?>
    <div class="ms-page ms-home ms-ui">
        <header class="ms-header" data-ms-header>
            <div class="ms-header__inner">
                <a class="ms-brand" href="<?php echo esc_url(home_url('/')); ?>">
                    <span class="ms-brand__mark">M</span><span class="ms-brand__text">Movie</span>
                </a>
                <nav class="ms-navlinks" aria-label="Primary">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                    <a href="<?php echo esc_url(trailingslashit(home_url('movies'))); ?>">Browse</a>
                    <a href="<?php echo esc_url(home_url('/search/')); ?>">Search</a>
                    <a href="<?php echo esc_url(home_url('/favorites/')); ?>">My List</a>
                </nav>
                <div class="ms-header__right">
                    <button class="ms-quicksearch" type="button" data-ms-open-search aria-label="Search"></button>
                    <?php if (is_user_logged_in()): ?>
                        <a class="ms-avatar" href="<?php echo esc_url(admin_url('profile.php')); ?>" aria-label="Profile"></a>
                    <?php else: ?>
                        <a class="ms-btn ms-btn--ghost" href="<?php echo esc_url(wp_login_url(home_url('/'))); ?>">Sign in</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <section class="ms-hero">
            <div class="ms-hero__bg" style="<?php echo $hero_bg ? 'background-image:url(' . esc_url($hero_bg) . ');' : ''; ?>"></div>
            <div class="ms-hero__grad"></div>
            <div class="ms-hero__content">
                <h1 class="ms-hero__title"><?php echo esc_html($hero_title); ?></h1>
                <?php if ($hero_desc): ?><p class="ms-hero__desc"><?php echo esc_html($hero_desc); ?></p><?php endif; ?>
                <div class="ms-hero__cta">
                    <a class="ms-btn ms-btn--primary" href="<?php echo esc_url($hero_watch); ?>">
                        <span class="ms-ico ms-ico--play" aria-hidden="true"></span> Watch Now
                    </a>
                    <a class="ms-btn ms-btn--ghost" href="<?php echo esc_url($hero_link); ?>">
                        More Info
                    </a>
                </div>
            </div>
        </section>

        <main class="ms-main">
            <?php echo ms_ui_render_slider('Trending', $trending, 'ms-trending'); ?>
            <?php if ($continue && $continue->have_posts()): ?>
                <?php echo ms_ui_render_slider('Continue Watching', $continue, 'ms-continue'); ?>
            <?php endif; ?>
            <?php echo ms_ui_render_slider('Popular', $popular, 'ms-popular'); ?>
            <?php echo ms_ui_render_slider('Anime', $anime, 'ms-anime'); ?>
            <?php echo ms_ui_render_slider('Top Rated', $top_rated, 'ms-top-rated'); ?>
        </main>

        <div class="ms-search-modal" data-ms-search-modal aria-hidden="true">
            <div class="ms-search-modal__panel">
                <div class="ms-searchbar">
                    <input class="ms-input" type="search" placeholder="Search movies…" data-ms-search-input />
                    <button class="ms-btn ms-btn--ghost" type="button" data-ms-close-search>Close</button>
                </div>
                <div class="ms-search-results" data-ms-search-results>
                    <div class="ms-skeleton-grid" data-ms-skeleton>
                        <?php for ($i=0;$i<10;$i++): ?>
                            <div class="ms-skeleton-card"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <div class="ms-search-modal__backdrop" data-ms-close-search></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

add_action('wp_ajax_ms_search_movies', 'ms_ui_ajax_search_movies');
add_action('wp_ajax_nopriv_ms_search_movies', 'ms_ui_ajax_search_movies');
function ms_ui_ajax_search_movies() {
    if (!check_ajax_referer('ms_ui_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'bad_nonce'], 403);
    }
    $q = isset($_POST['q']) ? sanitize_text_field((string)$_POST['q']) : '';
    if (mb_strlen($q) < 2) {
        wp_send_json_success(['html' => '']);
    }
    $res = ms_ui_query_movies([
        'posts_per_page' => 18,
        's' => $q,
    ]);

    $html = '';
    if ($res->have_posts()) {
        $html .= '<div class="ms-grid">';
        while ($res->have_posts()) {
            $res->the_post();
            $html .= ms_ui_render_movie_card(get_the_ID());
        }
        wp_reset_postdata();
        $html .= '</div>';
    } else {
        $html = '<div class="ms-empty">No results.</div>';
    }

    wp_send_json_success(['html' => $html]);
}

add_shortcode('ms_search', function () {
    ob_start();
    ?>
    <div class="ms-page ms-search ms-ui">
        <div class="ms-container">
            <h1 class="ms-h1">Search</h1>
            <div class="ms-searchbar ms-searchbar--page">
                <input class="ms-input" type="search" placeholder="Search by title…" data-ms-search-input />
                <button class="ms-btn ms-btn--primary" type="button" data-ms-run-search>Search</button>
            </div>
            <div class="ms-search-results" data-ms-search-results>
                <div class="ms-skeleton-grid" data-ms-skeleton>
                    <?php for ($i=0;$i<12;$i++): ?>
                        <div class="ms-skeleton-card"></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

add_shortcode('ms_favorites_history', function () {
    if (!is_user_logged_in()) {
        return '<div class="ms-page ms-ui"><div class="ms-container"><div class="ms-empty">Please <a href="' . esc_url(wp_login_url(home_url('/favorites/'))) . '">sign in</a> to view your list.</div></div></div>';
    }

    global $wpdb;
    $uid = get_current_user_id();

    $fav_ids = $wpdb->get_col($wpdb->prepare("
        SELECT movie_id
        FROM {$wpdb->prefix}movie_favorites
        WHERE user_id = %d
        ORDER BY created_at DESC
        LIMIT 60
    ", $uid));

    $hist_ids = $wpdb->get_col($wpdb->prepare("
        SELECT movie_id
        FROM {$wpdb->prefix}movie_watch_history
        WHERE user_id = %d
        GROUP BY movie_id
        ORDER BY MAX(watched_at) DESC
        LIMIT 60
    ", $uid));

    $fav_q  = $fav_ids ? ms_ui_query_movies(['post__in' => array_map('intval', $fav_ids), 'orderby' => 'post__in', 'posts_per_page' => 60]) : new WP_Query([]);
    $hist_q = $hist_ids ? ms_ui_query_movies(['post__in' => array_map('intval', $hist_ids), 'orderby' => 'post__in', 'posts_per_page' => 60]) : new WP_Query([]);

    ob_start();
    ?>
    <div class="ms-page ms-ui ms-library">
        <div class="ms-container">
            <h1 class="ms-h1">My List</h1>

            <div class="ms-tabs" data-ms-tabs>
                <button class="ms-tab is-active" type="button" data-ms-tab="favorites">Favorites</button>
                <button class="ms-tab" type="button" data-ms-tab="history">History</button>
            </div>

            <div class="ms-tabpanes">
                <section class="ms-pane is-active" data-ms-pane="favorites">
                    <?php if ($fav_q->have_posts()): ?>
                        <div class="ms-grid">
                            <?php while ($fav_q->have_posts()): $fav_q->the_post(); echo ms_ui_render_movie_card(get_the_ID()); endwhile; wp_reset_postdata(); ?>
                        </div>
                    <?php else: ?>
                        <div class="ms-empty">No favorites yet.</div>
                    <?php endif; ?>
                </section>
                <section class="ms-pane" data-ms-pane="history">
                    <?php if ($hist_q->have_posts()): ?>
                        <div class="ms-grid">
                            <?php while ($hist_q->have_posts()): $hist_q->the_post(); echo ms_ui_render_movie_card(get_the_ID()); endwhile; wp_reset_postdata(); ?>
                        </div>
                    <?php else: ?>
                        <div class="ms-empty">Nothing watched yet.</div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

add_shortcode('ms_player', function () {
    $movie_id = 0;
    if (isset($_GET['movie_id'])) $movie_id = (int) $_GET['movie_id'];
    if (!$movie_id && is_singular('movie')) $movie_id = get_the_ID();
    if (!$movie_id) return '<div class="ms-empty">Missing movie.</div>';

    $video_url = (string) get_post_meta($movie_id, '_video_url', true);
    $subtitle_vtt = (string) get_post_meta($movie_id, '_subtitle_vtt', true); // optional
    $title = get_the_title($movie_id);

    // Next episode support (optional meta)
    $next_id = (int) get_post_meta($movie_id, '_next_movie_id', true);
    $wbase = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));
    $next_href = $next_id ? add_query_arg('id', $next_id, $wbase) : '';

    ob_start();
    ?>
    <div class="ms-player ms-ui" data-ms-player>
        <div class="ms-player__top">
            <a class="ms-player__back" href="<?php echo esc_url(get_permalink($movie_id)); ?>">Back</a>
            <div class="ms-player__title"><?php echo esc_html($title); ?></div>
            <?php if ($next_href): ?>
                <a class="ms-player__next" href="<?php echo esc_url($next_href); ?>">Next</a>
            <?php endif; ?>
        </div>

        <div class="ms-player__stage">
            <?php if (!$video_url): ?>
                <div class="ms-empty">No video URL configured.</div>
            <?php else: ?>
                <?php if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false): ?>
                    <?php
                    $video_id = '';
                    if (preg_match('/[?&]v=([^&#]+)/', $video_url, $m)) $video_id = $m[1];
                    elseif (preg_match('/youtu\.be\/([^?&#]+)/', $video_url, $m)) $video_id = $m[1];
                    ?>
                    <div class="ms-player__embed">
                        <iframe
                            src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>?autoplay=1&modestbranding=1&rel=0"
                            allow="autoplay; fullscreen; picture-in-picture"
                            allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <video class="ms-player__video" playsinline controls controlsList="nodownload" preload="metadata">
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                        <?php if ($subtitle_vtt): ?>
                            <track kind="subtitles" srclang="en" label="English" src="<?php echo esc_url($subtitle_vtt); ?>" default>
                        <?php endif; ?>
                    </video>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// ============================================================
// PLAYBACK SOURCES META BOX
// ============================================================
add_action('add_meta_boxes', function () {
    $types = ['movie', 'tv_show', 'episode'];
    foreach ($types as $type) {
        add_meta_box(
            'mu_playback_sources',
            '🎬 Playback Sources',
            'mu_playback_sources_render',
            $type,
            'normal',
            'high'
        );
    }
});

function mu_playback_sources_render($post) {
    wp_nonce_field('mu_playback_sources_nonce', 'mu_playback_sources_nonce');
    $fields = [
        '_video_url'    => ['label' => 'Video URL (MP4)', 'type' => 'url', 'placeholder' => 'https://example.com/movie.mp4'],
        '_embed_url'    => ['label' => 'Embed / iFrame URL', 'type' => 'url', 'placeholder' => 'https://www.youtube.com/embed/XXXX or custom embed'],
        '_hls_url'      => ['label' => 'HLS Stream URL (.m3u8)', 'type' => 'url', 'placeholder' => 'https://example.com/stream.m3u8'],
        '_trailer_url'  => ['label' => 'Trailer URL (YouTube embed or MP4)', 'type' => 'url', 'placeholder' => 'https://www.youtube.com/embed/XXXX'],
        '_subtitle_vtt' => ['label' => 'Subtitle URL (.vtt)', 'type' => 'url', 'placeholder' => 'https://example.com/subtitle.vtt'],
        '_intro_start'  => ['label' => 'Intro Start (seconds)', 'type' => 'number', 'placeholder' => '0'],
        '_intro_end'    => ['label' => 'Intro End (seconds)', 'type' => 'number', 'placeholder' => '85'],
    ];

    echo '<style>
        .mu-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 4px 0; }
        .mu-meta-field label { display: block; font-weight: 600; font-size: 12px; color: #555; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
        .mu-meta-field input { width: 100%; }
        .mu-meta-note { font-size: 12px; color: #888; margin-top: 12px; padding: 8px 12px; background: #f8f8f8; border-left: 3px solid #e50914; border-radius: 2px; }
    </style>';

    echo '<div class="mu-meta-grid">';
    foreach ($fields as $key => $cfg) {
        $val = esc_attr(get_post_meta($post->ID, $key, true));
        echo '<div class="mu-meta-field">';
        echo '<label for="mu_' . esc_attr($key) . '">' . esc_html($cfg['label']) . '</label>';
        echo '<input type="' . esc_attr($cfg['type']) . '" id="mu_' . esc_attr($key) . '" name="mu_' . esc_attr($key) . '" value="' . $val . '" placeholder="' . esc_attr($cfg['placeholder']) . '">';
        echo '</div>';
    }
    echo '</div>';
    echo '<p class="mu-meta-note">⚠️ Only add legal video sources. TMDB imported data will not be overwritten. Leave blank to show the "no source" message on the watch page.</p>';
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['mu_playback_sources_nonce'])) return;
    if (!wp_verify_nonce($_POST['mu_playback_sources_nonce'], 'mu_playback_sources_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $url_fields = ['_video_url', '_embed_url', '_hls_url', '_trailer_url', '_subtitle_vtt'];
    foreach ($url_fields as $key) {
        $form_key = 'mu_' . $key;
        if (isset($_POST[$form_key])) {
            $val = esc_url_raw(trim($_POST[$form_key]));
            if ($val) {
                update_post_meta($post_id, $key, $val);
            } else {
                delete_post_meta($post_id, $key);
            }
        }
    }

    $num_fields = ['_intro_start', '_intro_end'];
    foreach ($num_fields as $key) {
        $form_key = 'mu_' . $key;
        if (isset($_POST[$form_key])) {
            $val = intval($_POST[$form_key]);
            if ($val > 0) {
                update_post_meta($post_id, $key, $val);
            } else {
                delete_post_meta($post_id, $key);
            }
        }
    }
});

/**
 * ============================================================
 * TOP RATED PAGE - AJAX LOAD MORE
 * ============================================================
 */
add_action('wp_ajax_mu_toprated_load_more', 'mu_toprated_ajax_load_more');
add_action('wp_ajax_nopriv_mu_toprated_load_more', 'mu_toprated_ajax_load_more');
function mu_toprated_ajax_load_more() : void {
    if (!check_ajax_referer('mu_toprated_ajax', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }
    
    $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
    $cat = isset($_POST['cat']) ? sanitize_key((string) $_POST['cat']) : 'all';
    $range = isset($_POST['range']) ? sanitize_key((string) $_POST['range']) : 'all';
    
    $per_page = 24;
    $offset = ($page - 1) * $per_page;
    
    // Post types
    $post_types = ($cat === 'all') ? ['movie', 'tv_show'] : [$cat === 'movies' ? 'movie' : 'tv_show'];
    
    // Date range
    $date_query = [];
    $now = new DateTimeImmutable('now', wp_timezone());
    if ($range === 'year') {
        $date_query = [['after' => $now->modify('-1 year')->format('Y-m-d') . ' 00:00:00']];
    } elseif ($range === 'month') {
        $date_query = [['after' => $now->modify('-1 month')->format('Y-m-d') . ' 00:00:00']];
    } elseif ($range === 'week') {
        $date_query = [['after' => $now->modify('-1 week')->format('Y-m-d') . ' 00:00:00']];
    }
    
    // Count total
    $count_args = [
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'date_query'     => $date_query,
        'fields'         => 'ids',
        'ignore_sticky_posts' => true,
    ];
    $total_count = count(get_posts($count_args));
    $total_pages = ceil($total_count / $per_page);
    
    // Main query
    $args = [
        'post_type'           => $post_types,
        'posts_per_page'       => $per_page,
        'offset'               => $offset,
        'post_status'          => 'publish',
        'meta_query'           => [
            'relation' => 'OR',
            ['key' => '_rating', 'compare' => 'EXISTS'],
            ['key' => '_tmdb_rating', 'compare' => 'EXISTS'],
            ['key' => '_imdb_rating', 'compare' => 'EXISTS'],
        ],
        'orderby' => [
            '_rating'      => 'DESC',
            '_tmdb_rating' => 'DESC',
            '_imdb_rating' => 'DESC',
            'modified'    => 'DESC',
        ],
        'order'              => 'DESC',
        'date_query'         => $date_query,
        'ignore_sticky_posts' => true,
    ];
    
    $q = new WP_Query($args);
    
    if (!$q->have_posts()) {
        wp_send_json_success([
            'success'    => true,
            'html'       => '',
            'has_more'   => false,
            'total_count' => $total_count,
            'total_shown' => $offset,
        ]);
    }
    
    ob_start();
    
    $rank = $offset + 1;
    $watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));
    
    while ($q->have_posts()) : $q->the_post();
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
        $play_action = $trailer ? 'trailer:' . esc_attr($trailer) : ($video ? 'watch:' . esc_url($watch_url) : '');
        $type_label = $ptype === 'tv_show' ? __('TV', 'astra-child') : __('Movie', 'astra-child');
        
        $has_more = $page < $total_pages;
        $next_rank = $rank + $per_page;
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
    
    $html = ob_get_clean();
    
    wp_send_json_success([
        'success'     => true,
        'html'         => $html,
        'has_more'     => $has_more,
        'next_page'    => $page + 1,
        'total_count'  => $total_count,
        'total_shown'  => min($next_rank, $total_count),
    ]);
}