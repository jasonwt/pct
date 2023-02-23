<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;

use pct\libraries\serializer\Serializer as SerializerSerializer;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    //require_once(__DIR__ . "/../vendor/autoload.php");

    require_once(__DIR__ . "/BitWise.php");

    class IEEE754 {
        const EXPONENT_BITS = (PHP_INT_SIZE == 8 ? 11 : 8);
        const MANTISSA_BITS = (PHP_INT_SIZE == 8 ? 52 : 23);
        const BIAS          = (PHP_INT_SIZE == 8 ? 1023 : 127);

        static public function Decode(float $value) : array {
            $floatInt = unpack("Q", pack("d", $value))[1];  

            return [
                "sign" => ($value < 0 ? 1 : 0),
                "exponent" => (($floatInt >> static::MANTISSA_BITS) & ((1 << static::EXPONENT_BITS)-1)) - static::BIAS,
                "mantissa" => ($floatInt & ((1 << static::MANTISSA_BITS)-1))
            ];    
            
        }

        static public function Encode(int $sign, int $exponent, int $mantissa) : float {
            $floatInt = $sign << (static::EXPONENT_BITS + static::MANTISSA_BITS) +
                $exponent << static::MANTISSA_BITS +
                $mantissa;

            return unpack("d", pack("Q", $floatInt))[1];
        }
    }

    


    class Serializer {
        // 0b00000000 : null
        // 0bVVVVVVV1 : unsigned int >= 0 && <= 127
        // 0bEEEBBB10 : float | bitfield
        // 0bVVVVT100 : array |
        // 0bBBBS1000 : int
        // 0bxxx10000 : bitfield
        // 0bxx100000 : string
        // 0bV1000000 : bool
        // 0b10000000 : reserved

        static public function SerializeNull() : string {
            // 0b00000000 : null

            return pack("C", 0b00000000);
        }

        static public function SerializeBool(bool $value) : string {
            // 0bV1000000 : bool

            return pack("C", (($value ? 0b11000000 : 0b01000000) << 7));
        }

        static public function SerializeArray(array $value) : string {
            $assocArray = array_keys($value) !== range(0, count($value) - 1);

            $packets = pack("C", ((min(count($value), 15)) << 4) + (($assocArray ? 1 : 0) << 3) + 0b100);

            foreach ($value as $k => $v) {
                if (!$assocArray)
                    $packets .= static::Serialize($k);

                $packets .= static::Serialize($v);
            }

            return $packets;
        }

        static public function SerializeInteger(int $value) : string {
            // 0bVVVVVVV1 : unsigned int >= 0 && <= 127
            if ($value >= 0 && $value <= 127)
                return pack("C", (($value << 1) + 1));

            // 0bBBBS1000 : int
            $sign = ($value < 0 ? 1 : 0);
            $bits  = decbin((int) abs($value));
            $bytes = (int) ceil(strlen($bits) / 8);

            $packets = pack("C", (($bytes << 5) + ($sign << 4) + 0b1000));

            $bits = str_pad($bits, ($bytes * 8), "0", STR_PAD_LEFT);

            while ($bits != "") {
                $packets .= pack("C", bindec(substr($bits, 0, 8)));

                $bits = substr($bits, 8);
            }
            
            return $packets;
        }

        static public function SerializeFloat(float $value) : string {
            // 0bEEEBBB10 : float

            $floatInt = unpack("Q", pack("d", $value))[1];

            $EXPONENT_BITS = (PHP_FLOAT_DIG == 15 ? 11 : 8);
            $MANTISSA_BITS = (PHP_FLOAT_DIG == 15 ? 52 : 23);
            $BIAS          = pow(2, ($EXPONENT_BITS-1)) - 1;

            $sign = ($value < 0 ? 1 : 0);
            $exponent = ((($floatInt >> $MANTISSA_BITS) & ((1 << $EXPONENT_BITS)-1))) - $BIAS;
            $esign = ($exponent < 0 ? 1 : 0);
            $exponent = abs($exponent);
            $mantissa = ($floatInt & ((1 << $MANTISSA_BITS)-1));      

            $numExponentBits = intval(ceil(strlen(decbin($exponent))/2)*2);
            $numMantissaBits = $MANTISSA_BITS;

            echo "value           : $value\n";
            echo "sign            : $sign\n";
            echo "esign           : $esign\n";
            echo "numExponentBits : [" . sprintf("%03b", $numExponentBits) . "] $numExponentBits\n";
            echo "exponent        : [" . sprintf("%0" . $numExponentBits. "b", $exponent) . "] $numExponentBits \n";
            echo "numMantissaBits : [" . decbin($numMantissaBits) . "] $numMantissaBits\n";
            echo "mantissa        : [" . sprintf("%0" . $numMantissaBits . "b", $mantissa) . "] $mantissa \n";

            $floatInt = 
                $sign << ($EXPONENT_BITS + $MANTISSA_BITS) |
                ((($exponent & ((1 << $EXPONENT_BITS) - 1))) << $MANTISSA_BITS) |
                ((($mantissa >> ($MANTISSA_BITS - $numMantissaBits)) & ((1 << $MANTISSA_BITS)-1)) << ($MANTISSA_BITS - $numMantissaBits))
            ;

            echo sprintf("%064b", $floatInt) . "\n";
            


            $floatInt = 
                $sign << ($EXPONENT_BITS + $MANTISSA_BITS) |
                (((($exponent * ($esign == 1 ? - 1 : 1) + $BIAS) & ((1 << $EXPONENT_BITS) - 1))) << $MANTISSA_BITS) |
                (($mantissa & ((1 << $MANTISSA_BITS)-1)))
            ;

            echo unpack("d", pack("Q", $floatInt))[1] . "\n";

            

            
//100000000000000000000000000000000000000000000000000000000000000
//1000000000000000000000000000000000000000000000000000000000000000
//1000000001010000000100111010100100101010001100000101010000000000
//1000000001010000000000000000010011101010010010101000110000010101
/*            

            $exponentBits = decbin($exponent);
            $mantissaBits = sprintf("%0" . $EXPONENT_BITS . "b", $mantissa);

            $numExponentBits = (int) ceil(strlen($exponentBits) / 2) * 2;
            $numMantissaBits = $MANTISSA_BITS;

            $numBytes = (int) ceil(($numExponentBits + $numMantissaBits + 1) / 8);

            echo $exponentBits . "\n";
            $exponentBits = str_pad($exponentBits, $numExponentBits, "0", STR_PAD_LEFT);

            $packetBits = str_pad((string) $sign . $exponentBits . $mantissaBits, ($numBytes * 8), "0");

            $packets = pack("C", (((int) ($numExponentBits / 2) << 5) + (($numBytes-1) << 2) + 0b10));

            echo "floatInt     : [" . sprintf("%064b", $floatInt) . "] $floatInt\n";         
            
//            echo "sign         : $sign\n";
            echo "exponent     : [" . sprintf("%011b", $exponent) . "] $exponent \n";
            echo "mantissa     : [" . sprintf("%052b", $mantissa) . "] $mantissa \n";
    //        echo "numBytes     : $numBytes\n";
            echo "packetBits   : [$packetBits]\n";
        //    echo "packetHeader : [" . sprintf("%08b", ord($packets)) . "]\n\n";



            while ($packetBits != "") {
                $packets .= pack("C", bindec(substr($packetBits, 0, 8)));

                $packetBits = substr($packetBits, 8);
            }
*/
            echo "\n";
exit(0);
            return $packets;
        }



/*



floatInt     : [0011111111010100000110001001001101110100101111000110101001111111] 4599328141049883263
mantissa     : [0100000110001001001101110100101111000110101001111111] 1152921504606847
packetBits   : 00100000110001001001101110100101111000110101001111111100
mantissa     : [1000001100010010011011101001011110001101010011111110] 2305843009213694
packetBits   : 00100000110001001001101110100101111000110101001111111100
floatInt     : [0100000000010000000000000000000000000000000000000000000000000000] 4616189618054758400



*/
        static public function UnserializeFloat(string &$data) : float {
            // 0bEEEBBB10 : float

            $EXPONENT_BITS = (PHP_FLOAT_DIG == 15 ? 11 : 8);
            $MANTISSA_BITS = (PHP_FLOAT_DIG == 15 ? 52 : 23);
            $BIAS          = pow(2, ($EXPONENT_BITS-1)) - 1;

            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            $numBytes = ((($packetHeader >> 2) & ((1 << 3)-1))) + 1;
            $numExponentBits = ((($packetHeader >> 5) & ((1 << 3)-1))) * 2;
            $numMantissaBits = (($numBytes * 8) - $numExponentBits - 1) - 1;
            
            $valueBits = 0;
            for ($bcnt = 0; $bcnt < $numBytes; $bcnt ++) {
                if ($bcnt > 0)
                    $valueBits = $valueBits << 8;

                $valueBits += unpack("C", $data)[1];                
            }

            //$sign = (($valueBits & (1 << (($numBytes*8)-1))) > 0 ? 1 : 0);
            $sign = (($valueBits >> ($numMantissaBits+$numExponentBits)) & 1);
            $exponent = (($valueBits >> $numMantissaBits) & ((1 << $numExponentBits)) - 1);
            $mantissa = ($valueBits & ((1 << $numMantissaBits) - 1));

            echo "valueBits    : [" . sprintf("%0" . ($numBytes * 8) . "b", $valueBits) . "] $valueBits\n";
            echo "sign         : $sign\n";
            echo "exponent     : [" . sprintf("%0" . $numExponentBits . "b", $exponent) . "] $exponent\n";
            echo "mantissa     : [" . sprintf("%0" . $numMantissaBits . "b", $mantissa) . "] $mantissa\n";
            echo "numBytes: $numBytes\n";
            echo "numExponentBits: $numExponentBits\n";
            echo "numMantissaBits: $numMantissaBits\n";

            $floatInt =
                ($sign << ($EXPONENT_BITS + $MANTISSA_BITS)) +
                (($exponent & ((1 << $EXPONENT_BITS) - 1)) << $MANTISSA_BITS) +
                ($mantissa & ((1 << $MANTISSA_BITS)-1));


            return unpack("d", pack("Q", $floatInt))[1];

            //$floatInt = unpack("Q", pack("d", $value))[1];
            
            return 0;

        }
//10000110100001101000011010000110100001101000011010000
        static public function SerializeString(string $value) : string {
            // 0bxx100000 : string

            $packets = pack("C", 0b00100000) . pack("Z*", $value);

            return $packets;
        }

        static public function Serialize($value) : ?string {
            if (is_null($value)) {
                return static::SerializeNull();
                
            } else if (is_bool($value)) {
                return static::SerializeBool($value);

            } else if (is_array($value)) {
                return static::SerializeArray($value);

            } else if (is_integer($value)) {
                return static::SerializeInteger($value);

            } else if (is_float($value)) {
                return static::SerializeFloat($value);

            } else if (is_string($value)) {
                return static::SerializeString($value);

            }


            return null;
        }

        static public function Unserialize(string &$data) {

        }
    }

    $serializedData = Serializer::Serialize(0.0314);
    echo Serializer::UnserializeFloat($serializedData) . "\n";
    

?>