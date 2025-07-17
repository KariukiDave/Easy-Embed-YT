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
        add_shortcode('ezmbedyt_gallery', array($this, 'render_ezmbedyt_gallery'));
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

    public function render_ezmbedyt_gallery($atts) {
        $settings = (new YouTubeVideoSettings())->get_settings();
        $atts = shortcode_atts(array(
            'ids' => '',
            'columns' => isset($settings['gallery_columns']) ? $settings['gallery_columns'] : 3,
            'spacing' => isset($settings['gallery_spacing']) ? $settings['gallery_spacing'] : 20,
            'per_page' => isset($settings['gallery_per_page']) ? $settings['gallery_per_page'] : 9,
            'page' => 1,
        ), $atts);
        $ids = array_filter(array_map('intval', explode(',', $atts['ids'])));
        $columns = max(1, min(6, intval($atts['columns'])));
        $spacing = max(0, intval($atts['spacing']));
        $per_page = max(1, intval($atts['per_page']));
        $page = max(1, intval($atts['page']));
        if ($ids) {
            $videos = array();
            foreach ($ids as $id) {
                $video = $this->database->get_video($id);
                if ($video) $videos[] = $video;
            }
        } else {
            $videos = $this->database->get_active_videos();
        }
        if (empty($videos)) {
            return '<p><em>No videos found for gallery.</em></p>';
        }
        $total = count($videos);
        $total_pages = ceil($total / $per_page);
        $offset = ($page - 1) * $per_page;
        $videos = array_slice($videos, $offset, $per_page);
        $out = '<div class="ezmbedyt-gallery" style="display:grid;grid-template-columns:repeat(' . $columns . ',1fr);gap:' . $spacing . 'px;">';
        foreach ($videos as $video) {
            $out .= '<div class="ezmbedyt-gallery-item">' . $this->player->render_video_player($video) . '</div>';
        }
        $out .= '</div>';
        $out .= '<style>.ezmbedyt-gallery .advanced-youtube-video{margin-bottom:0;}</style>';
        // Pagination links
        if ($total_pages > 1) {
            $out .= '<div class="ezmbedyt-gallery-pagination" style="margin-top:20px;text-align:center;">';
            $base_url = remove_query_arg('gallery_page');
            for ($i = 1; $i <= $total_pages; $i++) {
                $url = add_query_arg('gallery_page', $i, $base_url);
                $active = ($i == $page) ? 'font-weight:bold;text-decoration:underline;' : '';
                $out .= '<a href="' . esc_url($url) . '" style="margin:0 6px;' . $active . '">' . $i . '</a>';
            }
            $out .= '</div>';
        }
        return $out;
    }
} 