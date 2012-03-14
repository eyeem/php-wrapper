Basics
======

Initialising
------------

```php
require_once 'lib/Eyeem.php';

$eyeem = new Eyeem();
$eyeem->setClientId('CLIENT_ID');
$eyeem->setClientSecret('CLIENT_SECRET');
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
  'id', 'thumbUrl', 'photoUrl', 'width', 'height', 'updated',
  'webUrl', 'user', 'caption', 'totalLikes', 'totalComments'

```$photo->getUser();``` return an user object.

Acceding a ressource collection: ```$comments = $photo->getComments();```

Or: ```foreach ($photo->getLikers() as $user) { echo $user->getFullname(); }```

List of photo collections:
  'likers' (users), 'albums', 'comments'

Post a comment: ```$photo->postComment('Nice Photo!');```

Like/Unlike a photo: ```$photo->like();``` and ```$photo->unlike();```

User
----

```$user = $eyeem->getUser('{user_id}');```

Acceding a property: ```echo $user->fullname;``` or ```echo $user->getFullname();```

List of user properties:
  'id', 'fullname', 'nickname', 'thumbUrl', 'photoUrl',
  'totalPhotos', 'totalFollowers', 'totalFriends', 'totalLikedAlbums', 'totalLikedPhotos',
  'webUrl', 'description'

List of user collections:
  'photos', 'friends' (users), 'followers' (users), 'likedAlbums', 'likedPhotos', 'friendsPhotos', 'feed' (album)

Example: ```foreach ($user->getFriends() as $friend) { echo $friend->getFullname(); }```

Follow/Unfollow an user: ```$user->follow();``` and ```$user->unfollow();```

Authenticated User
------------------

Extend the the user ressource.

```$authUser = $eyeem->getAuthUser();```

Update user informations: ```$authUser->update(array('fullname' => 'Santa Klaus'));```

Album
------

```$album = $eyeem->getAlbum('{album_id}');```

List of album properties:
  'id', 'name', 'thumbUrl', 'updated',
  'webUrl', 'type', 'totalPhotos', 'totalLikers', 'totalContributors'

List of album collections:
  'photos', 'likers' (users), 'contributors' (users)

Example: ```foreach ($album->getPhotos() as $photo) { echo $photo->getCaption(); }```

Subscribe/Unsubscribe to an album: ```$album->subscribe();``` and ```$album->unsubscribe();```

Add an existing photo to an album: ```$album->addPhoto($photo);``` and ```$album->addPhoto('{photo_id_}');```

Remove a photo from an album: ```$album->removePhoto($photo);``` and ```$album->removePhoto('{photo_id_}');```

Comment
-------

List of comment properties:
  'id', 'photoId', 'updated', 'message', 'user'

```$comment->getUser();``` return an user object.

Delete a comment: ```$comment->delete();```

Upload a photo
--------------

1 step:

```$photo = $eyeem->postPhoto(array('photo' => '@/home/me/a-nice-photo.jpg', 'caption' => 'A nice photo.'));```

2 steps:

```
$filename = $eyeem->uploadPhoto('/home/me/a-nice-photo.jpg');
$photo = $eyeem->postPhoto(array('filename' => $filename, 'caption' => 'A nice photo.'));
```
