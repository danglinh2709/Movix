<?php
/**
 * AJAX Actions and Core Import Logic
 * Enhanced with full cast, clips, reviews import
 */

if (!defined('ABSPATH')) exit;

// Include media handler
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// ============================================================
// AJAX: Import Single Item
// ============================================================
add_action('wp_ajax_mu_import_item', 'mu_ajax_import_item');
function mu_ajax_import_item() {
    check_ajax_referer('mu_import_nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $tmdb_id = isset($_POST['tmdb_id']) ? intval($_POST['tmdb_id']) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'movie';

    if (!$tmdb_id) wp_send_json_error('Invalid TMDB ID');

    // Fetch full details including credits, videos, reviews
    $tmdb_data = mu_api_tmdb_get_details($tmdb_id, $type);
    if (is_wp_error($tmdb_data)) {
        wp_send_json_error($tmdb_data->get_error_message());
    }

    // Process the item
    $result = mu_importer_process_item($tmdb_data, $type);
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    // Import additional data
    $post_id = $result['post_id'];
    mu_import_additional_data($post_id, $tmdb_id, $type, $tmdb_data);

    wp_send_json_success([
        'message' => 'Imported ' . $result['title'] . ' (' . $result['action'] . ')',
        'post_id' => $post_id
    ]);
}

// ============================================================
// AJAX: Bulk Import
// ============================================================
add_action('wp_ajax_mu_import_bulk', 'mu_ajax_import_bulk');
function mu_ajax_import_bulk() {
    check_ajax_referer('mu_import_nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'movie';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;

    if (empty($category)) {
        wp_send_json_error('No category selected');
    }

    $tmdb_data = mu_api_tmdb_get_list($category);
    if (is_wp_error($tmdb_data)) {
        wp_send_json_error($tmdb_data->get_error_message());
    }

    if (empty($tmdb_data['results'])) {
        wp_send_json_error('No results found from TMDB. Try a different category.');
    }

    $items = [];
    $count = 0;
    foreach ($tmdb_data['results'] as $res) {
        if ($count >= $limit) break;
        
        $item_type = $type;
        if (isset($res['media_type']) && $res['media_type'] !== 'person') {
            $item_type = $res['media_type'];
        }
        
        $title = $res['title'] ?? $res['name'] ?? '';
        if (empty($title)) continue;
        
        $items[] = [
            'id' => $res['id'],
            'type' => $item_type,
            'title' => $title
        ];
        $count++;
    }

    if (empty($items)) {
        wp_send_json_error('No valid items found in this category.');
    }

    wp_send_json_success(['items' => $items]);
}

// ============================================================
// AJAX: Import Cast with Profile Images
// ============================================================
add_action('wp_ajax_mu_import_cast_images', 'mu_ajax_import_cast_images');
function mu_ajax_import_cast_images() {
    check_ajax_referer('mu_import_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) wp_send_json_error('Invalid post ID');

    $tmdb_id = get_post_meta($post_id, '_tmdb_id', true);
    $type = get_post_type($post_id) === 'tv_show' ? 'tv' : 'movie';

    if (!$tmdb_id) wp_send_json_error('No TMDB ID found');

    // Fetch credits
    $credits = mu_api_tmdb_get_credits($tmdb_id, $type);
    if (is_wp_error($credits)) {
        wp_send_json_error($credits->get_error_message());
    }

    $imported = 0;
    if (!empty($credits['cast'])) {
        foreach (array_slice($credits['cast'], 0, 10) as $actor) {
            if (empty($actor['profile_path'])) continue;
            
            // Download and attach profile image
            $attach_id = mu_importer_download_image($actor['profile_path'], $post_id, $actor['name'] . ' - Profile', true);
            if ($attach_id && !is_wp_error($attach_id)) {
                $imported++;
            }
            
            usleep(200000); // Rate limit
        }
    }

    wp_send_json_success([
        'message' => 'Imported ' . $imported . ' cast profile images',
        'imported' => $imported
    ]);
}

// ============================================================
// AJAX: Backfill Cast, Clips, Reviews
// ============================================================
add_action('wp_ajax_mu_backfill_cast', 'mu_ajax_backfill_cast');
function mu_ajax_backfill_cast() {
    check_ajax_referer('mu_import_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $batch = 5;
    $task = isset($_POST['task']) ? sanitize_text_field($_POST['task']) : 'all';

    $posts = get_posts([
        'post_type'      => ['movie', 'tv_show'],
        'posts_per_page' => $batch,
        'offset'         => $offset,
        'post_status'    => 'publish',
        'meta_key'       => '_tmdb_id',
        'fields'         => 'ids',
    ]);

    if (empty($posts)) {
        wp_send_json_success(['done' => true, 'message' => 'All items processed.']);
    }

    $results = ['updated' => 0, 'failed' => 0, 'log' => []];
    $type_map = ['movie' => 'movie', 'tv_show' => 'tv'];

    foreach ($posts as $post_id) {
        $tmdb_id = get_post_meta($post_id, '_tmdb_id', true);
        $post_type = get_post_type($post_id);
        $tmdb_type = $type_map[$post_type] ?? 'movie';

        if (!$tmdb_id) {
            $results['log'][] = "#{$post_id}: no tmdb_id, skipped";
            continue;
        }

        // Get full details (includes credits, videos, reviews)
        $details = mu_api_tmdb_get_details($tmdb_id, $tmdb_type);
        if (is_wp_error($details)) {
            $results['failed']++;
            $results['log'][] = "#{$post_id}: API error - " . $details->get_error_message();
            continue;
        }

        // Import full cast with images
        if ($task === 'all' || $task === 'cast') {
            mu_import_full_cast($post_id, $details, $tmdb_id, $tmdb_type);
        }

        // Import all clips/videos
        if ($task === 'all' || $task === 'clips') {
            mu_import_all_videos($post_id, $details);
        }

        // Import reviews
        if ($task === 'all' || $task === 'reviews') {
            mu_import_reviews($post_id, $tmdb_id, $tmdb_type);
        }

        $results['updated']++;
        $results['log'][] = "#{$post_id} " . get_the_title($post_id) . ": updated ({$task})";
        usleep(400000); // Rate limit
    }

    wp_send_json_success([
        'done'       => false,
        'updated'    => $results['updated'],
        'failed'     => $results['failed'],
        'next_offset'=> $offset + $batch,
        'log'        => $results['log'],
    ]);
}

// ============================================================
// Import Full Cast with Profile Images
// ============================================================
function mu_import_full_cast($post_id, $details, $tmdb_id, $type) {
    if (empty($details['credits']['cast'])) return;

    $cast_data = [];
    $actor_names = [];
    $top_cast_ids = [];

    foreach (array_slice($details['credits']['cast'], 0, 30) as $i => $actor) {
        if (empty($actor['name'])) continue;

        $actor_data = [
            'name'            => sanitize_text_field($actor['name']),
            'character'       => sanitize_text_field($actor['character'] ?? ''),
            'profile_path'    => sanitize_text_field($actor['profile_path'] ?? ''),
            'order'           => (int)($actor['order'] ?? $i),
            'tmdb_person_id'  => (int)($actor['id'] ?? 0),
            'known_for_department' => sanitize_text_field($actor['known_for_department'] ?? 'Acting'),
            'popularity'      => (float)($actor['popularity'] ?? 0),
        ];

        // Download profile image for top 10 cast
        if ($i < 10 && !empty($actor['profile_path'])) {
            $attach_id = mu_importer_download_image($actor['profile_path'], $post_id, $actor['name'], true);
            if ($attach_id && !is_wp_error($attach_id)) {
                $actor_data['profile_image_id'] = $attach_id;
            }
            usleep(200000); // Rate limit
        }

        $cast_data[] = $actor_data;
        $actor_names[] = sanitize_text_field($actor['name']);
        
        if ($i < 15) {
            $top_cast_ids[] = (int)($actor['id'] ?? 0);
        }
    }

    if (!empty($cast_data)) {
        update_post_meta($post_id, '_cast', wp_json_encode($cast_data));
        update_post_meta($post_id, '_actors', implode(', ', array_slice($actor_names, 0, 15)));
        update_post_meta($post_id, '_top_cast_ids', implode(',', array_filter($top_cast_ids)));
    }

    if (!empty($actor_names)) {
        wp_set_object_terms($post_id, array_slice($actor_names, 0, 20), 'actor', false);
    }

    // Crew
    if (!empty($details['credits']['crew'])) {
        $crew_data = [];
        $director_names = [];
        $writer_names = [];
        $producer_names = [];

        foreach ($details['credits']['crew'] as $crew) {
            if (empty($crew['name'])) continue;

            $crew_item = [
                'name'           => sanitize_text_field($crew['name']),
                'job'            => sanitize_text_field($crew['job'] ?? ''),
                'department'     => sanitize_text_field($crew['department'] ?? ''),
                'profile_path'   => sanitize_text_field($crew['profile_path'] ?? ''),
                'tmdb_person_id' => (int)($crew['id'] ?? 0),
            ];

            $crew_data[] = $crew_item;

            if ($crew['job'] === 'Director') {
                $director_names[] = sanitize_text_field($crew['name']);
            }
            if (in_array($crew['job'], ['Screenplay', 'Writer', 'Story'])) {
                $writer_names[] = sanitize_text_field($crew['name']);
            }
            if ($crew['job'] === 'Producer') {
                $producer_names[] = sanitize_text_field($crew['name']);
            }
        }

        if (!empty($crew_data)) {
            update_post_meta($post_id, '_crew', wp_json_encode($crew_data));
        }
        if (!empty($director_names)) {
            update_post_meta($post_id, '_director', implode(', ', $director_names));
            wp_set_object_terms($post_id, $director_names, 'director', false);
        }
        if (!empty($writer_names)) {
            update_post_meta($post_id, '_writer', implode(', ', array_slice($writer_names, 0, 5)));
        }
        if (!empty($producer_names)) {
            update_post_meta($post_id, '_producer', implode(', ', array_slice($producer_names, 0, 5)));
        }
    }
}

// ============================================================
// Import All Videos/Clips
// ============================================================
function mu_import_all_videos($post_id, $details) {
    if (empty($details['videos']['results'])) return;

    $all_videos = [];
    $trailers = [];
    $teasers = [];
    $clips = [];
    $behind_scenes = [];
    $featurettes = [];
    $bloopers = [];

    foreach ($details['videos']['results'] as $video) {
        if (empty($video['key']) || $video['site'] !== 'YouTube') continue;

        $video_data = [
            'key'          => sanitize_text_field($video['key']),
            'name'         => sanitize_text_field($video['name'] ?? ''),
            'site'         => 'YouTube',
            'type'         => sanitize_text_field($video['type'] ?? 'Clip'),
            'official'     => (bool)($video['official'] ?? false),
            'published_at'  => sanitize_text_field($video['published_at'] ?? ''),
            'url'          => 'https://www.youtube.com/watch?v=' . $video['key'],
            'embed_url'    => 'https://www.youtube.com/embed/' . $video['key'],
            'thumbnail'    => 'https://img.youtube.com/vi/' . $video['key'] . '/hqdefault.jpg',
            'thumbnail_hd' => 'https://img.youtube.com/vi/' . $video['key'] . '/maxresdefault.jpg',
        ];

        $type = strtolower($video['type'] ?? 'clip');

        $all_videos[] = $video_data;

        // Categorize by type
        if ($type === 'trailer') {
            $trailers[] = $video_data;
        } elseif ($type === 'teaser') {
            $teasers[] = $video_data;
        } elseif ($type === 'clip' || $type === 'preview') {
            $clips[] = $video_data;
        } elseif ($type === 'behind the scenes') {
            $behind_scenes[] = $video_data;
        } elseif ($type === 'featurette') {
            $featurettes[] = $video_data;
        } elseif ($type === 'blooper') {
            $bloopers[] = $video_data;
        }
    }

    // Save all videos as single JSON
    if (!empty($all_videos)) {
        update_post_meta($post_id, '_all_videos', wp_json_encode($all_videos));
        update_post_meta($post_id, '_video_count', count($all_videos));
    }

    // Set primary trailer (official trailer, first one)
    foreach ($trailers as $t) {
        if (!get_post_meta($post_id, '_trailer_url', true)) {
            update_post_meta($post_id, '_trailer_url', $t['url']);
            update_post_meta($post_id, '_trailer_key', $t['key']);
            update_post_meta($post_id, '_trailer_name', $t['name']);
        }
    }

    // Save categorized videos (for easy display)
    update_post_meta($post_id, '_trailers', wp_json_encode($trailers));
    update_post_meta($post_id, '_teasers', wp_json_encode($teasers));
    update_post_meta($post_id, '_clips', wp_json_encode($clips));
    update_post_meta($post_id, '_behind_scenes', wp_json_encode($behind_scenes));
    update_post_meta($post_id, '_featurettes', wp_json_encode($featurettes));
    update_post_meta($post_id, '_bloopers', wp_json_encode($bloopers));
}

// ============================================================
// Import Reviews
// ============================================================
function mu_import_reviews($post_id, $tmdb_id, $type, $page = 1) {
    $reviews_data = mu_api_tmdb_get_reviews($tmdb_id, $type, $page);
    if (is_wp_error($reviews_data) || empty($reviews_data['results'])) {
        return false;
    }

    $reviews = [];
    foreach ($reviews_data['results'] as $review) {
        $reviews[] = [
            'id'           => sanitize_text_field($review['id'] ?? ''),
            'author'       => sanitize_text_field($review['author'] ?? ''),
            'author_details' => [
                'name'        => sanitize_text_field($review['author_details']['name'] ?? ''),
                'username'    => sanitize_text_field($review['author_details']['username'] ?? ''),
                'avatar_path' => sanitize_text_field($review['author_details']['avatar_path'] ?? ''),
                'rating'      => (float)($review['author_details']['rating'] ?? 0),
            ],
            'content'      => wp_strip_all_tags($review['content'] ?? ''),
            'created_at'   => sanitize_text_field($review['created_at'] ?? ''),
            'updated_at'   => sanitize_text_field($review['updated_at'] ?? ''),
            'url'          => esc_url_raw($review['url'] ?? ''),
        ];
    }

    if (!empty($reviews)) {
        // Merge with existing reviews
        $existing = get_post_meta($post_id, '_reviews', true);
        $existing_reviews = $existing ? json_decode($existing, true) : [];
        $existing_ids = array_column($existing_reviews ?: [], 'id');
        
        foreach ($reviews as $review) {
            if (!in_array($review['id'], $existing_ids)) {
                $existing_reviews[] = $review;
            }
        }

        update_post_meta($post_id, '_reviews', wp_json_encode(array_slice($existing_reviews, 0, 20)));
        update_post_meta($post_id, '_review_count', count($existing_reviews));
    }

    return true;
}

// ============================================================
// Import Additional Data (Cast, Videos, Reviews)
// ============================================================
function mu_import_additional_data($post_id, $tmdb_id, $type, $details) {
    // Full cast with images
    mu_import_full_cast($post_id, $details, $tmdb_id, $type);
    
    // All videos/clips
    mu_import_all_videos($post_id, $details);
    
    // Reviews
    mu_import_reviews($post_id, $tmdb_id, $type);
}

// ============================================================
// Core Import Logic
// ============================================================
function mu_importer_process_item($data, $type) {
    global $wpdb;

    if (empty($data) || empty($data['id'])) {
        return new WP_Error('invalid_data', 'Invalid TMDB data received.');
    }

    $post_type = ($type === 'tv') ? 'tv_show' : 'movie';
    $title = ($type === 'tv') ? ($data['name'] ?? '') : ($data['title'] ?? '');
    
    if (empty($title)) {
        return new WP_Error('no_title', 'Could not determine title from TMDB data.');
    }
    
    $original_title = ($type === 'tv') ? ($data['original_name'] ?? '') : ($data['original_title'] ?? '');
    $release_date = ($type === 'tv') ? ($data['first_air_date'] ?? '') : ($data['release_date'] ?? '');
    $release_year = $release_date && preg_match('/^\d{4}/', $release_date) ? substr($release_date, 0, 4) : '';
    
    // Check duplicates
    $existing_id = $wpdb->get_var($wpdb->prepare("
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_tmdb_id' AND meta_value = %s LIMIT 1
    ", $data['id']));

    $action = 'created';
    if ($existing_id) {
        $post_id = (int) $existing_id;
        $action = 'updated';
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $title,
            'post_content' => $data['overview'] ?? ''
        ]);
    } else {
        $post_id = wp_insert_post([
            'post_type' => $post_type,
            'post_title' => $title,
            'post_content' => $data['overview'] ?? '',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ]);
    }

    if (is_wp_error($post_id) || !$post_id) {
        return new WP_Error('insert_failed', 'Failed to insert post into database.');
    }

    // === META DATA ===
    update_post_meta($post_id, '_tmdb_id', $data['id']);
    
    if (!empty($data['external_ids']['imdb_id'])) {
        update_post_meta($post_id, '_imdb_id', $data['external_ids']['imdb_id']);
    }
    if (!empty($data['external_ids']['facebook_id'])) {
        update_post_meta($post_id, '_facebook_id', $data['external_ids']['facebook_id']);
    }
    if (!empty($data['external_ids']['instagram_id'])) {
        update_post_meta($post_id, '_instagram_id', $data['external_ids']['instagram_id']);
    }
    if (!empty($data['external_ids']['twitter_id'])) {
        update_post_meta($post_id, '_twitter_id', $data['external_ids']['twitter_id']);
    }
    
    update_post_meta($post_id, '_original_title', $original_title);
    update_post_meta($post_id, '_release_year', $release_year);
    update_post_meta($post_id, '_release_date', $release_date);
    update_post_meta($post_id, '_rating', number_format((float)($data['vote_average'] ?? 0), 1));
    update_post_meta($post_id, '_vote_count', (int)($data['vote_count'] ?? 0));
    update_post_meta($post_id, '_popularity', (float)($data['popularity'] ?? 0));
    update_post_meta($post_id, '_tmdb_rating', number_format((float)($data['vote_average'] ?? 0), 1));
    
    // Adult content flag
    update_post_meta($post_id, '_adult', (bool)($data['adult'] ?? false));
    
    // Backeddrop and poster paths for dynamic loading
    if (!empty($data['backdrop_path'])) {
        update_post_meta($post_id, '_backdrop_path', $data['backdrop_path']);
    }
    if (!empty($data['poster_path'])) {
        update_post_meta($post_id, '_poster_path', $data['poster_path']);
    }
    
    // Overview
    if (!empty($data['overview'])) {
        update_post_meta($post_id, '_overview', wp_strip_all_tags($data['overview']));
    }
    
    // Tagline
    if (!empty($data['tagline'])) {
        update_post_meta($post_id, '_tagline', $data['tagline']);
    }

    // Type-specific
    if ($type === 'tv') {
        update_post_meta($post_id, '_seasons', (int)($data['number_of_seasons'] ?? 0));
        update_post_meta($post_id, '_episodes', (int)($data['number_of_episodes'] ?? 0));
        update_post_meta($post_id, '_status', $data['status'] ?? '');
        update_post_meta($post_id, '_type', $data['type'] ?? 'Scripted');
        update_post_meta($post_id, '_last_air_date', $data['last_air_date'] ?? '');
        update_post_meta($post_id, '_next_episode', $data['next_episode_to_air']['air_date'] ?? '');
    } else {
        update_post_meta($post_id, '_duration', (int)($data['runtime'] ?? 0));
        update_post_meta($post_id, '_budget', (int)($data['budget'] ?? 0));
        update_post_meta($post_id, '_revenue', (int)($data['revenue'] ?? 0));
    }

    if (!empty($data['homepage'])) {
        update_post_meta($post_id, '_homepage', $data['homepage']);
    }
    
    // IMDb ID
    if (!empty($data['imdb_id'])) {
        update_post_meta($post_id, '_imdb_id', $data['imdb_id']);
    }

    // === TRAILERS & VIDEOS ===
    if (!empty($data['videos']['results'])) {
        $videos = [];
        $trailer_set = false;
        
        foreach ($data['videos']['results'] as $vid) {
            if ($vid['site'] !== 'YouTube') continue;
            
            $video_info = [
                'key'    => $vid['key'],
                'name'   => $vid['name'] ?? '',
                'type'   => $vid['type'] ?? 'Clip',
                'site'   => 'YouTube',
                'url'    => 'https://www.youtube.com/watch?v=' . $vid['key'],
                'thumb'  => 'https://img.youtube.com/vi/' . $vid['key'] . '/hqdefault.jpg',
            ];
            
            $videos[] = $video_info;
            
            // Set primary trailer
            if (!$trailer_set && strtolower($vid['type'] ?? '') === 'trailer') {
                update_post_meta($post_id, '_trailer_url', $video_info['url']);
                update_post_meta($post_id, '_trailer_key', $vid['key']);
                update_post_meta($post_id, '_trailer_name', $vid['name'] ?? 'Trailer');
                $trailer_set = true;
            }
        }
        
        if (!empty($videos)) {
            update_post_meta($post_id, '_videos', wp_json_encode($videos));
            update_post_meta($post_id, '_video_count', count($videos));
        }
    }

    // === TAXONOMIES ===
    if (!empty($data['genres'])) {
        $genre_names = wp_list_pluck($data['genres'], 'name');
        wp_set_object_terms($post_id, $genre_names, 'genre', false);
    }
    
    if (!empty($data['production_countries'])) {
        $country_names = wp_list_pluck($data['production_countries'], 'name');
        wp_set_object_terms($post_id, $country_names, 'country', false);
    }

    if (!empty($data['production_companies'])) {
        $company_names = wp_list_pluck($data['production_companies'], 'name');
        wp_set_object_terms($post_id, $company_names, 'studio', false);
    }
    
    if (!empty($data['spoken_languages'])) {
        $lang_names = wp_list_pluck($data['spoken_languages'], 'english_name');
        $lang_names = array_filter($lang_names);
        if (!empty($lang_names)) {
            wp_set_object_terms($post_id, $lang_names, 'language', false);
        }
    }

    // === IMAGES ===
    $existing_poster = get_post_thumbnail_id($post_id);
    if (!$existing_poster && !empty($data['poster_path'])) {
        $attach_id = mu_importer_download_image($data['poster_path'], $post_id, $title . ' Poster');
        if ($attach_id && !is_wp_error($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
        }
    }

    $existing_backdrop = get_post_meta($post_id, '_backdrop_id', true);
    if (!$existing_backdrop && !empty($data['backdrop_path'])) {
        $attach_id = mu_importer_download_image($data['backdrop_path'], $post_id, $title . ' Backdrop');
        if ($attach_id && !is_wp_error($attach_id)) {
            update_post_meta($post_id, '_backdrop_id', $attach_id);
            update_post_meta($post_id, '_backdrop_url', wp_get_attachment_url($attach_id));
        }
    }

    // Ensure _view_count exists
    if (!get_post_meta($post_id, '_view_count', true)) {
        update_post_meta($post_id, '_view_count', 0);
    }

    return [
        'post_id' => $post_id,
        'title'   => $title,
        'action'  => $action
    ];
}

// ============================================================
// Download Image Helper
// ============================================================
if (!function_exists('mu_importer_download_image')) {
    function mu_importer_download_image($tmdb_path, $post_id, $desc = '', $is_profile = false) {
        if (empty($tmdb_path)) return false;

        // Determine size based on type
        $size = $is_profile ? 'w185' : 'original';
        $image_url = 'https://image.tmdb.org/t/p/' . $size . $tmdb_path;

        $attachment_id = media_sideload_image($image_url, $post_id, $desc, 'id');

        if (is_wp_error($attachment_id)) {
            return false;
        }

        return $attachment_id;
    }
}
