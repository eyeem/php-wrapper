<?php

class Eyeem_Ressource
{

  /* Context */

  protected $eyeem = null;

  /* Static Properties */

  public static $name;

  public static $endpoint;

  public static $properties = array();

  public static $collections = array();

  /* Object Properties */

  public $id;

  public $updated;

  public function __construct($infos = array())
  {
    if (is_int($infos) || is_string($infos)) {
      $this->id = $infos;
    }
    if (is_array($infos)) {
      $this->setInfos($infos);
    }
  }

  public function setInfos($infos = array())
  {
    foreach ($infos as $key => $value) {
      if (in_array($key, static::$properties)) {
        $this->$key = $value;
      }
      if (isset(static::$collections[$key])) {
        $this->$key = $value;
      }
    }
  }

  public function getInfos()
  {
    $infos = $this->getRessource();
    $this->setInfos($infos);
    return $infos;
  }

  public function getName()
  {
    return static::$name;
  }

  public function getCacheKey($ts = true)
  {
    if (empty($this->id)) {
      throw new Exception("Unknown id.");
    }
    $updated = $this->getUpdated('U');
    return static::$name . '_' . $this->id . ($ts && $updated ? '_' . $updated : '');
  }

  public function getEndpoint()
  {
    if (empty($this->id)) {
      throw new Exception("Unknown id.");
    }
    return str_replace('{id}', $this->id, static::$endpoint);
  }

  public function getUpdated($format = null)
  {
    if ($this->updated) {
      $format = isset($format) ? $format : DateTime::ISO8601;
      $dt = new DateTime($this->updated);
      return $dt->format($format);
    }
  }

  public function getRessource()
  {
    // From Cache
    $cacheKey = $this->getCacheKey();
    if (!$cacheKey || !$value = Eyeem_Cache::get($cacheKey)) {
      // Fresh
      $name = $this->getName();
      $response = $this->request( $this->getEndpoint() );
      if (empty($response[$name])) {
        throw new Exception("Missing ressource in response ($name).");
      }
      $value = $response[$name];
      if ($cacheKey) {
        Eyeem_Cache::set($cacheKey, $value, $this->getUpdated() ? 0 : null);
      }
    }
    return $value;
  }

  public function updateCache($value)
  {
    $cacheKey = $this->getCacheKey();
    Eyeem_Cache::set($cacheKey, $value);
    $cacheKey = $this->getCacheKey(false);
    Eyeem_Cache::set($cacheKey, $value);
  }

  public function flushCache()
  {
    $cacheKey = $this->getCacheKey();
    Eyeem_Cache::delete($cacheKey);
    $cacheKey = $this->getCacheKey(false);
    Eyeem_Cache::delete($cacheKey);
  }

  public function flushCollection($name = null)
  {
    if ($name && isset(static::$collections[$name])) {
      unset($this->$name);
      $totalKey = 'total' . ucfirst($name);
      unset($this->$totalKey);
      $this->flushCache();
    }
  }

  public function getRawArray()
  {
    return $this->getRessource();
  }

  public function getRessourceObject($type, $infos = array())
  {
    return $this->getEyeem()->getRessourceObject($type, $infos);
  }

  public function getCollection($name, $parameters = array())
  {
    $collection = new Eyeem_RessourceCollection();
    // Collection name (match the name in URL: friendsPhotos, comments, likers, etc ...)
    $collection->setName($name);
    // Which kind of objects we are handling (user, album, photo, etc)
    $collection->setType(static::$collections[$name]);
    // Keep a link to the current object
    $collection->setParentRessource($this);
    // The query parameters (one of Eyeem_RessourceCollection::$parameters)
    $collection->setParameters($parameters);
    // If we have some properties already available (offset, limit, total, items)
    if (isset($this->$name)) {
      $collection->setProperties($this->$name);
    }
    // If we have the total already available
    $totalKey = 'total' . ucfirst($name);
    if (isset($this->$totalKey)) {
      $collection->setTotal($this->$totalKey);
    }
    return $collection;
  }

  public function save()
  {
    // TODO: implement saving an object
  }

  public function update($params = array())
  {
    $response = $this->request($this->getEndpoint(), 'PUT', $params);
    return $response;
  }

  public function delete()
  {
    $response = $this->request($this->getEndpoint(), 'DELETE');
    return true;
  }

  public function request($endpoint, $method = 'GET', $params = array(), $authenticated = false)
  {
    return $this->getEyeem()->request($endpoint, $method, $params, $authenticated);
  }

  public function __get($key)
  {
    if (in_array($key, static::$properties)) {
      $infos = $this->getInfos();
      return $infos[$key];
    }
    throw new Exception("Unknown property ($key).");
  }

  public function __call($name, $arguments)
  {
    // Get methods
    if (substr($name, 0, 3) == 'get') {
      $key = lcfirst(substr($name, 3));
      // Collection Objects
      if (isset(static::$collections[$key])) {
        $parameters = isset($arguments[0]) ? $arguments[0] : array();
        return $this->getCollection($key, $parameters);
      }
      // Default (read object property)
      return $this->$key;
    }
    // Set methods
    if (substr($name, 0, 3) == 'set') {
      $key = lcfirst(substr($name, 3));
      // Default (write object property)
      $this->$key = $arguments[0];
      return $this;
    }
    // Flush methods
    if (substr($name, 0, 5) == 'flush') {
      $key = lcfirst(substr($name, 5));
      // Default (write object property)
      if (isset($this->$key)) unset($this->$key);
      return $this;
    }
    throw new Exception("Unknown method ($name).");
  }

  public function toArray()
  {
    // To Fetch or Not To Fetch missing data?
    $array = array();
    foreach (static::$properties as $key) {
      $array[$key] = $this->$key;
    }
    return $array;
  }

}
