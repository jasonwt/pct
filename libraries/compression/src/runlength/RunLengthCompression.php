<?php
    declare(strict_types=1);

    namespace pct\libraries\compression\runlength;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\libraries\compression\Compression;

    class RunLengthCompression extends Compression implements IRunLengthCompression {
        public static function GetMagicNumber(): string { 
            throw new \Exception("Not implemented.");
        }

        public static function GetCompressionAlgorithm(): string { 
            throw new \Exception("Not implemented.");
        }

        public static function GetMajorVersion(): int { 
            throw new \Exception("Not implemented.");
        }

        public static function GetMinorVersion(): int { 
            throw new \Exception("Not implemented.");
        }

        public static function Compress(string $uncompressedData, array $arguments = []) : ?string {
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