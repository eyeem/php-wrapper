<?php

class Eyeem_Ressource_Album extends Eyeem_Ressource
{

  public static $name = 'album';

  public static $endpoint = '/albums/{id}';

  public static $properties = array(
    /* Basic */
    'id',
    'name',
    'thumbUrl',
    'updated',
    /* Detailed */
    'webUrl',
    'type',
    'totalPhotos',
    'totalLikers',
    'totalContributors'
  );

  public static $collections = array(
    'photos' => 'photo',
    'likers' => 'user',
    'contributors' => 'user'
  );

  public static $parameters = array(
    'detailed',
    'includePhotos',
    'numPhotos',
    'includeContributors',
    'numContributors',
    'includeLikers',
    'numLikers',
    'photoDetails',
    'photoLikers',
    'photoComments',
    'userDetails'
  );

  // Helper to get a Thumb Url

  public function getThumbUrl($width = 'sq', $height = '200')
  {
    $thumbUrl = $this->thumbUrl;
    if ($height != '200') {
      $thumbUrl = str_replace('/thumb/sq/200/', "/thumb/sq/$height/", $thumbUrl);
    }
    if ($width != 'sq') {
      $thumbUrl = str_replace('/thumb/sq/', "/thumb/$width/", $thumbUrl);
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
    return $this->subscribe();
  }

  public function subscribe()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getLikers()->add($me);
    $me->getLikedAlbums()->flushMember($this, true);
    return $this;
  }

  public function unlike()
  {
    return $this->unsubscribe();
  }

  public function unsubscribe()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getLikers()->remove($me);
    $me->getLikedAlbums()->flushMember($this, false);
    return $this;
  }

  public function addPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $this->getPhotos()->add($photo);
    $photo->getAlbums()->flushMember($this, true);
    return $this;
  }

  public function removePhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $this->getPhotos()->remove($photo);
    $photo->getAlbums()->flushMember($this, false);
    return $this;
  }

}
