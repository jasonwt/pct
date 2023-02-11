<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/IRunLengthCompression.php");
    require_once(__DIR__ . "/../Compression.php");

    class RunLengthCompression extends Compression implements IRunLengthCompression {
        public static function Compress(string $uncompressedData) : ?string {
            if (strlen($uncompressedData) == 0)
                return "";

            $encoded = "";
            $lastChar = null;
            $rl = 0;

            for ($cnt = 0; $cnt < strlen($uncompressedData); $cnt ++) {
                if (is_null($lastChar) || $uncompressedData[$cnt] != $lastChar || $rl == 255) {
                    if (!is_null($lastChar))
                        $encoded .= chr($rl);
                    
                    $encoded .= ($lastChar = $uncompressedData[$cnt]);

                    $rl = 0;
                } else {
                    $rl ++;
                }
            }

            return $encoded . chr($rl);
        }
         
        public static function Decompress(string $compressedData) : string {
            throw new \Exception("Not implemented.");
        }
    }

?>