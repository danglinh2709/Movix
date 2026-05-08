<?php
/**
 * Home Page — Cinematic Discovery
 */
get_header();
get_template_part('template-parts/streaming/header');
?>

<div class="mu-page mu-home">
    <?php
    // Hero: Latest Featured Movie
    $hero_q = movie_ui_query(['posts_per_page' => 1]);
    if ($hero_q->have_posts()) : $hero_q->the_post();
        $id = get_the_ID();
        $backdrop = movie_ui_backdrop_url($id);
        $title = get_the_title();
        $desc = get_the_excerpt();
        $watch_url = add_query_arg('movie_id', $id, home_url('/watch/'));
    ?>
    <section class="mu-hero">
        <div class="mu-hero__bg" style="background-image:url('<?php echo esc_url($backdrop); ?>')"></div>
        <div class="mu-hero__grad"></div>
        <div class="mu-container mu-hero__inner">
            <div class="mu-hero__content">
                <h1 class="mu-hero__title"><?php echo esc_html($title); ?></h1>
                <p class="mu-hero__desc"><?php echo wp_trim_words($desc, 30); ?></p>
                <div class="mu-hero__actions">
                    <a href="<?php echo esc_url($watch_url); ?>" class="mu-btn mu-btn--primary">Watch Now</a>
                    <a href="<?php the_permalink(); ?>" class="mu-btn mu-btn--ghost">More Info</a>
                </div>
            </div>
        </div>
    </section>
    <?php wp_reset_postdata(); endif; ?>

    <main class="mu-main">
        <?php
        $rows = [
            'Trending Now' => ['orderby' => 'rand'],
            'Popular Movies' => ['post_type' => 'movie'],
            'TV Shows' => ['post_type' => 'tv_show'],
            'Action & Adventure' => ['tax_query' => [['taxonomy' => 'genre', 'field' => 'slug', 'terms' => 'action']]],
        ];

        foreach ($rows as $label => $args) :
            $q = movie_ui_query(array_merge(['posts_per_page' => 12], $args));
            if ($q->have_posts()) :
        ?>
        <section class="mu-row">
            <div class="mu-container">
                <div class="mu-row__head">
                    <h2 class="mu-row__title"><?php echo esc_html($label); ?></h2>
                    <div class="mu-row__nav">
                        <button class="mu-navbtn mu-navbtn--prev" aria-label="Previous"></button>
                        <button class="mu-navbtn mu-navbtn--next" aria-label="Next"></button>
                    </div>
                </div>
                <div class="swiper mu-swiper" data-mu-swiper="row">
                    <div class="swiper-wrapper">
                        <?php while ($q->have_posts()) : $q->the_post(); ?>
                            <div class="swiper-slide mu-slide">
                                <?php movie_ui_render_movie_card(get_the_ID()); ?>
                            </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; endforeach; ?>
    </main>
</div>

<?php 
// Search Modal (Global)
?>
<div class="mu-modal mu-search-modal" data-mu-search-modal>
    <div class="mu-modal__backdrop" data-mu-close-search></div>
    <div class="mu-modal__panel">
        <div class="mu-searchbar">
            <input type="text" placeholder="Search for movies, TV shows..." data-mu-search-input>
            <button class="mu-btn-close" data-mu-close-search>&times;</button>
        </div>
        <div class="mu-search-results" data-mu-search-results></div>
    </div>
</div>

<?php get_footer(); ?>
