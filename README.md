Swoole-thinkphp3.2
----------
thinkphp3.2运行与swoole下。

swoole版本要求高于1.9.

用swoole模拟php-fpm运行thinkphp，缺点是每次运行过后释放work进程，浪费系统资源，优点是没有对thinkphp本身做任何破坏性修改。

