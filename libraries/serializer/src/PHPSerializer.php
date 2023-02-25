<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\libraries\serializer\ISerializer;
//    use ReflectionObject;
    use ReflectionProperty;

    class PHPSerializer implements ISerializer {
        static public function Serialize($value, ?array $classProperties = null) : string {
            
            if (gettype($value) == "string" && !is_null($classProperties)) {
                $serializedString = "O:" . strlen($value) . ":\"" . $value . "\":" . count($classProperties) . ":{";

                foreach ($classProperties as $propertyName => list($protectionLevel, $propertyValue)) {
                    if ($protectionLevel == ReflectionProperty::IS_PRIVATE)
                        $propertyName = "\0" . $value . "\0" . $propertyName;
                    else if ($protectionLevel == ReflectionProperty::IS_PROTECTED)
                        $propertyName = "\0*\0" . $propertyName;            

                    $serializedString .= static::Serialize($propertyName);
                    $serializedString .= static::Serialize($propertyValue);
                }

                $serializedString .= "}";

                return $serializedString;
            }

            return serialize($value);

/*            
            if (($valueType = gettype($value)) == "object") {
                $objectName = get_class($value);                

                $objectReflection = new ReflectionObject($value);

                $classProperties = [];

                foreach ([ReflectionProperty::IS_PRIVATE, ReflectionProperty::IS_PROTECTED, ReflectionProperty::IS_PUBLIC] as $protectionLevel) {
                    foreach ($objectReflection->getProperties($protectionLevel) as $property) {
                        $property->setAccessible(true);

                        $classProperties[$property->getName()] = [
                            $protectionLevel,
                            $property->getValue($value)
                        ];
                    }
                }

                $value = $objectName;
            }

            if (($valueType = gettype($value)) == "NULL") {
                return "N;";
            } else if ($valueType == "boolean") {
                return "b:" . ($value ? "1" : "0") . ";";
            } else if ($valueType == "string") {
                if (is_null($classProperties))
                    return "s:" . strlen($value) . ":\"$value\";";

                $serializedString = "O:" . strlen($value) . ":\"" . $value . "\":" . count($classProperties) . ":{";

                foreach ($classProperties as $propertyName => list($protectionLevel, $propertyValue)) {
                    if ($protectionLevel == ReflectionProperty::IS_PRIVATE)
                        $propertyName = "\0" . $value . "\0" . $propertyName;
                    else if ($protectionLevel == ReflectionProperty::IS_PROTECTED)
                        $propertyName = "\0*\0" . $propertyName;            

                    $serializedString .= static::Serialize($propertyName);
                    $serializedString .= static::Serialize($propertyValue);
                }

                $serializedString .= "}";

                return $serializedString;
            } else if ($valueType == "integer") {
                return "i:" . $value . ";";         

            } else if ($valueType == "double") {
                return "d:" . $value . ";";         

            } else if ($valueType == "array") {
                $serializedString = "a:" . count($value) . ":{";
                
                foreach ($value as $k => $v) {
                    $serializedString .= static::Serialize($k);
                    $serializedString .= static::Serialize($v);
                }

                $serializedString .= "}";

            } else {
                throw new \Exception("Unsupported type: " . gettype($value));
            }

            return $serializedString;
*/            
        }        

        static public function Unserialize(string &$data) { 
            return unserialize($data);
        }   
    }
?>