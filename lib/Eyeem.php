<?php

class Eyeem
{

  public $baseUrl = 'https://www.eyeem.com/api/v2';

  public $clientId = null;

  public $clientSecret = null;

  public $accessToken = null;

  protected $_ressources = array(
    'user', 'album', 'photo', 'comment',
  );

  public static function autoload()
  {
    spl_autoload_register(array('self', 'loader'));
  }

  public static function loader($className)
  {
    if (strpos($className, 'Eyeem') === 0) {
      $filename = str_replace('_', '/', $className);
      require_once __DIR__ . '/' . $filename . '.php';
    }
  }

  public function getApiUrl($endpoint)
  {
    $url = $this->baseUrl . $endpoint;
    if ($this->accessToken) {
      $url .= '?access_token=' . $this->accessToken;
    } elseif ($this->clientId) {
      $url .= '?client_id=' . $this->clientId;
    }
    return $url;
  }

  public function request($endpoint, $method = 'GET', $params = array())
  {
    $url = $this->getApiUrl($endpoint);
    $body = Eyeem_Http::request($url, $method, $params);
    $response = json_decode($body, true);
    return $response;
  }

  public function getRessourceObject($ressourceName, $infos = array())
  {
    $classname = 'Eyeem_' . ucfirst($ressourceName);
    $object = new $classname($infos);
    $object->setEyeem($this);
    return $object;
  }

  public function getLoginUrl()
  {
    $clientId = $this->getClientId();
    return Eyeem_OAuth2::getLoginUrl($clientId);
  }

  public function getToken($code)
  {
    $clientId = $this->getClientId();
    $clientSecret = $this->getClientSecret();
    return Eyeem_OAuth2::getAccessToken($code, $clientId, $clientSecret);
  }

  public function __call($name, $arguments)
  {
    // Get methods
    if (substr($name, 0, 3) == 'get') {
      $key = lcfirst(substr($name, 3));
      // Ressource Objects
      if (in_array($key, $this->_ressources)) {
        // TODO: change syntax to allow more than one argument
        return $this->getRessourceObject($key, $arguments[0]);
      }
      // Default (read object property)
      return $this->$key;
    }
    // Set methods
    if (substr($name, 0, 3) == 'set') {
      $key = lcfirst(substr($name, 3));
      // Default (write object property)
      return $this->$key = $arguments[0];
    }
    throw new Exception("Unknown method ($name).");
  }

}
