<?php

class Eyeem_Log
{

  public static $file = null;

  protected static $handle = null;

  public static function setFile($file)
  {
    self::$file = $file;
  }

  public static function log($message = '')
  {
    if (class_exists('sfConfig') && class_exists('sfContext'))
    {
      if (sfConfig::get('sf_logging_enabled'))
      {
        sfContext::getInstance()->getLogger()->info($message);
      }
    }

    if (isset(self::$file)) {
      global $eyeem, $eyeem_userid;
      $current_user = isset($eyeem_userid) ? $eyeem_userid : 'anonymous';
      if (empty(self::$handle)) {
        self::$handle = fopen(self::$file, "a");
      }
      fwrite(self::$handle, date("Y-m-d H:i:s") . " - " . $current_user .  " - " . $message . "\n");
    }
  }

}
