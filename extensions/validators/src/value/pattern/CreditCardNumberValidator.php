<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators\value\pattern;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

	use pct\core\errorhandlers\IErrorHandler;

    use pct\core\extensions\validators\value\pattern\ValuePatternValidator;
	
	class CreditCardNumberValidator extends ValuePatternValidator {
		public function __construct(array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
			parent::__construct(
                "CreditCardNumberValidator", 
                "not a valid credit card number", 
                '^(\s*|(?:4[0-9]{12}(?:[0-9]{3})?|[25][1-7][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11}))$', 
                $attributes, 
                $components, 
                $errorHandler
            );
		}
	}
?>