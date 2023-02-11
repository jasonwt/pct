<?php	
    declare(strict_types=1);	

	namespace pct\extensions\getsetvalue;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\core\ICore;
    use pct\core\errorhandlers\IErrorHandler;
    use pct\core\extensions\Extension;
    use pct\extensions\getsetvalue\IGetSetValueExtension;

    class GetSetValueExtension extends Extension implements IGetSetValueExtension {
        public function __construct($defaultValue = null, array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
            $attributes["value"] = $defaultValue;

            parent::__construct("GetSetValueExtension", $attributes, $components, $errorHandler);
        }

        public function GetValue() {
            return $this["value"];
        }

        public function SetValue($value) : ICore {
            $this["value"] = $value;
            return $this;
        }
    }