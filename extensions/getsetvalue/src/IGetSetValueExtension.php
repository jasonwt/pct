<?php	
    declare(strict_types=1);	

	namespace pct\extensions\getsetvalue;

    use pct\core\ICore;
    use pct\core\extensions\IExtension;

    interface IGetSetValueExtension extends IExtension {
        public function GetValue();
        public function SetValue($value): ICore;
    }