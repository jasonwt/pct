<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    /*******************************************************************************************/

    class CompressionMasks {
        protected $masks = [];

        public function __construct($data) {
            if (is_array($data)) {
            }
        }

        public function AddMask(int $asciiCode, int $weight, string $mask) {
            $this->masks[] = [
                "asciiCode" => $asciiCode,
                "weight" => $weight,
                "mask" => $mask
            ];
        }

        public function GetMask(int $asciiCode) : ?array {
            return $this->masks[$asciiCode] ?? null;
        }

        public function GetMasks() : array {
            return $this->masks;
        }
    }

    /*******************************************************************************************/

    interface ICompressionTree {
        public function Build(array $asciiCodeFreqArray) : ICompressionTree;
    }

    class CompressionTree implements ICompressionTree {
        public function Build(array $asciiCodeFreqArray) : ICompressionTree {
            asort($asciiCodeFreqArray);

            $nodes = [];

            foreach ($asciiCodeFreqArray as $k => $v)
                $nodes[] = new CompressionTreeNode($k, $v, null, null);

            while (count($nodes) > 1) {
                usort($nodes, function (CompressionTreeNode $left, CompressionTreeNode $right) {
                    return ($left->GetWeight() == $right->GetWeight() ? 0 : (
                        $left->GetWeight() < $right->GetWeight() ? - 1 : 1
                    ));
                });

                echo "\n************************************************\n" . print_r($nodes, true) . "\n************************************************\n";

                $leftNode  = array_shift($nodes);
                $rightNode = array_shift($nodes);

                $nodes[] = new CompressionTreeNode(null, $leftNode->GetWeight() + $rightNode->GetWeight(), $leftNode, $rightNode);
                
                
                readline();
            }

            echo "\n************************************************\n" . print_r($nodes, true) . "\n************************************************\n";
            
            return $nodes[0];
        }
    }
    
    /*******************************************************************************************/


    class CompressionTreeNode {
        protected ?int $asciiCode;
        protected int $weight = 0;
        protected ?CompressionTreeNode $left = null;
        protected ?CompressionTreeNode $right = null;

        public function __construct(?int $asciiCode, int $weight, ?CompressionTreeNode $left, ?CompressionTreeNode $right) {
            $this->asciiCode = $asciiCode;
            $this->weight = $weight;
            $this->left = $left;
            $this->right = $right;
        }

        public function GetAsciiCode() : ?int {
            return $this->asciiCode;
        }

        public function GetWeight() : int {
            return $this->weight;
        }

        public function GetLeft() : ?CompressionTreeNode {
            return $this->left;
        }

        public function GetRight() : ?CompressionTreeNode {
            return $this->right;
        }
    }

    /*******************************************************************************************/

    class CompressionHeader {
        protected string $magicNumber = "PCT";
        protected int $majorVersion = 0;
        protected int $minorVersion = 0;
        protected string $reserved = "          ";
        protected int $uncompressedSize = 0;
        protected int $compressedSize = 0;

        protected CompressionMasks $masks;

        public function __construct($data) {
            if ($data instanceof CompressionMasks) {
                $masks = $data->GetMasks();

                for ($cnt = 0; $cnt < 256; $cnt ++) {
                    if (($header["maskLengths"][$cnt] = strlen($masks[$cnt]["mask"] ?? "")) > 0) {
                        $header["masks"][$cnt] = bindec($masks[$cnt]["mask"]);
                        $header["uncompressedSize"] += $masks[$cnt]["weight"];
                    }
                }
            } else if (is_string($data)) {

            }
        }

        public function GetMagicNumber() : string {
            return $this->magicNumber . "." . $this->minorVersion;
        }

        public function GetMajorVersion() : int {
            return $this->majorVersion;
        }

        public function GetMinorVersion() : int {
            return $this->minorVersion;
        }

        public function GetUncompressedSize() : int {
            return $this->uncompressedSize;
        }

        public function GetCompressedSize() : int {
            return $this->compressedSize;
        }

        public function GetMasks() : CompressionMasks {
            return $this->masks;
        }

        public function GetBinaryHeader() : string {
            $binaryData = 
                $this->GetMagicNumber() .
                pack('C*', $this->GetMajorVersion(), $this->GetMinorVersion()) .
                pack('C*', explode("", $this->reserved)) .
                pack('L', $this->GetUncompressedSize());

/*                
            foreach ($header["maskLengths"] as $maskLength)
                $binaryData .= pack('C', $maskLength);

            for ($cnt = 0; $cnt < count($header["maskLengths"]); $cnt ++)
                if ($header["maskLengths"][$cnt] > 0)
                    $binaryData .= pack('C', $mask[$cnt]["mask"]);
*/
            return $binaryData;
        }
    }

    /*******************************************************************************************/

    class Compression {
        public static function CalculateCharacterWeights(string $data) : array {
            $characterWeights = [];

            for ($cnt = 0; $cnt < strlen($data); $cnt ++)
                $characterWeights[ord($data[$cnt])] = ($characterWeights[ord($data[$cnt])] ?? 0) + 1;

            arsort($characterWeights);

            return $characterWeights;
        }

        public static function CalculateMasks(array $node, bool $rleMask = false, string $mask = "") : array {
            $maskArray = [];

            if (!is_null($node["left"])) {
                foreach (self::CalculateMasks($node["left"], $rleMask, $mask . "0") as $k => $v)
                    $maskArray[$k] = $v;
                
            }

            if (!is_null($node["right"])) {
                foreach (self::CalculateMasks($node["right"], $rleMask, $mask . "1") as $k => $v)
                    $maskArray[$k] = $v;
            }

            if (!is_null($node["character"]))
                $maskArray[$node["character"]] = [
                    "weight" => $node["weight"],
                    "mask" => ($rleMask ? Compression::RunLengthEncode($mask) : $mask),
                    "maskLen" => strlen($rleMask ? Compression::RunLengthEncode($mask) : $mask)
                ];

                
                  
            
            return $maskArray;
        }

        public static function RunLengthEncodeMasks(array $masks) : array {
            return [];
        }

        public static function CalculateCompressedDataSize(array $masks) : int {
            $totalSize = 0;

            foreach ($masks as $character => $info) {
               // list ($charCount, $mask) = explode(":", $info);

               // $totalSize += ((int) $charCount * strlen($mask));
               $totalSize += ((int) $info["weight"] * strlen($info["mask"]));
            }

            $byteSize = (int) ($totalSize / 8);

            return $byteSize + ($totalSize % 8 != 0 ? 1 : 0);
        }

        public static function CalculateCompressedHeaderSize(array $masks) : int {
            return 0;
        }

        public static function BalancedTree(array $characterWeights, int $bias) : array {
            $nodes = [];

            foreach ($characterWeights as $k => $v) {
                $nodes[] = [
                    "character" => $k,
                    "weight" => $v,
                    "left" => null,
                    "right" => null
                ];
            }

            while (count($nodes) > 1) {
                $newNodes = [];

  //              echo "\n************************************************\n" . print_r($nodes, true) . "\n************************************************\n";

                while (count($nodes) > 0) {
                    $leftNode = array_shift($nodes);

                    if (count($nodes) > 0) {
                        $rightNode = array_pop($nodes);

                        $newNodes[] = [
                            "character" => null,
                            "weight"    => $leftNode["weight"] + $rightNode["weight"],
                            "left"      => $leftNode,
                            "right"     => $rightNode
                        ];
                        
                    } else {
                        $newNodes[] = $leftNode;
                    }
                }

                uasort($newNodes, function ($a, $b) { 
                    return ($a["weight"] == $b["weight"] ? 0 : (
                        $a["weight"] > $b["weight"] ? -1 : 1
                    ));
                });

//                readline();

                $nodes = $newNodes;
            }
            
            return $newNodes;
        }

        public static function JasonTree(array $characterWeights, int $bias = 0) : array {
            $bias = min(max(0, $bias), 255);

            asort($characterWeights);

            $nodes = [];

            foreach ($characterWeights as $k => $v) {
                $nodes[] = [
                    "character" => $k,
                    "weight" => $v,
                    "left" => null,
                    "right" => null,
                    "bias" => $bias
                ];
            }

            while (count($nodes) > 1) {
                
                usort($nodes, function ($a, $b) {
                    $aWeight = (float) $a["weight"] * (1.0 + (((float) ($a["bias"] ?? -1) + 1) / 64.0));
                    $bWeight = (float) $b["weight"] * (1.0 + (((float) ($b["bias"] ?? -1) + 1) / 64.0));

                    return ($aWeight == $bWeight ? 0 : (
                        $aWeight < $bWeight ? - 1 : 1
                    ));
                });

//                echo "\n************************************************\n" . print_r($nodes, true) . "\n************************************************\n";

                $leftNode  = array_shift($nodes);
                $rightNode = array_shift($nodes);

                $nodes[] = [
                    "character" => null,
                    "weight" => $leftNode["weight"] + $rightNode["weight"],
                    "left" => $leftNode,
                    "right" => $rightNode
                ];          
                
    //            readline();
            }

  //          echo "\n************************************************\n" . print_r($nodes, true) . "\n************************************************\n";
            
            return $nodes[0];
        }

        public static function CompressionTree(array $characterWeights, int $bias = 0) : CompressionTreeNode {
            $bias = min(max(0, $bias), 255);

            asort($characterWeights);

            $nodes = [];

            foreach ($characterWeights as $k => $v)
                $nodes[] = new CompressionTreeNode($k, $v, null, null);

            while (count($nodes) > 1) {
                usort($nodes, function (CompressionTreeNode $left, CompressionTreeNode $right) {
                    return ($left->GetWeight() == $right->GetWeight() ? 0 : (
                        $left->GetWeight() < $right->GetWeight() ? - 1 : 1
                    ));
                });

                echo "\n************************************************\n" . print_r($nodes, true) . "\n************************************************\n";

                $leftNode  = array_shift($nodes);
                $rightNode = array_shift($nodes);

                $nodes[] = new CompressionTreeNode(null, $leftNode->GetWeight() + $rightNode->GetWeight(), $leftNode, $rightNode);
                
                readline();
            }

            echo "\n************************************************\n" . print_r($nodes, true) . "\n************************************************\n";
            
            return $nodes[0];
        }

        public static function RunLengthEncode(string $str) : string {
            if (strlen($str) == 0)
                return "";

            $encoded = "";
            $lastChar = null;
            $rl = 0;

            for ($cnt = 0; $cnt < strlen($str); $cnt ++) {
                if (is_null($lastChar) || $str[$cnt] != $lastChar || $rl == 255) {
                    if (!is_null($lastChar))
                        $encoded .= chr($rl);
                    
                    $encoded .= ($lastChar = $str[$cnt]);

                    $rl = 0;
                } else {
                    $rl ++;
                }
            }

            return $encoded . chr($rl);
        }

        public static function CreateBinaryHeader(array $mask) : string {
            $header = [
                "magicNumber" => "PCT",
                "version" => [
                    "major" => 0,
                    "minor" => 1
                ],
                "uncompressedSize" => 0,
                "maskLengths" => [],
                "masks" => []
            ];

            for ($cnt = 0; $cnt < 256; $cnt ++) {
                if (($header["maskLengths"][$cnt] = strlen($mask[$cnt]["mask"] ?? "")) > 0) {
                    $header["masks"][$cnt] = bindec($mask[$cnt]["mask"]);
                    $header["uncompressedSize"] += $mask[$cnt]["weight"];
                }
            }

            print_r($header);


            $binaryData = 
                $header["magicNumber"] . 
                pack('C', $header["version"]["major"]) . 
                pack('C', $header["version"]["minor"]) .
                pack('L', $header["uncompressedSize"]);

            foreach ($header["maskLengths"] as $maxLength)
                $binaryData .= pack('C', $maxLength);

            for ($cnt = 0; $cnt < count($header["maskLengths"]); $cnt ++)
                if ($header["maskLengths"][$cnt] > 0)
                    $binaryData .= pack('C', $mask[$cnt]["mask"]);


            return $binaryData;
        }

        public static function Compress(string $data, int $biasMod = 0) : array {
            $biasMod = min(max(0, $biasMod), 255);

            $characterWeights = self::CalculateCharacterWeights($data);
            
            $trees = [];
            $masks = [];
            $sizes = [];

            for ($bias = 0; $bias < 256; $bias ++) {
                if ($bias != 0)
                    if ($bias % $biasMod != 0)
                        continue;

                $trees[$bias] = self::CompressionTree($characterWeights, $bias);
                $masks[$bias] = self::CalculateMasks($trees[$bias]);
                $sizes[$bias] = self::CalculateCompressedDataSize($masks[$bias]);
            
                if ($biasMod == 0)
                    break;
            }

            $tree = $trees[array_key_first($sizes)];
            $mask = $masks[array_key_first($sizes)];
            $size = $sizes[array_key_first($sizes)];

           

            $results = [
                "tree" => $tree,
                "mask" => $mask,
                "size" => $size
            ];

            return $results;
        }
    }

    $dataString = "";

    srand(100);

    if (count($argv) > 1) {
        if ($argv[1][0] == "-") {
            for ($cnt = 1; $cnt <= (int) substr($argv[1], 1); $cnt ++)
                $dataString .= str_repeat((string) chr(ord('a') + ($cnt-1)), (rand(1, 100)*$cnt));
        } else {
            $dataString = file_get_contents($argv[1]);
        }
        
    } else {
        $dataString = file_get_contents("Compress.php");
    }


    
//    $dataString = "aaaaaaabbbbbbbbcccccdddee";


    

    

    $compressedData = Compression::Compress($dataString, 0);

    print_r($compressedData);
    die();

/*    
    foreach ($compressedData["mask"] as $k => $v) {
        echo "[$k] => ";

        list ($weight, $mask) = explode(":", $v, 2);

        echo $weight . ":";

        echo Compression::RunLengthEncode($mask) . "\n";
    }
*/
    echo Compression::RunLengthEncode("this is a test");

    

    echo "orignalSize: " . (strlen($dataString));
    /*

    $dataSize = strlen($dataString);
    $masks = Compression::CalculateMasks($compressedData["tree"][0]);
    $compressedDataSize = ceil(Compression::CalculateCompressedDataSize($masks) / 8);
    $compressedDataRatio = sprintf("%0.2f", ($dataSize / $compressedDataSize) * 100);
    
    arsort($masks);

//    print_r($compressedData);

    echo "\n";
    //echo "dataString          : " . $dataString . "\n";
    echo "dataStringSize      : " . $dataSize . "\n";
    echo "masks               : " . print_r($masks, true) . "\n";
    echo "compressedDataSize  : " . $compressedDataSize . "\n";
    echo "compressedDataRatio : " .  $compressedDataRatio . "%\n";
    echo "\n";

    */
?>