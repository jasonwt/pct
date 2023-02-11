<?php	
    declare(strict_types=1);	

	namespace pct\extensions\managechildren;

    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? '1'));

    use pct\core\ICore;
    use pct\core\errorhandlers\IErrorHandler;
    use pct\core\extensions\Extension;

    use pct\extensions\managechildren\IManageChildrenExtension;

    class ManageChildrenExtension extends Extension implements IManageChildrenExtension {
        public function __construct(array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
            parent::__construct("ManageChildrenExtension", $attributes, $components, $errorHandler);
        }

        public function RegisterClonedObject(ICore $obj, string $childName): ?ICore {
            if (is_null($this->GetParent()))
                return $this->errorHandler->RegisterError("Method can not be called with no parent assigned.'", IErrorHandler::TYPE_ICORE_PARENTREQUIRED);
                
            $funcGetArgs = func_get_args();

            for ($cnt = 1; $cnt < count($funcGetArgs); $cnt ++) {
                if (($childName = trim($funcGetArgs[$cnt])) == "")
                    continue;

                $newObject = clone $obj;

                if (is_null($newObject->Rename($childName)))
                    return null;

                if (is_null($this->GetParent()->RegisterChildren($newObject)))
                    return null;
            }

            return $this->GetParent();
        }
    }

?>