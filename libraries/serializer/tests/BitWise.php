<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    class BitWise {
        static public function UsedBits(int $integer, bool $ignoreSign = true) : int {
            if ($integer == 0)
                return 0;
            else if ($integer > 0 || $ignoreSign)
                return (int) floor(log(abs($integer), 2)) + 1;

            return PHP_INT_SIZE * 8;
        }

        static public function EnableBits(int &$integer, int $position, int $numBits = 0) : int {
            if ($position >= (PHP_INT_SIZE*8))
                return $integer;

            while ($position < 0)
                $position += static::UsedBits($integer);

            if ($numBits <= 0)
                $numBits = (PHP_INT_SIZE*8);

            if ($position + $numBits >= (PHP_INT_SIZE*8))
                $numBits = (PHP_INT_SIZE*8) - $position;

            return ($integer = $integer | (((1 << $numBits) - 1) << $position));
        }

        static public function DisableBits(int &$integer, int $position, int $numBits = 0) : int {

            if ($position >= (PHP_INT_SIZE*8))
                return $integer;

            while ($position < 0)
                $position += static::UsedBits($integer);

            if ($numBits <= 0)
                $numBits = (PHP_INT_SIZE*8);

                

            if ($position + $numBits >= (PHP_INT_SIZE*8))
                $numBits = (PHP_INT_SIZE*8) - $position;

//                echo "position: $position\n";
//                echo "numBits: $numBits\n";

            return ($integer = $integer & (~(((1 << $numBits) - 1) << $position)));
        }

        static public function ToggleBits(int &$integer, int $position, int $numBits = 0) {
            if ($position >= (PHP_INT_SIZE*8))
                return $integer;

            while ($position < 0)
                $position += static::UsedBits($integer);

            if ($numBits <= 0)
                $numBits = (PHP_INT_SIZE*8);

            if ($position + $numBits >= (PHP_INT_SIZE*8))
                $numBits = (PHP_INT_SIZE*8) - $position;

            for ($bcnt = $position; $bcnt < $position + $numBits; $bcnt ++) {
/*                
                if (static::GetBits($integer, $bcnt))
                    static::DisableBits($integer, $position);
                else
                    static::EnableBits($integer, $position);
*/
                if (($integer & (1 << $bcnt)) >> $position)
                    $integer = $integer & (~(1 << $position));
                else
                    $integer = $integer | (1 << $position);
            }
        } 

        static public function GetBits(int $integer, int $position, int $numBits = 0) : int {
            if ($position >= (PHP_INT_SIZE*8))
                return $integer;

            while ($position < 0)
                $position += static::UsedBits($integer);

            if ($numBits <= 0)
                $numBits = (PHP_INT_SIZE*8);

            if ($position + $numBits >= (PHP_INT_SIZE*8))
                $numBits = (PHP_INT_SIZE*8) - $position;


            // Generate a mask with length 1s starting at position and perform
            $mask = ((1 << $numBits) - 1) << $position;

            // Perform a bitwise AND with the mask
            $result = $integer & $mask;

            // Shift the result back to the least significant bits
            return $result >> $position;
        }

                

        static public function SetBits(int &$integer, int $position, int $bits, int $numBits = 0) : int {
            if ($position >= (PHP_INT_SIZE*8))
                return $integer;

            while ($position < 0)
                $position += static::UsedBits($integer);

            if ($numBits <= 0)
                $numBits = (PHP_INT_SIZE*8);

            if ($position + $numBits >= (PHP_INT_SIZE*8))
                $numBits = (PHP_INT_SIZE*8) - $position;
            
            // Create a mask to zero out the target bits and Zero out the target bits
            $integer &= ~(((1 << $numBits) - 1) << $position);

            // Extract the bits to be set from the source integer and set them in the integer
            return ($integer |= (($bits & ((1 << $numBits) - 1)) << $position));
        }

        static public function ReverseBits(int &$integer, int $position = 0, int $numBits = 0) : int {
            if ($position >= (PHP_INT_SIZE*8))
                return $integer;

            while ($position < 0)
                $position += static::UsedBits($integer);

            if ($numBits <= 0)
                $numBits = (PHP_INT_SIZE*8);

            if ($position + $numBits >= (PHP_INT_SIZE*8))
                $numBits = (PHP_INT_SIZE*8) - $position;

            $newBits = (($integer & (1 << $position)) >> $position);

            for ($bcnt = $position+1; $bcnt < $position + $numBits; $bcnt ++) {                
                $newBits = $newBits << 1;
                
                $newBits |= (($integer & (1 << $bcnt)) >> $bcnt);
            }

            return ($integer = $newBits);
        }
    }
?>