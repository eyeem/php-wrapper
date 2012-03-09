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
    $endpoint = $this->getEndpoint() . '/likers/me';
    $this->request($endpoint, 'PUT');
    $this->flushCollection('likers');
    return true;
  }

  public function unsubscribe()
  {
    $endpoint = $this->getEndpoint() . '/likers/me';
    $this->request($endpoint, 'DELETE');
    $this->flushCollection('likers');
    return true;
  }

  public function postPhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $this->getPhotos()->post(array('photo_id' => $photo->getId()));
    $photo->flushCollection('albums');
    return $this;
  }

  public function removePhoto($photo)
  {
    $photo = $this->getEyeem()->getPhoto($photo);
    $endpoint = $this->getEndpoint() . '/photos/' . $photo->getId();
    $this->request($endpoint, 'DELETE');
    $this->flushCollection('photos');
    $photo->flushCollection('albums');
    return $this;
  }

}
