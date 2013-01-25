<?php

class Eyeem_Ressource_User extends Eyeem_Ressource
{

  public static $name = 'user';

  public static $endpoint = '/users/{id}';

  public static $properties = array(
    /* Basic */
    'id',
    'fullname',
    'nickname',
    'thumbUrl',
    'photoUrl',
    /* Detailed */
    'totalPhotos',
    'totalFollowers',
    'totalFriends',
    'totalLikedAlbums',
    'totalLikedPhotos',
    'webUrl',
    'description',
    /* Auth User */
    'email',
    'emailNotifications',
    'pushNotifications',
    /* Admin */
    'admin',
    'hidden',
    /* Settings */
    'settings',
    'newsSettings',
    'follower',
    'following',
    'restricted',
    'blocked'
  );

  public static $collections = array(
    'photos' => 'photo',
    'friends' => 'user',
    'followers' => 'user',
    'likedAlbums' => 'album',
    'likedPhotos' => 'photo',
    'friendsPhotos' => 'photo',
    'feed' => 'album',
    'apps' => 'app',
    'linkedApps' => 'app',
    'discoverAlbums' => 'album'
  );

  public function getId()
  {
    // Only Return Integer IDs
    if (isset($this->id) && $int = (int)$this->id) {
      return $this->id;
    }
    return $this->id = $this->getAttribute('id');
  }

  // Helper to get a Thumb Url

  public function getThumbUrl()
  {
    $thumbUrl = $this->thumbUrl;
    $thumbUrl = str_replace('www.eyeem.com/thumb', "cdn.yemimg.com/thumb", $thumbUrl);
    /*
    if (Eyeem_Utils::getCurrentScheme() == 'https') {
      $thumbUrl = str_replace('http://', 'https://', $thumbUrl);
    }
    */
    return $thumbUrl;
  }

  public function getCacheKey($params = array())
  {
    if (empty($this->id)) {
      throw new Exception("Unknown id.");
    }
    $id = $this->id == 'me' ? $this->getEyeem()->getAccessToken() : $this->id;
    $cacheKey =  static::$name . '_' . $id;
    if (!empty($params)) {
      $cacheKey .= '_' . http_build_query($params);
    }
    return $cacheKey;
  }

  public function flushCache()
  {
    $cache = $this->getEyeem()->getCache();
    if ($id = $this->getId()) {
      $cache->delete("user_$id");
    }
    if ($nickname = $this->getNickname()) {
      $cache->delete("user_$nickname");
    }
    parent::flushCache();
  }

  public function getFriendsPhotos($params = array())
  {
    /* Fix defaults in API */
    $default_params = array('includeComments' => false, 'includeLikers' => false);
    $params = array_merge($default_params, $params);
    return $this->getCollection('friendsPhotos')->setQueryParameters($params);
  }

  public function isFollowing($user)
  {
    $user = $this->getEyeem()->getUser($user);
    return $this->getFriends()->hasMember($user);
  }

  public function isFollowedBy($user)
  {
    $user = $this->getEyeem()->getUser($user);
    return $this->getFollowers()->hasMember($user);
  }

  public function ownsPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    return $photo->getUser()->getId() == $this->getId();
  }

  public function likesPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    return $this->getLikedPhotos()->hasMember($photo);
  }

  public function likesAlbum($album)
  {
    $album = $this->getEyeem()->getAlbum($album);
    return $this->getLikedAlbums()->hasMember($album);
  }

  public function isAdmin()
  {
    $admin = $this->getAttribute('admin');
    return $admin == true;
  }

  // For Authenticated Users

  public function follow()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getFollowers()->add($me);
    $me->getFriends()->flushMember($this, true);
    return $this;
  }

  public function unfollow()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getFollowers()->remove($me);
    $me->getFriends()->flushMember($this, false);
    return $this;
  }

  public function block()
  {
    $me = $this->getEyeem()->getAuthUser();
    $result = $this->request($me->getEndpoint() . '/blocked/'. $this->getId(), 'PUT', array());    
    return $this;
  }
 
 
  public function unblock()
  {
    $me = $this->getEyeem()->getAuthUser();
    $result = $this->request($me->getEndpoint() . '/blocked/'. $this->getId(), 'DELETE', array()); 
    return $this;
  }
   
  public function update($params = array())
  {
    $this->setAttributes($response['user']);
    $this->updateCache($response['user']);
    return $this;
  }

  public function postPhoto($params = array())
  {
    return $this->getPhotos()->post($params);
  }

  /* For Admin Users */

  public function hide()
  {
    $result = $this->request($this->getEndpoint() . '/hide', 'POST', array('hide' => true));
    $this->flushCache();
    return $this;
  }

  public function unhide()
  {
    $result = $this->request($this->getEndpoint() . '/hide', 'POST', array('hide' => false));
    $this->flushCache();
    return $this;
  }

  /* Social Media */

  public function getSocialMedia()
  {
    $user = $this->getRawArray();
    if (isset($user['services'])) {
      return array('services' => $user['services']);
    }
    return $this->request($this->getEndpoint() . '/socialMedia');
  }

}