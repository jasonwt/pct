<?php
    declare(strict_types=1);

    namespace pct\libraries\compression\lz4;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\libraries\compression\Compression;

    class RunLengthCompression extends Compression implements ILZ4Compression {
        const BLOCK_SIZE = 64 * 1024;
        const LZ4_MAX_LEN = 64;
        const LZ4_OFFSET_LIMIT = 1 << 16;

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
            $inputSize = strlen($uncompressedData);

            $output = "";

            $inputIndex = 0;
            $outputIndex = 0;
        
            while ($inputIndex < $inputSize) {
                $matchLength = 0;
                $matchIndex = 0;
        
                for ($i = 1; $i <= static::LZ4_MAX_LEN && $inputIndex + $i < $inputSize; $i++) {
                    $lookaheadIndex = $inputIndex + $i;
        
                    for ($j = 0; $j < $inputIndex && $lookaheadIndex + $j < $inputSize; $j++) {
                        if ($uncompressedData[$inputIndex + $j] != $uncompressedData[$lookaheadIndex + $j]) {
                            break;
                        }
        
                        if ($j + 1 > $matchLength) {
                            $matchLength = $j + 1;
                            $matchIndex = $inputIndex - ($lookaheadIndex - $inputIndex);
                        }
                    }
                }
        
                if ($matchLength == 0) {
                    $output[$outputIndex++] = $uncompressedData[$inputIndex++];
                } else {
                    $offset = $inputIndex - $matchIndex;
                    $length = $matchLength;
        
                    while ($length > 0xff) {
                        $output[$outputIndex++] = 0xff;
                        $output[$outputIndex++] = 0xff;
                        $output[$outputIndex++] = ($offset >> 8) & 0xff;
                        $output[$outputIndex++] = $offset & 0xff;
                        $length -= 0xff;
                    }
        
                    $output[$outputIndex++] = $length;
                    $output[$outputIndex++] = ($offset >> 8) & 0xff;
                    $output[$outputIndex++] = $offset & 0xff;
        
                    $inputIndex += $matchLength;
                }
            }

            return $output;
        
            //return $outputIndex;
        }
         
        public static function Decompress(string $compressedData) : string {
            throw new \Exception("Not implemented.");
        }
    }

?>