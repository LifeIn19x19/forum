<?php

$url = $_GET['url'];
if (!preg_match('/^(http|https|ftp):/', $url)) {
    // they're trying to get an unsupported protocol or a local file (BAD!)
    exit;
}

$content = file_get_contents($url, 0, null, -1, 64*1024+2);
if (strlen($content) > 64*1204) {
    // 64 KB limit
    exit;
}

echo $content;

?>
