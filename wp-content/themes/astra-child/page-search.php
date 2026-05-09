<?php
/**
 * Template Name: Search
 */
get_header();
get_template_part('template-parts/streaming/header');

$initial = isset($_GET['q']) ? sanitize_text_field(wp_unslash((string) $_GET['q'])) : '';
?>

<div class="mu-page mu-search-page">
    <div class="mu-container" style="padding-top:88px;">
        <div class="mu-search-page-head">
            <h1 class="mu-h1"><?php esc_html_e('Search', 'astra-child'); ?></h1>
            <div class="mu-search-box-large">
                <label class="mu-srl" for="mu-page-search-q"><?php esc_html_e('Query', 'astra-child'); ?></label>
                <input id="mu-page-search-q" class="mu-input-lg" type="search" autocomplete="off" value="<?php echo esc_attr($initial); ?>" placeholder="<?php esc_attr_e('Actors, moods, franchises…', 'astra-child'); ?>" data-mu-page-search/>

                <div class="mu-recent-queries" data-mu-recent-queries hidden aria-label="<?php esc_attr_e('Recent searches', 'astra-child'); ?>">
                    <span class="mu-muted"><?php esc_html_e('Recent', 'astra-child'); ?></span>
                    <div class="mu-chip-row" data-mu-recent-chips></div>
                </div>
            </div>
        </div>

        <div class="mu-skel-grid" data-mu-page-skel style="display:none;min-height:180px;"></div>
        <div id="mu-page-search-results" class="mu-page-search-results" data-mu-page-results></div>
    </div>
</div>

<?php get_footer(); ?>
