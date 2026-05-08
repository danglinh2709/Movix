<?php
/**
 * Template Name: Favorites
 */
get_header();
get_template_part('template-parts/streaming/header');

if (!is_user_logged_in()) {
    auth_redirect();
}

$user_id = get_current_user_id();
global $wpdb;
$table = $wpdb->prefix . 'movie_favorites';
$ids = $wpdb->get_col($wpdb->prepare("SELECT movie_id FROM $table WHERE user_id = %d ORDER BY created_at DESC", $user_id));
?>

<div class="mu-page">
    <div class="mu-container" style="padding-top:100px;">
        <h1 class="mu-h1">My List</h1>
        
        <?php if ($ids) : ?>
            <div class="mu-grid">
                <?php foreach ($ids as $pid) : ?>
                    <?php movie_ui_render_movie_card((int)$pid); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="mu-empty">Your list is empty. Start adding movies!</div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
