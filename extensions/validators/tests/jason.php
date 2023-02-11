<?php

class HuffmanNode {
    public $char;
    public $freq;
    public $left;
    public $right;
  
    public function __construct($char, $freq, $left = null, $right = null) {
        $this->char = $char;
        $this->freq = $freq;
        $this->left = $left;
        $this->right = $right;
    }
}

class HuffmanTree {
    public $root;
  
    public function __construct($root) {
        $this->root = $root;
    }
}

function buildHuffmanTree($freqMap) {
    $priorityQueue = new SplPriorityQueue();
  
    foreach ($freqMap as $char => $freq) {
        $node = new HuffmanNode($char, $freq);
        $priorityQueue->insert($node, $freq * -1);
    }
  
    while ($priorityQueue->count() > 1) {
        $left = $priorityQueue->extract();
        $right = $priorityQueue->extract();
      
        $merged = new HuffmanNode(null, $left->freq + $right->freq, $left, $right);
        $priorityQueue->insert($merged, $merged->freq * -1);
    }
  
    return new HuffmanTree($priorityQueue->extract());
}

function buildEncodingMap($root, &$encodingMap, $encoding = '') {
    if ($root === null) {
        return;
    }
  
    if ($root->char !== null) {
        $encodingMap[$root->char] = $encoding;
        return;
    }
  
    buildEncodingMap($root->left, $encodingMap, $encoding . '0');
    buildEncodingMap($root->right, $encodingMap, $encoding . '1');
}

function compress($input) {
    $freqMap = array_count_values(str_split($input));
    $tree = buildHuffmanTree($freqMap);

    $encodingMap = [];
    buildEncodingMap($tree->root, $encodingMap);

    
  
    $compressed = '';
    foreach (str_split($input) as $char) {
        $compressed .= $encodingMap[$char] . "\n";
    }
  
    return ["tree" => $tree, "compressedData" => $compressed];
}

function decompress($input, $tree) {
    $decompressed = '';
    $node = $tree->root;
  
    for ($i = 0; $i < strlen($input); $i++) {
        $char = $input[$i];
      
        if ($char === '0') {
            $node = $node->left;
        } else {
            $node = $node->right;
        }
      
        if ($node->char !== null) {
            $decompressed .= $node->char;
            $node = $tree->root;
        }
    }
  
    return $decompressed;
}



print_r($compressed = compress("aaaaaaabbbbbbbbcccccdddee"));

echo "\n";
print_r(decompress($compressed["compressedData"], $compressed["tree"]));
//1010101010101011111111111111110000000000011011011010010
?>