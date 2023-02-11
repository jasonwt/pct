<?php	
    declare(strict_types=1);

	namespace pct\core;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use \Exception;
    use \BadMethodCallException;
    use \UnexpectedValueException;
    use \InvalidArgumentException;

    use pct\core\exceptions\ParentRequirementsException;
    use pct\core\exceptions\DoesNotExistException;
    use pct\core\exceptions\AlreadyExistsException;
	use pct\core\exceptions\InvalidNameException;

	use pct\core\ICore;
	use pct\core\errorhandlers\IErrorHandler;
	use pct\core\errorhandlers\ErrorHandler;

	abstract class Core implements ICore {
		private int $coreAttributesIteratorPosition = 0;

        private ErrorHandler $errorHandler;

		private ?ICore $coreParent = null;
		private string $coreName = "";
		private string $coreVersion = "";
        private array $coreChildren = [];
		private array $coreAttributes = [];

		public function __construct(string $name, array $attributes = [], $children = null, ?IErrorHandler $errorHandler = null) {
			$this->errorHandler = (is_null($errorHandler) ? new ErrorHandler() : $errorHandler);

			if (!$this->ValidateCoreName($this->coreName = trim($name))) {
                $this->errorHandler->RegisterError("Invalid ICore Name '$name'.", $this->errorHandler::TYPE_ICORE_INVALIDNAME);
            } else {
                if (count($attributes) > 0) {
                    foreach ($attributes as $k => $v) {
                        if(array_keys($attributes) !== range(0, count($attributes) - 1))
                            $this[$k] = $v;
                        else
                            $this[$v] = null;
                    }
                }
    
                if (!is_null($children)) {
                    if (!is_array($children))
                        $children = [$children];
    
                    foreach ($children as $childKey => $child) {
                        if (is_array($child))
                            $child = new $childKey(...$child);

                        if ($child instanceof ICore)
                            $this->RegisterChildren($child);
                        else
                            $this->errorHandler->RegisterError("Argument is not an instance of ICore.", $this->errorHandler::TYPE_INVALIDARGUMENT);
                    }
                }
            }
		}

        /************************************ MAGIC METHODS ************************************/

        static private function clone_array($array) : array {
            return array_map(function($element) {
                return ((is_array($element))
                    ? static::clone_array($element)
                    : ((is_object($element))
                        ? clone $element
                        : $element
                    )
                );
            }, $array);
        }

        public function __clone() {            
            $this->coreAttributesIteratorPosition = 0;
            $this->errorHandler = clone $this->errorHandler;
            $this->coreParent = null;
            
            foreach (array_keys($this->coreChildren) as $childName) {
                $clonedChild = clone $this->coreChildren[$childName];
                $clonedChild->SetParent($this);

                $this->coreChildren[$childName] = $clonedChild;
            }

            $this->coreAttributes = static::clone_array($this->coreAttributes);            
        }

        public function __get($name) {
			if (!isset($this->coreChildren[$name])) {
                $this->errorHandler->RegisterError("Child '$name' does not exist.", $this->errorHandler::TYPE_ICORE_DOESNOTEXISTS);
			} else {
                return $this->coreChildren[$name];
            }
		}

		public function __isset($name) {
			return isset($this->coreChildren[$name]);
		}

        public function __call(string $methodName, array $arguments) {
            $canCallList = $this->CanCallList($methodName, $this->GetChildren());

            if (count($canCallList) > 0)
                return call_user_func_array([current($canCallList), $methodName], $arguments);            
            
            $this->errorHandler->RegisterError("$methodName() does not exist.", $this->errorHandler::TYPE_BADMETHODCALL);
        }

        /************************************ CANCALL METHODS ************************************/

        public function CanCallList(string $methodName, $obj) : ?array {
            $canCallList = [];

            $funcGetArgs = func_get_args();
            $methodName = array_shift($funcGetArgs);

            foreach ($funcGetArgs as $arg) {
                if (!is_array($arg))
                    $arg = [$arg];

                foreach ($arg as $obj) {
                    if (!is_object($obj)) {
                        return $this->errorHandler->RegisterError("Argument '" . gettype($obj) . "' is not an object.", $this->errorHandler::TYPE_INVALIDARGUMENT);
                    } else if (!$obj instanceof ICore) {
                        return $this->errorHandler->RegisterError("Argument is not an instance of ICore.", $this->errorHandler::TYPE_INVALIDARGUMENT);
                    } else if (method_exists($obj, $methodName)) {
                        $reflection = new \ReflectionMethod($obj, $methodName);

                        if ($reflection->isPublic())
                            $canCallList[$obj->GetName()] = $obj;        
                    }
                }
            }

            return $canCallList;
        }

        public function CanCall(string $methodName, bool $includeSelf = true, bool $includecomponents = false) : bool {
            if (!$includeSelf && !$includecomponents)
                return false;
            else if ($includeSelf && $includecomponents)
                return count($this->CanCallList($methodName, [$this] + $this->GetChildren())) > 0;
            else if ($includecomponents)
                return count($this->CanCallList($methodName, $this->GetChildren())) > 0;

            return count($this->CanCallList($methodName, $this)) > 0;
        }

        /************************************ PUBLIC PROPERTIES GET/SET ************************************/

		public function GetParent() : ?ICore {
			return $this->coreParent;
		}

        public function SetParent(?ICore $parent): ICore {
            if ($this->coreParent != null) {
                $this->coreParent->UnRegisterChildren($this);
            }

            $this->coreParent = $parent;

            return $this;
        }

		public function GetName() : string {
			return $this->coreName;
		}

        public function Rename(string $name) : ?ICore {
            if (!is_null($this->GetParent()))
                return $this->errorHandler->RegisterError("Can not rename ICore objects when a parent is set.", IErrorHandler::TYPE_ICORE_ILLEGALOPERATION);

            if (!$this->ValidateCoreName($name = trim($name)))
                return $this->errorHandler->RegisterError("Invalid ICore Name '$name'.", $this->errorHandler::TYPE_ICORE_INVALIDNAME);

            $this->coreName = $name;

            return $this;
        }

		public function GetVersion() : string {
			return $this->coreVersion;
		}

        public function GetRequiredParentMethods(): array {
            return [];
        }
        public function GetRequiredParentChildrenNames(): array {
            return [];
        }

        public function GetRequiredParentChildrenTypes(): array {
            return [];
        }

        public function ValidateParentRequirements(ICore $parent): bool {
            foreach ($this->GetRequiredParentMethods() as $requiredMethod)
                if (!$parent->CanCall($requiredMethod, true, true))
                    return (bool) $this->errorHandler->RegisterError("Missing Required Parent Method: '$requiredMethod'.", $this->errorHandler::TYPE_ICORE_PARENTREQUIREMENTS);

            foreach ($this->GetRequiredParentChildrenNames() as $coreName)
                if (!isset($parent->$coreName))
                    return (bool) $this->errorHandler->RegisterError("Missing Required Parent Component Name: '$coreName'.", $this->errorHandler::TYPE_ICORE_PARENTREQUIREMENTS);

            foreach ($this->GetRequiredParentChildrenTypes() as $childType)
                if (count($parent->GetChildren($childType)) == 0)
                    return (bool) $this->errorHandler->RegisterError("Missing Required Parent Component Type: '$childType'", $this->errorHandler::TYPE_ICORE_PARENTREQUIREMENTS);

            return true;
        }

		/************************************ VALIDATIONS ************************************/

		protected function ValidateCoreName($name) : bool {
            if (trim($name) == "")
                return false;

            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name))
                return false;

            return true;
		}

		protected function ValidateAttributeName($name) : bool {
            if (trim($name) == "")
                return false;

            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name))
                return false;

            return true;
		}

		protected function ValidateAttributeValue($name, $value): bool {
			return true;
		}

        /************************************ PROTECTED CALLBACKS ************************************/

		public function OnRegisteredCallback() : ?ICore  {
			return $this;
		}

		public function OnUnregisteredCallback() : ?ICore {
			$this->coreParent = null;
			return $this;
		}

		/************************************ ERROR HANDLER ************************************/

		public function GetFirstError(bool $peek = false) : ?string {
			return $this->errorHandler->GetFirstError($peek);
		}

		public function GetLastError(bool $peek = false) : ?string {
			return $this->errorHandler->GetLastError($peek);
		}

		public function GetErrors() : array {
			return $this->errorHandler->GetErrors();
		}

		/************************************ Iterator Methods ************************************/
		
		public function rewind(): void {
			$this->coreAttributesIteratorPosition = 0;
		}
	
		#[\ReturnTypeWillChange]
		public function &current() {
			return $this->coreAttributes[array_keys($this->coreAttributes)[$this->coreAttributesIteratorPosition]];
		}
	
		#[\ReturnTypeWillChange]
		public function key() {
			return array_keys($this->coreAttributes)[$this->coreAttributesIteratorPosition];			
		}
	
		public function next(): void {
			++$this->coreAttributesIteratorPosition;
		}
	
		public function valid(): bool {
			return ($this->coreAttributesIteratorPosition <= count($this->coreAttributes));			
		}

        /************************************ ArrayAccess Methods ************************************/

		#[\ReturnTypeWillChange]
		public function offsetExists($offset): bool {
			return array_key_exists($offset, $this->coreAttributes);
		}

		#[\ReturnTypeWillChange]
		public function offsetGet($offset) {
            if (!array_key_exists($offset, $this->coreAttributes))
			//if (!isset($this->coreAttributes[$offset]))
				return $this->errorHandler->RegisterError("attribute '$offset' is not set", $this->errorHandler::TYPE_OUTOFBOUNDS);

			return $this->coreAttributes[$offset];
		}

		#[\ReturnTypeWillChange]
		public function offsetSet($offset, $value): void {
			if (!$this->ValidateAttributeName($offset))
                $this->errorHandler->RegisterError("Invalid attribute name '" . print_r($offset, true) . "'.", $this->errorHandler::TYPE_ICORE_INVALIDNAME);
			else if (!$this->ValidateAttributeValue($offset, $value))
                $this->errorHandler->RegisterError("Invalid attribute value for name '$offset'", $this->errorHandler::TYPE_UNEXPECTEDVALUE);
            else
			    $this->coreAttributes[$offset] = $value;
		}

		#[\ReturnTypeWillChange]
		public function offsetUnset($offset): void { 
			if (!isset($this->coreAttributes[$offset]))
                $this->errorHandler->RegisterError("Attribute '$offset' is not set.", $this->errorHandler::TYPE_OUTOFBOUNDS);
			else
				unset($this->coreAttributes[$offset]);
		}

        /************************************ ATTRIBUTES ************************************/		

		public function GetAttributes() : array {
			return $this->coreAttributes;
		}

        /************************************ CHILDREN ************************************/		

		public function RegisterChildren(ICore $obj) : ?ICore {
            $lastRegisteredObject = $obj;

            foreach (func_get_args() as $obj) {
                if (is_string($obj)) {
                    $objName = $obj;
                    
                    $obj = clone $lastRegisteredObject;
                    $obj->Rename($objName);
                }

                if (!($obj instanceof ICore)) {
                    return $this->errorHandler->RegisterError("Invalid argument for obj, not a valid ICore:object.", $this->errorHandler::TYPE_INVALIDARGUMENT);
                } else if (!$this->ValidateCoreName($name = trim($obj->GetName()))) {
                    return $this->errorHandler->RegisterError("Invalid ICore Object Name '$name'.", $this->errorHandler::TYPE_ICORE_INVALIDNAME);
                } else if (property_exists($this, $name)) {
                    return $this->errorHandler->RegisterError("A property with the name '$name' already exists.", $this->errorHandler::TYPE_ICORE_ALREADYEXISTS);
                } else if (isset($this->$name)) {
                    return $this->errorHandler->RegisterError("A child with the name '$name' already exists.", $this->errorHandler::TYPE_ICORE_ALREADYEXISTS);
                } else if ($obj->GetParent() != "") {
                    return $this->errorHandler->RegisterError("The child with the name '$name' is already registered with parent '" . $obj->GetParent()->GetName() . "'", $this->errorHandler::TYPE_ICORE_ALREADYEXISTS);
                } else if (!$obj->ValidateParentRequirements($this)) {
                    return $this->errorHandler->RegisterError("The parent " . $this->GetName() . " does not meet the requirments to register the object " . $obj->GetName() . "'", $this->errorHandler::TYPE_ICORE_PARENTREQUIREMENTS);
                } else {
                    $obj->SetParent($this);
                    $this->coreChildren[$obj->GetName()] = $obj;
                    $this->coreChildren[$obj->GetName()]->OnRegisteredCallback();

                    $lastRegisteredObject = $obj;
                }
            }

			return $this;
		}

		public function UnRegisterChildren($obj): ?ICore {
            foreach (func_get_args() as $obj) {
                if ($obj instanceof ICore)
                    $obj = $obj->GetName();

                if (!is_string($obj)) {
                    return $this->errorHandler->RegisterError("Invalid argument for obj, not a valid ICore:object.", $this->errorHandler::TYPE_INVALIDARGUMENT);
                } else if (!isset($this->$obj)) {
                    return $this->errorHandler->RegisterError("obj with name '$obj' does not exist.", $this->errorHandler::TYPE_ICORE_DOESNOTEXISTS);
                } else {
                    $obj = $this->coreChildren[$obj];
                    unset($this->coreChildren[$obj->GetName()]);
                    $obj->OnUnregisteredCallback();
                    $obj->SetParent(null);
                }
            }
			
			return $this;
		}

		public function GetChildren(string $derivedFrom = "") : ?array {
			$returnValue = [];

			if (count($funcGetArgs = func_get_args()) == 0)
				return $this->coreChildren;

			foreach ($funcGetArgs as $derivedFrom) {
				if (!is_string($derivedFrom)) {
                    return $this->errorHandler->RegisterError("Expected type string for derivedFrom parameter.", $this->errorHandler::TYPE_INVALIDARGUMENT);
				} else {
                    if (($derivedFrom = trim($derivedFrom)) == "")
                        continue;

                $returnValue += array_filter($this->coreChildren, function ($v, $k) use ($derivedFrom) {
                    return is_a($v, $derivedFrom);
                    }, ARRAY_FILTER_USE_BOTH);
                }
			}
			
			return $returnValue;
		}
	}

?>