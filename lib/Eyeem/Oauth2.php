<?php

class Eyeem_OAuth2
{

  public static $authorizeUrl = 'http://www.eyeem.com/oauth/authorize';

  public static $accessTokenUrl = 'https://www.eyeem.com/api/v2/oauth/token';

  public static function getLoginUrl($client_id, $redirect_uri = null)
  {
    if (empty($redirect_uri)) {
      $redirect_uri = Eyeem_Utils::getCurrentUrl();
    }
    $params = array(
      'client_id' => $client_id,
      'response_type' => 'code',
      'redirect_uri' => $redirect_uri
    );
    $url = self::$authorizeUrl . '?' . http_build_query($params);
    return $url;
  }

  public static function getAccessToken($code, $client_id, $client_secret, $redirect_uri = null)
  {
    if (empty($redirect_uri)) {
      $redirect_uri = Eyeem_Utils::getCurrentUrl();
    }
    $params = array(
      'code' => $code,
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'grant_type' => 'authorization_code',
      'redirect_uri' => $redirect_uri
    );
    $url = self::$accessTokenUrl . '?' . http_build_query($params);
    $response = Eyeem_Http::get($url);
    $token = json_decode($response, true);
    if (isset($token['error'])) {
      throw new Exception($token['error_description']);
    }
    return $token;
  }

}
