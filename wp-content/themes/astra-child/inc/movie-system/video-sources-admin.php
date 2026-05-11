<?php
/**
 * Video Sources Admin Panel
 * Allow adding/editing video URLs for movies/TV shows
 */

if (!defined('ABSPATH')) exit;

class MU_Video_Sources_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_mu_search_movies', [$this, 'ajax_search_movies']);
        add_action('wp_ajax_mu_save_video_source', [$this, 'ajax_save_video_source']);
        add_action('wp_ajax_mu_delete_video_source', [$this, 'ajax_delete_video_source']);
        add_action('wp_ajax_mu_bulk_add_sources', [$this, 'ajax_bulk_add_sources']);
    }
    
    public function add_menu() {
        add_submenu_page(
            'mu-movie-importer',
            'Video Sources',
            'Video Sources',
            'manage_options',
            'mu-video-sources',
            [$this, 'render_page']
        );
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'mu-video-sources') === false) return;
        
        wp_enqueue_style('mu-video-admin', get_stylesheet_directory_uri() . '/assets/css/video-sources-admin.css', [], '1.0');
        wp_enqueue_script('mu-video-admin', get_stylesheet_directory_uri() . '/assets/js/video-sources-admin.js', ['jquery'], '1.0', true);
        wp_localize_script('mu-video-admin', 'muVideoAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mu_video_sources_nonce'),
            'strings' => [
                'saved' => 'Saved successfully!',
                'deleted' => 'Deleted successfully!',
                'error' => 'Error occurred',
                'confirm_delete' => 'Delete this video source?'
            ]
        ]);
    }
    
    public function ajax_search_movies() {
        check_ajax_referer('mu_video_sources_nonce');
        
        $query = sanitize_text_field($_POST['q'] ?? '');
        if (strlen($query) < 2) wp_send_json_error('Query too short');
        
        $posts = get_posts([
            'post_type' => ['movie', 'tv_show'],
            'posts_per_page' => 20,
            's' => $query,
            'post_status' => 'publish'
        ]);
        
        $results = [];
        foreach ($posts as $p) {
            $type = get_post_type($p);
            $poster = get_the_post_thumbnail_url($p->ID, 'thumbnail');
            $video_count = $this->count_video_sources($p->ID);
            $results[] = [
                'id' => $p->ID,
                'title' => $p->post_title,
                'type' => $type,
                'poster' => $poster,
                'video_count' => $video_count,
                'has_video' => $video_count > 0
            ];
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_save_video_source() {
        check_ajax_referer('mu_video_sources_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $source_type = sanitize_text_field($_POST['source_type'] ?? '');
        $source_url = esc_url_raw($_POST['source_url'] ?? '');
        $quality = sanitize_text_field($_POST['quality'] ?? 'auto');
        $label = sanitize_text_field($_POST['label'] ?? '');
        
        if (!$post_id || !$source_type || !$source_url) {
            wp_send_json_error('Missing required fields');
        }
        
        // Save to post meta
        $existing = get_post_meta($post_id, '_video_sources', true);
        $sources = $existing ? json_decode($existing, true) : [];
        
        if (!is_array($sources)) $sources = [];
        
        // Add new source
        $sources[] = [
            'id' => uniqid('src_'),
            'type' => $source_type,
            'url' => $source_url,
            'quality' => $quality,
            'label' => $label,
            'added' => current_time('mysql')
        ];
        
        update_post_meta($post_id, '_video_sources', wp_json_encode($sources));
        
        // Also set primary video URL based on type
        if ($source_type === 'mp4' || $source_type === 'hls' || $source_type === 'direct') {
            if (!get_post_meta($post_id, '_video_url', true)) {
                update_post_meta($post_id, '_video_url', $source_url);
            }
        } elseif ($source_type === 'embed') {
            if (!get_post_meta($post_id, '_embed_url', true)) {
                update_post_meta($post_id, '_embed_url', $source_url);
            }
        } elseif ($source_type === 'hls') {
            if (!get_post_meta($post_id, '_hls_url', true)) {
                update_post_meta($post_id, '_hls_url', $source_url);
            }
        }
        
        wp_send_json_success([
            'message' => 'Video source added!',
            'sources' => $sources
        ]);
    }
    
    public function ajax_delete_video_source() {
        check_ajax_referer('mu_video_sources_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $source_id = sanitize_text_field($_POST['source_id'] ?? '');
        
        if (!$post_id || !$source_id) wp_send_json_error('Missing fields');
        
        $existing = get_post_meta($post_id, '_video_sources', true);
        $sources = $existing ? json_decode($existing, true) : [];
        
        $sources = array_values(array_filter($sources, function($s) use ($source_id) {
            return $s['id'] !== $source_id;
        }));
        
        update_post_meta($post_id, '_video_sources', wp_json_encode($sources));
        
        wp_send_json_success(['message' => 'Deleted', 'sources' => $sources]);
    }
    
    public function ajax_bulk_add_sources() {
        check_ajax_referer('mu_video_sources_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $bulk_url = esc_url_raw($_POST['bulk_url'] ?? '');
        $bulk_type = sanitize_text_field($_POST['bulk_type'] ?? 'embed');
        $pattern = sanitize_text_field($_POST['pattern'] ?? '');
        
        if (!$post_id || !$bulk_url) wp_send_json_error('Missing fields');
        
        $added = 0;
        $existing = get_post_meta($post_id, '_video_sources', true);
        $sources = $existing ? json_decode($existing, true) : [];
        if (!is_array($sources)) $sources = [];
        
        // If pattern provided, generate multiple sources
        if ($pattern && preg_match('/\{(\d+)\}/', $pattern)) {
            // Pattern like https://example.com/movie-{1-10}.mp4
            $qualities = ['360', '480', '720', '1080', '4k'];
            foreach ($qualities as $q) {
                $url = str_replace('{1}', $q, $bulk_url);
                $url = str_replace('{quality}', $q, $url);
                
                if (!empty($url)) {
                    $sources[] = [
                        'id' => uniqid('src_'),
                        'type' => $bulk_type,
                        'url' => $url,
                        'quality' => $q,
                        'label' => $q . 'p',
                        'added' => current_time('mysql')
                    ];
                    $added++;
                }
            }
        } else {
            // Single URL
            $sources[] = [
                'id' => uniqid('src_'),
                'type' => $bulk_type,
                'url' => $bulk_url,
                'quality' => 'auto',
                'label' => 'Source 1',
                'added' => current_time('mysql')
            ];
            $added = 1;
        }
        
        update_post_meta($post_id, '_video_sources', wp_json_encode($sources));
        
        // Set primary
        update_post_meta($post_id, '_video_url', $bulk_url);
        
        wp_send_json_success([
            'message' => "Added {$added} source(s)",
            'sources' => $sources
        ]);
    }
    
    private function count_video_sources($post_id) {
        $sources = get_post_meta($post_id, '_video_sources', true);
        if (!$sources) return 0;
        $data = json_decode($sources, true);
        return is_array($data) ? count($data) : 0;
    }
    
    public function render_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap mu-video-sources-admin">
            <h1>Video Sources Manager</h1>
            <p>Add video URLs so users can watch movies/TV shows (not just trailers).</p>
            
            <div class="mu-vs-layout">
                <!-- Search Panel -->
                <div class="mu-vs-search-panel">
                    <h2>Select Movie/TV Show</h2>
                    <div class="mu-vs-search-box">
                        <input type="text" id="mu-vs-search" placeholder="Search movies or TV shows..." autocomplete="off">
                        <span class="mu-vs-search-icon">🔍</span>
                    </div>
                    <div id="mu-vs-search-results" class="mu-vs-results"></div>
                </div>
                
                <!-- Source Editor -->
                <div class="mu-vs-editor-panel">
                    <div class="mu-vs-placeholder" id="mu-vs-placeholder">
                        <div class="mu-vs-placeholder-icon">🎬</div>
                        <p>Select a movie or TV show to manage video sources</p>
                    </div>
                    
                    <div class="mu-vs-editor" id="mu-vs-editor" style="display:none;">
                        <div class="mu-vs-header">
                            <img id="mu-vs-poster" src="" alt="">
                            <div>
                                <h2 id="mu-vs-title"></h2>
                                <span id="mu-vs-type" class="mu-vs-type-badge"></span>
                            </div>
                        </div>
                        
                        <!-- Existing Sources -->
                        <div class="mu-vs-sources-section">
                            <h3>Current Video Sources</h3>
                            <div id="mu-vs-sources-list" class="mu-vs-sources-list"></div>
                        </div>
                        
                        <!-- Add New Source -->
                        <div class="mu-vs-add-section">
                            <h3>Add Video Source</h3>
                            
                            <div class="mu-vs-form-group">
                                <label>Source Type</label>
                                <select id="mu-vs-source-type">
                                    <option value="embed">Embed / iFrame (Recommended)</option>
                                    <option value="mp4">MP4 Direct Link</option>
                                    <option value="hls">HLS Stream (.m3u8)</option>
                                    <option value="dash">DASH Stream (.mpd)</option>
                                </select>
                            </div>
                            
                            <div class="mu-vs-form-group">
                                <label>Video URL</label>
                                <input type="url" id="mu-vs-source-url" placeholder="https://example.com/video.mp4 or embed URL">
                                <p class="description">For embed: YouTube, Vimeo, or any iframe embed URL</p>
                            </div>
                            
                            <div class="mu-vs-form-group">
                                <label>Quality</label>
                                <select id="mu-vs-quality">
                                    <option value="auto">Auto</option>
                                    <option value="4k">4K (2160p)</option>
                                    <option value="1080p">1080p</option>
                                    <option value="720p">720p</option>
                                    <option value="480p">480p</option>
                                    <option value="360p">360p</option>
                                </select>
                            </div>
                            
                            <div class="mu-vs-form-group">
                                <label>Label (Optional)</label>
                                <input type="text" id="mu-vs-label" placeholder="e.g. Server 1, Backup">
                            </div>
                            
                            <button type="button" class="button button-primary" id="mu-vs-btn-add">Add Source</button>
                        </div>
                        
                        <!-- Bulk Add -->
                        <div class="mu-vs-bulk-section">
                            <h3>Bulk Add (Multiple Qualities)</h3>
                            <p>Use pattern like: <code>https://example.com/video-{quality}.mp4</code></p>
                            <input type="url" id="mu-vs-bulk-url" placeholder="https://example.com/video-{quality}.mp4">
                            <button type="button" class="button" id="mu-vs-btn-bulk">Add Multiple Qualities</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var currentPostId = null;
            
            // Search
            var searchTimeout;
            $('#mu-vs-search').on('input', function() {
                var q = $(this).val();
                clearTimeout(searchTimeout);
                if (q.length < 2) {
                    $('#mu-vs-search-results').html('');
                    return;
                }
                searchTimeout = setTimeout(function() {
                    $.post(muVideoAdmin.ajaxUrl, {
                        action: 'mu_search_movies',
                        q: q,
                        _ajax_nonce: muVideoAdmin.nonce
                    }, function(resp) {
                        if (resp.success) {
                            renderResults(resp.data);
                        }
                    });
                }, 300);
            });
            
            function renderResults(items) {
                if (!items.length) {
                    $('#mu-vs-search-results').html('<p class="mu-vs-no-results">No movies found</p>');
                    return;
                }
                var html = '';
                items.forEach(function(item) {
                    var hasVideo = item.has_video ? '<span class="mu-vs-has-video">✓ Has video</span>' : '<span class="mu-vs-no-video">No video</span>';
                    html += '<div class="mu-vs-result-item" data-id="' + item.id + '">' +
                        '<img src="' + (item.poster || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 90"%3E%3Crect fill="%23333" width="60" height="90"/%3E%3C/svg%3E') + '" alt="">' +
                        '<div class="mu-vs-result-info">' +
                        '<strong>' + item.title + '</strong>' +
                        '<span>' + (item.type === 'movie' ? 'Movie' : 'TV Show') + '</span>' +
                        hasVideo +
                        '</div></div>';
                });
                $('#mu-vs-search-results').html(html);
            }
            
            // Select movie
            $(document).on('click', '.mu-vs-result-item', function() {
                var id = $(this).data('id');
                var title = $(this).find('strong').text();
                var type = $(this).find('.mu-vs-result-info span').first().text();
                var poster = $(this).find('img').attr('src');
                
                currentPostId = id;
                $('#mu-vs-title').text(title);
                $('#mu-vs-type').text(type);
                $('#mu-vs-poster').attr('src', poster);
                $('#mu-vs-placeholder').hide();
                $('#mu-vs-editor').show();
                
                // Load sources
                loadSources(id);
            });
            
            function loadSources(postId) {
                $.post(muVideoAdmin.ajaxUrl, {
                    action: 'mu_get_video_sources',
                    post_id: postId,
                    _ajax_nonce: muVideoAdmin.nonce
                }, function(resp) {
                    if (resp.success) {
                        renderSources(resp.data.sources);
                    }
                });
            }
            
            function renderSources(sources) {
                if (!sources || !sources.length) {
                    $('#mu-vs-sources-list').html('<p class="mu-vs-no-sources">No video sources added yet</p>');
                    return;
                }
                var html = '';
                sources.forEach(function(src) {
                    var typeIcon = src.type === 'embed' ? '📺' : src.type === 'hls' ? '📡' : '🎬';
                    var typeLabel = src.type.charAt(0).toUpperCase() + src.type.slice(1);
                    html += '<div class="mu-vs-source-item" data-id="' + src.id + '">' +
                        '<div class="mu-vs-source-icon">' + typeIcon + '</div>' +
                        '<div class="mu-vs-source-info">' +
                        '<strong>' + (src.label || typeLabel) + '</strong>' +
                        '<code>' + src.url.substring(0, 50) + (src.url.length > 50 ? '...' : '') + '</code>' +
                        '<span>' + typeLabel + ' • ' + src.quality + '</span>' +
                        '</div>' +
                        '<button class="mu-vs-source-delete button button-small button-link" data-id="' + src.id + '">Delete</button>' +
                        '</div>';
                });
                $('#mu-vs-sources-list').html(html);
            }
            
            // Add source
            $('#mu-vs-btn-add').on('click', function() {
                if (!currentPostId) return;
                var source_type = $('#mu-vs-source-type').val();
                var source_url = $('#mu-vs-source-url').val();
                var quality = $('#mu-vs-quality').val();
                var label = $('#mu-vs-label').val();
                
                if (!source_url) {
                    alert('Please enter a video URL');
                    return;
                }
                
                $.post(muVideoAdmin.ajaxUrl, {
                    action: 'mu_save_video_source',
                    post_id: currentPostId,
                    source_type: source_type,
                    source_url: source_url,
                    quality: quality,
                    label: label,
                    _ajax_nonce: muVideoAdmin.nonce
                }, function(resp) {
                    if (resp.success) {
                        renderSources(resp.data.sources);
                        $('#mu-vs-source-url').val('');
                        $('#mu-vs-label').val('');
                    } else {
                        alert('Error: ' + resp.data);
                    }
                });
            });
            
            // Delete source
            $(document).on('click', '.mu-vs-source-delete', function() {
                if (!confirm('Delete this video source?')) return;
                var sourceId = $(this).data('id');
                
                $.post(muVideoAdmin.ajaxUrl, {
                    action: 'mu_delete_video_source',
                    post_id: currentPostId,
                    source_id: sourceId,
                    _ajax_nonce: muVideoAdmin.nonce
                }, function(resp) {
                    if (resp.success) {
                        renderSources(resp.data.sources);
                    }
                });
            });
            
            // Bulk add
            $('#mu-vs-btn-bulk').on('click', function() {
                if (!currentPostId) return;
                var url = $('#mu-vs-bulk-url').val();
                var type = $('#mu-vs-source-type').val();
                
                if (!url) {
                    alert('Please enter a URL pattern');
                    return;
                }
                
                $.post(muVideoAdmin.ajaxUrl, {
                    action: 'mu_bulk_add_sources',
                    post_id: currentPostId,
                    bulk_url: url,
                    bulk_type: type,
                    pattern: url,
                    _ajax_nonce: muVideoAdmin.nonce
                }, function(resp) {
                    if (resp.success) {
                        renderSources(resp.data.sources);
                        $('#mu-vs-bulk-url').val('');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Add AJAX handler for getting sources
add_action('wp_ajax_mu_get_video_sources', function() {
    check_ajax_referer('mu_video_sources_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
    $post_id = intval($_POST['post_id'] ?? 0);
    $sources = get_post_meta($post_id, '_video_sources', true);
    $data = $sources ? json_decode($sources, true) : [];
    
    wp_send_json_success(['sources' => $data ?: []]);
});

// Initialize
new MU_Video_Sources_Admin();
