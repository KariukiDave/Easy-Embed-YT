<?php
/**
 * Main YouTube Video Manager Class
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

class AdvancedYouTubeVideo {
    private $option_name = 'simple_youtube_video_url'; // Keep for backward compatibility
    private $videos_option = 'advanced_youtube_videos';
    private $settings_option = 'advanced_youtube_settings';
    private $analytics_option = 'advanced_youtube_analytics';
    
    public function __construct() {
        $this->init();
    }
    
    public function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize core functionality
        $this->create_tables();
        $this->setup_hooks();
        $this->register_shortcodes();
    }
    
    private function load_dependencies() {
        // Load admin functionality
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . '../admin/class-admin.php';
            new YouTubeVideoAdmin($this);
        }
        
        // Load core functionality
        require_once plugin_dir_path(__FILE__) . 'class-database.php';
        require_once plugin_dir_path(__FILE__) . 'class-shortcodes.php';
        require_once plugin_dir_path(__FILE__) . 'class-player.php';
        require_once plugin_dir_path(__FILE__) . 'class-analytics.php';
        require_once plugin_dir_path(__FILE__) . 'class-settings.php';
    }
    
    private function setup_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_youtube_video_play', array($this, 'track_video_play'));
        add_action('wp_ajax_nopriv_youtube_video_play', array($this, 'track_video_play'));
    }
    
    private function register_shortcodes() {
        $shortcodes = new YouTubeVideoShortcodes($this);
        $shortcodes->register();
    }
    
    private function create_tables() {
        $database = new YouTubeVideoDatabase();
        $database->create_tables();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('advanced-youtube-video', plugin_dir_url(__FILE__) . '../assets/script.js', array('jquery'), '2.2.1', true);
        wp_localize_script('advanced-youtube-video', 'advancedYouTube', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('youtube_video_nonce')
        ));
        wp_enqueue_style('advanced-youtube-video', plugin_dir_url(__FILE__) . '../assets/style.css', array(), '2.2.1');
    }
    
    public function track_video_play() {
        if (!wp_verify_nonce($_POST['nonce'], 'youtube_video_nonce')) {
            wp_die('Security check failed');
        }
        $video_id = intval($_POST['video_id']);
        
        $analytics = new YouTubeVideoAnalytics();
        $analytics->track_view($video_id);
        
        wp_die();
    }
    
    // Getters for other classes to access
    public function get_option_name() {
        return $this->option_name;
    }
    
    public function get_videos_option() {
        return $this->videos_option;
    }
    
    public function get_settings_option() {
        return $this->settings_option;
    }
    
    public function get_analytics_option() {
        return $this->analytics_option;
    }
} 