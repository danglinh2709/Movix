<?php
/**
 * Auto Create Required Pages
 */

if (!defined('ABSPATH')) exit;

/**
 * Automatically create essential pages for the streaming site
 */
function mu_create_required_pages() {
    $pages = [
        'watch' => 'Watch',
        'search' => 'Search',
        'favorites' => 'My List',
        'history' => 'History',
        'profile' => 'Profile',
        'vip' => 'VIP Pricing',
        'trending' => 'Trending',
        'top-rated' => 'Top Rated',
        'new-releases' => 'New Releases'
    ];

    foreach ($pages as $slug => $title) {
        $page_check = get_page_by_path($slug);
        if (!isset($page_check->ID)) {
            wp_insert_post([
                'post_type' => 'page',
                'post_title' => $title,
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_author' => 1
            ]);
        }
    }
}

// Hook it to admin init so it runs when admin accesses the dashboard
add_action('admin_init', 'mu_create_required_pages');
