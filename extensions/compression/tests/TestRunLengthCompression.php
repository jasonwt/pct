<?php
    declare(strict_types=1);
	
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/../src/runlength/RunLengthCompression.php");
    
    $compression = new RunLengthCompression();

    print_r($compression);
?>