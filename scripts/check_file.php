<?php
$filename = $argv[1] ?? '6936637d2d573.png';
$base = __DIR__ . '/../public/uploads/rooms/';
$path = realpath($base . $filename);
if (!$path) {
    echo "File not found: $base$filename\n";
    exit(1);
}
$contents = file_get_contents($path);
$size = filesize($path);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $path);
finfo_close($finfo);
echo "Path: $path\nSize: $size bytes\nMime: $mime\nContents start: " . substr($contents, 0, 32) . "\n";
