<?php
/**
 * Videos Tab View
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'order';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$sortable = [
    'order' => '`order`',
    'id' => 'id',
    'title' => 'title',
    'views' => 'view_count',
    'created' => 'created_at',
];
$sort_sql = isset($sortable[$sort]) ? $sortable[$sort] : '`order`';
$order_sql = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
$videos = $this->database->get_videos("$sort_sql $order_sql");
function sort_link($label, $col, $current_sort, $current_order) {
    $next_order = ($current_sort === $col && $current_order === 'asc') ? 'desc' : 'asc';
    $arrow = '';
    if ($current_sort === $col) {
        $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
    }
    $url = add_query_arg(['sort' => $col, 'order' => $next_order]);
    return '<a href="' . esc_url($url) . '">' . esc_html($label) . $arrow . '</a>';
}
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
        <form method="post" action="" id="video-order-form">
            <input type="hidden" name="tab" value="videos">
            <div style="margin-bottom:10px;display:flex;align-items:center;gap:10px;">
                <select name="bulk_action" style="min-width:120px;">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                    <option value="edit" <?php if ($show_bulk_edit) echo 'selected'; ?>>Edit Settings</option>
                </select>
                <button type="submit" class="button">Apply</button>
                <button type="button" id="save-order-btn" class="button button-secondary" style="margin-left:auto;">Save Order</button>
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
            <table class="wp-list-table widefat fixed striped" id="videos-table">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th><?php echo sort_link('ID', 'id', $sort, $order); ?></th>
                        <th><?php echo sort_link('Title', 'title', $sort, $order); ?></th>
                        <th>Video</th>
                        <th>Schedule</th>
                        <th><?php echo sort_link('Views', 'views', $sort, $order); ?></th>
                        <th>Shortcode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($videos as $video): ?>
                        <tr data-id="<?php echo esc_attr($video->id); ?>">
                            <td class="drag-handle" style="cursor:move;">&#9776;</td>
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
            <input type="hidden" name="save_order" value="1" id="save-order-input" disabled>
            <input type="hidden" name="order_ids" id="order-ids-input" value="">
        </form>
    <?php endif; ?>
    <div style="margin-top: 30px; padding: 22px 24px; background: linear-gradient(90deg, #e8f4fa 80%, #d1eaff 100%); border-left: 5px solid #0073aa; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <h2 style="margin-top:0;font-size:1.4em;display:flex;align-items:center;gap:10px;">
            <span style="font-size:1.5em;">🎬</span> Gallery Feature & Shortcode
        </h2>
        <p style="font-size:1.08em;margin-bottom:12px;">
            <strong>Easily display multiple YouTube videos in a beautiful, responsive gallery grid anywhere on your site!</strong>
        </p>
        <div style="margin-bottom:10px;">
            <span style="display:inline-block;width:2.2em;">📋</span>
            <strong>All videos:</strong> <code>[ezmbedyt_gallery]</code>
        </div>
        <div style="margin-bottom:10px;">
            <span style="display:inline-block;width:2.2em;">🎯</span>
            <strong>Specific videos:</strong> <code>[ezmbedyt_gallery ids="1,2,3"]</code>
        </div>
        <div style="margin-bottom:10px;">
            <span style="display:inline-block;width:2.2em;">🛠️</span>
            <strong>Custom layout:</strong> <code>[ezmbedyt_gallery columns="4" spacing="30" per_page="8"]</code>
        </div>
        <div style="margin:14px 0 0 0; color:#155a7a; font-size:0.98em;">
            <span style="font-size:1.1em;">💡</span> <strong>Tip:</strong> You can set default gallery layout (columns, spacing, per page) in the <a href="?page=advanced-youtube-video&tab=settings">plugin settings</a>.
        </div>
        <div style="margin-top:8px;color:#155a7a;font-size:0.98em;">
            <span style="font-size:1.1em;">✨</span> Supports pagination, drag-and-drop ordering, and mobile-friendly design.
        </div>
    </div>
    <div style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-left: 4px solid #0073aa;">
        <h3>Shortcode Usage</h3>
        <p><strong>Legacy (backward compatible):</strong> <code>[youtube_video]</code> - Shows the first active video</p>
        <p><strong>Specific video:</strong> <code>[ezmbedyt_video id="1"]</code> - Shows video with ID 1</p>
        <p><strong>Playlist:</strong> <code>[ezmbedyt_playlist id="1"]</code> - Shows playlist with navigation</p>
        <p><em>💡 Tip: Click on any shortcode in the table above to copy it to your clipboard!</em></p>
    </div>
</div>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
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

    // Drag-and-drop ordering
    $('#videos-table tbody').sortable({
        handle: '.drag-handle',
        helper: function(e, tr) {
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function(index) {
                $(this).width($originals.eq(index).width());
            });
            return $helper;
        },
        update: function(event, ui) {
            $('#save-order-btn').addClass('button-primary');
        }
    }).disableSelection();
    // Save order button
    $('#save-order-btn').on('click', function() {
        var ids = [];
        $('#videos-table tbody tr').each(function() {
            ids.push($(this).data('id'));
        });
        $('#order-ids-input').val(ids.join(','));
        $('#save-order-input').prop('disabled', false);
        $('#video-order-form').submit();
    });
});
</script> 