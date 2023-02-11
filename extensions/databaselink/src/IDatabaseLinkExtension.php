<?php	
    declare(strict_types=1);	

	namespace pct\extensions\databaselink;

	use pct\core\extensions\IExtension;

	interface IDatabaseLinkExtension extends IExtension {
		const RESULT_MODE_STORE = 1;
		const RESULT_MODE_USE = 2;
		const RESULT_MODE_ASYNC = 3;

        public function GetDatabaseTableStructure(string $tableName): ?array;
        public function Connect(string $hostName = "", string $userName = "", string $password = "", string $database = "", int $port = 0): bool;
        public function Disconnect(): bool;
        public function IsConnected(): bool;
		public function AffectedRows() : ?string;
		public function FieldCount() : ?string;
		public function InsertId() : ?string;

		public function EscapeString(string $string) : ?string;
		public function Query(string $query, int $resultsMode = self::RESULT_MODE_STORE);
	}
?>