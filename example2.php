<?php
/**
 * SLtxt2PNG.php -- create a PNG image from Sensei's Library diagram format
 * Copyright (C) 2001-2009 by
 * Arno Hollosi <ahollosi@xmp.net>, Morten Pahle <morten@pahle.org.uk>
 */

require 'sltxt2png.php';

$diagramsrc = $_REQUEST['text'];

// parse diagram and set up PNG & SGF diagram classes
$diagram = new GoDiagram($diagramsrc);
if (!$diagram->isValid()) {
    die("Invalid diagram.");
}
$png = new GoDiagramPNG($diagram);
$sgf = new GoDiagramSGF($diagram, 'http://example.net/');

if ($_REQUEST['type'] == 'PNG') {
    $img = $png->createPNG();
    Header("Content-type: image/png");
    ImagePng($img);
    ImageDestroy($img);
    exit;
}
elseif ($_REQUEST['type'] == 'thumb') {
    $thumb = $png->createThumbnail();
    Header("Content-type: image/jpeg");
    ImageJPEG($thumb);
    ImageDestroy($thumb);
    exit;
}
elseif ($_REQUEST['type'] == 'SGF') {
    $sgfdata = $sgf->createSGF();
    Header("Content-type: application/x-go-sgf");
    echo $sgfdata;
    exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>SL diagram example</title>
    </head>
    <body>
        <h1>Diagram source</h1>
            <pre><?php echo $diagramsrc; ?></pre>
        <h1>Image data</h1>
        <ul>
<?php
    $title = $diagram->getTitle();
    echo "<li>Title: $title";
    list ($width, $height) = $diagram->getDimensions();
    echo "<li>Dimensions (pixels): $width x $height";
    $linkmap = $diagram->getLinkMap('diagram1');
    echo "<li>Link map: <small><i>" . htmlspecialchars($linkmap) . "</i></small>";
?>
        </ul>
        <h1>Generated image</h1>
            <img src="example2.php?type=PNG" alt="diagram" widht="<?php echo $width; ?>" height="<?php echo $height; ?>">
        <h1>Thumbnail image</h1>
            <img src="example2.php?type=thumb" alt="thumbnail diagram">
        <h1>Generated SGF</h1>
<?php
    $sgfdata = $sgf->createSGF();
    echo '<pre>' . htmlspecialchars($sgfdata) . '</pre>';
?>
        <h1>Linked image</h1>
<?php
    $divwidth = $width + 20;
    echo <<<EOF
$linkmap
<div style="padding:5px;border:1px solid black;width:$divwidth;text-align:center">
<a href='example2.php?type=SGF'><img src="example2.php?type=PNG" alt="diagram" widht="$width" height="$height" usemap="#diagram1" border=0></a>
<p style='background:#ddd'>$title</p>
</div>
EOF;
?>
    </body>
</html>
