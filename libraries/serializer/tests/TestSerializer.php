<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    //require_once(__DIR__ . "/../vendor/autoload.php");

    require_once(__DIR__ . "/BitWise.php");
    require_once(__DIR__ . "/Serializer.php");
    require_once(__DIR__ . "/IEEE754.php");



    
//    $value = "18446744073709551615";

  //  echo ceil( strlen((string) $value) * log(10, 2)) . "\n";
   $data = Serializer::SerializeNumber(0.123);
   echo strlen($data) . "\n";
    echo "UnserializeNumber: " . Serializer::UnserializeNumber($data) . "\n";
   

   exit(0);
    //exit(0);
   

    function EchoValue($value) {
        echo "(" . gettype($value) . ") " . print_r($value, true);

        if (is_int($value))
            echo " [" . decbin($value) . "]\n";        
        else
            echo "\n";
    }

    if (is_null(($value = $argv[1] ?? null))) {

        if (false) {
            $intStr = "";

            $maxCnt = 10;

            for ($cnt = 1; $cnt < $maxCnt; $cnt ++)
                $intStr .= str_repeat(($cnt % 2 ? "1" : "0"), ($maxCnt-$cnt));

            $value = (int) bindec($intStr);
        } else {
            $value = range(1, 13);
        }
    }

    if (is_string($value)) {
        if (preg_match('/^[01]+$/', $value))
            $value = (int) bindec($value);
        else if (filter_var($value, FILTER_VALIDATE_INT))
            $value = (int) $value;
        else if (filter_var($value, FILTER_VALIDATE_FLOAT))
            $value = (float) $value;
        
    }

    EchoValue($value);
    
    $packedData = Serializer::Serialize($value);

    echo "Strlen(): " . strlen($packedData) . "\n";
    $value = Serializer::Unserialize($packedData);

    echo "output\n";
    EchoValue($value);

    exit(0);    
?>