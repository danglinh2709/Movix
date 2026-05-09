<?php
/**
 * Template Name: Profile / Account
 */
get_header();
get_template_part('template-parts/streaming/header');

$list_u = mu_get_page_url_by_slug('favorites');
$hist_u = mu_get_page_url_by_slug('history');
$vip_u  = mu_get_page_url_by_slug('vip');
$user   = wp_get_current_user();

function mu_profile_stat(string $label, string $value) : void {
    echo '<div class="mu-mini-stat">';
    echo '<span class="mu-mini-stat__v">' . esc_html($value) . '</span>';
    echo '<span class="mu-mini-stat__l">' . esc_html($label) . '</span>';
    echo '</div>';
}
?>

<div class="mu-page mu-profile-premium">
    <div class="mu-container" style="padding-top:88px;">
        <h1 class="mu-h1"><?php esc_html_e('Profile', 'astra-child'); ?></h1>

        <?php if (!is_user_logged_in()) : ?>
            <div class="mu-panel glass">
                <p class="mu-muted"><?php esc_html_e('Sign in for synced watch history & premium upgrades.', 'astra-child'); ?></p>
                <a class="mu-btn mu-btn--primary" href="<?php echo esc_url(wp_login_url(mu_get_page_url_by_slug('profile'))); ?>"><?php esc_html_e('Sign in', 'astra-child'); ?></a>
            </div>
        <?php else : ?>
            <div class="mu-profile-grid">
                <div class="mu-panel glass mu-profile-card">
                    <?php echo get_avatar((int) $user->ID, 96, '', '', ['class' => 'mu-prof-avatar']); ?>
                    <h2><?php echo esc_html($user->display_name ?: $user->user_login); ?></h2>
                    <p class="mu-muted"><?php echo esc_html($user->user_email); ?></p>
                    <div class="mu-profile-minirow">
                        <?php mu_profile_stat(__('Joined', 'astra-child'), gmdate(get_option('date_format'), strtotime($user->user_registered))); ?>
                        <?php mu_profile_stat(__('User ID', 'astra-child'), (string) get_current_user_id()); ?>
                    </div>
                </div>
                <div class="mu-profile-actions mu-panel glass">
                    <h3><?php esc_html_e('Library', 'astra-child'); ?></h3>
                    <div class="mu-profile-btnrow">
                        <a class="mu-btn mu-btn--ghost" href="<?php echo esc_url($list_u); ?>"><?php esc_html_e('My List', 'astra-child'); ?></a>
                        <a class="mu-btn mu-btn--ghost" href="<?php echo esc_url($hist_u); ?>"><?php esc_html_e('History', 'astra-child'); ?></a>
                        <a class="mu-btn mu-btn--primary" href="<?php echo esc_url($vip_u); ?>"><?php esc_html_e('Upgrade VIP', 'astra-child'); ?></a>
                        <a class="mu-btn mu-btn--ghost" href="<?php echo esc_url(admin_url('profile.php')); ?>"><?php esc_html_e('WP settings', 'astra-child'); ?></a>
                    </div>
                    <div class="mu-muted" style="margin-top:16px;font-size:13px;"><?php esc_html_e('Local progress also syncs automatically when AJAX handlers are configured.', 'astra-child'); ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
