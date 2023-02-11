<?php	
	declare(strict_types=1);	

	namespace pct\extensions\databaselink;
	
	error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\core\extensions\Extension;
	use pct\extensions\databaselink\IDatabaseLinkExtension;
    use pct\extensions\databaselink\errorhandler\DatabaseErrorHandler;
    use pct\extensions\databaselink\errorhandler\IDatabaseErrorHandler;
	
	abstract class DatabaseLinkExtension extends Extension implements IDatabaseLinkExtension {
		public function __construct(array $attributes = [], $children = null, ?IDatabaseErrorHandler $errorHandler = null) {
            $errorHandler = (is_null($errorHandler) ? new DatabaseErrorHandler() : $errorHandler);

			parent::__construct("DatabaseLinkExtension", $attributes, $children, $errorHandler);
		}

        protected function VerifyConnected() : bool {
            if (!$this->IsConnected())
                return (bool) $this->errorHandler->RegisterError("Not Connected", IDatabaseErrorHandler::TYPE_DATABASELINK_NOTCONNECTED);

            return true;
        }
	}
?>