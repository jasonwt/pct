<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/ICompression.php");

    abstract class Compression implements ICompression {
        public static function SerializeValue($value) : string {
            $serializedData = "";
            
            if (is_string($value)) {
                $serializedData .= "Z" . pack('Z*', $value);
            } else if (is_int($value)) {
                $absValue = abs($value);

                if ($absValue <= 255) {
                    $serializedData .= ($value < 0 ? "c" : "C") . pack('C', $absValue);
                } else if ($absValue <= 65535) {
                    $serializedData .= ($value < 0 ? "s" : "S") . pack('S', $absValue);
                } else if ($absValue <= 4294967295) {
                    $serializedData .= ($value < 0 ? "l" : "L") . pack('L', $absValue);
                } else {
                    $serializedData .= ($value < 0 ? "q" : "Q") . pack('Q', $absValue);
                }
            } else if (is_float($value)) {
                $fvalue = (int) (round($value, 4) * 10000);
                $absValue = abs($fvalue);

                if ($absValue <= 255) {
                    $serializedData .= ($value < 0 ? "w" : "W") . pack('C', $absValue);
                } else if ($absValue <= 65535) {
                    $serializedData .= ($value < 0 ? "x" : "X") . pack('S', $absValue);
                } else if ($absValue <= 4294967295) {
                    $serializedData .= ($value < 0 ? "y" : "Y") . pack('L', $absValue);
                } else {
                    throw new \UnexpectedValueException("floating point arguments must be inbetween the range of -429496.xxxx and +4294967.xxxx");
                }
            } else {
                throw new \UnexpectedValueException("Can not serialize value type '" . gettype($value) . "'");
            }

            return $serializedData;
        }

        public static function UnserializeString(string $data) {
            if (($data = ltrim($data)) == "")
                return "";

            $valueType = $data[0];

            $value = unpack(
                ["Z"=>"Z*","W"=>"C","C"=>"C","X"=>"S","S"=>"S","Y"=>"L","L"=>"L","Q"=>"Q"][strtoupper($valueType)], 
                $data, 
                1
            );

            if (is_null($value))
                return null;
            else
                $value = $value[1];

            if (in_array($valueType, ["c", "s", "l", "q", "w", "x", "y"]))
                $value = -$value;

            if (in_array(strtoupper($valueType), ["W","X","Y"]))
                $value = (float) $value / 10000.0;            

            echo "ValueType: $valueType\n";
            echo "Value: $value\n";

            return $value;
        }

        protected static function UnserializeArgument(string $data) :?array {
            if (is_null($name = static::UnserializeString($data)))
                return null;

            if (is_null($value = static::UnserializeString(substr($data, strlen($name)+1))))
                return null;

            return [$name => $value];
        }

        protected static function SerializeArgument(string $name, $value) : ?string {
            if (preg_match('~^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$~', $name) != 1)
                throw new UnexpectedValueException("invalid argument name '$name'");            

            if (is_null($serializedName = static::SerializeValue($name)))
                return null;

            if (is_null($serializedValue = static::SerializeValue($value)))
                return null;

            return $serializedName . $serializedValue;
        }

        public static function GetHeader($data, array $compressArguments = []) : ?array {
            $headerArray = [
                "magicNumber" => static::GetMagicNumber(),
                "algorithm" => substr(md5(static::GetCompressionAlgorithm()), 0, 4),
                "majorVersion" => static::GetMajorVersion(),
                "minorVersion" => static::GetMinorVersion(),
                "compressedDataSize" => 0,
                "uncompressedDataSize" => 0,
                "compressArgumentsSize" => 0,
                "compressArguments" => null
            ];

            if (is_string($data)) {
                $headerArray["magicNumber"] = substr($data, 0, 4);
                $headerArray["algorithm"] = substr($data, 3, 4);                

                if (($versions = unpack('C2', $data, 7)) === false)
                    return null;
                else
                    list ($headerArray["majorVersion"], $headerArray["minorVersion"]) = array_values($versions);

                if (($sizes = unpack('L2', $data, 9)) === false)
                    return null;
                else
                    list ($headerArray["compressedDataSize"], $headerArray["uncompressedDataSize"]) = array_values($sizes);

                if (($headerArray["compressArgumentSize"] = unpack('S1', $data, 17)) === false)
                    return null;
                else
                    $headerArray["compressArgumentSize"] = $headerArray["compressArgumentSize"][1];

            } else {
                if (count($compressArguments) > 0) {
                    foreach ($compressArguments as $name => $value)
                        $headerArray["compressArgumentsSize"] += strlen(static::SerializeArgument($name, $value));
                    
                    $headerArray["compressArguments"] = $compressArguments;
                }
            }

            return $headerArray;
        }
    }

?>