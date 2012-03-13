<?php

class Eyeem_User extends Eyeem_Ressource
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
    'description'
  );

  public static $collections = array(
    'photos' => 'photo',
    'friends' => 'user',
    'followers' => 'user',
    'likedAlbums' => 'album',
    'likedPhotos' => 'photo',
    'friendsPhotos' => 'photo',
    'feed' => 'album'
  );

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
    /* Fix wrong defaults in API */
    $default_params = array('detailed' => false, 'includeComments' => false, 'includeLikers' => false);
    $params = array_merge($default_params, $params);
    return $this->getCollection('friendsPhotos', $params);
  }

  public function isFollowing($user)
  {
    $user = $this->getEyeem()->getUser($user);
    $endpoint = $this->getEndpoint() . '/friends/' . $user->getId();
    try {
      $response = $this->getEyeem()->request($endpoint, 'GET');
    } catch (Exception $e) {
      return false;
    }
    return true;
  }

  public function isFollowedBy($user)
  {
    $user = $this->getEyeem()->getUser($user);
    $endpoint = $this->getEndpoint() . '/followers/' . $user->getId();
    try {
      $response = $this->getEyeem()->request($endpoint, 'GET');
    } catch (Exception $e) {
      return false;
    }
    return true;
  }

  // For Authenticated Users

  public function follow()
  {
    $endpoint = $this->getEndpoint() . '/followers/me';
    $response = $this->request($endpoint, 'PUT');
    return $response;
  }

  public function unfollow()
  {
    $endpoint = $this->getEndpoint() . '/followers/me';
    $response = $this->request($endpoint, 'DELETE');
    return $response;
  }

  public function update($params = array())
  {
    $response = $this->request($this->getEndpoint(), 'POST', $params);
    $this->setAttributes($response['user']);
    $this->updateCache($response['user']);
    return $this;
  }

}
