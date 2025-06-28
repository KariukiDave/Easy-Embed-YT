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
    <h2>Video Library</h2>
    <?php if (empty($videos)): ?>
        <p>No videos found. <a href="?page=advanced-youtube-video&tab=add">Add your first video</a>.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
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