<?php
/**
 * Premium Streaming Navbar: M MOVIE • Home • Movies • TV Shows • Trending • My List
 * Right: Search icon • Notification bell • Profile avatar with dropdown
 * Transparent over hero, darkens on scroll. Fully responsive.
 */
$avatar_url = get_avatar_url(get_current_user_id() ?: 0, ['size' => 80]);
if (!$avatar_url || is_wp_error($avatar_url)) {
    $avatar_url = 'https://secure.gravatar.com/avatar/00000000000000000000000000000000?s=80&d=mm&r=g';
}

$movies_archive = function_exists('mu_get_page_url_by_slug')
    ? mu_get_page_url_by_slug('movies')
    : trailingslashit(home_url('movies'));
$tv_archive     = trailingslashit(home_url('/tv'));

$trend_u   = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('trending')     : trailingslashit(home_url('trending'));
$list_u    = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('favorites')   : trailingslashit(home_url('favorites'));
$search_u  = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('search')      : trailingslashit(home_url('search'));
$prof_u    = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('profile')    : trailingslashit(home_url('profile'));
$vip_u     = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('vip')        : trailingslashit(home_url('vip'));
$hist_u    = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('history')    : trailingslashit(home_url('history'));
$toprated_u= function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('top-rated')  : trailingslashit(home_url('top-rated'));
$newrel_u  = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('new-releases') : trailingslashit(home_url('new-releases'));

$home = home_url('/');
$log  = is_user_logged_in();

$current_user = $log ? wp_get_current_user() : null;
$display_name = $current_user ? $current_user->display_name : '';

// Get current path for active state detection
$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$current_path = untrailingslashit($current_path);

$active = static function (string $k) use ($log, $current_path): string {
    $log_class = $log ? ' is-logged-in' : '';
    switch ($k) {
        case 'home':
            return ($current_path === '' || $current_path === '/' || $current_path === home_url('/', 'relative')) ? 'is-active' . $log_class : '';
        case 'movies':
            return ($current_path === '/movies' || $current_path === 'movies') ? 'is-active' . $log_class : '';
        case 'tv':
            return ($current_path === '/tv' || $current_path === 'tv') ? 'is-active' . $log_class : '';
        case 'trending':
            return ($current_path === '/trending' || $current_path === 'trending') ? 'is-active' . $log_class : '';
        case 'toprated':
            return ($current_path === '/top-rated' || $current_path === 'top-rated') ? 'is-active' . $log_class : '';
        case 'newrel':
            return ($current_path === '/new-releases' || $current_path === 'new-releases') ? 'is-active' . $log_class : '';
        case 'list':
            return ($current_path === '/favorites' || $current_path === 'favorites') ? 'is-active' . $log_class : '';
        case 'history':
            return ($current_path === '/history' || $current_path === 'history') ? 'is-active' . $log_class : '';
        case 'search':
            return ($current_path === '/search' || $current_path === 'search') ? 'is-active' . $log_class : '';
        case 'profile':
            return ($current_path === '/profile' || $current_path === 'profile') ? 'is-active' . $log_class : '';
        case 'vip':
            return ($current_path === '/vip' || $current_path === 'vip') ? 'is-active' . $log_class : '';
        case 'watch':
            return ($current_path === '/watch' || $current_path === 'watch') ? 'is-active' . $log_class : '';
        default:
            return '';
    }
};
?>
<header class="mu-header" data-mu-header role="banner">

    <div class="mu-header__inner">

        <?php // Mobile burger ?>
        <!-- <button type="button" class="mu-burger" data-mu-open-drawer aria-label="<?php esc_attr_e('Menu', 'astra-child'); ?>">
            <span></span>
        </button> -->

        <?php // Logo ?>
        <a href="<?php echo esc_url($home); ?>" class="mu-brand" aria-label="<?php esc_attr_e('Home', 'astra-child'); ?>">
            <span class="mu-brand__mark" aria-hidden="true">M</span>
            <span class="mu-brand__text"><?php esc_html_e('MOVIE', 'astra-child'); ?></span>
        </a>

        <?php // Main Navigation ?>
        <nav class="mu-nav" aria-label="<?php esc_attr_e('Primary Navigation', 'astra-child'); ?>">
            <a href="<?php echo esc_url($home); ?>"
               class="<?php echo esc_attr($active('home')); ?>"
               data-nav="home">
                <?php esc_html_e('Home', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url($movies_archive); ?>"
               class="<?php echo esc_attr($active('movies')); ?>"
               data-nav="movies">
                <?php esc_html_e('Movies', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url($tv_archive); ?>"
               class="<?php echo esc_attr($active('tv')); ?>"
               data-nav="tv">
                <?php esc_html_e('TV Shows', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url($trend_u); ?>"
               class="<?php echo esc_attr($active('trending')); ?>"
               data-nav="trending">
                <?php esc_html_e('Trending', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url($toprated_u); ?>"
               class="<?php echo esc_attr($active('toprated')); ?>"
               data-nav="toprated">
                <?php esc_html_e('Top Rated', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url($list_u); ?>"
               class="<?php echo esc_attr($active('list')); ?>"
               data-nav="list">
                <?php esc_html_e('My List', 'astra-child'); ?>
            </a>
            <a href="<?php echo esc_url($newrel_u); ?>"
               class="<?php echo esc_attr($active('newrel')); ?>"
               data-nav="newrel">
                <?php esc_html_e('New Releases', 'astra-child'); ?>
            </a>
            <!-- <a href="<?php echo esc_url($search_u); ?>"
               class="<?php echo esc_attr($active('watch')); ?>"
               data-nav="watch">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px;">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <?php esc_html_e('Watch', 'astra-child'); ?>
            </a> -->
        </nav>

        <?php // Right actions ?>
        <div class="mu-header__actions">

            <?php // Search button — opens search overlay/page ?>
            <button type="button"
                    class="mu-icon-btn mu-icon-btn--search"
                    data-mu-open-search
                    aria-label="<?php esc_attr_e('Search', 'astra-child'); ?>"
                    title="<?php esc_attr_e('Search', 'astra-child'); ?>"
                    onclick="window.location.href='<?php echo esc_url($search_u); ?>'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>

            <?php // Notification bell ?>
            <button type="button"
                    class="mu-icon-btn mu-icon-btn--bell"
                    data-mu-open-notifications
                    aria-label="<?php esc_attr_e('Notifications', 'astra-child'); ?>"
                    title="<?php esc_attr_e('Notifications', 'astra-child'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="mu-icon-badge" hidden>0</span>
            </button>

            <?php // Profile avatar + dropdown ?>
            <div class="mu-user-menu" data-mu-user-menu>
                <button type="button"
                        class="mu-avatar-btn"
                        data-mu-open-profile
                        aria-label="<?php esc_attr_e('Profile Menu', 'astra-child'); ?>"
                        aria-expanded="false"
                        aria-haspopup="true">
                    <img src="<?php echo esc_url($avatar_url); ?>"
                         alt="<?php echo esc_attr($display_name ?: __('User avatar', 'astra-child')); ?>"
                         class="mu-avatar"
                         width="32"
                         height="32"
                         loading="lazy"
                         decoding="async">
                </button>

                <div class="mu-user-dropdown" data-mu-user-dropdown hidden>
                    <?php if ($log) : ?>
                        <div class="mu-user-dropdown__header">
                            <img src="<?php echo esc_url($avatar_url); ?>"
                                 alt=""
                                 class="mu-user-dropdown__avatar"
                                 width="44"
                                 height="44"
                                 loading="lazy"
                                 decoding="async">
                            <div class="mu-user-dropdown__info">
                                <span class="mu-user-dropdown__name"><?php echo esc_html($display_name); ?></span>
                                <span class="mu-user-dropdown__email"><?php echo esc_html($current_user->user_email ?? ''); ?></span>
                            </div>
                        </div>
                        <hr class="mu-user-dropdown__divider">
                        <a href="<?php echo esc_url($prof_u); ?>" class="mu-user-dropdown__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <?php esc_html_e('Profile', 'astra-child'); ?>
                        </a>
                        <a href="<?php echo esc_url($list_u); ?>" class="mu-user-dropdown__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <?php esc_html_e('My List', 'astra-child'); ?>
                        </a>
                        <a href="<?php echo esc_url($hist_u); ?>" class="mu-user-dropdown__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?php esc_html_e('History', 'astra-child'); ?>
                        </a>
                        <a href="<?php echo esc_url($vip_u); ?>" class="mu-user-dropdown__item mu-user-dropdown__item--vip">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php esc_html_e('VIP Membership', 'astra-child'); ?>
                        </a>
                        <hr class="mu-user-dropdown__divider">
                        <a href="<?php echo esc_url(wp_logout_url($home)); ?>" class="mu-user-dropdown__item mu-user-dropdown__item--logout">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <?php esc_html_e('Logout', 'astra-child'); ?>
                        </a>
                    <?php else : ?>
                        <div class="mu-user-dropdown__guest">
                            <p class="mu-user-dropdown__guest-text"><?php esc_html_e('Sign in to access your profile, watchlist and history.', 'astra-child'); ?></p>
                            <a href="<?php echo esc_url(wp_login_url($home)); ?>" class="mu-user-dropdown__signin-btn">
                                <?php esc_html_e('Sign In', 'astra-child'); ?>
                            </a>
                            <?php if (get_option('users_can_register')) : ?>
                                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="mu-user-dropdown__register-btn">
                                    <?php esc_html_e('Create Account', 'astra-child'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <?php // Active indicator bar ?>
    <div class="mu-header__bar" aria-hidden="true"></div>

</header>

<?php // ============================================================
      // NOTIFICATIONS DROPDOWN
      // ============================================================ ?>
<div class="mu-notifications-dropdown" data-mu-notifications-dropdown hidden>
    <div class="mu-notifications-dropdown__header">
        <h3 class="mu-notifications-dropdown__title"><?php esc_html_e('Notifications', 'astra-child'); ?></h3>
        <button type="button" class="mu-notifications-dropdown__close" data-mu-close-notifications aria-label="<?php esc_attr_e('Close', 'astra-child'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="mu-notifications-dropdown__body">
        <div class="mu-notifications-dropdown__empty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <p><?php esc_html_e('No new notifications', 'astra-child'); ?></p>
        </div>
    </div>
</div>
<div class="mu-notifications-backdrop" data-mu-close-notifications hidden></div>

<?php // ============================================================
      // MOBILE DRAWER
      // ============================================================ ?>
<div class="mu-drawer" data-mu-drawer aria-hidden="true">
    <div class="mu-drawer__backdrop" data-mu-close-drawer></div>
    <div class="mu-drawer__panel" tabindex="-1">
        <button type="button" class="mu-drawer__close" data-mu-close-drawer aria-label="<?php esc_attr_e('Close Menu', 'astra-child'); ?>">&times;</button>

        <?php if ($log) : ?>
            <div class="mu-drawer__user">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="mu-drawer__avatar" width="48" height="48" loading="lazy" decoding="async">
                <div>
                    <div class="mu-drawer__user-name"><?php echo esc_html($display_name); ?></div>
                    <a href="<?php echo esc_url($prof_u); ?>" class="mu-drawer__user-link"><?php esc_html_e('View Profile', 'astra-child'); ?></a>
                </div>
            </div>
        <?php endif; ?>

        <nav class="mu-drawer__nav" aria-label="<?php esc_attr_e('Mobile Navigation', 'astra-child'); ?>">
            <a href="<?php echo esc_url($home); ?>"><?php esc_html_e('Home', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($movies_archive); ?>"><?php esc_html_e('Movies', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($tv_archive); ?>"><?php esc_html_e('TV Shows', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($trend_u); ?>"><?php esc_html_e('Trending', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($toprated_u); ?>"><?php esc_html_e('Top Rated', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($newrel_u); ?>"><?php esc_html_e('New Releases', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($list_u); ?>"><?php esc_html_e('My List', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($hist_u); ?>"><?php esc_html_e('History', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($search_u); ?>"><?php esc_html_e('Search', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($prof_u); ?>"><?php esc_html_e('Profile', 'astra-child'); ?></a>
            <a href="<?php echo esc_url($vip_u); ?>"><?php esc_html_e('VIP', 'astra-child'); ?></a>
        </nav>

        <hr class="mu-drawer__hr">

        <?php if ($log) : ?>
            <a href="<?php echo esc_url(wp_logout_url($home)); ?>" class="mu-drawer__logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <?php esc_html_e('Logout', 'astra-child'); ?>
            </a>
        <?php else : ?>
            <div class="mu-drawer__auth">
                <a href="<?php echo esc_url(wp_login_url($home)); ?>" class="mu-drawer__auth-btn mu-drawer__auth-btn--signin">
                    <?php esc_html_e('Sign In', 'astra-child'); ?>
                </a>
                <?php if (get_option('users_can_register')) : ?>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="mu-drawer__auth-btn mu-drawer__auth-btn--register">
                        <?php esc_html_e('Create Account', 'astra-child'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
