<?php
    declare(strict_types=1);
	
    namespace pct\libraries\compression\binarytree;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\libraries\compression\Compression;
    use pct\libraries\compression\binarytree\IBinaryTreeCompression;

    class BinaryTreeCompression extends Compression implements IBinaryTreeCompression {
        public static function GetMagicNumber() : string {
            return "PCT";
        }

        public static function GetCompressionAlgorithm() : string {
            return "BinaryTreeCompression";
        }

        public static function GetMajorVersion() : int {
            return 0;
        }

        public static function GetMinorVersion() : int {
            return 6;
        }

        protected static function CalculateCharacterWeights(string $data) : array {
            $characterWeights = [];

            for ($cnt = 0; $cnt < strlen($data); $cnt ++)
                $characterWeights[ord($data[$cnt])] = ($characterWeights[ord($data[$cnt])] ?? 0) + 1;

            return $characterWeights;
        }



/*
***** S - size in bytes of value *****
00 = 8bit
01 = 16bit
10 = 32bit
11 = 64bit

***** R - number of concurrent values
neg int   : 000
neg float : 001
pos int   : 010
pos float : 011
null      : 100 RR RRR
bool      : 101 RR RRR
string    : 110 RR RRR
array     : 111 RR RRR
*/
        public static function GenerateSerializeCode($value) : int {
            $serializeCode = 0;

            if (is_string($value)) {
                $serializeCode |= 0b00000110;

                if (strlen($value) < 31)
                    $serializeCode |= (strlen($value) << 3);
                else
                    $serializeCode |= 0b11111000;

            } else if (is_null($value)) {
                $serializeCode |= 0b00000100;

            } else if (is_bool($value)) {
                $serializeCode |= ($value ? 0b00001101 : 0b00000101);

                //die(decbin($serializeCode));
            } else if (is_numeric($value)) {
                if (is_float($value)) {
                    $serializeCode |= 0b00000001;

                    $value *= 1000;
                }
                    
                if ($value >= 0)
                    $serializeCode |= 0b00000010;

                $absValue = abs($value);

                if ($absValue > 4294967295)
                    $serializeCode |= 0b00011000;
                else if ($absValue > 65535)
                    $serializeCode |= 0b00010000;
                else if ($absValue > 255)
                    $serializeCode |= 0b00001000;

            } else if (is_array($value)) {
                $serializeCode |= 0b00000111;

                if (count($value) < 31)
                    $serializeCode |= (count($value) << 3);
                else
                    $serializeCode |= 0b11111000;
                    
            } else {
                throw new \UnexpectedValueException("Can not serialize value type '" . gettype($value) . "'");
            }

            echo decbin($serializeCode) . "\n";

            return $serializeCode;
        }

        /*
***** S - size in bytes of value *****
00 = 8bit
01 = 16bit
10 = 32bit
11 = 64bit

***** R - number of concurrent values
neg int   : 000
neg float : 001
pos int   : 010
pos float : 011
null      : 100 RR RRR
bool      : 101 RR RRR
string    : 110 RR RRR
array     : 111 RR RRR
*/

        public static function ParseSerialCode(int $serialCode) : array {
            echo decbin($serialCode) . "\n";

            $serialCodeInformation = [
                // type
                // sign
                // packcode
                // runlength
            ];

            $runLengthMask = 5;

            $codeType = $serialCode & 0b00000111;

            echo "codeType: $codeType" . "\n";

            if ($codeType == 0b000 || $codeType == 0b001 || $codeType == 0b010 || $codeType == 0b011) {
                $serialCodeInformation[] = ($serialCode & 0b10 ? "+" : "-") . ($serialCode & 0b1 ? "float" : "int");

                if ((($serialCode & 0b00011000) >> 3) == 0b11)
                    $serialCodeInformation[] = "Q";
                else if ((($serialCode & 0b00011000) >> 3) == 0b10)
                    $serialCodeInformation[] = "L";
                else if ((($serialCode & 0b00011000) >> 3) == 0b01)
                    $serialCodeInformation[] = "S";
                else
                    $serialCodeInformation[] = "C";

                $runLengthMask = 3;
            } else if ($codeType == 0b100) {
                $serialCodeInformation[] = "null";
            } else if ($codeType == 0b101) {
                $serialCodeInformation[] = "bool";
                $serialCodeInformation[] = ($serialCode & 0b00001000 ? true : false);
                $runLengthMask = 4;
            } else if ($codeType == 0b110) {
                $serialCodeInformation[] = "string";

                $serialCodeInformation[] = "Z*";
            } else if ($codeType == 0b111) {
                $serialCodeInformation[] = "array";
            }

            $runLength = $serialCode >> (8 - $runLengthMask);

            $serialCodeInformation[] = $runLength;

            return $serialCodeInformation;

        }




        public static function SerializedValueCode($value) : string {
/*


// NULL DATA TYPE

R = concurrent types

000 = null
001 = bool
010 = pos int
011 = neg int
100 = pos float
101 = neg float
110 = string
111 = array

***** S - size in bytes of value *****
00 = 8bit
01 = 16bit
10 = 32bit
11 = 64bit

***** R - number of concurrent values

null      : 000 RR RRR
bool      : 001 RR RRR
pos int   : 010 SS RRR
neg int   : 011 SS RRR
pos float : 100 SS RRR
neg float : 101 SS RRR
string    : 110 RR RRR
array     : 111 RR RRR


for int and float types


000 = null
001 = bool
010 = int
011 = float
100 = string
101 = array
110 = 

00 = 8bit
01 = 16bit
10 = 32bit
11 = 64bit

000000xx = int
000001xx = float
000010xx = bool
000011xx = string

0000xxxx = neg
0001xxxx = pos

000xxxxx = 1
001xxxxx = 2
010xxxxx = 3
011xxxxx = 4
100xxxxx = 5
101xxxxx = 6
110xxxxx = 7
111xxxxx = 8

            
               RRRRR PPP
            Z*
            C
            S
            L
            Q

*/
            return "";

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
            return "";

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

        protected static function BuildCompressionTree(array $data) : ?array {
/*
            Node Array Structure [
                ?string asciiCode, 
                int weight, 
                ?array leftNode, 
                ?array rightNode
            ]
*/
            

            if (is_int($data[array_key_first($data)])) {
                $nodes = [];

                asort($data);

                // if we are parsing character weights
                foreach ($data as $k => $v)
                    $nodes[] = [$k, $v, null, null];

                while (count($nodes) > 1) {
                    usort($nodes, function (array $leftNode, array $rightNode) {
                        return ($leftNode[1] == $rightNode[1] ? 0 : (
                            $leftNode[1] < $rightNode[1] ? - 1 : 1
                        ));
                    });

                    $leftNode  = array_shift($nodes);
                    $rightNode = array_shift($nodes);

                    $nodes[] = [null, $leftNode[1] + $rightNode[1], $leftNode, $rightNode];
                }

                return $nodes[0];
            } else if(is_string($data[array_key_first($data)])) {
                // should be a binary string of 1's and 0's
                $nodes = [null, 0, null, null];

                foreach ($data as $asciiCode => $mask) {                    
                    $ptr = &$nodes;

                    for ($pcnt = 0; $pcnt < strlen($mask); $pcnt ++) {
                        $index = ($mask[$pcnt] == "0" ? 2 : 3);

                        if (is_null($ptr[$index]))
                            $ptr[$index] = [null, 0, null, null];

                        $ptr = &$ptr[$index];
                    }

                    $ptr[0] = $asciiCode;
                }

                return $nodes;
            } else {
                throw new \UnexpectedValueException("Expected an array with members as a character weights array of ints or a mask binary string of 1's and 0's");
            }

            return null;
        }

        protected static function BuildCompressionMasks(array $treeNode, string $mask = "") : ?array {
/*
            Tree Mask Array Structure

            string asciiCode => [
                int weight,
                string mask
            ]
*/
            $returnMasks = [];

            if (!is_null($treeNode[0]))
                return [$treeNode[0] => [$treeNode[1], $mask]];
                
            if (!is_null($treeNode[2]))
                $returnMasks = $returnMasks + static::BuildCompressionMasks($treeNode[2], $mask . "0");

            if (!is_null($treeNode[3]))
                $returnMasks = $returnMasks + static::BuildCompressionMasks($treeNode[3], $mask . "1");
                    
            return $returnMasks;
        }

        public static function GenerateHeader($data, array $arguments = []) : ?array {
            if (is_string($data)) {         
                return static::UnserializeString($data);  
            } else if (is_array($data)) {
                $headerArray = parent::GenerateHeader($data, $arguments) + [                
                    "masks" => []
                ];            

                for ($mcnt = 0; $mcnt < 256; $mcnt ++) {
                    if (isset($data[$mcnt])) {
                        $headerArray["uncompressedDataSize"] += $data[$mcnt][0];
                        $headerArray["compressedDataSize"] += ($data[$mcnt][0] * strlen($data[$mcnt][1]));
                        $headerArray["masks"][$mcnt] = $data[$mcnt][1];    
                    } else {
                        $headerArray["masks"][$mcnt] = "";
                    }
                }
  
                $headerArray["compressedDataSize"] = 
                    (int) ($headerArray["compressedDataSize"] / 8) + ($headerArray["compressedDataSize"] % 8 ? 1 : 0);

            } else {
                throw new \UnexpectedValueException("GenerateHeader() argumented expectected to be a binary string or Tree Mask array.");
            }

            return $headerArray;
        }

        public static function Compress(string $uncompressedData, array $arguments = []) : ?string {
            $characterWeights = static::CalculateCharacterWeights($uncompressedData);

            if (is_null($tree = static::BuildCompressionTree($characterWeights)))
                return null;

            if (is_null($masks = static::BuildCompressionMasks($tree)))
                return null;

            if (is_null($header = static::GenerateHeader($masks, $arguments)))
                return null;

            

            //for ($mcnt = 0; $mcnt < 256; $mcnt ++)
             //   $binaryHeaderData .= pack('S1', bindec("1" . ($header["masks"][$mcnt] ?? "")));

            $binaryHeaderData = static::SerializeValue($header);
            
            $compressedData = "";
            $tmpBuffer = "";

            for ($cnt = 0; $cnt < strlen($uncompressedData); $cnt ++) {
                $tmpBuffer .= $header["masks"][ord($uncompressedData[$cnt])];

                while (strlen($tmpBuffer) >= 8) {
                    $compressedData .= pack('C', bindec(substr($tmpBuffer, 0, 8)));
                    $tmpBuffer = substr($tmpBuffer, 8);
                }
            }
            
            if (strlen($tmpBuffer) > 0)
                $compressedData .= pack('C', bindec($tmpBuffer . str_repeat("0", 8 - strlen($tmpBuffer))));

            //print_r(static::BuildCompressionTree($header["masks"]));

            return $binaryHeaderData . $compressedData;
        }
         
        public static function Decompress(string $compressedData) : string {
            throw new \Exception("Not implemented.");
        }
    }

?>