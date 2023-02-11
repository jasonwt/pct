<?php	
    declare(strict_types=1);	

	namespace pct\extensions\managechildren;

    use pct\core\ICore;
    use pct\core\extensions\IExtension;

    Interface IManageChildrenExtension extends IExtension {
        public function RegisterClonedObject(ICore $obj, string $childName): ?ICore;        
    }

?>