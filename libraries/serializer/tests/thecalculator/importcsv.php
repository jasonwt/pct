<?php
    declare(strict_types=1);
	
    namespace thecalculator\imports;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    class SSCImport {
        protected ?\mysqli $dbLInk;

        protected string $sccStandardYear = "";

        protected array $currentCSV = [];
        protected ?array $previousCSV = null;

        protected ?array $previousRowTitles = null;
        protected array $currentRowTitles = [];

        protected array $validTableIds = [
            25,34,44,43,26,1,22,35,23,27,36,45,37,28,5,10,2,17,18,16,19,4,3,30,31,6,38,7,29,8,39,21,20,11,12,32,15,14,46,40,9,24,41,13,42,33
        ];

        protected array $databaseRowNames = [
            8  => "housing",
            9  => "childcare",
            10 => "food",
            11 => "transportation",
            12 => "healthcare",
            13 => "miscellaneous",
            14 => "taxes",
            15 => "eitc",
            16 => "cctc",
            17 => "ctc",
            19 => "sschourly",
            21 => "sscmonthly",
            22 => "sscannually",
            23 => "mwptc"
        ];

        public function __construct(?\mysqli $dbLink, string $currentYearsCSVFile, string $previousYearsCSVFile) {
            $this->dbLInk = $dbLink;

            $this->currentCSV = $this->LoadCSV($currentYearsCSVFile);

            if (trim($previousYearsCSVFile) != "" && file_exists($previousYearsCSVFile))
                $this->previousCSV = $this->LoadCSV($previousYearsCSVFile);

            $this->ValidateCSV();

            for ($rcnt = 8; $rcnt < 24; $rcnt ++) {
                if ($rcnt == 18 || $rcnt == 20)
                    continue;

                $this->currentRowTitles[$rcnt+1] = trim($this->currentCSV[$rcnt][0]);

                if (!is_null($this->previousCSV))
                    $this->previousRowTitles[$rcnt+1] = trim($this->previousCSV[$rcnt][0]);
            }
        }

        protected function ValidateCSV() : bool {
            $this->sccStandardYear = substr($this->currentCSV[0][0], -4);

            if (!is_null($this->previousCSV))
                if ((int) $this->sccStandardYear <= (int) ($previousYear = substr($this->previousCSV[0][0], -4)))
                    throw new \Exception("previousCSV year '$previousYear' is >= currentCSV year '" . $this->sccStandardYear . "'");

            if (!is_null($this->previousCSV))                    
                if (count($this->previousCSV) != count($this->currentCSV))
                    throw new \Exception("previousCSV count: " . count($this->previousCSV) . ", currentCSV count: " . count($this->currentCSV));

            for ($rcnt = 0; $rcnt < count($this->currentCSV); $rcnt ++) {
                $currentRecord = $this->currentCSV[$rcnt];

                $rcntMod24 = $rcnt % 24;

                if (!is_null($this->previousCSV)) {
                    $previousRecord = $this->previousCSV[$rcnt];

                    if ($rcntMod24 == 0) {
                        $currentParts  = explode("\n", $currentRecord[0], 2);       
                        $previousParts = explode("\n", $previousRecord[0], 2);

                        if (trim($currentParts[0]) != trim($previousParts[0]))
                            throw new \Exception("row previous/current heading mismatch on row: $rcnt\n\nprevious: " . $previousRecord[0] . "\n\ncurrent: " . $currentRecord[0] . "\n\n");
                    } else {
                        if (trim($previousRecord[0]) != trim($currentRecord[0]))
                            throw new \Exception("row previous/current heading mismatch on row: $rcnt\n\nprevious: " . $previousRecord[0] . "\n\ncurrent: " . $currentRecord[0] . "\n\n");
                    }
                }

                if ($rcntMod24 != 18 && $rcntMod24 != 20 && $rcntMod24 > 0) {
                    for ($ccnt = 1; $ccnt < count($currentRecord); $ccnt ++) {
                        if ($rcntMod24 > 0 && $rcntMod24 < 7) {
                            if (!is_null($this->previousCSV))
                                if (trim($previousRecord[$ccnt]) != trim($currentRecord[$ccnt]))
                                    throw new \Exception("column previous/current heading mismatch on row: $rcnt, col: $ccnt\n\nprevious: " . $previousRecord[0][$ccnt] . "\n\ncurrent: " . $currentRecord[0][$ccnt] . "\n\n");
                        } else if ($rcntMod24 > 7 && $rcntMod24 < 24) {
                            if (($cell = trim($currentRecord[$ccnt])) == "") {
                                throw new \Exception("invalid value '$cell' for row: $rcnt, col: $ccnt\n\n");
                            } else {
                                if ($cell[0] != "(" && $cell[0] != "\$")
                                    throw new \Exception("invalid value '$cell' for row: $rcnt, col: $ccnt\n\n");
                            }   
                        }
                    }
                }
            }

            return true;
        }

        protected function LoadCSV(string $fileName) : array {
            $results = [];

            if (($handle = fopen($fileName, "r")) !== FALSE) {
                $row = 0;

                while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) 
                    $results[($row++)] = $data;

                fclose($handle);
            } else {
                throw new \Exception("Could not open csv file '$fileName");
            }

            return $results;
        }

        protected function ParseCSV(array $csvArray) : array {
            $results = [];

            $tbl = "";

            for ($rcnt = 0; $rcnt < count($csvArray); $rcnt ++) {
                $currentRow = $csvArray[$rcnt];

                $rcntMod24 = $rcnt % 24;

                if ($rcntMod24 == 0) {
                    if (preg_match('/Table\s(.*?)\n/', $currentRow[0], $matches)) {
                        if (!in_array((int) ($tbl = $matches[1]), $this->validTableIds))
                            throw new \Exception("Invalid tableId: $tbl");
                        
                        $results[$tbl] = [
                            "tbl" => str_replace("\r", "", $tbl),
                            0 => implode(" ", array_slice(explode("\n", $currentRow[0]), 1)),
                            8 => [],
                            9 => [],
                            10 => [],
                            11 => [],
                            12 => [],
                            13 => [],
                            14 => [],
                            15 => [],
                            16 => [],
                            17 => [],
                            19 => [],
                            21 => [],
                            22 => [],
                            23 => []
                        ];

                        $rcnt += 7;
                    } else {
                        throw new \Exception("Get Table Id failed.");                    
                    }
                    
                } else if (isset($results[$tbl][$rcntMod24])) {
                    for ($ccnt = 1; $ccnt < count($currentRow); $ccnt ++)
                        $results[$tbl][$rcntMod24][] = sprintf("%0.2f", str_replace(["(", ")", ",", "\$"], ["-", "", "", ""], trim($currentRow[$ccnt])));
                }
            }

            return $results;
        }

        protected function EscapeString(string $str) : string {
            if (!is_null($this->dbLInk))
                return $this->dbLInk->escape_string($str);

            return $str;
        }

        public function BuildSQL() : string {
            $sql = "";

            $parsedCSV = $this->ParseCSV($this->currentCSV);

            foreach ($parsedCSV as $id => $tbl) {
                for ($ccnt = 0; $ccnt < count($tbl[8]); $ccnt ++) {
                    $setArray = [
                        "id='$id'", 
                        "sscyear='" . $this->sccStandardYear . "'",
                        "famtype='" . ($ccnt+1) . "'"
                    ];

                    foreach ($this->databaseRowNames as $rowId => $dbRowName)
                        $setArray[] = $dbRowName . "='" . $this->EscapeString($tbl[$rowId][$ccnt]) . "'";

                    $sql .= "INSERT INTO ssc_standard SET " . implode(", ", $setArray) . " ON DUPLICATE KEY UPDATE " . implode(", ", $setArray) . ";\n";
                }  
            }

            return $sql;
        }
    }

    function Usage(string $errorMessage = "") {
        if (($errorMessage = trim($errorMessage)) != "")
            echo "\n" . $errorMessage . "\n";

        echo "\nUsage: sscimport.php [current csv path] [previous csv path] [database username] [database password] [database name] [OPTIONS]\n\n";

        echo "OPTIONS:\n";
        echo "\t-P         - Omit previous csv file.  This will disable most of the csv verification.\n";
        echo "\t-D         - Omit database information.  This will disable the ability to import\n\t             directly and escape strings with the database connection.\n\n";
        echo "\t-I         - Import csv directly into database.\n\n";
        
        exit(0);
    }

    $cliArguments = [
        "currentCSVPath" => "",
        "previousCSVPath" => "",
        "databaseUsername" => "",
        "databasePassword" => "",
        "databaseName" => "",
        "P" => false,
        "D" => false,
        "I" => false,
    ];

    for ($cnt = 1; $cnt < count($argv); $cnt ++) {
        $arg = trim($argv[$cnt]);

        if ($arg[0] == "-") {
            $option = strtoupper($arg[1]);

            if ($option == "P") {
                if ($cliArguments[$option] != "") {
                    if ($cliArguments["databaseName"] == "" && $cliArguments["databasePassword"] != "") {
                        $cliArguments["databaseName"] = $cliArguments["databasePassword"];
                        $cliArguments["databasePassword"] = "";
                    }

                    if ($cliArguments["databasePassword"] == "" && $cliArguments["databaseUsername"] != "") {
                        $cliArguments["databasePassword"] = $cliArguments["databaseUsername"];
                        $cliArguments["databaseUsername"] = "";
                    }

                    if ($cliArguments["databaseUsername"] == "" && $cliArguments["previousCSVPath"] != "") {
                        $cliArguments["databaseUsername"] = $cliArguments["previousCSVPath"];
                        $cliArguments["previousCSVPath"] = "";
                    }
                }
            }
            
            if (isset($cliArguments[$option])) {
                $cliArguments[$option] = true;
            } else {
                Usage("Invalid option argument '$-arg'.\n");
            }
            
        } else {
            if ($cliArguments["currentCSVPath"] == "")
                $cliArguments["currentCSVPath"] = $arg;
            else if ($cliArguments["previousCSVPath"] == "")
                $cliArguments["previousCSVPath"] = $arg;
            else if ($cliArguments["databaseUsername"] == "")
                $cliArguments["databaseUsername"] = $arg;
            else if ($cliArguments["databasePassword"] == "")
                $cliArguments["databasePassword"] = $arg;
            else if ($cliArguments["databaseName"] == "")
                $cliArguments["databaseName"] = $arg;
            else
                Usage("Invalid argument '$arg'.\n");
        }
    }

    if ($cliArguments["currentCSVPath"] == "")
        Usage("Missing current csv path.\n");
    else if (!file_exists($cliArguments["currentCSVPath"]))
        Usage("Current CSV Path '" . $cliArguments["currentCSVPath"] . "' does not exist.\n");

    if ($cliArguments["P"]) {
        $cliArguments["previousCSVPath"] = "";
    } else {
        if ($cliArguments["previousCSVPath"] == "")
            Usage("Missing previous csv path.\n");
        else if (!file_exists($cliArguments["previousCSVPath"]))
            Usage("Previous CSV Path '" . $cliArguments["previousCSVPath"] . "' does not exist.\n");
    }

    $dbLink = null;

    if ($cliArguments["D"]) {
        $cliArguments["databaseUsername"] = "";
        $cliArguments["databasePassword"] = "";
        $cliArguments["databaseName"] = "";
    } else {
        if ($cliArguments["databaseUsername"] == "")
            Usage("Missing database username.\n");

        if ($cliArguments["databasePassword"] == "")
            Usage("Missing database password.\n");

        if ($cliArguments["databaseName"] == "")
            Usage("Missing database name.\n");

        $dbLink = new \mysqli("localhost", $cliArguments["databaseUsername"], $cliArguments["databasePassword"], $cliArguments["databaseName"]);

        if ($dbLink->connect_errno)
            Usage("MySQL connect error: " . $dbLink->connect_errno . "\n\n" . $dbLink->connect_error . "\n");
    }

    if ($cliArguments["I"] && !$cliArguments["D"])
        Usage("Can not import directly to database while ommiting the database information.\n");

    $sscImport = new SSCImport($dbLink, $cliArguments["currentCSVPath"], $cliArguments["previousCSVPath"]);

    echo $sscImport->BuildSQL();
?>