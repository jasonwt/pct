<?php	
    declare(strict_types=1);	

	namespace pct\extensions\getsetchildrenvalues;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\core\ICore;
    use pct\core\extensions\Extension;
    use pct\core\errorhandlers\IErrorHandler;
    use pct\extensions\getsetchildrenvalues\IGetSetChildrenValuesExtension;

    class GetSetChildrenValuesExtension extends Extension implements IGetSetChildrenValuesExtension {
        public function __construct(array $attributes = [], $components = null, ?IErrorHandler $errorHandler = null) {
            parent::__construct("GetSetChildrenValuesExtension", $attributes, $components, $errorHandler);
        }

        public function GetChildValue(string $childName) {
            if (is_null($this->GetParent()))
                return $this->errorHandler->RegisterError("Method can not be called with no parent assigned.'", IErrorHandler::TYPE_ICORE_PARENTREQUIRED);

            if (!isset($this->GetParent()->$childName))
                return $this->errorHandler->RegisterError("Object '$childName' does not exist in parent object '" . $this->GetParent()->GetName() . "'", IErrorHandler::TYPE_ICORE_DOESNOTEXISTS);

            $component = $this->GetParent()->$childName;

            if (!$component->CanCall("GetValue", true, true))
                return $this->errorHandler->RegisterError("Required method 'GetValue()' is not available in object '$childName'.", IErrorHandler::TYPE_ICORE_REQUIREDMETHODNOTAVAILABLE);

            return $component->GetValue();
        }

        public function GetChildrenValues(): ?array {
            if (is_null($this->GetParent()))
                return $this->errorHandler->RegisterError("Method can not be called with no parent assigned.'", IErrorHandler::TYPE_ICORE_PARENTREQUIRED);

            $componentsValues = [];

            foreach ($this->GetParent()->GetChildren() as $childName => $component)
                if ($component->CanCall("GetValue", true, true))
                    $componentsValues[$childName] = $this->GetParent()->GetChildValue($childName);
                
            return $componentsValues;
        }

        public function SetChildValue(string $childName, $value) : ?ICore {
            if (is_null($this->GetParent()))
                return $this->errorHandler->RegisterError("Method can not be called with no parent assigned.'", IErrorHandler::TYPE_ICORE_PARENTREQUIRED);

            if (!isset($this->GetParent()->$childName))
                return $this->errorHandler->RegisterError("Object '$childName' does not exist in parent object '" . $this->GetParent()->GetName() . "'", IErrorHandler::TYPE_ICORE_DOESNOTEXISTS);            

            $component = $this->GetParent()->$childName;

            if (!$component->CanCall("SetValue", true, true))
                return $this->errorHandler->RegisterError("Required method 'SetValue()' is not available in object '$childName'.", IErrorHandler::TYPE_ICORE_REQUIREDMETHODNOTAVAILABLE);

            $component->SetValue($value);

            return $this->GetParent();
        }

        public function SetChildrenValues(array $values): ?ICore {
            if (is_null($this->GetParent()))
                return $this->errorHandler->RegisterError("Method can not be called with no parent assigned.'", IErrorHandler::TYPE_ICORE_PARENTREQUIRED);
                
            foreach ($this->GetParent()->GetChildren() as $childName => $component)
                if ($component->CanCall("SetValue", true, true))
                    $this->GetParent()->SetChildValue($childName, $values[$childName]);                        
            
            return $this->GetParent();
        }
    }

?>