<?php
/**
 * Movies Archive - Premium OTT Streaming Platform
 * Netflix/HBO Max/Prime Video Quality
 * URL: /movies/
 */
defined('ABSPATH') || exit;

// Helper: remove duplicate titles (keep first/highest rated)
function mu_unique_by_title($posts) {
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

// Check user
$current_user = wp_get_current_user();
$user_name = $current_user->exists() ? $current_user->display_name : 'Guest';
$user_avatar = get_avatar_url($current_user->ID) ?: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"%3E%3Ccircle fill="%23333" cx="20" cy="20" r="20"/%3E%3Ccircle fill="%23666" cx="20" cy="16" r="8"/%3E%3Cellipse fill="%23666" cx="20" cy="38" rx="14" ry="10"/%3E%3C/svg%3E';

// URLs
$watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));
$movies_url = get_post_type_archive_link('movie') ?: trailingslashit(home_url('movies'));
$tv_url = get_post_type_archive_link('tv_show') ?: trailingslashit(home_url('tv'));
$trending_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('trending') : trailingslashit(home_url('trending'));
$toprated_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('top-rated') : trailingslashit(home_url('top-rated'));
$mylist_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('favorites') : trailingslashit(home_url('favorites'));
$newreleases_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('new-releases') : trailingslashit(home_url('new-releases'));

// Genres
$all_genres = get_terms([
    'taxonomy' => 'genre',
    'hide_empty' => true,
    'number' => 20,
]);

// Hero: Top 5 Rated Movies (no duplicates)
$hero_q = movie_ui_query([
    'post_type' => 'movie',
    'posts_per_page' => 20,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
$hero_posts = mu_unique_by_title($hero_q->posts);
$hero_posts = array_slice($hero_posts, 0, 5);

$hero_slides = [];
foreach ($hero_posts as $post) {
    setup_postdata($post);
    $hid = $post->ID;
    $year = movie_ui_meta($hid, ['year', '_release_year'], '');
    $runtime = movie_ui_meta($hid, ['duration', '_duration'], '');
    $rating = movie_ui_meta($hid, ['rating', '_rating'], '');
    $quality = movie_ui_meta($hid, ['quality', '_quality'], 'HD');
    $age = movie_ui_meta($hid, ['age_rating', '_age_rating'], '');
    $genres = movie_ui_terms_text($hid, 'genre', 2);
    $backdrop = movie_ui_backdrop_url($hid) ?: get_the_post_thumbnail_url($hid, 'full');
    $overview = wp_trim_words(wp_strip_all_tags($post->post_content ?: $post->post_excerpt ?: ''), 35);
    $watch_url = add_query_arg('id', $hid, $watch_base);
    $detail_url = get_permalink($hid);
    
    $hero_slides[] = [
        'id' => $hid,
        'title' => get_the_title($hid),
        'backdrop' => $backdrop,
        'rating' => $rating,
        'year' => $year,
        'runtime' => $runtime,
        'quality' => $quality,
        'age' => $age,
        'genres' => $genres,
        'overview' => $overview,
        'watch_url' => $watch_url,
        'detail_url' => $detail_url,
    ];
}
wp_reset_postdata();

// Top 10 Rated Movies (no duplicates)
$toprated_q = movie_ui_query([
    'post_type' => 'movie',
    'posts_per_page' => 30,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
$toprated_unique = mu_unique_by_title($toprated_q->posts);
$toprated_unique = array_slice($toprated_unique, 0, 10);

// New Releases (no duplicates)
$newrel_q = movie_ui_query([
    'post_type' => 'movie',
    'posts_per_page' => 30,
    'orderby' => 'date',
    'order' => 'DESC',
]);
$newrel_unique = mu_unique_by_title($newrel_q->posts);
$newrel_unique = array_slice($newrel_unique, 0, 10);

// Trending (no duplicates)
$trending_q = movie_ui_query([
    'post_type' => 'movie',
    'posts_per_page' => 30,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
$trending_unique = mu_unique_by_title($trending_q->posts);
$trending_unique = array_slice($trending_unique, 0, 10);

// Popular (no duplicates)
$popular_q = movie_ui_query([
    'post_type' => 'movie',
    'posts_per_page' => 30,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
$popular_unique = mu_unique_by_title($popular_q->posts);
$popular_unique = array_slice($popular_unique, 0, 10);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Movies', 'astra-child'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="tv-page">
<?php get_template_part('template-parts/streaming/header'); ?>

<div class="tv-page__content">

<!-- ================================================ -->
<!-- HERO SECTION -->
<!-- ================================================ -->
<?php if (!empty($hero_slides)) : ?>
<section class="tv-hero" id="tvHero">
    <div class="tv-hero__track">
        <?php foreach ($hero_slides as $si => $slide) : ?>
        <div class="tv-hero__slide<?php echo $si === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($si); ?>">
            <div class="tv-hero__bg" style="background-image:url('<?php echo esc_url($slide['backdrop']); ?>')"></div>
            <div class="tv-hero__gradient"></div>
            <div class="tv-hero__content">
                <div class="tv-hero__top">
                    <?php if (!empty($slide['genres'])) : ?>
                        <span class="tv-hero__genre-tag"><?php echo esc_html($slide['genres']); ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="tv-hero__title"><?php echo esc_html($slide['title']); ?></h1>
                <div class="tv-hero__meta">
                    <?php if (!empty($slide['rating'])) : ?>
                        <span class="tv-hero__rating">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="#ffd700"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <?php echo esc_html($slide['rating']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($slide['year'])) : ?>
                        <span class="tv-hero__year"><?php echo esc_html(substr($slide['year'], 0, 4)); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($slide['runtime'])) : ?>
                        <span class="tv-hero__runtime"><?php echo esc_html($slide['runtime']); ?> min</span>
                    <?php endif; ?>
                    <?php if (!empty($slide['age'])) : ?>
                        <span class="tv-hero__age"><?php echo esc_html($slide['age']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($slide['quality'])) : ?>
                        <span class="tv-hero__quality"><?php echo esc_html($slide['quality']); ?></span>
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
            <button class="tv-hero__dot<?php echo $si === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($si); ?>"></button>
                        <?php endforeach; ?>
                    </div>
</section>
                <?php endif; ?>
                
<!-- ================================================ -->
<!-- GENRE TABS -->
<!-- ================================================ -->
<nav class="tv-genres">
    <div class="tv-genres__scroll">
        <button class="tv-genre is-active" data-genre="">All</button>
        <?php foreach ($all_genres as $g) : ?>
            <button class="tv-genre" data-genre="<?php echo esc_attr($g->slug); ?>"><?php echo esc_html($g->name); ?></button>
                        <?php endforeach; ?>
                    </div>
</nav>

<?php
// Build unique IDs arrays
$toprated_ids = wp_list_pluck($toprated_unique, 'ID');
$newrel_ids = wp_list_pluck($newrel_unique, 'ID');
$trending_ids = wp_list_pluck($trending_unique, 'ID');
$popular_ids = wp_list_pluck($popular_unique, 'ID');
?>

<!-- ================================================ -->
<!-- MAIN CONTENT -->
<!-- ================================================ -->
<main class="tv-main">

    <?php if (!empty($toprated_ids)) : ?>
    <section class="tv-section">
        <div class="tv-section__header">
            <h2 class="tv-section__title">Top Rated Movies</h2>
            <a href="#" class="tv-section__link">View All</a>
                </div>
        <div class="tv-row" data-row="toprated">
            <div class="tv-row__track">
                <?php foreach ($toprated_ids as $post_id) : movie_ui_render_movie_card($post_id); endforeach; ?>
            </div>
            <button class="tv-row__nav tv-row__nav--prev" aria-label="Previous"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
            <button class="tv-row__nav tv-row__nav--next" aria-label="Next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
        </div>
    </section>
                    <?php endif; ?>

    <?php if (!empty($newrel_ids)) : ?>
    <section class="tv-section">
        <div class="tv-section__header">
            <h2 class="tv-section__title">New Releases</h2>
            <a href="#" class="tv-section__link">View All</a>
                </div>
        <div class="tv-row" data-row="newreleases">
            <div class="tv-row__track">
                <?php foreach ($newrel_ids as $post_id) : movie_ui_render_movie_card($post_id); endforeach; ?>
            </div>
            <button class="tv-row__nav tv-row__nav--prev" aria-label="Previous"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
            <button class="tv-row__nav tv-row__nav--next" aria-label="Next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
                                        </div>
    </section>
                                    <?php endif; ?>
                                    
    <?php if (!empty($trending_ids)) : ?>
    <section class="tv-section">
        <div class="tv-section__header">
            <h2 class="tv-section__title">Trending Now</h2>
            <a href="#" class="tv-section__link">View All</a>
                                    </div>
        <div class="tv-row" data-row="trending">
            <div class="tv-row__track">
                <?php foreach ($trending_ids as $post_id) : movie_ui_render_movie_card($post_id); endforeach; ?>
                                    </div>
            <button class="tv-row__nav tv-row__nav--prev" aria-label="Previous"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
            <button class="tv-row__nav tv-row__nav--next" aria-label="Next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
                                    </div>
    </section>
                    <?php endif; ?>
                    
    <?php if (!empty($popular_ids)) : ?>
    <section class="tv-section">
        <div class="tv-section__header">
            <h2 class="tv-section__title">Popular Movies</h2>
            <a href="#" class="tv-section__link">View All</a>
        </div>
        <div class="tv-row" data-row="popular">
            <div class="tv-row__track">
                <?php foreach ($popular_ids as $post_id) : movie_ui_render_movie_card($post_id); endforeach; ?>
                        </div>
            <button class="tv-row__nav tv-row__nav--prev" aria-label="Previous"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
            <button class="tv-row__nav tv-row__nav--next" aria-label="Next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
                    </div>
    </section>
                <?php endif; ?>
                
        </main>
        
<!-- ================================================ -->
<!-- FOOTER -->
<!-- ================================================ -->
<footer class="tv-footer">
    <div class="tv-footer__inner">
        <div class="tv-footer__brand">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="tv-footer__logo">
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                    <circle cx="16" cy="16" r="15" stroke="#E50914" stroke-width="2"/>
                    <path d="M12 8L22 16L12 24V8Z" fill="#E50914"/>
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
            <a href="#">Corporate Information</a>
            <a href="#">Contact Us</a>
                </div>
        <div class="tv-footer__social">
            <a href="#" aria-label="Facebook"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
            <a href="#" aria-label="Twitter"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg></a>
            <a href="#" aria-label="Instagram"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
            <a href="#" aria-label="YouTube"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="#000"/></svg></a>
        </div>
        <p class="tv-footer__copy">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
        </div>
    </footer>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('tvNavbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('is-scrolled', window.pageYOffset > 50);
    });

    const hero = document.getElementById('tvHero');
    if (hero) {
        const slides = hero.querySelectorAll('.tv-hero__slide');
        const dots = hero.querySelectorAll('.tv-hero__dot');
        let current = 0;
        let autoplay = setInterval(() => goTo(current + 1), 7000);
        function goTo(idx) {
            idx = ((idx % slides.length) + slides.length) % slides.length;
            slides[current].classList.remove('is-active');
            dots[current].classList.remove('is-active');
            current = idx;
            slides[current].classList.add('is-active');
            dots[current].classList.add('is-active');
        }
        dots.forEach((dot, i) => dot.addEventListener('click', () => { clearInterval(autoplay); goTo(i); autoplay = setInterval(() => goTo(current + 1), 7000); }));
    }

    document.querySelectorAll('.tv-row').forEach(row => {
        const track = row.querySelector('.tv-row__track');
        const prevBtn = row.querySelector('.tv-row__nav--prev');
        const nextBtn = row.querySelector('.tv-row__nav--next');
        if (track && prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => track.scrollBy({ left: -600, behavior: 'smooth' }));
            nextBtn.addEventListener('click', () => track.scrollBy({ left: 600, behavior: 'smooth' }));
        }
    });

    document.querySelectorAll('.tv-genre').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tv-genre').forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');
        });
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
