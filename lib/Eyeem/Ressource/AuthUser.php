<?php

class Eyeem_Ressource_AuthUser extends Eyeem_Ressource_User
{

  public static $parameters = array(
    'includeSettings'
  );

  protected $_queryParameters = array(
    'includeSettings' => true
  );

  public function getEndpoint()
  {
    if (empty($this->id)) {
      return str_replace('{id}', 'me', static::$endpoint);
    } else {
      return str_replace('{id}', $this->id, static::$endpoint);
    }
  }

  public function getCacheKey($params = array())
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
    // First flush AuthUser cache
    $cache = $this->getEyeem()->getCache();
    $cache->delete( $this->getCacheKey() );
    // Then flush parent cache
    parent::flushCache();
  }

  public function request($endpoint, $method = 'GET', $params = array(), $authenticated = false)
  {
    return parent::request($endpoint, $method, $params, true);
  }

  public function update($params = array())
  {
    $cache = $this->getEyeem()->getCache();
    $response = $this->request($this->getEndpoint(), 'POST', $params);
    $this->setAttributes($response['user']);
    // Flush Public User cache
    // we can't just update it because it may contains private informations at this point
    parent::flushCache();
    return $this;
  }

  /* Apps */

  public function getApp($id)
  {
    $apps = $this->getCollection('apps')->setAuthenticated(true);
    foreach ($apps as $app) {
      if ($id == $app->getId()) {
        return $app;
      }
    }
  }

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

  public function getSmContacts($service)
  {
    $params['matchContacts'] = 1;
    $result = $this->request($this->getEndpoint() . '/smContacts/' . $service, 'GET', $params);
    return $result['contacts'];
  }

  /* Settings */

  public function getSettings()
  {
    if (!$settings = $this->getAttribute('settings')) {
      $this->flushCache();
      $settings = $this->getAttribute('settings');
    }
    if (empty($settings)) {
      $result = $this->request($this->getEndpoint() . '/settings');
      $settings = $result['settings'];
    }
    return $settings;
  }

  public function setSettings($settings = array())
  {
    $params = array('settings' => $settings);
    $params = http_build_query($params);
    $result = $this->request($this->getEndpoint() . '/settings', 'POST', $params);
    return $result['settings'];
  }

  /* News Settings */

  public function setNewsSettings($params = array())
  {
    $params = http_build_query($params);
    $result = $this->request($this->getEndpoint() . '/newsSettings', 'POST', $params);
    return $result['newsSettings'];
  }

  /* Delete */

  public function delete()
  {
    $params = array('user_id' => 'me');
    $params = http_build_query($params);
    $result = $this->request('/auth/deleteUser', 'DELETE', $params, true);
    $this->flushCache();
    return true;
  }

  /* Discover */

  public function getDiscoverAlbums()
  {
    return $this->getCollection('discoverAlbums')->setAuthenticated(true);
  }

}
