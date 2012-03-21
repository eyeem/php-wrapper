<?php

class Eyeem_RessourceCollection extends Eyeem_Collection
{

  protected $parentRessource;

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
    parent::flush();
    $this->getParentRessource()->flushCollection($this->name);
  }

  public function getRessourceObject($ressource)
  {
    return $this->getParentRessource()->getRessourceObject($this->type, $ressource);
  }

  public function getEyeem()
  {
    return $this->getParentRessource()->getEyeem();
  }

}
