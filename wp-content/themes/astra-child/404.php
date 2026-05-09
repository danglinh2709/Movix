<?php
/**
 * Premium dark 404 matching Movix streaming UI.
 */
defined('ABSPATH') || exit;
status_header(404);
nocache_headers();
get_header();
get_template_part('template-parts/streaming/header');

$movies = trailingslashit(home_url('movies'));
$search = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('search') : trailingslashit(home_url('search'));
?>

<div class="mu-page mu-404-page">
    <div class="mu-404-shell">
        <div class="mu-404-glare" aria-hidden="true"></div>
        <div class="mu-404-copy">
            <p class="mu-404-tag"><?php esc_html_e('Playback interrupted', 'astra-child'); ?></p>
            <h1 class="mu-404-num">404</h1>
            <p class="mu-404-title"><?php esc_html_e('We could not roll this reel.', 'astra-child'); ?></p>
            <p class="mu-muted mu-404-desc"><?php esc_html_e('The page moved, was archived, or the link is corrupted. Browse the catalogue or search for a title.', 'astra-child'); ?></p>
            <div class="mu-404-actions">
                <a class="mu-btn mu-btn--primary" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'astra-child'); ?></a>
                <a class="mu-btn mu-btn--ghost" href="<?php echo esc_url($movies); ?>"><?php esc_html_e('Movies', 'astra-child'); ?></a>
                <a class="mu-btn mu-btn--ghost" href="<?php echo esc_url($search); ?>"><?php esc_html_e('Search', 'astra-child'); ?></a>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
