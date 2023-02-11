<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators\value\pattern;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

	use pct\core\errorhandlers\IErrorHandler;

    use pct\core\extensions\validators\value\pattern\ValuePatternValidator;
	
	class NegativeIntgerValidator extends ValuePatternValidator {
		public function __construct(array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
			parent::__construct(
                "NegativeIntegerValidator", 
                "not a valid negative integer", 
                '^(\s*|-[0-9]+)$',
                $attributes, 
                $components, 
                $errorHandler
            );
		}
	}
?>