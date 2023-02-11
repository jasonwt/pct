<?php	
    declare(strict_types=1);

	namespace pct\core\extensions;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

	use pct\core\Core;
    use pct\core\errorhandlers\IErrorHandler;
    use pct\core\extensions\IExtension;

    class Extension extends Core implements IExtension {
        public function __construct(string $name, array $attributes = [], $children = null, ?IErrorHandler $errorHandler = null) {
            parent::__construct($name, $attributes, $children, $errorHandler);
        }

        public function Rename(string $name): ?IExtension {
            return $this->errorHander->RegisterError("Exctensions can not be renamed.");
        }
    }