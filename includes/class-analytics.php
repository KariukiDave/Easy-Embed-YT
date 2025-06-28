<?php
/**
 * Analytics Class
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

class YouTubeVideoAnalytics {
    private $database;
    
    public function __construct() {
        $this->database = new YouTubeVideoDatabase();
    }
    
    public function track_view($video_id) {
        $this->database->increment_view_count($video_id);
        $this->database->log_view($video_id);
    }
    
    public function get_view_logs($video_id = null) {
        return $this->database->get_view_logs($video_id);
    }
    
    public function get_top_videos($limit = 5) {
        return $this->database->get_top_videos($limit);
    }
    
    public function get_analytics_summary() {
        // Fetch videos ordered by view_count DESC
        $videos = $this->database->get_videos('view_count DESC');
        $total_videos = count($videos);
        $total_views = array_sum(array_map(function($v) { return $v->view_count; }, $videos));
        $most_viewed = $total_videos > 0 ? $videos[0]->title : 'N/A';
        
        return array(
            'total_videos' => $total_videos,
            'total_views' => $total_views,
            'most_viewed' => $most_viewed
        );
    }
    
    public function export_analytics_csv() {
        $videos = $this->database->get_videos();
        $csv_data = array();
        
        // Add header
        $csv_data[] = array('Video Title', 'Total Views', 'Type', 'Created Date');
        
        // Add video data
        foreach ($videos as $video) {
            $csv_data[] = array(
                $video->title,
                $video->view_count,
                $video->is_playlist ? 'Playlist' : 'Video',
                $video->created_at
            );
        }
        
        return $csv_data;
    }
    
    public function get_trends_data($video_id, $days = 7, $start_date = null, $end_date = null) {
        $logs = $this->get_view_logs($video_id);
        $date_map = array();
        
        // Create date map from logs
        foreach ($logs as $log) {
            $date_map[$log->view_date] = intval($log->views);
        }
        
        $labels = array();
        $data = array();
        
        // Determine date range
        if ($start_date && $end_date) {
            $d0 = new DateTime($start_date);
            $d1 = new DateTime($end_date);
        } else {
            $d1 = new DateTime();
            $d0 = clone $d1;
            $d0->modify("-{$days} days");
        }
        
        // Generate data points
        $current = clone $d0;
        while ($current <= $d1) {
            $date_string = $current->format('Y-m-d');
            $labels[] = $date_string;
            $data[] = isset($date_map[$date_string]) ? $date_map[$date_string] : 0;
            $current->modify('+1 day');
        }
        
        return array(
            'labels' => $labels,
            'data' => $data
        );
    }
} 