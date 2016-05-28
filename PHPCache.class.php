<?php
/**
 * Cache something to file - gvoss
 */
class PHPCache
{
  const CACHE_DIR = "/tmp/php-cache",
    CACHE_FOLDER_MODE = 0600,
    CACHE_TIMEOUT = 1296000,
    CACHE_HASH_ALGO = "sha1",
    FILE_DIRECTORY_DELIMITER="/",
    FILE_METADATA_CREATED_TIME="ctime",
    DATE_FORMAT_UNIX_TIMESTAMP="U",
    CACHE_OBJECT_CREATED="c",
    CACHE_OBJECT_BLOB="d",
    SESSION_NAME = "PHPCACHE",
    OS_ENV = "LINUX";#

  /**
   * function potObject - serialize something and place it on file system
   * @param key string key to use when caching
   * @param object thing to serialize and place on file system
   */
  public static function putObject($key,$object)
  {
    if(is_null($object) || empty($object) || is_null($key) || empty($key)){return false;}

    self::buildDir();

    $objectToPut = array();
    $objectToPut[self::CACHE_OBJECT_CREATED] = date(self::DATE_FORMAT_UNIX_TIMESTAMP);
    $objectToPut[self::CACHE_OBJECT_BLOB] = serialize($object);

    $objectSerialized = serialize($objectToPut);

    $fileName = self::buildFilename($key);

    if(self::writeObject($fileName,$objectSerialized))
    {
      return true;
    }
    return false;
  }
  /**
   * function putObjectInSession - serialize something and place it in a session
   * @param key string key to use when caching
   * @param object thing to serialize and place in session
   */
  public static function putObjectInSession($key,$object)
  {
    if(is_null($object) || empty($object) || is_null($key) || empty($key)){return false;}
    if(!isset($_SESSION)){return false;}

    $objectToPut = array();
    $objectToPut[self::CACHE_OBJECT_CREATED] = date(self::DATE_FORMAT_UNIX_TIMESTAMP);
    $objectToPut[self::CACHE_OBJECT_BLOB] = serialize($object);

    $objectSerialized = serialize($objectToPut);

    $_SESSION[self::SESSION_NAME][hash(self::CACHE_HASH_ALGO,$key)] = $objectSerialized;

    return true;
  }
  /**
   * function getObject - retrieve something from cache on file system
   * @param key string key to use when retrieving something
   */
  public static function getObject($key)
  {
    if(!is_string($key))
    {
      return false;
    }
    $fileName = self::buildFilename($key);
    if(is_readable($fileName))
    {
      if($fileContents = file_get_contents($fileName))
      {
        if($fileContents = unserialize($fileContents))
        {
          if(($fileContents[self::CACHE_OBJECT_CREATED]+self::CACHE_TIMEOUT)<=date(self::DATE_FORMAT_UNIX_TIMESTAMP))
          {
            unlink($fileName);
            return false;
          }
          else
          {
            if($fileContents = unserialize($fileContents[self::CACHE_OBJECT_BLOB]))
            {
              return $fileContents;
            }
            else{return false;};
          }
        }
        else{return false;}
      }
      else{return false;}
    }
    else{return false;}
  }
  /**
   * function getObjectFromSession - retrieve something from cache in session
   * @param key string key to use when retrieving something
   */
  public static function getObjectFromSession($key)
  {
    if(!is_string($key))
    {
      return false;
    }

    if(isset($_SESSION) && isset($_SESSION[self::SESSION_NAME]) && isset($_SESSION[self::SESSION_NAME][hash(self::CACHE_HASH_ALGO,$key)]))
    {
      if($sessionContents = unserialize($_SESSION[self::SESSION_NAME][hash(self::CACHE_HASH_ALGO,$key)]))
      {
        if(($sessionContents[self::CACHE_OBJECT_CREATED]+self::CACHE_TIMEOUT)<=date(self::DATE_FORMAT_UNIX_TIMESTAMP))
        {
          unset($_SESSION[self::SESSION_NAME][hash(self::CACHE_HASH_ALGO,$key)]);
          return false;
        }
        else
        {
          if($sessionContents = unserialize($sessionContents[self::CACHE_OBJECT_BLOB]))
          {
            return $sessionContents;
          }
          else{return false;};
        }
      }
      else{return false;}
    }
    else{return false;}
  }
  /**
   * function clear - clear cache with zero prejudice
   */
  public static function clear()
  {
    try
    {
      $files = glob(self::getCacheDir()."/*"); # get all file names
      foreach($files as $file)
      {
        if(is_file($file))
        {
          unlink($file);
        }
      }
    }
    catch(Exception $e)
    {
      return false;
    }
    return true;
  }
  /**
   * function stats - return stats about cache
   */
  public static function stats()
  {
    $toReturn = array();
    $toReturn["size"] = self::dirSize(self::getCacheDir());
    return $toReturn;
  }
  /**
   * function writeObject - helper function to write something to file system
   */
  private static function writeObject($filename,&$data,$flags=LOCK_EX)
  {
    $fileWrite = file_put_contents($filename,$data,$flags);
    if($fileWrite===false){return false;}
    return true;
  }
  /**
   * function buildFilename - helper function to build a file name
   */
  private static function buildFilename($key)
  {
    return self::getCacheDir().self::FILE_DIRECTORY_DELIMITER.hash(self::CACHE_HASH_ALGO,$key);
  }
  /**
   * function buildDir - helper function to build the directory on the file system
   */
  private static function buildDir()
  {
    if(!is_dir(self::getCacheDir()))
    {
      mkdir(self::getCacheDir(),self::CACHE_FOLDER_MODE,true);
    }
  }
  /**
   * function dirSize - in the words of candyman the following 'came from the internet'
   *  non linux http://stackoverflow.com/questions/478121/php-get-directory-size
   *  linux http://forums.devshed.com/php-development-5/directory-size-php-efficiently-361124.html
   */
  private static function dirSize($path)
  {
    $dirSize = 0;
    $files = scandir($path);
    $cleanPath = rtrim($path,"/")."/";

    foreach($files as $t)
    {
      if ($t<>"." && $t<>"..")
      {
        $currentFile = $cleanPath.$t;
        if(is_dir($currentFile))
        {
          $size = foldersize($currentFile);
          $dirSize += $size;
        }
        else
        {
          $size = filesize($currentFile);
          $dirSize += $size;
        }
      }
    }
    return $dirSize;
  }
  /**
   * function getCacheDir - helper function to allow setting of cache dir as named constant
   */
  private static function getCacheDir()
  {
    if(defined("PHP_CACHE_DIR"))
    {
      return PHP_CACHE_DIR;
    }
    else
    {
      return self::CACHE_DIR;
    }
  }
}
