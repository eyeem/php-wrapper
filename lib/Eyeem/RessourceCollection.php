<?php

class Eyeem_RessourceCollection extends Eyeem_Collection
{

  public $name;

  public $type;

  protected $parentRessource;

  public static $properties = array(
    'offset',
    'limit',
    'total',
    'items'
  );

  public static $parameters = array(
    'offset',
    'limit',
    'detailed',
    'includeComments',
    'numComments',
    'includeLikers',
    'numLikers',
    'includePhotos',
    'numPhotos',
    'includeAlbums',
    'numAlbums',
    'includeContributors',
    'numContributors'
  );

  public function setProperties($params = array())
  {
    foreach ($params as $key => $value) {
      if (in_array($key, static::$properties)) {
        $this->$key = $value;
      }
    }
  }

  public function setParameters($params = array())
  {
    foreach ($params as $key => $value) {
      if (in_array($key, static::$parameters)) {
        $this->$key = $value;
      }
    }
  }

  public function getEndpoint()
  {
    $parentEndpoint = $this->getParentRessource()->getEndpoint();
    return $parentEndpoint . '/' . $this->name;
  }

  public function getCacheKey($params = array())
  {
    $parent = $this->getParentRessource();
    $cacheKey = $parent->getName() . '_' . $parent->getId() . '_' . $this->name;
    if (!empty($params)) {
      $cacheKey .= '_' . http_build_query($params);
    }
    return $cacheKey;
  }

  public function getParams()
  {
    $params = array();
    foreach (static::$parameters as $key) {
      if (isset($this->$key)) {
        $params[$key] = $this->$key;
      }
    }
    return $params;
  }

  public function getCollection()
  {
    $params = $this->getParams();
    $cacheKey = $this->getCacheKey($params);
    if (!$value = Eyeem_Cache::get($cacheKey)) {
      $response = $this->getEyeem()->request($this->getEndpoint(), 'GET', $params);
      if (empty($response[$this->name])) {
        throw new Exception("Missing collection in response ($this->name).");
      }
      $value = $response[$this->name];
      Eyeem_Cache::set($cacheKey, $value);
    }
    return $value;
  }

  public function flushCache()
  {
    $params = $this->getParams();
    $cacheKey = $this->getCacheKey($params);
    Eyeem_Cache::delete($cacheKey);
  }

  public function flush()
  {
    $this->flushCache();
    $this->flushItems();
    $this->flushTotal();
    $this->getParentRessource()->flushCollection($this->name);
  }

  public function getRessourceObject($ressource)
  {
    return $this->getParentRessource()->getRessourceObject($this->type, $ressource);
  }

  public function post($params = array())
  {
    $response = $this->getEyeem()->request($this->getEndpoint(), 'POST', $params);
    $this->flush();
    return $response;
  }

  public function getEyeem()
  {
    return $this->getParentRessource()->getEyeem();
  }

  public function __get($key)
  {
    if (in_array($key, static::$properties)) {
      $collection = $this->getCollection();
      return $collection[$key];
    }
    throw new Exception("Unknown property ($key).");
  }

  public function __call($name, $arguments)
  {
    // Get methods
    if (substr($name, 0, 3) == 'get') {
      $key = lcfirst(substr($name, 3));
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
      unset($this->$key);
      return $this;
    }
    throw new Exception("Unknown method ($name).");
  }

}
