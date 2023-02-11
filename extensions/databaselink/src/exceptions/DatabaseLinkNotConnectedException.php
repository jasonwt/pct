<?php
    declare(strict_types=1);

    namespace pct\extensions\databaselink\exceptions;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

	class DatabaseLinkNotConnectedException extends \Exception {
    }