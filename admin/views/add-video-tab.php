<?php
/**
 * Add Video Tab View
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$video = null;
if ($edit_id) {
    $video = $this->database->get_video($edit_id);
}

$settings = $this->settings->get_settings();

$just_added = false;
$new_shortcode = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($edit_id) && isset($_POST['action']) && $_POST['action'] === 'add_video' && isset($_POST['title'])) {
    // Try to get the last inserted video
    $last_video = $this->database->get_videos('id DESC');
    if ($last_video && count($last_video) > 0) {
        $v = $last_video[0];
        $just_added = true;
        $new_shortcode = $v->is_playlist ? "[ezmbedyt_playlist id=\"{$v->id}\"]" : "[ezmbedyt_video id=\"{$v->id}\"]";
    }
}
?>

<div class="tabcontent">
    <a href="?page=advanced-youtube-video&tab=add" class="button button-primary" style="margin-top:15px;margin-bottom:20px;font-size:16px;">
        + Add New Video / Playlist
    </a>
    <h2><?php echo $edit_id ? 'Edit Video' : 'Add New Video'; ?></h2>
    <?php if ($just_added): ?>
        <div class="notice notice-success" style="padding:15px 20px 15px 20px;margin-bottom:20px;">
            <strong>Video added!</strong> Shortcode: 
            <code class="shortcode-copy" data-shortcode="<?php echo esc_attr($new_shortcode); ?>" style="cursor:pointer;padding:5px;background:#f1f1f1;border-radius:3px;" title="Click to copy"><?php echo esc_html($new_shortcode); ?></code>
            <span id="copy-feedback" style="color:green;margin-left:10px;display:none;">✓ Copied!</span>
        </div>
    <?php endif; ?>
    
    <form method="post" action="" id="add-video-form">
        <?php wp_nonce_field('youtube_video_action', 'youtube_video_nonce'); ?>
        <?php if ($edit_id): ?>
            <input type="hidden" name="video_id" value="<?php echo $edit_id; ?>">
            <input type="hidden" name="action" value="update_video">
        <?php else: ?>
            <input type="hidden" name="action" value="add_video">
        <?php endif; ?>
        
        <!-- Basic Info Section (Always Visible) -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Basic Information</h2>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label for="title">Video Title *</label></th>
                        <td>
                            <input type="text" id="title" name="title" value="<?php echo $video ? esc_attr($video->title) : ''; ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="youtube_url">YouTube URL *</label></th>
                        <td>
                            <input type="url" id="youtube_url" name="youtube_url" value="<?php echo $video ? esc_attr($video->youtube_url) : ''; ?>" class="regular-text" required placeholder="https://www.youtube.com/watch?v=... or https://www.youtube.com/playlist?list=...">
                            <p class="description">Enter video URL or playlist URL</p>
                            <div id="url-validation" style="display: none; margin-top: 10px;">
                                <div id="validation-success" style="display: none; color: green;">
                                    <span class="dashicons dashicons-yes"></span> Valid YouTube URL
                                </div>
                                <div id="validation-error" style="display: none; color: red;">
                                    <span class="dashicons dashicons-no"></span> Invalid YouTube URL
                                </div>
                            </div>
                            <div id="video-preview" style="display: none; margin-top: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                                <div id="thumbnail-container" style="float: left; margin-right: 15px;"></div>
                                <div id="video-info">
                                    <h4 id="preview-title" style="margin: 0 0 5px 0;"></h4>
                                    <p id="preview-type" style="margin: 0; color: #666;"></p>
                                </div>
                                <div style="clear: both;"></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Player Settings Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Player Settings</h2>
                <div class="handle-actions">
                    <button type="button" class="handlediv" aria-expanded="true">
                        <span class="screen-reader-text">Toggle panel</span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th>Player Options</th>
                        <td>
                            <fieldset>
                                <label><input type="checkbox" name="autoplay" value="1" <?php checked($video ? $video->autoplay : $settings['default_autoplay'], 1); ?>> Autoplay</label><br>
                                <label><input type="checkbox" name="hide_controls" value="1" <?php checked($video ? $video->hide_controls : $settings['default_hide_controls'], 1); ?>> Hide Controls</label><br>
                                <label><input type="checkbox" name="loop_video" value="1" <?php checked($video ? $video->loop_video : $settings['default_loop'], 1); ?>> Loop Video</label><br>
                                <label><input type="checkbox" name="mute_video" value="1" <?php checked($video ? $video->mute_video : 0, 1); ?>> Mute Video</label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Schedule Visibility Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Schedule Visibility</h2>
                <div class="handle-actions">
                    <button type="button" class="handlediv" aria-expanded="false">
                        <span class="screen-reader-text">Toggle panel</span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <div class="inside" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th></th>
                        <td>
                            <label><input type="checkbox" id="enable_scheduling" name="enable_scheduling" value="1" <?php checked(($video && ($video->start_date || $video->end_date)) ? 1 : 0, 1); ?>> Schedule when this video is visible</label>
                        </td>
                    </tr>
                    <tr id="schedule-fields" style="display: none;">
                        <th><label for="start_date">Start Date</label></th>
                        <td>
                            <input type="datetime-local" id="start_date" name="start_date" value="<?php echo $video && $video->start_date ? date('Y-m-d\TH:i', strtotime($video->start_date)) : ''; ?>">
                            <p class="description">When should this video become visible?</p>
                        </td>
                    </tr>
                    <tr id="schedule-fields-end" style="display: none;">
                        <th><label for="end_date">End Date</label></th>
                        <td>
                            <input type="datetime-local" id="end_date" name="end_date" value="<?php echo $video && $video->end_date ? date('Y-m-d\TH:i', strtotime($video->end_date)) : ''; ?>">
                            <p class="description">When should this video be hidden?</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Advanced Options Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Advanced Options</h2>
                <div class="handle-actions">
                    <button type="button" class="handlediv" aria-expanded="false">
                        <span class="screen-reader-text">Toggle panel</span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <div class="inside" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th>Display Options</th>
                        <td>
                            <fieldset>
                                <label><input type="checkbox" name="lazy_load" value="1" <?php checked($video ? $video->lazy_load : $settings['default_lazy_load'], 1); ?>> Lazy Load (Show thumbnail until clicked)</label><br>
                                <label><input type="checkbox" name="lightbox" value="1" <?php checked($video ? $video->lightbox : $settings['default_lightbox'], 1); ?>> Open in Lightbox</label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="start_time">Start Time (seconds)</label></th>
                        <td>
                            <input type="number" id="start_time" name="start_time" value="<?php echo $video ? $video->start_time : 0; ?>" min="0" class="small-text">
                            <p class="description">Start playback at this timestamp</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- View Tracking Section (if enabled in global settings) -->
        <?php if ($settings['default_analytics']): ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">View Tracking</h2>
                <div class="handle-actions">
                    <button type="button" class="handlediv" aria-expanded="false">
                        <span class="screen-reader-text">Toggle panel</span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <div class="inside" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th></th>
                        <td>
                            <label><input type="checkbox" name="track_views" value="1" <?php checked($video ? ($video->track_views ?? 1) : 1, 1); ?>> Track views for this video</label>
                            <p class="description">View analytics will be available in the Analytics tab</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php submit_button($edit_id ? 'Update Video' : 'Add Video'); ?>
    </form>
</div>

<style>
.postbox {
    margin-bottom: 20px;
}
.postbox .inside {
    padding: 12px;
}
.postbox-header {
    border-bottom: 1px solid #ccd0d4;
    padding: 8px 12px;
    background: #f6f7f7;
}
.postbox-header .hndle {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}
.handle-actions {
    float: right;
}
.handlediv {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
}
.toggle-indicator {
    font-family: dashicons;
    font-size: 20px;
    line-height: 1;
    color: #787c82;
}
.toggle-indicator:before {
    content: "\f140";
}
.handlediv[aria-expanded="false"] .toggle-indicator:before {
    content: "\f139";
}
#video-preview img {
    max-width: 120px;
    height: auto;
    border-radius: 4px;
}
#url-validation .dashicons {
    margin-right: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Collapsible sections
    $('.handlediv').on('click', function() {
        var $postbox = $(this).closest('.postbox');
        var $inside = $postbox.find('.inside');
        var $button = $(this);
        var isExpanded = $button.attr('aria-expanded') === 'true';
        
        if (isExpanded) {
            $inside.slideUp();
            $button.attr('aria-expanded', 'false');
        } else {
            $inside.slideDown();
            $button.attr('aria-expanded', 'true');
        }
    });

    // Schedule visibility toggle
    $('#enable_scheduling').on('change', function() {
        if ($(this).is(':checked')) {
            $('#schedule-fields, #schedule-fields-end').show();
        } else {
            $('#schedule-fields, #schedule-fields-end').hide();
        }
    });

    // Initialize schedule fields visibility
    if ($('#enable_scheduling').is(':checked')) {
        $('#schedule-fields, #schedule-fields-end').show();
    }

    // YouTube URL validation and preview
    var validationTimer;
    $('#youtube_url').on('input paste', function() {
        clearTimeout(validationTimer);
        var url = $(this).val();
        
        if (url.length > 10) {
            validationTimer = setTimeout(function() {
                validateYouTubeUrl(url);
            }, 500);
        } else {
            $('#url-validation, #video-preview').hide();
        }
    });

    function validateYouTubeUrl(url) {
        var videoId = extractVideoId(url);
        var playlistId = extractPlaylistId(url);
        
        if (videoId) {
            showValidationSuccess('Video');
            fetchVideoInfo(videoId, 'video');
        } else if (playlistId) {
            showValidationSuccess('Playlist');
            fetchVideoInfo(playlistId, 'playlist');
        } else {
            showValidationError();
            $('#video-preview').hide();
        }
    }

    function extractVideoId(url) {
        var regex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
        var match = url.match(regex);
        return match ? match[1] : null;
    }

    function extractPlaylistId(url) {
        var regex = /(?:youtube\.com\/.*[?&]list=)([^"&?\/\s]{34})/;
        var match = url.match(regex);
        return match ? match[1] : null;
    }

    function showValidationSuccess(type) {
        $('#validation-error').hide();
        $('#validation-success').show().text('✓ Valid YouTube ' + type + ' URL');
        $('#url-validation').show();
    }

    function showValidationError() {
        $('#validation-success').hide();
        $('#validation-error').show().text('✗ Invalid YouTube URL');
        $('#url-validation').show();
    }

    function fetchVideoInfo(id, type) {
        var thumbnailUrl = type === 'video' 
            ? 'https://img.youtube.com/vi/' + id + '/default.jpg'
            : 'https://img.youtube.com/vi/' + id + '/default.jpg';
        
        var title = type === 'video' ? 'Video: ' + id : 'Playlist: ' + id;
        
        $('#thumbnail-container').html('<img src="' + thumbnailUrl + '" alt="Thumbnail">');
        $('#preview-title').text(title);
        $('#preview-type').text(type.charAt(0).toUpperCase() + type.slice(1));
        $('#video-preview').show();
    }

    // Form validation
    $('#add-video-form').on('submit', function(e) {
        var url = $('#youtube_url').val();
        var videoId = extractVideoId(url);
        var playlistId = extractPlaylistId(url);
        
        if (!videoId && !playlistId) {
            e.preventDefault();
            alert('Please enter a valid YouTube video or playlist URL.');
            $('#youtube_url').focus();
            return false;
        }
    });

    // Copy to clipboard for shortcodes
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
            var feedback = $('#copy-feedback');
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
    // Auto-copy if in notice-success
    $('.notice-success .shortcode-copy').each(function() {
        $(this).trigger('click');
    });
});
</script> 