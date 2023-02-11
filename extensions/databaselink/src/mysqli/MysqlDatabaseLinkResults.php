<?php	
    declare(strict_types=1);	

	namespace pct\extensions\databaselink\mysqli;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use \mysqli_result;

	use pct\extensions\databaselink\DatabaseLinkResults;
    use pct\extensions\databaselink\errorhandler\IDatabaseErrorHandler;

	class MysqlDatabaseLinkResults extends DatabaseLinkResults {
		protected mysqli_result $queryResults;

		public function __construct(mysqli_result $queryResults, ?IDatabaseErrorHandler $errorHandler = null) {
			parent::__construct($errorHandler);

			$this->queryResults = $queryResults;
		}

		public function NumRows(): string { 
			return (string) $this->queryResults->num_rows;
		}

		public function FetchRow() {
			return $this->queryResults->fetch_row();
		}

		public function FetchAssoc() {		
			return $this->queryResults->fetch_assoc();
		}

		public function FetchArray(int $resultsMode = self::RESULTS_MODE_ALL) {
			if ($resultsMode == static::RESULTS_MODE_ALL)
				$resultsMode = MYSQLI_BOTH;
			else if ($resultsMode == static::RESULTS_MODE_NUM)
				$resultsMode = MYSQLI_NUM;
			else if ($resultsMode == static::RESULTS_MODE_ASSOC)
				$resultsMode = MYSQLI_ASSOC;
            else
                return (bool) $this->errorHandler->RegisterError("Invalid resultsMode '$resultsMode'", IDatabaseErrorHandler::TYPE_INVALIDARGUMENT);
			
			return $this->queryResults->fetch_array($resultsMode);
		}
		
	}
?>