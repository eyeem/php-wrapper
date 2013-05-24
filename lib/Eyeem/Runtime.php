<?php

class Eyeem_Runtime
{

  /* CC (Call Cache) */

  public static $cc = array();

  public static $ccStoreRegistered = false;

  public static function cc($name, $actions = array())
  {
    // First Run
    if (empty(self::$cc)) {
      if (function_exists('apc_fetch') && $cc = apc_fetch('Eyeem_Runtime_CC')) {
        self::$cc = $cc;
      }
    }
    // Direct
    if (isset(self::$cc[$name])) {
      return self::$cc[$name];
    }
    // Iterate over actions passed as parameter
    foreach ($actions as $action) {
      // Found a match
      if (strpos($name, $action) === 0) {
        $key = substr($name, strlen($action));
        if (function_exists('lcfirst')) {
          $key = lcfirst($key);
        } else {
          $key{0} = strtolower($key{0});
        }
        // Register shutdown function
        if (!self::$ccStoreRegistered) {
          register_shutdown_function(array('Eyeem_Runtime', 'ccStore'));
          self::$ccStoreRegistered = true;
        }
        // Return and store result
        return self::$cc[$name] = array($action, $key);
      }
    }
    // Return and store empty result
    return self::$cc[$name] = array(null, null);
  }

  public static function ccStore()
  {
    if (function_exists('apc_store')) {
      apc_store('Eyeem_Runtime_CC', self::$cc);
    }
  }

}
