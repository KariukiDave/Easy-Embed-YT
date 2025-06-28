<?php
/**
 * Database Operations Class
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

class YouTubeVideoDatabase {
    
    public function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            youtube_url text NOT NULL,
            video_id varchar(50) NOT NULL,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            autoplay tinyint(1) DEFAULT 0,
            hide_controls tinyint(1) DEFAULT 0,
            loop_video tinyint(1) DEFAULT 0,
            start_time int(11) DEFAULT 0,
            mute_video tinyint(1) DEFAULT 0,
            lazy_load tinyint(1) DEFAULT 0,
            lightbox tinyint(1) DEFAULT 0,
            is_playlist tinyint(1) DEFAULT 0,
            playlist_id varchar(50) DEFAULT NULL,
            view_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create youtube_video_views table for analytics
        $views_table = $wpdb->prefix . 'youtube_video_views';
        $sql2 = "CREATE TABLE $views_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            video_id MEDIUMINT(9) NOT NULL,
            view_date DATE NOT NULL,
            views INT(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY video_id (video_id),
            KEY view_date (view_date)
        ) $charset_collate;";
        
        dbDelta($sql2);
    }
    
    public function get_videos($order_by = 'created_at DESC') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY $order_by");
    }
    
    public function get_video($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }
    
    public function get_active_videos() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY created_at ASC");
    }
    
    public function save_video($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        
        $sanitized_data = array(
            'title' => sanitize_text_field($data['title']),
            'youtube_url' => sanitize_url($data['youtube_url']),
            'video_id' => sanitize_text_field($data['video_id']),
            'playlist_id' => isset($data['playlist_id']) ? sanitize_text_field($data['playlist_id']) : null,
            'is_playlist' => isset($data['is_playlist']) ? intval($data['is_playlist']) : 0,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            'autoplay' => isset($data['autoplay']) ? 1 : 0,
            'hide_controls' => isset($data['hide_controls']) ? 1 : 0,
            'loop_video' => isset($data['loop_video']) ? 1 : 0,
            'start_time' => intval($data['start_time']),
            'mute_video' => isset($data['mute_video']) ? 1 : 0,
            'lazy_load' => isset($data['lazy_load']) ? 1 : 0,
            'lightbox' => isset($data['lightbox']) ? 1 : 0,
        );
        
        if (isset($data['id']) && $data['id']) {
            return $wpdb->update($table_name, $sanitized_data, array('id' => intval($data['id'])));
        } else {
            return $wpdb->insert($table_name, $sanitized_data);
        }
    }
    
    public function delete_video($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        return $wpdb->delete($table_name, array('id' => intval($id)));
    }
    
    public function increment_view_count($video_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET view_count = view_count + 1 WHERE id = %d",
            $video_id
        ));
    }
    
    public function log_view($video_id) {
        global $wpdb;
        $views_table = $wpdb->prefix . 'youtube_video_views';
        $today = current_time('Y-m-d');
        
        // Try to update existing row
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE $views_table SET views = views + 1 WHERE video_id = %d AND view_date = %s",
            $video_id, $today
        ));
        
        // If no row was updated, insert new row
        if ($updated === 0) {
            $wpdb->insert($views_table, array(
                'video_id' => $video_id,
                'view_date' => $today,
                'views' => 1
            ));
        }
    }
    
    public function get_view_logs($video_id = null) {
        global $wpdb;
        $views_table = $wpdb->prefix . 'youtube_video_views';
        
        if ($video_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT video_id, view_date, SUM(views) as views FROM $views_table WHERE video_id = %d GROUP BY video_id, view_date ORDER BY view_date ASC",
                $video_id
            ));
        } else {
            return $wpdb->get_results("SELECT video_id, view_date, SUM(views) as views FROM $views_table GROUP BY video_id, view_date ORDER BY view_date ASC");
        }
    }
    
    public function get_top_videos($limit = 5) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'youtube_videos';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, view_count, is_playlist, video_id, youtube_url FROM $table_name ORDER BY view_count DESC LIMIT %d",
            $limit
        ));
    }
} 