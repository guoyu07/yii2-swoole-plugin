<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/12
 * Time: 下午3:02
 */
namespace app\components\yii2Swoole\src;
use yii\base\Component;

class Server extends Component {
    public $host = "127.0.0.1";
    public $port = "9778";
    public $mode = SWOOLE_PROCESS;
    public $socket_type = SWOOLE_TCP;
    public $config = [];
    public $entrance_file;//项目入口文件
    private $server;
    private $custom_config = [
        'daemonize'=>1,
        'reactor_num'=>4,
        'worker_num'=>20,
        'max_request' => 100,

    ];
    public function init()
    {
        $this->server = new \swoole_http_server($this->host,$this->port,$this->mode,$this->socket_type);
        //设置配置
        $this->config = array_merge($this->custom_config,$this->config);
        $this->server->set($this->config);
        $this->server->on('request',[$this, 'Request']);
        parent::init();
    }

    public function run(){
        $this->server->start();
    }

    public function Request($request,$response){
        $this->dealRequest($request);

       $this->appRun();
    }

    /**
     * 处理请求 赋值给$_SERVER $_GET $_POST $_COOKIE $_FILE
     */
    private function dealRequest($request){
        $_GET = isset($request->get) ? $request->get : [];
        $_POST = isset($request->post) ?  $request->post : [];
        $_COOKIE = isset($request->cookie) ?  $request->cookie : [];
        if( isset($request->files) ) {
            $files = $request->files;
            foreach ($files as $k => $v) {
                if( isset($v['name']) ){
                    $_FILES = $files;
                    break;
                }
                foreach ($v as $key => $val) {
                    $_FILES[$k]['name'][$key] = $val['name'];
                    $_FILES[$k]['type'][$key] = $val['type'];
                    $_FILES[$k]['tmp_name'][$key] = $val['tmp_name'];
                    $_FILES[$k]['size'][$key] = $val['size'];
                    if(isset($val['error'])) $_FILES[$k]['error'][$key] = $val['error'];
                }
            }
        }
        $server = isset($request->server) ? $request->server : [];
        $header = isset($request->header) ? $request->header : [];
        foreach ($server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
            unset($server[$key]);
        }
        foreach ($header as $key => $value) {
            $_SERVER['HTTP_'.strtoupper($key)] = $value;
        }
    }

    /**
     * 跑起应用程序
     * @param $request
     * @param $response
     */
    public function appRun(){
        require $this->entrance_file;
        $this->run();
    }
}