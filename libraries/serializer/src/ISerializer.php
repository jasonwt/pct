<?php
    declare(strict_types=1);
	
    namespace pct\libraries\serializer;
    
    interface ISerializer {
        static public function Serialize($data) : string;
        static public function Unserialize(string $data);
    }
?>