<?php
namespace Logic;

class Log
{
    private static $allowedSeverity = ['INFO', 'ERROR', 'WARNING', 'DEBUG'];
    
    public static function setAllowdSeverity($allowedSeverity) {
        self::$allowedSeverity = $allowedSeverity;
    }
    
    public static function write($message, $severity = 'INFO') {
        if (!in_array($severity, self::$allowedSeverity)) {
             return;
        }
        
        file_put_contents("storage/log/server-".date("Y-m-d").".log", date("Y-m-d H:i:s")." ".$severity." ".$message."\n", FILE_APPEND | LOCK_EX);
    }
}
