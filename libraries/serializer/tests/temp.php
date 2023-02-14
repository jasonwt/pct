<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    class Serializer implements ISerializer {
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
        static public function GenerateSerializedCode($value) : int {
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
        static public function ParseSerialCode(int $serialCode) : array {
            $serialCodeInformation = [
                // type
                // sign
                // packcode
                // runlength
            ];

            $runLengthMask = 5;

            $codeType = $serialCode & 0b00000111;

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

        static public function Serialize($data) : string {
            echo decbin($serializedCode = static::GenerateSerializedCode($data)) . "\n";
            print_r($serializedCodeInfo = static::ParseSerialCode($serializedCode));

            $serializedData = "";

            if ($serializedCodeInfo[0] == "null") {

            } else if ($serializedCodeInfo[0] == "bool") {

            } else if (substr($serializedCodeInfo[0], -3) == "int") {
                $serializedData = pack($serializedCodeInfo[1], $data);

            } else if (substr($serializedCodeInfo[0], -5) == "float") {
                $serializedData = pack($serializedCodeInfo[1], $data);

            } else if ($serializedCodeInfo[0] == "string") {
                if (strlen($data) > 30) {
                    $serializedData = static::Serialize(strlen($data)) . $data;
                } else if (strlen($data) > 0) {
                    $serializedData = $data;
                }                    
                
            } else if ($serializedCodeInfo[0] == "array") {
                if (count($data) > 30) {
                    
                } else if (count($data) > 0) {
                    
                }
                
            } else {
                throw new \UnexpectedValueException("Can not serialize value type '" . $serializedCodeInfo[0] . "'");

            }

            return pack("C", $serializedCode) . $serializedData;
        }

        static public function Unserialize(string $data) {
            return static::ParseSerialCode(bindec($data));
        }
    }

?>