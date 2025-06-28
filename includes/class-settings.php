<?php
/**
 * Settings Class
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

class YouTubeVideoSettings {
    private $settings_option = 'advanced_youtube_settings';
    
    public function get_default_settings() {
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
    
    public function get_settings() {
        return get_option($this->settings_option, $this->get_default_settings());
    }
    
    public function save_settings($settings) {
        return update_option($this->settings_option, $settings);
    }
    
    public function reset_to_defaults() {
        return update_option($this->settings_option, $this->get_default_settings());
    }
    
    public function get_setting($key, $default = null) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    public function update_setting($key, $value) {
        $settings = $this->get_settings();
        $settings[$key] = $value;
        return $this->save_settings($settings);
    }
} 