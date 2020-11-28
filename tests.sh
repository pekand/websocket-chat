export  XDEBUG_CONFIG=remote_enable=1 remote_mode=req remote_port=9000 remote_host=127.0.0.1 remote_connect_back=0
php tests.php limits
read -p "done"
