<?php

class Eyeem_Http
{

  public static $userAgent = 'Eyeem PHP Client';

  public static $timeout = 5;

  public static function get($url, $params = array())
  {
    $response = self::request($url, 'GET', $params);
    return $response['body'];
  }

  public static function request($url, $method = 'GET', $params = array())
  {
      // echo "$method:$url\n";
      if (function_exists('curl_init')) {
          $ch = curl_init();
          // Method
          if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
          } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
          }
          // Parameters
          if (!empty($params)) {
            if ($method == 'GET') {
              $url .= (strpos($url, '?') === false ? '?': '&') . http_build_query($params, null, '&');
            } else {
              curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
          }
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HEADER, false);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent . ' (curl)');
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
          $body = curl_exec($ch);
          $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);
      } else {
          throw new Exception('Curl not available.');
          /*
          $httpParams = array(
            'method' => $method,
            'user_agent' => self::$userAgent . '(php)',
            'timeout' => self::$timeout
          );
          $httpContext = stream_context_create(array('http' => $httpParams));
          $body = file_get_contents($url, false, $httpContext);
          */
      }
      return compact('code', 'body');
  }

}
