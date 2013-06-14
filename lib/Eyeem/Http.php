<?php

class Eyeem_Http
{

  public static $userAgent = 'Eyeem PHP Client';

  public static $timeout = 5;

  public static function get($url, $params = array())
  {
    $response = self::request(array('url' => $url, 'method' => 'GET', 'params' => $params));
    return $response['body'];
  }

  public static function request($options = array())
  {
    extract($options);

    if (function_exists('curl_init')) {
      $ch = curl_init();
      // Method
      if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
      } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      }
      // Headers
      $headers = array();
      if (isset($accessToken)) {
        $headers[] = "Authorization: Bearer $accessToken";
      } elseif (isset($clientId)) {
        $headers[] = "X-Client-Id: $clientId";
      }
      $headers[] = "X-Api-Version: 2.0.1";
      // Parameters
      if (!empty($params)) {
        switch ($method) {
          case 'GET':
            $url .= (strpos($url, '?') === false ? '?': '&') . http_build_query($params, null, '&');
            break;
          case 'PUT':
          case 'DELETE':
            $params = http_build_query($params, null, '&');
          case 'POST':
          default:
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            break;
        }
      } else {
        // Fix 411 HTTP errors
        if ($method != 'GET') {
          $headers[] = "Content-Length:0";
        }
      }
      $time_start = microtime(true);
      Eyeem_Log::log("Eyeem_Http:$method:$url start");
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent . ' (curl)');
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
      $body = curl_exec($ch);
      $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      $time_end = microtime(true);
      $time = round($time_end - $time_start, 3);
      Eyeem_Log::log("Eyeem_Http:$method:$url completed in $time");
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
