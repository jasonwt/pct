<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;

    require_once(__DIR__ . "/BitWise.php");
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

//    const PHP_EXPONENT_BITS = (PHP_INT_SIZE == 8 ? 11 : 8);
  //  const PHP_MANTISSA_BITS = (PHP_INT_SIZE == 8 ? 52 : 23);

    class IEEE754 {
        public static array $EXPONENT_BITS = [
            1, 3, 5, 8, 9, 10, 11, 11
        ];

        public static function ExponentBits(int $bytes = PHP_INT_SIZE) : int {
            if ($bytes <= 0 || $bytes > PHP_INT_SIZE)
                throw new \Exception("Invalid bytes value '$bytes'");

            return static::$EXPONENT_BITS[$bytes-1];
        }

        public static function MantissaBits(int $bytes = PHP_INT_SIZE) : int {
            if ($bytes <= 0 || $bytes > PHP_INT_SIZE)
                throw new \Exception("Invalid bytes value '$bytes'");

            return ($bytes * 8) - static::ExponentBits($bytes) - 1;
        }

        public static function Bias(int $exponentBits) : int {
            return intval(ceil(pow(2, ($exponentBits-1))) - 1);
        }

        public static function Unpack(string $value) : float {

        }
//https://www.wikihow.com/Convert-a-Number-from-Decimal-to-IEEE-754-Floating-Point-Representation
        public static function Encode(float $value, int $exponentBits = 8, int $mantissaBits = 23) : string {
            $stringValue = ($value < 0 ? "1" : "0");

            $value = abs($value);

            $exponent  = (int) $value;
            $bias      = (int) pow(2, ($exponentBits-1)) - 1;
            $remainder = fmod($value, 1);

            for ($mantissa = "", $tmp = $remainder, $cnt = 0; $cnt < $mantissaBits ; $cnt ++) {
                $tmp *= 2;

                if ($tmp >= 1) {
                    $tmp -= 1;
                    $mantissa .= "1";
                } else {
                    $mantissa .= "0";
                }
            }

            //$combined = substr(decbin($exponent) . $mantissa);

            

            $newExponent = strlen(decbin($exponent)) + $bias - 1;

            echo "remainder : $remainder\n";
            echo "exponent  : " . decbin($exponent) . "\n";
            echo "newExponent  : " . decbin($newExponent) . "\n";
            
            echo "mantissa  : $mantissa\n";
            //echo "combined  : $combined\n";

            
            return substr($stringValue . " " . decbin($newExponent) . " " . $mantissa, 0, $exponentBits + $mantissaBits + 3);

/*            
            $exponent = (int) $value;
            $bias     = (int) pow(2, ($exponentBits-1)) - 1;
            $remainder = fmod($value, 1);

            for ($mantissa = "", $cnt = 0; $cnt < $mantissaBits ; $cnt ++) {
                echo $cnt . "/$mantissaBits: [$remainder/$mantissa] " . $remainder . "\n";

                $remainder *= 2;

                if ($remainder >= 1) {
                    $mantissa .= "1";
                    $remainder -= 1.0;
                } else {
                    $mantissa .= "0";
                }

                
            }

            if (strlen($mantissa) < $mantissaBits)
                $mantissa .= str_repeat("0", ($mantissaBits - strlen($mantissa)));

            return
                $stringValue . " " .
                sprintf("%0" . $exponentBits . "b", $exponent) . " " .
                $mantissa;

  */          
/*            
            list ($exponent, $remainder) = explode(".", (string) $value);
            $exponent  = (int) $exponent;
            $bias      = (int) pow(2, ($exponentBits-1)) - 1;
            $remainder = (int) $remainder;

            $stringValue .= " " . sprintf("%0" . $exponentBits . "b", ($exponent+$bias));
            $stringValue .= " " . sprintf("%0" . $mantissaBits . "b", $remainder);
            //$remainderBin = decbin($remainder);
            //$stringValue .= " " . $remainderBin . str_repeat("0", $mantissaBits - strlen($remainderBin));
*/
/*            
            $exponent = (int) $value;
            $remainder = (int) fmod($value, 1);

            //
*/
/*
            
            

            $exponent = sprintf("%0" . $exponentBits . "b", decbin((int) $exponent));
            $remainder = decbin((int) $exponent);
            $remainder .= str_repeat("0", $mantissaBits - strlen($remainder));
*/
/*
            $exponent += $bias;
            echo "exponent: $exponent [ " . sprintf("%011b", decbin($exponent)) . " ]\n";
            echo "exponent: $exponent\n";
            echo "bias: $bias\n";
            echo "remainder: $remainder\n";
            //$remainder = $value - floatval($exponent);
            
            
           // $stringValue .= sprintf("%0" . $exponentBits . "b", decbin($exponent));
*/
            return $stringValue;
        }

        public static function Pack(float $value, bool $string = true) : string {
            $valueBits = unpack("P", pack("e", $value))[1];
            
            list ($readNumber, $decimalNumber) = explode(".", (string) $value);
            $valueDecimalPlaces = strlen($decimalNumber);
            
            
            $valueSign         = ($value < 0 ? 1 : 0);
            $valueMantissaBits = static::MantissaBits();
            $valueExponentBits = static::ExponentBits();
            $valueBias         = static::Bias($valueExponentBits);
            $valueExponent     = (($valueBits >> $valueMantissaBits) & (( 1 << $valueExponentBits) - 1)) - $valueBias;
            $valueMantissa     = ($valueBits & (( 1 << $valueMantissaBits) - 1));
            $valueFraction     = ($valueMantissa | (1 << $valueMantissaBits)) / pow(2, $valueMantissaBits);

            echo 'valueBits          : ' . sprintf('%d %011b %052b', $valueSign, $valueExponent, $valueMantissa) . "\n";
            echo "value              : $readNumber $decimalNumber\n";
            echo "valueExponent      : $valueExponent [" . decbin($valueExponent) . "]\n";
            echo "mantissa           : $valueMantissa [" . decbin($valueMantissa) . "]\n";
            echo "valueFraction      : $valueFraction\n";
            echo "valueDecimalPlaces : $valueDecimalPlaces\n\n";

            $value = static::Shrink($value);

            $valueBits = unpack("P", pack("e", $value))[1];

            $valueString = rtrim(number_format($value));
            $valueDecimalPlaces = strlen(substr($valueString, strpos($valueString, ".") + 1)) + 1;
            
            $valueSign         = ($value < 0 ? 1 : 0);
            $valueMantissaBits = static::MantissaBits();
            $valueExponentBits = static::ExponentBits();
            $valueBias         = static::Bias($valueExponentBits);
            $valueExponent     = (($valueBits >> $valueMantissaBits) & (( 1 << $valueExponentBits) - 1)) - $valueBias;
            $valueMantissa     = ($valueBits & (( 1 << $valueMantissaBits) - 1));
            $valueFraction     = ($valueMantissa | (1 << $valueMantissaBits)) / pow(2, $valueMantissaBits);

            echo 'valueBits          : ' . sprintf('%d %011b %052b', $valueSign, $valueExponent, $valueMantissa) . "\n";
            echo "value              : $valueString\n";
            echo "valueExponent      : $valueExponent [" . decbin($valueExponent) . "]\n";
            echo "mantissa           : $valueMantissa [" . decbin($valueMantissa) . "]\n";
            echo "valueFraction      : $valueFraction\n";
            echo "valueDecimalPlaces : $valueDecimalPlaces\n\n";


            return "";
        }
        
        public static function Parts(float $value) : array {
            $floatInt = unpack("Q", pack("d", $value))[1];

            return [
                "sign" => ($value < 0 ? 1 : 0),
                "bias" => static::Bias(static::ExponentBits()),
                "exponent" => (($floatInt >> static::MantissaBits()) & ((1 << static::ExponentBits())-1)),
                "mantissa" => ($floatInt & ((1 << static::MantissaBits())-1))
            ];            
        }

        public static function Exponent(float $value) : int {
            return static::Parts($value)["exponent"];            
        }

        public static function Mantissa(float $value) : int {
            return static::Parts($value)["mantissa"];            
        }

        

        

        public static function Adjust(float $value, int $exponentBits = PHP_EXPONENT_BITS, int $mantissaBits = PHP_MANTISSA_BITS) : float {            
            if ($exponentBits < 0 || $exponentBits > PHP_EXPONENT_BITS)
                throw new \Exception("Invalid EXPONENT_BITS value '$exponentBits'");

            if ($mantissaBits < 0 || $mantissaBits > PHP_MANTISSA_BITS)
                throw new \Exception("Invalid mantissaBits value '$mantissaBits'");

            $bits = unpack("P", pack("e", $value))[1];

            $sign         = ($value < 0 ? 1 : 0);
            $bias         = static::Bias(PHP_EXPONENT_BITS);
            $exponent     = (($bits >> PHP_MANTISSA_BITS) & (( 1 << PHP_EXPONENT_BITS) - 1)) - $bias;
            $mantissa     = ($bits & (( 1 << PHP_MANTISSA_BITS) - 1));
            $fraction     = ($mantissa | (1 << PHP_MANTISSA_BITS)) / pow(2, PHP_MANTISSA_BITS);

            echo "decbin   : " . sprintf('%064b', $bits) . "\n";

            echo 'bits     : ' . sprintf('%d %011b %052b', $sign, $exponent, $mantissa) . "\n";
            echo "exponent : $exponent [" . decbin($exponent) . "]\n";
            echo "mantissa : $mantissa [" . decbin($mantissa) . "]\n";
            echo "fraction : $fraction\n\n";

            if ($exponentBits != PHP_EXPONENT_BITS) {
                for ($cnt = 0; $cnt < PHP_EXPONENT_BITS - $exponentBits; $cnt ++) {
                    $exponent = $exponent << 1;
                    $mantissa = BitWise::GetBits(($mantissa >> 1), 1, PHP_MANTISSA_BITS);

                    
                    
                    
                }

                
                $exponent += (52 - BitWise::UsedBits($mantissa));
                BitWise::EnableBits($mantissa, PHP_MANTISSA_BITS - 1, 1);
                echo BitWise::UsedBits($mantissa);
            }

            
//            if ($mantissaBits != PHP_MANTISSA_BITS)
  //              BitWise::DisableBits($mantissa, 0, PHP_MANTISSA_BITS - $mantissaBits);


            $packedValue = 
                ($sign << (PHP_EXPONENT_BITS + PHP_MANTISSA_BITS)) | 
                (($exponent+$bias) << PHP_MANTISSA_BITS) | 
                $mantissa;

            $packedValue = 0b0100000000000110010001111010111000010100011110010111111010101100;
/*            
            $packedValue = 0;
            BitWise::SetBits($packedValue, 0, $mantissa);
            BitWise::SetBits($packedValue, PHP_MANTISSA_BITS, ($exponent+$bias));
            BitWise::SetBits($packedValue, (PHP_EXPONENT_BITS + PHP_MANTISSA_BITS), $sign);
  */          
            

            $fraction     = ($mantissa | (1 << PHP_MANTISSA_BITS)) / pow(2, PHP_MANTISSA_BITS);

            echo "c: " . $fraction * (pow(2, $exponent)) . "\n";

            echo "decbin   : " . sprintf('%064b', $packedValue) . "\n";
            echo 'bits     : ' . sprintf('%d %011b %052b', $sign, $exponent, $mantissa) . "\n";
            echo "exponent : $exponent [" . decbin($exponent) . "]\n";
            echo "mantissa : $mantissa [" . decbin($mantissa) . "]\n";
            echo "fraction : $fraction\n\n";

            return unpack("e", pack("P", $packedValue))[1];
            
        }

        public static function Shrink(float $value, int $byte = 0) : float {
            if ($byte < 0 || $byte > PHP_INT_SIZE)
                throw new \Exception("Invalid bytes value '$byte'");

            $valueBits = unpack("P", pack("e", $value))[1];

            list ($readNumber, $decimalNumber) = explode(".", (string) $value);
            $valueDecimalPlaces = strlen($decimalNumber);
            
            $valueSign         = ($value < 0 ? 1 : 0);
            $valueMantissaBits = static::MantissaBits();
            $valueExponentBits = static::ExponentBits();
            $valueBias         = static::Bias($valueExponentBits);
            $valueExponent     = (($valueBits >> $valueMantissaBits) & (( 1 << $valueExponentBits) - 1)) - $valueBias;
            $valueMantissa     = ($valueBits & (( 1 << $valueMantissaBits) - 1));
            $valueFraction     = ($valueMantissa | (1 << $valueMantissaBits)) / pow(2, $valueMantissaBits);

            echo 'valueBits          : ' . sprintf('%d %011b %052b', $valueSign, $valueExponent, $valueMantissa) . "\n";
            echo "valueExponent      : $valueExponent [" . decbin($valueExponent) . "]\n";
            echo "mantissa           : $valueMantissa [" . decbin($valueMantissa) . "]\n";
            echo "valueFraction      : $valueFraction\n";
            echo "valueDecimalPlaces : $valueDecimalPlaces\n\n";
            
            $bytes = ($byte == 0 ? PHP_INT_SIZE : $byte);

            for ($byte = max(1, $byte); $byte <= $bytes; $byte ++) {
                echo "\tbyte:      : $byte\n";

                $newValueTotalBits = ($byte * 8);
                $newValueExponentBits = static::ExponentBits($byte);
                $newValueMantissaBits = $newValueTotalBits - $newValueExponentBits - 1;
                $newValueExponentBias = pow(2, ($newValueExponentBits-1)) - 1;

                $newValueMantissa = $valueMantissa;

                echo "newValueMantissa   : " . (((1 << $newValueExponentBits)-1)) . "  \t[" . decbin((((1 << $newValueExponentBits)-1))) . "]\n";
    
                if (intval(abs($valueExponent) > ((1 << $newValueExponentBits)-1)))
                    continue;

                if ($numBits = ($valueMantissaBits - $newValueMantissaBits))
                    BitWise::DisableBits($newValueMantissa, 0, $numBits);

                $packedValue = 0;
                BitWise::SetBits($packedValue, 0, $newValueMantissa);
                BitWise::SetBits($packedValue, $valueMantissaBits, ($valueExponent+$valueBias));
                BitWise::SetBits($packedValue, ((PHP_INT_SIZE * 8) - 1), $valueSign);

                $newFloat = unpack("e", pack("P", $packedValue))[1];

                if ($newFloat == $value)
                    return $newFloat;
                
                echo "\tnewFloat   : " . round($newFloat, $valueDecimalPlaces) . "\n\n";

                if (round($newFloat, $valueDecimalPlaces) == $value)
                    return $newFloat;
            }
            
            return $value;
        }
    }
?>