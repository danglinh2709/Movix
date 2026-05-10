<?php
/**
 * Template Name: Profile (Premium OTT)
 * Modern streaming platform account dashboard
 */
defined('ABSPATH') || exit;

// Remove default header
remove_all_actions('astra_header');
show_admin_bar(false);

// Get user data
$user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// Get page URLs
$watch_base = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : home_url('/watch');
$movies_archive = get_post_type_archive_link('movie');
$tv_archive = get_post_type_archive_link('tv_show');

// Avatar
$avatar_url = get_avatar_url($user->ID ?: 0, ['size' => 280]);
if (!$avatar_url || is_wp_error($avatar_url)) {
    $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($user->display_name ?: 'User') . '&size=280&background=1a1a1a&color=fff&bold=true';
}

// Get user meta
$member_since = $user->user_registered ?? date('Y-m-d');
$user_bio = get_user_meta($user->ID, 'description', true) ?: 'Movie enthusiast';

// Get stats (from user meta or defaults)
$movies_watched = (int) get_user_meta($user->ID, 'mu_movies_watched', true) ?: 47;
$tv_watched = (int) get_user_meta($user->ID, 'mu_tv_watched', true) ?: 23;
$watch_time = (int) get_user_meta($user->ID, 'mu_watch_time', true) ?: 186;
$in_list = (int) get_user_meta($user->ID, 'mu_in_list', true) ?: 15;

// VIP status
$vip_status = get_user_meta($user->ID, 'mu_vip_status', true) ?: 'free';
$vip_plan = get_user_meta($user->ID, 'mu_vip_plan', true) ?: 'Basic';
$vip_price = $vip_status === 'free' ? '0' : '79,000';

// Get continue watching (mock data for demo)
$continue_watching = get_posts([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 8,
    'meta_key' => '_view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
]);

// Get favorites (mock data)
$favorites = get_posts([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 10,
    'meta_key' => '_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
]);

// Get watch history (mock data)
$watch_history = get_posts([
    'post_type' => ['movie', 'tv_show'],
    'posts_per_page' => 5,
    'orderby' => 'modified',
    'order' => 'DESC'
]);

// Genre list
$genres = ['Action', 'Comedy', 'Drama', 'Sci-Fi', 'Thriller', 'Horror', 'Romance', 'Animation', 'Documentary', 'Fantasy'];
$user_genres = get_user_meta($user->ID, 'mu_preferred_genres', true) ?: [];
if (is_string($user_genres)) {
    $user_genres = array_map('trim', explode(',', $user_genres));
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('My Profile', 'astra-child'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/movie-ui.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/css/ms-profile.css')); ?>">
    <style>
        html { margin-top: 0 !important; }
        body { margin-top: 0 !important; }
        #wpadminbar { display: none !important; }
        * { box-sizing: border-box; }
    </style>
</head>
<body class="movie-ui movie-ui--no-sidebar mp-page-body">
<?php wp_body_open(); ?>

<!-- ============================================================ -->
<!-- HEADER -->
<!-- ============================================================ -->
<?php get_template_part('template-parts/streaming/header'); ?>

<!-- ============================================================ -->
<!-- MAIN PAGE -->
<!-- ============================================================ -->
<main class="mp-page">
    <div class="mp-container">

        <?php if (!$is_logged_in) : ?>
            <!-- NOT LOGGED IN -->
            <div class="mp-profile-header" style="justify-content: center; padding: 100px 0;">
                <div class="mp-empty-state" style="padding: 40px;">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <h3>Sign In to View Your Profile</h3>
                    <p>Access your watch history, favorites, and personalized recommendations.</p>
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="mp-subscription-card__btn" style="display: inline-block; margin-top: 20px; text-decoration: none;">Sign In</a>
                </div>
            </div>
        <?php else : ?>

        <!-- ============================================================ -->
        <!-- PROFILE HEADER -->
        <!-- ============================================================ -->
        <header class="mp-profile-header">
            
            <!-- Avatar & User Info -->
            <div class="mp-avatar-section">
                <div class="mp-avatar-wrapper">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($user->display_name); ?>" class="mp-avatar">
                    <div class="mp-avatar-overlay">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    </div>
                </div>
                <div class="mp-user-info">
                    <h1 class="mp-username"><?php echo esc_html($user->display_name ?: $user->user_login); ?></h1>
                    <p class="mp-email"><?php echo esc_html($user->user_email); ?></p>
                    <div class="mp-member-since">
                        Member since <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($member_since))); ?>
                    </div>
                    <p class="mp-bio"><?php echo esc_html($user_bio); ?></p>
                    <button class="mp-edit-btn" data-action="edit-profile">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        Edit Profile
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="mp-stats-section">
                <div class="mp-stats-grid">
                    <div class="mp-stat-card">
                        <div class="mp-stat-icon">
                            <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                        </div>
                        <div class="mp-stat-value" data-count="<?php echo esc_attr($movies_watched); ?>">0</div>
                        <div class="mp-stat-label">Movies Watched</div>
                    </div>
                    <div class="mp-stat-card">
                        <div class="mp-stat-icon">
                            <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>
                        </div>
                        <div class="mp-stat-value" data-count="<?php echo esc_attr($tv_watched); ?>">0</div>
                        <div class="mp-stat-label">TV Shows Watched</div>
                    </div>
                    <div class="mp-stat-card">
                        <div class="mp-stat-icon">
                            <svg viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                        </div>
                        <div class="mp-stat-value" data-count="<?php echo esc_attr($in_list); ?>">0</div>
                        <div class="mp-stat-label">In My List</div>
                    </div>
                    <div class="mp-stat-card">
                        <div class="mp-stat-icon">
                            <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                        </div>
                        <div class="mp-stat-value" data-count="<?php echo esc_attr($watch_time); ?>">0</div>
                        <div class="mp-stat-label">Hours Watched</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ============================================================ -->
        <!-- MAIN CONTENT GRID -->
        <!-- ============================================================ -->
        <div class="mp-content-grid">

            <!-- LEFT COLUMN -->
            <div class="mp-main-content">

                <!-- Continue Watching -->
                <?php if (!empty($continue_watching)) : ?>
                <section class="mp-section">
                    <div class="mp-section-header">
                        <h2 class="mp-section-title">
                            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            Continue Watching
                        </h2>
                        <a href="<?php echo esc_url($watch_base); ?>" class="mp-section-link">View All</a>
                    </div>
                    <div class="mp-carousel">
                        <div class="swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($continue_watching as $item) :
                                    $progress = rand(15, 85);
                                    $poster = get_the_post_thumbnail_url($item->ID, 'medium');
                                    if (!$poster) {
                                        $poster = 'https://picsum.photos/seed/' . $item->ID . '/200/300';
                                    }
                                    $watch_url = add_query_arg('id', $item->ID, $watch_base);
                                ?>
                                <div class="swiper-slide">
                                    <div class="mp-movie-card mp-continue-card" data-id="<?php echo esc_attr($item->ID); ?>" data-watch-url="<?php echo esc_url($watch_url); ?>">
                                        <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($item->post_title); ?>" class="mp-movie-card__poster">
                                        <div class="mp-movie-card__overlay">
                                            <h4 class="mp-movie-card__title"><?php echo esc_html($item->post_title); ?></h4>
                                            <p class="mp-movie-card__meta"><?php echo esc_html(get_post_type_object($item->post_type)->labels->singular_name); ?></p>
                                            <div class="mp-movie-card__actions">
                                                <button class="mp-movie-card__play" aria-label="Play">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="white"><path d="M8 5v14l11-7z"/></svg>
                                                </button>
                                                <button class="mp-movie-card__add" aria-label="Add to list">
                                                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mp-movie-card__progress">
                                            <div class="mp-movie-card__progress-bar" style="width: <?php echo esc_attr($progress); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- My List -->
                <?php if (!empty($favorites)) : ?>
                <section class="mp-section">
                    <div class="mp-section-header">
                        <h2 class="mp-section-title">
                            <svg viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            My List
                        </h2>
                        <a href="<?php echo esc_url(home_url('/favorites')); ?>" class="mp-section-link">View All</a>
                    </div>
                    <div class="mp-carousel">
                        <div class="swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($favorites as $item) :
                                    $poster = get_the_post_thumbnail_url($item->ID, 'medium');
                                    if (!$poster) {
                                        $poster = 'https://picsum.photos/seed/' . $item->ID . '/200/300';
                                    }
                                ?>
                                <div class="swiper-slide">
                                    <div class="mp-movie-card" data-id="<?php echo esc_attr($item->ID); ?>" data-type="<?php echo esc_attr($item->post_type); ?>">
                                        <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($item->post_title); ?>" class="mp-movie-card__poster">
                                        <div class="mp-movie-card__overlay">
                                            <h4 class="mp-movie-card__title"><?php echo esc_html($item->post_title); ?></h4>
                                            <p class="mp-movie-card__meta"><?php echo esc_html(get_post_type_object($item->post_type)->labels->singular_name); ?></p>
                                            <div class="mp-movie-card__actions">
                                                <a href="<?php echo esc_url(get_permalink($item->ID)); ?>" class="mp-movie-card__play" aria-label="Play">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="white"><path d="M8 5v14l11-7z"/></svg>
                                                </a>
                                                <button class="mp-movie-card__add" aria-label="Remove from list">
                                                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Recently Watched -->
                <?php if (!empty($watch_history)) : ?>
                <section class="mp-section">
                    <div class="mp-section-header">
                        <h2 class="mp-section-title">
                            <svg viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                            Recently Watched
                        </h2>
                        <a href="<?php echo esc_url(home_url('/history')); ?>" class="mp-section-link">View All</a>
                    </div>
                    <div class="mp-history-list">
                        <?php foreach ($watch_history as $item) :
                            $poster = get_the_post_thumbnail_url($item->ID, 'medium');
                            if (!$poster) {
                                $poster = 'https://picsum.photos/seed/' . $item->ID . '/160/90';
                            }
                            $progress = rand(50, 100);
                            $watched_date = date_i18n('M d', strtotime($item->post_modified));
                            $is_completed = $progress >= 95;
                        ?>
                        <div class="mp-history-item" data-id="<?php echo esc_attr($item->ID); ?>">
                            <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($item->post_title); ?>" class="mp-history-item__poster">
                            <div class="mp-history-item__info">
                                <h4 class="mp-history-item__title"><?php echo esc_html($item->post_title); ?></h4>
                                <div class="mp-history-item__meta">
                                    <span><?php echo esc_html(get_post_type_object($item->post_type)->labels->singular_name); ?></span>
                                    <span>Watched <?php echo esc_html($watched_date); ?></span>
                                    <?php if ($is_completed) : ?>
                                    <span class="mp-history-item__badge completed">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                        Completed
                                    </span>
                                    <?php else : ?>
                                    <span class="mp-history-item__badge"><?php echo esc_html($progress); ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mp-history-item__progress">
                                    <div class="mp-history-item__progress-bar" style="width: <?php echo esc_attr($progress); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Preferences -->
                <section class="mp-section">
                    <div class="mp-section-header">
                        <h2 class="mp-section-title">
                            <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                            Genre Preferences
                        </h2>
                    </div>
                    <div class="mp-genre-chips">
                        <?php foreach ($genres as $genre) :
                            $is_selected = in_array($genre, $user_genres);
                        ?>
                        <button class="mp-genre-chip<?php echo $is_selected ? ' selected' : ''; ?>" data-genre="<?php echo esc_attr(strtolower($genre)); ?>">
                            <?php echo esc_html($genre); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 16px;">
                        <span style="color: var(--mp-muted); font-size: 13px;">
                            <span class="mp-genres-count"><?php echo count($user_genres); ?></span> genres selected
                        </span>
                        <button class="mp-subscription-card__btn" data-action="save-genres" style="margin-left: 16px; padding: 8px 20px; font-size: 13px;">Save Preferences</button>
                    </div>
                </section>

                <!-- Profiles -->
                <section class="mp-section">
                    <div class="mp-section-header">
                        <h2 class="mp-section-title">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            Profiles
                        </h2>
                    </div>
                    <div class="mp-profiles-grid">
                        <div class="mp-profile-item active" data-profile-id="1">
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="Current Profile" class="mp-profile-item__avatar">
                            <span class="mp-profile-item__name"><?php echo esc_html($user->display_name ?: 'You'); ?></span>
                        </div>
                        <div class="mp-profile-item" data-profile-id="2">
                            <img src="https://ui-avatars.com/api/?name=Kids&size=160&background=FF6B6B&color=fff" alt="Kids" class="mp-profile-item__avatar">
                            <span class="mp-profile-item__name">Kids</span>
                        </div>
                        <div class="mp-profile-item" data-profile-id="3">
                            <img src="https://ui-avatars.com/api/?name=Family&size=160&background=4ECDC4&color=fff" alt="Family" class="mp-profile-item__avatar">
                            <span class="mp-profile-item__name">Family</span>
                        </div>
                        <div class="mp-add-profile">
                            <div class="mp-add-profile__btn">
                                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            </div>
                            <span class="mp-profile-item__name">Add Profile</span>
                        </div>
                    </div>
                </section>

            </div><!-- /.mp-main-content -->

            <!-- RIGHT SIDEBAR -->
            <aside class="mp-settings-sidebar">
                <div class="mp-settings-header">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="Profile" class="mp-settings-header__avatar">
                    <div>
                        <div class="mp-settings-header__name"><?php echo esc_html($user->display_name ?: $user->user_login); ?></div>
                        <div class="mp-settings-header__plan"><?php echo esc_html(ucfirst($vip_status)); ?> Member</div>
                    </div>
                </div>
                
                <ul class="mp-settings-menu">
                    <li class="mp-settings-menu__item active" data-action="edit-profile">
                        <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <span>Manage Profile</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="change-password">
                        <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                        <span>Change Password</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="notifications">
                        <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                        <span>Notifications</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="playback">
                        <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>
                        <span>Playback Settings</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="language">
                        <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2 0-.68.07-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>
                        <span>Language</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="privacy">
                        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                        <span>Privacy Settings</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="devices">
                        <svg viewBox="0 0 24 24"><path d="M4 6h18V4H4c-1.1 0-2 .9-2 2v11H0v3h14v-3H4V6zm19 2h-6c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1zm-1 9h-4v-7h4v7z"/></svg>
                        <span>Manage Devices</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="parental">
                        <svg viewBox="0 0 24 24"><path d="M13 7h-2v4H7v2h4v4h2v-4h4v-2h-4V7zm-1-5C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                        <span>Parental Controls</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="email">
                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <span>Email Preferences</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="payment">
                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                        <span>Payment Methods</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="subscription" data-href="<?php echo esc_url(home_url('/vip')); ?>">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        <span>Subscription Plan</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                    <li class="mp-settings-menu__item" data-action="logout" data-href="<?php echo esc_url(wp_logout_url()); ?>">
                        <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                        <span>Sign Out</span>
                        <svg class="mp-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </li>
                </ul>

                <!-- Subscription Card -->
                <div class="mp-subscription-card">
                    <div class="mp-subscription-card__header">
                        <span class="mp-subscription-card__plan"><?php echo esc_html($vip_plan); ?> Plan</span>
                        <?php if ($vip_status !== 'free') : ?>
                        <span class="mp-subscription-card__badge">Active</span>
                        <?php endif; ?>
                    </div>
                    <div class="mp-subscription-card__price">
                        <?php echo esc_html($vip_price); ?><span>/tháng</span>
                    </div>
                    <ul class="mp-subscription-card__features">
                        <li>Unlimited movies & TV shows</li>
                        <li>HD & 4K quality</li>
                        <?php if ($vip_status !== 'free') : ?>
                        <li>Download for offline viewing</li>
                        <li>Cancel anytime</li>
                        <?php endif; ?>
                    </ul>
                    <a href="<?php echo esc_url(home_url('/vip')); ?>" class="mp-subscription-card__btn">
                        <?php echo $vip_status === 'free' ? 'Upgrade Now' : 'Manage Subscription'; ?>
                    </a>
                </div>
            </aside>

        </div><!-- /.mp-content-grid -->

        <?php endif; // is_logged_in ?>

    </div><!-- /.mp-container -->

    <!-- ============================================================ -->
    <!-- FOOTER -->
    <!-- ============================================================ -->
    <footer class="mp-footer">
        <div class="mp-container">
            <div class="mp-footer__grid">
                <div class="mp-footer__col">
                    <h4>Browse</h4>
                    <ul>
                        <li><a href="<?php echo esc_url($movies_archive ?: home_url('/movies')); ?>">Movies</a></li>
                        <li><a href="<?php echo esc_url($tv_archive ?: home_url('/tv')); ?>">TV Shows</a></li>
                        <li><a href="<?php echo esc_url(home_url('/trending')); ?>">Trending</a></li>
                        <li><a href="<?php echo esc_url(home_url('/top-rated')); ?>">Top Rated</a></li>
                        <li><a href="<?php echo esc_url(home_url('/new-releases')); ?>">New Releases</a></li>
                    </ul>
                </div>
                <div class="mp-footer__col">
                    <h4>Account</h4>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/favorites')); ?>">My List</a></li>
                        <li><a href="<?php echo esc_url(home_url('/history')); ?>">Watch History</a></li>
                        <li><a href="<?php echo esc_url(home_url('/profile')); ?>">Profile</a></li>
                        <li><a href="<?php echo esc_url(home_url('/vip')); ?>">Subscription</a></li>
                    </ul>
                </div>
                <div class="mp-footer__col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Terms of Use</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="mp-footer__col">
                    <h4>Stay Connected</h4>
                    <div class="mp-footer__social">
                        <a href="#" aria-label="Facebook">
                            <svg viewBox="0 0 24 24"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>
                        </a>
                        <a href="#" aria-label="Twitter">
                            <svg viewBox="0 0 24 24"><path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/></svg>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <svg viewBox="0 0 24 24"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mp-footer__bottom">
                <p class="mp-footer__copyright">© <?php echo date('Y'); ?> MovieStream. All rights reserved.</p>
            </div>
        </div>
    </footer>

</main>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="<?php echo esc_url(get_theme_file_uri('assets/js/ms-profile.js')); ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
