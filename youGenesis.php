<?php
/**
 * @package Youtube_genesis
 * @version 1.0
 */
/*
Plugin Name: Youtube Genesis
Description: This plugin searches youtube links in posts and gets info about video. 
Author: Cepishmohnoril
Version: 1.0
*/

add_action('save_post', 'find_youtube_link');
add_action('wp_head', 'insert_youtube_og_meta');

function insert_youtube_og_meta ()
{
    if (is_single()) {
        $postId = get_the_ID();
        if (!empty($youtbMeta=get_post_meta($postId, 'youtb_inf'))) {
            echo "<meta property='og:image' content={$youtbMeta[0]['preview']}>";
            echo "<meta property='video:duration' content={$youtbMeta[0]['duration']}>";
        }
    }
}

function find_youtube_link ($postId) //Using 'snake_case' because of Wordpress naming convention.
{
    $videoId = get_youtube_link_id(get_post($postId)->post_content);

    if ($videoId) {
        $videoInfo = get_video_info($videoId);
        if ($videoInfo) {
            update_post_meta($postId, 'youtb_inf', $videoInfo);
        } else {
            $result = false;
        }
    } else {
        $result = false;
    }

    return $result;
}

function get_youtube_link_id ($text)
{
    $pattern = "/https?:\/\/(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube(?:-nocookie)?\.com\S*[^\w\s-])([\w-]{11})(?=[^\w-]|$)(?![?=&+%\w.-]*(?:[\'\"][^<>]*>|<\/a>))[?=&+%\w.-]*/i";
    preg_match($pattern, $text, $matches); //We are looking for only the first link.
    
    if (empty($matches)) {
        $result = false;
    } else {
        $result = $matches['1'];
    }

    return $result;
}

function get_video_info ($videoId)
{
    $apiKey = 'AIzaSyCcizuO4G6c5xGUVmY6zPkuGhxugkL_FOU';
    $snippet = api_request("https://www.googleapis.com/youtube/v3/videos?key={$apiKey}&part=snippet&id={$videoId}");
    $contentDetails = api_request("https://www.googleapis.com/youtube/v3/videos?key={$apiKey}&part=contentDetails&id={$videoId}");
    
    if ($snippet && $contentDetails) {
        $interval = new DateInterval($contentDetails->items['0']->contentDetails->duration);
        $result = [
            'preview' => $snippet->items['0']->snippet->thumbnails->standard->url,
            'duration' => $interval->h*3600 + $interval->i*60 + $interval->s
        ];
    } else {
        $result = false;
    }

    return $result;
}

function api_request($url)
{
    $responseJson = file_get_contents($url);
    $responsetObj = json_decode($responseJson);

    if (is_object($responsetObj)) {
        $response = $responsetObj;
    } else {
        $response = false;
    }

    return $response;
}
