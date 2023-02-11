<?php	
    declare(strict_types=1);	

	namespace pct\extensions\databaselink\errorhandler;

    use pct\core\errorhandlers\IErrorHandler;

	interface IDatabaseErrorHandler extends IErrorHandler {
        const TYPE_DATABASELINK              = 100;  // Database Link General
        const TYPE_DATABASELINK_CONNECT      = 101;  // Database Link Connect
        const TYPE_DATABASELINK_QUERY        = 102; // Database Link Query
        const TYPE_DATABASELINK_NOTCONNECTED = 103; //
	}
?>