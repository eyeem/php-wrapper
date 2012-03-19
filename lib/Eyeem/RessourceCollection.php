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

  protected $_collection = null;

  protected $queryParameters = array(
    'detailed' => true,
    'offset' => 0
  );

  public function setProperties($params = array())
  {
    foreach ($params as $key => $value) {
      if (in_array($key, static::$properties)) {
        $this->$key = $value;
      }
    }
    return $this;
  }

  public function setQueryParameters($params = array())
  {
    foreach ($params as $key => $value) {
      if (in_array($key, static::$parameters)) {
        $this->queryParameters[$key] = $value;
      }
    }
    return $this;
  }

  public function getEndpoint()
  {
    $parentEndpoint = $this->getParentRessource()->getEndpoint();
    return $parentEndpoint . '/' . $this->name;
  }

  public function getCacheKey($params = array())
  {
    $parent = $this->getParentRessource();
    $cacheKey = $parent::$name . '_' . $parent->getId() . '_' . $this->name;
    if (!empty($params)) {
      $cacheKey .= '_' . http_build_query($params);
    }
    return $cacheKey;
  }

  protected function _getCollection()
  {
    // Local Cache
    if (isset($this->_collection)) {
      return $this->_collection;
    }
    // From Cache?
    $params = $this->getQueryParameters();
    unset($params['limit']);
    $cacheKey = $this->getCacheKey($params);
    if (!$value = Eyeem_Cache::get($cacheKey)) {
      // Fresh!
      $response = $this->getEyeem()->request($this->getEndpoint(), 'GET', $params);
      if (empty($response[$this->name])) {
        throw new Exception("Missing collection in response ($this->name).");
      }
      $value = $response[$this->name];
      Eyeem_Cache::set($cacheKey, $value);
    }
    return $this->_collection = $value;
  }

  public function getItems()
  {
    // Do we have the right items?
    if (isset($this->queryParameters['offset']) && $this->getOffset() !== null) {
      if ($this->queryParameters['offset'] != $this->getOffset()) {
        $this->flushAttributes();
      }
    }
    if (isset($this->queryParameters['limit']) && $this->getLimit() !== null) {
      if ($this->queryParameters['limit'] > $this->getLimit()) {
        $this->flushAttributes();
      }
    }

    // Do we have the expected sub-collections?
    foreach (array('comments', 'likers', 'photos', 'albums', 'contributors') as $cname) {
      $keyname = 'include' . ucfirst($cname);
      if (!empty($this->queryParameters[$keyname]) && !empty($this->items)) {
        $first = $this->items[0];
        if (empty($first[$cname])) {
          $this->flushAttributes();
        }
      }
    }

    if (!isset($this->items)) {
      $collection = $this->_getCollection();
      return $this->items = $collection['items'];
    }

    return $this->items;
  }
  }

  public function flushCache()
  {
    $this->_collection = null;
    $params = $this->getQueryParameters();
    $cacheKey = $this->getCacheKey($params);
    Eyeem_Cache::delete($cacheKey);
  }

  public function flushAttributes()
  {
    $this->_collection = null;
    $this->flushItems();
    $this->flushTotal();
    $this->flushOffset();
    $this->flushLimit();
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

  public function hasMember($member)
  {
    $member = $this->getRessourceObject($member);

    // Optimised version up to LIMIT total likers
    if ($this->getTotal() < $this->getLimit()) {
      foreach ($this->getItems() as $item) {
        if ($item['id'] == $member->getId()) {
          return true;
        }
      }
      return false;
    }

    // Direct Version
    $endpoint = $this->getEndpoint() . '/' . $member->getId();
    try {
      $response = $this->getEyeem()->request($endpoint, 'GET');
      // TODO: test response in case it's not an exception
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  public function getLatest()
  {
    return $this->get(0);
  }

  public function add($member)
  {
    $member = $this->getRessourceObject($member);
    $endpoint = $this->getEndpoint() . '/' . $member->getId();
    $response = $this->getEyeem()->request($endpoint, 'PUT');
    $this->flush();
    return $this;
  }

  public function remove($member)
  {
    $member = $this->getRessourceObject($member);
    $endpoint = $this->getEndpoint() . '/' . $member->getId();
    $response = $this->getEyeem()->request($endpoint, 'DELETE');
    $this->flush();
    return $this;
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

  public function __isset($key)
  {
    if (in_array($key, static::$properties)) {
      $collection = $this->_getCollection();
      return false === empty($collection[$key]);
    }
  }

  public function __get($key)
  {
    if (in_array($key, static::$properties)) {
      $collection = $this->_getCollection();
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
