<?php
// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Read videos data
$videosFile = '../data/videos.json';

if (!file_exists($videosFile)) {
    // Return empty array if file doesn't exist
    echo json_encode([]);
    exit;
}

try {
    $videos = json_decode(file_get_contents($videosFile), true);
    
    if ($videos === null) {
        // Invalid JSON, return empty array
        echo json_encode([]);
        exit;
    }
    
    // Filter out fake videos (they should not appear in the main listing)
    $filteredVideos = array_filter($videos, function($video) {
        return isset($video['type']) && $video['type'] !== 'fake';
    });
    
    // Re-index array
    $filteredVideos = array_values($filteredVideos);
    
    // Sort by creation date (newest first)
    usort($filteredVideos, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    echo json_encode($filteredVideos, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load videos']);
}
?>