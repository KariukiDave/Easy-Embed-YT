<?php
/**
 * Settings Tab View
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

$settings = $this->settings->get_settings();
?>

<div class="tabcontent">
    <a href="?page=advanced-youtube-video&tab=add" class="button button-primary" style="margin-top:15px;margin-bottom:20px;font-size:16px;">
        + Add New Video / Playlist
    </a>
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