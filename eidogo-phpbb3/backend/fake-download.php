<?php

$data = $_GET['data'] . "\n";
if (get_magic_quotes_gpc())
    $data = stripslashes($data);

$file_size = strlen($data);
if ($file_size > 64*1024)
    exit;

$file_name = sha1($data) . '.sgf';

header("Content-type: application/x-go-sgf");
header("Content-Length: " . $file_size);
header("Content-Disposition: inline; filename=\"$file_name\"");

echo $data;

?>
