<?php
/**
 * Streaming routes: resolve Page permalinks by slug so menu never 404s when pages exist.
 *
 * Place: wp-content/themes/astra-child/inc/movie-system/routing-helpers.php
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string> Slugs for app pages matching page-{slug}.php
 */
function mu_streaming_page_slugs(): array {
    return [
        'movies',
        'tv',
        'watch',
        'search',
        'favorites',
        'history',
        'profile',
        'vip',
        'trending',
        'top-rated',
        'new-releases',
    ];
}

/**
 * Permalink for a published Page by slug; falls back to pretty URL root (rewrite still needs page).
 */
function mu_get_page_url_by_slug(string $slug): string {
    $slug = sanitize_title($slug);
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page instanceof WP_Post && $page->post_status === 'publish') {
        return get_permalink($page);
    }
    return trailingslashit(home_url($slug));
}
