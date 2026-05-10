<?php
/**
 * Template Name: TV Show Detail (Premium OTT)
 * Full cinematic TV show detail page with seasons, episodes, tabs, and related shows.
 */
defined('ABSPATH') || exit;
get_header();
get_template_part('template-parts/streaming/header');

while (have_posts()) :
    the_post();
    $id = get_the_ID();
    
    // =================================================================
    // FETCH ALL META DATA
    // =================================================================
    $backdrop = movie_ui_backdrop_url($id);
    $poster = get_the_post_thumbnail_url($id, 'large');
    $title = get_the_title();
    
    // Basic info
    $rating = movie_ui_meta($id, ['rating', '_rating', '_tmdb_rating'], '');
    $year = movie_ui_meta($id, ['year', '_release_year', 'release_year'], '');
    if ($year && strlen($year) > 4) $year = substr($year, 0, 4);
    
    // Genres
    $genres = wp_get_post_terms($id, 'genre', ['fields' => 'all']);
    $genre_links = [];
    foreach (array_slice($genres, 0, 4) as $g) {
        $genre_links[] = '<a href="' . esc_url(get_term_link($g)) . '">' . esc_html($g->name) . '</a>';
    }
    $genre_text = implode(', ', $genre_links);
    
    // Cast
    $cast = wp_get_post_terms($id, 'actor', ['fields' => 'all']);
    $cast_data = [];
    foreach (array_slice($cast, 0, 12) as $actor) {
        $actor_img = get_term_meta($actor->term_id, 'image', true);
        $cast_data[] = [
            'name' => $actor->name,
            'slug' => $actor->slug,
            'image' => $actor_img ?: '',
            'character' => '',
        ];
    }
    
    // Director
    $directors = wp_get_post_terms($id, 'director', ['fields' => 'all']);
    $director_text = implode(', ', array_map(function($d) { return esc_html($d->name); }, $directors));
    
    // Status
    $status = movie_ui_meta($id, ['status', '_status'], 'Returning');
    
    // Release date
    $release_date = movie_ui_meta($id, ['release_date', '_release_date'], '');
    
    // Vote count
    $vote_count = movie_ui_meta($id, ['vote_count', '_vote_count'], '');
    
    // Quality
    $quality = movie_ui_meta($id, ['quality', '_quality'], 'HD');
    
    // Age rating
    $age_rating = movie_ui_meta($id, ['age_rating', '_age_rating'], '');
    
    // Video URLs
    $trailer = movie_ui_meta($id, ['trailer_url', '_trailer_url'], '');
    
    // Content
    $content = get_the_content();
    $overview = $content ? wp_trim_words(wp_strip_all_tags($content), 60) : '';
    
    // Episode data
    $episodes = get_posts([
        'post_type' => 'episode',
        'posts_per_page' => -1,
        'meta_query' => [['key' => 'tv_show_id', 'value' => $id, 'compare' => '=']],
        'orderby' => ['meta_value_num' => 'ASC', 'date' => 'ASC'],
        'meta_key' => 'episode_number',
    ]);
    
    // Group episodes by season
    $seasons = [];
    foreach ($episodes as $ep) {
        $sn = (int) get_post_meta($ep->ID, 'season_number', true) ?: 1;
        if (!isset($seasons[$sn])) $seasons[$sn] = [];
        $seasons[$sn][] = $ep;
    }
    krsort($seasons);
    $total_seasons = count($seasons);
    $total_episodes = count($episodes);
    
    // First episode for play
    $first_ep = !empty($episodes) ? $episodes[0] : null;
    
    // Recent episode
    $recent_ep = get_posts([
        'post_type' => 'episode',
        'posts_per_page' => 1,
        'meta_query' => [['key' => 'tv_show_id', 'value' => $id, 'compare' => '=']],
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids',
    ]);
    $recent_ep_id = !empty($recent_ep) ? (int) $recent_ep[0] : ($first_ep ? (int) $first_ep->ID : 0);
    $recent_ts = $recent_ep_id ? (int) get_post_time('U', true, $recent_ep_id) : 0;
    $is_fresh = $recent_ts && (time() - $recent_ts) < 7 * DAY_IN_SECONDS;
    
    // URLs
    $watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : trailingslashit(home_url('watch'));
    $play_id = $recent_ep_id ?: ($first_ep ? (int) $first_ep->ID : 0);
    $watch_url = $play_id ? add_query_arg('id', $play_id, $watch_base) : '';
    
    // Format numbers
    $rating_fmt = $rating ? number_format((float) $rating, 1) : '';
    
    // AJAX nonce
    $ajax_nonce = wp_create_nonce('mu_detail_ajax');
    $is_logged_in = is_user_logged_in();
?>

<style>
/* TV Show Detail uses same styles as Movie Detail */
.mdetail-page { background: #08080c; min-height: 100vh; padding-top: 70px; }
.mdetail-hero { position: relative; min-height: 500px; display: flex; align-items: flex-end; padding: 0 4% 50px; }
.mdetail-hero__bg { position: absolute; inset: 0; background-size: cover; background-position: center top; background-repeat: no-repeat; }
.mdetail-hero__grad { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(8,8,12,0.3) 0%, rgba(8,8,12,0.6) 40%, rgba(8,8,12,0.95) 80%, #08080c 100%); }
.mdetail-hero__content { position: relative; max-width: 1400px; margin: 0 auto; width: 100%; display: flex; gap: 40px; align-items: flex-end; }
.mdetail-hero__poster-wrap { flex-shrink: 0; position: relative; width: 280px; }
.mdetail-hero__poster { width: 100%; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.6); }
.mdetail-hero__poster-overlay { position: absolute; inset: 0; border-radius: 12px; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; cursor: pointer; }
.mdetail-hero__poster-wrap:hover .mdetail-hero__poster-overlay { opacity: 1; }
.mdetail-hero__play-btn { width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,0.9); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s, background 0.2s; }
.mdetail-hero__play-btn:hover { transform: scale(1.1); background: #fff; }
.mdetail-score { position: absolute; bottom: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #e50914, #b81d24); color: #fff; padding: 8px 16px; border-radius: 20px; font-weight: 800; font-size: 1rem; box-shadow: 0 4px 15px rgba(229,9,20,0.4); display: flex; align-items: center; gap: 4px; }
.mdetail-score__star { font-size: 0.9rem; }
.mdetail-hero__info { flex: 1; padding-bottom: 10px; }
.mdetail-hero__type { font-size: 0.85rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
.mdetail-hero__title { font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 800; color: #fff; margin: 0 0 16px; line-height: 1.1; letter-spacing: -0.02em; }
.mdetail-hero__meta { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.mdetail-hero__meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.9rem; color: rgba(255,255,255,0.7); }
.mdetail-hero__meta-dot { width: 4px; height: 4px; background: rgba(255,255,255,0.4); border-radius: 50%; }
.mdetail-hero__rating { color: #ffd700; font-weight: 600; }
.mdetail-hero__genres { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.mdetail-hero__genre-tag { padding: 6px 14px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); border-radius: 20px; font-size: 0.8rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; }
.mdetail-hero__genre-tag:hover { background: rgba(255,255,255,0.15); color: #fff; }
.mdetail-hero__show-info { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
.mdetail-hero__show-stat { display: flex; flex-direction: column; gap: 2px; }
.mdetail-hero__show-stat-label { font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; }
.mdetail-hero__show-stat-value { font-size: 0.9rem; color: rgba(255,255,255,0.9); }
.mdetail-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.mdetail-btn { display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; border-radius: 8px; font-size: 1rem; font-weight: 700; text-decoration: none; cursor: pointer; transition: all 0.2s; border: none; }
.mdetail-btn--primary { background: #e50914; color: #fff; }
.mdetail-btn--primary:hover { background: #ff1a25; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(229,9,20,0.4); }
.mdetail-btn--secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
.mdetail-btn--secondary:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); }
.mdetail-btn--fav { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
.mdetail-btn--fav:hover { background: rgba(255,255,255,0.15); }
.mdetail-btn--fav.is-on { background: rgba(229,9,20,0.2); border-color: rgba(229,9,20,0.5); color: #e50914; }
.mdetail-info-strip { background: rgba(255,255,255,0.03); border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 4%; }
.mdetail-info-strip__inner { max-width: 1400px; margin: 0 auto; display: flex; gap: 30px; flex-wrap: wrap; }
.mdetail-info-item { display: flex; flex-direction: column; gap: 4px; }
.mdetail-info-label { font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.1em; }
.mdetail-info-value { font-size: 0.9rem; color: rgba(255,255,255,0.9); font-weight: 500; }

/* Tabs */
.mdetail-tabs { background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.06); position: sticky; top: 70px; z-index: 50; }
.mdetail-tabs__inner { max-width: 1400px; margin: 0 auto; display: flex; gap: 4px; padding: 0 4%; overflow-x: auto; scrollbar-width: none; }
.mdetail-tabs__inner::-webkit-scrollbar { display: none; }
.mdetail-tab { padding: 16px 20px; font-size: 0.9rem; font-weight: 600; color: rgba(255,255,255,0.5); background: transparent; border: none; cursor: pointer; white-space: nowrap; transition: color 0.2s; position: relative; }
.mdetail-tab:hover { color: rgba(255,255,255,0.8); }
.mdetail-tab.is-active { color: #e50914; }
.mdetail-tab.is-active::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: #e50914; border-radius: 3px 3px 0 0; }
.mdetail-tab-content { display: none; padding: 40px 4%; max-width: 1400px; margin: 0 auto; }
.mdetail-tab-content.is-active { display: block; }

/* Overview */
.mdetail-overview__text { font-size: 1.05rem; line-height: 1.8; color: rgba(255,255,255,0.8); max-width: 800px; margin-bottom: 40px; }
.mdetail-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
.mdetail-info-row { display: flex; gap: 12px; }
.mdetail-info-row__label { font-size: 0.9rem; color: rgba(255,255,255,0.5); min-width: 120px; }
.mdetail-info-row__value { font-size: 0.9rem; color: rgba(255,255,255,0.9); }

/* Cast */
.mdetail-cast { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 20px; }
.mdetail-cast-card { text-align: center; }
.mdetail-cast-card__img { width: 100%; aspect-ratio: 1; border-radius: 12px; object-fit: cover; background: rgba(255,255,255,0.05); margin-bottom: 10px; }
.mdetail-cast-card__name { font-size: 0.85rem; font-weight: 600; color: #fff; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mdetail-cast-card__char { font-size: 0.75rem; color: rgba(255,255,255,0.5); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mdetail-empty { text-align: center; padding: 40px 20px; color: rgba(255,255,255,0.4); }

/* Episodes */
.mdetail-seasons { display: flex; flex-direction: column; gap: 24px; }
.mdetail-season { background: rgba(255,255,255,0.02); border-radius: 12px; overflow: hidden; }
.mdetail-season__header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: rgba(255,255,255,0.04); cursor: pointer; transition: background 0.2s; }
.mdetail-season__header:hover { background: rgba(255,255,255,0.06); }
.mdetail-season__title { font-size: 1rem; font-weight: 700; color: #fff; }
.mdetail-season__count { font-size: 0.85rem; color: rgba(255,255,255,0.5); }
.mdetail-season__toggle { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.5); transition: transform 0.3s; }
.mdetail-season.is-open .mdetail-season__toggle { transform: rotate(180deg); }
.mdetail-season__episodes { display: none; }
.mdetail-season.is-open .mdetail-season__episodes { display: block; }
.mdetail-episode { display: flex; gap: 16px; padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.05); transition: background 0.2s; cursor: pointer; }
.mdetail-episode:hover { background: rgba(255,255,255,0.03); }
.mdetail-episode__num { font-size: 0.85rem; color: rgba(255,255,255,0.4); min-width: 30px; }
.mdetail-episode__thumb { width: 120px; aspect-ratio: 16/9; border-radius: 6px; object-fit: cover; background: #1a1a2e; flex-shrink: 0; }
.mdetail-episode__info { flex: 1; min-width: 0; }
.mdetail-episode__title { font-size: 0.9rem; font-weight: 600; color: #fff; margin-bottom: 4px; }
.mdetail-episode__desc { font-size: 0.8rem; color: rgba(255,255,255,0.5); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.mdetail-episode__duration { font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-top: 4px; }
.mdetail-episode__play { width: 36px; height: 36px; border-radius: 50%; background: #e50914; border: none; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; align-self: center; transition: background 0.2s; }
.mdetail-episode__play:hover { background: #ff1a25; }

/* More Like This */
.mdetail-more__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.mdetail-more__title { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0; }
.mdetail-more__link { font-size: 0.9rem; color: rgba(255,255,255,0.5); text-decoration: none; transition: color 0.2s; }
.mdetail-more__link:hover { color: #e50914; }
.mdetail-more__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 20px; }
.mdetail-relcard { cursor: pointer; transition: transform 0.3s; }
.mdetail-relcard:hover { transform: scale(1.05); }
.mdetail-relcard__poster { width: 100%; aspect-ratio: 2/3; object-fit: cover; border-radius: 10px; background: #1a1a2e; margin-bottom: 10px; }
.mdetail-relcard__title { font-size: 0.85rem; font-weight: 600; color: #fff; margin: 0 0 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mdetail-relcard__meta { font-size: 0.75rem; color: rgba(255,255,255,0.5); }

/* Trailer Modal - Use global mu-trailer-modal styles from movie-ui.css */

/* Responsive */
@media (max-width: 900px) {
    .mdetail-hero__content { flex-direction: column; align-items: center; text-align: center; }
    .mdetail-hero__info { width: 100%; }
    .mdetail-hero__meta { justify-content: center; }
    .mdetail-hero__genres { justify-content: center; }
    .mdetail-hero__show-info { justify-content: center; }
    .mdetail-actions { justify-content: center; }
}
@media (max-width: 600px) {
    .mdetail-hero { min-height: auto; padding-bottom: 30px; }
    .mdetail-hero__poster-wrap { width: 140px; }
    .mdetail-hero__title { font-size: 1.5rem; }
    .mdetail-btn { padding: 12px 20px; font-size: 0.9rem; }
    .mdetail-episode__thumb { width: 80px; }
}

/* FIX TV detail tab buttons hover */
.mdetail-tabs {
  background: rgba(10,10,14,.88) !important;
  backdrop-filter: blur(14px) !important;
}

.mdetail-tabs__inner {
  gap: 10px !important;
  padding: 12px 4% !important;
}

.mdetail-tab {
  padding: 12px 24px !important;
  border-radius: 999px !important;
  background: transparent !important;
  border: 1px solid transparent !important;
  color: rgba(255,255,255,.56) !important;
  font-size: 15px !important;
  font-weight: 800 !important;
  transition: all .22s ease !important;
}

.mdetail-tab:hover {
  background: rgba(255,255,255,.08) !important;
  border-color: rgba(255,255,255,.14) !important;
  color: #fff !important;
  transform: translateY(-1px) !important;
}

.mdetail-tab.is-active {
  background: #e50914 !important;
  border-color: #e50914 !important;
  color: #fff !important;
  box-shadow: 0 10px 28px rgba(229,9,20,.32) !important;
}

.mdetail-tab.is-active::after {
  display: none !important;
}
</style>

<div class="mdetail-page">
    <?php // HERO SECTION ?>
    <section class="mdetail-hero">
        <div class="mdetail-hero__bg" style="<?php echo $backdrop ? 'background-image:url(' . esc_url($backdrop) . ');' : ''; ?>"></div>
        <div class="mdetail-hero__grad"></div>
        
        <div class="mdetail-hero__content">
            <div class="mdetail-hero__poster-wrap">
                <?php if ($poster) : ?>
                    <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" class="mdetail-hero__poster" loading="eager">
                <?php else : ?>
                    <div class="mdetail-hero__poster" style="aspect-ratio:2/3;background:#1a1a2e;"></div>
                <?php endif; ?>
                
                <?php if ($trailer) : ?>
                    <div class="mdetail-hero__poster-overlay" onclick="openTrailerModal('<?php echo esc_attr($trailer); ?>')">
                        <button class="mdetail-hero__play-btn" type="button">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#e50914"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if ($rating) : ?>
                    <div class="mdetail-score">
                        <?php echo esc_html($rating_fmt); ?><span class="mdetail-score__star">★</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mdetail-hero__info">
                <p class="mdetail-hero__type"><?php esc_html_e('TV Series', 'astra-child'); ?></p>
                <h1 class="mdetail-hero__title"><?php echo esc_html($title); ?></h1>
                
                <div class="mdetail-hero__meta">
                    <?php if ($year) : ?>
                        <span class="mdetail-hero__meta-item"><?php echo esc_html($year); ?></span>
                        <span class="mdetail-hero__meta-dot"></span>
                    <?php endif; ?>
                    <?php if ($total_seasons) : ?>
                        <span class="mdetail-hero__meta-item"><?php echo esc_html($total_seasons); ?> <?php esc_html_e('Seasons', 'astra-child'); ?></span>
                        <span class="mdetail-hero__meta-dot"></span>
                    <?php endif; ?>
                    <?php if ($total_episodes) : ?>
                        <span class="mdetail-hero__meta-item"><?php echo esc_html($total_episodes); ?> <?php esc_html_e('Episodes', 'astra-child'); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($genre_text) : ?>
                    <div class="mdetail-hero__genres"><?php echo $genre_text; ?></div>
                <?php endif; ?>
                
                <div class="mdetail-hero__show-info">
                    <div class="mdetail-hero__show-stat">
                        <span class="mdetail-hero__show-stat-label"><?php esc_html_e('Status', 'astra-child'); ?></span>
                        <span class="mdetail-hero__show-stat-value"><?php echo esc_html($status); ?></span>
                    </div>
                    <?php if ($release_date) : ?>
                    <div class="mdetail-hero__show-stat">
                        <span class="mdetail-hero__show-stat-label"><?php esc_html_e('First Aired', 'astra-child'); ?></span>
                        <span class="mdetail-hero__show-stat-value"><?php echo esc_html($release_date); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($rating) : ?>
                    <div class="mdetail-hero__show-stat">
                        <span class="mdetail-hero__show-stat-label"><?php esc_html_e('Rating', 'astra-child'); ?></span>
                        <span class="mdetail-hero__show-stat-value mdetail-hero__rating">★ <?php echo esc_html($rating_fmt); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="mdetail-actions">
                    <?php if ($watch_url) : ?>
                        <a href="<?php echo esc_url($watch_url); ?>" class="mdetail-btn mdetail-btn--primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            <?php esc_html_e('Play Latest Episode', 'astra-child'); ?>
                        </a>
                    <?php elseif ($trailer) : ?>
                        <button class="mdetail-btn mdetail-btn--primary" type="button" onclick="openTrailerModal('<?php echo esc_attr($trailer); ?>')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            <?php esc_html_e('Play Trailer', 'astra-child'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <button class="mdetail-btn mdetail-btn--fav" type="button" id="favBtn" data-id="<?php echo esc_attr($id); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line class="fav-plus" x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span class="fav-label"><?php esc_html_e('My List', 'astra-child'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <?php // INFO STRIP ?>
    <div class="mdetail-info-strip">
        <div class="mdetail-info-strip__inner">
            <div class="mdetail-info-item">
                <span class="mdetail-info-label"><?php esc_html_e('Quality', 'astra-child'); ?></span>
                <span class="mdetail-info-value"><?php echo esc_html($quality); ?></span>
            </div>
            <div class="mdetail-info-item">
                <span class="mdetail-info-label"><?php esc_html_e('Seasons', 'astra-child'); ?></span>
                <span class="mdetail-info-value"><?php echo esc_html($total_seasons); ?></span>
            </div>
            <div class="mdetail-info-item">
                <span class="mdetail-info-label"><?php esc_html_e('Episodes', 'astra-child'); ?></span>
                <span class="mdetail-info-value"><?php echo esc_html($total_episodes); ?></span>
            </div>
            <?php if ($age_rating) : ?>
            <div class="mdetail-info-item">
                <span class="mdetail-info-label"><?php esc_html_e('Age Rating', 'astra-child'); ?></span>
                <span class="mdetail-info-value"><?php echo esc_html($age_rating); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php // TABS ?>
    <nav class="mdetail-tabs">
        <div class="mdetail-tabs__inner">
            <button class="mdetail-tab is-active" data-tab="overview"><?php esc_html_e('Overview', 'astra-child'); ?></button>
            <button class="mdetail-tab" data-tab="episodes"><?php esc_html_e('Episodes', 'astra-child'); ?></button>
            <button class="mdetail-tab" data-tab="cast"><?php esc_html_e('Cast', 'astra-child'); ?></button>
        </div>
    </nav>
    
    <?php // Overview Tab ?>
    <div class="mdetail-tab-content is-active" id="tab-overview">
        <div class="mdetail-overview__text"><?php echo wp_kses_post($overview ?: __('No overview available.', 'astra-child')); ?></div>
        
        <div class="mdetail-info-grid">
            <div class="mdetail-info-row">
                <span class="mdetail-info-row__label"><?php esc_html_e('Status', 'astra-child'); ?></span>
                <span class="mdetail-info-row__value"><?php echo esc_html($status); ?></span>
            </div>
            <?php if ($release_date) : ?>
            <div class="mdetail-info-row">
                <span class="mdetail-info-row__label"><?php esc_html_e('First Aired', 'astra-child'); ?></span>
                <span class="mdetail-info-row__value"><?php echo esc_html($release_date); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($director_text) : ?>
            <div class="mdetail-info-row">
                <span class="mdetail-info-row__label"><?php esc_html_e('Creator', 'astra-child'); ?></span>
                <span class="mdetail-info-row__value"><?php echo esc_html($director_text); ?></span>
            </div>
            <?php endif; ?>
            <div class="mdetail-info-row">
                <span class="mdetail-info-row__label"><?php esc_html_e('Total Seasons', 'astra-child'); ?></span>
                <span class="mdetail-info-row__value"><?php echo esc_html($total_seasons); ?></span>
            </div>
            <div class="mdetail-info-row">
                <span class="mdetail-info-row__label"><?php esc_html_e('Total Episodes', 'astra-child'); ?></span>
                <span class="mdetail-info-row__value"><?php echo esc_html($total_episodes); ?></span>
            </div>
        </div>
    </div>
    
    <?php // Episodes Tab ?>
    <div class="mdetail-tab-content" id="tab-episodes">
        <?php if (!empty($seasons)) : ?>
            <div class="mdetail-seasons">
                <?php foreach ($seasons as $sn => $eps) : ?>
                    <div class="mdetail-season" data-season="<?php echo esc_attr($sn); ?>">
                        <div class="mdetail-season__header" onclick="toggleSeason(this)">
                            <div>
                                <span class="mdetail-season__title"><?php printf(esc_html__('Season %d', 'astra-child'), $sn); ?></span>
                                <span class="mdetail-season__count">(<?php echo count($eps); ?> <?php esc_html_e('episodes', 'astra-child'); ?>)</span>
                            </div>
                            <span class="mdetail-season__toggle">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </span>
                        </div>
                        <div class="mdetail-season__episodes">
                            <?php foreach ($eps as $ep) :
                                $ep_id = $ep->ID;
                                $ep_title = get_the_title($ep_id);
                                $ep_thumb = get_the_post_thumbnail_url($ep_id, 'medium') ?: '';
                                $ep_duration = movie_ui_meta($ep_id, ['duration', '_duration'], '');
                                $ep_desc = wp_trim_words(wp_strip_all_tags($ep->post_content ?: ''), 20);
                                $ep_watch_url = add_query_arg('id', $ep_id, $watch_base);
                            ?>
                                <div class="mdetail-episode" onclick="window.location.href='<?php echo esc_url($ep_watch_url); ?>'">
                                    <span class="mdetail-episode__num"><?php echo esc_html(get_post_meta($ep_id, 'episode_number', true) ?: $sn); ?></span>
                                    <?php if ($ep_thumb) : ?>
                                        <img class="mdetail-episode__thumb" src="<?php echo esc_url($ep_thumb); ?>" alt="" loading="lazy">
                                    <?php else : ?>
                                        <div class="mdetail-episode__thumb"></div>
                                    <?php endif; ?>
                                    <div class="mdetail-episode__info">
                                        <p class="mdetail-episode__title"><?php echo esc_html($ep_title); ?></p>
                                        <?php if ($ep_desc) : ?>
                                            <p class="mdetail-episode__desc"><?php echo esc_html($ep_desc); ?></p>
                                        <?php endif; ?>
                                        <?php if ($ep_duration) : ?>
                                            <p class="mdetail-episode__duration"><?php echo esc_html($ep_duration); ?> min</p>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?php echo esc_url($ep_watch_url); ?>" class="mdetail-episode__play" onclick="event.stopPropagation();">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="mdetail-empty">
                <p><?php esc_html_e('Episodes are not available yet.', 'astra-child'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php // Cast Tab ?>
    <div class="mdetail-tab-content" id="tab-cast">
        <?php if (!empty($cast_data)) : ?>
            <div class="mdetail-cast">
                <?php foreach ($cast_data as $actor) : ?>
                    <div class="mdetail-cast-card">
                        <?php if ($actor['image']) : ?>
                            <img src="<?php echo esc_url($actor['image']); ?>" alt="<?php echo esc_attr($actor['name']); ?>" class="mdetail-cast-card__img" loading="lazy">
                        <?php else : ?>
                            <div class="mdetail-cast-card__img" style="display:flex;align-items:center;justify-content:center;font-size:2rem;color:rgba(255,255,255,0.3);">
                                <?php echo esc_html(substr($actor['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <p class="mdetail-cast-card__name"><?php echo esc_html($actor['name']); ?></p>
                        <?php if ($actor['character']) : ?>
                            <p class="mdetail-cast-card__char"><?php echo esc_html($actor['character']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="mdetail-empty">
                <p><?php esc_html_e('Cast information is not available yet.', 'astra-child'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php // MORE LIKE THIS ?>
    <?php
    $genre_ids = wp_get_post_terms($id, 'genre', ['fields' => 'ids']);
    $rel_args = [
        'post_type' => ['tv_show', 'movie'],
        'posts_per_page' => 12,
        'post__not_in' => [$id],
        'ignore_sticky_posts' => true,
    ];
    if (!is_wp_error($genre_ids) && $genre_ids) {
        $rel_args['tax_query'] = [[
            'taxonomy' => 'genre',
            'field' => 'term_id',
            'terms' => array_map('intval', $genre_ids),
        ]];
    }
    $rel = new WP_Query($rel_args);
    if (!$rel->have_posts()) {
        wp_reset_postdata();
        $rel = movie_ui_query(['posts_per_page' => 12, 'post__not_in' => [$id]]);
    }
    ?>
    
    <?php if ($rel->have_posts()) : ?>
    <section class="mdetail-tab-content" style="padding-top:0;">
        <div class="mdetail-more">
            <div class="mdetail-more__header">
                <h2 class="mdetail-more__title"><?php esc_html_e('More Like This', 'astra-child'); ?></h2>
                <?php if (!empty($genres)) : ?>
                    <a href="<?php echo esc_url(get_term_link($genres[0])); ?>" class="mdetail-more__link"><?php esc_html_e('View All', 'astra-child'); ?> →</a>
                <?php endif; ?>
            </div>
            <div class="mdetail-more__grid">
                <?php while ($rel->have_posts()) : $rel->the_post(); ?>
                    <a class="mdetail-relcard" href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <img class="mdetail-relcard__poster" src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'medium')); ?>" alt="<?php the_title(); ?>" loading="lazy">
                        <?php else : ?>
                            <div class="mdetail-relcard__poster"></div>
                        <?php endif; ?>
                        <h3 class="mdetail-relcard__title"><?php the_title(); ?></h3>
                        <p class="mdetail-relcard__meta"><?php echo esc_html(movie_ui_meta(get_the_ID(), ['year', '_release_year'], '')); ?></p>
                    </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php // TRAILER MODAL - Uses global mu-trailer-modal from movie-ui.css ?>
<!-- Trailer modal is handled globally by movie-ui.js -->

<script>
(function() {
    'use strict';
    
    var trailerUrl = '<?php echo esc_attr($trailer); ?>';
    var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    var ajaxNonce = '<?php echo esc_attr($ajax_nonce); ?>';
    var postId = <?php echo (int) $id; ?>;
    
    // TABS
    document.querySelectorAll('.mdetail-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var tabName = this.getAttribute('data-tab');
            document.querySelectorAll('.mdetail-tab').forEach(function(t) { t.classList.remove('is-active'); });
            this.classList.add('is-active');
            document.querySelectorAll('.mdetail-tab-content').forEach(function(c) { c.classList.remove('is-active'); });
            var target = document.getElementById('tab-' + tabName);
            if (target) target.classList.add('is-active');
        });
    });
    
    // TOGGLE SEASON
    window.toggleSeason = function(header) {
        var season = header.closest('.mdetail-season');
        season.classList.toggle('is-open');
    };
    
    // FAVORITE
    var favBtn = document.getElementById('favBtn');
    if (favBtn) {
        checkFavoriteState();
        favBtn.addEventListener('click', function() {
            var saved = JSON.parse(localStorage.getItem('mu_favorites') || '[]');
            var idx = saved.indexOf(postId);
            var isAdding = idx === -1;
            if (isAdding) { saved.push(postId); } else { saved.splice(idx, 1); }
            localStorage.setItem('mu_favorites', JSON.stringify(saved));
            favBtn.classList.toggle('is-on', isAdding);
            favBtn.querySelector('.fav-label').textContent = isAdding ? '<?php esc_attr_e('Added', 'astra-child'); ?>' : '<?php esc_attr_e('My List', 'astra-child'); ?>';
            showToast(isAdding ? '<?php esc_attr_e('Added to My List', 'astra-child'); ?>' : '<?php esc_attr_e('Removed from My List', 'astra-child'); ?>');
        });
    }
    
    function checkFavoriteState() {
        var saved = JSON.parse(localStorage.getItem('mu_favorites') || '[]');
        if (saved.indexOf(postId) !== -1) {
            favBtn.classList.add('is-on');
            favBtn.querySelector('.fav-label').textContent = '<?php esc_attr_e('Added', 'astra-child'); ?>';
        }
    }
    
    // TRAILER - Use global openTrailer from movie-ui.js
    window.openTrailerModal = function(url) {
        if (!url) return;
        // Use global openTrailer function if available
        if (typeof window.openTrailer === 'function') {
            window.openTrailer(url);
            return;
        }
        // Fallback local implementation
        var modal = document.getElementById('trailerModal');
        var area = document.getElementById('trailerVideoArea');
        if (!modal || !area) return;
        var embedUrl = url;
        if (url.indexOf('youtube.com/watch') !== -1) {
            var vid = url.match(/[?&]v=([^&]+)/);
            if (vid) embedUrl = 'https://www.youtube.com/embed/' + vid[1] + '?autoplay=1&rel=0&modestbranding=1';
        } else if (url.indexOf('youtu.be/') !== -1) {
            var vid = url.match(/youtu\.be\/([^?]+)/);
            if (vid) embedUrl = 'https://www.youtube.com/embed/' + vid[1] + '?autoplay=1&rel=0&modestbranding=1';
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        area.innerHTML = '<iframe src="' + embedUrl + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%;height:100%;border:none;"></iframe>';
    };

    window.closeTrailerModal = function() {
        var modal = document.getElementById('trailerModal');
        var area = document.getElementById('trailerVideoArea');
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
        if (area) area.innerHTML = '';
    };

    window.closeModalOnBackdrop = function(e) {
        if (e.target === e.currentTarget) closeTrailerModal();
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeTrailerModal();
    });
    
    // TOAST
    function showToast(message) {
        if (typeof window.muShowToast === 'function') {
            window.muShowToast(message);
            return;
        }
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:12px 20px;background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;z-index:99999';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 2000);
    }
    
})();
</script>

<?php endwhile; get_footer();
