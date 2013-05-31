<?php

define('EYEEM_CLIENT_ID', '');
define('EYEEM_CLIENT_SECRET', '');

require_once __DIR__ . '/autoload.php';

session_start();

$eyeem = new Eyeem();
$eyeem->setClientId(EYEEM_CLIENT_ID);
$eyeem->setClientSecret(EYEEM_CLIENT_SECRET);

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
          <p><pre>&lt;?php echo json_encode($eyeem->getAuthUser()->getRawArray(), JSON_PRETTY_PRINT); ?></pre></p>
          <p><pre><?php echo json_encode($eyeem->getAuthUser()->getRawArray(), JSON_PRETTY_PRINT) ?></pre></p>
          <script>jQuery(function($) { $('.me').addClass('active'); })</script>

        <?php elseif ($_GET['part'] == 'photos') : ?>
          <h3>My Photos</h3>
          <p><pre>&lt;?php  echo json_encode($eyeem->getAuthUser()->getPhotos()->getItems(), JSON_PRETTY_PRINT); ?></pre></p>
          <p><pre><?php echo json_encode($eyeem->getAuthUser()->getPhotos()->getItems(), JSON_PRETTY_PRINT) ?></pre></p>
          <script>jQuery(function($) { $('.photos').addClass('active'); })</script>

        <?php elseif ($_GET['part'] == 'friends') : ?>
          <h3>My Photos</h3>
          <p><pre>&lt;?php echo json_encode($eyeem->getAuthUser()->getFriends()->getItems(), JSON_PRETTY_PRINT); ?></pre></p>
          <p><pre><?php echo json_encode($eyeem->getAuthUser()->getFriends()->getItems(), JSON_PRETTY_PRINT) ?></pre></p>
          <script>jQuery(function($) { $('.friends').addClass('active'); })</script>

        <?php elseif ($_GET['part'] == 'friendsPhotos') : ?>
          <h3>Friends Photos</h3>
          <p><pre>&lt;?php echo json_encode($eyeem->getAuthUser()->getFriendsPhotos(array('limit' => 3))->getItems(), JSON_PRETTY_PRINT); ?></pre></p>
          <p><pre><?php echo json_encode($eyeem->getAuthUser()->getFriendsPhotos(array('limit' => 3))->getItems(), JSON_PRETTY_PRINT) ?></pre></p>
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