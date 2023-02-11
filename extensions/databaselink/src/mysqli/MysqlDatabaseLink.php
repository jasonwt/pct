<?php	
	declare(strict_types=1);	

	namespace pct\extensions\databaselink\mysqli;

	error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use \mysqli;

	use pct\extensions\databaselink\DatabaseLinkExtension;
	use pct\extensions\databaselink\mysqli\MysqlDatabaseLinkResults;
    use pct\extensions\databaselink\exceptions\DatabaseLinkException;
    use pct\extensions\databaselink\errorhandler\IDatabaseErrorHandler;
	
	class MysqlDatabaseLink extends DatabaseLinkExtension {
		protected mysqli $dbLink;

		public function __construct(mysqli $dbLink, array $attributes = [], $children = null, ?IDatabaseErrorHandler $errorHandler = null) {
			$this->dbLink = $dbLink;

			parent::__construct($attributes, $children, $errorHandler);
		}

        public function GetDatabaseTableStructure(string $tableName): ?array {
            if (!$this->VerifyConnected())
                return null;

            $databaseTableStructure = [
                "name" => $tableName,
                "keys" => [
                    "primary" => "",
                    "unique" => [],
                    "indexes" => []
                ],
                "fields" => [                    
                ]
            ];

            $queryResults = $this->Query("DESCRIBE $tableName");

            while ($row = $queryResults->FetchAssoc()) {
                
                $fieldName = $row["Field"];
                $fieldAttributes = explode(" ", strtoupper(trim($row["Type"])));
                $fieldTypeParts = explode("(", array_shift($fieldAttributes), 2);
                $fieldType = $fieldTypeParts[0];
                $fieldSize = (count($fieldTypeParts) == 1 ? "" : explode(")", $fieldTypeParts[1], 2)[0]);

                $fieldAttributes[] = (strtoupper($row["Null"]) == "YES" ? "NULL" : "NOTNULL");
                $fieldKey = strtoupper($row["Key"]);
                $fieldDefaultValue = $row["Default"];

                if (($fieldExtra = strtoupper(trim($row["Extra"]))) != "")
                    $fieldAttributes = array_merge($fieldAttributes, explode(" ", $fieldExtra));

                if ($fieldKey == "PRI")
                    $databaseTableStructure["keys"]["primary"] = $fieldName;

                if ($fieldKey == "UNI")
                    $databaseTableStructure["keys"]["unique"][] = $fieldName;

                if ($fieldKey == "MUL")
                    $databaseTableStructure["keys"]["indexes"][] = $fieldName;
                
                $databaseTableStructure["fields"][$fieldName] = [
                    "fieldName" => $fieldName,
                    "fieldType" => $fieldType,
                    "fieldSize" => $fieldSize,
                    "fieldKey" => $fieldKey,
                    "fieldDefaultValue" => $fieldDefaultValue,
                    "fieldAttributes" => $fieldAttributes
                ];
            }

            return $databaseTableStructure;
        }

        public function Connect(string $hostName = "", string $userName = "", string $password = "", string $database = "", int $port = 0): bool {
            if (($hostName = trim($hostName)) == "")
                $hostName = ini_get("mysqli.default_host");

            if (($userName = trim($userName)) == "")
                $userName = ini_get("mysqli.default_user");

            if (($password = trim($password)) == "")
                $password = ini_get("mysqli.default_pw");

            if ($port == 0)            
                $port = ini_get("mysqli.default_port");

            if ($this->IsConnected())
                $this->Disconnect();

            $this->dbLink = new mysqli($hostName, $userName, $password, $database, $port);

            if ($this->dbLink->connect_errno)
                return (bool) $this->errorHandler->RegisterError("mysqli::connect() failed. " . $this->dbLink->connect_errno . ": " . $this->dbLink->connect_error, IDatabaseErrorHandler::TYPE_DATABASELINK_CONNECT);

            return true;
        }

        public function IsConnected() : bool {
            return $this->dbLink->ping();
        }

        public function Disconnect(): bool {
            if (!($closeResults = $this->dbLink->close()))
                return (bool) $this->errorHandler->RegisterError("mysqli::close() failed. " . $this->dbLink->connect_errno . ": " . $this->dbLink->connect_error, IDatabaseErrorHandler::TYPE_DATABASELINK);

            return $closeResults;
        }

		public function AffectedRows(): ?string {
            return ($this->VerifyConnected() ? (string) $this->dbLink->affected_rows : null);
		}

		public function FieldCount(): ?string { 
            return ($this->VerifyConnected() ? (string) $this->dbLink->field_count : null);
		}

		public function InsertId(): ?string { 
            return ($this->VerifyConnected() ? (string) $this->dbLink->insert_id : null);
		}

		public function EscapeString(string $str): ?string { 
            return ($this->VerifyConnected() ? (string) $this->dbLink->real_escape_string($str) : null);
		}

		public function Query(string $query, int $resultsMode = self::RESULT_MODE_STORE) {
            if (!$this->VerifyConnected())
                return null;            

			if ($resultsMode == static::RESULT_MODE_STORE)
				$resultsMode = MYSQLI_STORE_RESULT;
			else if ($resultsMode == static::RESULT_MODE_USE)
				$resultsMode = MYSQLI_USE_RESULT;
			else if ($resultsMode == static::RESULT_MODE_ASYNC)
				$resultsMode = MYSQLI_ASYNC;
			else
				throw new DatabaseLinkException("Invalid resultsMode '$resultsMode'");

            if (is_bool($results = $this->dbLink->query($query, $resultsMode))) {
                if ($this->dbLink->errno)
                    return (bool) $this->errorHandler->RegisterError("MySQL Query failed. " . $this->dbLink->connect_errno . "\n" . $this->dbLink->connect_error . "\n$query", IDatabaseErrorHandler::TYPE_DATABASELINK_QUERY);

                return $results;
            }

			return new MysqlDatabaseLinkResults($results);
		}
	}
?>