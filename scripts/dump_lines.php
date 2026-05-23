<?php
if ($argc < 4) {
    echo "Usage: php dump_lines.php <file> <start> <end>\n";
    exit(1);
}
$file = $argv[1];
$start = (int)$argv[2];
$end = (int)$argv[3];
if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit(1);
}
$lines = explode("\n", file_get_contents($file));
for ($i = $start; $i <= $end; $i++) {
    $idx = $i - 1;
    if (!isset($lines[$idx])) continue;
    $line = $lines[$idx];
    printf("%04d: %s\n", $i, $line);
    $bytes = unpack('C*', $line);
    $hex = array_map(function($b){ return sprintf('%02X',$b); }, $bytes ?: []);
    echo implode(' ', $hex), PHP_EOL;
}
