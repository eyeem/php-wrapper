<?php

class Eyeem_AuthUser extends Eyeem_User
{

  public function getEndpoint()
  {
    if (empty($this->id)) {
      return str_replace('{id}', 'me', static::$endpoint);
    } else {
      return str_replace('{id}', $this->id, static::$endpoint);
    }
  }

  /* Special case for this endpoint because ID is not passed to construct the object */

  public function getId()
  {
    if (empty($this->id)) {
      $infos = $this->getInfos();
      $this->id = $infos['id'];
    }
    return $this->id;
  }

  public function getCacheKey($ts = true)
  {
    if ($accessToken = $this->getEyeem()->getAccessToken()) {
      return 'user' . '_' . $accessToken;
    }
  }

  public function flushCache()
  {
    $cacheKey = $this->getCacheKey();
    Eyeem_Cache::delete($cacheKey);
  }

  public function request($endpoint, $method = 'GET', $params = array(), $authenticated = false)
  {
    return parent::request($endpoint, $method, $params, true);
  }

  public function follow($user)
  {
    $user = $this->getEyeem()->getUser($user);
    $endpoint = $this->getEndpoint() . '/followings/' . $user->getId();
    $response = $this->request($endpoint, 'PUT');
    return $response;
  }

  public function unfollow($user)
  {
    $user = $this->getEyeem()->getUser($user);
    $endpoint = $this->getEndpoint() . '/followings/' . $user->getId();
    $response = $this->request($endpoint, 'DELETE');
    return $response;
  }

}
