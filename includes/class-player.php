<?php
/**
 * Player Class
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

class YouTubeVideoPlayer {
    private $database;
    private $analytics;
    
    public function __construct() {
        $this->database = new YouTubeVideoDatabase();
        $this->analytics = new YouTubeVideoAnalytics();
    }
    
    public function render_video_player($video) {
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
        
        // Track view
        $this->analytics->track_view($video->id);
        
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
    
    public function render_playlist_player($video) {
        $playlist_id = $video->playlist_id;
        $params = array('listType=playlist', 'list=' . $playlist_id);
        
        if ($video->autoplay) $params[] = 'autoplay=1';
        if ($video->hide_controls) $params[] = 'controls=0';
        if ($video->mute_video) $params[] = 'mute=1';
        
        $param_string = '?' . implode('&', $params);
        $embed_url = "https://www.youtube.com/embed/videoseries{$param_string}";
        
        // Track playlist view
        $this->analytics->track_view($video->id);
        
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
    
    public function render_lazy_video($video, $embed_url) {
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
    
    public function render_lightbox_video($video, $embed_url) {
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
    
    public function get_youtube_embed($url) {
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
    
    public function extract_youtube_id($url) {
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
    
    public function extract_playlist_id($url) {
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