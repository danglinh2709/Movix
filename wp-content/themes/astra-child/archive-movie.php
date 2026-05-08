<?php
/**
 * Browse Movies — Premium OTT Interface
 */
get_header();
get_template_part('template-parts/streaming/header');

// --- Data: taxonomies, filters ---
$genres  = get_terms(['taxonomy' => 'genre',   'hide_empty' => true]);
$country = get_terms(['taxonomy' => 'country', 'hide_empty' => true]);

$current_genre   = isset($_GET['genre'])   ? sanitize_text_field($_GET['genre'])   : '';
$current_country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
$current_year    = isset($_GET['year'])    ? intval($_GET['year'])                 : '';
$current_sort    = isset($_GET['sort'])    ? sanitize_text_field($_GET['sort'])    : 'latest';
$current_s       = isset($_GET['s'])       ? sanitize_text_field($_GET['s'])       : '';

$is_filtered = ($current_genre || $current_country || $current_year || $current_sort !== 'latest' || $current_s);

// Statistics
$total_movies = wp_count_posts('movie')->publish;
$latest_year = 2025; // Could be dynamic
?>

<div class="mu-page mu-archive-movies">

  <!-- OTT Header -->
  <section class="mu-movies-hero">
    <div class="mu-movies-hero__bg"></div>
    <div class="mu-movies-hero__content">
      <h1 class="mu-movies-hero__title">Movies</h1>
      <p class="mu-movies-hero__desc">Explore popular, trending, and top-rated movies.</p>
      
      <div class="mu-movies-hero__stats">
        <div class="mu-stat">
          <span class="mu-stat__icon">🎬</span>
          <div class="mu-stat__info">
            <strong><?php echo number_format($total_movies); ?></strong>
            <span>Movies</span>
          </div>
        </div>
        <div class="mu-stat">
          <span class="mu-stat__icon" style="color:var(--mu-accent)">HD</span>
          <div class="mu-stat__info">
            <strong>4K</strong>
            <span>Quality</span>
          </div>
        </div>
        <div class="mu-stat">
          <span class="mu-stat__icon">📅</span>
          <div class="mu-stat__info">
            <strong><?php echo $latest_year; ?></strong>
            <span>Latest</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Layout: Sidebar + Main Content -->
  <div class="mu-movies-layout">
    
    <!-- Sidebar Filters -->
    <aside class="mu-movies-sidebar">
      <form method="GET" action="<?php echo esc_url(get_post_type_archive_link('movie')); ?>" id="mu-sidebar-form">
        
        <div class="mu-sb-search" style="margin-bottom:20px; position:relative;">
          <span class="mu-ico-search" style="position:absolute; left:12px; top:12px; opacity:0.5;"></span>
          <input type="text" name="s" placeholder="Search movies, genres..." value="<?php echo esc_attr($current_s); ?>" style="width:100%; background:var(--mu-bg-2); border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:10px 10px 10px 36px; color:#fff; outline:none; font-size:14px;">
        </div>

        <div class="mu-sb-header">
          <h3><span class="mu-ico-filter"></span> Filters</h3>
          <a href="<?php echo esc_url(get_post_type_archive_link('movie')); ?>" class="mu-sb-reset" <?php echo !$is_filtered ? 'style="display:none;"' : ''; ?>>Reset</a>
        </div>
        
        <?php if ($current_sort && $current_sort !== 'latest'): ?><input type="hidden" name="sort" value="<?php echo esc_attr($current_sort); ?>"><?php endif; ?>
        <?php if ($current_country): ?><input type="hidden" name="country" value="<?php echo esc_attr($current_country); ?>"><?php endif; ?>

        <!-- Genres -->
        <div class="mu-sb-group">
          <h4 class="mu-sb-title" data-mu-toggle="genre">Genre</h4>
          <div class="mu-sb-list" id="mu-list-genre">
            <?php 
            $genre_count = 0;
            foreach ($genres as $g): 
              $genre_count++;
              $is_hidden = $genre_count > 6 ? 'style="display:none;" data-mu-hidden="true"' : '';
            ?>
              <label class="mu-sb-checkbox" <?php echo $is_hidden; ?>>
                <input type="radio" name="genre" value="<?php echo esc_attr($g->slug); ?>" <?php checked($current_genre, $g->slug); ?>>
                <span class="mu-sb-check"></span>
                <span class="mu-sb-label"><?php echo esc_html($g->name); ?></span>
                <span class="mu-sb-count"><?php echo $g->count; ?></span>
              </label>
            <?php endforeach; ?>
            <?php if ($genre_count > 6): ?>
              <span class="mu-sb-more" data-mu-show-more="genre" style="font-size:12px; color:var(--mu-accent); cursor:pointer; margin-top:4px;">Show more <svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle; margin-left:4px;"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Year -->
        <div class="mu-sb-group">
          <h4 class="mu-sb-title" data-mu-toggle="year">Year</h4>
          <div class="mu-sb-list" id="mu-list-year">
            <?php foreach ([2025, 2024, 2023, 2022, '2021 & Earlier'] as $y): ?>
              <label class="mu-sb-checkbox">
                <input type="radio" name="year" value="<?php echo $y === '2021 & Earlier' ? 'before_2021' : $y; ?>" <?php checked($current_year, $y === '2021 & Earlier' ? 'before_2021' : $y); ?>>
                <span class="mu-sb-check"></span>
                <span class="mu-sb-label"><?php echo $y; ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Rating -->
        <div class="mu-sb-group">
          <h4 class="mu-sb-title" data-mu-toggle="rating">Rating</h4>
          <div class="mu-sb-list" id="mu-list-rating">
            <?php foreach ([9, 8, 7] as $r): ?>
              <label class="mu-sb-checkbox">
                <input type="radio" name="rating" value="<?php echo $r; ?>" <?php checked(isset($_GET['rating']) ? $_GET['rating'] : '', $r); ?>>
                <span class="mu-sb-check"></span>
                <span class="mu-sb-label"><?php echo $r; ?>+</span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </form>
    </aside>

    <!-- Main Content -->
    <main class="mu-movies-main">
      
      <!-- Topbar Filters -->
      <form method="GET" action="<?php echo esc_url(get_post_type_archive_link('movie')); ?>" id="mu-topbar-form" class="mu-movies-topbar">
        <?php if ($current_genre): ?><input type="hidden" name="genre" value="<?php echo esc_attr($current_genre); ?>"><?php endif; ?>
        <?php if ($current_year): ?><input type="hidden" name="year" value="<?php echo esc_attr($current_year); ?>"><?php endif; ?>
        <?php if ($current_s): ?><input type="hidden" name="s" value="<?php echo esc_attr($current_s); ?>"><?php endif; ?>
        
        <select name="year" class="mu-topbar-select">
          <option value="">Year</option>
          <option value="2025" <?php selected($current_year, 2025); ?>>2025</option>
          <option value="2024" <?php selected($current_year, 2024); ?>>2024</option>
          <option value="2023" <?php selected($current_year, 2023); ?>>2023</option>
        </select>
        
        <select name="country" class="mu-topbar-select">
          <option value="">Country</option>
          <?php foreach ($country as $c): ?>
            <option value="<?php echo esc_attr($c->slug); ?>" <?php selected($current_country, $c->slug); ?>><?php echo esc_html($c->name); ?></option>
          <?php endforeach; ?>
        </select>
        
        <select name="rating" class="mu-topbar-select">
          <option value="">Rating</option>
          <option value="9" <?php selected(isset($_GET['rating']) ? $_GET['rating'] : '', 9); ?>>9.0+</option>
          <option value="8" <?php selected(isset($_GET['rating']) ? $_GET['rating'] : '', 8); ?>>8.0+</option>
          <option value="7" <?php selected(isset($_GET['rating']) ? $_GET['rating'] : '', 7); ?>>7.0+</option>
        </select>

        <select name="sort" class="mu-topbar-select">
          <option value="latest" <?php selected($current_sort, 'latest'); ?>>Sort by: Latest</option>
          <option value="popular" <?php selected($current_sort, 'popular'); ?>>Sort by: Trending</option>
          <option value="top_rated" <?php selected($current_sort, 'top_rated'); ?>>Sort by: Top Rated</option>
        </select>
        
        <div class="mu-topbar-chips" id="mu-topbar-chips">
          <label class="mu-chip <?php echo !$current_genre ? 'is-active' : ''; ?>">
            <input type="radio" name="genre" value="" style="display:none;" <?php checked($current_genre, ''); ?>>
            All
          </label>
          <?php foreach (array_slice((array)$genres, 0, 10) as $g): ?>
            <label class="mu-chip <?php echo ($current_genre === $g->slug) ? 'is-active' : ''; ?>">
              <input type="radio" name="genre" value="<?php echo esc_attr($g->slug); ?>" style="display:none;" <?php checked($current_genre, $g->slug); ?>>
              <?php echo esc_html($g->name); ?>
            </label>
          <?php endforeach; ?>
          <a class="mu-sb-reset" href="<?php echo esc_url(get_post_type_archive_link('movie')); ?>" style="margin-left: 8px; line-height: 34px; <?php echo !$is_filtered ? 'display:none;' : ''; ?>">Clear Filters</a>
        </div>
      </form>

      <div class="mu-movies-content-area">
        <?php if ($is_filtered): ?>
          <!-- FILTERED RESULTS GRID -->
          <?php
            $args = ['post_type' => 'movie', 'posts_per_page' => 24, 'paged' => max(1, get_query_var('paged'))];
            if ($current_genre) $args['tax_query'][] = ['taxonomy' => 'genre', 'field' => 'slug', 'terms' => $current_genre];
            if ($current_country) $args['tax_query'][] = ['taxonomy' => 'country', 'field' => 'slug', 'terms' => $current_country];
            if ($current_year)  $args['meta_query'][] = ['key' => '_release_year', 'value' => $current_year, 'type' => 'NUMERIC'];
            if ($current_s)  $args['s'] = $current_s;
            
            if ($current_sort === 'popular') { $args['meta_key'] = '_view_count'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; }
            elseif ($current_sort === 'top_rated') { $args['meta_key'] = '_rating'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; }
            else { $args['orderby'] = 'date'; $args['order'] = 'DESC'; }
            
            $mq = new WP_Query($args);
          ?>
          <div class="mu-arc-grid">
            <?php if ($mq->have_posts()): while ($mq->have_posts()): $mq->the_post(); movie_ui_render_movie_card(get_the_ID()); endwhile; else: echo "<p>No movies found.</p>"; endif; ?>
          </div>
          <?php 
            echo '<div class="mu-arc-pagination">';
            echo paginate_links(['total' => $mq->max_num_pages, 'prev_text' => '&laquo;', 'next_text' => '&raquo;']);
            echo '</div>';
            wp_reset_postdata(); 
          ?>

        <?php else: ?>
          <!-- OTT SECTIONS -->
          
          <?php
          // Helper function for rendering a Swiper section
          function mu_render_movie_section($title, $query_args, $sort_link) {
            $mq = new WP_Query($query_args);
            if ($mq->have_posts()): ?>
              <div class="mu-ott-section">
                <div class="mu-ott-sec-head">
                  <h2 class="mu-ott-sec-title"><?php echo esc_html($title); ?></h2>
                  <?php if ($sort_link): ?>
                    <a href="<?php echo esc_url($sort_link); ?>" class="mu-ott-sec-link">View all ›</a>
                  <?php endif; ?>
                </div>
                <div class="mu-row" style="width: 100%; padding: 0;">
                  <div class="swiper" data-mu-swiper="row">
                    <div class="swiper-wrapper">
                      <?php while ($mq->have_posts()): $mq->the_post(); ?>
                        <div class="swiper-slide mu-slide">
                          <?php movie_ui_render_movie_card(get_the_ID()); ?>
                        </div>
                      <?php endwhile; ?>
                    </div>
                    <div class="mu-row__nav">
                      <button class="mu-navbtn mu-navbtn--prev" type="button" aria-label="Previous"></button>
                      <button class="mu-navbtn mu-navbtn--next" type="button" aria-label="Next"></button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; wp_reset_postdata();
          }

          // 1. Featured / Top 10
          mu_render_movie_section('Featured Movies', ['post_type' => 'movie', 'posts_per_page' => 10, 'meta_key' => '_view_count', 'orderby' => 'meta_value_num', 'order' => 'DESC'], '?sort=popular');

          // 2. Trending This Week
          mu_render_movie_section('Trending This Week', ['post_type' => 'movie', 'posts_per_page' => 10, 'orderby' => 'rand'], '?sort=popular');

          // 3. Top Rated
          mu_render_movie_section('Top Rated Movies', ['post_type' => 'movie', 'posts_per_page' => 10, 'meta_key' => '_rating', 'orderby' => 'meta_value_num', 'order' => 'DESC'], '?sort=top_rated');

          // 4. Recently Added
          mu_render_movie_section('Recently Added', ['post_type' => 'movie', 'posts_per_page' => 10, 'orderby' => 'date', 'order' => 'DESC'], '?sort=latest');
          ?>

        <?php endif; ?>
      </div>
    </main>

  </div>
</div>

<?php get_footer(); ?>
