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
  );

  public static $collections = array(
    'mentionedUsers' => 'user',
  );

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
