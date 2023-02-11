<?php
    declare(strict_types=1);

    namespace pct\libraries\compression;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\libraries\compression\ICompression;

    abstract class Compression implements ICompression {
        public static function SerializedValueCode($value) : string {
            
            if (is_null($value)) {
                return "N";
            } else if (is_bool($value)) {
                return ($value ? "T" : "F");            
            } else if (is_string($value)) {
                if (preg_match('/^[01]+$/', $value)) {
                    $value = bindec("1" . $value);
                    $absValue = abs($value);

                    if ($absValue <= 255)
                        return "h";
                    else if ($absValue <= 65535)
                        return "i";
                    else if ($absValue <= 4294967295)
                        return "j";
                    else 
                        return "k";
                    
                } else if (preg_match('/^[0-9A-Fa-f]+$/', $value)) {
                    $value = hexdec("FF" . $value);
                    $absValue = abs($value);

                    if ($absValue <= 255)
                        return "H";
                    else if ($absValue <= 65535)
                        return "I";
                    else if ($absValue <= 4294967295)
                        return "J";
                    else
                        return "K";
                    
                } else {
                    return "Z";
                }
            } else if (is_int($value)) {
                $absValue = abs($value);

                if ($absValue <= 255) {
                    return ($value < 0 ? "c" : "C");
                } else if ($absValue <= 65535) {
                    return ($value < 0 ? "s" : "S");
                } else if ($absValue <= 4294967295) {
                    return ($value < 0 ? "l" : "L");
                } else {
                    return ($value < 0 ? "q" : "Q");
                }
            } else if (is_float($value)) {
                $fvalue = (int) (round($value, 4) * 10000);
                $absValue = abs($fvalue);

                if ($absValue <= 255) {
                    return ($value < 0 ? "w" : "W");
                } else if ($absValue <= 65535) {
                    return ($value < 0 ? "x" : "X");
                } else if ($absValue <= 4294967295) {
                    return ($value < 0 ? "y" : "Y");
                } else {
                    throw new \UnexpectedValueException("floating point arguments must be inbetween the range of -429496.xxxx and +4294967.xxxx");
                }
            } else if (is_array($value)) {
                if (array_keys($value) === range(0, count($value) - 1)) 
                    return "a";

                return "A";
            } else {
                throw new \UnexpectedValueException("Can not serialize value type '" . gettype($value) . "'");
            }
        }

        public static function SerializeValue($value) : string {
            

            $valueType = static::SerializedValueCode($value);

            if (!is_array($value))
                echo "SerializeValue : $valueType : " . print_r($value, true) . "\n";
            else
                echo "SerializeValue : $valueType : ---- BEGIN ARRAY ----\n";

            $serializedData = $valueType;

            if ($valueType == "N") {
            } else if (in_array($valueType, ["T", "F"])) {                
            } else if ($valueType == "Z") {
                $serializedData .= pack('Z*', $value);
            } else if (in_array($valueType, ["h", "i", "j", "k"])) {
                $value = bindec("1" . $value);
                $absValue = abs($value);

                $serializedData .= pack(["h"=>"C","i"=>"S","j"=>"L","k"=>"Q"][$valueType], $absValue);
            } else if (in_array($valueType, ["H", "I", "J", "K"])) {
                $value = hexdec("FF" . $value);
                $absValue = abs($value);

                $serializedData .= pack(["H"=>"C","I"=>"S","J"=>"L","K"=>"Q"][$valueType], $absValue);
            } else if (in_array($valueType, ["C","c","S","s","L","l","Q","q"])) {
                $absValue = abs($value);

                $serializedData .= pack(strtoupper($valueType), $absValue);
            } else if (in_array(strtoupper($valueType), ["W","X","Y"])) {
                $fvalue = (int) (round($value, 4) * 10000);
                $absValue = abs($fvalue);

                $serializedData .= pack(["W"=>"C","X"=>"S","Y"=>"L"][strtoupper($valueType)], $absValue);
            } else if (in_array(strtoupper($valueType), ["A","B"])) {
                if (count($value) == 0)
                    return "";

                $serializedData .= static::SerializeValue(count($value));

                $valueKeys = array_keys($value);

                for ($cnt = 0; $cnt < count($value); $cnt ++) {
                    $valueKey = $valueKeys[$cnt];
                    $val = $value[$valueKey];

                    if ($valueType == "B" || $valueType == "A")
                        $serializedData .= static::SerializeValue($valueKey);

                    $serializedData .= static::SerializeValue($val);                    
                }

                


/*                

                $arrayGroups = [];

                $head = 0;
                $tail = 0;

                $valueKeys = array_keys($value);

                echo "\nbegin:\n";
                while ($head < count($value)) {
                    $head ++;
                    while ($head < count($value)) {
                        if (static::SerializedValueCode($value[$valueKeys[$tail]]) != static::SerializedValueCode($value[$valueKeys[$head]])) {
                            $arrayGroups[] = $head;
                            break;
                        }                        

                        $head ++;
                    }
                    $tail = $head;

                    echo "\ntail: $tail\n";
                    echo "head: $head\n\n";
                }

                print_r($arrayGroups);

                $cnt = 0;

                while (count($arrayGroups) > 0) {
                    $lastCnt = $cnt;

                    $serializedData .= static::SerializedValueCode($value[$valueKeys[$cnt]]);

                    $tmpData = "";

                    while ($cnt < $arrayGroups[0]) {
//                        echo "cnt: " . print_r($cnt, true) . "\n";
  //                      echo "value: " . print_r($value, true ) . "\n";
    //                    echo "2: " . print_r($value[$valueKeys[$cnt]], true) . "\n";
                        
                        
                        

                        if ($valueType == "B" || $valueType == "A") {
                            $tmpData .= static::SerializeValue($valueKeys[$cnt]);
                            $tmpData .= substr(static::SerializeValue($value[$valueKeys[$cnt]]), 1);
                        } else {
                            $tmpData .= substr(static::SerializeValue($value[$valueKeys[$cnt]]), 1);
                        }

                        $cnt ++;
                    }

                    echo "cnt: " . ($cnt - $lastCnt) . "\n";
                    
                    

                    $serializedData .= static::SerializeValue($cnt-$lastCnt);

                    


                    array_shift($arrayGroups);
                }

                

               
                */
            } else {
                throw new \UnexpectedValueException("Can not serialize value type '$valueType'");
            }

            return $serializedData;
        }

        public static function UnserializeString(string &$data) {
            if (strlen($data) == 0)
                return null;

            $value = null;
            $valueType = $data[0];

            $data = substr($data, 1);

            if (strtoupper($valueType) == "A" || strtoupper($valueType) == "B" ) {
                if ($data == "")                
                    throw new \UnderflowException("Expected more data.");
                
                    $value = [];

                if (($numArrayElements = static::UnserializeString($data)) === false)
                    return false;

                for ($cnt = 0; $cnt < $numArrayElements; $cnt ++) {
                    if ($valueType == "A" || $valueType == "B")
                        $value[static::UnserializeString($data)] = static::UnserializeString($data);
                    else
                        $value[] = static::UnserializeString($data);
                } 
            } else if ($valueType == "N") {
                $value = null;
            } else if ($valueType == "T") {
                $value = true;
            } else if ($valueType == "F") {
                $value = false;
            } else {
                $packMatch = [
                    "Z*" => ["Z"],
                    "C"  => ["C", "W", "H"],
                    "S"  => ["X", "S", "I"],
                    "L"  => ["Y", "L", "J"],
                    "Q"  => ["Q", "K"]
                ];

                $checkPackMatch = array_filter($packMatch, function ($v, $k) use ($valueType) {
                    return in_array(strtoupper($valueType), $v);
                }, ARRAY_FILTER_USE_BOTH);

                if (count($checkPackMatch) == 0)
                    throw new \Exception("Invalid valueType '$valueType'");

                if (($value = unpack(key($checkPackMatch), $data, 0)) === false)
                    throw new \Exception("Pack failed");
                    
                $value = $value[1];

                if (strtoupper($valueType) == "Z") {
                    $data = substr($data, strlen($value)+1);
                } else if (strtoupper($valueType) == "W" || strtoupper($valueType) == "C" || strtoupper($valueType) == "H") {
                    $data = substr($data, 1);
                } else if (strtoupper($valueType) == "S" || strtoupper($valueType) == "X" || strtoupper($valueType) == "I") {
                    $data = substr($data, 2);
                } else if (strtoupper($valueType) == "L" || strtoupper($valueType) == "Y" || strtoupper($valueType) == "J") {
                    $data = substr($data, 4);
                } else if (strtoupper($valueType) == "Q" || strtoupper($valueType) == "K") {
                    $data = substr($data, 8);
                }

                if (in_array($valueType, ["h","i","j","k"]))
                    $value = substr(decbin($value), 1);
                else if (in_array($valueType, ["H","I","J","K"]))
                    $value = substr(dechex($value), 2);
                else if (in_array($valueType, ["c","s","l","q","w","x","y"]))
                    $value = -$value;
                else if (in_array(strtoupper($valueType), ["W","X","Y"]))
                    $value = (float) $value / 10000.0;            
            }

            return $value;
        }

        public static function GenerateHeader($data, array $compressArguments = []) : ?array {
            $headerArray = [
                "magicNumber" => static::GetMagicNumber(),
                "algorithm" => substr(md5(static::GetCompressionAlgorithm()), 0, 4),
                "majorVersion" => static::GetMajorVersion(),
                "minorVersion" => static::GetMinorVersion(),
                "compressedDataSize" => 0,
                "uncompressedDataSize" => 0
            ];

            if (is_string($data)) {
                return static::UnserializeString($data);
            } else {
                if (count($compressArguments) > 0)
                    $headerArray["compressArguments"] = $compressArguments;                    
            }

            return $headerArray;
        }
    }

?>