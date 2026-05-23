<?php
$s = file_get_contents(__DIR__.'/../templates/admin/base.html.twig');
if (strpos($s, "\xC2\xA0") !== false) {
    echo "has_nbsp\n";
} else {
    echo "no_nbsp\n";
}
$hasNonAscii = preg_match('/[\x80-\xFF]/', $s);
echo $hasNonAscii ? "has_nonascii\n" : "all_ascii\n";
