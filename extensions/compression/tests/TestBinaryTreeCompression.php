<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/../src/binarytree/BinaryTreeCompression.php");

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
/*

    $f = 26.94004;

    $i = (round($f, 2) * 100);

    echo "i: $i\n";

    die();

    $ar = array_values(unpack("C*", pack("f", $f)));

    print_r($ar);

    $v0 = ((int) round($ar[0] / 16) << 4);
    $v1 = ((int) round($ar[1] / 16) << 4);
    $v2 = ((int) round($ar[2] / 16) << 4);
    $v3 = ((int) round($ar[3] / 16) << 4);

    echo "\n";
    echo "v0: " . $v0 . "\n";
    echo "v1: " . $v1 . "\n";
    echo "v2: " . $v2 . "\n";
    echo "v3: " . $v3 . "\n";

*/

    /*
    echo "\nv0: $v0\nv1: $v1\n";
    echo "v2: $v2\nv3: $v3\n";

    $av0 = $v0 | $v1;
    $av1 = $v2 | $v3;

    echo "\nav0: $av0\nav1: $av1\n";
    

    

    $v0 = $av0 & 0x11110000;
    $v1 = $av0 & 0x00001111;

    $v2 = $av1 & 0x11110000;
    $v3 = $av1 & 0x00001111;

    echo "\nv0: $v0\nv1: $v1\n";
    echo "v2: $v2\nv3: $v3\n";
    */

/*    
    $serializeArgument = BinaryTreeCompression::SerializeArgument("in", 123432);

    echo "\nserializedArgument: $serializeArgument\n";
    echo "\nUnserializeArgument: " . print_r(BinaryTreeCompression::UnserializeArgument($serializeArgument), true) . "\n";
    echo "\n";
    die();
*/  

    $compressedData = BinaryTreeCompression::Compress($dataString, ["str" => "str", "in" => 10, "fl" => 100.123456]);
    print_r($compressedDataHeader = BinaryTreeCompression::GetHeader($compressedData));

    
    echo "\n\nCompressed Data Size: " . strlen($compressedData) . "\n\n";

    
?>