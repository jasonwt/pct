<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\libraries\serializer\ISerializer;
    use ReflectionObject;
    use ReflectionProperty;

    use pct\libraries\serializer\PHPSerializer;
    class BinarySerializer implements ISerializer {
        // 0b00000000 : null
        // 0bVVVVVVV1 : unsigned int >= 0 && <= 127
        // 0bDDDBBB10 : float
        // 0bVVVVT100 : array
        // 0bBBBS1000 : int
        // 0bxxx10000 : reserved
        // 0bxx100000 : string
        // 0bV1000000 : bool
        // 0b10000000 : object

        static bool $debugging = false;

        const EXPONENT_BITS = [2,4,6,8,9,10,11,11];

        const PHP_EXPONENT_BITS = (PHP_FLOAT_DIG == 15 ? 11 : 8);
        const PHP_MANTISSA_BITS = (PHP_FLOAT_DIG == 15 ? 52 : 23);
        const PHP_EXPONENT_BIAS = (PHP_FLOAT_DIG == 15 ? 1023 : 127);

        static public function SerializeNull() : string {
            // 0b00000000 : null

            return pack("C", 0b00000000);
        }

        static public function UnserializeNull(string &$data) {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            return null;
        }

        static public function SerializeBool(bool $value) : string {
            // 0bV1000000 : bool

            return pack("C", ($value ? 0b11000000 : 0b01000000));
        }

        static public function UnserializeBool(string &$data) : bool {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            return ($packetHeader == 0b11000000 ? true : false);            
        }

        static public function SerializeArray(array $value) : string {
            $numElements = count($value);
            $assocArray = array_keys($value) !== range(0, $numElements - 1);

            $packets = pack("C", ((min($numElements, 15)) << 4) + (($assocArray ? 1 : 0) << 3) + 0b100);

            if ($numElements >= 15)
                $packets .= static::Serialize($numElements);

            foreach ($value as $k => $v) {
                if ($assocArray)
                    $packets .= static::Serialize($k);

                $packets .= static::Serialize($v);
            }

            return $packets;
        }

        static public function UnserializeArray(string &$data) : array {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            $assocArray = (($packetHeader >> 3) & 1);
            $numElements = (($packetHeader >> 4) & 0b1111);

            if ($numElements == 15)
                $numElements = static::Unserialize($data);

            $arrayValue = [];

            for ($cnt = 0; $cnt < $numElements; $cnt ++) {
                $arrayValueKey = ($assocArray ? static::Unserialize($data) : $cnt);

                $arrayValue[$arrayValueKey] = static::Unserialize($data);
            }

            return $arrayValue;
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

        static public function UnserializeInteger(string &$data) : int {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            if ($packetHeader & 0b01)
                return ($packetHeader >> 1);

            $sign = (($packetHeader >> 4) & 0b1);
            $bytes = (($packetHeader >> 5) & 0b111);

            $intValue = 0;

            for ($bcnt = 0; $bcnt < $bytes; $bcnt ++) {
                if ($bcnt > 0)
                    $intValue = $intValue << 8;

                $intValue |= unpack("C", $data)[1];
                $data = substr($data, 1);
            }

            return $intValue * ($sign ? -1 : 1);
        }

        // if there is no remainder we should set all exponent bits to 1 and use the mantissa to represent the integer value
        static public function SerializeFloat(float $value) : string {
            if (($decimalPlaces = strlen(explode(".", strval($value))[1] ?? "")) > 7)
                $decimalPlaces = 0;
            else
                $value = round($value, $decimalPlaces);
            
            $floatInt = unpack("Q", pack("d", $value))[1];

            $sign = ($value < 0 ? 1 : 0);
            $exponent = ((($floatInt >> static::PHP_MANTISSA_BITS) & ((1 << static::PHP_EXPONENT_BITS)-1)));
            $mantissa = ($floatInt & ((1 << static::PHP_MANTISSA_BITS)-1));  

            if ($exponent != 0)
                $exponent -= static::PHP_EXPONENT_BIAS;

            if (static::$debugging) {
                echo "\nvalue                : $value\n";
                echo "decimalPlaces        : [" . sprintf("%03b", $decimalPlaces) . "] $decimalPlaces \n";                
                echo "floatInt             : [" . sprintf("%064b", $floatInt) . "] $floatInt \n";
                echo "sign                 : $sign\n";
                echo "exponent             : [" . sprintf("%011b", $exponent) . "] $exponent \n";
                echo "mantissa             : [" . sprintf("%052b", $mantissa) . "] $mantissa \n";

                echo "\n";
            }

            $bytes           = 1;
            $newMantissa     = 0;
            $newMantissaBits = 0;
            $newExponent     = 0;

            if ($value == 0)
                return pack("C2", 0b00000010, 0);

            for (; $bytes <= count(static::EXPONENT_BITS); $bytes ++) {
                $signBits = 1;
                $newExponentBits = static::EXPONENT_BITS[$bytes-1];
                $newMantissaBits = ($bytes*8) - $newExponentBits - $signBits;

                $newExponentBias = pow(2, ($newExponentBits-1)) - 1;
                $newExponent = $exponent + $newExponentBias;

                if (static::$debugging) {
                    echo "bytes                : $bytes\n";
                    echo "newExponentBias      : [" . sprintf("%011b", $newExponentBias) . "] $newExponentBias \n";
                    echo "newExponentBits      : [" . sprintf("%011b", $newExponentBits) . "] $newExponentBits \n";
                    echo "newExponent          : [" . sprintf("%0" . $newExponentBits . "b", $newExponent) . "] $newExponent \n";
                    echo "newMantissaBits      : [" . sprintf("%011b", $newMantissaBits) . "] $newMantissaBits \n";
                    echo "newMantissa          : [" . sprintf("%0" . $newMantissaBits . "b", $newMantissa) . "] $newMantissa \n";
                }

                if ($newExponent <= 0 || $newExponent >= ((1 << ($newExponentBits))-1))
                    continue;
                
                $newMantissa = ($mantissa >> (static::PHP_MANTISSA_BITS - $newMantissaBits));

                $floatInt =
                    ($sign << (static::PHP_EXPONENT_BITS + static::PHP_MANTISSA_BITS)) |
                    ((($exponent + static::PHP_EXPONENT_BIAS) & ((1 << static::PHP_EXPONENT_BITS) - 1)) << static::PHP_MANTISSA_BITS) |
                    (($newMantissa << (static::PHP_MANTISSA_BITS - $newMantissaBits)) & ((1 << static::PHP_MANTISSA_BITS)-1));

                $newValue = unpack("d", pack("Q", $floatInt))[1];

                if (static::$debugging) {
                    echo "newValue             : $newValue\n";
                    echo "floatInt             : [" . sprintf("%064b", $floatInt) . "] $floatInt \n";                    

                    echo "\n";
                }

                if ($newValue == $value)
                    break;
                else if ($decimalPlaces > 0 && round($value, $decimalPlaces) == round($newValue, $decimalPlaces))
                    break;
            }
 
            $packetHeader = ($decimalPlaces << 5) | (($bytes-1) << 2) | 0b10;

            $packetValue = 
                $sign << (($bytes*8)-1) |
                ($newExponent << $newMantissaBits) |
                $newMantissa;            

            if (static::$debugging) {
                echo "packetHeader         : [" . sprintf("%08b", $packetHeader) . "] $packetHeader \n";
                echo "packetValue          : [" . sprintf("%0" . ($bytes*8) . "b", $packetValue) . "] $packetValue \n";
            }

            $packedData = "";

            for ($bcnt = 0; $bcnt < $bytes; $bcnt ++) {
                $packedData = pack("C", $packetValue) . $packedData;
                $packetValue = $packetValue >> 8;
            }

            return pack("C", $packetHeader) . $packedData;            
        }

        static public function UnserializeFloat(string &$data) : float {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            $bytes = (($packetHeader >> 2) & 0b111) + 1;
            $decimalPlaces = (($packetHeader >> 5) & 0b111);

            $packedData = 0;

            for ($bcnt = 0; $bcnt < $bytes; $bcnt ++) {
                if ($bcnt > 0)
                    $packedData = $packedData << 8;

                $packedData |= unpack("C", $data)[1];  
                $data = substr($data, 1);    
            }

            if ($packetHeader == 0b00000010 && $packedData == 0)
                return 0;

            $newExponentBits = static::EXPONENT_BITS[$bytes-1];
            $newMantissaBits = ($bytes*8) - $newExponentBits - 1;
            $newExponentBias = pow(2, ($newExponentBits-1)) - 1;

            $sign = ($packedData >> ($newExponentBits + $newMantissaBits));
            $exponent = (($packedData >> $newMantissaBits) & ((1 << $newExponentBits)-1));
            $mantissa = (($packedData & ((1 << $newMantissaBits)-1)) << (static::PHP_MANTISSA_BITS - $newMantissaBits));

            if ($exponent != 0)
                $exponent -= $newExponentBias;

            $value = unpack("d", pack("Q", 
                ($sign << (static::PHP_EXPONENT_BITS + static::PHP_MANTISSA_BITS)) |
                ((($exponent + static::PHP_EXPONENT_BIAS) & ((1 << static::PHP_EXPONENT_BITS)-1)) << (static::PHP_MANTISSA_BITS)) |
                ($mantissa & ((1 << static::PHP_MANTISSA_BITS) - 1))
            ))[1];

            if (static::$debugging) {
                echo "\npacketHeader         : [" . sprintf("%08b", $packetHeader) . "] $packetHeader \n";
                echo "bytes                : $bytes\n";
                echo "decimalPlaces        : $decimalPlaces\n";
                echo "packedData           : [" . sprintf("%0" . ($bytes*8) . "b", $packedData) . "] $packedData \n";
                echo "sign                 : $sign\n";
                echo "exponent             : [" . sprintf("%011b", $exponent) . "] $exponent \n";
                echo "mantissa             : [" . sprintf("%052b", $mantissa) . "] $mantissa \n";
                echo "value                : $value\n";
            }

            if ($decimalPlaces)
                $value = round($value, $decimalPlaces);
            
            return $value;
        }

        static public function SerializeHexfield(string $value) : string {
            $packedHexField = static::SerializeBitfield(base_convert($value, 16, 2));

            $packedHexField[0] = pack("C", 0b10100000);
            
            return $packedHexField;
        }

        static public function UnserializeHexfield(string &$data) : string {
            $data[0] = pack("C", 0b01100000);

            return base_convert(static::UnserializeBitfield($data), 2, 16);
        }

        static public function SerializeBitfield(string $value) : string {
            $packetHeader = 0b01100000;

            $numBits = strlen($value);
            $numBytes = intval(ceil($numBits / 8));

            $packedData = pack("C", $numBits);

            $value = str_pad($value, ($numBytes*8), "0", STR_PAD_LEFT);            

            for ($bcnt = 0; $bcnt < $numBytes; $bcnt ++)
                $packedData .= pack("C", bindec(substr($value, ($bcnt*8), 8)));
            
            return pack("C", $packetHeader) . $packedData;
        }

        static public function UnserializeBitfield(string &$data) : string {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            $numBits = unpack("C", $data)[1];
            $data = substr($data, 1);

            $numBytes = intval(ceil($numBits / 8));

            $bitValues = 0;

            for ($bcnt = 0; $bcnt < $numBytes; $bcnt ++) {
                if ($bcnt > 0)
                    $bitValues = $bitValues << 8;

                $bitValues |= unpack("C", $data)[1];

                $data = substr($data, 1);
            }
            
            return substr(sprintf("%0" . (PHP_INT_SIZE * 8) . "b", $bitValues), -$numBits);
        }

        static public function SerializeString(string $value) : string {
            // 0b00100000 : null terminated string
            // 0b01100000 : n bit bitfield - specify n with next unpack("C") value
            // 0b10100000 : n bit hexfield - specify n with next unpack("C") value
            // 0b11100000 : reserved

            $valueLength = strlen($value);

            if ($valueLength <= 256 && preg_match('/^[01]+$/', $value) == 1)
                return static::SerializeBitfield($value);
            else if ($valueLength <= 16 && preg_match('/^[0-9a-fA-F]+$/', $value) == 1)
                return static::SerializeHexfield($value);

            $packets = pack("C", 0b00100000) . pack("Z*", $value);

            return $packets;
        }

        static public function UnserializeString(string &$data) : string {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            $stringValue = unpack("Z*", $data)[1];
            $data = substr($data, strlen($stringValue)+1);

            return $stringValue;
        }

        static public function SerializeObject(object $value) : string {
            $packetHeader = 0b10000000;

            $getClassParts = explode('\\', get_class($value));

            $className = array_pop($getClassParts);
            $classNamespace = implode('\\', $getClassParts);

            $packedData = pack("Z*", $className);
            $packedData .= pack("Z*", $classNamespace);

            $objectReflection = new ReflectionObject($value);

            $classProperties = [];

            foreach ([ReflectionProperty::IS_PRIVATE, ReflectionProperty::IS_PROTECTED, ReflectionProperty::IS_PUBLIC] as $protectionLevel) {
                foreach ($objectReflection->getProperties($protectionLevel) as $property) {
                    $classProperties[$property->getName()] = [
                        $protectionLevel,
                        $property->getValue($value)
                    ];
                }
            }

            $packedData .= static::Serialize($classProperties);

            return pack("C", $packetHeader) . $packedData;
        }

        static public function UnserializeObject(string &$data) : object {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            $className = unpack("Z*", $data)[1];
            $data = substr($data, strlen($className)+1);

            $classNamespace = unpack("Z*", $data)[1];
            $data = substr($data, strlen($classNamespace)+1);

            $fullClassName = ($classNamespace != "" ? $classNamespace . "\\" : "") . $className;

            $classProperties = static::Unserialize($data);
            
            return unserialize(PHPSerializer::Serialize($fullClassName, $classProperties));
        }

        static public function Serialize($value) : ?string {

            if (($valueType = gettype($value)) == "NULL") {
                return static::SerializeNull();

            } else if ($valueType == "boolean") {
                return static::SerializeBool($value);

            } else if ($valueType == "array") {
                return static::SerializeArray($value);

            } else if ($valueType == "integer") {
                return static::SerializeInteger($value);

            } else if ($valueType == "double") {
                return static::SerializeFloat($value);

            } else if ($valueType == "string") {
                return static::SerializeString($value);

            } else if ($valueType == "object") {
                return static::SerializeObject($value);                

            }
            
            throw new \Exception("Unsupported type: " . gettype($value));            
        }

        static public function Unserialize(string &$data) {
            $packetHeader = unpack("C", $data)[1];

//            echo "\nUnserialize packetHeader: [" . sprintf("%08b", $packetHeader) . "] $packetHeader\n";

            if ($packetHeader == 0) {
                return static::UnserializeNull($data);

            } else if ((($packetHeader & 0b1) == 0b1) || (($packetHeader & 0b1111) == 0b1000)) {
                return static::UnserializeInteger($data);

            } else if (($packetHeader & 0b11) == 0b10) {
                return static::UnserializeFloat($data);

            } else if (($packetHeader & 0b111) == 0b100) {
                return static::UnserializeArray($data);

            } else if (($packetHeader & 0b11111111) == 0b01100000) {
                return static::UnserializeBitfield($data);

            } else if (($packetHeader & 0b11111111) == 0b10100000) {
                return static::UnserializeHexfield($data);

            } else if (($packetHeader & 0b111111) == 0b100000) {
                return static::UnserializeString($data);

            } else if (($packetHeader & 0b1111111) == 0b1000000) {
                return static::UnserializeBool($data);

            } else if (($packetHeader & 0b11111111) == 0b10000000) {
                return static::UnserializeObject($data);
            }

            throw new \Exception();
        }
    }
?>