<?php	
	declare(strict_types=1);	

	namespace pct\extensions\databasetables;

    use pct\core\components\IComponent;

    interface IDatabaseTablesField extends IComponent {
        public function GetValue(): ?string;
        public function SetValue(?string $value): IComponent;
    }
?>