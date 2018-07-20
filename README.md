﻿Swoole-thinkphp3.2
----------
thinkphp3.2运行于swoole下。

swoole版本要求高于1.9。

用swoole模拟php-fpm运行thinkphp，缺点是每次运行过后释放work进程，浪费系统资源，优点是没有对thinkphp本身做任何破坏性修改。

该修改主要作用为rpc调用，在基于thinkphp开发的系统重构过程中，进行rpc模块化切分重构是相对比较轻松的重构方式。

nginx反向代理配置：

<pre>
location / {
    if (!-e $request_filename){
        proxy_pass http://127.0.0.1:9501;
    }
}
</pre>

服务器命令：

<pre>
1、服务启动
	#启动服务,不指定绑定端口和ip，则使用默认配置
	php swoole.php start 
	#启动服务 指定ip 和 port
	php swoole.php -h127.0.0.1 -p9501 start
	#启动服务 守护进程模式
	php swoole.php -h127.0.0.1 -p9501 -d start
	#启动服务 非守护进程模式
	php swoole.php -h127.0.0.1 -p9501 -D start
	#启动服务 指定进程名称(显示进程名为 swooleServ-9510-[master|manager|event|task]
	php swoole.php -h127.0.0.1 -p9501 -n 9501 start


2、强制服务停止
	php swoole.php stop
	php swoole.php -p9501 stop
	php swoole.php -h127.0.0.1 -p9501 stop


3、关闭服务
	php swoole.php close
	php swoole.php -p9501 close
	php swoole.php -h127.0.0.1 -p9501 close


4、强制服务重启
	php swoole.php restart
	php swoole.php -p9501 restart
	php swoole.php -h127.0.0.1 -p9501 restart


5、平滑服务重启
	php swoole.php reload
	php swoole.php -p9501 reload
	php swoole.php -h127.0.0.1 -p9501 reload


6、服务状态
	php swoole.php status
	php swoole.php -h127.0.0.1 -p9501 status


7、swoole-task所有启动实例进程列表(一台服务器swoole-task可以有多个端口绑定的实例)
	php swoole.php list



