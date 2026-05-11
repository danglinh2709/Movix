<?php
/**
 * TV Shows Archive - Premium Netflix Style
 * Cinematic streaming experience matching reference design
 */
defined('ABSPATH') || exit;

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function tv_unique_by_title($posts) {
    $seen = [];
    $unique = [];
    foreach ($posts as $post) {
        $title = sanitize_title($post->post_title);
        if (!isset($seen[$title])) {
            $seen[$title] = true;
            $unique[] = $post;
        }
    }
    return $unique;
}

function tv_get_season_count($tv_id) {
    $seasons = get_terms([
        'taxonomy' => 'season',
        'hide_empty' => true,
        'meta_query' => [['key' => 'tv_show_id', 'value' => $tv_id]],
    ]);
    return $seasons && !is_wp_error($seasons) ? count($seasons) : 1;
}

function tv_get_first_episode($tv_id) {
    $first_ep = new WP_Query([
        'post_type' => 'episode',
        'posts_per_page' => 1,
        'meta_query' => [['key' => 'tv_show_id', 'value' => $tv_id]],
        'orderby' => ['meta_value_num' => 'ASC'],
        'meta_key' => 'episode_number',
    ]);
    $play_id = 0;
    if ($first_ep->have_posts()) {
        $first_ep->the_post();
        $play_id = get_the_ID();
    }
    wp_reset_postdata();
    return $play_id;
}

// ============================================================
// URLS
// ============================================================
$watch_base = function_exists('mu_get_page_url_by_slug') 
    ? mu_get_page_url_by_slug('watch') 
    : trailingslashit(home_url('watch'));
$movies_url = function_exists('mu_get_page_url_by_slug') 
    ? mu_get_page_url_by_slug('movies') 
    : trailingslashit(home_url('movies'));
$tv_url = get_post_type_archive_link('tv_show') ?: trailingslashit(home_url('tv'));
$search_url = function_exists('mu_get_page_url_by_slug') 
    ? mu_get_page_url_by_slug('search') 
    : trailingslashit(home_url('search'));

// ============================================================
// GENRES LIST
// ============================================================
$all_genres = get_terms([
    'taxonomy' => 'genre',
    'hide_empty' => true,
    'number' => 20,
]);

// ============================================================
// HERO: Top rated TV shows
// ============================================================
$hero_q = movie_ui_query([
    'post_type' => 'tv_show',
    'posts_per_page' => 20,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
$hero_posts = tv_unique_by_title($hero_q->posts);
$hero_posts = array_slice($hero_posts, 0, 5);

$hero_slides = [];
foreach ($hero_posts as $post) {
    setup_postdata($post);
    $hid = $post->ID;
    $year = movie_ui_meta($hid, ['year', '_release_year'], '');
    $rating = movie_ui_meta($hid, ['rating', '_rating'], '');
    $age = movie_ui_meta($hid, ['age_rating', '_age_rating'], '');
    $genres = movie_ui_terms_text($hid, 'genre', 2);
    $backdrop = movie_ui_backdrop_url($hid) ?: get_the_post_thumbnail_url($hid, 'full');
    $overview = wp_trim_words(wp_strip_all_tags($post->post_content ?: $post->post_excerpt ?: ''), 35);
    $season_count = tv_get_season_count($hid);
    $play_id = tv_get_first_episode($hid);
    
    $watch_url = $play_id ? add_query_arg('id', $play_id, $watch_base) : '#';
    $detail_url = get_permalink($hid);
    
    $hero_slides[] = [
        'id' => $hid,
        'title' => get_the_title($hid),
        'year' => $year,
        'rating' => $rating,
        'age' => $age,
        'genres' => $genres,
        'backdrop' => $backdrop,
        'overview' => $overview,
        'seasons' => $season_count,
        'watch_url' => $watch_url,
        'detail_url' => $detail_url,
    ];
}
wp_reset_postdata();

// ============================================================
// CONTENT SECTIONS DATA
// ============================================================

// Top Rated
$toprated_q = movie_ui_query([
    'post_type' => 'tv_show',
    'posts_per_page' => 30,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
$toprated_unique = tv_unique_by_title($toprated_q->posts);
$toprated_unique = array_slice($toprated_unique, 0, 12);

// New Episodes
$new_episodes_q = movie_ui_query([
    'post_type' => 'episode',
    'posts_per_page' => 12,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// Trending
$trending_q = movie_ui_query([
    'post_type' => 'tv_show',
    'posts_per_page' => 30,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
$trending_unique = tv_unique_by_title($trending_q->posts);
$trending_unique = array_slice($trending_unique, 0, 12);

// Popular
$popular_q = movie_ui_query([
    'post_type' => 'tv_show',
    'posts_per_page' => 30,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
$popular_unique = tv_unique_by_title($popular_q->posts);
$popular_unique = array_slice($popular_unique, 0, 12);

// Recently Added
$recent_q = movie_ui_query([
    'post_type' => 'tv_show',
    'posts_per_page' => 30,
    'orderby' => 'date',
    'order' => 'DESC',
]);
$recent_unique = tv_unique_by_title($recent_q->posts);
$recent_unique = array_slice($recent_unique, 0, 12);

// Continue Watching
$continue_ids = [];
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $history = get_user_meta($user_id, 'mu_watch_history', true);
    if (is_array($history) && !empty($history)) {
        $history_ids = array_keys($history);
        $continue_q = movie_ui_query([
            'post_type' => 'tv_show',
            'post__in' => array_map('intval', $history_ids),
            'posts_per_page' => 6,
            'orderby' => 'post__in',
        ]);
        $continue_ids = wp_list_pluck($continue_q->posts, 'ID');
    }
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('TV Shows', 'astra-child'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="tv-page">

<?php get_template_part('template-parts/streaming/header'); ?>

<div class="tv-wrap">

    <!-- HERO -->
    <?php if (!empty($hero_slides)) : ?>
    <section class="tv-hero" id="tvHero">
        <div class="tv-hero__track">
            <?php foreach ($hero_slides as $si => $slide) : ?>
            <div class="tv-hero__slide<?php echo $si === 0 ? ' active' : ''; ?>" data-index="<?php echo esc_attr($si); ?>">
                <div class="tv-hero__bg" style="background-image:url('<?php echo esc_url($slide['backdrop']); ?>')">
                    <div class="tv-hero__shade"></div>
                </div>
                <div class="tv-hero__content">
                    <div class="tv-hero__badges">
                        <span class="tv-hero__badge">
                            <span class="tv-hero__badge-dot"></span>
                            TV SERIES
                        </span>
                        <?php if (!empty($slide['genres'])) : ?>
                        <span class="tv-hero__hd">HD</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="tv-hero__title"><?php echo esc_html($slide['title']); ?></h1>
                    <div class="tv-hero__meta">
                        <?php if (!empty($slide['rating'])) : ?>
                        <span class="tv-hero__rating">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="#46d369"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <?php echo esc_html($slide['rating']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($slide['year'])) : ?>
                        <span class="tv-hero__year"><?php echo esc_html(substr($slide['year'], 0, 4)); ?></span>
                        <?php endif; ?>
                        <span class="tv-hero__seasons"><?php printf(esc_html(_n('%d Season', '%d Seasons', $slide['seasons'], 'astra-child')), $slide['seasons']); ?></span>
                        <?php if (!empty($slide['age'])) : ?>
                        <span class="tv-hero__age"><?php echo esc_html($slide['age']); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="tv-hero__desc"><?php echo esc_html($slide['overview']); ?></p>
                    <div class="tv-hero__buttons">
                        <a href="<?php echo esc_url($slide['watch_url']); ?>" class="tv-hero__btn tv-hero__btn--play">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            Play
                        </a>
                        <a href="<?php echo esc_url($slide['detail_url']); ?>" class="tv-hero__btn tv-hero__btn--info">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            More Info
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="tv-hero__dots">
            <?php foreach ($hero_slides as $si => $slide) : ?>
            <button class="tv-hero__dot<?php echo $si === 0 ? ' active' : ''; ?>" data-index="<?php echo esc_attr($si); ?>"></button>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- MAIN -->
    <main class="tv-main">

        <!-- GENRE BAR -->
        <nav class="tv-genres">
            <div class="tv-genres__inner">
                <button class="tv-genre active" data-genre="">All</button>
                <?php foreach ($all_genres as $g) : ?>
                <button class="tv-genre" data-genre="<?php echo esc_attr($g->slug); ?>"><?php echo esc_html($g->name); ?></button>
                <?php endforeach; ?>
            </div>
        </nav>

        <!-- TOP RATED -->
        <?php if (!empty($toprated_unique)) : ?>
        <section class="tv-section">
            <div class="tv-section__head">
                <h2 class="tv-section__title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#ffd700"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Top Rated TV Shows
                </h2>
                <a href="#" class="tv-section__more">View All</a>
            </div>
            <div class="tv-slider" data-slider="toprated">
                <div class="tv-slider__track">
                    <?php foreach ($toprated_unique as $post) : movie_ui_render_movie_card($post->ID); endforeach; ?>
                </div>
                <button class="tv-slider__btn tv-slider__btn--prev" aria-label="Previous">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button class="tv-slider__btn tv-slider__btn--next" aria-label="Next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </section>
        <?php endif; ?>

        <!-- NEW EPISODES -->
        <?php if ($new_episodes_q->have_posts()) : ?>
        <section class="tv-section">
            <div class="tv-section__head">
                <h2 class="tv-section__title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    New Episodes
                </h2>
                <a href="#" class="tv-section__more">View All</a>
            </div>
            <div class="tv-slider" data-slider="episodes">
                <div class="tv-slider__track">
                    <?php 
                    while ($new_episodes_q->have_posts()) : $new_episodes_q->the_post();
                        $ep_id = get_the_ID();
                        $tv_id = (int) get_post_meta($ep_id, 'tv_show_id', true);
                        $tv_title = $tv_id ? get_the_title($tv_id) : '';
                        $ep_num = get_post_meta($ep_id, 'episode_number', true) ?: 1;
                        $season_num = get_post_meta($ep_id, 'season_number', true) ?: 1;
                        $thumb = get_the_post_thumbnail_url($ep_id, 'medium');
                        if (!$thumb && $tv_id) {
                            $thumb = get_the_post_thumbnail_url($tv_id, 'medium');
                        }
                        $watch_url = add_query_arg('id', $ep_id, $watch_base);
                    ?>
                    <div class="tv-ep-card">
                        <a href="<?php echo esc_url($watch_url); ?>" class="tv-ep-card__link">
                            <div class="tv-ep-card__poster" style="background-image: url('<?php echo esc_url($thumb); ?>')">
                                <div class="tv-ep-card__overlay">
                                    <div class="tv-ep-card__play">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <span class="tv-ep-card__ep">S<?php echo esc_html($season_num); ?>E<?php echo esc_html($ep_num); ?></span>
                            </div>
                            <h3 class="tv-ep-card__title"><?php echo esc_html(get_the_title()); ?></h3>
                            <p class="tv-ep-card__sub"><?php echo esc_html($tv_title); ?></p>
                        </a>
                    </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                <button class="tv-slider__btn tv-slider__btn--prev" aria-label="Previous">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button class="tv-slider__btn tv-slider__btn--next" aria-label="Next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </section>
        <?php endif; ?>

        <!-- TRENDING -->
        <?php if (!empty($trending_unique)) : ?>
        <section class="tv-section">
            <div class="tv-section__head">
                <h2 class="tv-section__title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e50914" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    Trending Now
                </h2>
                <a href="#" class="tv-section__more">View All</a>
            </div>
            <div class="tv-slider" data-slider="trending">
                <div class="tv-slider__track">
                    <?php foreach ($trending_unique as $post) : movie_ui_render_movie_card($post->ID); endforeach; ?>
                </div>
                <button class="tv-slider__btn tv-slider__btn--prev" aria-label="Previous">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button class="tv-slider__btn tv-slider__btn--next" aria-label="Next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </section>
        <?php endif; ?>

        <!-- POPULAR -->
        <?php if (!empty($popular_unique)) : ?>
        <section class="tv-section">
            <div class="tv-section__head">
                <h2 class="tv-section__title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Popular TV Shows
                </h2>
                <a href="#" class="tv-section__more">View All</a>
            </div>
            <div class="tv-slider" data-slider="popular">
                <div class="tv-slider__track">
                    <?php foreach ($popular_unique as $post) : movie_ui_render_movie_card($post->ID); endforeach; ?>
                </div>
                <button class="tv-slider__btn tv-slider__btn--prev" aria-label="Previous">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button class="tv-slider__btn tv-slider__btn--next" aria-label="Next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </section>
        <?php endif; ?>

        <!-- NETWORKS -->
        <section class="tv-section tv-networks">
            <div class="tv-section__head">
                <h2 class="tv-section__title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>
                    Browse by Network
                </h2>
            </div>
            <div class="tv-network-grid">
                <div class="tv-network"><span>Netflix</span></div>
                <div class="tv-network"><span>HBO Max</span></div>
                <div class="tv-network"><span>Disney+</span></div>
                <div class="tv-network"><span>Prime</span></div>
                <div class="tv-network"><span>AMC</span></div>
                <div class="tv-network"><span>FX</span></div>
                <div class="tv-network"><span>Apple TV+</span></div>
                <div class="tv-network"><span>Hulu</span></div>
            </div>
        </section>

        <!-- RECENTLY ADDED -->
        <?php if (!empty($recent_unique)) : ?>
        <section class="tv-section">
            <div class="tv-section__head">
                <h2 class="tv-section__title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                    Recently Added
                </h2>
                <a href="#" class="tv-section__more">View All</a>
            </div>
            <div class="tv-slider" data-slider="recent">
                <div class="tv-slider__track">
                    <?php foreach ($recent_unique as $post) : movie_ui_render_movie_card($post->ID); endforeach; ?>
                </div>
                <button class="tv-slider__btn tv-slider__btn--prev" aria-label="Previous">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button class="tv-slider__btn tv-slider__btn--next" aria-label="Next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </section>
        <?php endif; ?>

        <!-- FEATURES -->
        <section class="tv-features">
            <div class="tv-feature">
                <div class="tv-feature__icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <h3 class="tv-feature__title">New Episodes Weekly</h3>
                <p class="tv-feature__desc">Fresh content added every week</p>
            </div>
            <div class="tv-feature">
                <div class="tv-feature__icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <h3 class="tv-feature__title">Watch Anywhere</h3>
                <p class="tv-feature__desc">On TV, phone, tablet, and more</p>
            </div>
            <div class="tv-feature">
                <div class="tv-feature__icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                </div>
                <h3 class="tv-feature__title">HD Quality</h3>
                <p class="tv-feature__desc">Crystal clear streaming</p>
            </div>
            <div class="tv-feature">
                <div class="tv-feature__icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </div>
                <h3 class="tv-feature__title">No Commitments</h3>
                <p class="tv-feature__desc">Cancel anytime you want</p>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="tv-footer">
        <div class="tv-footer__inner">
            <div class="tv-footer__brand">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="tv-footer__logo">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <circle cx="16" cy="16" r="15" stroke="#e50914" stroke-width="2"/>
                        <path d="M12 8L22 16L12 24V8Z" fill="#e50914"/>
                    </svg>
                    <span>MOVIE</span>
                </a>
            </div>
            <div class="tv-footer__links">
                <a href="#">About</a>
                <a href="#">Help Center</a>
                <a href="#">Terms of Use</a>
                <a href="#">Privacy</a>
                <a href="#">Cookie Preferences</a>
                <a href="#">Contact Us</a>
            </div>
            <div class="tv-footer__social">
                <a href="#" aria-label="Facebook">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a href="#" aria-label="Twitter">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                </a>
                <a href="#" aria-label="Instagram">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/></svg>
                </a>
                <a href="#" aria-label="YouTube">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/></svg>
                </a>
            </div>
            <p class="tv-footer__copy">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
        </div>
    </footer>

</div>

<!-- Toast -->
<div class="tv-toast" id="tvToast"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hero Slider
    const hero = document.getElementById('tvHero');
    if (hero) {
        const slides = hero.querySelectorAll('.tv-hero__slide');
        const dots = hero.querySelectorAll('.tv-hero__dot');
        let current = 0;
        let autoplay = setInterval(function() { goTo((current + 1) % slides.length); }, 6000);

        function goTo(idx) {
            if (slides.length <= 1) return;
            idx = ((idx % slides.length) + slides.length) % slides.length;
            slides[current] && slides[current].classList.remove('active');
            dots[current] && dots[current].classList.remove('active');
            current = idx;
            slides[current] && slides[current].classList.add('active');
            dots[current] && dots[current].classList.add('active');
        }

        dots.forEach(function(dot, i) {
            dot.addEventListener('click', function() {
                clearInterval(autoplay);
                goTo(i);
                autoplay = setInterval(function() { goTo((current + 1) % slides.length); }, 6000);
            });
        });
    }

    // Sliders
    document.querySelectorAll('.tv-slider').forEach(function(slider) {
        const track = slider.querySelector('.tv-slider__track');
        const prevBtn = slider.querySelector('.tv-slider__btn--prev');
        const nextBtn = slider.querySelector('.tv-slider__btn--next');
        
        if (!track || !prevBtn || !nextBtn) return;

        function getCardWidth() {
            const card = track.querySelector('.mu-card');
            return card ? card.offsetWidth + 16 : 220;
        }

        function updateArrows() {
            const maxScroll = track.scrollWidth - track.clientWidth;
            prevBtn.style.opacity = track.scrollLeft > 10 ? '1' : '0';
            prevBtn.style.pointerEvents = track.scrollLeft > 10 ? 'auto' : 'none';
            nextBtn.style.opacity = track.scrollLeft < maxScroll - 10 ? '1' : '0';
            nextBtn.style.pointerEvents = track.scrollLeft < maxScroll - 10 ? 'auto' : 'none';
        }

        updateArrows();

        prevBtn.addEventListener('click', function() {
            track.scrollBy({ left: -getCardWidth() * 3, behavior: 'smooth' });
        });
        nextBtn.addEventListener('click', function() {
            track.scrollBy({ left: getCardWidth() * 3, behavior: 'smooth' });
        });
        track.addEventListener('scroll', updateArrows);

        // Drag
        var isDown = false, startX, scrollLeft;
        track.addEventListener('mousedown', function(e) {
            isDown = true;
            startX = e.pageX - track.offsetLeft;
            scrollLeft = track.scrollLeft;
            track.style.cursor = 'grabbing';
        });
        track.addEventListener('mouseleave', function() {
            isDown = false;
            track.style.cursor = 'grab';
        });
        track.addEventListener('mouseup', function() {
            isDown = false;
            track.style.cursor = 'grab';
        });
        track.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            track.scrollLeft = scrollLeft - (e.pageX - track.offsetLeft - startX) * 2;
        });
    });

    // Genre filter
    document.querySelectorAll('.tv-genre').forEach(function(chip) {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.tv-genre').forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
        });
    });

    // Header scroll
    var header = document.querySelector('.mu-header');
    if (header) {
        window.addEventListener('scroll', function() {
            header.classList.toggle('scrolled', window.scrollY > 50);
        });
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
