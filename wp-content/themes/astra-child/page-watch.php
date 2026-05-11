<?php
/**
 * Template Name: Watch Player (Premium OTT)
 * Modern streaming platform design
 */
defined('ABSPATH') || exit;

remove_all_actions('astra_header');
show_admin_bar(false);

// Get post ID - support multiple parameter names
$movie_id = isset($_GET['movie_id']) ? (int) $_GET['movie_id'] : 0;
$episode_id = isset($_GET['episode_id']) ? (int) $_GET['episode_id'] : 0;
$id_param = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$id = $episode_id ?: ($movie_id ?: $id_param);

if (!$id || !get_post($id)) {
    get_template_part('template-parts/streaming/header');
    echo '<div class="mu-watch-error">';
    echo '<svg viewBox="0 0 24 24" width="80" height="80" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
    echo '<h2>No video selected</h2>';
    echo '<p>Please select a movie or TV show to watch.</p>';
    echo '<a href="' . esc_url(home_url('/')) . '" class="mu-btn mu-btn--primary">Go to Home</a>';
    echo '</div>';
    get_footer();
    exit;
}

// Get post data
$post_type = get_post_type($id);
$title = get_the_title($id);
$permalink = get_permalink($id);
$is_tv = ($post_type === 'episode');
$is_admin = current_user_can('edit_posts');

// Video source detection
$video_sources = [];

// NEW: Check for multiple video sources (JSON format)
$_video_sources_json = get_post_meta($id, '_video_sources', true);
if ($_video_sources_json) {
    $multi_sources = json_decode($_video_sources_json, true);
    if (is_array($multi_sources)) {
        foreach ($multi_sources as $src) {
            if (!empty($src['url'])) {
                $video_sources['multi_' . $src['id']] = $src['url'];
            }
        }
    }
}

if ($is_tv) {
    $ep_video_url = get_post_meta($id, 'video_url', true);
    if ($ep_video_url) $video_sources['episode_video_url'] = $ep_video_url;
}

$_video_url = get_post_meta($id, '_video_url', true);
if ($_video_url) $video_sources['_video_url'] = $_video_url;

$video_url = get_post_meta($id, 'video_url', true);
if ($video_url) $video_sources['video_url'] = $video_url;

$_video_alt = get_post_meta($id, '_video_url_alt', true);
if ($_video_alt) $video_sources['_video_url_alt'] = $_video_alt;

$hls_url = get_post_meta($id, 'hls_url', true);
if ($hls_url) $video_sources['hls_url'] = $hls_url;

$_hls_url = get_post_meta($id, '_hls_url', true);
if ($_hls_url) $video_sources['_hls_url'] = $_hls_url;

$embed_url = get_post_meta($id, 'embed_url', true);
if ($embed_url) $video_sources['embed_url'] = $embed_url;

$_embed_url = get_post_meta($id, '_embed_url', true);
if ($_embed_url) $video_sources['_embed_url'] = $_embed_url;

// Determine primary video
$priority = ['episode_video_url', '_video_url', 'video_url', '_video_url_alt', 'hls_url', '_hls_url', 'embed_url', '_embed_url'];
$primary_video = '';
$video_type = 'empty';

if (!empty($video_sources)) {
    foreach ($priority as $key) {
        if (isset($video_sources[$key])) {
            $primary_video = $video_sources[$key];
            break;
        }
    }

    if (strpos($primary_video, '.m3u8') !== false) {
        $video_type = 'hls';
    } elseif (strpos($primary_video, 'youtube.com') !== false || strpos($primary_video, 'youtu.be') !== false) {
        $video_type = 'youtube';
        if (strpos($primary_video, 'watch?v=') !== false) {
            $primary_video = str_replace('watch?v=', 'embed/', $primary_video);
        } elseif (preg_match('#youtu\.be/([^?&#]+)#', $primary_video, $m)) {
            $primary_video = 'https://www.youtube.com/embed/' . $m[1];
        }
    } elseif (strpos($primary_video, 'vimeo.com') !== false) {
        $video_type = 'vimeo';
    } elseif (strpos($primary_video, '<iframe') !== false || strpos($primary_video, 'embed') !== false) {
        $video_type = 'iframe';
    } else {
        $video_type = 'mp4';
    }
}

// Subtitle
$subtitle_url = get_post_meta($id, 'subtitle_url', true);
$_subtitle_url = get_post_meta($id, '_subtitle_url', true);
$sub_vtt = $subtitle_url ?: $_subtitle_url;

// Poster/Backdrop
$poster_url = get_the_post_thumbnail_url($id, 'large');
if (!$poster_url) {
    $poster_url = 'https://picsum.photos/seed/' . $id . '/400/600';
}

$backdrop = '';
if (function_exists('movie_ui_backdrop_url')) {
    $backdrop = movie_ui_backdrop_url($id);
}
if (!$backdrop) {
    $thumb_id = get_post_thumbnail_id($id);
    if ($thumb_id) {
        $backdrop_arr = wp_get_attachment_image_src($thumb_id, 'large');
        $backdrop = $backdrop_arr[0] ?? '';
    }
}

// Metadata
$rating = get_post_meta($id, '_rating', true) ?: get_post_meta($id, 'imdbRating', true) ?: '';
$year = get_post_meta($id, '_release_year', true) ?: get_post_meta($id, 'release_date', true) ?: '';
if ($year) $year = substr($year, 0, 4);

$runtime = get_post_meta($id, '_duration', true) ?: get_post_meta($id, 'runtime', true) ?: '';
if ($runtime) {
    $runtime_num = (int) filter_var($runtime, FILTER_SANITIZE_NUMBER_INT);
    if ($runtime_num) {
        $runtime = $runtime_num . ' min';
    }
}

$age_rating = get_post_meta($id, '_age_rating', true) ?: get_post_meta($id, 'age_rating', true) ?: '';
$quality = get_post_meta($id, '_quality', true) ?: get_post_meta($id, 'quality', true) ?: '';

// Overview
$overview = get_the_content($id);
$excerpt = get_the_excerpt($id);
$description = $overview ?: $excerpt;

// Genres
$genres = [];
$terms = get_the_terms($id, 'genre');
if ($terms && !is_wp_error($terms)) {
    $genres = array_map(function($t) {
        return '<a href="' . esc_url(get_term_link($t)) . '" class="mu-watch-info__genre">' . esc_html($t->name) . '</a>';
    }, $terms);
}

// Director
$directors = [];
$director_terms = get_the_terms($id, 'director');
if ($director_terms && !is_wp_error($director_terms)) {
    $directors = array_map(function($t) { return esc_html($t->name); }, $director_terms);
}

// Writers
$writers_meta = get_post_meta($id, '_writers', true);
$writers = $writers_meta ? (is_array($writers_meta) ? $writers_meta : explode(',', $writers_meta)) : [];

// Cast
$cast = [];
$cast_terms = get_the_terms($id, 'actor');
if ($cast_terms && !is_wp_error($cast_terms)) {
    $cast = array_slice($cast_terms, 0, 10);
}

// Audio
$audio_lang = get_post_meta($id, '_audio_language', true) ?: get_post_meta($id, 'audio_language', true) ?: '';

// Episodes
$tv_episodes = [];
if ($is_tv) {
    $tv_id = (int) get_post_meta($id, 'tv_show_id', true);
    if (!$tv_id) $tv_id = wp_get_post_parent_id($id);

    if ($tv_id) {
        $all_eps_query = new WP_Query([
            'post_type' => 'episode',
            'posts_per_page' => -1,
            'meta_query' => [['key' => 'tv_show_id', 'value' => $tv_id]],
            'orderby' => ['meta_value_num' => 'ASC', 'title' => 'ASC'],
            'meta_key' => 'episode_number',
            'no_found_rows' => true
        ]);

        if ($all_eps_query->have_posts()) {
            $tv_episodes = $all_eps_query->posts;
        }
        wp_reset_postdata();
    }
}

$current_season = get_post_meta($id, 'season_number', true) ?: 1;
$current_ep_num = get_post_meta($id, 'episode_number', true) ?: 1;

// Next episode
$next_href = '';
if ($is_tv && !empty($tv_episodes)) {
    foreach ($tv_episodes as $idx => $ep) {
        if ((int) $ep->ID === $id) {
            if (isset($tv_episodes[$idx + 1])) {
                $next_ep = $tv_episodes[$idx + 1];
                $wbase = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : home_url('/watch');
                $next_href = add_query_arg('episode_id', $next_ep->ID, $wbase);
            }
            break;
        }
    }
}

// Related
$related_movies = [];
if (!$is_tv && !empty($genres)) {
    $genre_ids = array_map(function($t) { return $t->term_id; }, $terms ?: []);
    $related_query = new WP_Query([
        'post_type' => 'movie',
        'posts_per_page' => 6,
        'post__not_in' => [$id],
        'tax_query' => [['taxonomy' => 'genre', 'field' => 'term_id', 'terms' => $genre_ids]],
        'no_found_rows' => true
    ]);
    if ($related_query->have_posts()) {
        $related_movies = $related_query->posts;
    }
    wp_reset_postdata();
}

// Up next
$up_next = null;
if ($next_href && isset($next_ep)) {
    $up_next = [
        'title' => get_the_title($next_ep->ID),
        'thumb' => get_the_post_thumbnail_url($next_ep->ID, 'medium'),
        'episode' => $current_ep_num + 1,
        'href' => $next_href
    ];
}

$edit_link = $is_admin ? get_edit_post_link($id) : '';
$fav_nonce = wp_create_nonce('mu_fav_nonce');
$progress_nonce = wp_create_nonce('mu_progress_nonce');

$player_settings = [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => $progress_nonce,
    'favNonce' => $fav_nonce,
    'isLoggedIn' => is_user_logged_in(),
    'postId' => $id
];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/movie-ui.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/ms-watch.css')); ?>">
    <style>
        html { margin-top: 0 !important; }
        body { margin-top: 0 !important; }
        #wpadminbar { display: none !important; }
        * { box-sizing: border-box; }
    </style>
</head>
<body class="movie-ui movie-ui--no-sidebar mu-watch-page">
<?php wp_body_open(); ?>

<!-- Header -->
<?php get_template_part('template-parts/streaming/header'); ?>

<div class="mu-watch-container">

    <!-- ============================================================ -->
    <!-- PLAYER SECTION -->
    <!-- ============================================================ -->
    <div class="mu-player-wrapper" data-post-id="<?php echo esc_attr($id); ?>" data-post-type="<?php echo esc_attr($post_type); ?>">
        <?php $all_sources = json_decode(get_post_meta($id, '_video_sources', true), true) ?: []; ?>
        <div class="mu-player-container" data-sources='<?php echo esc_attr(wp_json_encode($all_sources)); ?>'>
            
            <?php if ($video_type === 'iframe') : ?>
                <div class="mu-player-iframe-container">
                    <iframe class="mu-player-iframe" src="<?php echo esc_url($primary_video); ?>" title="<?php echo esc_attr($title); ?>" allowfullscreen></iframe>
                </div>
            <?php elseif ($video_type !== 'empty' && $video_type !== 'youtube' && $video_type !== 'vimeo') : ?>
                <video id="mu-player-video" class="mu-player-video" data-src="<?php echo esc_url($primary_video); ?>" poster="<?php echo esc_url($backdrop); ?>" playsinline crossorigin="anonymous">
                    <?php if ($sub_vtt) : ?>
                        <track kind="subtitles" srclang="<?php echo esc_attr(substr(get_locale(), 0, 2)); ?>" label="<?php esc_attr_e('Subtitles', 'astra-child'); ?>" src="<?php echo esc_url($sub_vtt); ?>">
                    <?php endif; ?>
                </video>
                <?php if (!empty($all_sources)) : ?>
                <div class="mu-player-sources">
                    <span class="mu-player-sources__label">Quality:</span>
                    <div class="mu-player-sources__btns">
                        <?php foreach ($all_sources as $idx => $src) : ?>
                            <button type="button" class="mu-player-source-btn <?php echo $idx === 0 ? 'active' : ''; ?>" 
                                    data-src="<?php echo esc_url($src['url']); ?>" 
                                    data-quality="<?php echo esc_attr($src['quality'] ?? 'auto'); ?>">
                                <?php echo esc_html(strtoupper($src['quality'] ?? 'Auto')); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php elseif ($video_type === 'youtube') : ?>
                <iframe class="mu-player-iframe" src="<?php echo esc_url($primary_video); ?>" title="<?php echo esc_attr($title); ?>" allowfullscreen></iframe>
            <?php else : ?>
                <div class="mu-watch-error">
                    <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <h3><?php esc_html_e('No Video Available', 'astra-child'); ?></h3>
                    <p><?php esc_html_e('This title has no video source configured yet.', 'astra-child'); ?></p>
                    <?php if ($is_admin && $edit_link) : ?>
                        <a href="<?php echo esc_url($edit_link); ?>" class="mu-btn mu-btn--primary"><?php esc_html_e('Add Video Source', 'astra-child'); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MAIN CONTENT -->
    <!-- ============================================================ -->
    <main class="mu-watch-main">

        <!-- POSTER (LEFT) -->
        <aside class="mu-watch-poster">
            <div class="mu-watch-poster__image">
                <img src="<?php echo esc_url($poster_url); ?>" alt="<?php echo esc_attr($title); ?>" class="mu-watch-poster__img">
                <div class="mu-watch-poster__overlay">
                    <button class="mu-watch-poster__play-btn" aria-label="Play">
                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                </div>
            </div>
            <div class="mu-watch-poster__actions">
                <button class="mu-watch-poster__action-btn is-active" data-action="favorites">
                    <svg viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                    My List
                </button>
                <button class="mu-watch-poster__action-btn" data-action="share">
                    <svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98"/></svg>
                    Share
                </button>
                <button class="mu-watch-poster__action-btn" data-action="download">
                    <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Download
                </button>
            </div>
        </aside>

        <!-- INFO (CENTER) -->
        <div class="mu-watch-info">
            
            <h1 class="mu-watch-info__title"><?php echo esc_html($title); ?></h1>

            <div class="mu-watch-info__meta">
                <?php if ($rating) : ?>
                    <span class="mu-watch-info__meta-item mu-watch-info__meta-item--rating">
                        <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?php echo esc_html($rating); ?>
                    </span>
                    <span class="mu-watch-info__meta-dot"></span>
                <?php endif; ?>
                <?php if ($year) : ?>
                    <span class="mu-watch-info__meta-item"><?php echo esc_html($year); ?></span>
                    <span class="mu-watch-info__meta-dot"></span>
                <?php endif; ?>
                <?php if ($runtime) : ?>
                    <span class="mu-watch-info__meta-item"><?php echo esc_html($runtime); ?></span>
                    <span class="mu-watch-info__meta-dot"></span>
                <?php endif; ?>
                <?php if ($age_rating) : ?>
                    <span class="mu-watch-info__age"><?php echo esc_html($age_rating); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($genres)) : ?>
                <div class="mu-watch-info__genres">
                    <?php echo implode('', $genres); ?>
                </div>
            <?php endif; ?>

            <div class="mu-watch-info__actions">
                <button class="mu-watch-info__btn mu-watch-info__btn--primary mu-btn-play" data-id="<?php echo esc_attr($id); ?>">
                    <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    Play
                </button>
                <button class="mu-watch-info__btn mu-watch-info__btn--secondary mu-btn-fav" data-id="<?php echo esc_attr($id); ?>">
                    <svg viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                    My List
                </button>
            </div>

            <?php if ($description) : ?>
                <p class="mu-watch-info__overview"><?php echo wp_kses_post(wp_trim_words($description, 50)); ?></p>
            <?php endif; ?>

            <div class="mu-watch-info__details">
                <?php if (!empty($directors)) : ?>
                    <div class="mu-watch-info__detail">
                        <span class="mu-watch-info__detail-label">Director:</span>
                        <span class="mu-watch-info__detail-value"><?php echo implode(', ', $directors); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($writers)) : ?>
                    <div class="mu-watch-info__detail">
                        <span class="mu-watch-info__detail-label">Writer:</span>
                        <span class="mu-watch-info__detail-value"><?php echo esc_html(implode(', ', $writers)); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($audio_lang) : ?>
                    <div class="mu-watch-info__detail">
                        <span class="mu-watch-info__detail-label">Audio:</span>
                        <span class="mu-watch-info__detail-value"><?php echo esc_html($audio_lang); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($sub_vtt) : ?>
                    <div class="mu-watch-info__detail">
                        <span class="mu-watch-info__detail-label">Subtitles:</span>
                        <span class="mu-watch-info__detail-value">Available</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cast -->
            <?php if (!empty($cast)) : ?>
                <div class="mu-watch-cast">
                    <h3 class="mu-watch-cast__title">Cast</h3>
                    <div class="mu-watch-cast__list">
                        <?php foreach ($cast as $actor) :
                            $actor_img = get_term_meta($actor->term_id, 'image_url', true);
                        ?>
                            <a href="#" class="mu-watch-cast__item">
                                <?php if ($actor_img) : ?>
                                    <img src="<?php echo esc_url($actor_img); ?>" alt="<?php echo esc_attr($actor->name); ?>" class="mu-watch-cast__avatar">
                                <?php else : ?>
                                    <div class="mu-watch-cast__avatar-placeholder">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    </div>
                                <?php endif; ?>
                                <span class="mu-watch-cast__name"><?php echo esc_html($actor->name); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Episodes -->
            <?php if ($is_tv && !empty($tv_episodes)) : ?>
                <div class="mu-watch-episodes">
                    <div class="mu-watch-episodes__header">
                        <h3 class="mu-watch-episodes__title">Episodes</h3>
                        <?php
                        $seasons = [];
                        foreach ($tv_episodes as $ep) {
                            $s = get_post_meta($ep->ID, 'season_number', true) ?: 1;
                            if (!in_array($s, $seasons)) $seasons[] = $s;
                        }
                        if (count($seasons) > 1) :
                        ?>
                            <select class="mu-watch-episodes__select">
                                <?php foreach ($seasons as $s) : ?>
                                    <option value="<?php echo esc_attr($s); ?>" <?php selected($s, $current_season); ?>>Season <?php echo esc_html($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="mu-watch-episodes__list">
                        <?php foreach ($tv_episodes as $ep) :
                            $ep_season = get_post_meta($ep->ID, 'season_number', true) ?: 1;
                            $ep_num = get_post_meta($ep->ID, 'episode_number', true) ?: 1;
                            $ep_thumb = get_the_post_thumbnail_url($ep->ID, 'medium');
                            $ep_runtime = get_post_meta($ep->ID, 'runtime', true) ?: get_post_meta($ep->ID, '_duration', true) ?: '';
                            $ep_desc = get_the_excerpt($ep->ID);
                            $wbase = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : home_url('/watch');
                            $ep_watch_url = add_query_arg('episode_id', $ep->ID, $wbase);
                            $is_current = ((int) $ep->ID === $id);
                        ?>
                            <a href="<?php echo esc_url($ep_watch_url); ?>" class="mu-watch-episode<?php echo $is_current ? ' is-active' : ''; ?>" data-season="<?php echo esc_attr($ep_season); ?>">
                                <div class="mu-watch-episode__thumb" <?php echo $ep_thumb ? 'style="background-image: url(' . esc_url($ep_thumb) . ')"' : ''; ?>>
                                    <div class="mu-watch-episode__play">
                                        <div class="mu-watch-episode__play-icon">
                                            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mu-watch-episode__info">
                                    <span class="mu-watch-episode__number">Episode <?php echo esc_html($ep_num); ?></span>
                                    <h4 class="mu-watch-episode__title"><?php echo esc_html($ep->post_title); ?></h4>
                                    <?php if ($ep_desc) : ?>
                                        <p class="mu-watch-episode__desc"><?php echo esc_html($ep_desc); ?></p>
                                    <?php endif; ?>
                                    <div class="mu-watch-episode__meta">
                                        <?php if ($ep_runtime) : ?>
                                            <span><?php echo esc_html($ep_runtime); ?> min</span>
                                        <?php endif; ?>
                                        <div class="mu-watch-episode__progress">
                                            <div class="mu-watch-episode__progress-bar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($is_tv) : ?>
                <div class="mu-watch-empty">
                    <p>No episodes available yet.</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- SIDEBAR (RIGHT) -->
        <aside class="mu-watch-sidebar">
            
            <?php if ($up_next) : ?>
                <div class="mu-watch-upnext">
                    <div class="mu-watch-upnext__header">
                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        <span class="mu-watch-upnext__title">Up Next</span>
                    </div>
                    <a href="<?php echo esc_url($up_next['href']); ?>" class="mu-watch-upnext__card">
                        <img src="<?php echo esc_url($up_next['thumb'] ?: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 9"%3E%3Crect fill="%23333" width="16" height="9"/%3E%3C/svg%3E'); ?>" alt="" class="mu-watch-upnext__thumb">
                        <div class="mu-watch-upnext__info">
                            <h4 class="mu-watch-upnext__name"><?php echo esc_html($up_next['title']); ?></h4>
                            <p class="mu-watch-upnext__episode">Episode <?php echo esc_html($up_next['episode']); ?></p>
                            <div class="mu-watch-upnext__progress">
                                <div class="mu-watch-upnext__progress-bar" style="width: 0%"></div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!empty($related_movies)) : ?>
                <div class="mu-watch-related">
                    <h3 class="mu-watch-related__title">More Like This</h3>
                    <div class="mu-watch-related__list">
                        <?php foreach ($related_movies as $rel) :
                            $rel_thumb = get_the_post_thumbnail_url($rel->ID, 'medium');
                            $rel_year = get_post_meta($rel->ID, '_release_year', true) ?: '';
                            $rel_runtime = get_post_meta($rel->ID, '_duration', true) ?: '';
                            $rel_rating = get_post_meta($rel->ID, '_rating', true) ?: '';
                        ?>
                            <a href="<?php echo esc_url(get_permalink($rel->ID)); ?>" class="mu-watch-related__item">
                                <img src="<?php echo esc_url($rel_thumb ?: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 90"%3E%3Crect fill="%23333" width="60" height="90"/%3E%3C/svg%3E'); ?>" alt="<?php echo esc_attr($rel->post_title); ?>" class="mu-watch-related__thumb">
                                <div class="mu-watch-related__info">
                                    <h4 class="mu-watch-related__name"><?php echo esc_html($rel->post_title); ?></h4>
                                    <div class="mu-watch-related__meta">
                                        <?php if ($rel_year) : ?><span><?php echo esc_html(substr($rel_year, 0, 4)); ?></span><?php endif; ?>
                                        <?php if ($rel_runtime) : ?><span><?php echo esc_html($rel_runtime); ?> min</span><?php endif; ?>
                                        <?php if ($rel_rating) : ?><span><?php echo esc_html($rel_rating); ?></span><?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </aside>

    </main>

</div>

<?php wp_footer(); ?>
<script src="<?php echo esc_url(get_theme_file_uri('assets/js/ms-watch.js')); ?>"></script>
<script>
window.MOVIE_UI = <?php echo wp_json_encode($player_settings); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest/dist/hls.min.js"></script>
</body>
</html>
