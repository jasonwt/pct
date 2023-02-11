<?php	
    declare(strict_types=1);

	namespace pct\core;

	use ArrayAccess;
	use Iterator;

	interface ICore extends ArrayAccess, Iterator {
        public function CanCallList(string $methodName, $obj) : ?array;

        public function GetRequiredParentMethods(): array;
        public function GetRequiredParentChildrenNames(): array;
        public function GetRequiredParentChildrenTypes(): array;
        public function ValidateParentRequirements(ICore $parent): bool;

        public function OnRegisteredCallback() : ?ICore;
        public function OnUnregisteredCallback(): ?ICore;

		public function GetParent() : ?ICore;
        public function SetParent(?ICore $parent): ICore;
		public function GetName() : string;
        public function Rename(string $name): ?ICore;
		public function GetVersion() : string;

		public function GetFirstError(bool $peek = false) : ?string;
		public function GetLastError(bool $peek = false) : ?string;
		public function GetErrors() : array;

        public function __clone();
        public function __get($name);
		public function __isset($name);
		public function __call(string $methodName, array $arguments);
        public function CanCall(string $methodName, bool $includeSelf = true, bool $includeChildren = false): bool;

        public function RegisterChildren(ICore $obj): ?ICore;
        public function UnregisterChildren(ICore $obj): ?ICore;
        public function GetChildren(string $derivedFrom = "") : ?array;

		public function GetAttributes() : array;
	}
?>