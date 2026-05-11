<?php
/**
 * TMDB API Communicator
 * Extended with more import categories and full data
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
    $params['language'] = 'en-US';

    $url = add_query_arg($params, $url);

    $response = wp_remote_get($url, [
        'timeout' => 20,
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
        'append_to_response' => 'credits,videos,external_ids,images,recommendations,reviews,similar'
    ]);
}

/**
 * Fetch cast details with full profile
 */
function mu_api_tmdb_get_credits($tmdb_id, $type = 'movie') {
    $endpoint = ($type === 'tv') ? "tv/{$tmdb_id}/credits" : "movie/{$tmdb_id}/credits";
    return mu_api_tmdb_request($endpoint);
}

/**
 * Fetch all videos (trailers, clips, teasers, behind the scenes)
 */
function mu_api_tmdb_get_videos($tmdb_id, $type = 'movie') {
    $endpoint = ($type === 'tv') ? "tv/{$tmdb_id}/videos" : "movie/{$tmdb_id}/videos";
    return mu_api_tmdb_request($endpoint);
}

/**
 * Fetch specific video types
 */
function mu_api_tmdb_get_clips($tmdb_id, $type = 'movie') {
    $videos = mu_api_tmdb_get_videos($tmdb_id, $type);
    if (is_wp_error($videos) || empty($videos['results'])) {
        return [];
    }
    
    $clips = [];
    foreach ($videos['results'] as $video) {
        if ($video['site'] !== 'YouTube') continue;
        
        $type_video = strtolower($video['type'] ?? 'clip');
        $clips[] = [
            'key'         => $video['key'],
            'name'        => $video['name'] ?? '',
            'site'        => 'YouTube',
            'type'        => $type_video,
            'official'    => (bool)($video['official'] ?? false),
            'published_at' => $video['published_at'] ?? '',
            'iso_639_1'   => $video['iso_639_1'] ?? '',
            'iso_3166_1'  => $video['iso_3166_1'] ?? '',
            'url'         => 'https://www.youtube.com/watch?v=' . $video['key'],
            'embed_url'   => 'https://www.youtube.com/embed/' . $video['key'],
            'thumbnail'    => 'https://img.youtube.com/vi/' . $video['key'] . '/hqdefault.jpg',
            'thumbnail_hd' => 'https://img.youtube.com/vi/' . $video['key'] . '/maxresdefault.jpg',
        ];
    }
    
    return $clips;
}

/**
 * Fetch reviews
 */
function mu_api_tmdb_get_reviews($tmdb_id, $type = 'movie', $page = 1) {
    $endpoint = ($type === 'tv') ? "tv/{$tmdb_id}/reviews" : "movie/{$tmdb_id}/reviews";
    return mu_api_tmdb_request($endpoint, ['page' => $page]);
}

/**
 * Fetch person details (for cast profile images)
 */
function mu_api_tmdb_get_person($person_id) {
    return mu_api_tmdb_request("person/{$person_id}");
}

/**
 * Fetch person images
 */
function mu_api_tmdb_get_person_images($person_id) {
    return mu_api_tmdb_request("person/{$person_id}/images");
}

/**
 * Fetch bulk lists (popular, trending, etc)
 */
function mu_api_tmdb_get_list($category, $page = 1) {
    $params = ['page' => $page];
    
    switch ($category) {
        // Movies
        case 'popular_movies':
            return mu_api_tmdb_request('movie/popular', $params);
        case 'trending_movies':
            return mu_api_tmdb_request('trending/movie/day', $params);
        case 'trending_movies_week':
            return mu_api_tmdb_request('trending/movie/week', $params);
        case 'top_rated_movies':
            return mu_api_tmdb_request('movie/top_rated', $params);
        case 'now_playing_movies':
            return mu_api_tmdb_request('movie/now_playing', $params);
        case 'upcoming_movies':
            return mu_api_tmdb_request('movie/upcoming', $params);
        case 'latest_movies':
            return mu_api_tmdb_request('movie/latest', $params);
        
        // TV Shows
        case 'popular_tv':
            return mu_api_tmdb_request('tv/popular', $params);
        case 'trending_tv':
            return mu_api_tmdb_request('trending/tv/day', $params);
        case 'trending_tv_week':
            return mu_api_tmdb_request('trending/tv/week', $params);
        case 'top_rated_tv':
            return mu_api_tmdb_request('tv/top_rated', $params);
        case 'airing_today_tv':
            return mu_api_tmdb_request('tv/airing_today', $params);
        case 'on_the_air_tv':
            return mu_api_tmdb_request('tv/on_the_air', $params);
        case 'latest_tv':
            return mu_api_tmdb_request('tv/latest', $params);
        
        // All (mixed)
        case 'trending_all':
            return mu_api_tmdb_request('trending/all/day', $params);
        case 'trending_all_week':
            return mu_api_tmdb_request('trending/all/week', $params);
        
        // Discovery
        case 'discover_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'sort_by' => 'popularity.desc',
                'vote_count.gte' => 100
            ]));
        case 'discover_tv':
            return mu_api_tmdb_request('discover/tv', array_merge($params, [
                'sort_by' => 'popularity.desc',
                'vote_count.gte' => 50
            ]));
        
        // Genre-based Movies
        case 'action_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 28,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 100
            ]));
        case 'comedy_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 35,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 100
            ]));
        case 'horror_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 27,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'romance_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 10749,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'scifi_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 878,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'animation_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 16,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'documentary_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 99,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 20
            ]));
        case 'thriller_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 53,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'family_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 10751,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'fantasy_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 14,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'war_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 10752,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'history_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_genres' => 36,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 30
            ]));
        
        // Genre-based TV
        case 'drama_tv':
            return mu_api_tmdb_request('discover/tv', array_merge($params, [
                'with_genres' => 18,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'comedy_tv':
            return mu_api_tmdb_request('discover/tv', array_merge($params, [
                'with_genres' => 35,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'crime_tv':
            return mu_api_tmdb_request('discover/tv', array_merge($params, [
                'with_genres' => 80,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 50
            ]));
        case 'sci_fi_tv':
            return mu_api_tmdb_request('discover/tv', array_merge($params, [
                'with_genres' => 10765,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 30
            ]));
        case 'animation_tv':
            return mu_api_tmdb_request('discover/tv', array_merge($params, [
                'with_genres' => 16,
                'sort_by' => 'vote_average.desc',
                'vote_count.gte' => 30
            ]));
        
        // Year-based
        case '2024_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'primary_release_year' => 2024,
                'sort_by' => 'popularity.desc'
            ]));
        case '2023_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'primary_release_year' => 2023,
                'sort_by' => 'popularity.desc'
            ]));
        case '2022_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'primary_release_year' => 2022,
                'sort_by' => 'popularity.desc'
            ]));
        case '2021_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'primary_release_year' => 2021,
                'sort_by' => 'popularity.desc'
            ]));
        
        // Studio-based
        case 'marvel_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_companies' => 420,
                'sort_by' => 'release_date.desc'
            ]));
        case 'dc_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_companies' => 9993,
                'sort_by' => 'release_date.desc'
            ]));
        case 'pixar_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_companies' => 3,
                'sort_by' => 'release_date.desc'
            ]));
        case 'disney_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_companies' => 2,
                'sort_by' => 'release_date.desc'
            ]));
        case 'dreamworks_movies':
            return mu_api_tmdb_request('discover/movie', array_merge($params, [
                'with_companies' => 521,
                'sort_by' => 'release_date.desc'
            ]));
        
        default:
            return new WP_Error('invalid_category', 'Invalid bulk category selected.');
    }
}

/**
 * Get category info
 */
function mu_api_get_category_info($category) {
    $categories = [
        'popular_movies'       => ['label' => 'Popular Movies', 'type' => 'movie', 'icon' => '🔥'],
        'trending_movies'      => ['label' => 'Trending Movies (Today)', 'type' => 'movie', 'icon' => '📈'],
        'trending_movies_week' => ['label' => 'Trending Movies (Week)', 'type' => 'movie', 'icon' => '📊'],
        'top_rated_movies'     => ['label' => 'Top Rated Movies', 'type' => 'movie', 'icon' => '⭐'],
        'now_playing_movies'   => ['label' => 'Now Playing Movies', 'type' => 'movie', 'icon' => '🎬'],
        'upcoming_movies'      => ['label' => 'Upcoming Movies', 'type' => 'movie', 'icon' => '🚀'],
        'latest_movies'        => ['label' => 'Latest Movies', 'type' => 'movie', 'icon' => '🆕'],
        'popular_tv'          => ['label' => 'Popular TV Shows', 'type' => 'tv', 'icon' => '📺'],
        'trending_tv'          => ['label' => 'Trending TV (Today)', 'type' => 'tv', 'icon' => '📈'],
        'trending_tv_week'     => ['label' => 'Trending TV (Week)', 'type' => 'tv', 'icon' => '📊'],
        'top_rated_tv'         => ['label' => 'Top Rated TV Shows', 'type' => 'tv', 'icon' => '⭐'],
        'airing_today_tv'      => ['label' => 'Airing Today TV', 'type' => 'tv', 'icon' => '📅'],
        'on_the_air_tv'         => ['label' => 'On The Air TV', 'type' => 'tv', 'icon' => '📡'],
        'latest_tv'            => ['label' => 'Latest TV Shows', 'type' => 'tv', 'icon' => '🆕'],
        'trending_all'         => ['label' => 'Trending All (Today)', 'type' => 'all', 'icon' => '🌟'],
        'trending_all_week'    => ['label' => 'Trending All (Week)', 'type' => 'all', 'icon' => '✨'],
        'discover_movies'      => ['label' => 'Discover Movies', 'type' => 'movie', 'icon' => '🔍'],
        'discover_tv'          => ['label' => 'Discover TV Shows', 'type' => 'tv', 'icon' => '🔍'],
        'action_movies'        => ['label' => 'Action Movies', 'type' => 'movie', 'icon' => '💪'],
        'comedy_movies'        => ['label' => 'Comedy Movies', 'type' => 'movie', 'icon' => '😂'],
        'horror_movies'        => ['label' => 'Horror Movies', 'type' => 'movie', 'icon' => '👻'],
        'romance_movies'       => ['label' => 'Romance Movies', 'type' => 'movie', 'icon' => '💕'],
        'scifi_movies'         => ['label' => 'Sci-Fi Movies', 'type' => 'movie', 'icon' => '🚀'],
        'animation_movies'     => ['label' => 'Animation Movies', 'type' => 'movie', 'icon' => '🎨'],
        'documentary_movies'   => ['label' => 'Documentary Movies', 'type' => 'movie', 'icon' => '📽️'],
        'thriller_movies'      => ['label' => 'Thriller Movies', 'type' => 'movie', 'icon' => '😱'],
        'family_movies'        => ['label' => 'Family Movies', 'type' => 'movie', 'icon' => '👨‍👩‍👧'],
        'fantasy_movies'       => ['label' => 'Fantasy Movies', 'type' => 'movie', 'icon' => '🧙'],
        'war_movies'           => ['label' => 'War Movies', 'type' => 'movie', 'icon' => '🎖️'],
        'history_movies'       => ['label' => 'History Movies', 'type' => 'movie', 'icon' => '📜'],
        'drama_tv'             => ['label' => 'Drama TV Shows', 'type' => 'tv', 'icon' => '🎭'],
        'comedy_tv'            => ['label' => 'Comedy TV Shows', 'type' => 'tv', 'icon' => '😂'],
        'crime_tv'             => ['label' => 'Crime TV Shows', 'type' => 'tv', 'icon' => '🔍'],
        'sci_fi_tv'            => ['label' => 'Sci-Fi & Fantasy TV', 'type' => 'tv', 'icon' => '🚀'],
        'animation_tv'         => ['label' => 'Animation TV Shows', 'type' => 'tv', 'icon' => '🎨'],
        '2024_movies'          => ['label' => 'Movies 2024', 'type' => 'movie', 'icon' => '📆'],
        '2023_movies'          => ['label' => 'Movies 2023', 'type' => 'movie', 'icon' => '📆'],
        '2022_movies'          => ['label' => 'Movies 2022', 'type' => 'movie', 'icon' => '📆'],
        '2021_movies'          => ['label' => 'Movies 2021', 'type' => 'movie', 'icon' => '📆'],
        'marvel_movies'        => ['label' => 'Marvel Movies', 'type' => 'movie', 'icon' => '🦸'],
        'dc_movies'            => ['label' => 'DC Movies', 'type' => 'movie', 'icon' => '🦇'],
        'pixar_movies'         => ['label' => 'Pixar Movies', 'type' => 'movie', 'icon' => '🔴'],
        'disney_movies'        => ['label' => 'Disney Movies', 'type' => 'movie', 'icon' => '🏰'],
        'dreamworks_movies'    => ['label' => 'DreamWorks Movies', 'type' => 'movie', 'icon' => '🌙'],
    ];
    
    return $categories[$category] ?? ['label' => ucfirst(str_replace('_', ' ', $category)), 'type' => 'all', 'icon' => '📦'];
}

/**
 * Get clips for a post - from local storage or fetch from TMDB
 */
function mu_get_clips_for_post($post_id, $limit = 10) {
    // Try local storage first
    $clips_json = get_post_meta($post_id, '_clips', true);
    $all_videos = get_post_meta($post_id, '_all_videos', true);
    $trailers = get_post_meta($post_id, '_trailers', true);
    
    $clips = [];
    
    // Combine all video sources
    if ($all_videos) {
        $data = json_decode($all_videos, true);
        if (is_array($data)) {
            $clips = $data;
        }
    }
    
    if ($trailers) {
        $trailer_data = json_decode($trailers, true);
        if (is_array($trailer_data)) {
            // Add trailers that aren't already in clips
            $existing_keys = array_column($clips, 'key');
            foreach ($trailer_data as $t) {
                if (!in_array($t['key'], $existing_keys)) {
                    $clips[] = $t;
                }
            }
        }
    }
    
    // If no local clips, try fetching from TMDB
    if (empty($clips)) {
        $tmdb_id = get_post_meta($post_id, '_tmdb_id', true);
        if ($tmdb_id) {
            $type = get_post_type($post_id) === 'tv_show' ? 'tv' : 'movie';
            $videos = mu_api_tmdb_get_videos($tmdb_id, $type);
            
            if (!is_wp_error($videos) && !empty($videos['results'])) {
                foreach ($videos['results'] as $v) {
                    if ($v['site'] !== 'YouTube') continue;
                    $clips[] = [
                        'key'          => $v['key'],
                        'name'         => $v['name'] ?? '',
                        'type'         => strtolower($v['type'] ?? 'clip'),
                        'site'         => 'YouTube',
                        'official'     => (bool)($v['official'] ?? false),
                        'url'          => 'https://www.youtube.com/watch?v=' . $v['key'],
                        'embed_url'    => 'https://www.youtube.com/embed/' . $v['key'],
                        'thumbnail'    => 'https://img.youtube.com/vi/' . $v['key'] . '/hqdefault.jpg',
                        'thumbnail_hd' => 'https://img.youtube.com/vi/' . $v['key'] . '/maxresdefault.jpg',
                    ];
                }
                
                // Cache locally for next time
                if (!empty($clips)) {
                    update_post_meta($post_id, '_all_videos', wp_json_encode($clips));
                }
            }
        }
    }
    
    return array_slice($clips, 0, $limit);
}
