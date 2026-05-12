<?php
/**
 * Template Name: Profile (Premium OTT)
 * Redesigned 2024 - Dark Cinematic Theme
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
$user_bio = get_user_meta($user->ID, 'description', true) ?: 'Movie lover. Always exploring new stories.';

// Get stats
$movies_watched = (int) get_user_meta($user->ID, 'mu_movies_watched', true) ?: 152;
$tv_watched = (int) get_user_meta($user->ID, 'mu_tv_watched', true) ?: 48;
$watch_time = (int) get_user_meta($user->ID, 'mu_watch_time', true) ?: 203;
$in_list = (int) get_user_meta($user->ID, 'mu_in_list', true) ?: 56;

// VIP status
$vip_status = get_user_meta($user->ID, 'mu_vip_status', true) ?: 'premium';
$vip_plan = get_user_meta($user->ID, 'mu_vip_plan', true) ?: 'Premium';
$vip_price = $vip_status === 'free' ? '0' : '14.99';

// Mock data for continue watching
$continue_watching = [
    ['id' => 1, 'title' => 'The Dark Knight', 'type' => 'movie', 'year' => '2008', 'duration' => '1h 45m left', 'progress' => 45, 'poster' => 'https://picsum.photos/seed/movie1/200/300'],
    ['id' => 2, 'title' => 'Breaking Bad', 'type' => 'tv', 'year' => '2023', 'duration' => '42m left', 'progress' => 72, 'poster' => 'https://picsum.photos/seed/tv1/200/300'],
    ['id' => 3, 'title' => 'Inception', 'type' => 'movie', 'year' => '2010', 'duration' => '2h 10m left', 'progress' => 28, 'poster' => 'https://picsum.photos/seed/movie2/200/300'],
    ['id' => 4, 'title' => 'Stranger Things', 'type' => 'tv', 'year' => '2024', 'duration' => 'Episode 5', 'progress' => 55, 'poster' => 'https://picsum.photos/seed/tv2/200/300'],
    ['id' => 5, 'title' => 'Interstellar', 'type' => 'movie', 'year' => '2014', 'duration' => '45m left', 'progress' => 62, 'poster' => 'https://picsum.photos/seed/movie3/200/300'],
];

// My List data
$my_list = [
    ['id' => 10, 'title' => 'Oppenheimer', 'year' => '2023', 'rating' => '8.4', 'poster' => 'https://picsum.photos/seed/list1/200/300'],
    ['id' => 11, 'title' => 'The Last of Us', 'year' => '2023', 'rating' => '8.8', 'poster' => 'https://picsum.photos/seed/list2/200/300'],
    ['id' => 12, 'title' => 'Dune', 'year' => '2021', 'rating' => '8.0', 'poster' => 'https://picsum.photos/seed/list3/200/300'],
    ['id' => 13, 'title' => 'House of Dragon', 'year' => '2022', 'rating' => '8.5', 'poster' => 'https://picsum.photos/seed/list4/200/300'],
    ['id' => 14, 'title' => 'The Batman', 'year' => '2022', 'rating' => '7.8', 'poster' => 'https://picsum.photos/seed/list5/200/300'],
];

// Recently Watched data
$recently_watched = [
    ['id' => 20, 'title' => 'John Wick 4', 'type' => 'movie', 'watched' => '2 hours ago', 'completed' => true, 'poster' => 'https://picsum.photos/seed/recent1/200/300'],
    ['id' => 21, 'title' => 'Succession', 'type' => 'tv', 'watched' => 'Yesterday', 'completed' => false, 'poster' => 'https://picsum.photos/seed/recent2/200/300'],
    ['id' => 22, 'title' => 'Top Gun: Maverick', 'type' => 'movie', 'watched' => '3 days ago', 'completed' => true, 'poster' => 'https://picsum.photos/seed/recent3/200/300'],
    ['id' => 23, 'title' => 'Wednesday', 'type' => 'tv', 'watched' => '1 week ago', 'completed' => false, 'poster' => 'https://picsum.photos/seed/recent4/200/300'],
];

// Genres
$all_genres = ['Action', 'Sci-Fi', 'Thriller', 'Drama', 'Comedy', 'Horror', 'Romance', 'Animation', 'Documentary', 'Fantasy'];
$user_genres = get_user_meta($user->ID, 'mu_preferred_genres', true) ?: ['Action', 'Sci-Fi', 'Thriller'];
if (is_string($user_genres)) {
    $user_genres = array_map('trim', explode(',', $user_genres));
}

// Profiles
$profiles = [
    ['id' => 1, 'name' => $user->display_name ?: 'John Doe', 'role' => 'You', 'avatar' => $avatar_url, 'is_active' => true, 'is_main' => true],
    ['id' => 2, 'name' => 'Jane Doe', 'role' => 'Kid', 'avatar' => 'https://ui-avatars.com/api/?name=Jane&size=160&background=FF6B6B&color=fff', 'is_active' => false, 'is_main' => false],
    ['id' => 3, 'name' => 'Alex Doe', 'role' => 'Kid', 'avatar' => 'https://ui-avatars.com/api/?name=Alex&size=160&background=4ECDC4&color=fff', 'is_active' => false, 'is_main' => false],
    ['id' => 4, 'name' => 'Kids', 'role' => 'Kid', 'avatar' => 'https://ui-avatars.com/api/?name=Kids&size=160&background=FFE66D&color=333', 'is_active' => false, 'is_main' => false],
];

// Content languages
$languages = ['English', 'Tiếng Việt', '中文', '日本語', '한국어'];
$selected_language = 'English';
$selected_subtitle = 'English (Default)';
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
    </style>
</head>
<body class="profile-page">
<?php wp_body_open(); ?>

<!-- ============================================================ -->
<!-- HEADER (Reused from template-parts/streaming/header) -->
<!-- ============================================================ -->
<?php get_template_part('template-parts/streaming/header'); ?>

<!-- ============================================================ -->
<!-- MAIN PROFILE CONTENT -->
<!-- ============================================================ -->
<main class="pp-container">

    <?php if (!$is_logged_in) : ?>
        <!-- NOT LOGGED IN -->
        <div class="pp-hero" style="justify-content: center; padding: 100px 0;">
            <div style="text-align: center;">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <h2 style="margin: 24px 0 12px; font-size: 24px;">Sign In to View Your Profile</h2>
                <p style="color: var(--pp-muted); margin-bottom: 24px;">Access your watch history, favorites, and personalized recommendations.</p>
                <a href="<?php echo esc_url(wp_login_url()); ?>" class="pp-btn pp-btn--primary">Sign In</a>
            </div>
        </div>
    <?php else : ?>

    <!-- ============================================================ -->
    <!-- HERO SECTION -->
    <!-- ============================================================ -->
    <section class="pp-hero">
        <!-- Avatar -->
        <div class="pp-hero__avatar-wrap">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($user->display_name); ?>" class="pp-hero__avatar">
            <button class="pp-hero__avatar-edit" data-action="edit-avatar" aria-label="Edit avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
        </div>

        <!-- User Info -->
        <div class="pp-hero__info">
            <h1 class="pp-hero__name"><?php echo esc_html($user->display_name ?: $user->user_login); ?></h1>
            <p class="pp-hero__email"><?php echo esc_html($user->user_email); ?></p>
            <p class="pp-hero__member">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Member since <?php echo esc_html(date_i18n('F j, Y', strtotime($member_since))); ?>
            </p>
            <p class="pp-hero__bio"><?php echo esc_html($user_bio); ?></p>
            <button class="pp-hero__edit-btn" data-action="edit-profile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit Profile
            </button>

            <!-- Stats -->
            <div class="pp-hero__stats">
                <div class="pp-stat-card">
                    <div class="pp-stat-card__icon">
                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <div class="pp-stat-card__content">
                        <div class="pp-stat-card__value"><?php echo esc_html($movies_watched); ?></div>
                        <div class="pp-stat-card__label">Movies Watched</div>
                    </div>
                </div>
                <div class="pp-stat-card">
                    <div class="pp-stat-card__icon">
                        <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>
                    </div>
                    <div class="pp-stat-card__content">
                        <div class="pp-stat-card__value"><?php echo esc_html($tv_watched); ?></div>
                        <div class="pp-stat-card__label">TV Shows Watched</div>
                    </div>
                </div>
                <div class="pp-stat-card">
                    <div class="pp-stat-card__icon">
                        <svg viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                    </div>
                    <div class="pp-stat-card__content">
                        <div class="pp-stat-card__value"><?php echo esc_html($in_list); ?></div>
                        <div class="pp-stat-card__label">In My List</div>
                    </div>
                </div>
                <div class="pp-stat-card">
                    <div class="pp-stat-card__icon">
                        <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                    </div>
                    <div class="pp-stat-card__content">
                        <div class="pp-stat-card__value"><?php echo esc_html($watch_time); ?>h</div>
                        <div class="pp-stat-card__label">Watch Time</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- MAIN CONTENT GRID -->
    <!-- ============================================================ -->
    <div class="pp-main-layout">

        <!-- LEFT COLUMN - Main Content -->
        <div class="pp-main-content">

            <!-- Continue Watching -->
            <section class="pp-section">
                <div class="pp-section__header">
                    <h2 class="pp-section__title">Continue Watching</h2>
                    <a href="#" class="pp-section__link">View All</a>
                </div>
                <div class="pp-movie-row" id="continue-watching-row">
                    <?php foreach ($continue_watching as $item) : ?>
                    <div class="pp-movie-card" data-id="<?php echo esc_attr($item['id']); ?>" data-type="<?php echo esc_attr($item['type']); ?>">
                        <div class="pp-movie-card__poster-wrap">
                            <img src="<?php echo esc_url($item['poster']); ?>" alt="<?php echo esc_attr($item['title']); ?>" class="pp-movie-card__poster">
                            <div class="pp-movie-card__overlay">
                                <button class="pp-movie-card__play" aria-label="Play">
                                    <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </button>
                            </div>
                            <div class="pp-movie-card__progress">
                                <div class="pp-movie-card__progress-bar" style="width: <?php echo esc_attr($item['progress']); ?>%"></div>
                            </div>
                        </div>
                        <h4 class="pp-movie-card__title"><?php echo esc_html($item['title']); ?></h4>
                        <p class="pp-movie-card__meta"><?php echo esc_html($item['duration']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- My List -->
            <section class="pp-section">
                <div class="pp-section__header">
                    <h2 class="pp-section__title">My List</h2>
                    <a href="#" class="pp-section__link">View All</a>
                </div>
                <div class="pp-movie-row" id="my-list-row">
                    <?php foreach ($my_list as $item) : ?>
                    <div class="pp-movie-card" data-id="<?php echo esc_attr($item['id']); ?>" data-type="movie">
                        <div class="pp-movie-card__poster-wrap">
                            <img src="<?php echo esc_url($item['poster']); ?>" alt="<?php echo esc_attr($item['title']); ?>" class="pp-movie-card__poster">
                            <div class="pp-movie-card__badge pp-movie-card__badge--bookmark">
                                <svg viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </div>
                            <div class="pp-movie-card__overlay">
                                <button class="pp-movie-card__play" aria-label="Play">
                                    <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </button>
                            </div>
                        </div>
                        <h4 class="pp-movie-card__title"><?php echo esc_html($item['title']); ?></h4>
                        <p class="pp-movie-card__meta"><?php echo esc_html($item['year']); ?> • <?php echo esc_html($item['rating']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Recently Watched -->
            <section class="pp-section">
                <div class="pp-section__header">
                    <h2 class="pp-section__title">Recently Watched</h2>
                    <a href="#" class="pp-section__link">View All</a>
                </div>
                <div class="pp-movie-row" id="recently-watched-row">
                    <?php foreach ($recently_watched as $item) : ?>
                    <div class="pp-movie-card" data-id="<?php echo esc_attr($item['id']); ?>" data-type="<?php echo esc_attr($item['type']); ?>">
                        <div class="pp-movie-card__poster-wrap">
                            <img src="<?php echo esc_url($item['poster']); ?>" alt="<?php echo esc_attr($item['title']); ?>" class="pp-movie-card__poster">
                            <div class="pp-movie-card__badge <?php echo $item['completed'] ? 'pp-movie-card__badge--completed' : ''; ?>">
                                <?php if ($item['completed']) : ?>
                                <svg viewBox="0 0 24 24" style="fill: #46d369;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                <?php else : ?>
                                <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="pp-movie-card__overlay">
                                <button class="pp-movie-card__play" aria-label="Play">
                                    <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </button>
                            </div>
                        </div>
                        <h4 class="pp-movie-card__title"><?php echo esc_html($item['title']); ?></h4>
                        <p class="pp-movie-card__meta">Watched <?php echo esc_html($item['watched']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Preferences -->
            <section class="pp-section">
                <div class="pp-section__header">
                    <h2 class="pp-section__title">Preferences</h2>
                </div>
                <div class="pp-preferences">
                    <div class="pp-preferences__grid">
                        <!-- Favorite Genres -->
                        <div class="pp-pref-item">
                            <label class="pp-pref-item__label">Favorite Genres</label>
                            <div class="pp-pref-item__tags" id="genre-tags">
                                <?php foreach ($all_genres as $genre) : 
                                    $is_active = in_array($genre, $user_genres);
                                ?>
                                <button class="pp-tag <?php echo $is_active ? 'pp-tag--active' : ''; ?>" 
                                        data-genre="<?php echo esc_attr(strtolower($genre)); ?>"
                                        data-action="toggle-genre">
                                    <?php echo esc_html($genre); ?>
                                </button>
                                <?php endforeach; ?>
                                <button class="pp-tag pp-tag--add" data-action="add-genre">
                                    + Add Genre
                                </button>
                            </div>
                        </div>

                        <!-- Content Language -->
                        <div class="pp-pref-item">
                            <label class="pp-pref-item__label">Content Language</label>
                            <select class="pp-select" id="content-language">
                                <?php foreach ($languages as $lang) : ?>
                                <option value="<?php echo esc_attr(strtolower($lang)); ?>" <?php selected($selected_language, $lang); ?>>
                                    <?php echo esc_html($lang); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="pp-select-hint">Change the language of movies and TV shows.</p>
                        </div>

                        <!-- Subtitle Language -->
                        <div class="pp-pref-item">
                            <label class="pp-pref-item__label">Subtitle Language</label>
                            <select class="pp-select" id="subtitle-language">
                                <option value="off" <?php selected($selected_subtitle, 'Off'); ?>>Off</option>
                                <?php foreach ($languages as $lang) : ?>
                                <option value="<?php echo esc_attr(strtolower($lang)); ?>" <?php selected($selected_subtitle, $lang . ' (Default)'); ?>>
                                    <?php echo esc_html($lang); ?> <?php echo $lang === 'English' ? '(Default)' : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="pp-select-hint">Change the default subtitle language.</p>
                        </div>
                    </div>
                </div>
            </section>

        </div><!-- /.pp-main-content -->

        <!-- RIGHT SIDEBAR -->
        <aside class="pp-sidebar">

            <!-- Account Card -->
            <div class="pp-account-card">
                <div class="pp-account-card__header">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="pp-account-card__avatar">
                    <div class="pp-account-card__user">
                        <div class="pp-account-card__name"><?php echo esc_html($user->display_name ?: 'Account'); ?></div>
                        <div class="pp-account-card__role"><?php echo esc_html(ucfirst($vip_status)); ?> Member</div>
                    </div>
                </div>
                <ul class="pp-account-menu">
                    <li class="pp-account-menu__item pp-account-menu__item--active" data-action="edit-profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>Manage Profile</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="change-password">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <span>Change Password</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="email-preferences">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span>Email Preferences</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="playback-settings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        <span>Playback Settings</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="parental-controls">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <span>Parental Controls</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="payment-methods">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        <span>Payment Methods</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="subscription-plan" data-scroll="subscription">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <span>Subscription Plan</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="manage-devices">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                        <span>Manage Devices</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="notification-settings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span>Notification Settings</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="privacy-settings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        <span>Privacy Settings</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item" data-action="language">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                        <span>Language — <?php echo esc_html($selected_language); ?></span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                    <li class="pp-account-menu__item pp-account-menu__item--logout" data-action="logout" data-href="<?php echo esc_url(wp_logout_url()); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        <span>Log Out</span>
                        <svg class="pp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </li>
                </ul>
            </div>

            <!-- Subscription Plan Card -->
            <div class="pp-subscription-card pp-scroll-to" id="subscription">
                <div class="pp-subscription-card__header">
                    <h3 class="pp-subscription-card__title">Subscription Plan</h3>
                    <span class="pp-subscription-card__badge">Active</span>
                </div>
                <p class="pp-subscription-card__plan"><?php echo esc_html($vip_plan); ?> Plan</p>
                <div class="pp-subscription-card__price">
                    $<?php echo esc_html($vip_price); ?><span>/month</span>
                </div>
                <ul class="pp-subscription-card__features">
                    <li>4K Ultra HD + HDR</li>
                    <li>Watch on 4 devices at once</li>
                    <li>Unlimited movies & TV shows</li>
                    <li>Download on 4 devices</li>
                    <li>No ads</li>
                    <li>Priority customer support</li>
                </ul>
                <button class="pp-subscription-card__btn" data-action="manage-subscription">Manage Subscription</button>
            </div>

            <!-- Profiles Card -->
            <div class="pp-profiles-card">
                <div class="pp-profiles-card__header">
                    <h3 class="pp-profiles-card__title">Profiles</h3>
                    <button class="pp-profiles-card__add-btn" data-action="add-profile">
                        + Add Profile
                    </button>
                </div>
                <div class="pp-profiles-grid">
                    <?php foreach ($profiles as $profile) : ?>
                    <div class="pp-profile-item <?php echo $profile['is_active'] ? 'pp-profile-item--active' : ''; ?>" 
                         data-profile-id="<?php echo esc_attr($profile['id']); ?>"
                         data-action="edit-profile-item">
                        <?php if ($profile['is_main']) : ?>
                        <div class="pp-profile-item__crown">
                            <svg viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        </div>
                        <?php endif; ?>
                        <img src="<?php echo esc_url($profile['avatar']); ?>" alt="<?php echo esc_attr($profile['name']); ?>" class="pp-profile-item__avatar">
                        <button class="pp-profile-item__edit" data-action="edit-profile-item" data-id="<?php echo esc_attr($profile['id']); ?>">
                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <span class="pp-profile-item__name"><?php echo esc_html($profile['name']); ?></span>
                        <span class="pp-profile-item__role"><?php echo esc_html($profile['role']); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="pp-add-profile-btn" data-action="add-profile">
                        <div class="pp-add-profile-btn__icon">
                            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        </div>
                        <span class="pp-add-profile-btn__label">Add Profile</span>
                    </div>
                </div>
            </div>

        </aside>

    </div><!-- /.pp-main-layout -->

    <?php endif; // is_logged_in ?>

</main>

<!-- ============================================================ -->
<!-- FOOTER -->
<!-- ============================================================ -->
<footer class="pp-footer">
    <div class="pp-container">
        <div class="pp-footer__grid">
            <!-- Brand -->
            <div class="pp-footer__brand">
                <div class="pp-footer__logo">
                    <div class="pp-footer__logo-mark">M</div>
                    <span class="pp-footer__logo-text">MOVIE</span>
                </div>
                <p class="pp-footer__desc">
                    Your ultimate destination for streaming movies and TV shows. 
                    Watch anywhere, anytime, on any device.
                </p>
                <div class="pp-footer__social">
                    <a href="#" aria-label="Facebook">
                        <svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                    </a>
                    <a href="#" aria-label="Instagram">
                        <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="#" aria-label="YouTube">
                        <svg viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                    </a>
                </div>
            </div>

            <!-- Browse -->
            <div class="pp-footer__col">
                <h4>Browse</h4>
                <ul>
                    <li><a href="<?php echo esc_url($movies_archive ?: home_url('/movies')); ?>">Movies</a></li>
                    <li><a href="<?php echo esc_url($tv_archive ?: home_url('/tv')); ?>">TV Shows</a></li>
                    <li><a href="#">Trending</a></li>
                    <li><a href="#">Top Rated</a></li>
                    <li><a href="#">New Releases</a></li>
                </ul>
            </div>

            <!-- Account -->
            <div class="pp-footer__col">
                <h4>Account</h4>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/favorites')); ?>">My List</a></li>
                    <li><a href="<?php echo esc_url(home_url('/history')); ?>">Watch History</a></li>
                    <li><a href="<?php echo esc_url(home_url('/profile')); ?>">Profile</a></li>
                    <li><a href="<?php echo esc_url(home_url('/vip')); ?>">Subscription</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="pp-footer__col">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Terms of Use</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
                <div class="pp-footer__newsletter">
                    <h4 style="margin-top: 20px; margin-bottom: 12px;">Newsletter</h4>
                    <form class="pp-footer__newsletter-form" onsubmit="event.preventDefault(); showToast('Thank you for subscribing!', 'success');">
                        <input type="email" class="pp-footer__newsletter-input" placeholder="Your email" required>
                        <button type="submit" class="pp-footer__newsletter-btn">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="pp-footer__bottom">
            <p class="pp-footer__copyright">&copy; <?php echo date('Y'); ?> MOVIE. All rights reserved.</p>
            <div class="pp-footer__links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Sitemap</a>
            </div>
        </div>
    </div>
</footer>

<!-- ============================================================ -->
<!-- MODALS -->
<!-- ============================================================ -->
<div class="pp-modal-backdrop" id="pp-modal-backdrop">
    <div class="pp-modal" id="pp-modal">
        <div class="pp-modal__header">
            <h3 class="pp-modal__title" id="pp-modal-title">Modal Title</h3>
            <button class="pp-modal__close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="pp-modal__body" id="pp-modal-body">
            <!-- Modal content will be injected here -->
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="pp-toast" id="pp-toast"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="<?php echo esc_url(get_theme_file_uri('assets/js/ms-profile.js')); ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
