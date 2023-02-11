<?php	
    declare(strict_types=1);

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? '1'));

	$dbLink = new mysqli("localhost", "root", "oiaw7jnt", "ynwildlife");

	if ($dbLink->connect_errno)
		throw new \Exception("\nerrno: {$dbLink->connect_errno}\nerror: {$dbLink->connect_error}\n\n");

    $dbAuth = [
        "host" => "localhost",
        "userName" => "root",
        "password" => "oiaw7jnt",
        "database" => "ynwildlife",
        "port" => 0
    ];
?>