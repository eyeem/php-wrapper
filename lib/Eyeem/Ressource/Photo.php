<?php

class Eyeem_Ressource_Photo extends Eyeem_Ressource
{

  public static $name = 'photo';

  public static $endpoint = '/photos/{id}';

  public static $properties = array(
    /* Basic */
    'id',
    'thumbUrl',
    'photoUrl',
    'width',
    'height',
    'updated',
    /* Detailed */
    'webUrl',
    'user',
    'title',
    'caption',
    'latitude',
    'longitude',
    'totalLikes',
    'totalComments',
    /* Admin */
    'hidden'
  );

  public static $collections = array(
    'likers' => 'user',
    'albums' => 'album',
    'comments' => 'comment'
  );

  public static $parameters = array(
    'detailed',
    'includeComments',
    'numComments',
    'includeLikers',
    'numLikers',
    'includeAlbums',
    'numAlbums',
    'userDetails'
  );

  protected $_queryParameters = array(
    'includeComments' => false,
    'includeLikers' => false,
    'includeAlbums' => false
  );

  public function getUser()
  {
    $user = parent::getUser();
    return $this->getRessourceObject('user', $user);
  }

  // Helper to get a Thumb Url

  public function getThumbUrl($width = 'h', $height = '100')
  {
    $thumbUrl = $this->thumbUrl;
    if ($height != '100') {
      $thumbUrl = str_replace('/thumb/h/100/', "/thumb/h/$height/", $thumbUrl);
    }
    if ($width != 'h') {
      $thumbUrl = str_replace('/thumb/h/', "/thumb/$width/", $thumbUrl);
    }
    if (Eyeem_Utils::getCurrentScheme() == 'https') {
      $thumbUrl = str_replace('http://', "https://", $thumbUrl);
    }
    return $thumbUrl;
  }

  public function hasLiker($user)
  {
    $user = $this->getEyeem()->getUser($user);
    return $this->getLikers()->hasMember($user);
  }

  // For Authenticated Users

  public function like()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getLikers()->add($me);
    $me->getLikedPhotos()->flushMember($this, true);
    return $this;
  }

  public function unlike()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getLikers()->remove($me);
    $me->getLikedPhotos()->flushMember($this, false);
    return $this;
  }

  public function share($params = array())
  {
    $me = $this->getEyeem()->getAuthUser();
    if ($me && $this->getUser()->getId() == $me->getId()) {
      $params['upload'] = true;
    }
    $params = http_build_query($params);
    $result = $this->request($this->getEndpoint() . '/share', 'POST', $params);
    return $this;
  }

  public function flag($offense = '')
  {
    $params = array('offense' => $offense);
    $result = $this->request($this->getEndpoint() . '/flag', 'POST', $params);
    return $this;
  }

  public function hide()
  {
    $result = $this->update(array('hide' => true));
    return $this;
  }

  public function unhide()
  {
    $result = $this->update(array('hide' => false));
    return $this;
  }

  public function postComment($params = array())
  {
    if (is_string($params)) {
      $params = array('message' => $params);
    }
    $params = http_build_query($params);
    return $this->getComments()->post($params);
  }

  public function addAlbum($album)
  {
    $album = $this->getEyeem()->getAlbum($album);
    $this->getAlbums()->add($album);
    $album->getPhotos()->flush();
    return $this;
  }

  public function removeAlbum($album)
  {
    $album = $this->getEyeem()->getAlbum($album);
    $this->getAlbums()->remove($album);
    $album->getPhotos()->flush();
    return $this;
  }

  public function delete()
  {
    $this->getUser()->getPhotos()->flush();
    foreach ($this->getAlbums() as $album) {
      $album->getPhotos()->flush();
    }
    foreach ($this->getLikers() as $liker) {
      $liker->getLikedPhotos()->flush();
    }
    return parent::delete();
  }

  public function discover($params = array())
  {
    $params = array();
    $result = $this->request($this->getEndpoint() . '/discover', 'POST', $params);
    return $result;
  }

}
