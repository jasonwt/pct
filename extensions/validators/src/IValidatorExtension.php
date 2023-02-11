<?php	
    declare(strict_types=1);	

	namespace pct\extensions\validators;

	use pct\core\extensions\IExtension;
    use pct\core\errorhandlers\IErrorHandler;

	interface IValidatorExtension extends IExtension {
        public function __construct(string $name, object $errorMessage, array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null);
        public function Validate(): bool;
        public function GetValidationErrors(): array;
	}

?>

