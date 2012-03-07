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
    return $url;
  }

  public function request($endpoint, $method = 'GET', $params = array(), $authenticated = false)
  {
    $request = array(
      'url'         => $this->getApiUrl($endpoint),
      'method'      => $method,
      'params'      => $params,
      'clientId'    => $this->getClientId(),
      'accessToken' => $authenticated || $method != 'GET' ? $this->getAccessToken() : null
    );
    $response = Eyeem_Http::request($request);
    $array = json_decode($response['body'], true);
    if ($response['code'] >= 400) {
      throw new Eyeem_Exception($array['message'], $response['code']);
    }
    return $array;
  }

  public function authenticatedRequest($endpoint, $method = 'GET', $params = array(), $authenticated = true)
  {
    return $this->request($endpoint, $method, $params, $authenticated);
  }

  public function getRessourceObject($ressourceName, $ressource = array())
  {
    // Support getUser('me')
    if ($ressourceName == 'user' && is_string($ressource) && $ressource == 'me') { return $this->getAuthUser(); }
    // Normal Behavior
    $classname = 'Eyeem_' . ucfirst($ressourceName);
    // If ressource is already an object
    if (is_object($ressource)) {
      if ($ressource instanceof $classname) {
        $object = $ressource;
      } else {
        throw new Exception("Ressource object not a $classname.");
      }
    // If ressource is a string or an array or whatever
    } else {
      $object = new $classname($ressource);
    }
    $object->setEyeem($this);
    return $object;
  }

  // Auth

  public function getAuthUser()
  {
    if ($accessToken = $this->getAccessToken()) {
      $cacheKey = 'user' . '_' . $accessToken;
      if (!$user = Eyeem_Cache::get($cacheKey)) {
        $response = $this->authenticatedRequest('/users/me');
        $user = $response['user'];
        Eyeem_Cache::set($cacheKey, $user);
      }
      return $this->getRessourceObject('authUser', $user);
    }
    // Return Exception or NULL?
    throw new Eyeem_Exception('User is not autenticated.', 401);
  }

  public function login($email, $password)
  {
    $response = $this->request('/auth/login', 'POST', compact('email', 'password'));
    $user = $response['user'];
    $accessToken = $response['access_token'];
    // Update Access Token
    $this->setAccessToken($accessToken);
    // Update User Cache
    $cacheKey = 'user' . '_' . $accessToken;
    Eyeem_Cache::set($cacheKey, $user);
    // Return Eyeem for chainability
    return $this;
  }

  // oAuth

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
      $this->$key = $arguments[0];
      return $this;
    }
    throw new Exception("Unknown method ($name).");
  }

}
