<?php

namespace Eureka;

use Eureka\Exceptions\DeRegisterFailureException;
use Eureka\Exceptions\InstanceFailureException;
use Eureka\Exceptions\RegisterFailureException;
use Eureka\Library\Logger;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class EurekaClient {

    /**
     * @var EurekaConfig
     */
    private $config;
    private $instances;

    // constructor
    public function __construct($config) {
        $this->config = new EurekaConfig($config);
    }

    // getter
    public function getConfig() {
        return $this->config;
    }

    // register with eureka
    public function register() {
        $config = $this->config->getRegistrationConfig();

        $client = new GuzzleClient(['base_uri' => $this->config->getEurekaDefaultUrl()]);
        $this->output("[" . date("Y-m-d H:i:s") . "]" . " Registering...");

        $response = $client->request('POST', '/eureka/apps/' . $this->config->getAppName(), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($config)
        ]);

        // 记录日志信息
        $logData["Register Url"] = $this->config->getEurekaDefaultUrl();
        $logData["Register Config"] = $config;
        $logData["Request Method"] = "POST";
        if($response->getStatusCode() != 204) {
            // 记录异常信息
            $logData["Register Result"] = "注册失败！";
            $logData["Register Response"] = $response->getBody();
            Logger::getInstance("register")->error($logData);
            throw new RegisterFailureException("Could not register with Eureka.");
        }else{
            // 正常记录日志信息
            // 记录异常信息
            $logData["Register Result"] = "注册成功！";
            $logData["Register Response"] = $response->getBody();
            Logger::getInstance("register")->info($logData);
        }
    }

    // de-register from eureka
    public function deRegister() {
        $client = new GuzzleClient(['base_uri' => $this->config->getEurekaDefaultUrl()]);
        $this->output("[" . date("Y-m-d H:i:s") . "]" . " De-registering...");

        $response = $client->request('DELETE', '/eureka/apps/' . $this->config->getAppName() . '/' . $this->config->getInstanceId(), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
        // 记录日志信息
        $logData["Delete Register Url"] = $this->config->getEurekaDefaultUrl();
        $logData["Request Method"] = "DELETE";
        $logData["Delete Register App Name"] = $this->config->getAppName();
        if($response->getStatusCode() != 200) {
            // 添加日志信息
            $logData["Delete Register Result"] = "取消注册失败！";
            $logData["Delete Register Response"] = $response->getBody();
            Logger::getInstance("delete_register")->error($logData);
            throw new DeRegisterFailureException("Cloud not de-register from Eureka.");
        }else{
            // 正常记录日志信息
            // 添加日志信息
            $logData["Delete Register Result"] = "取消注册成功！";
            $logData["Delete Register Response"] = $response->getBody();
            Logger::getInstance("register")->info($logData);
        }
    }

    // send heartbeat to eureka
    public function heartbeat() {
        $client = new GuzzleClient(['base_uri' => $this->config->getEurekaDefaultUrl()]);
        $this->output("[" . date("Y-m-d H:i:s") . "]" . " Sending heartbeat...");

        $response = null;
        try {
            $response = $client->request('PUT', '/eureka/apps/' . $this->config->getAppName() . '/' . $this->config->getInstanceId(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);
            // 记录日志信息
            $logData["Send Heart Beat Url"] = $this->config->getEurekaDefaultUrl();
            $logData["Request Method"] = "PUT";
            $logData["Send Heart Beat App Name"] = $this->config->getAppName();
            // 根据返回结果判断是否发送心跳成功
            if($response->getStatusCode() != 200) {

                // 记录日志信息
                $logData["Send Heart Beat Result"] = "发送心跳失败！";
                $this->output("[" . date("Y-m-d H:i:s") . "]" . " Heartbeat failed... (code: " . $response->getStatusCode() . ")");
            }else{
                // 记录日志信息
                $logData["Send Heart Beat Result"] = "发送心跳成功！";
            }
            $logData["Send Heart Beat Response"] = $response->getBody();
            Logger::getInstance("send_heart_beat")->info($logData);
        }
        catch (Exception $e) {
            $logData["Heart Beat Result"] = "心跳发送异常！";
            $logData["Heart Beat Exception"] = $e->getMessage();
            Logger::getInstance("send_heart_beat")->error($response->getBody());
            $this->output("[" . date("Y-m-d H:i:s") . "]" . "Heartbeat failed because of connection error... (code: " . $e->getCode() . ")");
        }
    }

    // register and send heartbeats periodically
    public function start() {
        $this->register();

        $counter = 0;
        while (true) {
            $this->heartbeat();
            $counter++;
            sleep($this->config->getHeartbeatInterval());
        }

        return 0;
    }

    public function fetchInstance($appName) {
        // 获取应用信息
        try {
            $instances = $this->fetchInstances($appName);
        } catch (InstanceFailureException $e) {
            Logger::getInstance("getInstance")->error($e->getMessage());
        }
        return $this->config->getDiscoveryStrategy()->getInstance($instances);
    }

    public function fetchInstances($appName) {
        if(!empty($this->instances[$appName])) {
            return $this->instances[$appName];
        }

        $client = new GuzzleClient(['base_uri' => $this->config->getEurekaDefaultUrl()]);
        $provider = $this->getConfig()->getInstanceProvider();

        try {
            $response = $client->request('GET', '/eureka/apps/' . $appName, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            // 记录日志信息
            $logData["Get Instance Url"] = $this->config->getEurekaDefaultUrl();
            $logData["Request Method"] = "GET";
            $logData["Get Instance Response"] = $response->getBody()->getContents();
            Logger::getInstance("get_instance")->info($logData);

            // 判断结果
            if($response->getStatusCode() != 200) {
                if(!empty($provider)) {
                    return $provider->getInstances($appName);
                }
                throw new InstanceFailureException("Could not get instances from Eureka.");
            }

            $body = json_decode($response->getBody()->getContents());
            if(!isset($body->application->instance)) {
                if(!empty($provider)) {
                    return $provider->getInstances($appName);
                }

                throw new InstanceFailureException("No instance found for '" . $appName . "'.");
            }

            $this->instances[$appName] = $body->application->instance;

            return $this->instances[$appName];
        }
        catch (RequestException $e) {
            if(!empty($provider)) {
                return $provider->getInstances($appName);
            }
            throw new InstanceFailureException("No instance found for '" . $appName . "'.");
        }
    }

    private function output($message) {
        if(php_sapi_name() !== 'cli')
            return;

        echo $message . "\n";
    }
}