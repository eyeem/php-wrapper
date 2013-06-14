<?php

class Eyeem_Ressource_User extends Eyeem_Ressource
{

  public static $attrs;

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
    /* Services */
    'services',
    /* Auth User */
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

  public static $parameters = array(
    'detailed',
    'includePhotos',
    'numPhotos',
    'photoDetails',
    'photoLikers',
    'photoNumLikers',
    'photoPeople',
    'photoNumPeople',
    'photoComments',
    'photoNumComments',
    'photoAlbums',
    'includeSettings'
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

  public function getThumbUrl($width = 'sq', $height = '50')
  {
    $thumbUrl = $this->getAttribute('thumbUrl');
    if ($height != '50') {
      $thumbUrl = str_replace('/thumb/sq/50/', "/thumb/sq/$height/", $thumbUrl);
    }
    if ($width != 'sq') {
      $thumbUrl = str_replace('/thumb/sq/', "/thumb/$width/", $thumbUrl);
    }
    $thumbUrl = str_replace('www.eyeem.com/thumb/', "cdn.eyeem.com/thumb/", $thumbUrl);
    return $thumbUrl;
  }

  public function getFriendsPhotos($params = array())
  {
    /* Fix defaults in API */
    $default_params = array('includeComments' => false, 'includeLikers' => false, 'includePeople' => false);
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
    $this->request($me->getEndpoint() . '/blocked/' . $this->getId(), 'PUT', array());
    return $this;
  }

  public function unblock()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->request($me->getEndpoint() . '/blocked/' . $this->getId(), 'DELETE', array());
    return $this;
  }

  public function postPhoto($params = array())
  {
    return $this->getPhotos()->post($params);
  }

  /* For Admin Users */

  public function hide()
  {
    $this->request($this->getEndpoint() . '/hide', 'POST', array('hide' => true));
    $this->setAttribute('hidden', true);
    return $this;
  }

  public function unhide()
  {
    $this->request($this->getEndpoint() . '/hide', 'POST', array('hide' => false));
    $this->setAttribute('hidden', false);
    return $this;
  }

  /* Flags */

  public function getFlags()
  {
    if ($newsSettings = $this->getAttribute('newsSettings')) {
      return $newsSettings;
    }
    $result = $this->request($this->getEndpoint() . '/flags');
    return $result['flags'];
  }

  public function setFlags($params = array())
  {
    $params = http_build_query($params);
    $result = $this->request($this->getEndpoint() . '/flags', 'POST', $params);
    $this->flush();
    return $result['flags'];
  }

  /* Social Media */

  public function getSocialMedia()
  {
    if ($services = $this->getAttribute('services', false)) {
      return array('services' => $services);
    }
    return $this->request($this->getEndpoint() . '/socialMedia');
  }

}
