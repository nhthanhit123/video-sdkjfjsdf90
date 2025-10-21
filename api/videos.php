<?php
header('Content-Type: application/json');

// Read videos from JSON file
$videosFile = '../data/videos.json';

if (file_exists($videosFile)) {
    $videos = json_decode(file_get_contents($videosFile), true);
    
    // Sort videos by creation date (newest first)
    usort($videos, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    echo json_encode($videos);
} else {
    echo json_encode([]);
}
?>