<?php	
    declare(strict_types=1);	

	namespace pct\extensions\getsetchildrenvalues;

    use pct\core\ICore;
    use pct\core\extensions\IExtension;

    interface IGetSetChildrenValuesExtension extends IExtension {

        public function GetChildValue(string $childName);
        public function GetChildrenValues(): ?array;

        public function SetChildValue(string $childName, $value) : ?ICore;
        public function SetChildrenValues(array $values): ?ICore;
    }

?>