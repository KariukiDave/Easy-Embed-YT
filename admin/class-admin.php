<?php
/**
 * Admin Class
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

class YouTubeVideoAdmin {
    private $plugin;
    private $database;
    private $settings;
    private $analytics;
    private $player;
    
    public function __construct($plugin) {
        $this->plugin = $plugin;
        $this->database = new YouTubeVideoDatabase();
        $this->settings = new YouTubeVideoSettings();
        $this->analytics = new YouTubeVideoAnalytics();
        $this->player = new YouTubeVideoPlayer();
        
        $this->init();
    }
    
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
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
    
    public function admin_enqueue_scripts($hook) {
        if ('media_page_advanced-youtube-video' !== $hook) {
            return;
        }
        
        wp_enqueue_script('advanced-youtube-admin', plugin_dir_url(__FILE__) . '../assets/admin.js', array('jquery'), '2.2.1', true);
        wp_enqueue_style('advanced-youtube-admin', plugin_dir_url(__FILE__) . '../assets/admin.css', array(), '2.2.1');
    }
    
    public function register_settings() {
        register_setting('advanced_youtube_settings', $this->plugin->get_settings_option());
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
    
    private function handle_form_submission() {
        // Bulk delete logic for videos tab
        if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete' && !empty($_POST['video_ids']) && is_array($_POST['video_ids'])) {
            // Security: Only allow on videos tab
            if (isset($_GET['tab']) && $_GET['tab'] === 'videos') {
                foreach ($_POST['video_ids'] as $id) {
                    $this->delete_video(intval($id));
                }
                echo '<div class="notice notice-success is-dismissible"><p>Selected videos deleted successfully!</p></div>';
            }
        }
        
        // Bulk edit logic for videos tab
        if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'bulk_edit_save' && !empty($_POST['video_ids']) && is_array($_POST['video_ids'])) {
            if (isset($_GET['tab']) && $_GET['tab'] === 'videos') {
                $fields = [
                    'autoplay' => 'bulk_autoplay',
                    'hide_controls' => 'bulk_hide_controls',
                    'loop_video' => 'bulk_loop_video',
                    'mute_video' => 'bulk_mute_video',
                    'lazy_load' => 'bulk_lazy_load',
                    'lightbox' => 'bulk_lightbox',
                ];
                $update = [];
                foreach ($fields as $db_field => $post_field) {
                    if (isset($_POST[$post_field])) {
                        $update[$db_field] = 1;
                    }
                }
                if (!empty($update)) {
                    foreach ($_POST['video_ids'] as $id) {
                        $id = intval($id);
                        $video = $this->database->get_video($id);
                        if ($video) {
                            $data = (array)$video;
                            foreach ($update as $k => $v) {
                                $data[$k] = $v;
                            }
                            $this->database->save_video($data);
                        }
                    }
                    echo '<div class="notice notice-success is-dismissible"><p>Selected videos updated successfully!</p></div>';
                }
            }
        }
        
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
    
    private function save_video() {
        $youtube_url = sanitize_url($_POST['youtube_url']);
        $video_id = $this->player->extract_youtube_id($youtube_url);
        $playlist_id = $this->player->extract_playlist_id($youtube_url);
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
            $data['id'] = intval($_POST['video_id']);
            $this->database->save_video($data);
            echo '<div class="notice notice-success is-dismissible"><p>Video updated successfully!</p></div>';
        } else {
            $result = $this->database->save_video($data);
            if (!$result) {
                echo '<div class="notice notice-error is-dismissible"><p>Error adding video. Please try again.</p></div>';
            }
        }
    }
    
    private function save_settings() {
        if (isset($_POST['restore_defaults'])) {
            $this->settings->reset_to_defaults();
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
        
        $this->settings->save_settings($settings);
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    private function delete_video($id) {
        $this->database->delete_video($id);
        echo '<div class="notice notice-success is-dismissible"><p>Video deleted successfully!</p></div>';
    }
    
    // Tab rendering methods will be implemented in separate files
    private function render_videos_tab() {
        require_once plugin_dir_path(__FILE__) . 'views/videos-tab.php';
    }
    
    private function render_add_video_tab() {
        require_once plugin_dir_path(__FILE__) . 'views/add-video-tab.php';
    }
    
    private function render_analytics_tab() {
        require_once plugin_dir_path(__FILE__) . 'views/analytics-tab.php';
    }
    
    private function render_settings_tab() {
        require_once plugin_dir_path(__FILE__) . 'views/settings-tab.php';
    }
} 