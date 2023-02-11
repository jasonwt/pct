<?php	
    declare(strict_types=1);

	namespace pct\core\errorhandlers;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

	use pct\core\errorhandlers\IErrorHandler;

    use pct\core\exceptions\AlreadyExistsException;
    use pct\core\exceptions\DoesNotExistException;
    use pct\core\exceptions\ParentRequirementsException;
    use pct\core\exceptions\InvalidNameException;
    use pct\core\exceptions\ParentRequiredException;
    use pct\core\exceptions\IllegalOperationException;
    use pct\core\exceptions\RequiredMethodNotAvailable;

	class ErrorHandler implements IErrorHandler {
		protected $errors = [];

		public function __construct() {}

		public function GetFirstError(bool $peek = false) : ?string {
			return (
				count($this->errors) == 0 ? null : (
					implode(":", ($peek ? $this->errors[array_key_first($this->errors)] : array_shift($this->errors)))
				)
			);
		}

		public function GetLastError(bool $peek = false) : ?string {
			return (
				count($this->errors) == 0 ? null : (
					implode(":", ($peek ? $this->errors[array_key_last($this->errors)] : array_pop($this->errors)))
				)
			);
		}

		public function GetErrors() : array {
			return $this->errors;
		}

		public function ClearErrors() : bool {
			$this->errors = [];

			return true;
		}

        protected function ThrowException(int $errorType, string $message) :?bool {
            switch ($errorType) {
                case self::TYPE_FATAL:
                    throw new \Exception($message);
                case self::TYPE_BADFUNCTIONCALL:
                    throw new \BadFunctionCallException($message);
                case self::TYPE_BADMETHODCALL:
                    throw new \BadMethodCallException($message);
                case self::TYPE_DOMAIN:
                    throw new \DomainException($message);
                case self::TYPE_INVALIDARGUMENT:
                    throw new \InvalidArgumentException($message);
                case self::TYPE_LENGTH:
                    throw new \LengthException($message);
                case self::TYPE_LOGIC:
                    throw new \LogicException($message);
                case self::TYPE_OUTOFBOUNDS:
                    throw new \OutOfBoundsException($message);
                case self::TYPE_OUTOFRANGE:
                    throw new \OutOfRangeException($message);
                case self::TYPE_OVERFLOW:
                    throw new \OverflowException($message);
                case self::TYPE_RANGE:
                    throw new \DomainException($message);
                case self::TYPE_DOMAIN:
                    throw new \RangeException($message);
                case self::TYPE_RUNTIME:
                    throw new \RuntimeException($message);
                case self::TYPE_UNDERFLOW:
                    throw new \UnderflowException($message);
                case self::TYPE_UNEXPECTEDVALUE:
                    throw new \UnexpectedValueException($message);

                case self::TYPE_ICORE_ALREADYEXISTS:
                    throw new AlreadyExistsException($message);
                case self::TYPE_ICORE_DOESNOTEXISTS:
                    throw new DoesNotExistException($message);
                case self::TYPE_ICORE_PARENTREQUIREMENTS:
                    throw new ParentRequirementsException($message);
                case self::TYPE_ICORE_INVALIDNAME:
                    throw new InvalidNameException($message);
                case self::TYPE_ICORE_PARENTREQUIRED:
                    throw new ParentRequiredException($message);
                case self::TYPE_ICORE_ILLEGALOPERATION:
                    throw new IllegalOperationException($message);
                case self::TYPE_ICORE_REQUIREDMETHODNOTAVAILABLE:
                    throw new RequiredMethodNotAvailable($message);
            }

            return null;
        }

		public function RegisterError(string $message, int $errorType, int $errorCode = 0) : ?bool {
			$this->errors[] = [
				"type" => $errorType,
				"code" => $errorCode,
				"message" => $message
			];

            return $this->ThrowException($errorType, print_r($this->errors, true));
		}
	}
	
?>