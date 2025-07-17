<?php
/**
 * Analytics Tab View
 * 
 * @package AdvancedYouTubeVideoManager
 * @since 2.2.1
 */

if (!defined('ABSPATH')) exit;

$videos = $this->database->get_videos();
$view_logs = $this->analytics->get_view_logs();
$summary = $this->analytics->get_analytics_summary();
?>

<div class="tabcontent">
    <a href="?page=advanced-youtube-video&tab=add" class="button button-primary" style="margin-top:15px;margin-bottom:20px;font-size:16px;">
        + Add New Video / Playlist
    </a>
    <h2>Video Analytics</h2>
    <div id="yt-analytics-dashboard">
        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="#" class="nav-tab nav-tab-active" data-tab="overview">Overview</a>
            <a href="#" class="nav-tab" data-tab="video-stats">Video Stats</a>
            <a href="#" class="nav-tab" data-tab="trends">Trends</a>
            <a href="#" class="nav-tab" data-tab="export">Export Reports</a>
        </h2>
        
        <!-- Overview Tab -->
        <div class="yt-analytics-tab" id="yt-analytics-overview" style="display:block;">
            <h3>Summary</h3>
            <ul style="display:flex;gap:40px;list-style:none;padding:0;">
                <li><strong>Total Videos:</strong> <?php echo $summary['total_videos']; ?></li>
                <li><strong>Total Views:</strong> <?php echo number_format($summary['total_views']); ?></li>
                <li><strong>Most Viewed:</strong> <?php echo esc_html($summary['most_viewed']); ?></li>
            </ul>
            <h3 style="margin-top:30px;">Top 5 Videos by Views</h3>
            <div style="max-width: 600px; margin: 0 auto;">
                <canvas id="yt-top5-bar" height="150"></canvas>
            </div>
        </div>
        
        <!-- Video Stats Tab -->
        <div class="yt-analytics-tab" id="yt-analytics-video-stats" style="display:none;">
            <h3>All Videos</h3>
            <div style="margin-bottom:15px;">
                <label for="yt-stats-date-range">Date Range:</label>
                <select id="yt-stats-date-range">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="custom">Custom</option>
                </select>
                <span id="yt-custom-range" style="display:none;">
                    <input type="text" id="yt-date-start" placeholder="Start date" style="width:110px;"> -
                    <input type="text" id="yt-date-end" placeholder="End date" style="width:110px;">
                </span>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Video Title</th>
                        <th>Total Views</th>
                        <th>Type</th>
                        <th>Shortcode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($videos as $video): ?>
                    <tr>
                        <td><a href="#" class="yt-video-title" data-id="<?php echo $video->id; ?>" data-title="<?php echo esc_attr($video->title); ?>" data-url="<?php echo esc_url($video->youtube_url); ?>" data-videoid="<?php echo esc_attr($video->video_id); ?>" data-isplaylist="<?php echo $video->is_playlist; ?>"><?php echo esc_html($video->title); ?></a></td>
                        <td><?php echo number_format($video->view_count); ?></td>
                        <td><?php echo $video->is_playlist ? 'Playlist' : 'Video'; ?></td>
                        <td>
                            <?php 
                            $shortcode = $video->is_playlist ? 
                                "[ezmbedyt_playlist id=\"{$video->id}\"]" : 
                                "[ezmbedyt_video id=\"{$video->id}\"]";
                            ?>
                            <code class="shortcode-copy" data-shortcode="<?php echo esc_attr($shortcode); ?>" style="cursor: pointer; padding: 5px; background: #f1f1f1; border-radius: 3px;" title="Click to copy"><?php echo $shortcode; ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Trends Tab -->
        <div class="yt-analytics-tab" id="yt-analytics-trends" style="display:none;">
            <h3>Views Over Time</h3>
            <div style="margin-bottom:15px;">
                <label for="yt-trends-video">Select Video:</label>
                <select id="yt-trends-video">
                    <?php foreach ($videos as $video): ?>
                        <option value="<?php echo $video->id; ?>"><?php echo esc_html($video->title); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="yt-trends-range" style="margin-left:20px;">Date Range:</label>
                <select id="yt-trends-range">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="custom">Custom</option>
                </select>
                <span id="yt-trends-custom-range" style="display:none;">
                    <input type="text" id="yt-trends-date-start" placeholder="Start date" style="width:110px;"> -
                    <input type="text" id="yt-trends-date-end" placeholder="End date" style="width:110px;">
                </span>
            </div>
            <div style="max-width: 700px; margin: 0 auto;">
                <canvas id="yt-trends-line" height="180"></canvas>
            </div>
        </div>
        
        <!-- Export Tab -->
        <div class="yt-analytics-tab" id="yt-analytics-export" style="display:none;">
            <h3>Export Analytics Data</h3>
            <button class="button button-primary" id="yt-export-csv">Export as CSV</button>
        </div>
    </div>
    
    <!-- Modal for video details -->
    <div id="yt-analytics-modal" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);">
        <div style="background:#fff;max-width:600px;margin:60px auto;padding:30px;position:relative;border-radius:8px;">
            <span id="yt-analytics-modal-close" style="position:absolute;top:10px;right:20px;cursor:pointer;font-size:24px;">&times;</span>
            <div id="yt-analytics-modal-content"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(function($){
    // Tab switching
    $('#yt-analytics-dashboard .nav-tab').on('click', function(e){
        e.preventDefault();
        var tab = $(this).data('tab');
        $('#yt-analytics-dashboard .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.yt-analytics-tab').hide();
        $('#yt-analytics-' + tab).show();
    });
    
    // Date pickers
    $('#yt-stats-date-range, #yt-trends-range').on('change', function(){
        var val = $(this).val();
        var custom = $(this).attr('id').indexOf('trends') !== -1 ? '#yt-trends-custom-range' : '#yt-custom-range';
        if(val==='custom') $(custom).show(); else $(custom).hide();
    });
    
    $('#yt-date-start, #yt-date-end, #yt-trends-date-start, #yt-trends-date-end').datepicker({dateFormat:'yy-mm-dd'});
    
    // Top 5 bar chart
    var top5 = <?php echo json_encode(array_slice($videos,0,5)); ?>;
    var ctxBar = document.getElementById('yt-top5-bar').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: top5.map(v=>v.title),
            datasets: [{
                label: 'Views',
                data: top5.map(v=>v.view_count),
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(0, 123, 255, 0.5)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#666',
                        font: { size: 11 }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#666',
                        font: { size: 11 },
                        maxTicksLimit: 5
                    }
                }
            }
        }
    });
    
    // Trends line chart
    var allLogs = <?php echo json_encode($view_logs); ?>;
    function getTrendsData(videoId, days, start, end) {
        var logs = allLogs.filter(l=>l.video_id==videoId);
        var dateMap = {};
        logs.forEach(l=>{dateMap[l.view_date]=parseInt(l.views)});
        var labels = [], data = [];
        var d0 = start ? new Date(start) : new Date();
        var d1 = end ? new Date(end) : new Date();
        if(!start||!end){
            d1 = new Date();
            d0 = new Date();
            d0.setDate(d1.getDate()-days+1);
        }
        for(var d=new Date(d0);d<=d1;d.setDate(d.getDate()+1)){
            var ds = d.toISOString().slice(0,10);
            labels.push(ds);
            data.push(dateMap[ds]||0);
        }
        return {labels,data};
    }
    
    var trendsChart;
    function renderTrends(){
        var vid = $('#yt-trends-video').val();
        var range = $('#yt-trends-range').val();
        var start = $('#yt-trends-date-start').val();
        var end = $('#yt-trends-date-end').val();
        var days = range==='custom'?null:parseInt(range);
        var d = getTrendsData(vid, days||7, start, end);
        if(trendsChart) trendsChart.destroy();
        var ctx = document.getElementById('yt-trends-line').getContext('2d');
        trendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: d.labels,
                datasets: [{
                    label: 'Views',
                    data: d.data,
                    fill: true,
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(0, 123, 255, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(0, 123, 255, 0.5)',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#666',
                            font: { size: 11 },
                            maxTicksLimit: 8
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#666',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    
    $('#yt-trends-video,#yt-trends-range,#yt-trends-date-start,#yt-trends-date-end').on('change',renderTrends);
    renderTrends();
    
    // Modal for video details
    $('.yt-video-title').on('click',function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var title = $(this).data('title');
        var url = $(this).data('url');
        var videoid = $(this).data('videoid');
        var isplaylist = $(this).data('isplaylist');
        var embed = isplaylist==1
            ? '<iframe width="100%" height="315" src="https://www.youtube.com/embed/videoseries?list='+videoid+'" frameborder="0" allowfullscreen></iframe>'
            : '<iframe width="100%" height="315" src="https://www.youtube.com/embed/'+videoid+'" frameborder="0" allowfullscreen></iframe>';
        var stats = '';
        var logs = allLogs.filter(l=>l.video_id==id);
        var total = logs.reduce((a,b)=>a+parseInt(b.views),0);
        stats += '<p><strong>Total Views:</strong> '+total+'</p>';
        stats += '<h4>Views Over Time</h4>';
        stats += '<ul style="max-height:120px;overflow:auto;">'+logs.map(l=>'<li>'+l.view_date+': '+l.views+'</li>').join('')+'</ul>';
        $('#yt-analytics-modal-content').html('<h3>'+title+'</h3>'+embed+stats);
        $('#yt-analytics-modal').show();
    });
    
    $('#yt-analytics-modal-close').on('click',function(){$('#yt-analytics-modal').hide();});
    
    // Export CSV
    $('#yt-export-csv').on('click',function(e){
        e.preventDefault();
        var csv = 'Video Title,Total Views,Type\n';
        <?php foreach ($videos as $video): ?>
        csv += '<?php echo addslashes($video->title); ?>,<?php echo $video->view_count; ?>,<?php echo $video->is_playlist ? 'Playlist' : 'Video'; ?>\n';
        <?php endforeach; ?>
        var blob = new Blob([csv],{type:'text/csv'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'youtube-analytics.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });
});
</script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script> 