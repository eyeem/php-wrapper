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

  public function getCacheKey($ts = true, $params = array())
  {
    if ($accessToken = $this->getEyeem()->getAccessToken()) {
      $cacheKey = 'user' . '_' . $accessToken;
      if (!empty($params)) {
        $cacheKey .= '_' . http_build_query($params);
      }
      return $cacheKey;
    }
  }

  public function flushCache()
  {
    // First flush User cache
    Eyeem_Cache::delete( parent::getCacheKey() );
    // Then flush AuthUser cache
    Eyeem_Cache::delete( $this->getCacheKey() );
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

  public function update($params = array())
  {
    $response = $this->request($this->getEndpoint(), 'POST', $params);
    $this->setInfos($response['user']);
    $this->updateCache($response['user']);
    // Flush Public User cache
    // we can't just update it because it may contains private informations at this point
    Eyeem_Cache::delete( parent::getCacheKey() );
    return $this;
  }

  public function postPhoto($params = array())
  {
    $response = $this->getPhotos()->post($params);
    return $this->getRessourceObject('photo', $response['photo']);
  }

}
