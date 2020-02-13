<?php

set_time_limit(0);

spl_autoload_register(function ($class_name) {
    require_once dirname(__FILE__)."/".str_replace("\\", "/", $class_name) . '.php';
});

if (isset($argv[1]) && $argv[1]=='chats') {
	include dirname(__FILE__)."/Tests/client-chats.php";
} else if (isset($argv[1]) &&$argv[1]=='messages') {
	include dirname(__FILE__)."/Tests/client-messages.php";
} else {
	echo "Available parameters:\n";
	echo "messages - test messaging functionality\n";
	echo "messages - test chat functionality\n";
}

