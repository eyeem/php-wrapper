<?php

class Eyeem_RessourceCollection extends Eyeem_Collection
{

  protected $parentRessource;

  public function getEndpoint()
  {
    $parentEndpoint = $this->getParentRessource()->getEndpoint();
    return $parentEndpoint . '/' . $this->name;
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
