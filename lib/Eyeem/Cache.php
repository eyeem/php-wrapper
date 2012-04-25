<?php

class Eyeem_Cache
{

  protected static $ttl = 300;

  protected static $tmpDir;

  protected static $memcache;

  protected static $apc;

  public static function get($key)
  {
    Eyeem_Log::log("Eyeem_Cache:get:$key");
    $key = md5($key);
    // Eyeem_Log::log("Eyeem_Cache:get:md5:$key");

    // Memcache
    if (self::$memcache) {
      $value = self::$memcache->get($key);
      if (isset($value) && $value !== '' && $value !== false) {
        return $value;
      }
      return null;
    }

    // Filecache
    $filecache = self::getFilecache($key);
    if (file_exists($filecache)) {
      $value = file_get_contents($filecache);
      $value = unserialize($value);
      return $value;
    }

    // APC
    if (self::$apc && function_exists('apc_fetch')) {
      $value = apc_fetch($key);
      return $value ? unserialize($value) : null;
    }
  }

  public static function set($key, $value, $ttl = null)
  {
    $ttl = isset($ttl) ? $ttl : self::$ttl;

    Eyeem_Log::log("Eyeem_Cache:set:$key:$ttl");
    $key = md5($key);
    // Eyeem_Log::log("Eyeem_Cache:set:md5:$key");

    // Memcache
    if (self::$memcache) {
      return self::$memcache->set($key, $value, false, $ttl);
    }

    // Filecache
    if ($filecache = self::getFilecache($key)) {
      $value = serialize($value);
      file_put_contents($filecache, $value);
    }

    // APC
    if (self::$apc && function_exists('apc_store')) {
      $value = serialize($value);
      apc_delete($key);
      $result = apc_store($key, $value, $ttl);
      return $result;
    }
  }

  public static function delete($key)
  {
    Eyeem_Log::log("Eyeem_Cache:delete:$key");
    $key = md5($key);
    // Eyeem_Log::log("Eyeem_Cache:delete:md5:$key");

    // Memcache
    if (self::$memcache) {
      return self::$memcache->delete($key, 0);
    }

    // Filecache
    $filecache = self::getFilecache($key);
    if (file_exists($filecache)) {
      return unlink($filecache);
    }

    // APC
    if (self::$apc && function_exists('apc_delete')) {
      return apc_delete($key);
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

  public static function setApc($apc = true)
  {
    self::$apc = $apc;
  }

}
