<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    class Serializer implements ISerializer {
//        
        static public function packFloat(float $value, float $maxError = 0.00000001) : string {
            $sign = ($value < 0 ? -1 : 1);

            $absValue = abs($value);

            $exponents = [1, 2, 5, 8, 9, 10, 11, 11];

            $minValueWithError = $value - ($value * $maxError);
            $maxValueWithError = $value + ($value * $maxError);

            for ($ecnt = 0; $ecnt < count($exponents); $ecnt ++) {
                $calculatedValue = 0;

                if ($ecnt == 0) {
                    $calculatedValue = (float) ((int) ($value * 127.0)) / 127.0;

                    if ($absValue > 1.0)
                        continue;

                } else {
                    $exponentBits = $exponents[$ecnt];
                    $mantissaBits = (($ecnt + 1) * 8) - $exponentBits - 1;

                    $maxExponent = (1 << $exponentBits) - 1;
                    $maxMantissa = (1 << $mantissaBits) - 1;
        
                    $bias = pow(2, $exponentBits - 1) - 1;

                    if ($absValue > ($maxValue = abs(1 - pow(2, -$mantissaBits)) * pow(2, pow(2, $exponentBits) - 1)))
                        continue;
                    
                    $exponent =  (int) floor(log($absValue, 2)) + 1;

                    echo $exponent . "\n";
                    $mantissa =  intval(($absValue * pow(2, -$exponent) * (float) $maxMantissa));

                    $calculatedValue = $sign * ((float) $mantissa / (float) $maxMantissa) * pow(2, $exponent);
                }

                if (abs($calculatedValue) < abs($minValueWithError) || abs($calculatedValue) > abs($maxValueWithError))
                    continue;
            
                break;
            }

            $ecnt = min($ecnt, count($exponents)-1);

            if ($ecnt == 0)
                return pack("c", $value * 127);

            $value = ($sign >= 0 ? 1 : 0);
            $value = ($value << $exponentBits) + ($exponent + $bias);
            $value = ($value << $mantissaBits) + $mantissa;

            $packedData = "";

            for ($cnt = 0; $cnt <= $ecnt; $cnt ++) {
                $packedData = pack("C", ($value & ((1 << 8) - 1))) . $packedData;

                $value = $value >> 8; 
            }

            return $packedData;
        }
    //
        static public function unpackFloat(string $data) : float {
            $strLen = strlen($data);

            $exponents = [1, 2, 5, 8, 9, 10, 11, 11];

            if ($strLen == 1)
                return (float) unpack("c", $data)[1] / 127.0;
            else if ($strLen > 8)
                throw new \Exception("Invalid strlen: " . strlen($data));
            
            $exponentBits = $exponents[$strLen-1];
            $mantissaBits = ($strLen * 8) - $exponentBits - 1;
            $maxMantissa = (1 << $mantissaBits) - 1;

            $bias = pow(2, $exponentBits - 1) - 1;

            $value = 0;
            $unpackedData = unpack("C*", $data);

            foreach ($unpackedData as $v) {
                $value = $value << 8;
                $value |= $v;
            }

            $mantissa = ($value & ((1 << $mantissaBits)-1));
            $exponent = (($value >> $mantissaBits) & ((1<< $exponentBits)-1)) - $bias;
            $sign     = (($value & (1 << ((strlen($data)*8)-1))) ? 1 : -1);

            return $sign * ((float) $mantissa / (float) $maxMantissa) * pow(2, $exponent);
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

/*

        VVVVVVV*: small unsigned int
        EEEEBB*0: custom float
        xxxxx*00: reserved
        BBBS*000: int
        BBB*0000: IEEE754 float
        Nx*00000: string
        V*000000: bool
        *0000000: null


        EEEEBBB*: float

        VVVVV0*0: small array
        VVVVV1*0: small array assoc

        VBBBS*00: int

        BBB0*000: large array
        BBB1*000: large array assoc
        
        xxxxxxx1: number

        xxxxxx01: int

        xxxS0010: int including value
        xxxS1010: int including bytes

        xxxxx110: float
        xxxx0110: float including bytes
        xxxx1110: float

        xxxxxxx1: array

        xxxxxx10: number
        SEBBB010: int
        EEBBB110: float


        xxxxxxx1: number
        SEEBBB01: int
        EEEBBB11: float


        xxxxxxx1: array
        xxxxxx10: int
        xxxxx100: float
        xxxT1000: string
        xxT10000: bool
        xx100000: null
        x1000000: reserved
        10000000: reserved
*/
        static public function GenerateSerializedCode($value) : int {
            $serializeCode = 0;

           if (is_string($value)) {
                $serializeCode |= 0b00001000;
            } else if (is_null($value)) {
                $serializeCode |= 0b00100000;
            } else if (is_bool($value)) {
                $serializeCode |= ($value ? 0b00110000 : 0b00010000);
            } else if (is_float($value)) {
            } else if (is_int($value)) {
                $serializeCode |= ($value < 0 ? 0b00000010 : 0b00000110);                

                if (abs($value) > 4294967295)
                    $serializeCode |= 0b00011000;
                else if (abs($value) > 65535)
                    $serializeCode |= 0b00010000;
                else if (abs($value) > 255)
                    $serializeCode |= 0b00001000;

            } else if (is_array($value)) {
                $serializeCode |= 0b00000001;

                if (count($value) <= 126)
                    $serializeCode |= ((count($value) << 1));
                else
                    $serializeCode |= 0b11111110;
                    
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
neg int   : RRR RR 000
neg float : RRR RR 001
pos int   : RRR RR 010
pos float : RRR RR 011
null      : RRR RR 100
bool      : RRR RR 101
string    : RRR RR 110
array     : RRR RR 111
*/  


/*
        xxxxxxx1: array
        xxxxxx10: int
        xxxxx100: float
        xxxx1000: string
        xxx10000: bool
        xx100000: null
        x1000000: reserved
        10000000: reserved
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