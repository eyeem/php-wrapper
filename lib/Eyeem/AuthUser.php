<?php

class Eyeem_AuthUser extends Eyeem_User
{

  public function follow($user)
  {
    $user = $this->getEyeem()->getUser($user);
    $endpoint = $this->getEndpoint() . '/followings/' . $user->getId();
    $response = $this->getEyeem()->authenticatedRequest($endpoint, 'PUT');
    return $response;
  }

  public function unfollow($user)
  {
    $user = $this->getEyeem()->getUser($user);
    $endpoint = $this->getEndpoint() . '/followings/' . $user->getId();
    $response = $this->getEyeem()->authenticatedRequest($endpoint, 'DELETE');
    return $response;
  }

}
