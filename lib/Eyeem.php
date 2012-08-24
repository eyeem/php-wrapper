<?php

class Eyeem
{

  public $baseUrl = 'https://www.eyeem.com/api/v2';
  //public $baseUrl = 'https://apitest.eyeem.com/api/v2';
  public $authorizeUrl = 'https://www.eyeem.com/oauth/authorize';

  public $clientId = null;

  public $clientSecret = null;

  public $accessToken = null;

  protected $_authUser = null;

  protected $_ressources = array(
    'user', 'album', 'photo', 'comment', 'app'
  );

  public static function autoload()
  {
    spl_autoload_register(array('self', 'loader'));
  }

  public static function loader($className)
  {
    if (strpos($className, 'Eyeem') === 0) {
      $filename = __DIR__ . '/' . str_replace('_', '/', $className) . '.php';
      if (file_exists($filename)) {
        require_once $filename;
      }
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
      throw new Exception($array['message'], $response['code']);
    }
    return $array;
  }

  public function authenticatedRequest($endpoint, $method = 'GET', $params = array(), $authenticated = true)
  {
    return $this->request($endpoint, $method, $params, $authenticated);
  }

  public function getRessourceObject($type, $ressource = array())
  {
    // Support getUser('me')
    if ($type == 'user' && is_string($ressource) && $ressource == 'me') { return $this->getAuthUser(); }
    // Normal Behavior
    $classname = 'Eyeem_Ressource_' . ucfirst($type);
    if (is_object($ressource)) { // if ressource is already an object
      if ($ressource instanceof $classname) {
        $object = $ressource;
      } else {
        $class = get_class($ressource);
        throw new Exception("Ressource object not a $classname ($class).");
      }
    } else { // if ressource is a string or an array or whatever
      $object = new $classname($ressource);
    }
    $object->setEyeem($this);
    return $object;
  }

  // Auth

  public function getAuthUser()
  {
    if (isset($this->_authUser)) {
      return $this->_authUser;
    }
    if ($accessToken = $this->getAccessToken()) {
      return $this->_authUser = $this->getRessourceObject('authUser');
    }
    throw new Exception('User is not autenticated (no Access Token set).', 401);
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

  public function facebookLogin($fbUserId, $fbAccessToken, $fbAccessTokenExpires = null)
  {
    $response = $this->request('/auth/facebookLogin', 'POST', compact('fbUserId', 'fbAccessToken', 'fbAccessTokenExpires'));
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

  public function confirmEmail($token)
  {
    $this->request('/auth/confirmEmail', 'POST', compact('token'));
    return $this;
  }

  public function requestPassword($email)
  {
    $this->request('/auth/requestPassword', 'POST', compact('email'));
    return $this;
  }

  public function resetPassword($token, $password = null)
  {
    if (empty($password)) {
      $response = $this->request('/auth/resetPassword', 'GET', compact('token'));
      $user = $response['user'];
      return $this->getUser($user);
    }
    $this->request('/auth/resetPassword', 'POST', compact('token', 'password'));
    return $this;
  }

  public function signUp($email, $password, $nickname = null)
  {
    $this->request('/auth/signUp', 'POST', compact('email', 'password', 'nickname'));
    return $this->login($email, $password, $nickname);
  }

  // oAuth

  public function getLoginUrl($redirect_uri = null)
  {
    if (empty($redirect_uri)) {
      $redirect_uri = Eyeem_Utils::getCurrentUrl();
    }
    $params = array(
      'client_id' => $this->getClientId(),
      'response_type' => 'code',
      'redirect_uri' => $redirect_uri
    );
    $url = $this->getAuthorizeUrl() . '?' . http_build_query($params);
    return $url;
  }

  public function getToken($code, $redirect_uri = null)
  {
    if (empty($redirect_uri)) {
      $redirect_uri = Eyeem_Utils::getCurrentUrl(array('code'));
    }
    $params = array(
      'code' => $code,
      'client_id' => $this->getClientId(),
      'client_secret' => $this->getClientSecret(),
      'grant_type' => 'authorization_code',
      'redirect_uri' => $redirect_uri
    );
    $url = $this->getApiUrl('/oauth/token') . '?' . http_build_query($params);
    $response = Eyeem_Http::get($url);
    $token = json_decode($response, true);
    if (isset($token['error'])) {
      throw new Exception($token['error_description']);
    }
    return $token;
  }

  // Upload

  public function uploadPhoto($filename)
  {
    $params = array('photo' => "@$filename");
    $response = $this->request('/photos/upload', 'POST', $params);
    return $response['filename'];
  }

  public function postPhoto($params = array())
  {
    $response = $this->request('/photos', 'POST', $params);
    return $this->getRessourceObject('photo', $response['photo']);
  }

  // Search

  public function searchAlbums($query = '', $params = array())
  {
    $collection = new Eyeem_Collection();
    $collection->setType('album');
    $collection->setName('albums');
    $collection->setEyeem($this);

    /* Fix defaults in API */
    $default_params = array('includePhotos' => false);
    $params = array_merge($default_params, $params);
    $params['q'] = $query;
    $collection->setQueryParameters($params);
    $collection->setUseCache(false);

    return $collection;
  }

  public function searchUsers($query = '', $params = array())
  {
    $collection = new Eyeem_Collection();
    $collection->setType('user');
    $collection->setName('users');
    $collection->setEyeem($this);

    $params['q'] = $query;
    $collection->setQueryParameters($params);
    $collection->setUseCache(false);

    return $collection;
  }

  // Suggested / Recommended

  public function getSuggestedUsers($params = array())
  {
    $collection = new Eyeem_Collection();
    $collection->setType('user');
    $collection->setName('users');
    $collection->setEyeem($this);

    $params['suggested'] = true;

    $collection->setQueryParameters($params);

    return $collection;
  }

  public function getRecommendedAlbums($params = array())
  {
    $collection = new Eyeem_Collection();
    $collection->setType('album');
    $collection->setName('albums');
    $collection->setEndpoint('/albums/recommended');
    $collection->setEyeem($this);

    $collection->setQueryParameters($params);

    return $collection;
  }

  public function __call($name, $arguments)
  {
    // Get methods
    if (substr($name, 0, 3) == 'get') {
      $key = lcfirst(substr($name, 3));
      // Ressource Objects
      if (in_array($key, $this->_ressources)) {
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
