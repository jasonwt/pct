<?php	
    declare(strict_types=1);

	namespace pct\core\debugging;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

	function DebugString() : string {
        $returnValue = "\n";

        $args = func_get_args();

        if (count($args) > 0) {
            foreach ($args as $arg) {
                if (is_null($arg)) {
                    $returnValue .= "(null)\n";
                } else if (is_string($arg)) {
                    $returnValue .= $arg . "\n";                
                } else {
                    $returnValue .= print_r($arg, true);
                }
            }            
        } 

        $debuggingBacktrace = debug_backtrace();

        for ($cnt = count($debuggingBacktrace)-1; $cnt >= 0; $cnt --) {
            $fileName     = (isset($debuggingBacktrace[$cnt]["file"])     ? $debuggingBacktrace[$cnt]["file"] : "");
            $lineNumber   = (isset($debuggingBacktrace[$cnt]["line"])     ? $debuggingBacktrace[$cnt]["line"] : "");
            $functionName = (isset($debuggingBacktrace[$cnt]["function"]) ? $debuggingBacktrace[$cnt]["function"] : "");
            $functionArgs = (isset($debuggingBacktrace[$cnt]["args"])     ? $debuggingBacktrace[$cnt]["args"] : "");
            $className    = (isset($debuggingBacktrace[$cnt]["class"])    ? $debuggingBacktrace[$cnt]["class"] : "");
            $classType    = (isset($debuggingBacktrace[$cnt]["type"])     ? $debuggingBacktrace[$cnt]["type"] : "");
            $obj          = (isset($debuggingBacktrace[$cnt]["object"])   ? $debuggingBacktrace[$cnt]["object"] : "");

            $returnValue .= $fileName . "[$lineNumber]: ";

            if ($cnt > 0) {
                $returnValue .= ($className != "" ? $className . ($classType == "::" ? "::" : "->") : "");
                $returnValue .= ($functionName != "" ? $functionName . "()" : "");
                $returnValue .= "\n";    
            } else {
                $returnValue .= "\n";    
            }
        }        

        $returnValue .= "\n";

        return $returnValue;
    }