<?php

require_once __DIR__ . '/../vendor/autoload.php';

$client = new \Eureka\EurekaClient([
    'eurekaDefaultUrl' => 'http://testserver.yichuangzone.com:8340/eureka',
    'hostName' => 'test-php.ermao.com',
    'appName' => 'test-php',
    'ip' => '192.168.0.50',
    'port' => ['80', true],
    'homePageUrl' => 'http://test-php.ermao.com',
    'statusPageUrl' => 'http://test-php.ermao.com/info',
    'healthCheckUrl' => 'http://test-php.ermao.com/health',
    'appId'=> 'eoRkv0KveZ5Ph2KkbK0g2H1LusoCFtAh',
    'appSecret'=> 'sAfZ3nKZK014fUwRpIdXaf36D0GBKluW',
]);

class DummyProvider implements \Eureka\Interfaces\InstanceProvider {

    public function getInstances($appName) {
        echo "Eureka didn't respond correctly.";

        $obj = new stdClass();
        $obj->homePageUrl = "http://stackoverflow.com";
        return [$obj];
    }
}

try {
    $client->start();
    $url = $client->fetchInstance("test-php")->homePageUrl;
    var_dump($url);
}
catch (\Eureka\Exceptions\EurekaClientException $e) {
    echo $e->getMessage();
}