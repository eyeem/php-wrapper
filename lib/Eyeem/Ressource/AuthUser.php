<?php

class Eyeem_Ressource_AuthUser extends Eyeem_Ressource_User
{

  public static $attrs;

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

  /* Collections Status */

  public function isFollowing($user)
  {
    $user = $this->getEyeem()->getUser($user);
    $attributes = $user->getAttributes();
    if (isset($attributes['following'])) {
      // Eyeem_Log::log('Eyeem_Ressource_AuthUser:isFollowing:' . $user->getId() . ':direct');
      return $attributes['following'];
    }
    return $this->getFriends()->hasMember($user);
  }

  public function isFollowedBy($user)
  {
    $user = $this->getEyeem()->getUser($user);
    $attributes = $user->getAttributes();
    if (isset($attributes['follower'])) {
      // Eyeem_Log::log('Eyeem_Ressource_AuthUser:isFollowedBy:' . $user->getId() . ':direct');
      return $attributes['follower'];
    }
    return $this->getFollowers()->hasMember($user);
  }

  public function likesPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $attributes = $photo->getAttributes();
    if (isset($attributes['liked'])) {
      // Eyeem_Log::log('Eyeem_Ressource_AuthUser:likesPhoto:' . $photo->getId() . ':direct');
      return $attributes['liked'];
    }
    return $this->getLikedPhotos()->hasMember($photo);
  }

  public function likesAlbum($album)
  {
    $album = $this->getEyeem()->getAlbum($album);
    $attributes = $album->getAttributes();
    if (isset($attributes['favorited'])) {
      // Eyeem_Log::log('Eyeem_Ressource_AuthUser:likesAlbum:' . $album->getId() . ':direct');
      return $attributes['favorited'];
    }
    return $this->getLikedAlbums()->hasMember($album);
  }

  /* Apps */

  public function getApp($id)
  {
    $apps = $this->getCollection('apps');
    foreach ($apps as $app) {
      if ($id == $app->getId()) {
        return $app;
      }
    }
  }

  public function getApps()
  {
    return $this->getCollection('apps');
  }

  public function getLinkedApps()
  {
    return $this->getCollection('linkedApps');
  }

  public function authorizeApp($params)
  {
    $params = http_build_query($params);
    $result = $this->request($this->getCollection('linkedApps')->getEndpoint(), 'POST', $params);
    return $result;
  }

  public function deauthorizeApp($id)
  {
    $params = [];
    $result = $this->request($this->getCollection('linkedApps')->getEndpoint() . '/' . $id, 'DELETE', $params);
    return $result;
  }

  /* Social Media */

  public function socialMediaConnect($service, $params = array())
  {
    $params['connect'] = 1;
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'POST', $params);
    $this->flush();
    return $result;
  }

  public function socialMediaKeys($service, $params = array())
  {
    $params['keys'] = 1;
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'POST', $params);
    $this->flush();
    return $result;
  }

  public function socialMediaDisconnect($service)
  {
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'DELETE');
    $this->flush();
    return $result;
  }

  public function socialMediaCallback($service, $params = array())
  {
    $params['callback'] = 1;
    $params = http_build_query($params);
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'POST', $params);
    $this->flush();
    return $result;
  }

  public function socialMediaUpdate($service, $params = array())
  {
    $result = $this->request($this->getEndpoint() . '/socialMedia/' . $service, 'PUT', $params);
    $this->flush();
    return $result;
  }

  public function getSmContacts($service)
  {
    $params = array('matchContacts' => 1);
    $result = $this->request($this->getEndpoint() . '/smContacts/' . $service, 'GET', $params);
    return $result['contacts'];
  }


  /* Delete */

  public function delete()
  {
    $params = array('user_id' => 'me');
    $this->request('/auth/deleteUser', 'DELETE', $params, true);
    $this->flush();
    return true;
  }

  /* Discover */

  public function getDiscoverAlbums($params = array())
  {
    return $this->getCollection('discoverAlbums')->setQueryParameters($params);;
  }

  /* Search Friends */

  public function searchFriends($query = '', $params = array())
  {
    $params['q'] = $query;
    $result = $this->request('/users', 'GET', $params, true);
    return $result['users'];
  }

}
