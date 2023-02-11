<?php	
	declare(strict_types=1);	

	namespace pct\extensions\databasetables;
	
	error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    use pct\core\components\Component;
	use pct\core\errorhandlers\IErrorHandler;

    use pct\core\components\IComponent;

    class DatabaseTablesField extends Component implements IDatabaseTablesField {
        public function __construct(string $name, ?string $defaultValue = null, array $attributes = [], $children = null, ?IErrorHandler $errorHandler = null) {
            $attributes["value"] = (!is_null($defaultValue) ? $defaultValue : ($attributes["value"] ?? null));

            parent::__construct($name, $attributes, $children, $errorHandler);
        }

        public function GetValue(): ?string {
            return $this["value"];
        }

        public function SetValue(?string $value): IComponent {
            $this["value"] = $value;
            return $this;
        }
    }

?>