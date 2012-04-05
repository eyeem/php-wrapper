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
    'userDetails'
  );

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
    $me->getLikedAlbums()->flushMember($this);
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
    $me->getLikedAlbums()->flushMember($this);
    return $this;
  }

  public function addPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $this->getPhotos()->add($photo);
    $photo->getAlbums()->flushMember($this);
    return $this;
  }

  public function removePhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $this->getPhotos()->remove($photo);
    $photo->getAlbums()->flushMember($this);
    return $this;
  }

}
