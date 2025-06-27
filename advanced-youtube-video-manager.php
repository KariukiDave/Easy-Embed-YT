<?php
/**
 * Plugin Name: Advanced YouTube Video Manager
 * Plugin URI: https://yourwebsite.com
 * Description: Advanced YouTube video management with scheduling, playlists, analytics, and multiple video support.
 * Version: 2.1.5
 * Author: Glowbal Digital
 * Author URI: http://glowbal.co.ke/?ref=YTembedplugin
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('AdvancedYouTubeVideo')) {
class AdvancedYouTubeVideo {
    private $option_name = 'simple_youtube_video_url'; // Keep for backward compatibility
    private $videos_option = 'advanced_youtube_videos';
    private $settings_option = 'advanced_youtube_settings';
    private $analytics_option = 'advanced_youtube_analytics';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_youtube_video_play', array($this, 'track_video_play'));
        add_action('wp_ajax_nopriv_youtube_video_play', array($this, 'track_video_play'));
    }

    public function init() {
        $this->create_tables();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('youtube_video', array($this, 'render_youtube_video'));
        add_shortcode('custom_youtube_video', array($this, 'render_custom_youtube_video'));
        add_shortcode('youtube_playlist', array($this, 'render_youtube_playlist'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    private function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            youtube_url text NOT NULL,
            video_id varchar(50) NOT NULL,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            autoplay tinyint(1) DEFAULT 0,
            hide_controls tinyint(1) DEFAULT 0,
            loop_video tinyint(1) DEFAULT 0,
            start_time int(11) DEFAULT 0,
            mute_video tinyint(1) DEFAULT 0,
            lazy_load tinyint(1) DEFAULT 0,
            lightbox tinyint(1) DEFAULT 0,
            is_playlist tinyint(1) DEFAULT 0,
            playlist_id varchar(50) DEFAULT NULL,
            view_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        // Create youtube_video_views table for analytics
        $views_table = $wpdb->prefix . 'youtube_video_views';
        $sql2 = "CREATE TABLE $views_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            video_id MEDIUMINT(9) NOT NULL,
            view_date DATE NOT NULL,
            views INT(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY video_id (video_id),
            KEY view_date (view_date)
        ) $charset_collate;";
        dbDelta($sql2);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('advanced-youtube-video', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '2.0.0', true);
        wp_localize_script('advanced-youtube-video', 'advancedYouTube', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('youtube_video_nonce')
        ));
        wp_enqueue_style('advanced-youtube-video', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '2.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if ('media_page_advanced-youtube-video' !== $hook) {
            return;
        }
        wp_enqueue_script('advanced-youtube-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '2.0.0', true);
        wp_enqueue_style('advanced-youtube-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '2.0.0');
    }

    public function add_admin_menu() {
        add_media_page(
            'YouTube Video Manager',
            'YouTube Videos',
            'manage_options',
            'advanced-youtube-video',
            array($this, 'admin_page')
        );
    }

    public function register_settings() {
        register_setting('advanced_youtube_settings', $this->settings_option);
    }

    public function admin_page() {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'videos';
        if ($_POST) {
            $this->handle_form_submission();
        }
        ?>
        <div class="wrap">
            <h1>Advanced YouTube Video Manager</h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=advanced-youtube-video&tab=videos" class="nav-tab <?php echo $tab === 'videos' ? 'nav-tab-active' : ''; ?>">Videos</a>
                <a href="?page=advanced-youtube-video&tab=add" class="nav-tab <?php echo $tab === 'add' ? 'nav-tab-active' : ''; ?>">Add Video</a>
                <a href="?page=advanced-youtube-video&tab=analytics" class="nav-tab <?php echo $tab === 'analytics' ? 'nav-tab-active' : ''; ?>">Analytics</a>
                <a href="?page=advanced-youtube-video&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            </nav>
            <?php
            switch ($tab) {
                case 'add':
                    $this->render_add_video_tab();
                    break;
                case 'analytics':
                    $this->render_analytics_tab();
                    break;
                case 'settings':
                    $this->render_settings_tab();
                    break;
                default:
                    $this->render_videos_tab();
                    break;
            }
            ?>
        </div>
        <?php
    }

    private function render_videos_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $videos = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        <div class="tabcontent">
            <h2>Video Library</h2>
            <?php if (empty($videos)): ?>
                <p>No videos found. <a href="?page=advanced-youtube-video&tab=add">Add your first video</a>.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Video</th>
                            <th>Schedule</th>
                            <th>Views</th>
                            <th>Shortcode</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $video): ?>
                            <tr>
                                <td><?php echo esc_html($video->id); ?></td>
                                <td><?php echo esc_html($video->title); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($video->youtube_url); ?>" target="_blank">
                                        <?php echo $video->is_playlist ? 'Playlist' : 'Video'; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($video->start_date || $video->end_date): ?>
                                        <?php echo $video->start_date ? date('M j, Y', strtotime($video->start_date)) : 'No start'; ?> - 
                                        <?php echo $video->end_date ? date('M j, Y', strtotime($video->end_date)) : 'No end'; ?>
                                    <?php else: ?>
                                        Always active
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($video->view_count); ?></td>
                                <td>
                                    <?php 
                                    $shortcode = $video->is_playlist ? 
                                        "[youtube_playlist id=\"{$video->id}\"]" : 
                                        "[custom_youtube_video id=\"{$video->id}\"]";
                                    ?>
                                    <code class="shortcode-copy" data-shortcode="<?php echo esc_attr($shortcode); ?>" style="cursor: pointer; padding: 5px; background: #f1f1f1; border-radius: 3px;" title="Click to copy">
                                        <?php echo $shortcode; ?>
                                    </code>
                                </td>
                                <td>
                                    <a href="?page=advanced-youtube-video&tab=add&edit=<?php echo $video->id; ?>">Edit</a> | 
                                    <a href="?page=advanced-youtube-video&action=delete&id=<?php echo $video->id; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <div style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                <h3>Shortcode Usage</h3>
                <p><strong>Legacy (backward compatible):</strong> <code>[youtube_video]</code> - Shows the first active video</p>
                <p><strong>Specific video:</strong> <code>[custom_youtube_video id="1"]</code> - Shows video with ID 1</p>
                <p><strong>Playlist:</strong> <code>[youtube_playlist id="1"]</code> - Shows playlist with navigation</p>
                <p><em>💡 Tip: Click on any shortcode in the table above to copy it to your clipboard!</em></p>
            </div>
        </div>
        <?php
    }

    private function render_add_video_tab() {
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $video = null;
        if ($edit_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'youtube_videos';
            $video = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
        }
        ?>
        <div class="tabcontent">
            <h2><?php echo $edit_id ? 'Edit Video' : 'Add New Video'; ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('youtube_video_action', 'youtube_video_nonce'); ?>
                <?php if ($edit_id): ?>
                    <input type="hidden" name="video_id" value="<?php echo $edit_id; ?>">
                    <input type="hidden" name="action" value="update_video">
                <?php else: ?>
                    <input type="hidden" name="action" value="add_video">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="title">Video Title</label></th>
                        <td>
                            <input type="text" id="title" name="title" value="<?php echo $video ? esc_attr($video->title) : ''; ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="youtube_url">YouTube URL</label></th>
                        <td>
                            <input type="url" id="youtube_url" name="youtube_url" value="<?php echo $video ? esc_attr($video->youtube_url) : ''; ?>" class="regular-text" required>
                            <p class="description">Enter video URL or playlist URL</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="start_date">Start Date (Optional)</label></th>
                        <td>
                            <input type="datetime-local" id="start_date" name="start_date" value="<?php echo $video && $video->start_date ? date('Y-m-d\TH:i', strtotime($video->start_date)) : ''; ?>">
                            <p class="description">When should this video become visible?</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="end_date">End Date (Optional)</label></th>
                        <td>
                            <input type="datetime-local" id="end_date" name="end_date" value="<?php echo $video && $video->end_date ? date('Y-m-d\TH:i', strtotime($video->end_date)) : ''; ?>">
                            <p class="description">When should this video be hidden?</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Player Options</th>
                        <td>
                            <label><input type="checkbox" name="autoplay" value="1" <?php echo $video && $video->autoplay ? 'checked' : ''; ?>> Autoplay</label><br>
                            <label><input type="checkbox" name="hide_controls" value="1" <?php echo $video && $video->hide_controls ? 'checked' : ''; ?>> Hide Controls</label><br>
                            <label><input type="checkbox" name="loop_video" value="1" <?php echo $video && $video->loop_video ? 'checked' : ''; ?>> Loop Video</label><br>
                            <label><input type="checkbox" name="mute_video" value="1" <?php echo $video && $video->mute_video ? 'checked' : ''; ?>> Mute Video</label><br>
                            <label><input type="checkbox" name="lazy_load" value="1" <?php echo $video && $video->lazy_load ? 'checked' : ''; ?>> Lazy Load (Show thumbnail first)</label><br>
                            <label><input type="checkbox" name="lightbox" value="1" <?php echo $video && $video->lightbox ? 'checked' : ''; ?>> Open in Lightbox</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="start_time">Start Time (seconds)</label></th>
                        <td>
                            <input type="number" id="start_time" name="start_time" value="<?php echo $video ? $video->start_time : 0; ?>" min="0">
                            <p class="description">Start video at specific time (in seconds)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button($edit_id ? 'Update Video' : 'Add Video'); ?>
            </form>
        </div>
        <?php
    }

    private function render_analytics_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        // Fetch all videos and their view counts
        $videos = $wpdb->get_results("SELECT id, title, view_count, is_playlist, video_id, youtube_url FROM $table_name ORDER BY view_count DESC");
        // Fetch view logs for trends (simulate with dummy data if not available)
        // For this example, we'll assume a table 'youtube_video_views' exists with columns: id, video_id, view_date (Y-m-d), views
        $view_logs = $wpdb->get_results("SELECT video_id, view_date, SUM(views) as views FROM {$wpdb->prefix}youtube_video_views GROUP BY video_id, view_date ORDER BY view_date ASC");
        ?>
        <div class="tabcontent">
            <h2>Video Analytics</h2>
            <div id="yt-analytics-dashboard">
                <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
                    <a href="#" class="nav-tab nav-tab-active" data-tab="overview">Overview</a>
                    <a href="#" class="nav-tab" data-tab="video-stats">Video Stats</a>
                    <a href="#" class="nav-tab" data-tab="trends">Trends</a>
                    <a href="#" class="nav-tab" data-tab="export">Export Reports</a>
                </h2>
                <!-- Overview Tab -->
                <div class="yt-analytics-tab" id="yt-analytics-overview" style="display:block;">
                    <h3>Summary</h3>
                    <ul style="display:flex;gap:40px;list-style:none;padding:0;">
                        <li><strong>Total Videos:</strong> <?php echo count($videos); ?></li>
                        <li><strong>Total Views:</strong> <?php echo number_format(array_sum(array_map(function($v){return $v->view_count;}, $videos))); ?></li>
                        <li><strong>Most Viewed:</strong> <?php echo esc_html($videos[0]->title ?? 'N/A'); ?></li>
                    </ul>
                    <h3 style="margin-top:30px;">Top 5 Videos by Views</h3>
                    <canvas id="yt-top5-bar" height="200"></canvas>
                </div>
                <!-- Video Stats Tab -->
                <div class="yt-analytics-tab" id="yt-analytics-video-stats" style="display:none;">
                    <h3>All Videos</h3>
                    <div style="margin-bottom:15px;">
                        <label for="yt-stats-date-range">Date Range:</label>
                        <select id="yt-stats-date-range">
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="custom">Custom</option>
                        </select>
                        <span id="yt-custom-range" style="display:none;">
                            <input type="text" id="yt-date-start" placeholder="Start date" style="width:110px;"> -
                            <input type="text" id="yt-date-end" placeholder="End date" style="width:110px;">
                        </span>
                    </div>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Video Title</th>
                                <th>Total Views</th>
                                <th>Shortcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($videos as $video): ?>
                            <tr>
                                <td><a href="#" class="yt-video-title" data-id="<?php echo $video->id; ?>" data-title="<?php echo esc_attr($video->title); ?>" data-url="<?php echo esc_url($video->youtube_url); ?>" data-videoid="<?php echo esc_attr($video->video_id); ?>" data-isplaylist="<?php echo $video->is_playlist; ?>"><?php echo esc_html($video->title); ?></a></td>
                                <td><?php echo number_format($video->view_count); ?></td>
                                <td>
                                    <?php 
                                    $shortcode = $video->is_playlist ? 
                                        "[youtube_playlist id=\"{$video->id}\"]" : 
                                        "[custom_youtube_video id=\"{$video->id}\"]";
                                    ?>
                                    <code class="shortcode-copy" data-shortcode="<?php echo esc_attr($shortcode); ?>" style="cursor: pointer; padding: 5px; background: #f1f1f1; border-radius: 3px;" title="Click to copy"><?php echo $shortcode; ?></code>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Trends Tab -->
                <div class="yt-analytics-tab" id="yt-analytics-trends" style="display:none;">
                    <h3>Views Over Time</h3>
                    <div style="margin-bottom:15px;">
                        <label for="yt-trends-video">Select Video:</label>
                        <select id="yt-trends-video">
                            <?php foreach ($videos as $video): ?>
                                <option value="<?php echo $video->id; ?>"><?php echo esc_html($video->title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="yt-trends-range" style="margin-left:20px;">Date Range:</label>
                        <select id="yt-trends-range">
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="custom">Custom</option>
                        </select>
                        <span id="yt-trends-custom-range" style="display:none;">
                            <input type="text" id="yt-trends-date-start" placeholder="Start date" style="width:110px;"> -
                            <input type="text" id="yt-trends-date-end" placeholder="End date" style="width:110px;">
                        </span>
                    </div>
                    <canvas id="yt-trends-line" height="200"></canvas>
                </div>
                <!-- Export Tab -->
                <div class="yt-analytics-tab" id="yt-analytics-export" style="display:none;">
                    <h3>Export Analytics Data</h3>
                    <button class="button button-primary" id="yt-export-csv">Export as CSV</button>
                </div>
            </div>
            <!-- Modal for video details -->
            <div id="yt-analytics-modal" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);">
                <div style="background:#fff;max-width:600px;margin:60px auto;padding:30px;position:relative;border-radius:8px;">
                    <span id="yt-analytics-modal-close" style="position:absolute;top:10px;right:20px;cursor:pointer;font-size:24px;">&times;</span>
                    <div id="yt-analytics-modal-content"></div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        jQuery(function($){
            // Tab switching
            $('#yt-analytics-dashboard .nav-tab').on('click', function(e){
                e.preventDefault();
                var tab = $(this).data('tab');
                $('#yt-analytics-dashboard .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.yt-analytics-tab').hide();
                $('#yt-analytics-' + tab).show();
            });
            // Date pickers
            $('#yt-stats-date-range, #yt-trends-range').on('change', function(){
                var val = $(this).val();
                var custom = $(this).attr('id').indexOf('trends') !== -1 ? '#yt-trends-custom-range' : '#yt-custom-range';
                if(val==='custom') $(custom).show(); else $(custom).hide();
            });
            $('#yt-date-start, #yt-date-end, #yt-trends-date-start, #yt-trends-date-end').datepicker({dateFormat:'yy-mm-dd'});
            // Top 5 bar chart
            var top5 = <?php echo json_encode(array_slice($videos,0,5)); ?>;
            var ctxBar = document.getElementById('yt-top5-bar').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: top5.map(v=>v.title),
                    datasets: [{
                        label: 'Views',
                        data: top5.map(v=>v.view_count),
                        backgroundColor: 'rgba(54,162,235,0.7)'
                    }]
                },
                options: {indexAxis:'y',responsive:true,plugins:{legend:{display:false}}}
            });
            // Trends line chart (dummy data for now)
            var allLogs = <?php echo json_encode($view_logs); ?>;
            function getTrendsData(videoId, days, start, end) {
                var logs = allLogs.filter(l=>l.video_id==videoId);
                var dateMap = {};
                logs.forEach(l=>{dateMap[l.view_date]=parseInt(l.views)});
                var labels = [], data = [];
                var d0 = start ? new Date(start) : new Date();
                var d1 = end ? new Date(end) : new Date();
                if(!start||!end){
                    d1 = new Date();
                    d0 = new Date();
                    d0.setDate(d1.getDate()-days+1);
                }
                for(var d=new Date(d0);d<=d1;d.setDate(d.getDate()+1)){
                    var ds = d.toISOString().slice(0,10);
                    labels.push(ds);
                    data.push(dateMap[ds]||0);
                }
                return {labels,data};
            }
            var trendsChart;
            function renderTrends(){
                var vid = $('#yt-trends-video').val();
                var range = $('#yt-trends-range').val();
                var start = $('#yt-trends-date-start').val();
                var end = $('#yt-trends-date-end').val();
                var days = range==='custom'?null:parseInt(range);
                var d = getTrendsData(vid, days||7, start, end);
                if(trendsChart) trendsChart.destroy();
                var ctx = document.getElementById('yt-trends-line').getContext('2d');
                trendsChart = new Chart(ctx, {
                    type:'line',
                    data:{labels:d.labels,datasets:[{label:'Views',data:d.data,fill:true,backgroundColor:'rgba(54,162,235,0.1)',borderColor:'rgba(54,162,235,1)'}]},
                    options:{responsive:true,plugins:{legend:{display:false}}}
                });
            }
            $('#yt-trends-video,#yt-trends-range,#yt-trends-date-start,#yt-trends-date-end').on('change',renderTrends);
            renderTrends();
            // Modal for video details
            $('.yt-video-title').on('click',function(e){
                e.preventDefault();
                var id = $(this).data('id');
                var title = $(this).data('title');
                var url = $(this).data('url');
                var videoid = $(this).data('videoid');
                var isplaylist = $(this).data('isplaylist');
                var embed = isplaylist==1
                    ? '<iframe width="100%" height="315" src="https://www.youtube.com/embed/videoseries?list='+videoid+'" frameborder="0" allowfullscreen></iframe>'
                    : '<iframe width="100%" height="315" src="https://www.youtube.com/embed/'+videoid+'" frameborder="0" allowfullscreen></iframe>';
                var stats = '';
                var logs = allLogs.filter(l=>l.video_id==id);
                var total = logs.reduce((a,b)=>a+parseInt(b.views),0);
                stats += '<p><strong>Total Views:</strong> '+total+'</p>';
                stats += '<h4>Views Over Time</h4>';
                stats += '<ul style="max-height:120px;overflow:auto;">'+logs.map(l=>'<li>'+l.view_date+': '+l.views+'</li>').join('')+'</ul>';
                $('#yt-analytics-modal-content').html('<h3>'+title+'</h3>'+embed+stats);
                $('#yt-analytics-modal').show();
            });
            $('#yt-analytics-modal-close').on('click',function(){$('#yt-analytics-modal').hide();});
            // Export CSV
            $('#yt-export-csv').on('click',function(e){
                e.preventDefault();
                var csv = 'Video Title,Total Views\n';
                <?php foreach ($videos as $video): ?>
                csv += '<?php echo addslashes($video->title); ?>,<?php echo $video->view_count; ?>\n';
                <?php endforeach; ?>
                var blob = new Blob([csv],{type:'text/csv'});
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'youtube-analytics.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            });
        });
        </script>
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <?php
    }

    private function render_settings_tab() {
        $settings = get_option($this->settings_option, $this->get_default_settings());
        ?>
        <div class="tabcontent">
            <h2>Global Settings</h2>
            <form method="post" action="">
                <?php wp_nonce_field('youtube_settings_action', 'youtube_settings_nonce'); ?>
                <input type="hidden" name="action" value="update_settings">
                <table class="form-table">
                    <tr>
                        <th scope="row">Player Behavior</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="default_lazy_load" value="1" <?php checked($settings['default_lazy_load'], 1); ?>>
                                    Enable lazy loading by default
                                </label>
                                <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Show video thumbnail first, load player only when clicked"></span>
                                <br><br>
                                
                                <label>
                                    <input type="checkbox" name="default_autoplay" value="1" <?php checked($settings['default_autoplay'], 1); ?>>
                                    Autoplay videos
                                </label>
                                <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Automatically start playing videos when they load"></span>
                                <br><br>
                                
                                <label>
                                    <input type="checkbox" name="default_loop" value="1" <?php checked($settings['default_loop'], 1); ?>>
                                    Loop playback
                                </label>
                                <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Automatically restart video when it ends"></span>
                                <br><br>
                                
                                <label>
                                    <input type="checkbox" name="default_hide_controls" value="1" <?php checked($settings['default_hide_controls'], 1); ?>>
                                    Hide YouTube controls
                                </label>
                                <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Hide the YouTube player controls (play/pause, volume, etc.)"></span>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Analytics & Tracking</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="default_analytics" value="1" <?php checked($settings['default_analytics'], 1); ?>>
                                    Enable view tracking by default
                                </label>
                                <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Track video views for analytics dashboard"></span>
                                <br><br>
                                
                                <label>
                                    <input type="checkbox" name="count_views_after_10s" value="1" <?php checked($settings['count_views_after_10s'], 1); ?>>
                                    Count only views > 10s
                                </label>
                                <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Only count a view if user watches for more than 10 seconds"></span>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Display Options</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="default_lightbox" value="1" <?php checked($settings['default_lightbox'], 1); ?>>
                                    Open videos in lightbox
                                </label>
                                <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Open videos in a popup overlay instead of inline"></span>
                                <br><br>
                                
                                <label>
                                    <input type="checkbox" name="use_dark_theme" value="1" <?php checked($settings['use_dark_theme'], 1); ?>>
                                    Use dark theme
                                </label>
                                <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Apply dark styling to video players and modals"></span>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_aspect_ratio">Default Aspect Ratio</label>
                        </th>
                        <td>
                            <select name="default_aspect_ratio" id="default_aspect_ratio">
                                <option value="16:9" <?php selected($settings['default_aspect_ratio'], '16:9'); ?>>16:9 (Widescreen)</option>
                                <option value="4:3" <?php selected($settings['default_aspect_ratio'], '4:3'); ?>>4:3 (Standard)</option>
                            </select>
                            <span class="dashicons dashicons-editor-help" style="color: #0073aa; cursor: help;" title="Default aspect ratio for video players"></span>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings">
                    <input type="submit" name="restore_defaults" id="restore_defaults" class="button button-secondary" value="Restore Defaults" style="margin-left: 10px;">
                </p>
            </form>
            
            <div style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                <h3>Settings Information</h3>
                <p><strong>Note:</strong> These settings will be applied as defaults when adding new videos. Existing videos will retain their individual settings.</p>
                <p><strong>Lazy Loading:</strong> Improves page load speed by showing thumbnails first.</p>
                <p><strong>View Tracking:</strong> Enables analytics dashboard with view statistics.</p>
                <p><strong>Lightbox:</strong> Opens videos in a modal overlay for better user experience.</p>
            </div>
        </div>
        <?php
    }

    private function get_default_settings() {
        return array(
            'default_lazy_load' => 0,
            'default_analytics' => 1,
            'default_autoplay' => 0,
            'default_loop' => 0,
            'default_hide_controls' => 1,
            'use_dark_theme' => 0,
            'default_aspect_ratio' => '16:9',
            'count_views_after_10s' => 0,
            'default_lightbox' => 1
        );
    }

    private function handle_form_submission() {
        if (!isset($_POST['action'])) {
            return;
        }
        $action = $_POST['action'];
        if ($action === 'add_video' || $action === 'update_video') {
            if (!wp_verify_nonce($_POST['youtube_video_nonce'], 'youtube_video_action')) {
                wp_die('Security check failed');
            }
            $this->save_video();
        } elseif ($action === 'update_settings') {
            if (!wp_verify_nonce($_POST['youtube_settings_nonce'], 'youtube_settings_action')) {
                wp_die('Security check failed');
            }
            $this->save_settings();
        }
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $this->delete_video(intval($_GET['id']));
        }
    }

    private function save_settings() {
        if (isset($_POST['restore_defaults'])) {
            update_option($this->settings_option, $this->get_default_settings());
            echo '<div class="notice notice-success is-dismissible"><p>Settings restored to defaults!</p></div>';
            return;
        }
        
        $settings = array(
            'default_lazy_load' => isset($_POST['default_lazy_load']) ? 1 : 0,
            'default_analytics' => isset($_POST['default_analytics']) ? 1 : 0,
            'default_autoplay' => isset($_POST['default_autoplay']) ? 1 : 0,
            'default_loop' => isset($_POST['default_loop']) ? 1 : 0,
            'default_hide_controls' => isset($_POST['default_hide_controls']) ? 1 : 0,
            'use_dark_theme' => isset($_POST['use_dark_theme']) ? 1 : 0,
            'default_aspect_ratio' => sanitize_text_field($_POST['default_aspect_ratio']),
            'count_views_after_10s' => isset($_POST['count_views_after_10s']) ? 1 : 0,
            'default_lightbox' => isset($_POST['default_lightbox']) ? 1 : 0
        );
        
        update_option($this->settings_option, $settings);
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }

    private function save_video() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $youtube_url = sanitize_url($_POST['youtube_url']);
        $video_id = $this->extract_youtube_id($youtube_url);
        $playlist_id = $this->extract_playlist_id($youtube_url);
        $is_playlist = !empty($playlist_id);
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'youtube_url' => $youtube_url,
            'video_id' => $video_id,
            'playlist_id' => $playlist_id,
            'is_playlist' => $is_playlist ? 1 : 0,
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'autoplay' => isset($_POST['autoplay']) ? 1 : 0,
            'hide_controls' => isset($_POST['hide_controls']) ? 1 : 0,
            'loop_video' => isset($_POST['loop_video']) ? 1 : 0,
            'start_time' => intval($_POST['start_time']),
            'mute_video' => isset($_POST['mute_video']) ? 1 : 0,
            'lazy_load' => isset($_POST['lazy_load']) ? 1 : 0,
            'lightbox' => isset($_POST['lightbox']) ? 1 : 0,
        );
        if (isset($_POST['video_id']) && $_POST['video_id']) {
            $wpdb->update($table_name, $data, array('id' => intval($_POST['video_id'])));
            echo '<div class="notice notice-success is-dismissible"><p>Video updated successfully!</p></div>';
        } else {
            $result = $wpdb->insert($table_name, $data);
            if ($result) {
                $new_video_id = $wpdb->insert_id;
                $shortcode = $is_playlist ? 
                    "[youtube_playlist id=\"{$new_video_id}\"]" : 
                    "[custom_youtube_video id=\"{$new_video_id}\"]";
                echo '<div class="notice notice-success is-dismissible">
                    <p>Video added successfully! 
                    <strong>Shortcode:</strong> 
                    <code class="shortcode-copy" data-shortcode="' . esc_attr($shortcode) . '" style="cursor: pointer; padding: 2px 5px; background: #fff; border-radius: 3px; margin-left: 5px;" title="Click to copy">' . $shortcode . '</code>
                    <span id="copy-feedback" style="color: green; margin-left: 10px; display: none;">✓ Copied!</span>
                    </p>
                </div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Error adding video. Please try again.</p></div>';
            }
        }
    }

    private function delete_video($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $wpdb->delete($table_name, array('id' => $id));
        echo '<div class="notice notice-success is-dismissible"><p>Video deleted successfully!</p></div>';
    }

    public function render_youtube_video($atts) {
        $legacy_url = get_option($this->option_name, '');
        if (!empty($legacy_url)) {
            return $this->get_youtube_embed($legacy_url);
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $video = $wpdb->get_row("SELECT * FROM $table_name WHERE (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY created_at ASC LIMIT 1");
        if (!$video) {
            return '<p><em>No active YouTube video found.</em></p>';
        }
        return $this->render_video_player($video);
    }

    public function render_custom_youtube_video($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        if (!$atts['id']) {
            return '<p><em>Video ID required.</em></p>';
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $video = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $atts['id']));
        if (!$video) {
            return '<p><em>Video not found.</em></p>';
        }
        $now = current_time('mysql');
        if (($video->start_date && $video->start_date > $now) || ($video->end_date && $video->end_date < $now)) {
            return '<p><em>Video is not currently available.</em></p>';
        }
        return $this->render_video_player($video);
    }

    public function render_youtube_playlist($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        if (!$atts['id']) {
            return '<p><em>Playlist ID required.</em></p>';
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $video = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND is_playlist = 1", $atts['id']));
        if (!$video) {
            return '<p><em>Playlist not found.</em></p>';
        }
        return $this->render_playlist_player($video);
    }

    private function render_video_player($video) {
        $video_id = $video->video_id;
        $params = array();
        if ($video->autoplay) $params[] = 'autoplay=1';
        if ($video->hide_controls) $params[] = 'controls=0';
        if ($video->loop_video) $params[] = 'loop=1&playlist=' . $video_id;
        if ($video->start_time) $params[] = 'start=' . $video->start_time;
        if ($video->mute_video) $params[] = 'mute=1';
        $param_string = !empty($params) ? '?' . implode('&', $params) : '';
        $embed_url = "https://www.youtube.com/embed/{$video_id}{$param_string}";
        if ($video->lazy_load) {
            return $this->render_lazy_video($video, $embed_url);
        }
        if ($video->lightbox) {
            return $this->render_lightbox_video($video, $embed_url);
        }
        $this->track_view($video->id);
        return sprintf(
            '<div class="advanced-youtube-video" data-video-id="%d">
                <div style="position: relative; padding-bottom: 56.25%%; height: 0; overflow: hidden; max-width: 100%%; height: auto;">
                    <iframe 
                        src="%s" 
                        style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%;" 
                        frameborder="0" 
                        allowfullscreen>
                    </iframe>
                </div>
            </div>',
            $video->id,
            esc_url($embed_url)
        );
    }

    private function render_playlist_player($video) {
        $playlist_id = $video->playlist_id;
        $params = array('listType=playlist', 'list=' . $playlist_id);
        if ($video->autoplay) $params[] = 'autoplay=1';
        if ($video->hide_controls) $params[] = 'controls=0';
        if ($video->mute_video) $params[] = 'mute=1';
        $param_string = '?' . implode('&', $params);
        $embed_url = "https://www.youtube.com/embed/videoseries{$param_string}";
        return sprintf(
            '<div class="advanced-youtube-playlist" data-video-id="%d">
                <div style="position: relative; padding-bottom: 56.25%%; height: 0; overflow: hidden; max-width: 100%%; height: auto;">
                    <iframe 
                        src="%s" 
                        style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%;" 
                        frameborder="0" 
                        allowfullscreen>
                    </iframe>
                </div>
            </div>',
            $video->id,
            esc_url($embed_url)
        );
    }

    private function render_lazy_video($video, $embed_url) {
        $thumbnail_url = "https://img.youtube.com/vi/{$video->video_id}/maxresdefault.jpg";
        return sprintf(
            '<div class="advanced-youtube-lazy" data-video-id="%d" data-embed-url="%s">
                <div style="position: relative; padding-bottom: 56.25%%; height: 0; overflow: hidden; max-width: 100%%; height: auto; cursor: pointer; background: #000;">
                    <img src="%s" style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%; object-fit: cover;">
                    <div style="position: absolute; top: 50%%; left: 50%%; transform: translate(-50%%, -50%%); background: rgba(0,0,0,0.8); border-radius: 50%%; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                        <div style="width: 0; height: 0; border-left: 25px solid white; border-top: 15px solid transparent; border-bottom: 15px solid transparent; margin-left: 5px;"></div>
                    </div>
                </div>
            </div>',
            $video->id,
            esc_url($embed_url),
            esc_url($thumbnail_url)
        );
    }

    private function render_lightbox_video($video, $embed_url) {
        $thumbnail_url = "https://img.youtube.com/vi/{$video->video_id}/maxresdefault.jpg";
        return sprintf(
            '<div class="advanced-youtube-lightbox-trigger" data-video-id="%d" data-embed-url="%s">
                <img src="%s" style="max-width: 100%%; height: auto; cursor: pointer;">
                <p style="text-align: center; margin-top: 10px;">
                    <button class="button" onclick="openVideoLightbox(%d, \'%s\')">▶ Play Video</button>
                </p>
            </div>',
            $video->id,
            esc_url($embed_url),
            esc_url($thumbnail_url),
            $video->id,
            esc_url($embed_url)
        );
    }

    private function track_view($video_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET view_count = view_count + 1 WHERE id = %d",
            $video_id
        ));
        // Log to youtube_video_views table (per day)
        $views_table = $wpdb->prefix . 'youtube_video_views';
        $today = current_time('Y-m-d');
        // Try to update existing row
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE $views_table SET views = views + 1 WHERE video_id = %d AND view_date = %s",
            $video_id, $today
        ));
        // If no row was updated, insert new row
        if ($updated === 0) {
            $wpdb->insert($views_table, array(
                'video_id' => $video_id,
                'view_date' => $today,
                'views' => 1
            ));
        }
    }

    public function track_video_play() {
        if (!wp_verify_nonce($_POST['nonce'], 'youtube_video_nonce')) {
            wp_die('Security check failed');
        }
        $video_id = intval($_POST['video_id']);
        $this->track_view($video_id);
        wp_die();
    }

    private function get_youtube_embed($url) {
        $video_id = $this->extract_youtube_id($url);
        if (!$video_id) {
            return '<p><em>Invalid YouTube URL format.</em></p>';
        }
        return sprintf(
            '<div style="position: relative; padding-bottom: 56.25%%; height: 0; overflow: hidden; max-width: 100%%; height: auto;">
                <iframe 
                    src="https://www.youtube.com/embed/%s" 
                    style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%;" 
                    frameborder="0" 
                    allowfullscreen>
                </iframe>
            </div>',
            esc_attr($video_id)
        );
    }

    private function extract_youtube_id($url) {
        $patterns = array(
            '/youtube\\.com\\/watch\\?v=([a-zA-Z0-9_-]+)/',
            '/youtube\\.com\\/embed\\/([a-zA-Z0-9_-]+)/',
            '/youtu\\.be\\/([a-zA-Z0-9_-]+)/',
        );
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return false;
    }

    private function extract_playlist_id($url) {
        $patterns = array(
            '/youtube\\.com\\/playlist\\?list=([a-zA-Z0-9_-]+)/',
            '/youtube\\.com\\/watch\\?.*list=([a-zA-Z0-9_-]+)/',
        );
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return false;
    }
}
} // end class_exists

// Initialize the plugin
new AdvancedYouTubeVideo();

// Plugin activation hook
if (!function_exists('advanced_youtube_video_activate')) {
function advanced_youtube_video_activate() {
    $plugin = new AdvancedYouTubeVideo();
}
}
register_activation_hook(__FILE__, 'advanced_youtube_video_activate');

// Plugin deactivation hook
if (!function_exists('advanced_youtube_video_deactivate')) {
function advanced_youtube_video_deactivate() {
    // Clean up if needed
}
}
register_deactivation_hook(__FILE__, 'advanced_youtube_video_deactivate');

// Plugin uninstall hook
if (!function_exists('advanced_youtube_video_uninstall')) {
function advanced_youtube_video_uninstall() {
    global $wpdb;
    delete_option('simple_youtube_video_url');
    delete_option('advanced_youtube_videos');
    delete_option('advanced_youtube_settings');
    delete_option('advanced_youtube_analytics');
    $table_name = $wpdb->prefix . 'youtube_videos';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
}
register_uninstall_hook(__FILE__, 'advanced_youtube_video_uninstall');

// Add custom CSS and JavaScript inline (since we can't create separate files)
if (!function_exists('advanced_youtube_video_inline_scripts')) {
add_action('wp_footer', 'advanced_youtube_video_inline_scripts');
function advanced_youtube_video_inline_scripts() {
    ?>
    <style>
    .advanced-youtube-lazy:hover {
        opacity: 0.9;
    }
    .advanced-youtube-lightbox {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.9);
    }
    .advanced-youtube-lightbox-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 800px;
        background: #000;
    }
    .advanced-youtube-lightbox-close {
        position: absolute;
        top: -40px;
        right: 0;
        color: white;
        font-size: 35px;
        font-weight: bold;
        cursor: pointer;
    }
    .advanced-youtube-lightbox-close:hover {
        color: #ccc;
    }
    </style>
    <div id="advanced-youtube-lightbox" class="advanced-youtube-lightbox">
        <div class="advanced-youtube-lightbox-content">
            <span class="advanced-youtube-lightbox-close" onclick="closeVideoLightbox()">&times;</span>
            <div id="lightbox-video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;"></div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.shortcode-copy', function(e) {
            e.preventDefault();
            var shortcode = $(this).data('shortcode');
            var $this = $(this);
            var tempTextarea = $('<textarea>');
            $('body').append(tempTextarea);
            tempTextarea.val(shortcode).select();
            try {
                document.execCommand('copy');
                var originalBg = $this.css('background-color');
                $this.css('background-color', '#d4edda');
                var feedback = $this.siblings('#copy-feedback');
                if (feedback.length === 0) {
                    feedback = $('<span id="copy-feedback" style="color: green; margin-left: 10px;">✓ Copied!</span>');
                    $this.after(feedback);
                }
                feedback.show();
                setTimeout(function() {
                    $this.css('background-color', originalBg);
                    feedback.fadeOut();
                }, 2000);
            } catch (err) {
                alert('Shortcode copied: ' + shortcode);
            }
            tempTextarea.remove();
        });
        $('.shortcode-copy').first().each(function() {
            var $notice = $(this).closest('.notice-success');
            if ($notice.length > 0) {
                $(this).trigger('click');
            }
        });
        $('.advanced-youtube-lazy').on('click', function() {
            var container = $(this);
            var embedUrl = container.data('embed-url');
            var videoId = container.data('video-id');
            var iframe = '<iframe src="' + embedUrl + '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>';
            container.html('<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; height: auto;">' + iframe + '</div>');
            if (typeof advancedYouTube !== 'undefined') {
                $.post(advancedYouTube.ajax_url, {
                    action: 'youtube_video_play',
                    video_id: videoId,
                    nonce: advancedYouTube.nonce
                });
            }
        });
    });
    function openVideoLightbox(videoId, embedUrl) {
        var lightbox = document.getElementById('advanced-youtube-lightbox');
        var container = document.getElementById('lightbox-video-container');
        container.innerHTML = '<iframe src="' + embedUrl + '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>';
        lightbox.style.display = 'block';
        if (typeof jQuery !== 'undefined' && typeof advancedYouTube !== 'undefined') {
            jQuery.post(advancedYouTube.ajax_url, {
                action: 'youtube_video_play',
                video_id: videoId,
                nonce: advancedYouTube.nonce
            });
        }
    }
    function closeVideoLightbox() {
        var lightbox = document.getElementById('advanced-youtube-lightbox');
        var container = document.getElementById('lightbox-video-container');
        lightbox.style.display = 'none';
        container.innerHTML = '';
    }
    window.onclick = function(event) {
        var lightbox = document.getElementById('advanced-youtube-lightbox');
        if (event.target == lightbox) {
            closeVideoLightbox();
        }
    }
    </script>
    <?php
}
}