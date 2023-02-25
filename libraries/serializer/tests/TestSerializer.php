<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    require_once(__DIR__ . "/../vendor/autoload.php");

    function Compare($value1, $value2) : int {
        if (gettype($value1) == "double")
            return (abs($value1 - $value2) < 0.0001 ? 0 : ($value1 < $value2 ? -1 : 1));

        if ($value1 < $value2)
            return - 1;
        else if ($value1 > $value2)
            return 1;
        
        return 0;        
    }

    function ExecuteTest(int $testType, int $iterations = 100, int $seed = 0, bool $verbose = true) : ?int {
        $iterations = abs($iterations);

        if ($seed)
            srand($seed);

        $totalBytes = 0;

        $randMax = getrandmax();

        for ($tcnt = 0; $tcnt < $iterations; $tcnt ++) {
            $value = "";

            if ($testType == TEST_DOUBLES) {
                // Double Test
                $value = (float) rand(-$randMax, $randMax) / pow(10, rand(1, 4));

            } else if ($testType == TEST_INTEGERS) {
                // Integer Test
                $value = (rand(0, $randMax) + rand(0, $randMax)) * (rand(0, 1) ? 1 : -1);

            } else if ($testType == TEST_BITFIELDS) {
                // Bitfield Test
                $nbits = rand(0, 16);
                $value = sprintf("%0" . $nbits . "b", (rand(0, $randMax) & ((1 << $nbits) - 1)));

            } else if ($testType == TEST_OBJECTS) {
                if (!class_exists("pct\libraries\serializer\Bar")) {
                    class Bar {
                        private bool $barTrue = true;
                        protected null $barNull = null;
                        public int $barInt = 1;
                        private float $barDouble = M_PI;
                        
                        public function __toString() {
                            return "Bar";
                        }
                    }
                }

                if (!class_exists("pct\libraries\serializer\Foo")) {
                    class Foo {
                        private $fooArray = [
                            "subArray" => [
                                [1, 2, 3],
                                [4.4, 5.5, 6.6],
                                ["seven", "eight", "nine"]
                            ],
                            "std" => null
                        ];

                        protected ?Bar $fooBar = null;
                        public string $fooBitField = "1010101010101";
                        private string $fooHexField = "70e1dd";

                        public function __construct() {
                            $this->fooArray["std"] = new \stdClass();
                            $this->fooBar = new Bar();
                        }

                        public function __toString() {
                            return "Foo";
                        }
                    }
                }

                $value = new Foo();
            } else {
                throw new \Exception("Invalid test type: $testType");
            }

            //$serializedValue = PHPSerializer::Serialize($value);
            $serializedValue = BinarySerializer::Serialize($value);

            if ($verbose)
                echo str_pad("Testing : $value", 30) . str_pad("bytes : " . strlen($serializedValue), 15);

            $totalBytes += strlen($serializedValue);

            //$unserializedValue = PHPSerializer::Unserialize($serializedValue);
            $unserializedValue = BinarySerializer::Unserialize($serializedValue);

            if (($compareResults = Compare($value, $unserializedValue)) == 0) {
                if ($verbose)
                    echo "OK\n";
            } else {
                if ($verbose) {
                    echo("FAILED WITH compareResults: $compareResults, value: $unserializedValue\n");

                    exit(1);
                }
            }            
        }

        if ($verbose)
            echo "\n" . str_repeat(" ", 30) . "total : $totalBytes\n";

        return $totalBytes;
    }

    const TEST_DOUBLES = 1;
    const TEST_INTEGERS = 2;
    const TEST_BITFIELDS = 3;
    const TEST_OBJECTS = 4;

    ExecuteTest(TEST_OBJECTS, 1000, 555);
?>