<?php

set_time_limit(0);

define("ROOT_PATH", dirname(__FILE__));
require_once(ROOT_PATH.'/config.php');
require_once(ROOT_PATH.'/vendor/autoload.php');

if (isset($argv[1]) && $argv[1]=='chats') {
	include dirname(__FILE__)."/Tests/client-chats.php";
} else if (isset($argv[1]) &&$argv[1]=='messages') {
	include dirname(__FILE__)."/Tests/client-messages.php";
} else if (isset($argv[1]) &&$argv[1]=='limits') {
    include dirname(__FILE__)."/Tests/client-limits.php";
} else {
	echo "Available parameters:\n";
	echo "messages - test messaging functionality\n";
	echo "chats - test chat functionality\n";
    echo "limits - test chat limits\n";
}

