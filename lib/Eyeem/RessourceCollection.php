<?php

class Eyeem_RessourceCollection extends Eyeem_Collection
{

  public function __construct($name, $type, $parentRessource)
  {
    $this->name = $name;
    $this->type = $type;
    $this->parentRessource = $parentRessource;
  }

  public function getParentRessource()
  {
    return $this->parentRessource;
  }

  public function getEndpoint()
  {
    $parentEndpoint = $this->getParentRessource()->getEndpoint();
    return $parentEndpoint . '/' . $this->name;
  }

  public function getCacheKey($params = array())
  {
    $parent = $this->getParentRessource();
    $parentId = $parent->getId();
    if ($parentId == 'me') {
      $parentId = $this->getEyeem()->getAccessToken();
    }
    $cacheKey = $parent->getName() . '_' . $parentId . '_' . $this->name;
    if (!empty($params)) {
      $cacheKey .= '_' . http_build_query($params);
    }
    return $cacheKey;
  }

  public function getEyeem()
  {
    return $this->getParentRessource()->getEyeem();
  }

  public function request($endpoint, $method = 'GET', $params = array())
  {
    $response = $this->getParentRessource()->request($endpoint, $method, $params);
    return $response;
  }

  public function getItems($params = array())
  {
    $cacheKey = $this->getCacheKey($params);
    if (!$value = Eyeem_Cache::get($cacheKey)) {
      $endpoint = $this->getEndpoint();
      $response = $this->request($endpoint, 'GET', $params);
      if (empty($response[$this->name])) {
        throw new Exception("Missing collection in response ($this->name).");
      }
      $value = $response[$this->name];
      Eyeem_Cache::set($cacheKey, $value);
    }
    return $value['items'];
  }

  public function getRessourceObject($infos = array())
  {
    return $this->getParentRessource()->getRessourceObject($this->type, $infos);
  }

}
