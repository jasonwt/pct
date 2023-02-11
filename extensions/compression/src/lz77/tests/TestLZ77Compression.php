<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/../src/binarytree/BinaryTreeCompression.php");
    
    $compression = new BinaryTreeCompression();




    function lz77_compress($input) {
        $dictionary = [];
        $output = [];
        $window_start = 0;
        $window_end = 0;
    
        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $match_length = 0;
            $match_offset = 0;
    
            // Look for a match in the dictionary
            for ($j = $window_start; $j <= min($i, $window_end); $j++) {
                $search_length = min(strlen($input) - $i, $window_end - $j + 1);
                $search_string = substr($input, $i, $search_length);
                $match_pos = strpos($input, $search_string, $j);
    
                if ($match_pos !== false && $match_length < $search_length) {
                    $match_length = $search_length;
                    $match_offset = $i - $j;
                }
            }
    
            // Add the match to the dictionary and output
            if ($match_length > 0) {
                $dictionary[] = [$match_offset, $match_length];
                $i += $match_length - 1;
            } else {
                $dictionary[] = [0, 1];
                $output[] = $char;
            }
    
            // Update the window
            $window_start = max(0, $i - 32767);
            $window_end = $i;
        }
    
        return $dictionary;
    }

    function lz77_compress2($input) {
        $output = [];
        $window_start = 0;
        $window_end = 0;
        $lookahead_start = 0;
        $lookahead_end = 0;
    
        while ($lookahead_start < strlen($input)) {
            $match_length = 0;
            $match_offset = 0;
    
            // Look for a match in the window
            for ($i = $window_start; $i <= $window_end; $i++) {
                $search_length = min(strlen($input) - $lookahead_start, $window_end - $i + 1);
                $search_string = substr($input, $lookahead_start, $search_length);
                $match_pos = strpos($input, $search_string, $i);
    
                if ($match_pos !== false && $match_length < $search_length) {
                    $match_length = $search_length;
                    $match_offset = $lookahead_start - $i;
                }
            }
    
            // Output the match or the next character
            if ($match_length > 0) {
                $output[] = [$match_offset, $match_length];
                $lookahead_start += $match_length;
            } else {
                $output[] = $input[$lookahead_start];
                $lookahead_start++;
            }
    
            // Update the window
            $window_start = max(0, $lookahead_start - 32767);
            $window_end = $lookahead_start - 1;
        }
    
        return $output;
    }


    $dataString = "";

    srand(100);

    if (count($argv) > 1) {
        if ($argv[1][0] == "-") {
            for ($cnt = 32; $cnt <= ((int) substr($argv[1], 1) + 32) && $cnt < 128; $cnt ++)
                $dataString .= str_repeat((string) chr($cnt), (rand(1, 100)*($cnt-31)));
        } else {
            $dataString = file_get_contents($argv[1]);
        }
        
    } else {
        $dataString = file_get_contents(__DIR__ . "/TestBinaryTreeCompression.php");
    }


//    echo $dataString;

//    echo "\n\n" . strlen($dataString) . "\n\n";
    
//    $dataString = "aaaaaaabbbbbbbbcccccdddee";


    $compressedData = BinaryTreeCompression::Compress($dataString);
    

    echo "\ncompressedDataSize: " . strlen($compressedData) . "\n\n";
    exit(0);
    

  //  print_r($compression);

  //print_r(lz77_compress2($dataString));

    

    //$compressedData = Compression::Compress($dataString, 0);

    //print_r($compressedData);
    die();




    
?>