<?php	
	declare(strict_types=1);	

	namespace pct\extensions\databaselink\errorhandler;
	
	error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

	use pct\core\errorhandlers\ErrorHandler;
	use pct\extensions\databaselink\errorhandler\IDatabaseErrorHandler;

    use pct\extensions\databaselink\exceptions\DatabaseLinkException;
    use pct\extensions\databaselink\exceptions\DatabaseLinkConnectException;
    use pct\extensions\databaselink\exceptions\DatabaseLinkQueryException;
    use pct\extensions\databaselink\exceptions\DatabaseLinkNotConnectedException;
	
	class DatabaseErrorHandler extends ErrorHandler implements IDatabaseErrorHandler {
        protected function ThrowException(int $errorType, string $message): ?bool {
            switch ($errorType) {
                case self::TYPE_DATABASELINK:
                    throw new DatabaseLinkException($message);
                case self::TYPE_DATABASELINK_CONNECT:
                    throw new DatabaseLinkConnectException($message);
                case self::TYPE_DATABASELINK_QUERY:
                    throw new DatabaseLinkQueryException($message); 
                case self::TYPE_DATABASELINK_NOTCONNECTED:
                    throw new DatabaseLinkNotConnectedException($message); 
            }

            return parent::ThrowException($errorType, $message);
        }
	}