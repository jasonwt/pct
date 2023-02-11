<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/../vendor/autoload.php");

    use pct\extensions\managechildren\ManageChildrenExtension;

    use pct\core\components\Component;


    $cloneComponent = new Component(
        "CloneComponent",
        [],
        new Component(
            "sub"
        )
    );
    

    $component = new Component("Family", [], new ManageChildrenExtension());

    $component->RegisterClonedObject(
        $cloneComponent,
        //new Component("tobecloned"),
        "JasonThompson",
        "StacyThompson",
        "PeytonThompson"
    );

    foreach ($component->GetChildren() as $childName => $child) {
        echo "$childName" . "->" . "GetParent(): " . $child->GetParent()->GetName() . "\n";
    }

    print_r($component);

//    use pct\errorhandler\ErrorHandler;

//    $errorHandler = new ErrorHandler();

//    print_r($errorHandler);

?>