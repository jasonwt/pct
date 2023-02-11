<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    interface ICompression {
        public static function Compress(string $uncompressedData, array $arguments = []) : ?string;
        public static function Decompress(string $compressedData) : ?string;

        public static function GetHeader($data, array $compressArguments = []) : ?array;

        public static function GetMagicNumber() : string;
        public static function GetCompressionAlgorithm() : string;
        public static function GetMajorVersion() : int;
        public static function GetMinorVersion() : int;
    }

?>