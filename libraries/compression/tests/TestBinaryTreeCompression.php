<?php
    declare(strict_types=1);
	
    namespace pct\libraries\compression\binarytree;

    use pct\libraries\compression\binarytree\BinaryTreeCompression;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    require_once(__DIR__ . "/../vendor/autoload.php");


    echo decbin($test1 = BinaryTreeCompression::GenerateSerializeCode($data = null)) . "\n";
    echo decbin($test2 = BinaryTreeCompression::GenerateSerializeCode($data = false)) . "\n";
    echo decbin($test3 = BinaryTreeCompression::GenerateSerializeCode($data = 3.1415)) . "\n";
    echo decbin($test4 = BinaryTreeCompression::GenerateSerializeCode($data = -3.1415)) . "\n";
    echo decbin($test5 = BinaryTreeCompression::GenerateSerializeCode($data = 99)) . "\n";
    echo decbin($test6 = BinaryTreeCompression::GenerateSerializeCode($data = -99)) . "\n";
    echo decbin($test7 = BinaryTreeCompression::GenerateSerializeCode($data = "12345678901234567890123456789012")) . "\n";

    echo "test1: " . print_r(BinaryTreeCompression::ParseSerialCode($test1, true)) . "\n";
    echo "test2: " . var_dump(BinaryTreeCompression::ParseSerialCode($test2, true)) . "\n";
    echo "test3: " . print_r(BinaryTreeCompression::ParseSerialCode($test3, true)) . "\n";
    echo "test4: " . print_r(BinaryTreeCompression::ParseSerialCode($test4, true)) . "\n";
    echo "test5: " . print_r(BinaryTreeCompression::ParseSerialCode($test5, true)) . "\n";
    echo "test6: " . print_r(BinaryTreeCompression::ParseSerialCode($test6, true)) . "\n";
    echo "test7: " . print_r(BinaryTreeCompression::ParseSerialCode($test7, true)) . "\n";

    print_r($data);

    die();

    echo $serializedData . "\n";

    die();

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

    $compressedData = BinaryTreeCompression::Compress($dataString);

    echo strlen($compressedData) . "\n";

    print_r(BinaryTreeCompression::UnserializeString($compressedData));

    //echo strlen($compressedData) . "\n";


    //print_r($compressedDataHeader = BinaryTreeCompression::GetHeader($compressedData));

    
    //echo "\n\nCompressed Data Size: " . strlen($compressedData) . "\n\n";

    
?>