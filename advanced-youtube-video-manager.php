<?php
/**
 * Plugin Name: Advanced YouTube Video Manager
 * Plugin URI: https://glowbal.co.ke
 * Description: Advanced YouTube video management with scheduling, playlists, analytics, and multiple video support.
 * Version: 2.7.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Glowbal Digital
 * Author URI: http://glowbal.co.ke/?ref=YTembedplugin
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('YT_VIDEO_MANAGER_VERSION', '2.7.0');
define('YT_VIDEO_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YT_VIDEO_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load the main plugin class
require_once YT_VIDEO_MANAGER_PLUGIN_DIR . 'includes/class-youtube-video-manager.php';

// Initialize the plugin
function yt_video_manager_init() {
    new AdvancedYouTubeVideo();
}
add_action('plugins_loaded', 'yt_video_manager_init');

// Plugin activation hook
function advanced_youtube_video_activate() {
    // Create tables on activation
    $database = new YouTubeVideoDatabase();
    $database->create_tables();
}
register_activation_hook(__FILE__, 'advanced_youtube_video_activate');

// Plugin deactivation hook
function advanced_youtube_video_deactivate() {
    // Clean up if needed
}
register_deactivation_hook(__FILE__, 'advanced_youtube_video_deactivate');

// Plugin uninstall hook
function advanced_youtube_video_uninstall() {
    global $wpdb;
    delete_option('simple_youtube_video_url');
    delete_option('advanced_youtube_videos');
    delete_option('advanced_youtube_settings');
    delete_option('advanced_youtube_analytics');
    $table_name = $wpdb->prefix . 'youtube_videos';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    $views_table = $wpdb->prefix . 'youtube_video_views';
    $wpdb->query("DROP TABLE IF EXISTS $views_table");
}
register_uninstall_hook(__FILE__, 'advanced_youtube_video_uninstall');

// Add custom CSS and JavaScript inline (since we can't create separate files)
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