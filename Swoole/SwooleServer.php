<?php

/**
 * Swoole服务端.
 */
class SwooleServer
{
    private $_serv = null;
    private $_setting = array();

    public function __construct($host = '0.0.0.0', $port = 9501)
    {
        $this->_setting = array(
            'host' => $host,
            'port' => $port,
            'env' => 'dev', //环境 dev|test|prod
            'process_name' => SWOOLE_TASK_NAME_PRE, //swoole 进程名称
            'worker_num' => 4, //一般设置为服务器CPU数的1-4倍
            'task_worker_num' => 0, //task进程的数量
            'task_ipc_mode' => 3, //使用消息队列通信，并设置为争抢模式
            'task_max_request' => 10000, //task进程的最大任务数
            'daemonize' => 1, //以守护进程执行
            'max_request' => 1,
            'dispatch_mode' => 3,
            'log_file' => SWOOLE_PATH.DIRECTORY_SEPARATOR.'Application'.DIRECTORY_SEPARATOR.'Runtime'.DIRECTORY_SEPARATOR.'Logs'.DIRECTORY_SEPARATOR.'Swoole'.date('Ymd').'.log', //日志
        );
    }

    /**
     * 运行swoole服务
     */
    public function run()
    {
        $this->_serv = new \swoole_http_server($this->_setting['host'], $this->_setting['port']);
        $this->_serv->set(array(
            'worker_num' => $this->_setting['worker_num'],
            'task_worker_num' => $this->_setting['task_worker_num'],
            'task_ipc_mode ' => $this->_setting['task_ipc_mode'],
            'task_max_request' => $this->_setting['task_max_request'],
            'daemonize' => $this->_setting['daemonize'],
            'max_request' => $this->_setting['max_request'],
            'dispatch_mode' => $this->_setting['dispatch_mode'],
            'log_file' => $this->_setting['log_file'],
        ));

        $this->_serv->on('Request', array($this, 'onRequest'));

        $this->_serv->on('Start', array($this, 'onStart'));
        $this->_serv->on('Connect', array($this, 'onConnect'));
        $this->_serv->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->_serv->on('ManagerStart', array($this, 'onManagerStart'));
        $this->_serv->on('WorkerStop', array($this, 'onWorkerStop'));
        //$this->_serv->on('Receive', array($this, 'onReceive'));
        $this->_serv->on('Task', array($this, 'onTask'));
        $this->_serv->on('Finish', array($this, 'onFinish'));
        $this->_serv->on('Shutdown', array($this, 'onShutdown'));
        $this->_serv->on('Close', array($this, 'onClose'));
        $res = $this->_serv->start();
    }

    public function onRequest($request, $response)
    {
        if (isset($request->server)) {
            foreach ($request->server as $key => $value) {
                unset($_SERVER[strtoupper($key)]);
                $_SERVER[strtoupper($key)] = $value;
            }
        }
        if (isset($request->header)) {
            foreach ($request->header as $key => $value) {
                unset($_SERVER[strtoupper($key)]);
                $_SERVER[strtoupper($key)] = $value;
            }
        }
        unset($_GET);
        if (isset($request->get)) {
            foreach ($request->get as $key => $value) {
                $_GET[$key] = $value;
            }
        }
        unset($_POST);
        if (isset($request->post)) {
            foreach ($request->post as $key => $value) {
                $_POST[$key] = $value;
            }
        }
        unset($_COOKIE);
        if (isset($request->cookie)) {
            foreach ($request->cookie as $key => $value) {
                $_COOKIE[$key] = $value;
            }
        }
        unset($_FILES);
        if (isset($request->files)) {
            foreach ($request->files as $key => $value) {
                $_FILES[$key] = $value;
            }
        }
        //$_SERVER['PATH_INFO'] = $_SERVER['QUERY_STRING'];

        // var_dump($_SERVER['QUERY_STRING']);
        // var_dump($_SERVER['PATH_INFO']);
        // $_SERVER['QUERY_STRING'] = 's=/Home/Index/test2';
        //$_SERVER['PATH_INFO'] = 'Index/test2';
        // $_SERVER['REQUEST_URI'] = '/Home/Index/test2';

        /*
        $uri = explode( "?", $_SERVER['REQUEST_URI'] );
        $_SERVER["PATH_INFO"] = $uri[0];
        if( isset( $uri[1] ) ) {
        $_SERVER['QUERY_STRING'] = $uri[1];
        }*/
        $_SERVER['PATH_INFO'] = explode('/', $_SERVER['PATH_INFO'], 3)[2];
        $_SERVER['argv'][1] = $_SERVER['PATH_INFO'];
        $_GET['s'] = '/'.$_SERVER['PATH_INFO'];

        // var_dump($_SERVER); //PATH_INFO

        // var_dump($_GET);
        // var_dump($_SERVER['PATH_INFO']);

        // 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
        define('APP_DEBUG', true);
        // 定义应用目录
        define('APP_PATH', SWOOLE_PATH.DIRECTORY_SEPARATOR.'Application'.DIRECTORY_SEPARATOR);
        // 定义应用模式
        //define('APP_MODE', 'cli');

        // 引入ThinkPHP入口文件
        require_once SWOOLE_PATH.DIRECTORY_SEPARATOR.'ThinkPHP'.DIRECTORY_SEPARATOR.'ThinkPHP.php';

        ob_start();

        // 记录加载文件时间
        G('loadTime');
        // 定义应用模式
        // define('APP_MODE', 'cli');
        // define('IS_CLI', true);
        // 运行应用
        \Think\App::run();

        $result = ob_get_contents();
        ob_end_clean();
        $response->end($result);

        //$this->_serv->start();

        // $response->header("Content-Type", "text/html; charset=utf-8");
        // $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
    }

    /**
     * 设置swoole进程名称.
     *
     * @param string $name swoole进程名称
     */
    private function setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } else {
            if (function_exists('swoole_set_process_name')) {
                swoole_set_process_name($name);
            } else {
                trigger_error(__METHOD__.' failed. require cli_set_process_title or swoole_set_process_name.');
            }
        }
    }

    /**
     * Server启动在主进程的主线程回调此函数.
     *
     * @param $serv
     */
    public function onStart($serv)
    {
        if (!$this->_setting['daemonize']) {
            echo 'Date:'.date('Y-m-d H:i:s')."\t swoole_server master worker start\n";
        }
        $this->setProcessName($this->_setting['process_name'].'-master');
        //记录进程id,脚本实现自动重启
        $pid = "{$serv->master_pid}\n{$serv->manager_pid}";
        file_put_contents(SWOOLE_TASK_PID_PATH, $pid);
    }

    /**
     * worker start 加载业务脚本常驻内存.
     *
     * @param $server
     * @param $workerId
     */
    public function onWorkerStart($serv, $workerId)
    {
        if ($workerId >= $this->_setting['worker_num']) {
            $this->setProcessName($this->_setting['process_name'].'-task');
        } else {
            $this->setProcessName($this->_setting['process_name'].'-event');
        }
    }

    /**
     * 监听连接进入事件.
     *
     * @param $serv
     * @param $fd
     */
    public function onConnect($serv, $fd)
    {
        if (!$this->_setting['daemonize']) {
            echo 'onConnect Date:'.date('Y-m-d H:i:s')."\t swoole_server connect[".$fd."]\n";
        }
    }

    /**
     * worker 进程停止.
     *
     * @param $server
     * @param $workerId
     */
    public function onWorkerStop($serv, $workerId)
    {
        if (!$this->_setting['daemonize']) {
            echo 'Date:'.date('Y-m-d H:i:s')."\t swoole_server[{$serv->setting['process_name']}  worker:{$workerId} shutdown\n";
        }
    }

    /**
     * 当管理进程启动时调用.
     *
     * @param $serv
     */
    public function onManagerStart($serv)
    {
        if (!$this->_setting['daemonize']) {
            echo 'Date:'.date('Y-m-d H:i:s')."\t swoole_server manager worker start\n";
        }
        $this->setProcessName($this->_setting['process_name'].'-manager');
    }

    /**
     * 此事件在Server结束时发生
     */
    public function onShutdown($serv)
    {
        if (file_exists(SWOOLE_TASK_PID_PATH)) {
            unlink(SWOOLE_TASK_PID_PATH);
        }
        if (!$this->_setting['daemonize']) {
            echo 'Date:'.date('Y-m-d H:i:s')."\t swoole_server shutdown\n";
        }
    }

    /**
     * 监听数据发送事件.
     *
     * @param $serv
     * @param $fd
     * @param $from_id
     * @param $data
     */
    public function onReceive($serv, $fd, $from_id, $data)
    {
        $len = strlen($data);
        echo "Get Message From Client {$fd}:{$len}\n";
        var_dump($serv->exist($fd));
        exit;

        if (!$this->_setting['daemonize']) {
            echo "Get Message From Client {$fd}:{$data}\n\n";
        }
        $result = json_decode($data, true);

        switch ($result['action']) {
            case 'reload': //重启
                $serv->reload();
                break;
            case 'close': //关闭
                $serv->shutdown();
                break;
            case 'status': //状态
                $serv->send($fd, json_encode($serv->stats()));
                break;
            default:
                $serv->task($data);
                break;
        }
    }

    /**
     * 监听连接Task事件.
     *
     * @param $serv
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function onTask($serv, $task_id, $from_id, $data)
    {
        $result = json_decode($data, true);
        //用TP处理各种逻辑
        $serv->finish($data);
    }

    /**
     * 监听连接Finish事件.
     *
     * @param $serv
     * @param $task_id
     * @param $data
     */
    public function onFinish($serv, $task_id, $data)
    {
        if (!$this->_setting['daemonize']) {
            echo "This is onFinish Task {$task_id} finish\n\n";
            echo "This is onFinish Result: {$data}\n\n";
        }
    }

    /**
     * 监听连接关闭事件.
     *
     * @param $serv
     * @param $fd
     */
    public function onClose($serv, $fd)
    {
        if (!$this->_setting['daemonize']) {
            echo 'Date:'.date('Y-m-d H:i:s')."\t swoole_server close[".$fd."]\n";
        }
    }
}
