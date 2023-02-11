<?php
    declare(strict_types=1);

    namespace pct\libraries\httprequests;
	
    error_reporting($pctRuntimeErrorReporting[__NAMESPACE__] ?? ($pctRuntimeErrorReporting[0] ?? E_ALL));
    ini_set('display_errors', $pctRuntimeIniSetDisplayErrors[__NAMESPACE__] ?? ($pctRuntimeIniSetDisplayErrors[0] ?? '1'));

    class httprequests implements IHTTPRequests {
        public function __construct() {

        }

        public function POST(string $url, array $parameters) : array {
            throw new \Exception("Not implemented.");
        }

        public function GET(string $url, array $parameters) : array {
            throw new \Exception("Not implemented.");
        }

        public function DELETE(string $url, array $parameters) : array {
            throw new \Exception("Not implemented.");
        }

        public function PUT(string $url, array $parameters) : array {
            throw new \Exception("Not implemented.");
        }

        public function HEAD(string $url, array $parameters) : array {
            throw new \Exception("Not implemented.");
        }

        public function PATCH(string $url, array $parameters) : array {
            throw new \Exception("Not implemented.");
        }

        public function OPTIONS(string $url, array $parameters) : array {
            throw new \Exception("Not implemented.");
        }

    }
?>