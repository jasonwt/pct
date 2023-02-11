<?php
    declare(strict_types=1);

    namespace pct\libraries\compression\runlength;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] = E_ALL);
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] = '1');

    
    $compression = new RunLengthCompression();

    print_r($compression);
?>