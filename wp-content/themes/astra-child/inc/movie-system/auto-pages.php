<?php
/**
 * Auto Create Required Pages + assign Movix templates when missing.
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, array{title: string, template: string}>
 */
function mu_required_streaming_pages_meta(): array {
    return [
        'watch'        => ['title' => 'Watch', 'template' => 'page-watch.php'],
        'search'       => ['title' => 'Search', 'template' => 'page-search.php'],
        'favorites'    => ['title' => 'My List', 'template' => 'page-favorites.php'],
        'history'      => ['title' => 'History', 'template' => 'page-history.php'],
        'profile'      => ['title' => 'Profile', 'template' => 'page-profile.php'],
        'vip'          => ['title' => 'VIP Pricing', 'template' => 'page-vip.php'],
        'trending'     => ['title' => 'Trending', 'template' => 'page-trending.php'],
        'top-rated'    => ['title' => 'Top Rated', 'template' => 'page-top-rated.php'],
        'new-releases' => ['title' => 'New Releases', 'template' => 'page-new-releases.php'],
    ];
}

/**
 * Create pages if missing; set Page Template when template is unset/default only.
 */
function mu_create_required_pages(): void {
    $defs = mu_required_streaming_pages_meta();
    foreach ($defs as $slug => $cfg) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page instanceof WP_Post) {
            $id = wp_insert_post([
                'post_type'    => 'page',
                'post_title'   => $cfg['title'],
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_author'  => 1,
            ], true);

            if (!is_wp_error($id) && $id) {
                update_post_meta((int) $id, '_wp_page_template', $cfg['template']);
            }
            continue;
        }

        $tpl = (string) get_post_meta($page->ID, '_wp_page_template', true);
        if ($tpl === '' || $tpl === 'default') {
            update_post_meta($page->ID, '_wp_page_template', $cfg['template']);
        }
    }
}

add_action('admin_init', 'mu_create_required_pages');
add_action('after_switch_theme', 'mu_create_required_pages');
