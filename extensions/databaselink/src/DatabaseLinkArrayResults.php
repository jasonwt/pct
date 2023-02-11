<?php	
    declare(strict_types=1);	

	namespace pct\extensions\databaselink;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\extensions\databaselink\DatabaseLinkResults;
    use pct\extensions\databaselink\errorhandler\IDatabaseErrorHandler;
    use pct\extensions\databaselink\errorhandler\DatabaseErrorHandler;

	class DatabaseLinkArrayResults extends DatabaseLinkResults {
        private int $resultsIterator = 0;
        private array $results = [];

		private IDatabaseErrorHandler $errorHandler;

		public function __construct(array $results, ?IDatabaseErrorHandler $errorHandler = null) {
			$this->errorHandler = (is_null($errorHandler) ? new DatabaseErrorHandler() : $errorHandler);

            $this->results = $results;
		}

        public function NumRows(): string {
            return (string) count($this->results);
        }

        public function FetchArray(int $resultsMode = self::RESULTS_MODE_ALL) {
            if ($this->resultsIterator >= count($this->results))
                return null;
                        
            $returnValue = [];
            
            foreach ($this->results[$this->resultsIterator] as $fieldName => $fieldValue) {
                if ($resultsMode == self::RESULTS_MODE_ALL || $resultsMode == self::RESULTS_MODE_NUM)
                    $returnValue[] = $fieldValue;

                if ($resultsMode == self::RESULTS_MODE_ALL || $resultsMode == self::RESULTS_MODE_ASSOC)
                    $returnValue[$fieldName] = $fieldValue;
            }

            $this->resultsIterator++;            

            return $returnValue;
        }
    }
?>