<?php

class Eyeem_Ressource_App extends Eyeem_Ressource
{

  public static $attrs;

  public static $name = 'app';

  public static $endpoint = '/apps/{id}';

  public static $properties = array(
    /* Basic */
    'id',
    'name',
    'url',
    'icon',
    'redirectUrl',
    'access',
    'clientId',
    'clientSecret',
    'approved'
  );

}
