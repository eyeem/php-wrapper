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
    'admin'
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
    'linkedApps' => 'app'
  );

  public function getId()
  {
    // Only Return Integer IDs
    if (isset($this->id) && $int = (int)$this->id) {
      return $this->id;
    }
    return $this->id = $this->getAttribute('id');
  }

  public function getCacheKey($ts = true, $params = array())
  {
    if (empty($this->id)) {
      throw new Exception("Unknown id.");
    }
    $id = $this->id == 'me' ? $this->getEyeem()->getAccessToken() : $this->id;
    $updated = $this->getUpdated('U');
    $cacheKey =  static::$name . '_' . $id . ($updated ? '_' . $updated : '');
    if (!empty($params)) {
      $cacheKey .= '_' . http_build_query($params);
    }
    return $cacheKey;
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
    return $this->getFriends()->setQueryParameters(array('limit' => 100))->hasMember($user);
  }

  public function isFollowedBy($user)
  {
    $user = $this->getEyeem()->getUser($user);
    return $this->getFollowers()->setQueryParameters(array('limit' => 100))->hasMember($user);
  }

  public function ownsPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    return $photo->getUser()->getId() == $this->getId();
  }

  public function likesPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    return $this->getLikedPhotos()->setQueryParameters(array('limit' => 100))->hasMember($photo);
  }

  public function likesAlbum($album)
  {
    $album = $this->getEyeem()->getAlbum($album);
    return $this->getLikedAlbums()->setQueryParameters(array('limit' => 100))->hasMember($album);
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
    $this->getFollowers()->setQueryParameters(array('limit' => 100))->add($me);
    $me->getFriends()->setQueryParameters(array('limit' => 100))->flushMember($this, true);
    return $this;
  }

  public function unfollow()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getFollowers()->setQueryParameters(array('limit' => 100))->remove($me);
    $me->getFriends()->setQueryParameters(array('limit' => 100))->flushMember($this, false);
    return $this;
  }

  public function update($params = array())
  {
    $response = $this->request($this->getEndpoint(), 'POST', $params);
    $this->setAttributes($response['user']);
    $this->updateCache($response['user']);
    return $this;
  }

  public function postPhoto($params = array())
  {
    return $this->getPhotos()->post($params);
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
