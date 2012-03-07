<?php

class Eyeem_Album extends Eyeem_Ressource
{

  public static $name = 'album';

  public static $endpoint = '/albums/{id}';

  public static $properties = array(
    'id',
    'name',
    'thumbUrl',
    'updated',
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

}
