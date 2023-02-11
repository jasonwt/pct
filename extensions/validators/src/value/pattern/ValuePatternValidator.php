<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators\value\pattern;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

	use pct\core\errorhandlers\IErrorHandler;
    use pct\core\extensions\validators\value\ValueValidator;

	class ValuePatternValidator extends ValueValidator {
		public function __construct(string $name, string $errorMessage, string $pattern, array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
            $attributes["pattern"] = $pattern;

			parent::__construct($name, $errorMessage, $attributes, $components, $errorHandler);			
		}

		public function GetValidationErrors(): array {	
			if (preg_match("~" . $this["pattern"] . "~", (string) $this->GetParent()->GetValue()) != 1)
				return [$this->GetName() => $this["errorMessage"]];

			return [];			
		}
	}


?>