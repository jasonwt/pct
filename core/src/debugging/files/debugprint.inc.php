<?php	
    declare(strict_types=1);

	namespace pct\core\debugging;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use function pct\core\debugging\DebugString;

	function DebugPrint() {
        echo DebugString(...func_get_args());    
    }