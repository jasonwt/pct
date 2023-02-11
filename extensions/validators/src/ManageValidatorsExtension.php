<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? '1'));

	use pct\core\extensions\Extension;
	use pct\core\errorhandlers\IErrorHandler;
    use pct\core\extensions\validators\IValidatorExtension;
	use pct\core\extensions\validators\IManageValidatorsExtension;

	class ManageValidatorsExtension extends Extension implements IManageValidatorsExtension {		
		public function __construct(array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {			
			parent::__construct("ComponentValidationsExtension", $attributes, $components, $errorHandler);
		}

        public function ValidateComponents() : array {
			$validationErrors = [];

			foreach ($this->GetParent()->GetComponents() as $componentName => $component) {
				$componentErrors = [];

				foreach ($component->GetExtensions("pct\\core\\extensions\\validators\\ValidatorExtension") as $extensionName => $extension)
					$componentErrors += $extension->ValidateComponent();

				if (count($componentErrors) > 0)
					$validationErrors[$componentName] = $componentErrors;
			}

			return $validationErrors;
		}

		public function AddComponentValidator(IValidatorExtension $validator, string $componentNames = "") : bool {
			if (count($funcGetArgs = func_get_args()) == 1)
				$funcGetArgs = array_keys($this->GetParent()->GetComponents());
			else
				array_shift($funcGetArgs);

            $validatorName = $validator->GetName();

			foreach ($funcGetArgs as $componentName) {
				$component = $this->GetParent()->$componentName;

                if (isset($component->$validatorName))
					continue;

                $component->RegisterComponents(clone $validator);				
			}

			return true;
		}

		public function RemoveComponentValidator(string $validatorName) : bool {
			if (count($funcGetArgs = func_get_args()) == 1)
				$funcGetArgs = array_keys($this->GetParent()->GetComponents());
			else
				array_shift($funcGetArgs);

			foreach ($funcGetArgs as $componentName) {
				$component = $this->GetParent()->$componentName;

				if (!isset($component->$validatorName))
					continue;

				if (!$component->UnregisterComponents($validatorName))
					return false;
			}

			return true;
		}
	}

?>