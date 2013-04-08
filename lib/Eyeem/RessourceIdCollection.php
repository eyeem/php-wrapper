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
    if (isset($this->_ids)) {
      return $this->_ids;
    }
    return $this->_ids = $this->_fetchIds();
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

  public function flush()
  {
    parent::flush();
    $this->_ids = null;
  }

}
