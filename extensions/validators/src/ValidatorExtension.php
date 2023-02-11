<?php	
    declare(strict_types=1);	

	namespace pct\extensions\validators;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

	use pct\core\extensions\Extension;
	use pct\core\errorhandlers\IErrorHandler;
	use pct\extensions\validators\IValidatorExtension;

	abstract class ValidatorExtension extends Extension implements IValidatorExtension {		
		public function __construct(string $name, string $errorMessage, array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
            $attributes["errorMessage"] = trim($errorMessage);

			parent::__construct($name, $attributes, $components, $errorHandler);
		}

        public function Validate() : bool {
            return count($this->GetValidationErrors()) == 0;
        }
	}

?>