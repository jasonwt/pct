<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    //require_once(__DIR__ . "/../vendor/autoload.php");

    require_once(__DIR__ . "/BitWise.php");





    /*

VVVVVVV*: small unsigned int
EEEEBB*0: custom float
xxxxx*00: reserved
BBBS*000: int
BBB*0000: IEEE754 float
Nx*00000: string
V*000000: bool
*0000000: null


*/

class Serializer {
    static public function SerializeString(string $value) : string {
        return pack("C", 0b00001000) . pack("Z", $value);
    }

    static public function UnserializeInt(string &$data) : int {
        $PACKET_HEADER_ADDITIONAL_BYTES_DATA = 3;

        $packetHeader = unpack("C", $data)[1];

        echo "packetHeader : $packetHeader [" . decbin($packetHeader) . "]\n\n";
        echo "packetHeader : " . BitWise::GetBits($packetHeader, 1, 7) ." [" . decbin(BitWise::GetBits($packetHeader, 1, 7)) . "]\n\n";

        if (BitWise::GetBits($packetHeader, 0, 1) == 1) {
            
            return (int) BitWise::GetBits($packetHeader, 1, 7);
        } else {

        }

        return 0;

        $value = -1;
        echo "value : $value [" . decbin($value) . "]\n\n";

        BitWise::SetBits($value, 0, BitWise::GetBits($headerPacket, 4, 4), 4);
        
        if (BitWise::GetBits($headerPacket, $PACKET_HEADER_ADDITIONAL_BYTES_DATA)) {
            $offset = 4;

            while ($data = substr($data, 1)) {
                $packet = unpack('C', $data)[1];
                
                

                $offset += 7;

                if (BitWise::GetBits($packet, 0, 1) == 0) {
                    BitWise::SetBits($value, $offset, BitWise::GetBits($packet, 1, 7), 7);
                    break;
                } else {
                    BitWise::SetBits($value, $offset, BitWise::GetBits($packet, 1, 7), 7);
                }
        
                

            }

            echo "value : $value [" . decbin($value) . "]\n\n";

            $value = BitWise::DisableBits($value, $offset, 0);
        }

        echo "value : $value [" . decbin($value) . "]\n\n";

        return $value;
    }

    static public function SerializeInt(int $value) : string {
        if ($value >= 0 && $value <= 127) {
            $packet = $value << 1;

            return pack("C", BitWise::SetBits($packet, 0, 1, 1));
        }

        return " ";

        $PACKET_HEADER_LESS_THAN_ZERO_FLAG = 2;
        $PACKET_HEADER_ADDITIONAL_BYTES_DATA = 3;

        $packetHeader = 0b00000110;

        if ($value < 0)
            BitWise::EnableBits($packetHeader, $PACKET_HEADER_LESS_THAN_ZERO_FLAG);
        
        $value = abs($value);

        if ($value == 0)
            return pack("C", $packetHeader);
        
        $valueBits = BitWise::UsedBits($value);

        echo "value        : $value [" . decbin($value) . "]\n";
        echo "valueBits    : $valueBits [" . sprintf("%08d", decbin($valueBits)) . "]\n";
        echo "packetHeader : $packetHeader [" . decbin($packetHeader) . "]\n\n";
        
        BitWise::SetBits(
            $packetHeader,
            3,
            BitWise::GetBits($value, -5, 4),
            4
        );

        echo "value        : $value [" . decbin($value) . "]\n";
        echo "valueBits     : $valueBits [" . sprintf("%08d", decbin($valueBits)) . "]\n";
        echo "packetHeader : $packetHeader [" . decbin($packetHeader) . "]\n\n";

        $offset = 4;

        $packedData = pack("C", $packetHeader);

        if ($valueBits > 4) {
            BitWise::SetBits($packetHeader, -1, 1, 1);

            while ($bitsLeft = ($valueBits-$offset)) {
                $newPacket = 0;

                BitWise::EnableBits($newPacket, min($bitsLeft, 7));
                

                if ($bitsLeft > 7)
                    $offset += min($bitsLeft, 7);
            }
        }

        echo "value        : $value [" . decbin($value) . "]\n";
        echo "valueBits     : $valueBits [" . sprintf("%08d", decbin($valueBits)) . "]\n";
        echo "packetHeader : $packetHeader [" . decbin($packetHeader) . "]\n\n";
        

/*            


1+2+4+8+16
        $otherBits = 0;

        

        if (false && ($usedBits = BitWise::UsedBits($value)) > 0) {
            BitWise::EnableBits($packetHeader, $PACKET_HEADER_ADDITIONAL_BYTES_DATA);

            $packedData = pack("C", $packetHeader);

            for ($cnt = 0;; $cnt ++ ) {
                if (($usedBits = BitWise::UsedBits($value)) == 0)
                    break;

                echo "cnt          : $cnt\n";
                echo "value        : $value [" . decbin($value) . "]\n";
                echo "packetHeader : $packetHeader [" . decbin($packetHeader) . "]\n";
                echo "otherBits    : $otherBits [" . decbin($otherBits) . "]\n";
                echo "usedBits     : $usedBits [" . sprintf("%08d", decbin($usedBits)) . "]\n\n";

                $newPackedData = 0;

                BitWise::SetBits($otherBits, ($cnt*8)+1, $value, 7);
                BitWise::SetBits($newPackedData, 1, $value, 7);

                $value = $value >> 7;

                if ($value > 0) {
                    BitWise::SetBits($otherBits, ($cnt*8), 1, 1);
                    BitWise::SetBits($newPackedData, 0, 1, 1);
                }

                $packedData .= pack("C", $newPackedData);
            } 
        }

*/          

        return $packedData;
    }
    
    static public function SerializeFloat(float $value) : string {
        return "";
    }

    static public function SerializeArray($value) : string {
        return "";
    }

    static public function Serialize($data) : string {
        if (is_string($data)) {
            return static::SerializeString($data);
        } else if (is_null($data)) {
            return pack("C", 0b10000000);
        } else if (is_bool($data)) {
            return ($data ? pack("C", 0b11000000) : pack("C", 0b01000000));
        } else if (is_float($data)) {
            return static::SerializeFloat($data);
        } else if (is_int($data)) {                
            return static::SerializeInt($data);
        } else if (is_array($data)) {
            return static::SerializeArray($data);
        }

        throw new \Exception("Unsupported datatype: " . gettype($data));
    }

    static public function Unserialize(string &$data) {
        $packet = unpack("C", $data)[1];
        
        echo "packet: $packet [" . decbin($packet) . "]\n";

        if (BitWise::GetBits($packet, 0, 1) == 0b1) {
            echo "small unsigned int [0b00000001]\n";

            $data = substr($data, 1);

            return BitWise::GetBits($packet, 1, 7);
        } else if (BitWise::GetBits($packet, 0, 2) == 0b10) {
            echo "int [0b00000010]\n";
            return static::UnserializeInt($data);
        } else if (BitWise::GetBits($packet, 0, 3) == 0b100) {
            echo "float [0b00000100]\n";
        } else if (BitWise::GetBits($packet, 0, 4) == 0b1000) {
            echo "reserved [0b00001000]\n";
        } else if (BitWise::GetBits($packet, 0, 5) == 0b10000) {
            echo "reserved [0b00010000]\n";
        } else if (BitWise::GetBits($packet, 0, 6) == 0b100000) {
            echo "reserved [0b00100000]\n";
        } else if (BitWise::GetBits($packet, 0, 7) == 0b1000000) {
            echo "bool [0b01000000]\n";
        } else if (BitWise::GetBits($packet, 0, 8) == 0b10000000) {
            echo "null [0b10000000]\n";
        }
        
    }
}


    

    $intStr = "";

    $maxCnt = 10;

    for ($cnt = 1; $cnt < $maxCnt; $cnt ++)
        $intStr .= str_repeat(($cnt % 2 ? "1" : "0"), ($maxCnt-$cnt));

    //$packedData = Tmp::Serialize((int) bindec ($intStr));

    $packedData = Serializer::Serialize(10);

    echo "strlen(packedData): " . strlen($packedData) . "\n";
    echo Serializer::Unserialize($packedData);

    exit(0);

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
/*        
        $packedValue = pack('d', $value);
        $unpackedValue = unpack('Q', $packedValue)[1];

        echo "\nvalue   : $value\n";
        echo "decbin  : " . decbin($unpackedValue) . "\n";

        $mantissa = $unpackedValue & ((1 << 52)-1);
        $unpackedValue = ($unpackedValue >> 52);
    //    echo "decbin  : " . decbin($unpackedValue) . "\n";
        $exponent = $unpackedValue & ((1 << 11)-1);
    //    echo "mask: " . decbin(((1 << 11)-1)) . "\n";
        $exponent = $exponent - 1023;
        $unpackedValue = ($unpackedValue >> 11);
    //    echo "decbin  : " . decbin($unpackedValue) . "\n";
        $sign = $unpackedValue;
*/    
        $absValue = abs($value);
        
    
        $sign = ($value < 0 ? -1 : 1);
        //$value = $value >> 1;

        

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
        




        for ($bytes = 1; $bytes <= 1; $bytes ++) {
            for ($exponentBits = 0; $exponentBits < ((($bytes*8)-2)-1) && $exponentBits < 16; $exponentBits ++) {
                $mantissaBits = ($bytes*8) - $exponentBits - 1;
                $maxExponent = (1 << $exponentBits) - 1;
                $maxMantissa = (1 << $mantissaBits) - 1;

                $bias = pow(2, $exponentBits - 1) - 1;


                






                $maxValue = abs(1 - pow(2, -$mantissaBits)) * pow(2, pow(2, $exponentBits) - 1);

                $exponent =  (int) (floor(log($absValue, 2)) + $bias);
//                if ($exponent < 0) { // !
  //                  $bias = $bias + -$exponent;// !
    //                $exponent = 0;// !
      //          }

  //              if ($exponent == -4)
//                    $exponent = 0;
                
//                if ($exponent < 0)
  //                  $exponent = (~$exponent);
                

                
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

    function FloatParts(float $value) {
        $floatInt = unpack("Q", pack("d", $value))[1];

        return [
            "sign" => ($floatInt >> 63) & 1,
            "exponent" => (($floatInt >> 52) & ((1 << 11)-1)),
            "mantissa" => $floatInt & ((1 << 52)-1)
        ];
    }

    function plz(float $value, float $maxError = 0.00000001) {

        //$sign = ($unpackedValue >> 63) & 1;
        //$exponent = (($unpackedValue >> 52) & ((1 << 11)-1)) - $bias;
        //$mantissa = $unpackedValue & ((1 << 52)-1);

        $absValue = abs($value);

        $sign = ($value < 0 ? 1 : 0);

        $originalExponent = (int) floor(log($absValue, 2));
        $mantissaFloat = $absValue * pow(2, -$originalExponent);

        $valueParts = IEEE754::frexp($value);
        

/*
        $exponent =  (int) floor(log($absValue, 2));
        $mantissa =  (int) (($absValue * pow(2, -$exponent)) * (float) $newMantissaBits);
        $calcValue = ((float) $mantissa / (float) $newMantissaBits) *  pow(2, $exponent);

        echo "\nvalue    : $value\n";
        echo "exponent  : " . $exponent . " [" . decbin($exponent) . "]\n";
        echo "mantissa  : " . $mantissa . "\n";

        0000000000000000000000000000000000000000000001100101
        0100001111110011010011010110101000010110000111100101

  */      
        echo "\n";        
        echo "value                : " . $value . "\n";     
        echo "valueParts::bias     : " . $valueParts["bias"]     . " [" . decbin($valueParts["bias"]) . "]\n";   
        echo "valueParts::sign     : " . $valueParts["sign"]     . " [" . decbin($valueParts["sign"]) . "]\n";
        echo "valueParts::exponent : " . $valueParts["exponent"] . " [" . decbin($valueParts["exponent"]) . "]\n";
        echo "valueParts::mantissa : " . $valueParts["mantissa"] . " [" . decbin($valueParts["mantissa"]) . "]\n";
        
        for ($bytes = 1; $bytes <= 8; $bytes ++) {
            echo "\n";        
            
            
            $maxBits = min(16, ($bytes*8)-1);

            for ($exponentBits = 0; $exponentBits <= $maxBits; $exponentBits ++) {
                $exponentBitMask = (1 << $exponentBits) - 1;

                $mantissaBits = (($bytes*8)-1) - $exponentBits;
                $mantissaBitMask = (1 << $mantissaBits) - 1;

                $exponent = $valueParts["exponent"];
                $mantissa = $valueParts["mantissa"];
                
                echo "\n";
                echo "\texponentBits         : $exponentBits ["    . decbin($exponentBits) . "]\n";
                echo "\texponentBitMask      : $exponentBitMask [" . decbin($exponentBitMask) . "]\n";
                echo "\tmantissaBits         : $mantissaBits ["    . decbin($mantissaBits) . "]\n";
                echo "\tmantissaBitMask      : $mantissaBitMask [" . decbin($mantissaBitMask) . "]\n";
                echo "\texponent             : $exponent ["        . decbin($exponent) . "]\n";   
                echo "\tmantissa             : $mantissa ["        . decbin($mantissa) . "]\n";


/*

                $exponentBitMask = (1 << $exponentBits) - 1;

                $mantissaBits = (($bytes*8)-1) - $exponentBits;
                $mantissaBitMask = (1 << $mantissaBits) - 1;

                $exponent = $originalExponent;
                
                $mantissa =  (int) ($mantissaFloat * (float) $mantissaBitMask);
                


                if ($exponent < 0) {
                    $exponent = 0;
//                    $mantissa = $mantissa << abs($exponent);
  //                  $exponent = 0;
                }

                $mantissaInt2 = $mantissaInt;

                for ($cnt = 0; $cnt < 52; $cnt ++) {                    

                    if ($cnt > 51 - $exponentBits)
                        continue;

                    $mask = ~(1 << $cnt);

                    $mantissaInt2 = $mask & $mantissaInt2;
                }


                $newMantissa = unpack("d", pack("Q", $mantissaInt2))[1];


                
                
                $calculatedValue = $newMantissa * pow(2, ($exponent));



                echo "\texponentBits     : $exponentBits [" . decbin($exponentBits) . "]\n";
                echo "\texponentBitMask  : $exponentBitMask [" . decbin($exponentBitMask) . "]\n";
                echo "\tmantissaBits     : $mantissaBits [" . decbin($mantissaBits) . "]\n";
                echo "\tmantissaBitMask  : $mantissaBitMask [" . decbin($mantissaBitMask) . "]\n";
                
                echo "\tmantissaInt2     : $mantissaInt2 [" . decbin($mantissaInt2) . "]\n";
                echo "\tnewMantissa      : $newMantissa \n";


                //if ($mantissaShift > 0)
                    //$mantissa = $mantissa << $mantissaShift;

                echo "\texponent         : $exponent\n";
                echo "\tmantissa         : $mantissa\n";
                echo "\tmantissaFloat    : $mantissaFloat\n";

                echo "\tcalculatedValue  : $calculatedValue\n";

                echo "\n";
                    
*/
                
            }

            break;
        }

//01111100
        return;



    }

/*    
    $number = 0;
$bytes = unpack('C*', pack('d', $number));

foreach ($bytes as $byte)
    echo decbin($byte);
01111111111

echo "\n";

exit(0);

$sign = ($bytes[1] >> 7) & 1;
$exponent = (($bytes[1] & 0x7F) << 4) | (($bytes[2] >> 4) & 0xF);
$bias = 1023; // The bias for double-precision format
$mantissa = (($bytes[2] & 0xF) << 48) | ($bytes[3] << 40) | ($bytes[4] << 32) | ($bytes[5] << 24) | ($bytes[6] << 16) | ($bytes[7] << 8) | $bytes[8];

echo "Sign: $sign, Exponent: $exponent, Mantissa: $mantissa, Bias: $bias";
1111111111
01111111111
0000000000000000000000000000000000000000000000000000
011111111110000000000000000000000000000000000000000000000000000
11111111110000000000000000000000000000000000000000000000000000
1011111111110000000000000000000000000000000000000000000000000000

01111111111
01111111111
11111111110000000000000000000000000000000000000000000000000000
1011111111110000000000000000000000000000000000000000000000000000

1111111111111111111111111111111111111111111111111111101111111111
1111111111    
exit(0);
*/

/*
    $value = 0.1;

    $packedValue = pack('d', $value);
    $unpackedValue = unpack('Q', $packedValue)[1];

    echo "\nvalue   : $value\n";
    echo "decbin  : " . decbin($unpackedValue) . "\n";

    $mantissa = $unpackedValue & ((1 << 52)-1);
    $unpackedValue = ($unpackedValue >> 52);
    $exponent = ($unpackedValue & ((1 << 11)-1));
    $unpackedValue = ($unpackedValue >> 11);
    $sign = $unpackedValue;

    echo "mantissa: $mantissa [" . decbin($mantissa) . "]\n";
    echo "exponent: $exponent [" . decbin($exponent) . "]\n";
    echo "sign: $sign [" . decbin($sign) . "]\n";

    exit(0);



    $float = -0.5; // The original value represented by the IEEE 754 float
$exponent_field = ($float >> 23) & 0xFF; // Extract the exponent field
$exponent = abs($exponent_field) - 127; // Calculate the exponent using the absolute value
$significand = 1 + ($float & 0x7FFFFF) / pow(2, 23); // Calculate the significand as a decimal value
$value = $significand * pow(2, $exponent); // Calculate the actual value of the float
if ($float < 0) { // If the original float was negative, negate the value
  $value = -$value;
}
echo $value; // Output: -0.5



exit(0);
*/
/*
    $bias = 127;
$f = 0.0625;
$sign = ($f < 0) ? 1 : 0;
$f = abs($f);

$exponent = floor(log($f, 2));
$encoded_exponent = $exponent + $bias;
$mantissa = $f / pow(2, $exponent);

printf("Sign: %d\n", $sign);
printf("Encoded Exponent: %d\n", $encoded_exponent);
printf("Mantissa: %f\n", $mantissa);



exit(0);
*/
    plz(0.12345);

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