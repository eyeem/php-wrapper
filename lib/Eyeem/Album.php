<?php

class Eyeem_Album extends Eyeem_Ressource
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

  // For Authenticated Users

  public function subscribe()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getLikers()->add($me);
    $me->getLikedAlbums()->flush();
    return $this;
  }

  public function unsubscribe()
  {
    $me = $this->getEyeem()->getAuthUser();
    $this->getLikers()->remove($me);
    $me->getLikedAlbums()->flush();
    return $this;
  }

  public function addPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $this->getPhotos()->add($photo);
    $photo->getAlbums()->flush();
    return $this;
  }

  public function removePhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $this->getPhotos()->remove($photo);
    $photo->getAlbums()->flush();
    return $this;
  }

}
