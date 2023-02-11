<?php	
    declare(strict_types=1);

    error_reporting($pctRuntimeErrorReporting[] = E_ALL);
	ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[] = '1');

    require_once(__DIR__ . "/../vendor/autoload.php");

    require_once(__DIR__ . "/dbauth.inc.php");

    use pct\core\components\Component;
    use pct\extensions\databaselink\mysqli\MysqlDatabaseLink;

	$dbLink = new mysqli(
        $dbAuth["host"],
        $dbAuth["userName"],
        $dbAuth["password"],
        $dbAuth["database"],
        $dbAuth["port"]
    );

	if ($dbLink->connect_errno)
		throw new \Exception("\nerrno: {$dbLink->connect_errno}\nerror: {$dbLink->connect_error}\n\n");

    $component = new Component(
        "form", 
        ["attribute1" => "attrv1", "attribute2" => "attrv2"],
        new MysqlDatabaseLink($dbLink)
    );

    //$results = $component->Query("SELECT * FROM permits");

    print_r($component->GetDatabaseTableStructure("permits"));
    //print_r($results->FetchAll());

    //print_r($component);
?>