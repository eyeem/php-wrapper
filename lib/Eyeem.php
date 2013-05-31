<?php

class Eyeem
{

  public $baseUrl = 'https://www.eyeem.com/api/v2';

  public $authorizeUrl = 'https://www.eyeem.com/oauth/authorize';

  public $clientId;

  public $clientSecret;

  public $accessToken;

  protected $_authUser;

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
        return true;
      }
    }
  }

  public function getApiUrl($endpoint)
  {
    $url = $this->baseUrl . $endpoint;
    return $url;
  }

  public function request($endpoint, $method = 'GET', $params = array())
  {
    $request = array(
      'url'         => $this->getApiUrl($endpoint),
      'method'      => $method,
      'params'      => $params,
      'clientId'    => $this->getClientId(),
      'accessToken' => $this->getAccessToken()
    );
    $response = Eyeem_Http::request($request);
    $array = json_decode($response['body'], true);
    if ($response['code'] >= 400) {
      throw new Exception($array['message'], $response['code']);
    }
    return $array;
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

  public function getAuthUser($params = array())
  {
    if (isset($this->_authUser)) {
      return $this->_authUser;
    }
    if ($this->getAccessToken()) {
      return $this->_authUser = $this->getRessourceObject('authUser', $params);
    }
    throw new Exception('User is not autenticated (no Access Token set).', 401);
  }

  public function login($email, $password)
  {
    $response = $this->request('/auth/login', 'POST', compact('email', 'password'));
    // Set Auth User
    $this->_authUser = $this->getRessourceObject('authUser', $response['user']);
    // Update Access Token
    $this->setAccessToken($response['access_token']);
    // Return Eyeem (not response)
    return $this;
  }

  public function facebookLogin($fbUserId, $fbAccessToken, $fbAccessTokenExpires = null)
  {
    $response = $this->request('/auth/facebookLogin', 'POST', compact('fbUserId', 'fbAccessToken', 'fbAccessTokenExpires'));
    // Set Auth User
    $this->_authUser = $this->getRessourceObject('authUser', $response['user']);
    // Update Access Token
    $this->setAccessToken($response['access_token']);
    // Return response (not EyeEm)
    return $response;
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

  public function signUp($email, $password, $nickname = null, $fullname = null)
  {
    $this->request('/auth/signUp', 'POST', compact('email', 'password', 'nickname', 'fullname'));
    return $this->login($email, $password);
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

  // Check Nickname & Email

  public function checkNickname($nickname)
  {
    return $this->request('/auth/checkNickname', 'GET', array('nickname' => $nickname));
  }

  public function checkEmail($email)
  {
    return $this->request('/auth/checkEmail', 'POST', array('email' => $email));
  }

  // Get Author from fb_action

  public function getFbAuthor($action_id)
  {
    $params = array('action_id' => $action_id);
    $response = $this->request('/users', 'GET', $params);
    return $this->getRessourceObject('user', $response['user']);
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

  // Suggested / Recommended / Popular

  public function getSuggestedUsers($params = array())
  {
    $params['suggested'] = true;

    $collection = new Eyeem_Collection();
    $collection
      ->setType('user')
      ->setName('users')
      ->setEndpoint('/users')
      ->setEyeem($this)
      ->setQueryParameters($params);

    return $collection;
  }

  public function getRecommendedAlbums($params = array())
  {
    $collection = new Eyeem_Collection();
    $collection
      ->setType('album')
      ->setName('albums')
      ->setEndpoint('/albums/recommended')
      ->setEyeem($this)
      ->setQueryParameters($params);

    return $collection;
  }

  public function getPopularPhotos($params = array())
  {
    $collection = new Eyeem_Collection();
    $collection
      ->setType('photo')
      ->setName('photos')
      ->setEndpoint('/photos/popular')
      ->setEyeem($this)
      ->setQueryParameters($params);

    return $collection;
  }

  public function __call($name, $arguments)
  {
    $actions = array('get', 'set');
    list($action, $key) = isset(Eyeem_Runtime::$cc[$name]) ? Eyeem_Runtime::$cc[$name] : Eyeem_Runtime::cc($name, $actions);
    // Get methods
    if ($action == 'get') {
      // Ressource Objects
      if (in_array($key, $this->_ressources)) {
        return $this->getRessourceObject($key, $arguments[0]);
      }
      // Default (read object property)
      return $this->$key;
    }
    // Set methods
    elseif ($action == 'set') {
      // Default (write object property)
      $this->$key = $arguments[0];
      return $this;
    }
    throw new Exception("Unknown method ($name).");
  }

}
