<?php

class Eyeem_Utils
{

  public static function getCurrentScheme()
  {
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) {
      return 'https';
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
      return 'https';
    }
    return 'http';
  }

  public static function getCurrentUrl($ignoreParams = array())
  {
    $protocol = self::getCurrentScheme() . '://';

    $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $parts = parse_url($currentUrl);

    $query = '';
    if (!empty($parts['query'])) {
      // ignore some params
      $params = explode('&', $parts['query']);
      $retained_params = array();
      foreach ($params as $param) {
        $explode = explode('=', $param);
        if (count($explode) == 2) {
          list($key, $value) = $explode;
          if (in_array($key, $ignoreParams)) {
            continue;
          }
        }
        $retained_params[] = $param;
      }
      if (!empty($retained_params)) {
        $query = '?'. implode('&', $retained_params);
      }
    }

    // use port if non default
    $port =
      isset($parts['port']) &&
      (($protocol === 'http://' && $parts['port'] !== 80) ||
      ($protocol === 'https://' && $parts['port'] !== 443))
      ? ':' . $parts['port'] : '';

    // rebuild
    return $protocol . $parts['host'] . $port . $parts['path'] . $query;
  }

}
