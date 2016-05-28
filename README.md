# php-cache
Library to cache things during a session and to cache things to the file system.  Exposes methods:

1. PHPCache::putObject - puts something onto the file system
1. PHPCache::putObjectInSession - puts something into session
1. PHPCache::getObject - gets something from file system
1. PHPCache::getObjectFromSession - gets something from session
1. PHPCache::clear - clears the cache
1. PHPCache::stats - returns stats on the cache
