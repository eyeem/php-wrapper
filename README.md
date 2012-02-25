Basics
======

Initialising
------------

```php
require_once 'lib/Eyeem.php';

$eyeem = new Eyeem();
$eyeem->setClientId('qWRkvLNYv5RFksuG5BcAWBIenb7SCtqY');
$eyeem->setClientSecret('8ZwWTTSIH6ygy9tvc65mY5qKa6jtDs1P');
$eyeem->autoload();
```

Ressources
==========

Photo
------

Querying an object: ```$photo = $eyeem->getPhoto('{photo_id}');```

Acceding a property: ```echo $photo->caption;``` or ```echo $photo->getCaption();```

Getting an object as array: ```$array = $photo->toArray();```

List of photo properties:
  'id', 'updated', 'thumbUrl', 'photoUrl', 'width', 'height', 'caption', 'user'

```$photo->getUser();``` return an user object.

Acceding a ressource collection: ```$comments = $photo->getComments();```

Looping over a collection: ```foreach ($comments as $comment) { echo $comment->getMessage(); }```

Or: ```foreach ($photo->getLikers() as $user) { echo $user->getFullname(); }```

List of photo collections:
  'likers' (users), 'albums', 'comments'

User
----

```$user = $eyeem->getUser('{user_id}');```

Acceding a property: ```echo $user->fullname;``` or ```echo $user->getFullname();```

List of user properties:
  'id', 'fullname', 'nickname', 'description', 'thumbUrl', 'photoUrl',
  'totalPhotos', 'totalFollowers', 'totalFriends', 'totalLikedAlbums'

List of user collections:
  'photos', 'friends' (users), 'followers' (users), 'likedAlbums', 'likedPhotos', 'friendsPhotos', 'feed' (album)

Album
------

```$album = $eyeem->getAlbum('{album_id}');```

List of album properties: 'id', 'updated', 'name', 'thumbUrl'

List of album collections:
  'photos', 'likers' (users), 'contributors' (users)

Comment
-------

List of comment properties: 'id', 'photoId', 'updated', 'message', 'user'

```$comment->getUser();``` return an user object.
