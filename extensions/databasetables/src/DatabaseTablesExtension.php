<?php	
	declare(strict_types=1);	

	namespace pct\extensions\databasetables;
	
	error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\core\ICore;
    use pct\core\components\IComponent;
	use pct\core\extensions\Extension;
	use pct\core\errorhandlers\IErrorHandler;

    use pct\extensions\databasetables\DatabaseTablesField;
	use pct\extensions\databasetables\IDatabaseTablesExtension;
	
	class DatabaseTablesExtension extends Extension implements IDatabaseTablesExtension {
		public function __construct(string $tableNames, array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
            $attributes["tables"] = [];
            
			parent::__construct("DatabaseTablesExtension", $attributes, $components, $errorHandler);

			if (($tableNames = trim($tableNames)) == "")
				$this->TriggerError("Invalid tableNames '$tableNames'.");

			foreach (explode(",", $tableNames) as $tableName) {
				if (($tableName = trim($tableName)) == "")
					continue;

				$tableNamesAttribute[] = $tableName;

				$this[$tableName] = [
					"primaryKey" => "",
					"indexKeys" => [],
					"uniqueKeys" => [],
					"autoIncrementingKeys" => "",
					"fields" => []
				];				
			}

			$this["tables"] = $tableNamesAttribute;
		}

        public function GetRequiredParentMethods(): array {
            return parent::GetRequiredParentMethods();
        }
        public function GetRequiredParentChildrenNames(): array {
            return parent::GetRequiredParentChildrenNames();
        }

        public function GetRequiredParentChildrenTypes(): array {
            return ["pct\\extensions\\databaselink\\DatabaseLinkExtension"] + parent::GetRequiredParentChildrenTypes();
        }

        public function NewDatabaseTablesField(string $name, array $attributes = [], $children = null, ?IErrorHandler $errorHander = null) : ?IComponent {            
            return new DatabaseTablesField($name, null, $attributes, $children, $errorHander);
        }

		public function OnRegisteredCallback() : ?ICore {
            if (is_null(parent::OnRegisteredCallback()))
                return null;

            foreach ($this["tables"] as $tableName) {
                $this[$tableName] = $this->GetParent()->GetDatabaseTableStructure($tableName);

                foreach ($this[$tableName]["fields"] as $fieldName => $field) {
                    $this->GetParent()->RegisterChildren($this->GetParent()->NewDatabaseTablesField(
                        $tableName . "_" . $fieldName
                    ));
                }
            }               

            return $this->GetParent();
		}

		public function GetSelectFieldNames(string $tableName) : array {
			$selectFieldNames = [];

			$tableAttributes = $this[$tableName];

			foreach ($tableAttributes["fields"] as $fieldName => $fieldInfo)
				$selectFieldNames[$tableName . "." . $fieldName] = "$tableName" . "." . $fieldName . " AS $tableName" . "_" . $fieldName;

			return $selectFieldNames;			
		}

		public function GetInsertFieldValues(string $tableName) : array {
			$insertFieldValues = [];

            $autoIncrementingFields = array_filter($this[$tableName]["fields"], function ($v, $k) {
                return in_array("AUTO_INCREMENT", $v["fieldAttributes"]);
            }, ARRAY_FILTER_USE_BOTH);

			foreach ($this[$tableName]["fields"] as $fieldName => $fieldInfo) {
				$databaseFieldName = $tableName . "." . $fieldName;
				$componentName = $tableName . "_" . $fieldName;
            
				$componentValue = $this->GetParent()->$componentName->GetValue();

                if (isset($autoIncrementingFields[str_replace($tableName . "_", "", $componentName)]))
                    if (intval($componentValue) == 0)
                        continue;

				$insertFieldValues[$componentName] = $databaseFieldName . "='" . $this->GetParent()->EscapeString((string) $componentValue) . "'";
			}

			return $insertFieldValues;			
		}

		public function LoadFromDatabase(string $tableNames = "", string $whereQuery = ""): bool {
            if (is_null($this->GetParent()))
                return $this->errorHandler->RegisterError("Method can not be called with no parent assigned.'", IErrorHandler::TYPE_ICORE_PARENTREQUIRED);

			if (($tableNames = trim($tableNames)) == "")
				$tableNames = implode(",", $this["tables"]);

			$selectFieldNames = [];

			$tableNames = explode(",", $tableNames);

			for ($cnt = 0; $cnt < count($tableNames); $cnt ++) {
				if (($tableNames[$cnt] = trim($tableNames[$cnt])) == "")
					unset($tableNames[$cnt]);
				else
					$selectFieldNames += $this->GetParent()->GetSelectFieldNames($tableNames[$cnt]);
			}
			
			$query = "SELECT " . implode(", ", $selectFieldNames) . " FROM " . implode(", ", $tableNames);

			if (($whereQuery = trim($whereQuery)) != "")
				$query .= " WHERE $whereQuery";

			$queryResults = $this->GetParent()->Query($query);

			if ($lastDatabaseError = $this->GetParent()->GetLastError())
                return false;

			if ($row = $queryResults->FetchAssoc())
                foreach ($row as $fieldName => $fieldValue)
                    $this->GetParent()->$fieldName->SetValue($fieldValue);

			return true;
		}

//        
// numeric credit union daisy 5094604750 
// 

		public function WriteToDatabase(string $tableNames = ""): bool {
            if (is_null($this->GetParent()))
                return $this->errorHandler->RegisterError("Method can not be called with no parent assigned.'", IErrorHandler::TYPE_ICORE_PARENTREQUIRED);
                
			if (($tableNames = trim($tableNames)) == "")
				$tableNames = implode(",", $this["tables"]);

			$tableNames = explode(",", $tableNames);

			for ($cnt = 0; $cnt < count($tableNames); $cnt ++) {
				$tableName = $tableNames[$cnt];

				if (($tableName = trim($tableName)) == "") {
					unset($tableNames[$cnt]);
				} else {
                    $autoIncrementingFields = array_filter($this[$tableName]["fields"], function ($v, $k) {
                        return in_array("AUTO_INCREMENT", $v["fieldAttributes"]);
                    }, ARRAY_FILTER_USE_BOTH);

					$tableAttributes = $this[$tableName];

					$insertFieldValues = $this->GetParent()->GetInsertFieldValues($tableName);

					$sqlFunction = "INSERT INTO";

					$primaryKey = $tableAttributes["keys"]["primary"];
					$componentName = $tableName . "_" . $primaryKey;
					
					$primaryKeyComponentValue = ($primaryKey != "" ? $this->GetParent()->$componentName->GetValue() : "");
					
					if ($primaryKey != "" && $primaryKeyComponentValue != "")
						$sqlFunction = "UPDATE";						

					$query = $sqlFunction . " $tableName SET " . implode(", ", $insertFieldValues);

					if ($sqlFunction == "UPDATE")
						$query .= " WHERE $tableName.$primaryKey='" . $this->GetParent()->EscapeString($primaryKeyComponentValue) . "' LIMIT 1";					

					$this->GetParent()->Query($query);

					if ($lastDatabaseError = $this->GetParent()->GetLastError())
						return false;

                    if (count($autoIncrementingFields) > 0) {
                        if ($sqlFunction == "INSERT INTO") {
                            $fieldName = $tableName . "_" . key($autoIncrementingFields);

                            $this->GetParent()->$fieldName->SetValue($this->GetParent()->InsertId());
                        }
                    }
				}
			}

			return true;			
		}
	}

?>