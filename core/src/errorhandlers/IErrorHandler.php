<?php	
    declare(strict_types=1);

	namespace pct\core\errorhandlers;

	interface IErrorHandler {
        /******** BY DEFAULT NOT THROWN ********/

		const TYPE_NOTICE = 1;
		const TYPE_WARNING = 2;
		const TYPE_ERROR = 3;

        /********** BY DEFAULT THROWN **********/

        // Built-in Exceptions
		const TYPE_FATAL = 4;
        const TYPE_BADFUNCTIONCALL = 10;    // BadFunctionCallException: thrown if a callback refers to an undefined function or if some arguments are missing.
        const TYPE_BADMETHODCALL   = 11;    // BadMethodCallException: thrown if a callback refers to an undefined method or if some arguments are missing.
        const TYPE_DOMAIN          = 12;    // DomainException: thrown when an error occurs within the program logic
        const TYPE_INVALIDARGUMENT = 13;   // InvalidArgumentException: thrown when an argument is not of the expected type or value
        const TYPE_LENGTH          = 14;   // LengthException: thrown when an argument exceeds a maximum length
        const TYPE_LOGIC           = 15;   // LogicException: thrown when an error occurs in the program logic
        const TYPE_OUTOFBOUNDS     = 16;  // OutOfBoundsException: thrown when an array index or object property is not found
        const TYPE_OUTOFRANGE      = 17;  // OutOfRangeException: thrown when a value is not within the range of acceptable values
        const TYPE_OVERFLOW        = 18;  // OverflowException: thrown when a value exceeds the maximum value allowed
        const TYPE_RANGE           = 19;  // RangeException: thrown when a value is not within the range of acceptable values
        const TYPE_RUNTIME         = 20; // RuntimeException: thrown when an error occurs during runtime
        const TYPE_UNDERFLOW       = 21; // UnderflowException: thrown when a value is less than the minimum value allowed
        const TYPE_UNEXPECTEDVALUE = 22; // UnexpectedValueException: thrown if a value does not match with a set of values

        // ICore Exceptions
        const TYPE_ICORE_ALREADYEXISTS              = 100; //
        const TYPE_ICORE_DOESNOTEXISTS              = 101; //
        const TYPE_ICORE_PARENTREQUIREMENTS         = 102; //
        const TYPE_ICORE_INVALIDNAME                = 103; //
        const TYPE_ICORE_PARENTREQUIRED             = 104;
        const TYPE_ICORE_ILLEGALOPERATION           = 105;
        const TYPE_ICORE_REQUIREDMETHODNOTAVAILABLE = 106;
        
		public function GetFirstError(bool $peek = false) : ?string;
		public function GetLastError(bool $peek = false) : ?string;
		public function GetErrors() : array;
		public function ClearErrors() : bool;
		public function RegisterError(string $message, int $errorType, int $errorCode = 0) : ?bool;
	}
?>