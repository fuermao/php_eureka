<?php


namespace Eureka\Library;


/**
 * Class Logger
 * @package Eureka\Library
 */
class Logger
{
    /**
     * 日志文件夹名称
     */
    const logDirName = "logs";

    /**
     * 文件分隔符号
     */
    const EurekaDS = DIRECTORY_SEPARATOR;

    /**
     * 设置文件权限
     */
    const FileAccess = 0766;

    /**
     * 日志实例
     * @var Logger
     */
    private static $instance = null;

    /**
     * 日志文件名称
     * @var string
     */
    private $logFileName;

    /**
     * 日志文件绝对路径
     * @var string
     */
    private $logAbsolutePath;

    /**
     * Logger constructor.
     */
    protected function __construct(){

    }

    /**
     * 设置文件名称
     * @param string $logFileName
     */
    public function setLogFileName(string $logFileName)
    {
        $this->logFileName = $logFileName;
    }

    /**
     * 获取日志实例
     * @param string $logFileName 日志文件名称
     * @return Logger
     */
    public static function getInstance(string $logFileName): Logger
    {
        if(self::$instance == null){
            self::$instance = new self();
        }
        // 设置日志文件名称
        self::$instance->setLogFileName($logFileName);
        // 创建日志目录
        self::$instance->createLogDir();
        return self::$instance;
    }

    /**
     * 创建日志目录以及创建日志文件
     * @return Logger
     */
    protected function createLogDir(): Logger{
        $rootLogPath = realpath(dirname(dirname(__DIR__)));
        $rootLogPath .= self::EurekaDS.self::logDirName.self::EurekaDS.date("Y",time()).self::EurekaDS.date("m",time());
        // 创建日志文件
        if(!is_dir($rootLogPath)){
            mkdir($rootLogPath,self::FileAccess,true);
        }
        $this->logAbsolutePath = $rootLogPath.self::EurekaDS.$this->logFileName."-".date("d",time()).".log";
        return $this;
    }

    /**
     * trace级别日志
     * @param $data
     */
    public function trace($data) :void
    {
        $this->write($data,"trace");
        return;
    }

    /**
     * debug级别日志
     * @param $data
     */
    public function debug($data):void
    {
        $this->write($data,"debug");
        return;
    }

    /**
     * info级别日志
     * @param $data
     */
    public function info($data):void{
        $this->write($data);
        return;
    }

    /**
     * notice级别日志
     * @param $data
     */
    public function notice($data):void
    {
        $this->write($data,"notice");
        return;
    }

    /**
     * error级别日志
     * @param $data
     */
    public function error($data):void
    {
        $this->write($data,"error");
        return;
    }

    /**
     * 写日志文件
     * @param $data
     * @param string $logLevel
     */
    private function write($data ,string $logLevel="info"):void{
        $str = "";
        $str.= "========================= Log Start =========================".PHP_EOL;
        $str.= "[Log Time]:".date("Y-m-d H:i:s:",time()).PHP_EOL;
        $str.= "[Log Timestamp]:".microtime(true).PHP_EOL;
        $str.= "[Log Level]:".$logLevel.PHP_EOL;
        // 编辑数据格式
        if(is_array($data)){
            foreach ($data as $key=>$datum) {
                // 保险起见如果值仍然是数组，则将其转换换未JSON数据
                if(is_array($datum)){
                    $str .= "[".$key."]:".json_encode($datum).PHP_EOL;
                }else{
                    $str .= "[".$key."]:".$datum.PHP_EOL;
                }
            }
        }else{
            $str.="[Log Data]:".$data.PHP_EOL;
        }
        $str .= "========================== Log End ==========================".PHP_EOL.PHP_EOL;

        $resource = null;
        try {
            // 打开编辑
            $resource = fopen($this->logAbsolutePath,"a+");
            // 写入数据
            fwrite($resource,$str);
        }catch (\Exception $exception){
            throw new $exception;
        } finally {
            // 关闭资源
            fclose($resource);
        }
        unset($resource,$str);
        return;
    }
}