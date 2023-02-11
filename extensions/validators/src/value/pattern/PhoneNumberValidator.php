<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators\value\pattern;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

	use pct\core\errorhandlers\IErrorHandler;

    use pct\core\extensions\validators\value\pattern\ValuePatternValidator;
	
	class PhoneNumberValidator extends ValuePatternValidator {
		public function __construct(array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
			parent::__construct(
                "PhoneNumberValidator", 
                "not a valid phone number", 
                '^(\s*|(\+\d{1,2}\s)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4})$',
                $attributes, 
                $components, 
                $errorHandler
            );
		}
	}
?>