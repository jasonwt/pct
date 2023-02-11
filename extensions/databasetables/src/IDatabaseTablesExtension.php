<?php	
    declare(strict_types=1);	

	namespace pct\extensions\databasetables;

    use pct\core\components\IComponent;
    use pct\core\extensions\IExtension;
    use pct\core\errorhandlers\IErrorHandler;

	interface IDatabaseTablesExtension extends IExtension {
        				
		public function LoadFromDatabase(string $tableNames, string $whereQuery) : bool;
		public function WriteToDatabase(string $tableName = "") : bool;
		public function GetSelectFieldNames(string $tableName) : array;
		public function GetInsertFieldValues(string $tableName) : array;
        public function NewDatabaseTablesField(string $name, array $attributes = [], $children = null, ?IErrorHandler $errorHander = null): ?IComponent;
	}
?>