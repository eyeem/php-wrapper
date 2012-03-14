<?php

class Eyeem_Comment extends Eyeem_Ressource
{

  public static $name = 'album';

  public static $endpoint = '/photos/{photoId}/comments/{id}';

  public static $properties = array(
    'id',
    'photoId',
    'message',
    'user',
    'updated',
  );

  public static $collections = array();

  public function getEndpoint()
  {
    if (empty($this->id)) {
      throw new Exception("Unknown id.");
    }
    $endpoint = static::$endpoint;
    $endpoint = str_replace('{photoId}', $this->getPhotoId(), $endpoint);
    $endpoint = str_replace('{id}', $this->id, $endpoint);
    return $endpoint;
  }

  public function getUser()
  {
    $user = parent::getUser();
    return $this->getRessourceObject('user', $user);
  }

  public function getPhoto()
  {
    return $this->getRessourceObject('photo', $this->getPhotoId());
  }

  public function delete()
  {
    parent::delete();
    $this->getPhoto()->getComments()->flush();
    return true;
  }

}
