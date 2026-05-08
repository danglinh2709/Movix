<?php
/**
 * Single Movie — Cinematic Detail Page
 */
get_header();
get_template_part('template-parts/streaming/header');

if (have_posts()) : while (have_posts()) : the_post();
    $id = get_the_ID();
    $backdrop = movie_ui_backdrop_url($id);
    $poster = get_the_post_thumbnail_url($id, 'large');
    $rating = get_post_meta($id, '_rating', true);
    $year = get_post_meta($id, '_release_year', true);
    $duration = get_post_meta($id, '_duration', true);
    $trailer = get_post_meta($id, '_trailer_url', true);
    $watch_url = add_query_arg('movie_id', $id, home_url('/watch/'));
?>

<div class="mu-page mu-single-movie">
    <div class="mu-hero-detail">
        <div class="mu-hero-detail__bg" style="background-image:url('<?php echo esc_url($backdrop); ?>')"></div>
        <div class="mu-hero-detail__grad"></div>
        
        <div class="mu-container mu-hero-detail__inner">
            <div class="mu-hero-detail__poster">
                <img src="<?php echo esc_url($poster); ?>" alt="<?php the_title(); ?>">
            </div>
            <div class="mu-hero-detail__info">
                <h1 class="mu-hero-detail__title"><?php the_title(); ?></h1>
                <div class="mu-hero-detail__meta">
                    <span><?php echo esc_html($year); ?></span>
                    <span><?php echo esc_html($duration); ?>m</span>
                    <span class="mu-rating">★ <?php echo esc_html($rating); ?></span>
                </div>
                <div class="mu-hero-detail__desc">
                    <?php the_content(); ?>
                </div>
                <div class="mu-hero-detail__actions">
                    <a href="<?php echo esc_url($watch_url); ?>" class="mu-btn mu-btn--primary">Watch Now</a>
                    <?php if ($trailer) : ?>
                        <button class="mu-btn mu-btn--ghost" data-mu-trailer="<?php echo esc_attr($trailer); ?>">Trailer</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mu-container">
        <!-- Related Movies -->
        <section class="mu-row" style="margin-top:60px;">
            <h2 class="mu-row__title">Related Movies</h2>
            <div class="mu-grid">
                <?php
                $related = movie_ui_query(['post_type' => 'movie', 'posts_per_page' => 6, 'post__not_in' => [$id]]);
                while ($related->have_posts()) : $related->the_post();
                    movie_ui_render_movie_card(get_the_ID());
                endwhile; wp_reset_postdata();
                ?>
            </div>
        </section>
    </div>
</div>

<?php endwhile; endif; ?>
<?php get_footer(); ?>
