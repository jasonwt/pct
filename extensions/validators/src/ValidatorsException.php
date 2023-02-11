<?php
    declare(strict_types=1);

    namespace pct\core\extensions\validators;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? '1'));

    use \Exception;

	class ValidatorsException extends Exception {
    }