<?php

class Eyeem_Log
{

  public static function log($message = '')
  {
    if (class_exists('sfConfig') && class_exists('sfContext'))
    {
      if (sfConfig::get('sf_logging_enabled'))
      {
        sfContext::getInstance()->getLogger()->info($message);
      }
    }
  }

}
