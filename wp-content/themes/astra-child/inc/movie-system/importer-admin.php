<?php
/**
 * Admin Panel for Movie Importer
 * Enhanced with Cast, Clips, Reviews import
 */

if (!defined('ABSPATH')) exit;

// Register Settings
add_action('admin_init', function() {
    register_setting('mu_importer_options', 'mu_tmdb_api_key');
    register_setting('mu_importer_options', 'mu_omdb_api_key');
});

// Add Menu Page
add_action('admin_menu', function() {
    add_menu_page(
        'Movie Importer',
        'Movie Importer',
        'manage_options',
        'mu-movie-importer',
        'mu_importer_admin_page',
        'dashicons-download',
        30
    );
});

// Enqueue admin scripts
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_mu-movie-importer') return;
    
    wp_add_inline_style('admin-bar', '
        .mu-importer-wrap { max-width: 1200px; margin: 20px 0; }
        .mu-card-panel { background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-bottom: 20px; border-radius: 4px;}
        .mu-card-panel h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;}
        .mu-log-area { width: 100%; height: 250px; background: #1a1a1a; color: #0f0; font-family: monospace; padding: 10px; overflow-y: auto; box-sizing: border-box; margin-top: 10px; border-radius: 4px; font-size: 13px; }
        .mu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .mu-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .mu-category-btn { display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; cursor: pointer; transition: all 0.2s; font-size: 13px; text-align: left; width: 100%; }
        .mu-category-btn:hover { background: #f0f6fc; border-color: #0366d6; }
        .mu-category-btn.active { background: #0366d6; color: #fff; border-color: #0366d6; }
        .mu-category-btn .mu-cat-icon { font-size: 16px; }
        .mu-category-btn .mu-cat-label { flex: 1; }
        .mu-category-btn .mu-cat-type { font-size: 10px; padding: 2px 5px; background: rgba(0,0,0,0.1); border-radius: 3px; }
        .mu-category-btn.active .mu-cat-type { background: rgba(255,255,255,0.2); }
        .mu-section-title { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #646970; margin: 15px 0 8px; padding-bottom: 5px; border-bottom: 1px solid #eee; }
        .mu-section-title:first-child { margin-top: 0; }
        .mu-import-summary { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .mu-summary-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f6f7f7; border-radius: 4px; }
        .mu-summary-item .mu-s-num { font-size: 16px; font-weight: 700; color: #0366d6; }
        .mu-summary-item .mu-s-label { font-size: 11px; color: #646970; }
        .mu-progress-bar { height: 6px; background: #dcdcde; border-radius: 3px; overflow: hidden; margin: 10px 0; }
        .mu-progress-bar-fill { height: 100%; background: #0366d6; transition: width 0.3s; }
        .mu-loading-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #f3f4f5; border-top-color: #0366d6; border-radius: 50%; animation: mu-spin 0.8s linear infinite; }
        @keyframes mu-spin { to { transform: rotate(360deg); } }
        .mu-tabs { display: flex; gap: 0; margin-bottom: 20px; border-bottom: 1px solid #dcdcde; }
        .mu-tab { padding: 10px 20px; cursor: pointer; border: none; background: none; font-size: 14px; color: #646970; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .mu-tab:hover { color: #000; }
        .mu-tab.active { color: #0366d6; border-bottom-color: #0366d6; font-weight: 500; }
        .mu-tab-content { display: none; }
        .mu-tab-content.active { display: block; }
        .mu-task-btn { padding: 8px 16px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .mu-task-btn:hover { background: #f0f6fc; border-color: #0366d6; }
        .mu-task-btn.active { background: #0366d6; color: #fff; border-color: #0366d6; }
    ');
});

function mu_importer_admin_page() {
    if (!current_user_can('manage_options')) return;
    
    $movie_count = wp_count_posts('movie')->publish ?? 0;
    $tv_count = wp_count_posts('tv_show')->publish ?? 0;
    ?>
    <div class="wrap mu-importer-wrap">
        <h1>Movie Data Importer</h1>
        <p>Automatically fetch movies, TV shows, and metadata from TMDB (The Movie Database).</p>
        
        <?php settings_errors(); ?>

        <!-- Tabs -->
        <div class="mu-tabs">
            <button class="mu-tab active" data-tab="import">Import Content</button>
            <button class="mu-tab" data-tab="backfill">Update Existing</button>
            <button class="mu-tab" data-tab="settings">Settings</button>
        </div>

        <!-- Import Tab -->
        <div class="mu-tab-content active" id="tab-import">
            <div class="mu-grid">
                <div class="mu-col">
                    <!-- API Settings -->
                    <div class="mu-card-panel">
                        <h2>API Settings</h2>
                        <form method="post" action="options.php">
                            <?php settings_fields('mu_importer_options'); ?>
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row"><label for="mu_tmdb_api_key">TMDB API Key</label></th>
                                    <td>
                                        <input name="mu_tmdb_api_key" type="password" id="mu_tmdb_api_key" value="<?php echo esc_attr(get_option('mu_tmdb_api_key')); ?>" class="regular-text" />
                                        <p class="description">Get your API key from <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDB Settings</a> (free).</p>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button('Save API Key'); ?>
                        </form>
                    </div>

                    <!-- Quick Stats -->
                    <div class="mu-card-panel">
                        <h2>Library Stats</h2>
                        <div class="mu-import-summary">
                            <div class="mu-summary-item">
                                <span class="mu-s-num"><?php echo number_format($movie_count); ?></span>
                                <span class="mu-s-label">Movies</span>
                            </div>
                            <div class="mu-summary-item">
                                <span class="mu-s-num"><?php echo number_format($tv_count); ?></span>
                                <span class="mu-s-label">TV Shows</span>
                            </div>
                        </div>
                    </div>

                    <!-- Single Import -->
                    <div class="mu-card-panel">
                        <h2>Import Single Item</h2>
                        <form id="mu-import-single-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="import_id">TMDB ID</label></th>
                                    <td>
                                        <input type="number" id="import_id" required class="regular-text" placeholder="e.g. 550 (Fight Club)" />
                                        <p class="description">Find ID: themoviedb.org/movie/<strong>550</strong></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="import_type">Type</label></th>
                                    <td>
                                        <select id="import_type">
                                            <option value="movie">Movie</option>
                                            <option value="tv">TV Show</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <button type="button" class="button button-primary" id="mu-btn-import-single">Import Item</button>
                                <span id="mu-single-status"></span>
                            </p>
                        </form>
                    </div>
                </div>

                <div class="mu-col">
                    <!-- Bulk Import -->
                    <div class="mu-card-panel">
                        <h2>Bulk Import</h2>
                        
                        <div class="mu-category-grid" id="mu-category-grid">
                            
                            <div class="mu-section-title">Trending & Popular</div>
                            <button type="button" class="mu-category-btn" data-cat="trending_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Trending Movies</span><span class="mu-cat-type">Movie</span></button>
                            <button type="button" class="mu-category-btn" data-cat="popular_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Popular Movies</span><span class="mu-cat-type">Movie</span></button>
                            <button type="button" class="mu-category-btn" data-cat="trending_tv" data-type="tv"><span class="mu-cat-icon"></span><span class="mu-cat-label">Trending TV Shows</span><span class="mu-cat-type">TV</span></button>
                            <button type="button" class="mu-category-btn" data-cat="popular_tv" data-type="tv"><span class="mu-cat-icon"></span><span class="mu-cat-label">Popular TV Shows</span><span class="mu-cat-type">TV</span></button>

                            <div class="mu-section-title">Top Rated</div>
                            <button type="button" class="mu-category-btn" data-cat="top_rated_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Top Rated Movies</span><span class="mu-cat-type">Movie</span></button>
                            <button type="button" class="mu-category-btn" data-cat="top_rated_tv" data-type="tv"><span class="mu-cat-icon"></span><span class="mu-cat-label">Top Rated TV Shows</span><span class="mu-cat-type">TV</span></button>

                            <div class="mu-section-title">Movies by Genre</div>
                            <div class="mu-grid-3">
                                <button type="button" class="mu-category-btn" data-cat="action_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Action</span></button>
                                <button type="button" class="mu-category-btn" data-cat="comedy_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Comedy</span></button>
                                <button type="button" class="mu-category-btn" data-cat="horror_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Horror</span></button>
                                <button type="button" class="mu-category-btn" data-cat="scifi_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Sci-Fi</span></button>
                                <button type="button" class="mu-category-btn" data-cat="romance_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Romance</span></button>
                                <button type="button" class="mu-category-btn" data-cat="thriller_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Thriller</span></button>
                                <button type="button" class="mu-category-btn" data-cat="animation_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Animation</span></button>
                                <button type="button" class="mu-category-btn" data-cat="fantasy_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Fantasy</span></button>
                                <button type="button" class="mu-category-btn" data-cat="documentary_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">Documentary</span></button>
                            </div>

                            <div class="mu-section-title">TV Shows by Genre</div>
                            <div class="mu-grid-3">
                                <button type="button" class="mu-category-btn" data-cat="drama_tv" data-type="tv"><span class="mu-cat-icon"></span><span class="mu-cat-label">Drama</span></button>
                                <button type="button" class="mu-category-btn" data-cat="comedy_tv" data-type="tv"><span class="mu-cat-icon"></span><span class="mu-cat-label">Comedy</span></button>
                                <button type="button" class="mu-category-btn" data-cat="crime_tv" data-type="tv"><span class="mu-cat-icon"></span><span class="mu-cat-label">Crime</span></button>
                                <button type="button" class="mu-category-btn" data-cat="sci_fi_tv" data-type="tv"><span class="mu-cat-icon"></span><span class="mu-cat-label">Sci-Fi</span></button>
                                <button type="button" class="mu-category-btn" data-cat="animation_tv" data-type="tv"><span class="mu-cat-icon"></span><span class="mu-cat-label">Animation</span></button>
                            </div>

                            <div class="mu-section-title">By Year</div>
                            <div class="mu-grid-3">
                                <button type="button" class="mu-category-btn" data-cat="2024_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">2024</span></button>
                                <button type="button" class="mu-category-btn" data-cat="2023_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">2023</span></button>
                                <button type="button" class="mu-category-btn" data-cat="2022_movies" data-type="movie"><span class="mu-cat-icon"></span><span class="mu-cat-label">2022</span></button>
                            </div>
                        </div>

                        <!-- Import Controls -->
                        <div id="mu-import-controls" style="display:none; margin-top:20px; padding:15px; background:#f6f7f7; border-radius:4px;">
                            <p style="margin:0 0 10px;">Selected: <strong id="mu-selected-cat">-</strong></p>
                            <table class="form-table" style="margin:0;">
                                <tr>
                                    <th><label for="bulk_limit">Items:</label></th>
                                    <td>
                                        <input type="number" id="bulk_limit" value="20" min="1" max="100" class="small-text" />
                                    </td>
                                </tr>
                            </table>
                            <p class="submit" style="margin:15px 0 0;">
                                <button type="button" class="button button-primary" id="mu-btn-import-bulk">Start Import</button>
                                <button type="button" class="button button-secondary" id="mu-btn-cancel-bulk">Cancel</button>
                            </p>
                        </div>

                        <!-- Progress -->
                        <div id="mu-import-progress" style="display:none; margin-top:20px;">
                            <div class="mu-progress-bar"><div class="mu-progress-bar-fill" id="mu-progress-fill" style="width:0%"></div></div>
                            <p id="mu-progress-text" style="margin:0; font-size:12px; color:#646970;">Preparing...</p>
                        </div>
                    </div>

                    <!-- Logs -->
                    <div class="mu-card-panel">
                        <h2>Import Logs</h2>
                        <div class="mu-log-area" id="mu-log-area">System ready...</div>
                        <p style="margin:10px 0 0; text-align:right;">
                            <button type="button" class="button button-secondary" id="mu-btn-clear-log">Clear</button>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backfill Tab -->
        <div class="mu-tab-content" id="tab-backfill">
            <div class="mu-grid">
                <div class="mu-col">
                    <div class="mu-card-panel">
                        <h2>Update Existing Content</h2>
                        <p>Import full cast with profile images, all video clips, and reviews for movies/TV shows already in your library.</p>
                        
                        <div style="margin: 20px 0;">
                            <p style="font-weight:600; margin-bottom:10px;">Select what to update:</p>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button type="button" class="mu-task-btn active" data-task="all">All (Recommended)</button>
                                <button type="button" class="mu-task-btn" data-task="cast">Cast + Photos</button>
                                <button type="button" class="mu-task-btn" data-task="clips">Video Clips</button>
                                <button type="button" class="mu-task-btn" data-task="reviews">Reviews</button>
                            </div>
                        </div>

                        <p style="margin-bottom:15px;">
                            <strong>Note:</strong> This will update existing items in batches of 5 to avoid rate limits.
                        </p>

                        <p class="submit">
                            <button type="button" class="button button-primary" id="mu-btn-backfill-all">Start Update</button>
                            <span id="mu-backfill-status" style="margin-left:12px;color:#666;"></span>
                        </p>
                        
                        <div id="mu-backfill-progress" style="display:none; margin-top:15px;">
                            <div class="mu-progress-bar"><div class="mu-progress-bar-fill" id="mu-backfill-fill" style="width:0%"></div></div>
                            <p id="mu-backfill-text" style="margin:0; font-size:12px; color:#646970;"></p>
                        </div>
                    </div>

                    <div class="mu-card-panel">
                        <h2>What gets imported?</h2>
                        <ul style="margin:0; padding-left:20px;">
                            <li><strong>Cast:</strong> Full cast list (up to 30) with profile photos for top 10 actors</li>
                            <li><strong>Videos:</strong> All trailers, teasers, clips, behind the scenes footage</li>
                            <li><strong>Reviews:</strong> User reviews from TMDB (up to 20 per title)</li>
                            <li><strong>Crew:</strong> Directors, writers, producers with profile images</li>
                        </ul>
                    </div>
                </div>

                <div class="mu-col">
                    <div class="mu-card-panel">
                        <h2>Backfill Logs</h2>
                        <div class="mu-log-area" id="mu-backfill-log">Ready to start update...</div>
                        <p style="margin:10px 0 0; text-align:right;">
                            <button type="button" class="button button-secondary" id="mu-btn-clear-backfill-log">Clear</button>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div class="mu-tab-content" id="tab-settings">
            <div class="mu-card-panel" style="max-width:600px;">
                <h2>API Settings</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('mu_importer_options'); ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="mu_tmdb_api_key">TMDB API Key</label></th>
                            <td>
                                <input name="mu_tmdb_api_key" type="password" id="mu_tmdb_api_key" value="<?php echo esc_attr(get_option('mu_tmdb_api_key')); ?>" class="regular-text" />
                                <p class="description">Required for importing. Get free key at <a href="https://www.themoviedb.org/settings/api" target="_blank">themoviedb.org</a></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Save Settings'); ?>
                </form>

                <hr style="margin:30px 0; border:none; border-top:1px solid #eee;">
                
                <h3>Rate Limits</h3>
                <p>TMDB has rate limits of <strong>40 requests every 10 seconds</strong>. The importer automatically adds delays to stay within limits.</p>
                
                <h3 style="margin-top:20px;">Need Help?</h3>
                <p>For more categories or custom imports, you can modify the importer files in <code>/wp-content/themes/astra-child/inc/movie-system/</code></p>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var selectedCategory = null;
        var selectedType = null;
        var isImporting = false;
        var selectedTask = 'all';

        // Tabs
        $('.mu-tab').on('click', function() {
            $('.mu-tab').removeClass('active');
            $('.mu-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#tab-' + $(this).data('tab')).addClass('active');
        });

        // Task selection
        $('.mu-task-btn').on('click', function() {
            $('.mu-task-btn').removeClass('active');
            $(this).addClass('active');
            selectedTask = $(this).data('task');
        });

        function logMsg(msg, color, area) {
            area = area || 'mu-log-area';
            color = color || '#fff';
            $('#' + area).append('<div style="color:'+color+'">['+new Date().toLocaleTimeString()+'] '+msg+'</div>');
            $('#' + area).scrollTop($('#' + area)[0].scrollHeight);
        }

        // Category selection
        $('.mu-category-btn').on('click', function() {
            if (isImporting) return;
            $('.mu-category-btn').removeClass('active');
            $(this).addClass('active');
            selectedCategory = $(this).data('cat');
            selectedType = $(this).data('type') || 'movie';
            $('#mu-selected-cat').text($(this).find('.mu-cat-label').text());
            $('#mu-import-controls').show();
        });

        $('#mu-btn-cancel-bulk').on('click', function() {
            isImporting = false;
            $('#mu-import-controls').hide();
            $('#mu-import-progress').hide();
            $('.mu-category-btn').removeClass('active');
            logMsg('Import cancelled', '#ffb900');
        });

        // Bulk import
        $('#mu-btn-import-bulk').on('click', function() {
            if (!selectedCategory) { alert('Select a category first'); return; }
            var limit = parseInt($('#bulk_limit').val()) || 20;
            limit = Math.min(limit, 100);
            
            isImporting = true;
            $('#mu-import-controls').hide();
            $('#mu-import-progress').show();
            $('#mu-progress-fill').css('width', '0%');
            
            logMsg('Fetching: ' + selectedCategory + ' (limit: ' + limit + ')', '#0ff');
            
            $.post(ajaxurl, {
                action: 'mu_import_bulk',
                category: selectedCategory,
                type: selectedType,
                limit: limit,
                _ajax_nonce: '<?php echo wp_create_nonce("mu_import_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    var items = response.data.items;
                    logMsg('Found ' + items.length + ' items. Starting...', '#ffb900');
                    processBatch(items, 0);
                } else {
                    logMsg('ERROR: ' + (response.data || 'Failed'), '#dc3232');
                    isImporting = false;
                    $('#mu-import-progress').hide();
                }
            }).fail(function() {
                logMsg('ERROR: Request failed', '#dc3232');
                isImporting = false;
                $('#mu-import-progress').hide();
            });
        });

        function processBatch(items, index) {
            if (index >= items.length) {
                logMsg(' Import complete! ' + items.length + ' items.', '#46b450');
                isImporting = false;
                $('#mu-import-progress').hide();
                $('.mu-category-btn').removeClass('active');
                return;
            }
            
            var item = items[index];
            var progress = Math.round(((index + 1) / items.length) * 100);
            $('#mu-progress-fill').css('width', progress + '%');
            $('#mu-progress-text').text('Importing ' + (index + 1) + ' of ' + items.length + '...');
            
            logMsg('Importing: ' + item.title + ' [' + (index+1) + '/' + items.length + ']');
            
            $.post(ajaxurl, {
                action: 'mu_import_item',
                tmdb_id: item.id,
                type: item.type,
                _ajax_nonce: '<?php echo wp_create_nonce("mu_import_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    logMsg('-> SUCCESS: ' + response.data.message, '#46b450');
                } else {
                    logMsg('-> ERROR: ' + (response.data || 'Unknown'), '#dc3232');
                }
                setTimeout(function(){ processBatch(items, index + 1); }, 300);
            }).fail(function() {
                logMsg('-> ERROR: Request failed', '#dc3232');
                setTimeout(function(){ processBatch(items, index + 1); }, 300);
            });
        }

        // Single import
        $('#mu-btn-import-single').on('click', function() {
            var id = $('#import_id').val();
            var type = $('#import_type').val();
            var btn = $(this);
            
            if (!id) { alert('Enter TMDB ID'); return; }
            
            btn.prop('disabled', true).html('<span class="mu-loading-spinner"></span> Importing...');
            logMsg('Importing ID: ' + id + ' (' + type + ')', '#0ff');
            
            $.post(ajaxurl, {
                action: 'mu_import_item',
                tmdb_id: id,
                type: type,
                _ajax_nonce: '<?php echo wp_create_nonce("mu_import_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    logMsg('SUCCESS: ' + response.data.message, '#46b450');
                    $('#mu-single-status').html('<span style="color:#46b450;">✓ Success!</span>');
                } else {
                    logMsg('ERROR: ' + (response.data || 'Unknown'), '#dc3232');
                    $('#mu-single-status').html('<span style="color:#dc3232;">✗ Failed</span>');
                }
            }).fail(function() {
                logMsg('ERROR: Server failed', '#dc3232');
            }).always(function() {
                btn.prop('disabled', false).text('Import Item');
                setTimeout(function() { $('#mu-single-status').text(''); }, 3000);
            });
        });

        // Backfill all
        $('#mu-btn-backfill-all').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="mu-loading-spinner"></span> Updating...');
            $('#mu-backfill-status').text('Starting...');
            $('#mu-backfill-progress').show();
            logMsg('Starting backfill: ' + selectedTask, '#0ff', 'mu-backfill-log');
            runBackfill(0, btn);
        });

        function runBackfill(offset, btn) {
            $.post(ajaxurl, {
                action: 'mu_backfill_cast',
                offset: offset,
                task: selectedTask,
                _ajax_nonce: '<?php echo wp_create_nonce("mu_import_nonce"); ?>'
            }, function(response) {
                if (!response.success) {
                    logMsg('ERROR: ' + (response.data || 'Unknown'), '#dc3232', 'mu-backfill-log');
                    btn.prop('disabled', false).text('Start Update');
                    $('#mu-backfill-status').text('Failed');
                    return;
                }
                var data = response.data;
                if (data.log && data.log.length) {
                    data.log.forEach(function(line) {
                        logMsg(line, '#46b450', 'mu-backfill-log');
                    });
                }
                if (data.done) {
                    logMsg(' Backfill complete!', '#46b450', 'mu-backfill-log');
                    btn.prop('disabled', false).text('Start Update');
                    $('#mu-backfill-status').text('Done!');
                } else {
                    var total = <?php echo $movie_count + $tv_count; ?>;
                    var progress = Math.round((data.next_offset / total) * 100);
                    $('#mu-backfill-fill').css('width', Math.min(progress, 100) + '%');
                    $('#mu-backfill-text').text('Processed ' + data.next_offset + ' items...');
                    $('#mu-backfill-status').text('Offset ' + data.next_offset + '...');
                    setTimeout(function() { runBackfill(data.next_offset, btn); }, 500);
                }
            }).fail(function() {
                logMsg('ERROR: Server failed', '#dc3232', 'mu-backfill-log');
                btn.prop('disabled', false).text('Start Update');
                $('#mu-backfill-status').text('Failed');
            });
        }

        // Clear logs
        $('#mu-btn-clear-log').on('click', function() { $('#mu-log-area').html(''); });
        $('#mu-btn-clear-backfill-log').on('click', function() { $('#mu-backfill-log').html(''); });
    });
    </script>
    <?php
}
