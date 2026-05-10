<?php
/**
 * Template Name: Movies Page
 * Premium OTT Streaming Platform
 * Custom page for /movies/ route
 */
defined('ABSPATH') || exit;

get_header();
get_template_part('template-parts/streaming/header');

// ============================================================
// URL PARAMETERS & STATE
// ============================================================
$current_genre = isset($_GET['genre']) ? sanitize_text_field(wp_unslash($_GET['genre'])) : '';
$current_sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'featured';
$search_query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$is_filtered = !empty($current_genre) || !empty($search_query);

// ============================================================
// URLs
// ============================================================
$watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));

// ============================================================
// COUNT
// ============================================================
$movie_count = wp_count_posts('movie');
$total_movies = $movie_count && isset($movie_count->publish) ? (int) $movie_count->publish : 0;

// ============================================================
// GENRES
// ============================================================
$all_genres = get_terms([
    'taxonomy' => 'genre',
    'hide_empty' => true,
    'number' => 20,
]);

$genre_list = [];
foreach ($all_genres as $g) {
    $genre_list[] = [
        'slug' => $g->slug,
        'name' => $g->name,
    ];
}

// ============================================================
// HERO: Featured Movies
// ============================================================
$hero_args = [
    'post_type' => 'movie',
    'posts_per_page' => 5,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
];
$hero_q = new WP_Query($hero_args);

$hero_slides = [];
if ($hero_q->have_posts()) {
    while ($hero_q->have_posts()) {
        $hero_q->the_post();
        $hid = get_the_ID();
        
        $backdrop = '';
        if (function_exists('movie_ui_backdrop_url')) {
            $backdrop = movie_ui_backdrop_url($hid);
        }
        if (!$backdrop) {
            $thumb_id = get_post_thumbnail_id($hid);
            if ($thumb_id) {
                $bg = wp_get_attachment_image_src($thumb_id, 'large');
                $backdrop = $bg[0] ?? '';
            }
        }
        
        $poster = get_the_post_thumbnail_url($hid, 'medium');
        $rating = movie_ui_meta($hid, ['rating', '_rating'], '');
        $year = movie_ui_meta($hid, ['year', '_release_year'], '');
        $age = movie_ui_meta($hid, ['age_rating', '_age_rating'], '');
        $runtime = movie_ui_meta($hid, ['duration', '_duration'], '');
        $quality = movie_ui_meta($hid, ['quality', '_quality'], 'HD');
        $genres = movie_ui_terms_text($hid, 'genre', 2);
        $overview = wp_trim_words(wp_strip_all_tags(get_the_content() ?: get_the_excerpt() ?: ''), 35);
        
        $watch_url = add_query_arg('id', $hid, $watch_base);
        $detail_url = get_permalink($hid);
        
        $hero_slides[] = [
            'id' => $hid,
            'title' => get_the_title(),
            'backdrop' => $backdrop,
            'poster' => $poster,
            'rating' => $rating,
            'year' => $year,
            'age' => $age,
            'runtime' => $runtime,
            'quality' => $quality,
            'genres' => $genres,
            'overview' => $overview,
            'watch_url' => $watch_url,
            'detail_url' => $detail_url,
        ];
    }
    wp_reset_postdata();
}

// ============================================================
// SECTIONS DATA
// ============================================================

// Popular Movies
$popular_q = new WP_Query([
    'post_type' => 'movie',
    'posts_per_page' => 16,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'no_found_rows' => true,
]);

// New Releases
$newrel_q = new WP_Query([
    'post_type' => 'movie',
    'posts_per_page' => 16,
    'orderby' => 'date',
    'order' => 'DESC',
    'no_found_rows' => true,
]);

// Top Rated
$toprated_q = new WP_Query([
    'post_type' => 'movie',
    'posts_per_page' => 16,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'no_found_rows' => true,
]);

// Trending
$trending_q = new WP_Query([
    'post_type' => 'movie',
    'posts_per_page' => 16,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'offset' => 0,
    'no_found_rows' => true,
]);

// ============================================================
// HELPERS
// ============================================================
function mu_render_movie_card_data($post_id, $watch_base) {
    $poster = get_the_post_thumbnail_url($post_id, 'medium');
    $rating = movie_ui_meta($post_id, ['rating', '_rating'], '');
    $year = movie_ui_meta($post_id, ['year', '_release_year'], '');
    $quality = movie_ui_meta($post_id, ['quality', '_quality'], 'HD');
    $runtime = movie_ui_meta($post_id, ['duration', '_runtime'], '');
    
    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'poster' => $poster,
        'rating' => $rating,
        'year' => $year,
        'quality' => $quality,
        'runtime' => $runtime,
        'watch_url' => add_query_arg('id', $post_id, $watch_base),
        'detail_url' => get_permalink($post_id),
    ];
}

function mu_movie_render_card($data) {
    $poster_placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 330"%3E%3Crect fill="%23111" width="220" height="330"/%3E%3C/svg%3E';
    ?>
    <div class="tv-card" data-id="<?php echo esc_attr($data['id']); ?>" data-href="<?php echo esc_url($data['detail_url']); ?>">
        <div class="tv-card__poster">
            <img src="<?php echo esc_url($data['poster'] ?: $poster_placeholder); ?>" 
                 alt="<?php echo esc_attr($data['title']); ?>" 
                 class="tv-card__img"
                 loading="lazy">
            <?php if (!empty($data['quality'])) : ?>
                <span class="tv-card__badge" style="background:rgba(0,0,0,0.8);"><?php echo esc_html($data['quality']); ?></span>
            <?php endif; ?>
            <div class="tv-card__overlay">
                <div class="tv-card__actions">
                    <button class="tv-card__action tv-card__action--play" 
                            data-action="play" 
                            data-href="<?php echo esc_url($data['watch_url']); ?>"
                            aria-label="Play">
                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                    <button class="tv-card__action" 
                            data-action="add-list"
                            aria-label="Add to My List">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/>
                        </svg>
                    </button>
                    <button class="tv-card__action" 
                            data-action="info"
                            aria-label="More Info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="tv-card__info">
            <h3 class="tv-card__title"><?php echo esc_html($data['title']); ?></h3>
            <div class="tv-card__meta">
                <?php if ($data['rating']) : ?>
                    <span class="tv-card__rating">
                        <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?php echo esc_html($data['rating']); ?>
                    </span>
                    <span class="tv-card__meta-dot"></span>
                <?php endif; ?>
                <?php if ($data['year']) : ?>
                    <span><?php echo esc_html(substr($data['year'], 0, 4)); ?></span>
                    <span class="tv-card__meta-dot"></span>
                <?php endif; ?>
                <?php if ($data['runtime']) : ?>
                    <span><?php echo esc_html($data['runtime']); ?> min</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function mu_movie_render_section($title, $query, $watch_base) {
    if (!$query->have_posts()) {
        wp_reset_postdata();
        return;
    }
    ?>
    <section class="tv-section" data-genre="all">
        <div class="tv-section__header">
            <h2 class="tv-section__title"><?php echo esc_html($title); ?></h2>
            <a href="#" class="tv-section__link">
                View all
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </a>
        </div>
        <div class="tv-swiper">
            <div class="tv-swiper__wrapper">
                <?php
                while ($query->have_posts()) :
                    $query->the_post();
                    mu_movie_render_card(mu_render_movie_card_data(get_the_ID(), $watch_base));
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
            <button class="tv-swiper__nav tv-swiper__nav--prev" aria-label="Previous">
                <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
            </button>
            <button class="tv-swiper__nav tv-swiper__nav--next" aria-label="Next">
                <svg viewBox="0 0 24 24"><path d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z"/></svg>
            </button>
        </div>
    </section>
    <?php
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Movies', 'astra-child'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/movie-ui.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/ms-tv-shows.css')); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
</head>
<body class="movie-ui movie-ui--no-sidebar tv-shows-page">
<?php wp_body_open(); ?>

<div class="tv-page">

    <!-- HERO SECTION -->
    <?php if (!empty($hero_slides)) : ?>
    <section class="tv-hero">
        <div class="tv-hero__slider">
            <?php foreach ($hero_slides as $i => $slide) : ?>
            <div class="tv-hero__slide<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($i); ?>">
                <div class="tv-hero__bg" style="background-image: url('<?php echo esc_url($slide['backdrop']); ?>');"></div>
                <div class="tv-hero__content">
                    <div class="tv-hero__badge" style="background:rgba(255,255,255,0.2);">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                        <?php echo esc_html($slide['quality'] ?: 'Movie'); ?>
                    </div>
                    <h1 class="tv-hero__title"><?php echo esc_html($slide['title']); ?></h1>
                    <div class="tv-hero__meta">
                        <?php if ($slide['rating']) : ?>
                            <span class="tv-hero__meta-item tv-hero__meta-item--rating">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="#ffd700"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <?php echo esc_html($slide['rating']); ?>
                            </span>
                            <span class="tv-hero__meta-dot"></span>
                        <?php endif; ?>
                        <?php if ($slide['year']) : ?>
                            <span class="tv-hero__meta-item"><?php echo esc_html(substr($slide['year'], 0, 4)); ?></span>
                            <span class="tv-hero__meta-dot"></span>
                        <?php endif; ?>
                        <?php if ($slide['runtime']) : ?>
                            <span class="tv-hero__meta-item"><?php echo esc_html($slide['runtime']); ?> min</span>
                            <span class="tv-hero__meta-dot"></span>
                        <?php endif; ?>
                        <?php if ($slide['age']) : ?>
                            <span class="tv-hero__age"><?php echo esc_html($slide['age']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($slide['genres'])) : ?>
                    <div class="tv-hero__genres">
                        <?php foreach ($slide['genres'] as $genre) : ?>
                            <span class="tv-hero__genre"><?php echo esc_html($genre); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <p class="tv-hero__desc"><?php echo esc_html($slide['overview']); ?></p>
                    <div class="tv-hero__actions">
                        <a href="<?php echo esc_url($slide['watch_url']); ?>" class="tv-hero__btn tv-hero__btn--primary">
                            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            Play Now
                        </a>
                        <a href="<?php echo esc_url($slide['detail_url']); ?>" class="tv-hero__btn tv-hero__btn--secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            More Info
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($hero_slides) > 1) : ?>
        <div class="tv-hero__dots">
            <?php foreach ($hero_slides as $i => $slide) : ?>
                <button class="tv-hero__dot<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($i); ?>" aria-label="Go to slide <?php echo esc_attr($i + 1); ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- GENRE CHIPS -->
    <div class="tv-genres">
        <button class="tv-genre-chip is-active" data-genre="">All</button>
        <?php foreach ($genre_list as $genre) : ?>
            <button class="tv-genre-chip" data-genre="<?php echo esc_attr($genre['slug']); ?>">
                <?php echo esc_html($genre['name']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- CONTENT SECTIONS -->
    <div class="tv-content">
        <?php mu_movie_render_section('Trending Now', $trending_q, $watch_base); ?>
        <?php mu_movie_render_section('New Releases', $newrel_q, $watch_base); ?>
        <?php mu_movie_render_section('Popular Movies', $popular_q, $watch_base); ?>
        <?php mu_movie_render_section('Top Rated', $toprated_q, $watch_base); ?>
    </div>

    <!-- FEATURES SECTION -->
    <section class="tv-features">
        <div class="tv-feature">
            <div class="tv-feature__icon">
                <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4zm-6.75 11.25L8 12l3.25-3.25L14 12l-2.75 3.25zM16 18H8v-2h8v2z"/></svg>
            </div>
            <h3 class="tv-feature__title">Thousands of Movies</h3>
            <p class="tv-feature__desc">Access an extensive library of movies across all genres</p>
        </div>
        <div class="tv-feature">
            <div class="tv-feature__icon">
                <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14zM9 8l7 4-7 4V8z"/></svg>
            </div>
            <h3 class="tv-feature__title">4K Ultra HD</h3>
            <p class="tv-feature__desc">Crystal clear video quality with HDR support</p>
        </div>
        <div class="tv-feature">
            <div class="tv-feature__icon">
                <svg viewBox="0 0 24 24"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg>
            </div>
            <h3 class="tv-feature__title">Watch Anywhere</h3>
            <p class="tv-feature__desc">Enjoy on TV, tablet, phone or laptop anytime</p>
        </div>
        <div class="tv-feature">
            <div class="tv-feature__icon">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            </div>
            <h3 class="tv-feature__title">No Ads</h3>
            <p class="tv-feature__desc">Experience uninterrupted viewing without advertisements</p>
        </div>
    </section>

</div>

<?php wp_footer(); ?>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="<?php echo esc_url(get_theme_file_uri('assets/js/ms-tv-shows.js')); ?>"></script>
</body>
</html>
