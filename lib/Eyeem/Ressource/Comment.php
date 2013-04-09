<?php

class Eyeem_Ressource_Comment extends Eyeem_Ressource
{

  public static $name = 'comment';

  public static $endpoint = '/comments/{id}';

  public static $properties = array(
    'id',
    'photoId',
    'message',
    'user',
    'updated',
    'extendedMessage',
    'mentionedUsers'
  );

  public static $collections = array(
  );

  public function getUser()
  {
    $user = $this->getAttribute('user');
    return $this->getRessourceObject('user', $user);
  }

  public function getPhoto()
  {
    return $this->getRessourceObject('photo', $this->getPhotoId());
  }

  public function delete()
  {
    $this->getPhoto()->getComments()->flush();
    return parent::delete();
  }

}
