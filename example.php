<?php

session_start();

require_once 'lib/Eyeem.php';

$eyeem = new Eyeem();
$eyeem->setClientId('CLIENT_ID');
$eyeem->setClientSecret('CLIENT_SECRET');
$eyeem->autoload();

// Cache (optional)
if (extension_loaded('memcache')) {
  $memcache = new Memcache;
  $memcache->addServer('localhost', 11211);
  Eyeem_Cache::setMemcache($memcache);
} elseif (is_writable(__DIR__ . '/tmp')) {
  Eyeem_Cache::setTmpDir(__DIR__ . '/tmp');
}

// Sign Out
if (isset($_GET['signout'])) {
  unset($_SESSION['token']);
  header('Location:' . Eyeem_Utils::getCurrentUrl(array('signout')));
  exit;
// oAuth callback
} else if (isset($_GET['code'])) {
  $_SESSION['token'] = $token = $eyeem->getToken($_GET['code']);
  $eyeem->setAccessToken($token['access_token']);
  header('Location:' . Eyeem_Utils::getCurrentUrl(array('code', 'state')));
  exit;
// Authenticated
} else if (!empty($_SESSION['token']['access_token'])) {
  $eyeem->setAccessToken($_SESSION['token']['access_token']);
}

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8"/>
<title>EyeEm Client - Example</title>
<link rel="stylesheet" href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css"/>
<link rel="stylesheet" href="http://twitter.github.com/bootstrap/assets/css/bootstrap-responsive.css"/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
</head>
<body>
  <div class="container">
    <div class="content">

      <div class="page-header">
        <h1>EyeEm Client - Example</a></h1>
      </div>

      <?php if ($accessToken = $eyeem->getAccessToken()) : ?>

        <p>Authenticated. <a href="?signout=1">Sign Out</a></p>
        <p>Access Token: <b><?php echo $accessToken ?></b></p>

        <hr/>

        <ul class="nav nav-pills">
          <li class="me"><a href="?part=me">Me</a></li>
          <li class="photos"><a href="?part=photos">Photos</a></li>
          <li class="friends"><a href="?part=friends">Friends</a></li>
          <li class="friendsPhotos"><a href="?part=friendsPhotos">Friends Photos</a></li>
        </ul>

        <?php if (empty($_GET['part']) || $_GET['part'] == 'me') : ?>
          <h3>Me</h3>
          <p><pre>&lt;?php print_r( $eyeem->getUser('me')->getInfos() ); ?></pre></p>
          <p><pre><?php print_r( $eyeem->getUser('me')->getInfos() ) ?></pre></p>
          <script>jQuery(function($) { $('.me').addClass('active'); })</script>

        <?php elseif ($_GET['part'] == 'photos') : ?>
          <h3>My Photos</h3>
          <p><pre>&lt;?php print_r( $eyeem->getUser('me')->getPhotos()->getItems() ); ?></pre></p>
          <p><pre><?php print_r( $eyeem->getUser('me')->getPhotos()->getItems() ) ?></pre></p>
          <script>jQuery(function($) { $('.photos').addClass('active'); })</script>

        <?php elseif ($_GET['part'] == 'friends') : ?>
          <h3>My Photos</h3>
          <p><pre>&lt;?php print_r( $eyeem->getUser('me')->getFriends()->getItems() ); ?></pre></p>
          <p><pre><?php print_r( $eyeem->getUser('me')->getFriends()->getItems() ) ?></pre></p>
          <script>jQuery(function($) { $('.friends').addClass('active'); })</script>

        <?php elseif ($_GET['part'] == 'friendsPhotos') : ?>
          <h3>Friends Photos</h3>
          <p><pre>&lt;?php print_r( $eyeem->getUser('me')->getFriendsPhotos()->getItems(array('limit' => 3)) ); ?></pre></p>
          <p><pre><?php print_r( $eyeem->getUser('me')->getFriendsPhotos()->getItems(array('limit' => 3)) ) ?></pre></p>
          <script>jQuery(function($) { $('.friendsPhotos').addClass('active'); })</script>

        <?php else: ?>
          <p>Unknown part.</p>
        <?php endif ?>

      <?php else : ?>
        <p>Not Authenticated. <a href="<?php echo $eyeem->getLoginUrl(); ?>">Sign In</a></p>
      <?php endif ?>

      <hr/>
    </div>
  </div>
</body>
</html>