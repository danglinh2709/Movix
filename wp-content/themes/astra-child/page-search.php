<?php
/**
 * Template Name: Search
 */
get_header();
get_template_part('template-parts/streaming/header');

$q = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
?>

<div class="mu-page mu-search-page">
    <div class="mu-container" style="padding-top:100px;">
        <div class="mu-search-header">
            <h1 class="mu-h1">Search</h1>
            <div class="mu-search-box">
                <form action="<?php echo home_url('/search/'); ?>" method="GET">
                    <input type="text" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Search movies, TV shows, actors..." autofocus>
                    <button type="submit">Search</button>
                </form>
            </div>
        </div>

        <div id="mu-search-results" class="mu-search-content" style="margin-top:40px;">
            <?php if ($q) : ?>
                <?php
                $query = movie_ui_query([
                    'post_type' => ['movie', 'tv_show'],
                    's' => $q,
                    'posts_per_page' => 24
                ]);
                if ($query->have_posts()) :
                    echo '<div class="mu-grid">';
                    while ($query->have_posts()) : $query->the_post();
                        movie_ui_render_movie_card(get_the_ID());
                    endwhile;
                    echo '</div>';
                else :
                    echo '<div class="mu-empty">No results found for "' . esc_html($q) . '".</div>';
                endif;
                wp_reset_postdata();
                ?>
            <?php else : ?>
                <div class="mu-empty">Enter a keyword to start searching.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
