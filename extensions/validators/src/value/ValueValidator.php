<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators\value;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

	use pct\core\errorhandlers\IErrorHandler;
	use pct\core\extensions\validators\ValidatorExtension;
    use pct\core\extensions\validators\value\IValueValidator;

	abstract class ValueValidator extends ValidatorExtension implements IValueValidator {
		public function __construct(string $name, string $errorMessage, array $attributes, $components, ?IErrorHandler $errorHandler = null) {
            parent::__construct($name, $errorMessage, $attributes, $components, $errorHandler);
		}

        public function GetRequiredParentMethods(): array {
            return ["GetValue"];
        }
	}
?>