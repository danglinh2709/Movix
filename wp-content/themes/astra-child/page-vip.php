<?php
/**
 * Template Name: VIP Pricing
 */
get_header();
get_template_part('template-parts/streaming/header');

$list_u = mu_get_page_url_by_slug('profile');
?>

<div class="mu-page mu-vip-prem">
    <div class="mu-container" style="padding-top:88px;">
        <h1 class="mu-h1"><?php esc_html_e('VIP access', 'astra-child'); ?></h1>
        <p class="mu-muted"><?php esc_html_e('Ultra HD streams, simultaneous devices, concierge support.', 'astra-child'); ?></p>

        <div class="mu-vip-cards">
            <article class="mu-vip-tier">
                <h3><?php esc_html_e('Lite', 'astra-child'); ?></h3>
                <p class="mu-vip-price">$<strong>5</strong><span><?php esc_html_e('/month', 'astra-child'); ?></span></p>
                <ul class="mu-vip-list">
                    <li><?php esc_html_e('720p cinematic streaming', 'astra-child'); ?></li>
                    <li><?php esc_html_e('1 device • Ad-light', 'astra-child'); ?></li>
                </ul>
                <button type="button" class="mu-btn mu-btn--ghost" disabled><?php esc_html_e('Notify me', 'astra-child'); ?></button>
            </article>
            <article class="mu-vip-tier mu-vip-tier--accent">
                <span class="mu-vip-ribbon"><?php esc_html_e('Best value', 'astra-child'); ?></span>
                <h3><?php esc_html_e('Max', 'astra-child'); ?></h3>
                <p class="mu-vip-price">$<strong>10</strong><span><?php esc_html_e('/month', 'astra-child'); ?></span></p>
                <ul class="mu-vip-list">
                    <li><?php esc_html_e('4K Dolby Vision-ish palette', 'astra-child'); ?></li>
                    <li><?php esc_html_e('4 concurrent screens', 'astra-child'); ?></li>
                    <li><?php esc_html_e('Priority encode queue', 'astra-child'); ?></li>
                </ul>
                <button type="button" class="mu-btn mu-btn--primary" disabled><?php esc_html_e('Coming soon', 'astra-child'); ?></button>
            </article>
            <article class="mu-vip-tier">
                <h3><?php esc_html_e('Studio', 'astra-child'); ?></h3>
                <p class="mu-vip-price"><?php esc_html_e('Talk to us', 'astra-child'); ?></p>
                <ul class="mu-vip-list">
                    <li><?php esc_html_e('Brand white-label kiosk', 'astra-child'); ?></li>
                    <li><?php esc_html_e('SLA + ingestion pipeline', 'astra-child'); ?></li>
                </ul>
                <a class="mu-btn mu-btn--ghost" href="<?php echo esc_url($list_u); ?>"><?php esc_html_e('Account', 'astra-child'); ?></a>
            </article>
        </div>

        <div class="mu-vip-matrix mu-panel glass" style="margin-top:42px;">
            <h3 class="mu-h3"><?php esc_html_e('Comparison', 'astra-child'); ?></h3>
            <table class="mu-comp-table">
                <thead><tr><th></th><th><?php esc_html_e('Lite', 'astra-child'); ?></th><th><?php esc_html_e('Max', 'astra-child'); ?></th></tr></thead>
                <tbody>
                    <tr><td><?php esc_html_e('Ultra HD', 'astra-child'); ?></td><td>—</td><td><?php esc_html_e('Included', 'astra-child'); ?></td></tr>
                    <tr><td><?php esc_html_e('Downloads', 'astra-child'); ?></td><td>—</td><td><?php esc_html_e('Roadmap', 'astra-child'); ?></td></tr>
                    <tr><td><?php esc_html_e('Support', 'astra-child'); ?></td><td>Email</td><td>VIP Slack</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php get_footer(); ?>
