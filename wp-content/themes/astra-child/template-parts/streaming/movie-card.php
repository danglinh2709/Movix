<?php
/**
 * ============================================================
 * Reusable Movie/Show Card component
 * File: template-parts/streaming/movie-card.php
 * Usage:
 *   get_template_part('template-parts/streaming/movie-card', null, ['post_id' => get_the_ID()]);
 * ============================================================
 */

$post_id = isset($args['post_id']) ? (int) $args['post_id'] : get_the_ID();
if (!$post_id) return;

$title = get_the_title($post_id);
$url   = get_permalink($post_id);

// Custom fields (support both with/without underscores)
$rating   = movie_ui_meta($post_id, ['rating', '_rating'], '');
$year     = movie_ui_meta($post_id, ['year', '_release_year'], '');
$duration = movie_ui_meta($post_id, ['duration', '_duration'], '');
$genre    = movie_ui_terms_text($post_id, 'genre', 2);
$trailer  = movie_ui_meta($post_id, ['trailer_url','_trailer_url'], '');

$poster = get_the_post_thumbnail($post_id, 'medium_large', [
  'class' => 'mu-poster',
  'loading' => 'lazy',
  'decoding' => 'async',
]);

if (!$poster) $poster = movie_ui_placeholder_poster();

$meta_bits = [];
if ($year) $meta_bits[] = esc_html($year);
if ($genre) $meta_bits[] = esc_html($genre);
if ($rating) $meta_bits[] = '★ ' . esc_html($rating);
$meta = $meta_bits ? implode(' • ', $meta_bits) : '';

$backdrop  = movie_ui_backdrop_url($post_id);
$overview  = wp_trim_words(get_post_field('post_content', $post_id), 24);
$post_type = get_post_type($post_id);
$type_badge = $post_type === 'tv_show' ? 'TV Show' : 'Movie';
$watch_url = add_query_arg('id', $post_id, home_url('/watch/'));
?>

<article class="mu-card"
  data-id="<?php echo esc_attr((string) $post_id); ?>"
  data-url="<?php echo esc_attr($url); ?>"
  data-watch="<?php echo esc_attr($watch_url); ?>"
  data-title="<?php echo esc_attr($title); ?>"
  data-meta="<?php echo esc_attr($meta); ?>"
  data-trailer="<?php echo esc_attr($trailer); ?>"
  data-backdrop="<?php echo esc_attr($backdrop ?: get_the_post_thumbnail_url($post_id, 'large')); ?>"
  data-overview="<?php echo esc_attr($overview); ?>"
  data-type="<?php echo esc_attr($type_badge); ?>"
>
  <div class="mu-card__inner">
    <a class="mu-card__link" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($title); ?>">
      <?php echo $poster; ?>
      <div class="mu-card__shade"></div>
    </a>
    
    <div class="mu-card__overlay" aria-hidden="true">
      <div class="mu-card__content-click" onclick="window.location.href='<?php echo esc_js($url); ?>'">
        <div class="mu-card__title"><?php echo esc_html($title); ?></div>
        <?php if ($meta): ?><div class="mu-card__meta"><?php echo $meta; ?></div><?php endif; ?>
      </div>

      <div class="mu-card__actions">
        <a class="mu-btn mu-btn--icon" href="<?php echo esc_url($watch_url); ?>" aria-label="Play" title="Watch Now">
          <span class="mu-ico-play" aria-hidden="true"></span>
        </a>
        <button class="mu-btn mu-btn--icon" type="button" data-mu-smart-play aria-label="Play Trailer" title="Play Trailer">
          +
        </button>
        <button class="mu-btn mu-btn--icon" type="button" data-mu-preview aria-label="More Info" title="More Info">
          <span class="mu-ico-info" style="color:#fff">i</span>
        </button>
      </div>
      
      <?php if ($duration): ?>
        <div class="mu-muted" style="margin-top:10px;font-size:11px;">⏱ <?php echo esc_html($duration); ?>m</div>
      <?php endif; ?>
      
      <div class="mu-card__progress-wrap">
        <div class="mu-card__progress-bar" data-mu-progress="<?php echo esc_attr((string) $post_id); ?>"></div>
      </div>
    </div>
  </div>
</article>

