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

  public function get()
  {
    $name = static::$name;
    $params = array('includeComments' => false, 'includeLikers' => false, 'includeAlbums' => false);
    $response = $this->request($this->getEndpoint(), 'GET', $params);
    if (empty($response[$name])) {
      throw new Exception("Missing ressource in response ($name).");
    }
    return $response[$name];
  }

  // For Authenticated Users

  public function like()
  {
    $endpoint = $this->getEndpoint() . '/likers/me';
    $this->request($endpoint, 'PUT');
    $this->flushCollection('likers');
    return true;
  }

  public function unlike()
  {
    $endpoint = $this->getEndpoint() . '/likers/me';
    $this->request($endpoint, 'DELETE');
    $this->flushCollection('likers');
    return true;
  }

  public function postComment($params = array())
  {
    if (is_string($params)) {
      $params = array('message' => $params);
    }
    $response = $this->getComments()->post($params);
    return $this->getRessourceObject('comment', $response['comment']);
  }

}
