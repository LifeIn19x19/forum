<?php

if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']))
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
    $app_ads_referer = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
else
    $app_ads_referer = $board_url;


// for those forum system which can not add js in html body, please set $functionCallAfterWindowLoad as 1
$functionCallAfterWindowLoad = isset($functionCallAfterWindowLoad) && $functionCallAfterWindowLoad ? 1 : 0;

$app_ios_id_default = '307880732';      // Tapatalk 1, 585178888 for Tapatalk 2
$app_ios_hd_id_default = '307880732';   // Tapatalk 1, 481579541 for Tapatalk HD
$app_android_id_default = 'com.quoord.tapatalkpro.activity';

$app_location_url = isset($app_location_url) && preg_match('#^tapatalk://#i', $app_location_url) ? $app_location_url : 'tapatalk://';
$app_location_url_byo = str_replace('tapatalk://', 'tapatalk-byo://', $app_location_url);
$tapatalk_dir_url = isset($tapatalk_dir_url) && $tapatalk_dir_url ? $tapatalk_dir_url : './mobiquo';
$app_forum_name = isset($app_forum_name) && $app_forum_name ? $app_forum_name : 'this forum';

$app_ios_id = isset($app_ios_id) && intval($app_ios_id) ? intval($app_ios_id) : '';
$app_android_id = isset($app_android_id) && $app_android_id ? preg_replace('/^.*?details\?id=([^\s,&]+).*?$/si', '$1', $app_android_id) : '';
$app_kindle_url = isset($app_kindle_url) ? $app_kindle_url : '';
$app_banner_message = isset($app_banner_message) && $app_banner_message ? preg_replace('/\r\n|\n|\r/si', '<br />', $app_banner_message) : '';
$is_mobile_skin = isset($is_mobile_skin) && $is_mobile_skin ? 1 : 0;


// display twitter card
$twitter_card_head = '';
if ($app_ios_id != -1 || $app_android_id != -1)
{
    $twitter_card_head .= '
        <!-- twitter app card start-->
        <!-- https://dev.twitter.com/docs/cards/types/app-card -->
        <meta name="twitter:card" content="app" />
    ';
    
    if ($app_ios_id != '-1')
    {
        $twitter_card_head .= '
        <meta name="twitter:app:id:iphone" content="'.($app_ios_id ? $app_ios_id : $app_ios_id_default).'" />
        <meta name="twitter:app:url:iphone" content="'.($app_ios_id ? $app_location_url_byo : $app_location_url).'" />
        <meta name="twitter:app:id:ipad" content="'.($app_ios_id ? $app_ios_id : $app_ios_hd_id_default).'">
        <meta name="twitter:app:url:ipad" content="'.($app_ios_id ? $app_location_url_byo : $app_location_url).'" />
        ';
    };
        
    if ($app_android_id != '-1')
    {
        $twitter_card_head .= '
        <meta name="twitter:app:id:googleplay" content="'.($app_android_id ? $app_android_id : $app_android_id_default).'" />
        <meta name="twitter:app:url:googleplay" content="'.($app_android_id ? $app_location_url_byo : $app_location_url).'" />
        ';
    };
    
    $twitter_card_head .= '
    <!-- twitter app card -->
    ';
}

// display smart banner
$app_banner_head = '
    <!-- Tapatalk Banner head start -->
    <link href="'.$tapatalk_dir_url.'/smartbanner/appbanner.css" rel="stylesheet" type="text/css" media="screen" />
    <script type="text/javascript">
        var is_mobile_skin     = '.$is_mobile_skin.';
        var app_ios_id         = "'.$app_ios_id.'";
        var app_android_id     = "'.addslashes($app_android_id).'";
        var app_kindle_url     = "'.addslashes(urlencode($app_kindle_url)).'";
        var app_banner_message = "'.addslashes($app_banner_message).'";
        var app_forum_name     = "'.addslashes($app_forum_name).'";
        var app_location_url   = "'.addslashes($app_location_url).'";
        var app_board_url      = "'.addslashes(urlencode($board_url)).'";
        var functionCallAfterWindowLoad = '.$functionCallAfterWindowLoad.'
    </script>
    <script src="'.$tapatalk_dir_url.'/smartbanner/appbanner.js" type="text/javascript"></script>
    <!-- Tapatalk Banner head end-->
';

// display full app ads
$full_view_ads = '';
if (file_exists($tapatalk_dir . '/smartbanner/ads.php') && $app_ads_enable)
{
    $full_view_ads = '
        <!-- tapatalk full view ads -->
        <script type="text/javascript">
            var app_forum_code = "'.md5($api_key).'";
            var app_ads_referer = "'.addslashes(urlencode($app_ads_referer)).'";
            var app_ads_url = "'.addslashes($tapatalk_dir_url.'/smartbanner/ads.php').'";
        </script>
        <script src="'.$tapatalk_dir_url.'/smartbanner/ads.js" type="text/javascript"></script>
    ';
}

$app_head_include = $twitter_card_head.$app_banner_head.$full_view_ads;
