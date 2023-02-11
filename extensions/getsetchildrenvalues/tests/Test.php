<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/../vendor/autoload.php");

    use pct\core\ICore;
    use pct\core\Core;

//    use pct\extensions\getsetvalue\GetSetValueExtension;
    use pct\extensions\getsetchildrenvalues\GetSetChildrenValuesExtension;

    class Component extends Core implements ICore {
    }

    $component = new Component(
        "componentName", 
        ["age" => 45, "dob" => "01/14/1977"],
        [
            new GetSetChildrenValuesExtension(),
            new Component("StacyThompson", 
                [
                    "relationship" => "Wife"
                ]
            ),
            new Component("PeytonThompson", 
                [
                    "relationship" => "Daughter"
                ]
            )
        ]
    );

/*    
    foreach (array_filter($component->GetChildren(), function($v, $k) {return $v->CanCall("GetValue", true, true);}, ARRAY_FILTER_USE_BOTH) as $k => $v)
        echo "$k: " . print_r($v->GetValue(), true) . "\n";

    foreach (array_filter($component->GetChildren(), function($v, $k) {return $v->CanCall("SetValue", true, true);}, ARRAY_FILTER_USE_BOTH) as $k => $v)
        $v->SetValue($k);

    foreach (array_filter($component->GetChildren(), function($v, $k) {return $v->CanCall("GetValue", true, true);}, ARRAY_FILTER_USE_BOTH) as $k => $v)
        echo "$k: " . print_r($v->GetValue(), true) . "\n";
*/
    //print_r($component->GetChildren());

//    echo "GetValue(): " . $component->GetValue() . "\n";
  //  $component->SetValue("-100");
    //echo "GetValue(): " . $component->GetValue() . "\n";

    //print_r(class_parents($component) + [get_class($component) => get_class($component)]);
//    use pct\errorhandler\ErrorHandler;

//    $errorHandler = new ErrorHandler();

//    print_r($errorHandler);

?>