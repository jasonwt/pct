<?php
    declare(strict_types=1);
	
    namespace pct\libraries\compression;

    interface ICompression {
        public static function Compress(string $uncompressedData, array $arguments = []) : ?string;
        public static function Decompress(string $compressedData) : ?string;

        public static function GenerateHeader($data, array $compressArguments = []) : ?array;
        
        public static function GetMagicNumber() : string;
        public static function GetCompressionAlgorithm() : string;
        public static function GetMajorVersion() : int;
        public static function GetMinorVersion() : int;
    }

?>