<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/../Compression.php");

    require_once(__DIR__ . "/IBinaryTreeCompression.php");

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

        protected static function BuildCompressionTree(array $data) : ?array {
/*
            Node Array Structure [
                ?string asciiCode, 
                int weight, 
                ?array leftNode, 
                ?array rightNode
            ]
*/
            $nodes = [];

            if (is_int($data[array_key_first($data)])) {
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
            } else {
                throw new \UnexpectedValueException("Expected an array with members as a character weights array of ints or a mask binary string of 1's and 0's");
            }

            return $nodes[0];
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

        public static function GetHeader($data, array $arguments = []) : ?array {
            $headerArray = parent::GetHeader($data, $arguments) + [                
                "masks" => []
            ];            

            if (is_string($data)) {                
                for ($mcnt = 0; $mcnt < 256; $mcnt ++) {
                    if (is_null($maskLength = unpack('S', $data, 19 + ($mcnt*2))))
                        return null;

                    if (($maskLength = substr(decbin($maskLength[1]), 1)) == "")
                        continue;

                    $headerArray["masks"][$mcnt] = $maskLength;
                }
            } else if (is_array($data)) {
                foreach ($data as $asciiCode => $info) {
                    $headerArray["uncompressedDataSize"] += $info[0];
                    $headerArray["compressedDataSize"] += ($info[0] * strlen($info[1]));
                    $headerArray["masks"][$asciiCode] = $info[1];
                }

                $headerArray["compressedDataSize"] = 
                    (int) ($headerArray["compressedDataSize"] / 8) + ($headerArray["compressedDataSize"] % 8 ? 1 : 0);

            } else {
                throw new \UnexpectedValueException("GetHeader() argumented expectected to be a binary string or Tree Mask array.");
            }

            return $headerArray;
        }

        public static function Compress(string $uncompressedData, array $arguments = []) : ?string {
            $characterWeights = static::CalculateCharacterWeights($uncompressedData);

            if (is_null($tree = static::BuildCompressionTree($characterWeights)))
                return null;

            if (is_null($masks = static::BuildCompressionMasks($tree)))
                return null;

            if (is_null($header = static::GetHeader($masks, $arguments)))
                return null;

            print_r($header);

            $binaryHeaderData =
                $header["magicNumber"] .
                $header["algorithm"] .
                
                pack('C2', $header["majorVersion"], $header["minorVersion"]) .
                pack('L2', $header["compressedDataSize"], $header["uncompressedDataSize"]) .
                pack('S1', $header["compressArgumentsSize"]);


            for ($mcnt = 0; $mcnt < 256; $mcnt ++)
                $binaryHeaderData .= pack('S1', bindec("1" . ($header["masks"][$mcnt] ?? "")));

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

            print_r(static::BuildCompressionTree($header["masks"]));

            return $binaryHeaderData . $compressedData;
        }
         
        public static function Decompress(string $compressedData) : string {
            throw new \Exception("Not implemented.");
        }
    }

?>