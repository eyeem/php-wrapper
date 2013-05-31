<?php

class Eyeem_Collection extends Eyeem_CollectionIterator
{

  /* Context */

  protected $eyeem = null;

  /* Object Properties */

  public $name;

  public $type;

  public $endpoint;

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
    'includePeople',
    'numPeople',
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
    'albumType',
    // Suggested
    'suggested'
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

  protected function _fetchCollection()
  {
    $params = $this->getQueryParameters();
    $response = $this->getEyeem()->request($this->getEndpoint(), 'GET', $params);
    if (empty($response[$this->name])) {
      throw new Exception("Missing collection in response ($this->name).");
    }
    return $response[$this->name];
  }

  protected function _getCollection()
  {
    if (isset($this->_collection)) {
      return $this->_collection;
    }
    return $this->_collection = $this->_fetchCollection();
  }

  public function getItems()
  {
    // Do we have the right items?
    // - non-matching offset
    if (isset($this->queryParameters['offset']) && $this->getOffset() !== null) {
      if ($this->queryParameters['offset'] != $this->getOffset()) {
        $this->flush();
      }
    }
    // - non-matching limit
    if (isset($this->queryParameters['limit']) && $this->getLimit() !== null) {
      if ($this->queryParameters['limit'] > $this->getLimit() && $this->getTotal() > $this->getLimit()) {
        $this->flush();
      }
    }

    // Do we have the expected sub-collections?
    foreach (array('comments', 'likers', 'photos', 'albums', 'contributors') as $cname) {
      $keyname = 'include' . ucfirst($cname);
      if (!empty($this->queryParameters[$keyname]) && !empty($this->items)) {
        $first = $this->items[0];
        if (empty($first[$cname])) {
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

  public function flush()
  {
    $this->_collection = null;
    $this->flushItems();
    $this->flushTotal();
    $this->flushOffset();
    $this->flushLimit();
  }

  public function getRessourceObject($ressource)
  {
    return $this->getEyeem()->getRessourceObject($this->type, $ressource);
  }

  public function hasMember($member)
  {
    $member = $this->getRessourceObject($member);

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
      $this->getEyeem()->request($endpoint, 'GET');
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  public function flushMember($member, $value = null)
  {
    $this->flush();
  }

  public function getLatest()
  {
    foreach ($this->getItems() as $item) {
      if ($object = $this->getRessourceObject($item)) {
        return $object;
      }
    }
  }

  public function add($member)
  {
    $member = $this->getRessourceObject($member);
    $endpoint = $this->getEndpoint() . '/' . $member->getId();
    $this->getEyeem()->request($endpoint, 'PUT');
    $this->flushMember($member, true);
    return $this;
  }

  public function remove($member)
  {
    $member = $this->getRessourceObject($member);
    $endpoint = $this->getEndpoint() . '/' . $member->getId();
    $this->getEyeem()->request($endpoint, 'DELETE');
    $this->flushMember($member, false);
    return $this;
  }

  public function post($params = array())
  {
    $response = $this->getEyeem()->request($this->getEndpoint(), 'POST', $params);
    $this->flush();
    return $this->getRessourceObject($response[$this->type]);
  }

  public function toArray()
  {
    $array = array();
    foreach ($this as $ressource) $array[] = $ressource;
    return $array;
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
    $actions = array('get', 'set', 'flush');
    list($action, $key) = isset(Eyeem_Runtime::$cc[$name]) ? Eyeem_Runtime::$cc[$name] : Eyeem_Runtime::cc($name, $actions);
    // Get methods
    if ($action == 'get') {
      // Default (read object property)
      return $this->$key;
    }
    // Set methods
    elseif ($action == 'set') {
      // Default (write object property)
      $this->$key = $arguments[0];
      return $this;
    }
    // Flush methods
    elseif ($action == 'flush') {
      // Default (flush object property)
      $this->$key = null;
      unset($this->$key);
      return $this;
    }
    throw new Exception("Unknown method ($name).");
  }

}
