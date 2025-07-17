<?php
/**
 * Shortcodes Class
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

class YouTubeVideoShortcodes {
    private $plugin;
    private $database;
    private $player;
    
    public function __construct($plugin) {
        $this->plugin = $plugin;
        $this->database = new YouTubeVideoDatabase();
        $this->player = new YouTubeVideoPlayer();
    }
    
    public function register() {
        add_shortcode('youtube_video', array($this, 'render_youtube_video'));
        add_shortcode('custom_youtube_video', array($this, 'render_custom_youtube_video'));
        add_shortcode('youtube_playlist', array($this, 'render_youtube_playlist'));
        add_shortcode('ezmbedyt_video', array($this, 'render_custom_youtube_video'));
        add_shortcode('ezmbedyt_playlist', array($this, 'render_youtube_playlist'));
    }
    
    public function render_youtube_video($atts) {
        $legacy_url = get_option($this->plugin->get_option_name(), '');
        if (!empty($legacy_url)) {
            return $this->player->get_youtube_embed($legacy_url);
        }
        
        $videos = $this->database->get_active_videos();
        if (empty($videos)) {
            return '<p><em>No active YouTube video found.</em></p>';
        }
        
        $video = $videos[0]; // Get first active video
        return $this->player->render_video_player($video);
    }
    
    public function render_custom_youtube_video($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        if (!$atts['id']) {
            return '<p><em>Video ID required.</em></p>';
        }
        
        $video = $this->database->get_video($atts['id']);
        if (!$video) {
            return '<p><em>Video not found.</em></p>';
        }
        
        $now = current_time('mysql');
        if (($video->start_date && $video->start_date > $now) || ($video->end_date && $video->end_date < $now)) {
            return '<p><em>Video is not currently available.</em></p>';
        }
        
        return $this->player->render_video_player($video);
    }
    
    public function render_youtube_playlist($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        if (!$atts['id']) {
            return '<p><em>Playlist ID required.</em></p>';
        }
        
        $video = $this->database->get_video($atts['id']);
        if (!$video || !$video->is_playlist) {
            return '<p><em>Playlist not found.</em></p>';
        }
        
        return $this->player->render_playlist_player($video);
    }
} 