<?php

class Eyeem_Log
{

  public static $file;

  protected static $handle;

  public static function setFile($file)
  {
    self::$file = $file;
  }

  public static function log($message = '')
  {
    if (isset(self::$file)) {
      if (empty(self::$handle)) {
        self::$handle = fopen(self::$file, "a");
      }
      fwrite(self::$handle, date("Y-m-d H:i:s") . " - " . $message . "\n");
    }
    if (php_sapi_name() == 'cli-server') {
      error_log($message);
    }
  }

}
