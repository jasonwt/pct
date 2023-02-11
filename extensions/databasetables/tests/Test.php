<?php	
    declare(strict_types=1);

    namespace pct\extensions\databasetables\test;

    $pctRuntimeErrorReporting[] = E_ALL;
    $pctRuntimeIniSetDisplayErrors[] = "1";
    
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    require_once(__DIR__ . "/../vendor/autoload.php");

    use pct\core\components\Component;
    use pct\core\errorhandlers\IErrorHandler;

    use pct\extensions\databaselink\mysqli\MysqlDatabaseLink;

    use pct\extensions\databasetables\DatabaseTablesExtension;

    use function pct\core\debugging\DebugPrint;

    class TestComponent extends Component {
        protected \mysqli $dbLink;

        public function __construct(string $name, \mysqli $dbLink, array $attributes = [], $children = null, ?IErrorHandler $errorHandler = null) {
            $this->dbLink = $dbLink;

            parent::__construct($name, $attributes, $children, $errorHandler);
        }
    }

    require_once(__DIR__ . "/dbauth.inc.php");

    $component = new TestComponent("Form", $dbLink, [], [
        new MysqlDatabaseLink($dbLink),
        new DatabaseTablesExtension("hunters,permits")
    ]);

$component->LoadFromDatabase("hunters", "hunters.id='0004230'");
$component->hunters_firstname->SetValue("Stacy");
$component->WriteToDatabase("hunters");
//$component->WriteToDatabase("hunters");
    DebugPrint($component);


    

?>