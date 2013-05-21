<?php

class Eyeem_Collection_People extends Eyeem_RessourceCollection
{

  public $idsRessourceName = 'userIds';

  public function getEndpoint()
  {
    $parentEndpoint = $this->getParentRessource()->getEndpoint();
    return $parentEndpoint . '/' . 'taggedPeople';
  }

}
