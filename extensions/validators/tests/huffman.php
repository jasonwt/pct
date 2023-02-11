<?php

class Huffman {
    public static function Compress(string $str) {

    }

    public static function Decompress(string $)
}

function huffman_compress($string)
{
$tree = [];
$l = strlen($string);
for ($i = 0; $i < $l; $i++) {
$char = $string[$i];
if (!isset($tree[$char])) {
$tree[$char] = [
'freq' => 1,
'code' => '',
];
} else {
$tree[$char]['freq']++;
}
}
// sort by frequency
uasort($tree, function ($a, $b) {
return $a['freq'] <=> $b['freq'];
});
// build huffman tree
while (count($tree) > 1) {
$a = array_shift($tree);
$b = array_shift($tree);
$c = [
'freq' => $a['freq'] + $b['freq'],
'code' => '',
];
foreach ($a['code'] as $i => $char) {
$a['code'][$i] = '0' . $char;
}
foreach ($b['code'] as $i => $char) {
$b['code'][$i] = '1' . $char;
}
$c['code'] = array_merge($a['code'], $b['code']);
$tree[] = $c;
uasort($tree, function ($a, $b) {
return $a['freq'] <=> $b['freq'];
});
}
$root = array_shift($tree);
$codes = [];
foreach ($root['code'] as $char) {
$codes[$char] = substr_count($char, '1');
}
// compress
$compressed = '';
for ($i = 0; $i < $l; $i++) {
$compressed .= $root['code'][$string[$i]];
}
return [
'compressed' => $compressed,
'codes' => $codes,
];
}



function huffman_decode(string $code, array $tree) {
$output = '';
$node = reset($tree);
for ($i = 0; $i < strlen($code); $i++) {
$node = $tree[$node][$code[$i]];
if (isset($tree[$node]['leaf'])) {
$output .= $tree[$node]['leaf'];
$node = reset($tree);
}
}
return $output;
}


?>