<?php

class Eyeem_Cache
{

  protected static $ttl = 300;

  protected static $tmpDir;

  protected static $memcache;

  public static function get($key)
  {
    // echo "Eyeem_Cache:get:$key\n";
    $key = md5($key);
    // echo "Eyeem_Cache:get:md5:$key\n";

    // Memcache
    if (self::$memcache) {
      $value = self::$memcache->get($key);
      return $value;
    }

    // Filecache
    $filecache = self::getFilecache($key);
    if (file_exists($filecache)) {
      $value = file_get_contents($filecache);
      $value = unserialize($value);
      return $value;
    }

    // APC
    if (function_exists('apc_fetch')) {
      $value = apc_fetch($key);
      return $value ? unserialize($value) : null;
    }
  }

  public static function set($key, $value, $ttl = null)
  {
    $ttl = isset($ttl) ? $ttl : self::$ttl;
    // echo "Eyeem_Cache:set:$ttl:$key\n";
    $key = md5($key);
    // echo "Eyeem_Cache:set:md5:$key:$ttl\n";

    // Memcache
    if (self::$memcache) {
      $return = self::$memcache->set($key, $value, false, $ttl);
      return $return;
    }

    // Filecache
    if ($filecache = self::getFilecache($key)) {
      $value = serialize($value);
      file_put_contents($filecache, $value);
    }

    // APC
    if (function_exists('apc_store')) {
      $value = serialize($value);
      return apc_store($key, $value, $ttl);
    }
  }

  protected static function getFilecache($key)
  {
    if (self::$tmpDir) {
      return self::$tmpDir . '/' . $key;
    }
  }

  public static function setTmpDir($tmpDir)
  {
    self::$tmpDir = $tmpDir;
  }

  public static function setMemcache($memcache)
  {
    self::$memcache = $memcache;
  }

}
