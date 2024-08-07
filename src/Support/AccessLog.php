<?php
namespace Jou\AccessLog\Support;

use Jou\AccessLog\Handlers\DeviceTypeHandlers;
use Jou\AccessLog\Models\AccessLog as AccessLogModel;

class AccessLog
{
    private $path,$method,$host,$referer,$ip,$ipcountry,$user_agent,$response,$parameter,$header;


    /**
     * AccessLog constructor.
     * @param $path
     * @param $method
     * @param $host
     * @param $referer
     * @param $ip
     * @param $ipcountry
     * @param $user_agent
     * @param $response
     * @param array $parameter
     * @param array $header
     */
    public function __construct($path,$method,$host,$referer,$ip,$ipcountry,$user_agent,$parameter=[],$header=[],$response=null)
    {

        $this->path = $path;
        $this->method = $method;
        $this->host = $host;
        $this->referer = $referer;
        $this->ip = $ip;
        $this->ipcountry = $ipcountry;
        $this->user_agent = $user_agent;
        $this->parameter = $parameter;
        $this->header = $header;
        $this->response = $response;

    }

    /**
     * 运行任务。
     *
     * @return void
     */
    public function handle()
    {

        $model = new AccessLogModel();
        $data = [
            'url'=>$this->getUrlPath($this->path),
            'method'=>$this->method,
            'host'=>$this->host,
            'referer'=>$this->referer,
            'ip'=>$this->ip,
            'ipcountry'=>$this->ipcountry,
            'user_agent'=>$this->user_agent,
            'device'=>DeviceTypeHandlers::getDevice($this->user_agent),
            'crawler'=>DeviceTypeHandlers::getCrawler($this->user_agent),
            'parameter'=>$this->parameter,
            'headers'=>$this->header,

            //'response'=>$this->response
        ];

        try{
            $model->create($data);
        }catch (\Exception $exception){
        }


    }

    private function getUrlPath($path){
        $str = substr($path , 0 , 1);
        if($str != '/' ){
            $path = '/'.$path;
        }
        return $path;
    }
}
