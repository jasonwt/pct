<?php	
    declare(strict_types=1);	

	namespace pct\core\extensions\validators\value\pattern;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

	use pct\core\errorhandlers\IErrorHandler;

    use pct\core\extensions\validators\value\pattern\ValuePatternValidator;
	
	class EmailAddressValidator extends ValuePatternValidator {
		public function __construct(array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
			parent::__construct(
                "EmailAddressValidator", 
                "not a valid email address", 
                '^(\s*$|([a-z0-9!#$%&\'*+/=?^_`{|}\~-]+(?:\.[a-z0-9!#$%&\'*+/=?^_`{|}\~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?))',
                $attributes, 
                $components, 
                $errorHandler
            );
		}
	}
?>