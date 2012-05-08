<?php

class Eyeem_RessourceIdCollection extends Eyeem_RessourceCollection
{

  protected $_ids;

  public function hasMember($member)
  {
    $member = $this->getRessourceObject($member);
    Eyeem_Log::log('Eyeem_RessourceIdCollection:' . $this->name . ':hasMember:withIds');
    return in_array($member->getId(), $this->getIds());
  }

  public function getIds()
  {
    // Local Cache
    if (isset($this->_ids)) {
      return $this->_ids;
    }
    // From Cache?
    $parent = $this->getParentRessource();
    $cacheKey = $parent::$name . '_' . $parent->getId() . '_' . $this->name . '_ids';
    if (!$ids = Eyeem_Cache::get($cacheKey)) {
      // Fresh!
      $ids = $this->_fetchIds();
      if ($cacheKey) {
        Eyeem_Cache::set($cacheKey, $ids);
      }
    }
    return $this->_ids = $ids;
  }

  protected function _fetchIds()
  {
    $params = array('onlyId' => true);
    $response = $this->getEyeem()->request($this->getEndpoint(), 'GET', $params, $this->getAuthenticated());
    if (!isset($response[$this->idsRessourceName])) {
      throw new Exception("Missing ressource in response ($this->idsRessourceName).");
    }
    return $response[$this->idsRessourceName];
  }

  public function flushAttributes()
  {
    parent::flushAttributes();
    $this->_ids = null;
  }

  public function flushCache()
  {
    parent::flushCache();
    // Clear Local Cache
    $this->_ids = null;
    // Clear Cache
    $parent = $this->getParentRessource();
    $cacheKey = $parent::$name . '_' . $parent->getId() . '_' . $this->name . '_ids';
    Eyeem_Cache::delete($cacheKey);
  }

}
