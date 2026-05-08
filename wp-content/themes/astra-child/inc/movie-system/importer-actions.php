<?php
/**
 * AJAX Actions and Core Import Logic
 */

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_mu_import_item', 'mu_ajax_import_item');
function mu_ajax_import_item() {
    check_ajax_referer('mu_import_nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $tmdb_id = isset($_POST['tmdb_id']) ? intval($_POST['tmdb_id']) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'movie'; // 'movie' or 'tv'

    if (!$tmdb_id) wp_send_json_error('Invalid TMDB ID');

    $tmdb_data = mu_api_tmdb_get_details($tmdb_id, $type);
    if (is_wp_error($tmdb_data)) {
        wp_send_json_error($tmdb_data->get_error_message());
    }

    $result = mu_importer_process_item($tmdb_data, $type);
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'message' => 'Imported ' . $result['title'] . ' (' . $result['action'] . ')',
        'post_id' => $result['post_id']
    ]);
}

add_action('wp_ajax_mu_import_bulk', 'mu_ajax_import_bulk');
function mu_ajax_import_bulk() {
    check_ajax_referer('mu_import_nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;

    $tmdb_data = mu_api_tmdb_get_list($category);
    if (is_wp_error($tmdb_data)) {
        wp_send_json_error($tmdb_data->get_error_message());
    }

    if (empty($tmdb_data['results'])) {
        wp_send_json_error('No results found from TMDB.');
    }

    $items = [];
    $count = 0;
    foreach ($tmdb_data['results'] as $res) {
        if ($count >= $limit) break;
        $t = isset($res['media_type']) ? $res['media_type'] : (strpos($category, 'tv') !== false ? 'tv' : 'movie');
        $items[] = [
            'id' => $res['id'],
            'type' => $t,
            'title' => isset($res['title']) ? $res['title'] : (isset($res['name']) ? $res['name'] : 'Unknown')
        ];
        $count++;
    }

    wp_send_json_success(['items' => $items]);
}

/**
 * Process a single item from TMDB API data
 */
function mu_importer_process_item($data, $type) {
    global $wpdb;

    $post_type = ($type === 'tv') ? 'tv_show' : 'movie';
    $title = ($type === 'tv') ? $data['name'] : $data['title'];
    $original_title = ($type === 'tv') ? $data['original_name'] : $data['original_title'];
    $release_date = ($type === 'tv') ? $data['first_air_date'] : $data['release_date'];
    $release_year = $release_date ? substr($release_date, 0, 4) : '';
    
    // Check duplicates by meta tmdb_id
    $existing_id = $wpdb->get_var($wpdb->prepare("
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_tmdb_id' AND meta_value = %s LIMIT 1
    ", $data['id']));

    $action = 'created';
    if ($existing_id) {
        $post_id = $existing_id;
        $action = 'updated';
        // Update post basic info
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $title,
            'post_content' => $data['overview']
        ]);
    } else {
        // Create new
        $post_id = wp_insert_post([
            'post_type' => $post_type,
            'post_title' => $title,
            'post_content' => $data['overview'],
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ]);
    }

    if (is_wp_error($post_id) || !$post_id) {
        return new WP_Error('insert_failed', 'Failed to insert post into database.');
    }

    // --- META DATA ---
    update_post_meta($post_id, '_tmdb_id', $data['id']);
    if (isset($data['external_ids']['imdb_id'])) {
        update_post_meta($post_id, '_imdb_id', $data['external_ids']['imdb_id']);
    }
    update_post_meta($post_id, '_original_title', $original_title);
    update_post_meta($post_id, '_release_year', $release_year);
    update_post_meta($post_id, '_release_date', $release_date);
    update_post_meta($post_id, '_rating', number_format($data['vote_average'], 1));
    update_post_meta($post_id, '_popularity', $data['popularity']);
    
    if ($type === 'tv') {
        update_post_meta($post_id, '_seasons', $data['number_of_seasons']);
        update_post_meta($post_id, '_episodes', $data['number_of_episodes']);
    } else {
        update_post_meta($post_id, '_duration', $data['runtime']);
    }

    // Trailers
    if (!empty($data['videos']['results'])) {
        foreach ($data['videos']['results'] as $vid) {
            if ($vid['site'] === 'YouTube' && $vid['type'] === 'Trailer') {
                update_post_meta($post_id, '_trailer_url', 'https://www.youtube.com/watch?v=' . $vid['key']);
                break;
            }
        }
    }

    // Credits (Actors, Directors) — save rich JSON
    if (!empty($data['credits'])) {
        if (!empty($data['credits']['cast'])) {
            $cast_raw = array_slice($data['credits']['cast'], 0, 15);
            $cast_json = [];
            $actor_names = [];
            foreach ($cast_raw as $c) {
                $cast_json[] = [
                    'name'          => sanitize_text_field($c['name']),
                    'character'     => sanitize_text_field($c['character'] ?? ''),
                    'profile_path'  => sanitize_text_field($c['profile_path'] ?? ''),
                    'order'         => intval($c['order'] ?? 0),
                    'tmdb_person_id'=> intval($c['id'] ?? 0),
                ];
                $actor_names[] = sanitize_text_field($c['name']);
            }
            update_post_meta($post_id, '_cast', wp_json_encode($cast_json));
            // Also keep plain text for search compatibility
            update_post_meta($post_id, '_actors', implode(', ', array_slice($actor_names, 0, 5)));
            // Assign actor taxonomy
            if ($actor_names) {
                wp_set_object_terms($post_id, array_slice($actor_names, 0, 10), 'actor', false);
            }
        }
        if (!empty($data['credits']['crew'])) {
            $directors = [];
            $director_names = [];
            foreach ($data['credits']['crew'] as $crew) {
                if ($crew['job'] === 'Director') {
                    $directors[] = [
                        'name'          => sanitize_text_field($crew['name']),
                        'job'           => 'Director',
                        'department'    => sanitize_text_field($crew['department'] ?? 'Directing'),
                        'profile_path'  => sanitize_text_field($crew['profile_path'] ?? ''),
                        'tmdb_person_id'=> intval($crew['id'] ?? 0),
                    ];
                    $director_names[] = sanitize_text_field($crew['name']);
                }
            }
            if ($directors) {
                update_post_meta($post_id, '_crew', wp_json_encode($directors));
                update_post_meta($post_id, '_director', implode(', ', $director_names));
                // Assign director taxonomy
                wp_set_object_terms($post_id, $director_names, 'director', false);
            }
        }
    }

    // --- TAXONOMIES ---
    if (!empty($data['genres'])) {
        $genre_names = wp_list_pluck($data['genres'], 'name');
        wp_set_object_terms($post_id, $genre_names, 'genre', false);
    }
    if (!empty($data['production_countries'])) {
        $country_names = wp_list_pluck($data['production_countries'], 'name');
        wp_set_object_terms($post_id, $country_names, 'country', false);
    }

    // --- IMAGES ---
    // Only download if we don't have them yet
    $existing_poster = get_post_thumbnail_id($post_id);
    if (!$existing_poster && !empty($data['poster_path'])) {
        $attach_id = mu_importer_download_image($data['poster_path'], $post_id, $title . ' Poster');
        if ($attach_id) {
            set_post_thumbnail($post_id, $attach_id);
        }
    }

    $existing_backdrop = get_post_meta($post_id, '_backdrop_id', true);
    if (!$existing_backdrop && !empty($data['backdrop_path'])) {
        $attach_id = mu_importer_download_image($data['backdrop_path'], $post_id, $title . ' Backdrop');
        if ($attach_id) {
            update_post_meta($post_id, '_backdrop_id', $attach_id);
            update_post_meta($post_id, '_backdrop_url', wp_get_attachment_url($attach_id));
        }
    }

    // Ensure _view_count exists for sorting
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
// BACKFILL: Update Cast & Crew for existing imported movies
// ============================================================
add_action('wp_ajax_mu_backfill_cast', 'mu_ajax_backfill_cast');
function mu_ajax_backfill_cast() {
    check_ajax_referer('mu_import_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $batch  = 5; // Process 5 per request to avoid timeout

    $posts = get_posts([
        'post_type'      => ['movie', 'tv_show'],
        'posts_per_page' => $batch,
        'offset'         => $offset,
        'post_status'    => 'publish',
        'meta_key'       => '_tmdb_id',
        'fields'         => 'ids',
    ]);

    if (empty($posts)) {
        wp_send_json_success(['done' => true, 'message' => 'All movies updated.']);
    }

    $updated = 0;
    $failed  = 0;
    $log     = [];

    foreach ($posts as $post_id) {
        $tmdb_id   = get_post_meta($post_id, '_tmdb_id', true);
        $post_type = get_post_type($post_id);
        $tmdb_type = ($post_type === 'tv_show') ? 'tv' : 'movie';

        if (!$tmdb_id) { $log[] = "#{$post_id}: no tmdb_id, skipped"; continue; }

        // Fetch credits from TMDB
        $api_key = get_option('mu_tmdb_api_key', '');
        if (!$api_key) { wp_send_json_error('TMDB API key not configured.'); }

        $endpoint = "https://api.themoviedb.org/3/{$tmdb_type}/{$tmdb_id}/credits?api_key={$api_key}&language=en-US";
        $response = wp_remote_get($endpoint, ['timeout' => 15]);

        if (is_wp_error($response)) { $failed++; $log[] = "#{$post_id}: HTTP error"; continue; }

        $credits = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($credits)) { $failed++; $log[] = "#{$post_id}: empty response"; continue; }

        // Save cast
        if (!empty($credits['cast'])) {
            $cast_raw = array_slice($credits['cast'], 0, 15);
            $cast_json = [];
            $actor_names = [];
            foreach ($cast_raw as $c) {
                $cast_json[] = [
                    'name'          => sanitize_text_field($c['name']),
                    'character'     => sanitize_text_field($c['character'] ?? ''),
                    'profile_path'  => sanitize_text_field($c['profile_path'] ?? ''),
                    'order'         => intval($c['order'] ?? 0),
                    'tmdb_person_id'=> intval($c['id'] ?? 0),
                ];
                $actor_names[] = sanitize_text_field($c['name']);
            }
            update_post_meta($post_id, '_cast', wp_json_encode($cast_json));
            update_post_meta($post_id, '_actors', implode(', ', array_slice($actor_names, 0, 5)));
            if ($actor_names) wp_set_object_terms($post_id, array_slice($actor_names, 0, 10), 'actor', false);
        }

        // Save crew/directors
        if (!empty($credits['crew'])) {
            $directors = [];
            $director_names = [];
            foreach ($credits['crew'] as $crew) {
                if ($crew['job'] === 'Director') {
                    $directors[] = [
                        'name'          => sanitize_text_field($crew['name']),
                        'job'           => 'Director',
                        'department'    => sanitize_text_field($crew['department'] ?? 'Directing'),
                        'profile_path'  => sanitize_text_field($crew['profile_path'] ?? ''),
                        'tmdb_person_id'=> intval($crew['id'] ?? 0),
                    ];
                    $director_names[] = sanitize_text_field($crew['name']);
                }
            }
            if ($directors) {
                update_post_meta($post_id, '_crew', wp_json_encode($directors));
                update_post_meta($post_id, '_director', implode(', ', $director_names));
                wp_set_object_terms($post_id, $director_names, 'director', false);
            }
        }

        $updated++;
        $log[] = "#{$post_id} " . get_the_title($post_id) . ": updated";
        usleep(300000); // 0.3s delay to respect TMDB rate limits
    }

    wp_send_json_success([
        'done'    => false,
        'updated' => $updated,
        'failed'  => $failed,
        'next_offset' => $offset + $batch,
        'log'     => $log,
    ]);
}
