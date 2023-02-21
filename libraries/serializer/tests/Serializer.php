<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    //require_once(__DIR__ . "/../vendor/autoload.php");

    require_once(__DIR__ . "/BitWise.php");
    require_once(__DIR__ . "/IEEE754.php");

/*
00000000: null

VVVVVVV1: pos int 0 - 127

xxxxxx10: number
DDBBBS10: number
BBBST100: number

xxxx1000: array

xxx10000: reserved
 
xx100000: string
00100000: null terminated string
01100000: php serialize null terminated string
10100000: hex string
11100000: binary string

x1000000: bool
01000000: bool false
11000000: bool true

10000000: reserved


DDDRRR10: float

exponent = (num bytes / 2) + E

8 * 4 = 32

2 + 7
18446744073709551615
VVVVV100: array
xxxS1000: integer
xxx10000: float
xx100000: string



B1000000: bool
10000000:
*/

    class Serializer {

//      VVVVVVV*: small unsigned int
//      BBBS*000: int      

        static public function SerializeInt(int $value) : string {
            if ($value >= 0 && $value <= 127) {
                $packet = $value << 1;

                return pack("C", BitWise::SetBits($packet, 0, 1, 1));
            } 

            $packetHeader = 0b00001000;

            if ($value < 0)
                BitWise::EnableBits($packetHeader, 4, 1);

            $value = abs($value);

            $packedBytes = [];

            while ($value > 0) {
                $packedBytes[] = BitWise::GetBits($value, 0, 8);
                $value = $value >> 8;
            }

            BitWise::SetBits($packetHeader, 5, count($packedBytes), 3);

            return pack("C*", $packetHeader, ...$packedBytes);
        }

        static public function UnserializeInt(string &$data) : int {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            if (BitWise::GetBits($packetHeader, 0, 1) == 1)
                return BitWise::GetBits($packetHeader, 1, 7);
            
            $value = 0;

            $numBytes = BitWise::GetBits($packetHeader, 5, 3);

            echo "numBytes: $numBytes [" . decbin($numBytes) . "]\n";
            

            for ($bcnt = 0; $bcnt < $numBytes; $bcnt ++) {
                BitWise::SetBits($value, ($bcnt * 8), unpack("C", $data)[1], 8);
                $data = substr($data, 1);                
            }
            
            if (BitWise::GetBits($packetHeader, 4, 1))
                $value = -$value;

                
            return $value;
        }

//      PPPBBB*0: float
/*
10000000000
int mantissa = 123456; // example mantissa represented as an integer
int exponent = -3; // example exponent
float result = (float) (mantissa * Math.pow(2, exponent)); // generate floating-point number


*/

        static public function SerializeFloat(float $value, int $precision = 6) : string {
            $packetHeader = 0b00000010;

            $value = round($value, $precision);

            $bits = unpack("P", pack("e", $value))[1];

            $sign         = ($value < 0 ? 1 : 0);
            $bias         = intval(ceil(pow(2, (PHP_EXPONENT_BITS-1))) - 1);
            $exponent     = (($bits >> PHP_MANTISSA_BITS) & (( 1 << PHP_EXPONENT_BITS) - 1)) - $bias;
            $mantissa     = ($bits & (( 1 << PHP_MANTISSA_BITS) - 1));
            $fraction     = ($mantissa | (1 << PHP_MANTISSA_BITS)) / pow(2, PHP_MANTISSA_BITS);

            $exponentBits = strlen(decbin(abs($exponent)));

            echo "value: $value [" . sprintf("%064b", $bits) . "]" . "\n";
            echo "decbin   : " . sprintf('%064b', $bits) . "\n";
            echo 'bits     : ' . sprintf('%d %011b %052b', $sign, $exponent, $mantissa) . "\n";
            echo "exponent : $exponent [" . decbin($exponent) . "]\n";
            echo "exponentBits : $exponentBits [" . decbin($exponentBits) . "]\n";
            echo "mantissa : $mantissa [" . decbin($mantissa) . "]\n";
            echo "fraction : $fraction\n\n";

            for ($byteCnt = 0; $byteCnt < 8; $byteCnt ++) {
                for ($mantissaCnt = 0; $mantissaCnt < (($byteCnt*8)-$exponentBits-1); $mantissaCnt ++) {
                    $newBits = 0;
                    $newMantissa = $mantissa;

                    BitWise::SetBits($newBits, ((PHP_INT_SIZE*8)-1), $sign, 1);
                    BitWise::SetBits($newBits, PHP_MANTISSA_BITS, ($exponent+$bias), PHP_EXPONENT_BITS);
                    BitWise::SetBits($newBits, 0, $newMantissa, PHP_MANTISSA_BITS);

                    BitWise::DisableBits($newBits, 0, PHP_MANTISSA_BITS - $mantissaCnt);

//                    echo 'bits     : ' . sprintf('%064b', $newBits) . "\n";

                    echo "$byteCnt:$mantissaCnt " . round(unpack("e", pack("P", $newBits))[1], $precision) . "\n";
                }

                echo "\n";
            }

            exit(0);
        }

        static public function UnserializeFloat(string &$data) : float {
            $floatValue = 0;

            echo $data . "\n";

            exit(1);

            return $floatValue;
        }


//      VVVVK*00: array

        static public function SerializeArray($value) : string {
            $packetHeader = 0b00000100;
            $otherPackets = "";

            echo count($value) . "\n";

            if ($isAssocArray = (array_keys($value) !== range(0, count($value) - 1)))
                BitWise::SetBits($packetHeader, 3, 1);

            if (($numArrayElements = count($value)) < 15) {
                BitWise::SetBits($packetHeader, 4, $numArrayElements, 4);
            } else {
                BitWise::SetBits($packetHeader, 4, 15, 4);

                $otherPackets = static::SerializeInt($numArrayElements);
            }

            foreach ($value as $k => $v) {
                if ($isAssocArray)
                    $otherPackets .= static::Serialize($k);

                $otherPackets .= static::Serialize($v);
            }
            
            
            return pack("C", $packetHeader) . $otherPackets;
        }

        static public function UnserializeArray(string &$data) : array {
            $returnArray = [];

            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            if (($numArrayElements = BitWise::GetBits($packetHeader, 4, 4)) == 15) {
                throw new \Exception("To many Elements");
            }

            $isAssoc = BitWise::GetBits($packetHeader, 3, 1) == 1;

            for ($cnt = 0; $cnt < $numArrayElements; $cnt ++) {
                $k = ($isAssoc ? static::Unserialize($data) : $cnt);

                $returnArray[$k] = static::Unserialize($data);
            }

            return $returnArray;
        }

//      Nx*00000: string

        static public function SerializeString(string $value) : string {
            $packetHeader = 0b00100000;

            return pack("C", $packetHeader) . pack("Z*", $value);
        }

        static public function UnserializeString(string &$data) : string {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            $returnString = unpack("Z*", $data)[1];

            $data = substr($data, strlen($returnString)+1);

            return $returnString;
        }


        /*

        BBBBBS10: number
        BBBBB100: number cont

        00000000: null
        VVVVVVV*: custom unsigned 7 bit integer        
        DDRRRS*0: number        
        VVVVK*00: array
        xxx0*00: no decimal number
        xxx1*00: no exponent number        
        
        xxx*0000: reserved
        Nx*00000: string
        V*000000: bool
        10000000: reserved
*/

        static public function aUnserializeNumber(string &$data) {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            if (BitWise::GetBits($packetHeader, 0, 1) == 1)
                return BitWise::GetBits($packetHeader, 1, 7);
            

            if (BitWise::GetBits($packetHeader, 0, 2) == 0b10) {
                // real only
            }

            if (BitWise::GetBits($packetHeader, 0, 3) == 0b100) {
                // decimal only
            }
        }
/*

        xxxxxx10: number
        DDBBBS10: number
        BBBST100: number

*/
        static public function SerializeNumber($value) : string {
            $packetHeader = 0b00000010;

            if (($getType = gettype($value)) != "double" && $getType != "integer")                
                throw new \Exception();
            
            if ($getType == "integer" && $value >= 0 && $value <= 127) {
                $packet = $value << 1;

                return pack("C", BitWise::SetBits($packet, 0, 1, 1));
            }

            

            $numberParts = explode(".", (string) abs($value));

            $real = (int) $numberParts[0];
            $decimal = (int) ($numberParts[1] ?? 0);

            $realBytes = (int) ($real == 0 ? 0 : ceil(strlen(decbin($real)) / 8));
            $realBits = decbin($real);
            $decimalBytes = (int) ($decimal == 0 ? 0 : ceil(strlen(decbin($decimal)) / 8));
            $decimalBits = decbin($decimal);
            
            echo "real         : $real [$realBits]\n";
            echo "realBytes    : $realBytes [$realBytes]\n";
            echo "decimal      : $decimal [$decimalBits]\n";
            echo "decimalBytes : $decimalBytes [$decimalBytes]\n";

            $otherPackets = "";

            if ($realBytes == 0 || $decimalBytes == 0) {
                $packetHeader = 0b00000100;

                if ($value < 0)
                    BitWise::EnableBits($packetHeader, 3, 1);

                if ($decimalBytes == 0) {
                    BitWise::EnableBits($packetHeader, 4, 1);
                    BitWise::SetBits($packetHeader, 5, ($decimalBytes + 1), 3);
                    $bits = $decimalBits;
                } else {
                    BitWise::SetBits($packetHeader, 5, ($realBytes + 1), 3);
                    $bits = $realBits;
                }

                while ($bits != "") {
                    $otherPackets .= pack("C", bindec(substr($bits, -8)));
                    $bits = (strlen($bits) <= 8 ? "" : substr($bits, 0, strlen($bits) - 8));
                }                
            } else {
                BitWise::EnableBits($packetHeader, 1, 1);

                if ($value < 0)
                    BitWise::EnableBits($packetHeader, 2, 1);
                    
                BitWise::SetBits($packetHeader, 3, ($realBytes-1), 3);
                BitWise::SetBits($packetHeader, 6, ($decimalBytes-1), 2);
                
                while ($realBits != "") {
                    $otherPackets .= pack("C", bindec(substr($realBits, -8)));
                    $realBits = (strlen($realBits) <= 8 ? "" : substr($realBits, 0, strlen($realBits) - 8));
                }
                 
                while ($decimalBits != "") {
                    $otherPackets .= pack("C", bindec(substr($decimalBits, -8)));                
                    $decimalBits = (strlen($decimalBits) <= 8 ? "" : substr($decimalBits, 0, strlen($decimalBits) - 8));
                }
            }

            

            return pack("C", $packetHeader) . $otherPackets;
        }

        static public function UnserializeNumber(string &$data) {
            $packetHeader = unpack("C", $data)[1];
            $data = substr($data, 1);

            if (BitWise::GetBits($packetHeader, 0, 1) == 0b1)
                return BitWise::GetBits($packetHeader, 1, 7);

            if (BitWise::GetBits($packetHeader, 0, 2) == 0b10) {
                $realValue = 0;
                $decimalValue = 0;

                $realBytes = BitWise::GetBits($packetHeader, 3, 3) + 1;
                $decimalBytes = BitWise::GetBits($packetHeader, 6, 2) + 1;
                $sign = (BitWise::GetBits($packetHeader, 3, 1) == 1 ? -1 : 1);

                for ($cnt = 0; $cnt < $realBytes; $cnt ++) {
                    BitWise::SetBits($realValue, ($cnt*8), unpack("C", $data)[1], 8);
                    $data = substr($data, 1);
                }

                for ($cnt = 0; $cnt < $decimalBytes; $cnt ++) {
                    BitWise::SetBits($decimalValue, ($cnt*8), unpack("C", $data)[1], 8);
                    $data = substr($data, 1);
                }

                echo "realValue    : $realValue [" . decbin($realValue) . "]\n";
                echo "decimalValue : $decimalValue [" . decbin($decimalValue) . "]\n";
            
                echo "realBytes    : $realBytes [$realBytes]\n";
                echo "decimalBytes : $decimalBytes [$decimalBytes]\n";

                return floatval($realValue . "." . $decimalValue) * ($sign);
            }
                



            return "";
        }

/*
        VVVVVVV*: custom unsigned 7 bit integer
        VVVBBB*0: float
        VVVVK*00: array
        BBBS*000: int
        VVV*0000: group
        Nx*00000: string
        V*000000: bool
        *0000000: null
*/

        static public function Serialize($data) : string {
            
            if (is_string($data)) {
                return static::SerializeString($data);
            } else if (is_null($data)) {
                return pack("C", 0b10000000);
            } else if (is_bool($data)) {
                return ($data ? pack("C", 0b11000000) : pack("C", 0b01000000));
            } else if (is_float($data)) {
                return static::SerializeNumber($data);
                //return static::SerializeFloat($data);
            } else if (is_int($data)) {               
                return static::SerializeNumber($data); 
                //return static::SerializeInt($data);
            } else if (is_array($data)) {
                return static::SerializeArray($data);
            }

            throw new \Exception("Unsupported datatype: " . gettype($data));
        }

        static public function Unserialize(string &$data) {
            $packet = unpack("C", $data)[1];

            echo "packet: [" . sprintf("%08d", decbin($packet)) . "]\n";

            if (BitWise::GetBits($packet, 0, 1) == 0b1) {
                // custom unsigned 7 bit integer
                return static::UnserializeInt($data);

            } else if (BitWise::GetBits($packet, 0, 2) == 0b10) {
                // custom float

            } else if (BitWise::GetBits($packet, 0, 3) == 0b100) {
                
                return static::UnserializeArray($data);
            } else if (BitWise::GetBits($packet, 0, 4) == 0b1000) {
                // integer
                return static::UnserializeInt($data);

            } else if (BitWise::GetBits($packet, 0, 5) == 0b10000) {
                // IEEE754 float

            } else if (BitWise::GetBits($packet, 0, 6) == 0b100000) {
                // strings
                return static::UnserializeString($data);

            } else {
                $data = substr($data, 1);

                // bool
                if (BitWise::GetBits($packet, 0, 7) == 0b1000000)
                    return BitWise::GetBits($packet, 7, 1) == 1;

                // null
                if (BitWise::GetBits($packet, 0, 8) == 0b10000000)                
                    return null;
            }
            
        }
    }


?>