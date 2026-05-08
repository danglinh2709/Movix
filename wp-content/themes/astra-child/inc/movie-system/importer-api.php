<?php
/**
 * TMDB API Communicator
 */

if (!defined('ABSPATH')) exit;

/**
 * Make a request to TMDB API
 */
function mu_api_tmdb_request($endpoint, $params = []) {
    $api_key = get_option('mu_tmdb_api_key');
    if (empty($api_key)) {
        return new WP_Error('no_api_key', 'TMDB API key is missing. Please configure it in the Movie Importer settings.');
    }

    $base_url = 'https://api.themoviedb.org/3/';
    $url = $base_url . ltrim($endpoint, '/');
    
    $params['api_key'] = $api_key;
    $params['language'] = 'en-US'; // Default language

    $url = add_query_arg($params, $url);

    $response = wp_remote_get($url, [
        'timeout' => 15,
        'sslverify' => false
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code !== 200) {
        $msg = isset($data['status_message']) ? $data['status_message'] : 'TMDB API error (' . $status_code . ')';
        return new WP_Error('api_error', $msg);
    }

    return $data;
}

/**
 * Fetch detailed item info including append_to_response
 */
function mu_api_tmdb_get_details($tmdb_id, $type = 'movie') {
    $endpoint = ($type === 'tv') ? "tv/{$tmdb_id}" : "movie/{$tmdb_id}";
    return mu_api_tmdb_request($endpoint, [
        'append_to_response' => 'credits,videos,external_ids'
    ]);
}

/**
 * Fetch bulk lists (popular, trending, etc)
 */
function mu_api_tmdb_get_list($category) {
    switch ($category) {
        case 'popular_movies':
            return mu_api_tmdb_request('movie/popular');
        case 'trending_movies':
            return mu_api_tmdb_request('trending/movie/day');
        case 'top_rated_movies':
            return mu_api_tmdb_request('movie/top_rated');
        case 'popular_tv':
            return mu_api_tmdb_request('tv/popular');
        default:
            return new WP_Error('invalid_category', 'Invalid bulk category selected.');
    }
}
