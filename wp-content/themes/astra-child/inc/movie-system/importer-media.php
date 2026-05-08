<?php
/**
 * Handle Media Sideloading for Importer
 */

if (!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

/**
 * Download TMDB image and attach to post.
 * Returns attachment ID or false.
 */
function mu_importer_download_image($tmdb_path, $post_id, $desc = '') {
    if (empty($tmdb_path)) return false;

    // Build full TMDB image URL
    $image_url = 'https://image.tmdb.org/t/p/original' . $tmdb_path;

    // Check if we already downloaded this exact image for this post
    // (A simple heuristic is to check if it's already set as thumbnail, but since backdrops also exist, we just check meta)
    // Actually, `media_sideload_image` downloads it every time unless we write custom checks.
    // For now, we rely on the caller to only download if not already downloaded.

    $attachment_id = media_sideload_image($image_url, $post_id, $desc, 'id');

    if (is_wp_error($attachment_id)) {
        return false;
    }

    return $attachment_id;
}
