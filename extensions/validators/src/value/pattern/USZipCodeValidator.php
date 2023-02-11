<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators\value\pattern;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

	use pct\core\errorhandlers\IErrorHandler;

    use pct\core\extensions\validators\value\pattern\ValuePatternValidator;
	
	class USZipCodeValidator extends ValuePatternValidator {
		public function __construct(array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
			parent::__construct(
                "USZipCodeValidator", 
                "not a valid US zip code", 
                '^(\s*|[0-9]{5}(?:-[0-9]{4})?)$', 
                $attributes, 
                $components, 
                $errorHandler
            );
		}
	}
?>