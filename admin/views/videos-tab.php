<?php
/**
 * Videos Tab View
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

$videos = $this->database->get_videos();
?>

<div class="tabcontent">
    <a href="?page=advanced-youtube-video&tab=add" class="button button-primary" style="margin-top:15px;margin-bottom:20px;font-size:16px;">
        + Add New Video / Playlist
    </a>
    <h2>Video Library</h2>
    <?php if (empty($videos)): ?>
        <p>No videos found. <a href="?page=advanced-youtube-video&tab=add">Add your first video</a>.</p>
    <?php else: ?>
        <?php
        $show_bulk_edit = isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'edit' && !empty($_POST['video_ids']) && is_array($_POST['video_ids']);
        $selected_ids = $show_bulk_edit ? array_map('intval', $_POST['video_ids']) : [];
        ?>
        <form method="post" action="">
            <div style="margin-bottom:10px;display:flex;align-items:center;gap:10px;">
                <select name="bulk_action" style="min-width:120px;">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                    <option value="edit" <?php if ($show_bulk_edit) echo 'selected'; ?>>Edit Settings</option>
                </select>
                <button type="submit" class="button">Apply</button>
            </div>
            <?php if ($show_bulk_edit): ?>
                <div style="background:#f9f9f9;border:1px solid #ddd;padding:20px;margin-bottom:20px;">
                    <h3>Bulk Edit Video Settings</h3>
                    <input type="hidden" name="bulk_action" value="bulk_edit_save">
                    <?php foreach ($selected_ids as $id): ?>
                        <input type="hidden" name="video_ids[]" value="<?php echo esc_attr($id); ?>">
                    <?php endforeach; ?>
                    <table class="form-table">
                        <tr>
                            <th>Autoplay</th>
                            <td><input type="checkbox" name="bulk_autoplay" value="1"> Enable</td>
                        </tr>
                        <tr>
                            <th>Hide Controls</th>
                            <td><input type="checkbox" name="bulk_hide_controls" value="1"> Enable</td>
                        </tr>
                        <tr>
                            <th>Loop Video</th>
                            <td><input type="checkbox" name="bulk_loop_video" value="1"> Enable</td>
                        </tr>
                        <tr>
                            <th>Mute Video</th>
                            <td><input type="checkbox" name="bulk_mute_video" value="1"> Enable</td>
                        </tr>
                        <tr>
                            <th>Lazy Load</th>
                            <td><input type="checkbox" name="bulk_lazy_load" value="1"> Enable</td>
                        </tr>
                        <tr>
                            <th>Lightbox</th>
                            <td><input type="checkbox" name="bulk_lightbox" value="1"> Enable</td>
                        </tr>
                    </table>
                    <button type="submit" class="button button-primary">Update Selected Videos</button>
                </div>
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:30px;"><input type="checkbox" id="select-all-videos"></th>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Video</th>
                        <th>Schedule</th>
                        <th>Views</th>
                        <th>Shortcode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($videos as $video): ?>
                        <tr>
                            <td><input type="checkbox" name="video_ids[]" value="<?php echo esc_attr($video->id); ?>"></td>
                            <td><?php echo esc_html($video->id); ?></td>
                            <td><?php echo esc_html($video->title); ?></td>
                            <td>
                                <a href="<?php echo esc_url($video->youtube_url); ?>" target="_blank">
                                    <?php echo $video->is_playlist ? 'Playlist' : 'Video'; ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($video->start_date || $video->end_date): ?>
                                    <?php echo $video->start_date ? date('M j, Y', strtotime($video->start_date)) : 'No start'; ?> - 
                                    <?php echo $video->end_date ? date('M j, Y', strtotime($video->end_date)) : 'No end'; ?>
                                <?php else: ?>
                                    Always active
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($video->view_count); ?></td>
                            <td>
                                <?php 
                                $shortcode = $video->is_playlist ? 
                                    "[ezmbedyt_playlist id=\"{$video->id}\"]" : 
                                    "[ezmbedyt_video id=\"{$video->id}\"]";
                                ?>
                                <code class="shortcode-copy" data-shortcode="<?php echo esc_attr($shortcode); ?>" style="cursor: pointer; padding: 5px; background: #f1f1f1; border-radius: 3px;" title="Click to copy">
                                    <?php echo $shortcode; ?>
                                </code>
                            </td>
                            <td>
                                <a href="?page=advanced-youtube-video&tab=add&edit=<?php echo $video->id; ?>">Edit</a> | 
                                <a href="?page=advanced-youtube-video&action=delete&id=<?php echo $video->id; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php endif; ?>
    <div style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-left: 4px solid #0073aa;">
        <h3>Shortcode Usage</h3>
        <p><strong>Legacy (backward compatible):</strong> <code>[youtube_video]</code> - Shows the first active video</p>
        <p><strong>Specific video:</strong> <code>[ezmbedyt_video id="1"]</code> - Shows video with ID 1</p>
        <p><strong>Playlist:</strong> <code>[ezmbedyt_playlist id="1"]</code> - Shows playlist with navigation</p>
        <p><em>💡 Tip: Click on any shortcode in the table above to copy it to your clipboard!</em></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkboxes
    $('#select-all-videos').on('change', function() {
        var checked = $(this).is(':checked');
        $('input[name="video_ids[]"]').prop('checked', checked);
    });
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
            var feedback = $this.siblings('.copy-feedback');
            if (feedback.length === 0) {
                feedback = $('<span class="copy-feedback" style="color:green;margin-left:10px;">✓ Copied!</span>');
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
});
</script> 