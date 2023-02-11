<?php	
    declare(strict_types=1);

	namespace pct\core\components;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

	use pct\core\Core;
    use pct\core\components\IComponent;
    use pct\core\errorhandlers\IErrorHandler;

    class Component extends Core implements IComponent {
        public function __construct(string $name, array $attributes = [], $children = null, ?IErrorHandler $errorHandler = null) {
            parent::__construct($name, $attributes, $children, $errorHandler);
        }
    }
?>