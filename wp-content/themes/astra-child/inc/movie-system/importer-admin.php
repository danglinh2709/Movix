<?php
/**
 * Admin Panel for Movie Importer
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

// Enqueue admin scripts for the importer
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_mu-movie-importer') return;
    
    // Add custom CSS/JS for the importer panel if needed
    wp_add_inline_style('admin-bar', '
        .mu-importer-wrap { max-width: 1000px; margin: 20px 0; }
        .mu-card-panel { background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-bottom: 20px; border-radius: 4px;}
        .mu-card-panel h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; margin-bottom: 15px;}
        .mu-log-area { width: 100%; height: 300px; background: #111; color: #0f0; font-family: monospace; padding: 10px; overflow-y: auto; box-sizing: border-box; margin-top: 10px; }
        .mu-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; }
        .mu-badge-success { background: #46b450; }
        .mu-badge-warning { background: #ffb900; }
        .mu-badge-error { background: #dc3232; }
        .mu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    ');
});

function mu_importer_admin_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap mu-importer-wrap">
        <h1>Movie Data Importer</h1>
        <p>Automatically fetch movies, TV shows, and metadata from TMDB.</p>
        
        <?php settings_errors(); ?>

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
                                    <p class="description">Get your API key from <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDB Settings</a>.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mu_omdb_api_key">OMDb API Key (Optional)</label></th>
                                <td>
                                    <input name="mu_omdb_api_key" type="password" id="mu_omdb_api_key" value="<?php echo esc_attr(get_option('mu_omdb_api_key')); ?>" class="regular-text" />
                                    <p class="description">For fetching IMDb ratings.</p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Save API Keys'); ?>
                    </form>
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
                        </p>
                    </form>
                </div>
            </div>

            <div class="mu-col">
                <!-- Bulk Import -->
                <div class="mu-card-panel">
                    <h2>Bulk Import</h2>
                    <p>Fetch lists of movies automatically based on TMDB criteria.</p>
                    <form id="mu-import-bulk-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="bulk_category">Category</label></th>
                                <td>
                                    <select id="bulk_category">
                                        <option value="popular_movies">Popular Movies</option>
                                        <option value="trending_movies">Trending Movies</option>
                                        <option value="top_rated_movies">Top Rated Movies</option>
                                        <option value="popular_tv">Popular TV Shows</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bulk_limit">Limit</label></th>
                                <td>
                                    <input type="number" id="bulk_limit" value="20" min="1" max="100" class="small-text" /> items
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="button" class="button button-primary" id="mu-btn-import-bulk">Start Bulk Import</button>
                        </p>
                    </form>
                </div>

                <!-- Backfill Cast & Crew -->
                <div class="mu-card-panel" style="border-left:4px solid #0073aa;">
                    <h2>🎭 Update Cast &amp; Crew</h2>
                    <p>Fetch and save full cast/director data (with profile images) for all existing imported movies from TMDB. Run this once to backfill older imports.</p>
                    <p class="submit">
                        <button type="button" class="button button-secondary" id="mu-btn-backfill-cast">Update Cast &amp; Crew</button>
                        <span id="mu-backfill-status" style="margin-left:12px;color:#666;"></span>
                    </p>
                </div>

                <!-- Logs -->
                <div class="mu-card-panel">
                    <h2>Import Logs</h2>
                    <div class="mu-log-area" id="mu-log-area">
                        System ready. Awaiting import actions...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function logMsg(msg, color = '#fff') {
            $('#mu-log-area').append('<div style="color:'+color+'">['+new Date().toLocaleTimeString()+'] '+msg+'</div>');
            $('#mu-log-area').scrollTop($('#mu-log-area')[0].scrollHeight);
        }

        $('#mu-btn-import-single').on('click', function() {
            var id = $('#import_id').val();
            var type = $('#import_type').val();
            if(!id) { alert('Please enter an ID'); return; }
            
            var btn = $(this);
            btn.prop('disabled', true).text('Importing...');
            logMsg('Starting import for ' + type + ' ID: ' + id, '#0ff');
            
            $.post(ajaxurl, {
                action: 'mu_import_item',
                tmdb_id: id,
                type: type,
                _ajax_nonce: '<?php echo wp_create_nonce("mu_import_nonce"); ?>'
            }, function(response) {
                if(response.success) {
                    logMsg('SUCCESS: ' + response.data.message, '#46b450');
                } else {
                    logMsg('ERROR: ' + (response.data ? response.data : 'Unknown error'), '#dc3232');
                }
            }).fail(function() {
                logMsg('ERROR: Server request failed.', '#dc3232');
            }).always(function() {
                btn.prop('disabled', false).text('Import Item');
            });
        });

        $('#mu-btn-import-bulk').on('click', function() {
            var category = $('#bulk_category').val();
            var limit = $('#bulk_limit').val();
            var btn = $(this);
            btn.prop('disabled', true).text('Fetching list...');
            logMsg('Fetching ' + limit + ' items from ' + category + '...', '#0ff');
            
            $.post(ajaxurl, {
                action: 'mu_import_bulk',
                category: category,
                limit: limit,
                _ajax_nonce: '<?php echo wp_create_nonce("mu_import_nonce"); ?>'
            }, function(response) {
                if(response.success) {
                    var items = response.data.items;
                    logMsg('Found ' + items.length + ' items. Starting batch import...', '#ffb900');
                    processBatch(items, 0, btn);
                } else {
                    logMsg('ERROR: ' + (response.data ? response.data : 'Failed to fetch list'), '#dc3232');
                    btn.prop('disabled', false).text('Start Bulk Import');
                }
            }).fail(function() {
                logMsg('ERROR: Bulk fetch request failed.', '#dc3232');
                btn.prop('disabled', false).text('Start Bulk Import');
            });
        });

        function processBatch(items, index, btn) {
            if (index >= items.length) {
                logMsg('Bulk import completed!', '#46b450');
                btn.prop('disabled', false).text('Start Bulk Import');
                return;
            }
            
            var item = items[index];
            logMsg('Importing: ' + item.title + ' (ID: ' + item.id + ') [' + (index+1) + '/' + items.length + ']');
            
            $.post(ajaxurl, {
                action: 'mu_import_item',
                tmdb_id: item.id,
                type: item.type,
                _ajax_nonce: '<?php echo wp_create_nonce("mu_import_nonce"); ?>'
            }, function(response) {
                if(response.success) {
                    logMsg('-> SUCCESS: ' + response.data.message, '#46b450');
                } else {
                    logMsg('-> ERROR: ' + (response.data ? response.data : 'Unknown error'), '#dc3232');
                }
                // Delay next request slightly to prevent rate limiting
                setTimeout(function(){ processBatch(items, index + 1, btn); }, 500);
            }).fail(function() {
                logMsg('-> ERROR: Request failed for ' + item.id, '#dc3232');
                setTimeout(function(){ processBatch(items, index + 1, btn); }, 500);
            });
        }

        // --- Backfill Cast & Crew ---
        $('#mu-btn-backfill-cast').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Updating...');
            $('#mu-backfill-status').text('Starting...');
            logMsg('Starting Cast & Crew backfill...', '#0ff');
            runBackfill(0, btn);
        });

        function runBackfill(offset, btn) {
            $.post(ajaxurl, {
                action: 'mu_backfill_cast',
                offset: offset,
                _ajax_nonce: '<?php echo wp_create_nonce("mu_import_nonce"); ?>'
            }, function(response) {
                if (!response.success) {
                    logMsg('BACKFILL ERROR: ' + (response.data || 'Unknown error'), '#dc3232');
                    btn.prop('disabled', false).text('Update Cast & Crew');
                    $('#mu-backfill-status').text('Failed.');
                    return;
                }
                var data = response.data;
                if (data.log && data.log.length) {
                    data.log.forEach(function(line) {
                        logMsg(line, line.indexOf('updated') !== -1 ? '#46b450' : '#aaa');
                    });
                }
                if (data.done) {
                    logMsg('✅ Cast & Crew backfill complete!', '#46b450');
                    btn.prop('disabled', false).text('Update Cast & Crew');
                    $('#mu-backfill-status').text('Done!');
                } else {
                    $('#mu-backfill-status').text('Offset ' + data.next_offset + '...');
                    setTimeout(function() { runBackfill(data.next_offset, btn); }, 600);
                }
            }).fail(function() {
                logMsg('BACKFILL ERROR: Server request failed.', '#dc3232');
                btn.prop('disabled', false).text('Update Cast & Crew');
                $('#mu-backfill-status').text('Failed.');
            });
        }
    });
    </script>
    <?php
}
