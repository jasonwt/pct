<?php	
    declare(strict_types=1);	

	namespace pct\extensions\databaselink;

	interface IDatabaseLinkResults {
		const RESULTS_MODE_NUM = 1;
		const RESULTS_MODE_ASSOC = 2;
		const RESULTS_MODE_ALL = 3;

		public function NumRows() : string;

		public function FetchArray(int $resultsMode = self::RESULTS_MODE_ALL);
		public function FetchRow();
		public function FetchAssoc();
		public function FetchAll(int $resultsMode = self::RESULTS_MODE_ALL);		
	}

?>