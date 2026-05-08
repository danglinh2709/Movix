<?php
/**
 * Template Name: Watch Player
 */
get_header();

$movie_id = isset($_GET['movie_id']) ? (int) $_GET['movie_id'] : 0;
$episode_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$id = $episode_id ?: $movie_id;

if (!$id) {
    echo '<div class="mu-page mu-error">No movie selected.</div>';
    get_footer();
    exit;
}

$title = get_the_title($id);
$video_url = get_post_meta($id, '_video_url', true);
$backdrop = movie_ui_backdrop_url($id);
?>
<div class="mu-page mu-watch" data-mu-player data-id="<?php echo $id; ?>">
    <div class="mu-player-container">
        <div class="mu-player-top">
            <a href="javascript:history.back()" class="mu-btn-back">← Back</a>
            <h1 class="mu-player-title"><?php echo esc_html($title); ?></h1>
        </div>
        
        <div class="mu-video-wrapper">
            <?php if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) : ?>
                <iframe src="<?php echo str_replace('watch?v=', 'embed/', $video_url); ?>?autoplay=1" frameborder="0" allowfullscreen></iframe>
            <?php else : ?>
                <video id="mu-main-player" controls autoplay poster="<?php echo esc_url($backdrop); ?>">
                    <source src="<?php echo esc_url($video_url); ?>" type="application/x-mpegURL">
                </video>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php get_footer(); ?>
