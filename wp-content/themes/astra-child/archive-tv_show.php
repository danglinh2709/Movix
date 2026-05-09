<?php
/**
 * Browse TV Shows — mirrors Movies archive UX
 * URL: post type archive (usually /tv/)
 */
get_header();
get_template_part('template-parts/streaming/header');

$genres          = get_terms(['taxonomy' => 'genre', 'hide_empty' => true]);
$country         = get_terms(['taxonomy' => 'country', 'hide_empty' => true]);
$archive_link    = trailingslashit(home_url('tv'));
$current_genre   = isset($_GET['genre']) ? sanitize_text_field(wp_unslash($_GET['genre'])) : '';
$current_country = isset($_GET['country']) ? sanitize_text_field(wp_unslash($_GET['country'])) : '';
$current_year    = isset($_GET['year']) ? (int) $_GET['year'] : 0;
$current_sort    = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'latest';
$current_s       = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$is_filtered     = ($current_genre || $current_country || $current_year || $current_sort !== 'latest' || $current_s !== '');

$cpt_count = wp_count_posts('tv_show');
$total_tv  = $cpt_count && isset($cpt_count->publish) ? (int) $cpt_count->publish : 0;

if (!function_exists('mu_render_tv_section')) {

    /**
     * @param string               $title
     * @param array<string,mixed>  $query_args
     * @param string               $sort_link Query string suffix e.g. ?sort=popular
     */
    function mu_render_tv_section(string $title, array $query_args, string $sort_link = ''): void {
        $mq = new WP_Query($query_args);
        if (!$mq->have_posts()) {
            wp_reset_postdata();
            return;
        }
        $base = trailingslashit(home_url('tv'));
        $link = $sort_link !== '' ? esc_url($base . $sort_link) : '';

        ?>
        <div class="mu-ott-section">
            <div class="mu-ott-sec-head">
                <h2 class="mu-ott-sec-title"><?php echo esc_html($title); ?></h2>
                <?php if ($link) : ?>
                    <a href="<?php echo $link; ?>" class="mu-ott-sec-link">View all ›</a>
                <?php endif; ?>
            </div>
            <div class="mu-row" style="width:100%;padding:0;">
                <div class="swiper" data-mu-swiper="row">
                    <div class="swiper-wrapper">
                        <?php
                        while ($mq->have_posts()) :
                            $mq->the_post();
                            ?>
                            <div class="swiper-slide mu-slide"><?php movie_ui_render_movie_card(get_the_ID()); ?></div>
                            <?php
                        endwhile;
                        ?>
                    </div>
                    <div class="mu-row__nav">
                        <button class="mu-navbtn mu-navbtn--prev" type="button" aria-label="Previous"></button>
                        <button class="mu-navbtn mu-navbtn--next" type="button" aria-label="Next"></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }
}
?>

<div class="mu-page mu-archive-tv">

  <section class="mu-movies-hero">
    <div class="mu-movies-hero__bg"></div>
    <div class="mu-movies-hero__content">
      <h1 class="mu-movies-hero__title">TV Shows</h1>
      <p class="mu-movies-hero__subtitle">Binge-worthy series — browse by genre or country.</p>
      <div class="mu-movies-hero__stats">
        <div class="mu-stat">
          <span class="mu-stat__icon">📺</span>
          <div class="mu-stat__info">
            <strong><?php echo esc_html(number_format_i18n($total_tv)); ?></strong>
            <span>Shows</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="mu-movies-layout">

    <aside class="mu-movies-sidebar">
      <form method="GET" action="<?php echo esc_url($archive_link); ?>" id="mu-sidebar-form-tv">
        <div class="mu-sb-search" style="margin-bottom:20px; position:relative;">
          <span class="mu-ico-search" style="position:absolute;left:12px;top:12px;opacity:0.5;"></span>
          <input type="text" name="s" placeholder="Search shows..." value="<?php echo esc_attr($current_s); ?>" style="width:100%;background:var(--mu-bg-2);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:10px 10px 10px 36px;color:#fff;outline:none;font-size:14px;">
        </div>
        <div class="mu-sb-header">
          <h3><span class="mu-ico-filter"></span> Filters</h3>
          <a href="<?php echo esc_url($archive_link); ?>" class="mu-sb-reset" <?php echo $is_filtered ? '' : 'style="display:none;"'; ?>>Reset</a>
        </div>
        <?php if ($current_sort && $current_sort !== 'latest') : ?>
          <input type="hidden" name="sort" value="<?php echo esc_attr($current_sort); ?>">
        <?php endif; ?>
        <div class="mu-sb-group">
          <h4 class="mu-sb-title">Genre</h4>
          <div class="mu-sb-list">
            <?php foreach (array_slice((array) $genres, 0, 12) as $g) : ?>
              <label class="mu-sb-checkbox">
                <input type="radio" name="genre" value="<?php echo esc_attr($g->slug); ?>" <?php checked($current_genre, $g->slug); ?>>
                <span class="mu-sb-label"><?php echo esc_html($g->name); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </form>
    </aside>

    <main class="mu-movies-main">
      <form method="GET" action="<?php echo esc_url($archive_link); ?>" class="mu-movies-topbar">
        <?php if ($current_genre) : ?><input type="hidden" name="genre" value="<?php echo esc_attr($current_genre); ?>"><?php endif; ?>
        <?php if ($current_s) : ?><input type="hidden" name="s" value="<?php echo esc_attr($current_s); ?>"><?php endif; ?>
        <select name="country" class="mu-topbar-select">
          <option value=""><?php esc_html_e('Country', 'astra-child'); ?></option>
          <?php foreach ((array) $country as $c) : ?>
            <option value="<?php echo esc_attr($c->slug); ?>" <?php selected($current_country, $c->slug); ?>><?php echo esc_html($c->name); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="sort" class="mu-topbar-select">
          <option value="latest" <?php selected($current_sort, 'latest'); ?>>Latest</option>
          <option value="popular" <?php selected($current_sort, 'popular'); ?>>Popular</option>
          <option value="top_rated" <?php selected($current_sort, 'top_rated'); ?>>Top rated</option>
        </select>
      </form>

      <div class="mu-movies-content-area">
        <?php if ($is_filtered) : ?>
          <?php
            $args = [
                'post_type'      => 'tv_show',
                'posts_per_page' => 24,
                'paged'          => max(1, (int) get_query_var('paged')),
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            if ($current_genre) {
                $args['tax_query'][] = ['taxonomy' => 'genre', 'field' => 'slug', 'terms' => $current_genre];
            }
            if ($current_country) {
                $args['tax_query'][] = ['taxonomy' => 'country', 'field' => 'slug', 'terms' => $current_country];
            }
            if ($current_year) {
                $args['meta_query'][] = ['key' => '_release_year', 'value' => $current_year, 'type' => 'NUMERIC'];
            }
            if ($current_s) {
                $args['s'] = $current_s;
            }
            if ($current_sort === 'popular') {
                $args['meta_key'] = '_view_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
            } elseif ($current_sort === 'top_rated') {
                $args['meta_key'] = '_rating';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
            }
            $mq = new WP_Query($args);
          ?>
          <div class="mu-arc-grid">
            <?php
            if ($mq->have_posts()) :
                while ($mq->have_posts()) :
                    $mq->the_post();
                    movie_ui_render_movie_card(get_the_ID());
                endwhile;
            else :
                echo '<p class="mu-empty">' . esc_html__('No TV shows found.', 'astra-child') . '</p>';
            endif;
            ?>
          </div>
          <div class="mu-arc-pagination"><?php echo paginate_links(['total' => $mq->max_num_pages, 'prev_text' => '&laquo;', 'next_text' => '&raquo;']); ?></div>
          <?php wp_reset_postdata(); ?>
        <?php else : ?>
          <?php
          mu_render_tv_section('Featured', ['post_type' => 'tv_show', 'posts_per_page' => 10, 'meta_key' => '_view_count', 'orderby' => 'meta_value_num', 'order' => 'DESC'], '?sort=popular');
          mu_render_tv_section('Popular', ['post_type' => 'tv_show', 'posts_per_page' => 10, 'orderby' => 'rand'], '?sort=popular');
          mu_render_tv_section('Top rated', ['post_type' => 'tv_show', 'posts_per_page' => 10, 'meta_key' => '_rating', 'orderby' => 'meta_value_num', 'order' => 'DESC'], '?sort=top_rated');
          mu_render_tv_section('New on Movix', ['post_type' => 'tv_show', 'posts_per_page' => 10, 'orderby' => 'date', 'order' => 'DESC'], '?sort=latest');
          ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<?php
get_footer();
