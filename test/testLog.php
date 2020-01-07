<?php

use Eureka\Library\Logger;

if(!defined("EUREKA_DS")){
    define("EUREKA_DS",DIRECTORY_SEPARATOR);
}
require_once dirname(__DIR__).EUREKA_DS."vendor".EUREKA_DS."autoload.php";

$data["DD"] = "AA";
Logger::getInstance("eureka")->info($data);
