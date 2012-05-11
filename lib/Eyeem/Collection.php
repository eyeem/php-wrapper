<?php

class Eyeem_Collection extends Eyeem_CollectionIterator
{

  /* Context */

  protected $eyeem = null;

  /* Object Properties */

  public $name;

  public $type;

  public $endpoint;

  public $authenticated = false;

  public $useCache = true;

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
    // Sub-Collections
    'includeComments',
    'numComments',
    'includeLikers',
    'numLikers',
    'includePhotos',
    'numPhotos',
    'includeAlbums',
    'numAlbums',
    'includeContributors',
    'numContributors',
    // Details
    'photoDetails',
    'userDetails',
    // Search
    'q',
    'minPhotos',
    'albumType'
  );

  protected $_collection = null;

  protected $queryParameters = array(
    'detailed' => true,
    'limit' => 30,
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
    if (empty($this->endpoint)) {
      return '/' . $this->name;
    }
    return $this->endpoint;
  }

  public function getCacheKey($params = array())
  {
    // No cache for offset results
    if (isset($params['offset']) && $params['offset'] > 0) {
      return false;
    }
    $cacheKey = $this->name;
    return $cacheKey;
  }

  protected function _fetchCollection()
  {
    $params = $this->getQueryParameters();
    $response = $this->getEyeem()->request($this->getEndpoint(), 'GET', $params, $this->getAuthenticated());
    if (empty($response[$this->name])) {
      throw new Exception("Missing collection in response ($this->name).");
    }
    return $response[$this->name];
  }

  protected function _getCollection()
  {
    // Local Cache
    if (isset($this->_collection)) {
      return $this->_collection;
    }
    // From Cache?
    $params = $this->getQueryParameters();
    $useCache = $this->getUseCache();
    $cacheKey = $this->getCacheKey($params);
    if (!$useCache || !$cacheKey || !$value = Eyeem_Cache::get($cacheKey)) {
      // Fresh!
      $value = $this->_fetchCollection();
      if ($useCache && $cacheKey) {
        Eyeem_Cache::set($cacheKey, $value);
      }
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
      if ($this->queryParameters['limit'] > $this->getLimit() && $this->getTotal() > $this->getLimit()) {
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
          // We better flush the cache and not only attributes
          $this->flush();
        }
      }
    }

    if (!isset($this->items)) {
      $collection = $this->_getCollection();
      return $this->items = $collection['items'];
    }

    return $this->items;
  }

  public function flushCache()
  {
    $this->_collection = null;
    $params = $this->getQueryParameters();
    $cacheKey = $this->getCacheKey($params);
    if ($cacheKey) {
      Eyeem_Cache::delete($cacheKey);
    }
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
  }

  public function getRessourceObject($ressource)
  {
    return $this->getEyeem()->getRessourceObject($this->type, $ressource);
  }

  public function hasMember($member)
  {
    $member = $this->getRessourceObject($member);

    // From Cache
    $cacheKey = $this->getCacheKey() .  '_' . $member->getId();
    if ($cacheKey) {
      $value = Eyeem_Cache::get($cacheKey);
      if ($value !== null && $value != '') {
        return $value === 1 ? true : false;
      }
    }

    // Optimised version up to LIMIT total likers
    if ($this->getTotal() <= $this->getLimit()) {
      // Eyeem_Log::log('Eyeem_Collection:' . $this->name . ':hasMember:optimised');
      foreach ($this->getItems() as $item) {
        if ($item['id'] == $member->getId()) {
          return true;
        }
      }
      return false;
    }

    // Trace
    Eyeem_Log::log('Eyeem_Collection:' . $this->name . ':hasMember:total:' . $this->getTotal() . ':limit:' . $this->getLimit());

    // Direct Version
    $endpoint = $this->getEndpoint() . '/' . $member->getId();
    Eyeem_Log::log('Eyeem_Collection:' . $this->name . ':hasMember:direct');
    try {
      $response = $this->getEyeem()->request($endpoint, 'GET');
      $value = true;
    } catch (Exception $e) {
      $value = false;
    }

    // Set Cache
    if ($cacheKey) {
      Eyeem_Cache::set($cacheKey, ($value === true ? 1 : 0));
    }

    return $value;
  }

  public function flushMember($member, $value = null)
  {
    $this->flush();
    $cacheKey = $this->getCacheKey() .  '_' . $member->getId();
    if ($cacheKey) {
      if ($value === null) {
        Eyeem_Cache::delete($cacheKey);
      } else {
        Eyeem_Cache::set($cacheKey, ($value === true ? 1 : 0));
      }
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
    $this->flushMember($member, true);
    return $this;
  }

  public function remove($member)
  {
    $member = $this->getRessourceObject($member);
    $endpoint = $this->getEndpoint() . '/' . $member->getId();
    $response = $this->getEyeem()->request($endpoint, 'DELETE');
    $this->flushMember($member, false);
    return $this;
  }

  public function post($params = array())
  {
    $response = $this->getEyeem()->request($this->getEndpoint(), 'POST', $params);
    $this->flush();
    return $this->getRessourceObject($response[$this->type]);
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
      // Default (flush object property)
      $this->$key = null;
      unset($this->$key);
      return $this;
    }
    throw new Exception("Unknown method ($name).");
  }

}
