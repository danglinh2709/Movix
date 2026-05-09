<?php
/**
 * Template Name: Watch History (Premium OTT)
 * Premium cinematic watch history page
 */
get_header();
get_template_part('template-parts/streaming/header');

// ============================================================
// GET USER HISTORY DATA
// ============================================================
$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

// Get history data for JavaScript
$history_data = [];

// Database history (for logged-in users)
$db_history = [];
if ($is_logged_in) {
    global $wpdb;
    $table = $wpdb->prefix . 'movie_progress';
    
    // Check if table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table) === $table)) {
        $db_history = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, current_time, duration, progress_percent, updated_at 
             FROM {$table} 
             WHERE user_id = %d 
             ORDER BY updated_at DESC 
             LIMIT 200",
            $user_id
        ));
        
        // Process DB history
        foreach ($db_history as $row) {
            $post_id = (int) $row->post_id;
            if (get_post_status($post_id) !== 'publish') continue;
            
            $post_type = get_post_type($post_id);
            $title = get_the_title($post_id);
            $thumb = get_the_post_thumbnail_url($post_id, 'medium') ?: '';
            $year = get_post_meta($post_id, '_release_year', true) ?: '';
            $runtime = get_post_meta($post_id, '_duration', true) ?: '';
            
            // Get genres
            $genres = [];
            $terms = get_the_terms($post_id, 'genre');
            if ($terms && !is_wp_error($terms)) {
                $genres = array_map(function($t) { return $t->name; }, $terms);
            }
            
            $watch_url = home_url('/watch/?movie_id=' . $post_id);
            if ($post_type === 'episode') {
                $watch_url = home_url('/watch/?episode_id=' . $post_id);
            }
            
            $history_data[$post_id] = [
                'id' => $post_id,
                'title' => $title,
                'type' => $post_type,
                'thumb' => $thumb,
                'year' => $year,
                'runtime' => $runtime,
                'genres' => array_slice($genres, 0, 3),
                'watchUrl' => $watch_url,
                'detailUrl' => get_permalink($post_id),
                'progress' => [
                    'currentTime' => (int) $row->current_time,
                    'duration' => (int) $row->duration,
                    'percent' => (int) $row->progress_percent,
                    'updatedAt' => strtotime($row->updated_at) * 1000
                ]
            ];
        }
    }
}

// URL helpers
$movies_url = home_url('/movies/');
$tv_url = home_url('/tv/');
$trending_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('trending') : home_url('/trending/');
$watch_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('watch') : home_url('/watch/');

// Nonce for AJAX
$history_nonce = wp_create_nonce('mu_history_nonce');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Watch History', 'astra-child'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="movie-ui movie-ui--no-sidebar mu-history-page">
<?php wp_body_open(); ?>

<div class="mu-history-container">
    <!-- ============================================================ -->
    <!-- SIDEBAR FILTERS -->
    <!-- ============================================================ -->
    <aside class="mu-history-sidebar">
        <h3 class="mu-history-filter-title"><?php esc_html_e('Filters', 'astra-child'); ?></h3>
        <ul class="mu-history-filter-list">
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link is-active" data-filter="all">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?php esc_html_e('All History', 'astra-child'); ?>
                    </span>
                    <span class="mu-history-filter-count" data-count="all">0</span>
                </a>
            </li>
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link" data-filter="movies">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/>
                            <line x1="7" y1="2" x2="7" y2="22"/>
                            <line x1="17" y1="2" x2="17" y2="22"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <line x1="2" y1="7" x2="7" y2="7"/>
                            <line x1="2" y1="17" x2="7" y2="17"/>
                            <line x1="17" y1="17" x2="22" y2="17"/>
                            <line x1="17" y1="7" x2="22" y2="7"/>
                        </svg>
                        <?php esc_html_e('Movies', 'astra-child'); ?>
                    </span>
                    <span class="mu-history-filter-count" data-count="movies">0</span>
                </a>
            </li>
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link" data-filter="tv">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="15" rx="2" ry="2"/>
                            <polyline points="17 2 12 7 7 2"/>
                        </svg>
                        <?php esc_html_e('TV Shows', 'astra-child'); ?>
                    </span>
                    <span class="mu-history-filter-count" data-count="tv">0</span>
                </a>
            </li>
        </ul>

        <div class="mu-history-filter-divider"></div>

        <h3 class="mu-history-filter-title"><?php esc_html_e('Time Period', 'astra-child'); ?></h3>
        <ul class="mu-history-filter-list">
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link" data-filter="today">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                        <?php esc_html_e('Today', 'astra-child'); ?>
                    </span>
                    <span class="mu-history-filter-count" data-count="today">0</span>
                </a>
            </li>
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link" data-filter="yesterday">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6"/>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                        </svg>
                        <?php esc_html_e('Yesterday', 'astra-child'); ?>
                    </span>
                    <span class="mu-history-filter-count" data-count="yesterday">0</span>
                </a>
            </li>
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link" data-filter="last7days">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <?php esc_html_e('Last 7 Days', 'astra-child'); ?>
                    </span>
                </a>
            </li>
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link" data-filter="last30days">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <?php esc_html_e('Last 30 Days', 'astra-child'); ?>
                    </span>
                </a>
            </li>
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link" data-filter="older">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
                        </svg>
                        <?php esc_html_e('Older', 'astra-child'); ?>
                    </span>
                </a>
            </li>
        </ul>

        <div class="mu-history-filter-divider"></div>

        <ul class="mu-history-filter-list">
            <li class="mu-history-filter-item">
                <a href="#" class="mu-history-filter-link" data-action="settings">
                    <span class="mu-history-filter-text">
                        <svg class="mu-history-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <?php esc_html_e('Settings', 'astra-child'); ?>
                    </span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- ============================================================ -->
    <!-- MAIN CONTENT -->
    <!-- ============================================================ -->
    <main class="mu-history-content">
        <!-- Page Header -->
        <div class="mu-history-header">
            <div class="mu-history-header-info">
                <h1 class="mu-history-title"><?php esc_html_e('Watch History', 'astra-child'); ?></h1>
                <p class="mu-history-subtitle"><?php esc_html_e('Keep track of what you\'ve watched. Clear individual titles or your entire history anytime.', 'astra-child'); ?></p>
            </div>
            <div class="mu-history-actions">
                <button type="button" id="mu-history-clear-all" class="mu-history-btn mu-history-btn--clear">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    </svg>
                    <?php esc_html_e('Clear All History', 'astra-child'); ?>
                </button>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="mu-history-stats">
            <div class="mu-history-stat">
                <span class="mu-history-stat-value" id="mu-stat-total">0</span>
                <span class="mu-history-stat-label"><?php esc_html_e('Total Watched', 'astra-child'); ?></span>
            </div>
            <div class="mu-history-stat">
                <span class="mu-history-stat-value" id="mu-stat-movies">0</span>
                <span class="mu-history-stat-label"><?php esc_html_e('Movies', 'astra-child'); ?></span>
            </div>
            <div class="mu-history-stat">
                <span class="mu-history-stat-value" id="mu-stat-tv">0</span>
                <span class="mu-history-stat-label"><?php esc_html_e('TV Shows', 'astra-child'); ?></span>
            </div>
            <div class="mu-history-stat">
                <span class="mu-history-stat-value" id="mu-stat-completed">0</span>
                <span class="mu-history-stat-label"><?php esc_html_e('Completed', 'astra-child'); ?></span>
            </div>
        </div>

        <!-- History List -->
        <div class="mu-history-list">
            <!-- Populated by JavaScript -->
        </div>

        <!-- Empty State -->
        <div class="mu-history-empty" style="display: none;">
            <div class="mu-history-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h3 class="mu-history-empty-title"><?php esc_html_e('No watch history yet', 'astra-child'); ?></h3>
            <p class="mu-history-empty-desc"><?php esc_html_e('Start watching movies and TV shows to see your history here. Your progress will be saved automatically.', 'astra-child'); ?></p>
            <div class="mu-history-empty-actions">
                <a href="<?php echo esc_url($movies_url); ?>" class="mu-history-empty-btn mu-history-empty-btn--primary" data-href="<?php echo esc_url($movies_url); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/>
                        <line x1="7" y1="2" x2="7" y2="22"/>
                        <line x1="17" y1="2" x2="17" y2="22"/>
                    </svg>
                    <?php esc_html_e('Explore Movies', 'astra-child'); ?>
                </a>
                <a href="<?php echo esc_url($tv_url); ?>" class="mu-history-empty-btn mu-history-empty-btn--secondary" data-href="<?php echo esc_url($tv_url); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <rect x="2" y="7" width="20" height="15" rx="2" ry="2"/>
                        <polyline points="17 2 12 7 7 2"/>
                    </svg>
                    <?php esc_html_e('Explore TV Shows', 'astra-child'); ?>
                </a>
                <a href="<?php echo esc_url($trending_url); ?>" class="mu-history-empty-btn mu-history-empty-btn--secondary" data-href="<?php echo esc_url($trending_url); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <?php esc_html_e('Trending Now', 'astra-child'); ?>
                </a>
            </div>
        </div>

        <!-- Load More -->
        <div class="mu-history-load-more" id="mu-history-load-more-wrap" style="display: none;">
            <button type="button" id="mu-history-load-more" class="mu-history-load-more-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
                <?php esc_html_e('Load More', 'astra-child'); ?>
            </button>
            <span class="mu-history-load-more-info"></span>
        </div>
    </main>
</div>

<!-- ============================================================ -->
<!-- CONFIRM MODAL -->
<!-- ============================================================ -->
<div class="mu-history-modal" id="mu-history-modal">
    <div class="mu-history-modal-backdrop"></div>
    <div class="mu-history-modal-content">
        <div class="mu-history-modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <h3 class="mu-history-modal-title"></h3>
        <p class="mu-history-modal-desc"></p>
        <div class="mu-history-modal-actions">
            <button type="button" id="mu-history-modal-cancel" class="mu-history-modal-btn mu-history-modal-btn--cancel">
                <?php esc_html_e('Cancel', 'astra-child'); ?>
            </button>
            <button type="button" id="mu-history-modal-confirm" class="mu-history-modal-btn mu-history-modal-btn--confirm">
                <?php esc_html_e('Clear All', 'astra-child'); ?>
            </button>
        </div>
    </div>
</div>

<?php wp_footer(); ?>
<script>
window.MU_HISTORY_DATA = <?php echo wp_json_encode($history_data); ?>;
window.MU_HISTORY_NONCE = <?php echo wp_json_encode($history_nonce); ?>;
</script>
</body>
</html>
