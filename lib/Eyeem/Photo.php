<?php

class Eyeem_Photo extends Eyeem_Ressource
{

  public static $name = 'photo';

  public static $endpoint = '/photos/{id}';

  public static $properties = array(
    'id',
    'thumbUrl', 'photoUrl',
    'width', 'height',
    'updated',
    'caption',
    'user'
  );

  public static $collections = array(
    'likers' => 'user',
    'albums' => 'album',
    'comments' => 'comment'
  );

  public function getUser()
  {
    $user = parent::getUser();
    return $this->getRessourceObject('user', $user);
  }

  public function getUpdated($format = null)
  {
    $updated = parent::getUpdated();
    if ($updated) {
      $format = isset($format) ? $format : 'U';
      $dt = new DateTime($updated);
      return $dt->format($format);
    }
  }

}
