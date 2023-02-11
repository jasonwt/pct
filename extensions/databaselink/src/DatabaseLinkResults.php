<?php	
    declare(strict_types=1);	

	namespace pct\extensions\databaselink;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\extensions\databaselink\IDatabaseLinkResults;
    use pct\extensions\databaselink\errorhandler\IDatabaseErrorHandler;
    use pct\extensions\databaselink\errorhandler\DatabaseErrorHandler;

	abstract class DatabaseLinkResults implements IDatabaseLinkResults {
		protected IDatabaseErrorHandler $errorHandler;

		public function __construct(?IDatabaseErrorHandler $errorHandler = null) {
			$this->errorHandler = (is_null($errorHandler) ? new DatabaseErrorHandler() : $errorHandler);
		}

		public function FetchRow() {
			return $this->FetchArray(static::RESULTS_MODE_NUM);
		}

		public function FetchAssoc() {
			return $this->FetchArray(static::RESULTS_MODE_ASSOC);
		}

		public function FetchAll(int $resultsMode = self::RESULTS_MODE_ALL) {
			$returnValue = [];

			while ($results = $this->FetchArray($resultsMode))
				$returnValue[] = $results;

			return $returnValue;
		}
	}
?>