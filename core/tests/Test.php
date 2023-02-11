<?php
    declare(strict_types=1);

    namespace pct\test;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    require_once(__DIR__ . "/../vendor/autoload.php");

    use pct\core\components\Component;
    use pct\core\errorhandlers\IErrorHandler;
    use function pct\core\debugging\DebugPrint;

    class Person extends Component {
        public function __construct(string $name, array $attributes = [], $components = null, IErrorHandler $iErrorHander = null) {
            $attributes["name"] = "";
            $attributes["age"] = 0;
            $attributes["dob"] = "";

            parent::__construct($name, $attributes, $components, $iErrorHander);
        }
    }

    class Animal extends Component {
        public function __construct(string $name, string $type, array $attributes = [], $components = null, IErrorHandler $iErrorHander = null) {
            $attributes["name"] = "";
            $attributes["age"] = 0;
            $attributes["type"] = $type;
            $attributes["dob"] = "";

            parent::__construct($name, $attributes, $components, $iErrorHander);
        }
    }
    
    $component = new Component("family");

    $component->RegisterChildren(
        new Person("Jason"),
        "Stacy",
        "Peyton",
        new Animal("Timber", "Dog"),
        new Animal("Jade", "Cat"),
        "Meep",
        "Bones",
        "Cooper",
        "Franky"
    );

    DebugPrint($component);
?>