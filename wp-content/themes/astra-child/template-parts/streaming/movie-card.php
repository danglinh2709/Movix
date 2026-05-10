<?php
/**
 * Premium Movie/TV Card
 * All data-* attributes used by movie-ui.js for:
 * - Trailer modal (play button)
 * - Watch page (play → fallback)
 * - My List toggle (favorite)
 * - More Info (detail page)
 * - Hover preview card
 *
 * Usage: get_template_part('template-parts/streaming/movie-card', null, ['post_id' => get_the_ID()]);
 */
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : get_the_ID();
if (!$post_id) {
    return;
}

$title = get_the_title($post_id);
$url   = get_permalink($post_id);
$ptype = get_post_type($post_id);

$rating    = movie_ui_meta($post_id, ['rating', '_rating'], '');
$year      = movie_ui_meta($post_id, ['year', '_release_year'], '');
$runtime   = movie_ui_meta($post_id, ['duration', '_duration'], '');
$quality   = movie_ui_meta($post_id, ['quality', '_quality'], '');
$age       = movie_ui_meta($post_id, ['age_rating', '_age_rating'], '');
$genres    = movie_ui_terms_text($post_id, 'genre', 2);
$country   = movie_ui_terms_text($post_id, 'country', 1);
$trailer   = movie_ui_meta($post_id, ['trailer_url', '_trailer_url'], '');
$video_url = movie_ui_meta($post_id, ['video_url', '_video_url'], '');
$backdrop  = movie_ui_backdrop_url($post_id);
$overview  = wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id) ?: get_post_field('post_excerpt', $post_id) ?: ''), 24);

// Watch URL
$watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));
$watch_url  = add_query_arg('id', $post_id, $watch_base);

// Post thumbnail poster
$poster = get_the_post_thumbnail($post_id, 'medium_large', [
    'class' => 'mu-poster',
    'loading' => 'lazy',
    'decoding' => 'async',
    'alt' => $title,
]);
if (!$poster) {
    $poster = '<div class="mu-poster mu-poster--placeholder" role="img" aria-label="' . esc_attr($title) . '"></div>';
}

// Type badge
$type_badge = $ptype === 'tv_show' ? __('TV Show', 'astra-child') : ($ptype === 'episode' ? __('Episode', 'astra-child') : __('Movie', 'astra-child'));

// New badge: published within last 14 days
$post_ts  = (int) get_post_time('U', true, $post_id);
$new_days = apply_filters('movie_ui_new_badge_days', 14, $post_id);
$is_new   = $post_ts && (time() - (int) $post_ts) < ($new_days * DAY_IN_SECONDS);

// Build meta string (used in data attributes)
$meta_parts = [];
if ($year)     $meta_parts[] = $year;
if ($genres)  $meta_parts[] = $genres;
if ($rating)  $meta_parts[] = '★ ' . $rating;
$meta_str = implode(' • ', $meta_parts);

// Episode info for TV shows
$episode_count = '';
$seasons = [];
$eps = get_posts([
    'post_type' => 'episode',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [['key' => 'tv_show_id', 'value' => $post_id, 'compare' => '=']]
]);
if (!empty($eps)) {
    $seasons = [];
    foreach ($eps as $eid) {
        $sn = (int) get_post_meta((int)$eid, 'season_number', true);
        if ($sn) $seasons[$sn] = true;
    }
    $total_eps = count($eps);
    $season_count = count($seasons);
    if ($season_count > 0) {
        $episode_count = $season_count . ' ' . __('Season', 'astra-child') . ($season_count > 1 ? 's' : '') . ' • ' . $total_eps . ' ' . __('Eps', 'astra-child');
    } else {
        $episode_count = $total_eps . ' ' . __('Eps', 'astra-child');
    }
}

// Quality badge
$quality_display = $quality ?: 'HD';

// Age rating
$age_display = $age ?: '';

// Determine play action - Play ALWAYS goes to Watch page (never trailer)
$has_video   = !empty($video_url);
$has_trailer = !empty($trailer);

// Play action: Watch page URL (video_url takes priority over trailer)
$play_action = 'watch:' . esc_url($watch_url);
?>
<article class="mu-card"
         data-id="<?php echo esc_attr((string) $post_id); ?>"
         data-url="<?php echo esc_url($url); ?>"
         data-watch="<?php echo esc_url($watch_url); ?>"
         data-title="<?php echo esc_attr($title); ?>"
         data-meta="<?php echo esc_attr($meta_str); ?>"
         data-trailer="<?php echo esc_attr($trailer); ?>"
         data-backdrop="<?php echo esc_url($backdrop ?: get_the_post_thumbnail_url($post_id, 'large') ?: ''); ?>"
         data-overview="<?php echo esc_attr($overview); ?>"
         data-type="<?php echo esc_attr($type_badge); ?>"
         data-play-action="<?php echo esc_attr($play_action); ?>"
         data-has-trailer="<?php echo $has_trailer ? '1' : '0'; ?>"
         data-has-video="<?php echo $has_video ? '1' : '0'; ?>"
         data-year="<?php echo esc_attr($year); ?>"
         data-rating="<?php echo esc_attr($rating); ?>"
         data-runtime="<?php echo esc_attr($runtime); ?>"
         data-quality="<?php echo esc_attr($quality_display); ?>"
         data-age="<?php echo esc_attr($age_display); ?>"
         data-genres="<?php echo esc_attr($genres); ?>"
         data-country="<?php echo esc_attr($country); ?>"
         data-episode-count="<?php echo esc_attr($episode_count); ?>"
         data-post-type="<?php echo esc_attr($ptype); ?>"
         itemscope
         itemtype="https://schema.org/Movie">

    <div class="mu-card__inner">

        <?php // Main link (poster image + badges) ?>
        <a class="mu-card__link" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($title); ?>">
            <?php echo $poster; ?>

            <?php // Type badge (top-left) ?>
            <span class="mu-card__badge mu-card__badge--type"><?php echo esc_html($type_badge); ?></span>

            <?php // NEW badge (bottom-right corner) ?>
            <?php if ($is_new) : ?>
                <span class="mu-card__badge mu-card__badge--new"><?php esc_html_e('New', 'astra-child'); ?></span>
            <?php endif; ?>

            <?php // Rating badge (top-right) ?>
            <?php if ($rating) : ?>
                <span class="mu-card__badge mu-card__badge--rating">
                    <span class="mu-card__star" aria-hidden="true">★</span><?php echo esc_html($rating); ?>
                </span>
            <?php endif; ?>

            <?php // Quality badge ?>
            <?php if ($quality_display && $quality_display !== 'HD') : ?>
                <span class="mu-card__badge mu-card__badge--quality"><?php echo esc_html($quality_display); ?></span>
            <?php endif; ?>

            <?php // Episode count for TV Shows ?>
            <?php if ($episode_count && $ptype === 'tv_show') : ?>
                <span class="mu-card__badge mu-card__badge--episodes"><?php echo esc_html($episode_count); ?></span>
            <?php endif; ?>

            <?php // Hover shade overlay ?>
            <div class="mu-card__shade" aria-hidden="true"></div>
        </a>

        <?php // Hover overlay: actions + info ?>
        <div class="mu-card__overlay" aria-hidden="true">

            <?php // Title + meta (clickable) ?>
            <a class="mu-card__content-click" href="<?php echo esc_url($url); ?>">
                <div class="mu-card__title"><?php echo esc_html($title); ?></div>
                <?php if ($meta_str) : ?>
                    <div class="mu-card__meta"><?php echo esc_html($meta_str); ?></div>
                <?php endif; ?>
            </a>

            <?php // Action buttons row ?>
            <div class="mu-card__actions">

                <?php // Play / Watch button - ALWAYS goes to Watch page (never trailer) ?>
                <button type="button"
                        class="mu-btn mu-btn--play mu-btn--icon"
                        data-mu-card-play
                        data-id="<?php echo esc_attr((string) $post_id); ?>"
                        data-play-action="<?php echo esc_attr($play_action); ?>"
                        data-watch-url="<?php echo esc_url($watch_url); ?>"
                        aria-label="<?php esc_attr_e('Play / Watch', 'astra-child'); ?>"
                        title="<?php esc_attr_e('Play', 'astra-child'); ?>">
                    <svg class="mu-ico-play" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </button>

                <?php // My List / Favorite toggle ?>
                <button type="button"
                        class="mu-btn mu-btn--icon mu-fav"
                        data-id="<?php echo esc_attr((string) $post_id); ?>"
                        aria-pressed="false"
                        aria-label="<?php esc_attr_e('Add to My List', 'astra-child'); ?>"
                        title="<?php esc_attr_e('My List', 'astra-child'); ?>"
                        data-mu-fav-toggle>
                    <svg class="mu-ico-plus" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line class="mu-ico-plus-h" x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                </button>

                <?php // Trailer / Preview button ?>
                <?php if ($has_trailer) : ?>
                    <button type="button"
                            class="mu-btn mu-btn--icon"
                            data-mu-smart-play
                            data-id="<?php echo esc_attr((string) $post_id); ?>"
                            data-trailer="<?php echo esc_attr($trailer); ?>"
                            aria-label="<?php esc_attr_e('Watch Trailer', 'astra-child'); ?>"
                            title="<?php esc_attr_e('Trailer', 'astra-child'); ?>">
                        <svg class="mu-ico-trailer" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="2" y="4" width="20" height="16" rx="3"/>
                            <path d="M10 9L16 12L10 15V9Z" fill="currentColor"/>
                        </svg>
                    </button>
                <?php endif; ?>

                <?php // More Info / Details button ?>
                <button type="button"
                        class="mu-btn mu-btn--icon"
                        data-mu-preview
                        data-id="<?php echo esc_attr((string) $post_id); ?>"
                        data-url="<?php echo esc_url($url); ?>"
                        aria-label="<?php esc_attr_e('More Info', 'astra-child'); ?>"
                        title="<?php esc_attr_e('More Info', 'astra-child'); ?>">
                    <svg class="mu-ico-info" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <circle cx="12" cy="5" r="1.5" fill="currentColor"/>
                    </svg>
                </button>

                <?php // Episode list (TV Shows only) ?>
                <?php if ($ptype === 'tv_show' && $episode_count) : ?>
                    <button type="button"
                            class="mu-btn mu-btn--icon mu-btn--episodes"
                            data-mu-show-episodes
                            data-id="<?php echo esc_attr((string) $post_id); ?>"
                            data-watch-url="<?php echo esc_url($watch_url); ?>"
                            aria-label="<?php esc_attr_e('Episodes', 'astra-child'); ?>"
                            title="<?php esc_attr_e('Episodes', 'astra-child'); ?>">
                        <svg class="mu-ico-episodes" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <line x1="4" y1="8" x2="20" y2="8"/>
                            <line x1="4" y1="12" x2="20" y2="12"/>
                            <line x1="4" y1="16" x2="20" y2="16"/>
                        </svg>
                    </button>
                <?php endif; ?>

            </div>

            <?php // Bottom meta ?>
            <div class="mu-card__footer-meta">
                <?php if ($runtime) : ?>
                    <span class="mu-card__duration"><?php echo esc_html($runtime); ?>m</span>
                <?php endif; ?>
                <?php if ($age_display) : ?>
                    <span class="mu-card__age"><?php echo esc_html($age_display); ?></span>
                <?php endif; ?>
            </div>

            <?php // Watch progress bar (filled by JS) ?>
            <div class="mu-card__progress-wrap">
                <div class="mu-card__progress-bar" data-mu-progress="<?php echo esc_attr((string) $post_id); ?>"></div>
            </div>

        </div>
    </div>
</article>
