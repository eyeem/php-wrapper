<?php

class Eyeem_Photo extends Eyeem_Ressource
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
    'caption',
    'totalLikes',
    'totalComments'
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

}
