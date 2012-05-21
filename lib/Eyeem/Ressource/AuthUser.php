<?php

class Eyeem_Ressource_AuthUser extends Eyeem_Ressource_User
{

  public function getEndpoint()
  {
    if (empty($this->id)) {
      return str_replace('{id}', 'me', static::$endpoint);
    } else {
      return str_replace('{id}', $this->id, static::$endpoint);
    }
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
    $this->id = $this->getId();
    Eyeem_Cache::delete( parent::getCacheKey() );
    // Then flush AuthUser cache
    Eyeem_Cache::delete( $this->getCacheKey() );
    // Clean Local Ressource
    $this->_ressource = null;
  }

  public function request($endpoint, $method = 'GET', $params = array(), $authenticated = false)
  {
    return parent::request($endpoint, $method, $params, true);
  }

  public function update($params = array())
  {
    $response = $this->request($this->getEndpoint(), 'POST', $params);
    $this->setAttributes($response['user']);
    $this->updateCache($response['user']);
    // Flush Public User cache
    // we can't just update it because it may contains private informations at this point
    Eyeem_Cache::delete( parent::getCacheKey() );
    return $this;
  }

  /* Apps */

  public function getApps()
  {
    return $this->getCollection('apps')->setAuthenticated(true);
  }

  public function getLinkedApps()
  {
    return $this->getCollection('linkedApps')->setAuthenticated(true);
  }

  public function authorizeApp($params)
  {
    $params = http_build_query($params);
    $result = $this->request($this->getCollection('linkedApps')->getEndpoint(), 'POST', $params);
    return $result;
  }

  /* Social Media */

  public function socialMediaConnect($service, $params = array())
  {
    $params['connect'] = 1;
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'POST', $params);
    $this->flushCache();
    return $result;
  }

  public function socialMediaKeys($service, $params = array())
  {
    $params['keys'] = 1;
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'POST', $params);
    $this->flushCache();
    return $result;
  }

  public function socialMediaDisconnect($service)
  {
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'DELETE');
    $this->flushCache();
    return $result;
  }

  public function socialMediaCallback($service, $params = array())
  {
    $params['callback'] = 1;
    $params = http_build_query($params);
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'POST', $params);
    $this->flushCache();
    return $result;
  }

  public function socialMediaUpdate($service, $params = array())
  {
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'PUT', $params);
    $this->flushCache();
    return $result;
  }

}
