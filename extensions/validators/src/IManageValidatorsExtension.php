<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators;

	use pct\core\extensions\IExtension;
    use pct\core\extensions\validators\IValidatorExtension;

	interface IManageValidatorsExtension extends IExtension {
        public function ValidateComponents(): array;
        public function AddComponentValidator(IValidatorExtension $validator, string $componentNames = ""): bool;
        public function RemoveComponentValidator(string $validatorName): bool;
	}

?>

