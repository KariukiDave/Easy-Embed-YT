<?php
/**
 * Plugin Name: Simple YouTube Video
 * Plugin URI: https://ascendnbs.com/
 * Description: A simple plugin to embed YouTube videos using shortcodes with admin management.
 * Version: 1.0.0
 * Author: Glowbal Digital
 * Author URI: http://glowbal.co.ke/?ref=YTembedplugin
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SimpleYouTubeVideo {
    
    private $option_name = 'simple_youtube_video_url';
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register shortcode
        add_shortcode('youtube_video', array($this, 'render_youtube_video'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_media_page(
            'YouTube Video Settings',
            'YouTube Video',
            'manage_options',
            'simple-youtube-video',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('simple_youtube_video_settings', $this->option_name);
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>YouTube Video Settings</h1>
            
            <?php
            // Show success message if form was submitted
            if (isset($_GET['settings-updated'])) {
                echo '<div class="notice notice-success is-dismissible"><p>Settings saved!</p></div>';
            }
            ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('simple_youtube_video_settings');
                do_settings_sections('simple_youtube_video_settings');
                $youtube_url = get_option($this->option_name, '');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="youtube_url">YouTube Video URL</label>
                        </th>
                        <td>
                            <input 
                                type="url" 
                                id="youtube_url" 
                                name="<?php echo esc_attr($this->option_name); ?>" 
                                value="<?php echo esc_attr($youtube_url); ?>" 
                                class="regular-text"
                                placeholder="https://www.youtube.com/watch?v=VIDEO_ID"
                            />
                            <p class="description">
                                Enter the full YouTube video URL (e.g., https://www.youtube.com/watch?v=dQw4w9WgXcQ)
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Video URL'); ?>
            </form>
            
            <div style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                <h3>How to Use</h3>
                <p><strong>Shortcode:</strong> <code>[youtube_video]</code></p>
                <p>Add this shortcode to any page, post, or widget area where you want the YouTube video to appear.</p>
                
                <?php if (!empty($youtube_url)): ?>
                    <h4>Preview:</h4>
                    <div style="max-width: 560px;">
                        <?php echo $this->get_youtube_embed($youtube_url); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Shortcode handler
     */
    public function render_youtube_video($atts) {
        $youtube_url = get_option($this->option_name, '');
        
        if (empty($youtube_url)) {
            return '<p><em>No YouTube video configured. Please set one in the admin panel.</em></p>';
        }
        
        return $this->get_youtube_embed($youtube_url);
    }
    
    /**
     * Convert YouTube URL to embed code
     */
    private function get_youtube_embed($url) {
        // Extract video ID from various YouTube URL formats
        $video_id = $this->extract_youtube_id($url);
        
        if (!$video_id) {
            return '<p><em>Invalid YouTube URL format.</em></p>';
        }
        
        // Return responsive embed HTML
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
    
    /**
     * Extract YouTube video ID from URL
     */
    private function extract_youtube_id($url) {
        $patterns = array(
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
}

// Initialize the plugin
new SimpleYouTubeVideo();

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, 'simple_youtube_video_activate');
function simple_youtube_video_activate() {
    // Nothing special needed for activation
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, 'simple_youtube_video_deactivate');
function simple_youtube_video_deactivate() {
    // Clean up if needed
}

/**
 * Plugin uninstall hook
 */
register_uninstall_hook(__FILE__, 'simple_youtube_video_uninstall');
function simple_youtube_video_uninstall() {
    // Remove the saved option when plugin is deleted
    delete_option('simple_youtube_video_url');
}
?>