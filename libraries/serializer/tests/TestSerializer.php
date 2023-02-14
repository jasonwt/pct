<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    require_once(__DIR__ . "/../vendor/autoload.php");

    function packFloat(float $value, float $maxError = 0.00000001) {
        $sign = ($value < 0 ? -1 : 1);

        $absValue = abs($value);

        $exponents = [1, 2, 5, 8, 9, 10, 11, 11];

        $minValueWithError = $value - ($value * $maxError);
        $maxValueWithError = $value + ($value * $maxError);

        for ($ecnt = 0; $ecnt < count($exponents); $ecnt ++) {
            $calculatedValue = 0;

            if ($ecnt == 0) {
                $calculatedValue = (float) ((int) ($value * 127.0)) / 127.0;

                if ($absValue > 1.0)
                    continue;

            } else {
                $exponentBits = $exponents[$ecnt];
                $mantissaBits = (($ecnt + 1) * 8) - $exponentBits - 1;

                $maxExponent = (1 << $exponentBits) - 1;
                $maxMantissa = (1 << $mantissaBits) - 1;
    
                $bias = pow(2, $exponentBits - 1) - 1;

                if ($absValue > ($maxValue = abs(1 - pow(2, -$mantissaBits)) * pow(2, pow(2, $exponentBits) - 1)))
                    continue;
                
                $exponent =  (int) floor(log($absValue, 2)) + 1;
                $mantissa =  intval(($absValue * pow(2, -$exponent) * (float) $maxMantissa));

                $calculatedValue = $sign * ((float) $mantissa / (float) $maxMantissa) * pow(2, $exponent);
            }

            if (abs($calculatedValue) < abs($minValueWithError) || abs($calculatedValue) > abs($maxValueWithError))
                continue;
        
            break;
        }

        $ecnt = min($ecnt, count($exponents)-1);

        if ($ecnt == 0)
            return pack("c", $value * 127);

        $value = ($sign >= 0 ? 1 : 0);
        $value = ($value << $exponentBits) + ($exponent + $bias);
        $value = ($value << $mantissaBits) + $mantissa;

        $packedData = "";

        for ($cnt = 0; $cnt <= $ecnt; $cnt ++) {
            $packedData = pack("C", ($value & ((1 << 8) - 1))) . $packedData;

            $value = $value >> 8; 
        }

        return $packedData;
    }

    function unpackFloat(string $data) {        
        $strLen = strlen($data);

        $exponents = [1, 2, 5, 8, 9, 10, 11, 11];

        if ($strLen == 1)
            return (float) unpack("c", $data)[1] / 127.0;
        else if ($strLen > 8)
            throw new \Exception("Invalid strlen: " . strlen($data));
        
        $exponentBits = $exponents[$strLen-1];
        $mantissaBits = ($strLen * 8) - $exponentBits - 1;
        $maxMantissa = (1 << $mantissaBits) - 1;

        $bias = pow(2, $exponentBits - 1) - 1;

        $value = 0;
        $unpackedData = unpack("C*", $data);

        foreach ($unpackedData as $v) {
            $value = $value << 8;
            $value |= $v;
        }

        $mantissa = ($value & ((1 << $mantissaBits)-1));
        $exponent = (($value >> $mantissaBits) & ((1<< $exponentBits)-1)) - $bias;
        $sign     = (($value & (1 << ((strlen($data)*8)-1))) ? 1 : -1);

        return $sign * ((float) $mantissa / (float) $maxMantissa) * pow(2, $exponent);
    }



/*


$float = -23.5; // The original value represented by the IEEE 754 float
$exponent_field = ($float >> 23) & 0xFF; // Extract the exponent field
$exponent = abs($exponent_field) - 127; // Calculate the exponent using the absolute value
echo $exponent; // Output: 134

10100001111110011010010
01000011111100110100110
10100001111110011010010
10100001111110011010010

*/



    function newPackFloat(float $value, float $maxError = 0.00000001) {
        $sign = ($value < 0 ? -1 : 1);
        //$value = $value >> 1;

        $absValue = abs($value);

//        $exponent =  (int) floor(log($absValue, 2)) + 1;
        //$exponent = abs((($value >> 23) & 0xff));
        $mantissa =  $absValue * pow(2, -((int) floor(log($absValue, 2)) + 1));

        //$exponent =  (int) floor(log($absValue, 2)) + 1;
        //$exponent = abs((($value >> 23) & 0xff));
        //$mantissa =  $absValue * pow(2, -$exponent);

        echo "\n";
        echo "value           : $value\n";        
        echo "sign            : $sign\n";        
  //      echo "exponent        : " . $exponent . " [" . decbin($exponent) . "]\n";
        echo "mantissa        : $mantissa\n";
        




        for ($bytes = 1; $bytes <= 8; $bytes ++) {
            for ($exponentBits = 1; $exponentBits < ((($bytes*8)-2)-1) && $exponentBits < 16; $exponentBits ++) {
                $mantissaBits = ($bytes*8) - $exponentBits - 1;
                $maxExponent = (1 << $exponentBits) - 1;
                $maxMantissa = (1 << $mantissaBits) - 1;

                $bias = pow(2, $exponentBits - 1) - 1;

                $maxValue = abs(1 - pow(2, -$mantissaBits)) * pow(2, pow(2, $exponentBits) - 1);

                $exponent =  (int) floor(log($absValue, 2)) + $bias;
                
                

                
                $mantissaInt =  intval(($absValue * pow(2, -($exponent-$bias+1)) * (float) $maxMantissa));
                //$mantissaInt = intval($mantissa * (float) $maxMantissa);

                $calculatedValue = $sign * ((float) $mantissaInt / (float) $maxMantissa) * pow(2, ($exponent-$bias+1));

                echo "\n";
                echo "bytes           : $bytes\n";
                echo "value           : $value\n";        
                echo "exponent        : " . $exponent . " [" . decbin($exponent) . "]\n";
                echo "mantissa        : $mantissa\n";
                echo "exponentBits    : $exponentBits [" . sprintf("%08d", decbin($exponentBits)) . "]\n";
                echo "mantissaBits    : $mantissaBits [" . sprintf("%08d", decbin($mantissaBits)) . "]\n";
                echo "maxMantissa     : $maxMantissa [" . decbin($maxMantissa). "]\n";
                echo "bias            : $bias\n";
                echo "maxValue        : $maxValue\n";

                echo "mantissa        : $mantissa\n";
                echo "mantissaInt     : " . ($mantissaInt) . " [" . decbin($mantissaInt) . "]\n";
                echo "calculatedValue : $calculatedValue\n";

                

            }
        }

//01111100
        return;






        $exponents = [1, 2, 5, 8, 9, 10, 11, 11];

        $minValueWithError = $value - ($value * $maxError);
        $maxValueWithError = $value + ($value * $maxError);

        for ($ecnt = 0; $ecnt < count($exponents); $ecnt ++) {
            $calculatedValue = 0;

            if ($ecnt == 0) {
                $calculatedValue = (float) ((int) ($value * 127.0)) / 127.0;

                if ($absValue > 1.0)
                    continue;

            } else {
                $exponentBits = $exponents[$ecnt];
                $mantissaBits = (($ecnt + 1) * 8) - $exponentBits - 1;

                $maxExponent = (1 << $exponentBits) - 1;
                $maxMantissa = (1 << $mantissaBits) - 1;
    
                $bias = pow(2, $exponentBits - 1) - 1;

                if ($absValue > ($maxValue = abs(1 - pow(2, -$mantissaBits)) * pow(2, pow(2, $exponentBits) - 1)))
                    continue;
                
                $exponent =  (int) floor(log($absValue, 2)) + 1;
                $mantissa =  intval(($absValue * pow(2, -$exponent) * (float) $maxMantissa));

                $calculatedValue = $sign * ((float) $mantissa / (float) $maxMantissa) * pow(2, $exponent);
            }

            if (abs($calculatedValue) < abs($minValueWithError) || abs($calculatedValue) > abs($maxValueWithError))
                continue;
        
            break;
        }

        $ecnt = min($ecnt, count($exponents)-1);

        if ($ecnt == 0)
            return pack("c", $value * 127);

        $value = ($sign >= 0 ? 1 : 0);
        $value = ($value << $exponentBits) + ($exponent + $bias);
        $value = ($value << $mantissaBits) + $mantissa;

        $packedData = "";

        for ($cnt = 0; $cnt <= $ecnt; $cnt ++) {
            $packedData = pack("C", ($value & ((1 << 8) - 1))) . $packedData;

            $value = $value >> 8; 
        }

        return $packedData;
    }

    newPackFloat(10.12345);

    exit(0);

    $packedData = Serializer::packFloat(0.123456);

    exit(0);

    $unpackedData = Serializer::unpackFloat($packedData);

    echo "packedData: $packedData\n";
    echo "unpackedData: $unpackedData\n";

    die();

    use pct\libraries\serializer\Serializer;

    $serializedData = [
//        "null" => Serializer::Serialize(null),                

//        "truebool" => Serializer::Serialize(true),
//        "falsebool" => Serializer::Serialize(false),

//        "negchar" => Serializer::Serialize(-255),
//        "poschar" => Serializer::Serialize(255),
//        "negshort" => Serializer::Serialize(-65535),
//        "posshort" => Serializer::Serialize(65535),
//        "negint" => Serializer::Serialize(-4294967295),
//        "posint" => Serializer::Serialize(4294967295),
//        "neglong" => Serializer::Serialize(-4294967296),
//        "poslong" => Serializer::Serialize(4294967296),

//        "negfloat0" => Serializer::Serialize(-0.0255),
//        "posfloat0" => Serializer::Serialize(0.0255),
//        "negfloat1" => Serializer::Serialize(-6.5535),
//        "posfloat1" => Serializer::Serialize(6.5535),
//        "negfloat2" => Serializer::Serialize(-429496.7295),
//        "posfloat2" => Serializer::Serialize(429496.7295),
//        "negfloat3" => Serializer::Serialize(-429496729.6),
//        "posfloat3" => Serializer::Serialize(429496729.6),

        "shortstring" => Serializer::Serialize("this is a short string."),
        "longstring" => Serializer::Serialize("this is a very long string that will alow for its length to be set in this serial code."),

//        "shortarray" => Serializer::Serialize(range(1, 5)),
//        "longarray" => Serializer::Serialize(range(1, 31))
        
    ];

    var_dump($serializedData);

/*    
    $unserializedData = [];

    foreach ($serializedData as $k => $v)
        $unserializedData[$k] = Serializer::Unserialize($v);

    print_r($unserializedData);
*/
    
?>