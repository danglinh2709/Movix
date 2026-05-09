<?php
/**
 * Home — Premium Netflix / VieON / Prime Video Homepage
 * Cinematic hero + discovery rows with fully interactive UI.
 */
defined('ABSPATH') || exit;
get_header();
get_template_part('template-parts/streaming/header');

// ============================================================
// QUERIES — all driven by real CPT data
// ============================================================
$watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));
$movies_archive = trailingslashit(home_url('movies'));
$tv_archive = trailingslashit(home_url('tv'));
$trending_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('trending') : trailingslashit(home_url('trending'));
$toprated_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('top-rated') : trailingslashit(home_url('top-rated'));
$newrel_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('new-releases') : trailingslashit(home_url('new-releases'));
$history_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('history') : trailingslashit(home_url('history'));
$mylist_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('favorites') : trailingslashit(home_url('favorites'));

// Hero: top-rated / most popular movies
$hero_q = movie_ui_query([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 5,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);

$hero_slides = [];
if ($hero_q->have_posts()) {
    while ($hero_q->have_posts()) {
        $hero_q->the_post();
        $hid = get_the_ID();
        $year = movie_ui_meta($hid, ['year', '_release_year'], '');
        $runtime = movie_ui_meta($hid, ['duration', '_duration'], '');
        $rating = movie_ui_meta($hid, ['rating', '_rating'], '');
        $quality = movie_ui_meta($hid, ['quality', '_quality'], 'HD');
        $age = movie_ui_meta($hid, ['age_rating', '_age_rating'], '');
        $genres = movie_ui_terms_text($hid, 'genre', 2);
        $backdrop = movie_ui_backdrop_url($hid) ?: get_the_post_thumbnail_url($hid, 'full');
        $poster = get_the_post_thumbnail_url($hid, 'medium');
        $video_url = movie_ui_meta($hid, ['video_url', '_video_url'], '');
        $trailer_url = movie_ui_meta($hid, ['trailer_url', '_trailer_url'], '');
        $overview = wp_trim_words(wp_strip_all_tags(get_the_content() ?: get_the_excerpt() ?: ''), 28);
        $watch_url = add_query_arg('id', $hid, $watch_base);
        $detail_url = get_permalink($hid);

        // Determine play action: trailer first, then video, then none
        $play_action = '';
        if ($trailer_url) {
            $play_action = 'trailer:' . esc_attr($trailer_url);
        } elseif ($video_url) {
            $play_action = 'watch:' . esc_url($watch_url);
        }

        $hero_slides[] = [
            'id' => $hid,
            'title' => get_the_title(),
            'year' => $year,
            'runtime' => $runtime,
            'rating' => $rating,
            'quality' => $quality,
            'age' => $age,
            'genres' => $genres,
            'backdrop' => $backdrop,
            'poster' => $poster,
            'overview' => $overview,
            'watch_url' => $watch_url,
            'detail_url' => $detail_url,
            'play_action' => $play_action,
            'has_trailer' => !empty($trailer_url),
            'has_video' => !empty($video_url),
        ];
    }
    wp_reset_postdata();
}

// Trending Now
$trending_q = movie_ui_query([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 14,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);

// Top 10 Today
$top10_q = movie_ui_query([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 10,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);

// New Releases
$newrel_q = movie_ui_query([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 14,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// TV Shows / Series
$tv_q = movie_ui_query([
    'post_type' => 'tv_show',
    'posts_per_page' => 14,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);

// Recommended
$rec_q = movie_ui_query([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 14,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'offset' => 0,
]);

// ============================================================
// FEATURE BENEFITS (static — links to modals or pages)
// ============================================================
$feature_items = [
    [
        'icon' => 'tv',
        'title' => __('Enjoy on Your TV', 'astra-child'),
        'desc'  => __('Watch on Smart TVs, PlayStation, Xbox, Chromecast, Apple TV and more.', 'astra-child'),
        'action' => 'modal',
        'modal_id' => 'feat-tv',
    ],
    [
        'icon' => 'download',
        'title' => __('Download Your Shows', 'astra-child'),
        'desc'  => __('Save your favorites so you can always watch offline.', 'astra-child'),
        'action' => 'modal',
        'modal_id' => 'feat-download',
    ],
    [
        'icon' => 'devices',
        'title' => __('Watch Everywhere', 'astra-child'),
        'desc'  => __('Stream unlimited movies and TV shows on your phone, tablet, laptop and TV.', 'astra-child'),
        'action' => 'modal',
        'modal_id' => 'feat-devices',
    ],
    [
        'icon' => 'kids',
        'title' => __('Create Profiles for Kids', 'astra-child'),
        'desc'  => __('Let kids go on adventures with their favourite characters in a space made just for them.', 'astra-child'),
        'action' => 'modal',
        'modal_id' => 'feat-kids',
    ],
];

// ============================================================
// PAGE OUTPUT
// ============================================================
?>
<div class="mu-page mu-home" data-mu-home>

    <?php // ============================================================
          // HERO SECTION
          // ============================================================ ?>
    <?php if (!empty($hero_slides)) : ?>
        <section class="mu-hero" id="mu-hero" data-mu-hero-shell>
            <div class="mu-hero__track" data-mu-hero-track>
                <?php foreach ($hero_slides as $si => $slide) : ?>
                    <div class="mu-hero__slide<?php echo $si === 0 ? ' is-active' : ''; ?>"
                         data-mu-hero-slide
                         data-slide-index="<?php echo esc_attr((string) $si); ?>"
                         data-play-action="<?php echo esc_attr($slide['play_action']); ?>"
                         data-watch-url="<?php echo esc_url($slide['watch_url']); ?>"
                         data-detail-url="<?php echo esc_url($slide['detail_url']); ?>"
                         style="background-image:url('<?php echo esc_url($slide['backdrop']); ?>')">

                        <div class="mu-hero__grad"></div>

                        <div class="mu-hero__content">
                            <?php // Top badge row ?>
                            <div class="mu-hero__top-row">
                                <?php if (!empty($slide['genres'])) : ?>
                                    <span class="mu-hero__genre-tag"><?php echo esc_html($slide['genres']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($slide['quality'])) : ?>
                                    <span class="mu-hero__badge mu-hero__badge--quality"><?php echo esc_html($slide['quality']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($slide['age'])) : ?>
                                    <span class="mu-hero__badge mu-hero__badge--age"><?php echo esc_html($slide['age']); ?></span>
                                <?php endif; ?>
                            </div>

                            <h1 class="mu-hero__title"><?php echo esc_html($slide['title']); ?></h1>

                            <?php // Meta row ?>
                            <div class="mu-hero__meta-row">
                                <?php if (!empty($slide['rating'])) : ?>
                                    <span class="mu-hero__meta-item mu-hero__meta-item--match">
                                        <span class="mu-star-icon">★</span> <?php echo esc_html($slide['rating']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($slide['year'])) : ?>
                                    <span class="mu-hero__meta-item"><?php echo esc_html($slide['year']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($slide['runtime'])) : ?>
                                    <span class="mu-hero__meta-item"><?php echo esc_html($slide['runtime']); ?>m</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($slide['overview'])) : ?>
                                <p class="mu-hero__desc"><?php echo esc_html($slide['overview']); ?></p>
                            <?php endif; ?>

                            <?php // CTA Buttons ?>
                            <div class="mu-hero__cta">
                                <button type="button"
                                        class="mu-hero__btn mu-hero__btn--play"
                                        data-mu-hero-play
                                        data-play-action="<?php echo esc_attr($slide['play_action']); ?>"
                                        data-watch-url="<?php echo esc_url($slide['watch_url']); ?>"
                                        data-detail-url="<?php echo esc_url($slide['detail_url']); ?>"
                                        aria-label="<?php esc_attr_e('Play', 'astra-child'); ?>">
                                    <span class="mu-hero__btn-icon"><span class="mu-hero__play-icon"></span></span>
                                    <?php esc_html_e('Play Now', 'astra-child'); ?>
                                </button>

                                <button type="button"
                                        class="mu-hero__btn mu-hero__btn--info"
                                        data-mu-hero-info
                                        data-detail-url="<?php echo esc_url($slide['detail_url']); ?>"
                                        aria-label="<?php esc_attr_e('More Info', 'astra-child'); ?>">
                                    <span class="mu-hero__btn-icon"><span class="mu-hero__info-icon"></span></span>
                                    <?php esc_html_e('More Info', 'astra-child'); ?>
                                </button>

                                <button type="button"
                                        class="mu-hero__btn mu-hero__btn--fav"
                                        data-mu-fav-hero
                                        data-id="<?php echo esc_attr((string) $slide['id']); ?>"
                                        aria-label="<?php esc_attr_e('Add to My List', 'astra-child'); ?>">
                                    <span class="mu-hero__fav-icon">+</span>
                                    <span class="mu-hero__fav-label"><?php esc_html_e('My List', 'astra-child'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php // Hero pagination dots ?>
            <div class="mu-hero__dots" data-mu-hero-dots role="tablist" aria-label="<?php esc_attr_e('Hero slides', 'astra-child'); ?>">
                <?php foreach ($hero_slides as $si => $slide) : ?>
                    <button type="button"
                            class="mu-hero__dot<?php echo $si === 0 ? ' is-active' : ''; ?>"
                            data-mu-hero-dot="<?php echo esc_attr((string) $si); ?>"
                            role="tab"
                            aria-selected="<?php echo $si === 0 ? 'true' : 'false'; ?>"
                            aria-label="<?php echo esc_attr(sprintf(__('Slide %d', 'astra-child'), $si + 1)); ?>">
                    </button>
                <?php endforeach; ?>
            </div>

            <?php // Arrow navigation ?>
            <button type="button" class="mu-hero__arrow mu-hero__arrow--prev" data-mu-hero-prev aria-label="<?php esc_attr_e('Previous', 'astra-child'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button type="button" class="mu-hero__arrow mu-hero__arrow--next" data-mu-hero-next aria-label="<?php esc_attr_e('Next', 'astra-child'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </section>
    <?php else : ?>
        <section class="mu-hero mu-hero--empty">
            <div class="mu-hero__grad"></div>
            <div class="mu-hero__content">
                <h1 class="mu-hero__title"><?php esc_html_e('Welcome to Movix', 'astra-child'); ?></h1>
                <p class="mu-hero__desc"><?php esc_html_e('Import movies or run the importer to populate your cinematic home.', 'astra-child'); ?></p>
                <a class="mu-hero__btn mu-hero__btn--play" href="<?php echo esc_url(admin_url()); ?>">
                    <?php esc_html_e('Dashboard', 'astra-child'); ?>
                </a>
            </div>
        </section>
    <?php endif; ?>

    <main class="mu-main">

        <?php // ============================================================
              // CONTINUE WATCHING
              // ============================================================ ?>
        <?php mu_front_continue_row(__('Continue Watching', 'astra-child'), 'mu-continue'); ?>

        <?php // ============================================================
              // TRENDING NOW
              // ============================================================ ?>
        <?php if ($trending_q->have_posts()) : ?>
            <section class="mu-row" id="mu-trending">
                <div class="mu-row-inner">
                    <div class="mu-row__head">
                        <div class="mu-row__head-left">
                            <h2 class="mu-row__title"><?php esc_html_e('Trending Now', 'astra-child'); ?></h2>
                        </div>
                        <div class="mu-row__head-right">
                            <a href="<?php echo esc_url($trending_url); ?>" class="mu-viewall-link">
                                <?php esc_html_e('View All', 'astra-child'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="mu-swiper-wrap" data-mu-swiper-wrap="mu-trending">
                        <div class="mu-swiper-nav mu-swiper-nav--prev" data-mu-row-prev="mu-trending">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </div>
                        <div class="swiper mu-swiper" data-mu-swiper="row">
                            <div class="swiper-wrapper">
                                <?php
                                $trending_i = 0;
                                while ($trending_q->have_posts()) :
                                    $trending_q->the_post();
                                    $trending_i++;
                                ?>
                                    <div class="swiper-slide mu-slide">
                                        <div class="mu-card-rank" aria-hidden="true"><?php echo esc_html((string) $trending_i); ?></div>
                                        <?php movie_ui_render_movie_card(get_the_ID()); ?>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <div class="mu-swiper-nav mu-swiper-nav--next" data-mu-row-next="mu-trending">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php // ============================================================
              // TOP 10 TODAY
              // ============================================================ ?>
        <?php if ($top10_q->have_posts()) : ?>
            <section class="mu-row mu-top10-row" id="mu-top10">
                <div class="mu-row-inner">
                    <div class="mu-row__head">
                        <div class="mu-row__head-left">
                            <h2 class="mu-row__title"><?php esc_html_e('Top 10 Today', 'astra-child'); ?></h2>
                        </div>
                        <div class="mu-row__head-right">
                            <a href="<?php echo esc_url($toprated_url); ?>" class="mu-viewall-link">
                                <?php esc_html_e('View All', 'astra-child'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="mu-swiper-wrap" data-mu-swiper-wrap="mu-top10">
                        <div class="mu-swiper-nav mu-swiper-nav--prev" data-mu-row-prev="mu-top10">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </div>
                        <div class="swiper mu-swiper" data-mu-swiper="row">
                            <div class="swiper-wrapper">
                                <?php
                                $top10_i = 0;
                                while ($top10_q->have_posts()) :
                                    $top10_q->the_post();
                                    $top10_i++;
                                ?>
                                    <div class="swiper-slide mu-slide mu-slide--top10">
                                        <div class="mu-top10-num" aria-hidden="true"><?php echo esc_html((string) $top10_i); ?></div>
                                        <?php movie_ui_render_movie_card(get_the_ID()); ?>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <div class="mu-swiper-nav mu-swiper-nav--next" data-mu-row-next="mu-top10">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php // ============================================================
              // NEW RELEASES
              // ============================================================ ?>
        <?php if ($newrel_q->have_posts()) : ?>
            <section class="mu-row" id="mu-new-releases">
                <div class="mu-row-inner">
                    <div class="mu-row__head">
                        <div class="mu-row__head-left">
                            <h2 class="mu-row__title"><?php esc_html_e('New Releases', 'astra-child'); ?></h2>
                        </div>
                        <div class="mu-row__head-right">
                            <a href="<?php echo esc_url($newrel_url); ?>" class="mu-viewall-link">
                                <?php esc_html_e('View All', 'astra-child'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="mu-swiper-wrap" data-mu-swiper-wrap="mu-new-releases">
                        <div class="mu-swiper-nav mu-swiper-nav--prev" data-mu-row-prev="mu-new-releases">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </div>
                        <div class="swiper mu-swiper" data-mu-swiper="row">
                            <div class="swiper-wrapper">
                                <?php while ($newrel_q->have_posts()) : $newrel_q->the_post(); ?>
                                    <div class="swiper-slide mu-slide">
                                        <?php movie_ui_render_movie_card(get_the_ID()); ?>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <div class="mu-swiper-nav mu-swiper-nav--next" data-mu-row-next="mu-new-releases">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php // ============================================================
              // POPULAR TV SHOWS
              // ============================================================ ?>
        <?php if ($tv_q->have_posts()) : ?>
            <section class="mu-row" id="mu-tvshows">
                <div class="mu-row-inner">
                    <div class="mu-row__head">
                        <div class="mu-row__head-left">
                            <h2 class="mu-row__title"><?php esc_html_e('Popular TV Shows', 'astra-child'); ?></h2>
                        </div>
                        <div class="mu-row__head-right">
                            <a href="<?php echo esc_url($tv_archive); ?>" class="mu-viewall-link">
                                <?php esc_html_e('View All', 'astra-child'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="mu-swiper-wrap" data-mu-swiper-wrap="mu-tvshows">
                        <div class="mu-swiper-nav mu-swiper-nav--prev" data-mu-row-prev="mu-tvshows">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </div>
                        <div class="swiper mu-swiper" data-mu-swiper="row">
                            <div class="swiper-wrapper">
                                <?php while ($tv_q->have_posts()) : $tv_q->the_post(); ?>
                                    <div class="swiper-slide mu-slide">
                                        <?php movie_ui_render_movie_card(get_the_ID()); ?>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <div class="mu-swiper-nav mu-swiper-nav--next" data-mu-row-next="mu-tvshows">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php // ============================================================
              // RECOMMENDED FOR YOU
              // ============================================================ ?>
        <?php if ($rec_q->have_posts()) : ?>
            <section class="mu-row" id="mu-recommended">
                <div class="mu-row-inner">
                    <div class="mu-row__head">
                        <div class="mu-row__head-left">
                            <h2 class="mu-row__title"><?php esc_html_e('Highly Rated', 'astra-child'); ?></h2>
                        </div>
                    </div>
                    <div class="mu-swiper-wrap" data-mu-swiper-wrap="mu-recommended">
                        <div class="mu-swiper-nav mu-swiper-nav--prev" data-mu-row-prev="mu-recommended">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </div>
                        <div class="swiper mu-swiper" data-mu-swiper="row">
                            <div class="swiper-wrapper">
                                <?php while ($rec_q->have_posts()) : $rec_q->the_post(); ?>
                                    <div class="swiper-slide mu-slide">
                                        <?php movie_ui_render_movie_card(get_the_ID()); ?>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <div class="mu-swiper-nav mu-swiper-nav--next" data-mu-row-next="mu-recommended">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php // ============================================================
              // ALL MOVIES ROW
              // ============================================================ ?>
        <?php
        $all_movies_q = movie_ui_query([
            'post_type' => 'movie',
            'posts_per_page' => 14,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        if ($all_movies_q->have_posts()) :
        ?>
            <section class="mu-row" id="mu-movies">
                <div class="mu-row-inner">
                    <div class="mu-row__head">
                        <div class="mu-row__head-left">
                            <span class="mu-row__movies-icon">🎬</span>
                            <h2 class="mu-row__title"><?php esc_html_e('All Movies', 'astra-child'); ?></h2>
                        </div>
                        <div class="mu-row__head-right">
                            <a href="<?php echo esc_url($movies_archive); ?>" class="mu-viewall-link">
                                <?php esc_html_e('Browse All Movies', 'astra-child'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="mu-swiper-wrap" data-mu-swiper-wrap="mu-movies">
                        <div class="mu-swiper-nav mu-swiper-nav--prev" data-mu-row-prev="mu-movies">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </div>
                        <div class="swiper mu-swiper" data-mu-swiper="row">
                            <div class="swiper-wrapper">
                                <?php while ($all_movies_q->have_posts()) : $all_movies_q->the_post(); ?>
                                    <div class="swiper-slide mu-slide">
                                        <?php movie_ui_render_movie_card(get_the_ID()); ?>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <div class="mu-swiper-nav mu-swiper-nav--next" data-mu-row-next="mu-movies">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <?php // ============================================================
          // FEATURE BENEFITS FOOTER STRIP
          // ============================================================ ?>
    <section class="mu-features-strip" id="mu-features">
        <div class="mu-features-strip__inner">
            <h2 class="mu-features-strip__headline"><?php esc_html_e('The best streaming experience', 'astra-child'); ?></h2>
            <div class="mu-features-grid">
                <?php foreach ($feature_items as $feat) : ?>
                    <button type="button"
                            class="mu-feature-card"
                            data-mu-feature-modal="<?php echo esc_attr($feat['modal_id']); ?>"
                            aria-label="<?php echo esc_attr($feat['title']); ?>">
                        <div class="mu-feature-card__icon">
                            <?php if ($feat['icon'] === 'tv') : ?>
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><polyline points="8 21 12 17 16 21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            <?php elseif ($feat['icon'] === 'download') : ?>
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <?php elseif ($feat['icon'] === 'devices') : ?>
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
                            <?php elseif ($feat['icon'] === 'kids') : ?>
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                            <?php endif; ?>
                        </div>
                        <h3 class="mu-feature-card__title"><?php echo esc_html($feat['title']); ?></h3>
                        <p class="mu-feature-card__desc"><?php echo esc_html($feat['desc']); ?></p>
                        <span class="mu-feature-card__cta">
                            <?php esc_html_e('Learn More', 'astra-child'); ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php // ============================================================
          // FOOTER
          // ============================================================ ?>
    <footer class="mu-home-footer">
        <div class="mu-home-footer__inner">
            <div class="mu-home-footer__links">
                <a href="<?php echo esc_url($movies_archive); ?>"><?php esc_html_e('Movies', 'astra-child'); ?></a>
                <a href="<?php echo esc_url($tv_archive); ?>"><?php esc_html_e('TV Shows', 'astra-child'); ?></a>
                <a href="<?php echo esc_url($trending_url); ?>"><?php esc_html_e('Trending', 'astra-child'); ?></a>
                <a href="<?php echo esc_url($mylist_url); ?>"><?php esc_html_e('My List', 'astra-child'); ?></a>
                <a href="<?php echo esc_url($history_url); ?>"><?php esc_html_e('History', 'astra-child'); ?></a>
            </div>
            <p class="mu-home-footer__copy">
                &copy; <?php echo esc_html(gmdate('Y')); ?> Movix — <?php esc_html_e('Powered by WordPress + Astra', 'astra-child'); ?>
            </p>
        </div>
    </footer>

</div>

<?php // ============================================================
      // FEATURE MODALS (Coming Soon)
      // ============================================================ ?>
<div class="mu-modal mu-feature-modal" id="mu-feature-modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="mu-modal__backdrop" data-mu-close-feature-modal></div>
    <div class="mu-modal__content mu-modal__content--feature">
        <button type="button" class="mu-modal__close" data-mu-close-feature-modal aria-label="<?php esc_attr_e('Close', 'astra-child'); ?>">&times;</button>
        <div class="mu-feature-modal__body" id="mu-feature-modal-body">
            <div class="mu-feature-modal__coming">
                <div class="mu-feature-modal__icon-large">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <h2 class="mu-feature-modal__title"><?php esc_html_e('Coming Soon', 'astra-child'); ?></h2>
                <p class="mu-feature-modal__desc"><?php esc_html_e('This feature is being developed and will be available soon.', 'astra-child'); ?></p>
                <button type="button" class="mu-feature-modal__got-it-btn" data-mu-close-feature-modal>
                    <?php esc_html_e('Got It', 'astra-child'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php // ============================================================
      // TRAILER / VIDEO MODAL - UNIFIED
      // ============================================================ ?>
<div class="mu-modal mu-trailer-modal" id="mu-trailer-modal" aria-hidden="true" role="dialog" aria-modal="true">
    <button type="button" class="mu-trailer-modal__close" data-mu-close-trailer aria-label="<?php esc_attr_e('Close', 'astra-child'); ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>
    <div class="mu-modal__backdrop" data-mu-close-trailer></div>
    <div class="mu-modal__content mu-modal__content--video mu-trailer-modal-wrapper">
        <div class="mu-modal__video" id="mu-trailer-video-area"></div>
    </div>
</div>

<?php // ============================================================
      // UNAVAILABLE MODAL
      // ============================================================ ?>
<div class="mu-modal mu-unavailable-modal" id="mu-unavailable-modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="mu-modal__backdrop" data-mu-close-unavailable></div>
    <div class="mu-modal__content mu-modal__content--unavailable">
        <button type="button" class="mu-modal__close" data-mu-close-unavailable aria-label="<?php esc_attr_e('Close', 'astra-child'); ?>">&times;</button>
        <div class="mu-unavailable-body">
            <div class="mu-unavailable-icon">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <h2 class="mu-unavailable-title"><?php esc_html_e('Content Unavailable', 'astra-child'); ?></h2>
            <p class="mu-unavailable-desc"><?php esc_html_e('No video source has been added for this title yet. Please check back later or contact support.', 'astra-child'); ?></p>
            <div class="mu-unavailable-actions">
                <a href="#" class="mu-hero__btn mu-hero__btn--play" data-mu-close-unavailable>
                    <?php esc_html_e('Close', 'astra-child'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
