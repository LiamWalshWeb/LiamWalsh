<?php
/**
 * Session
 * API for interacting with the PHP session
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Session
{
    /**
     * Fetch a bit of flash data, if available; accepts "colon" notation
     *
     * @param string  $name  Name of Flash to get
     * @param mixed  $default  Default value to use if none exists
     * @return string
     */
    public static function getFlash($name, $default = NULL)
    {
        if (isset($_SESSION['slim.flash'])) {
            return array_get($_SESSION['slim.flash'], $name, $default);
        }

        return $default;
    }


    /**
     * Sets flash data to be made available at the next request
     *
     * @param string  $name  Name of Flash to set
     * @param string  $value  Value to set to Flash
     * @return void
     */
    public static function setFlash($name, $value)
    {
        $app = \Slim\Slim::getInstance();
        $app->flash($name, $value);
    }


    /**
     * Get value of unencrypted HTTP cookie
     *
     * Return the value of a cookie from the current HTTP request,
     * or return NULL if cookie does not exist. Cookies created during
     * the current request will not be available until the next request.
     *
     * @param string  $cookie  Name of cookie to retrieve
     * @return string|null
     */
    public function getCookie($cookie)
    {
        $app = \Slim\Slim::getInstance();
        return $this->app->getCookie($cookie);;
    }


    /**
     * Get value of encrypted HTTP cookie
     *
     * Return the value of an encrypted cookie from the current HTTP request,
     * or return FALSE if cookie does not exist. Encrypted cookies created during
     * the current request will not be available until the next request.
     *
     * @param string  $cookie  Name of cookie to retrieve
     * @return string|false
     */
    private function getEncryptedCookie($cookie)
    {
        $app = \Slim\Slim::getInstance();
        return $app->getEncryptedCookie($cookie);
    }

    /**
     * Sets an encrypted HTTP cookie
     * 
     * @param string  $name  Name of cookie to set
     * @param string  $value  Value of cookie to set
     * @param string  $expire  Optional length of time until the cookie expires
     * @return void
     */
    public function setCookie($name, $value, $expire = NULL)
    {
        $app = \Slim\Slim::getInstance();
        $app->setCookie($name, $value, $expire);
    }

    /**
     * Sets an encrypted HTTP cookie
     * 
     * @param string  $cookie  Name of cookie to set
     * @param string  $value  Value of cookie to set
     * @param string  $expire  Optional length of time until the cookie expires
     */
    private function setEncryptedCookie($cookie, $value, $expire = '1 day')
    {
        $app = \Slim\Slim::getInstance();
        $this->app->setEncryptedCookie($cookie, $value, $expire);
    }




   // namespacing sessions
   // -------------------------------------------------------------------------

   /**
    * Gets the value of a namespaced session variable if it exists
    *
    * @param string  $namespace  Namespace to use
    * @param string  $key  Key to retrieve
    * @param boolean $strict  Throw exception if $key does not exist?
    * @throws Exception
    * @return mixed
    */
   public static function get($namespace, $key, $strict=FALSE)
   {
      // starts up the session if it hasn't already been started
      self::startSession();

      // check that this key exists
      if (!self::isKey($namespace, $key)) {
         if ($strict) {
            throw new Exception('Cannot get session variable ' . $key . '. Key does not exist in this namespace.');
         }

         // if not strict, just return NULL
         return NULL;
      }

      return $_SESSION['_statamic']['plugins'][$namespace][$key];
   }


   /**
    * Sets the value of a namespaced session variable
    *
    * @param string  $namespace  Namespace to set
    * @param string  $key  Key to set within the namespace
    * @param mixed  $value  Value to set
    * @return void
    */
   public static function set($namespace, $key, $value)
   {
      // starts up the session if it hasn't already been started
      self::startSession();

      if (!self::isNamespace($namespace)) {
         $_SESSION['_statamic']['plugins'][$namespace] = array();
      }

      $_SESSION['_statamic']['plugins'][$namespace][$key] = $value;
   }


   /**
    * destroy
    * Destroys a namespace, unsetting all values within it
    *
    * @param string  $namespace  Namespace to destroy
    * @return void
    */
   public static function destroy($namespace)
   {
      // starts up the session if it hasn't already been started
      self::startSession();

      if (self::isNamespace($namespace)) {
         unset($_SESSION['_statamic']['plugins'][$namespace]);
      }
   }


   /**
    * Unsets a given $key from a given $namespace
    *
    * @param string  $namespace  Namespace to use
    * @param string  $key  Key to unset
    * @return void
    */
   public static function unsetKey($namespace, $key)
   {
      // starts up the session if it hasn't already been started
      self::startSession();

      if (self::isKey($namespace, $key)) {
         unset($_SESSION['_statamic']['plugins'][$namespace][$key]);
      }
   }


   /**
    * Checks if a given $namespace exists
    *
    * @param string  $namespace  Namespace to check
    * @return boolean
    */
   public static function isNamespace($namespace)
   {
      // starts up the session if it hasn't already been started
      self::startSession();

      return isset($_SESSION['_statamic']['plugins'][$namespace]);
   }


   /**
    * Checks to see if a given $key exists within a given $namespace
    *
    * @param string  $namespace  Namespace to check within
    * @param string  $key  Key to check
    * @return boolean
    */
   public static function isKey($namespace, $key)
   {
      self::startSession();

      if (!self::isNamespace($namespace)) {
         return FALSE;
      }

      return (isset($_SESSION['_statamic']['plugins'][$namespace][$key]));
   }


   /**
    * Starts up the session if it hasn't already been started, otherwise aborts
    *
    * @return void
    */
   protected static function startSession()
   {
      // enable sessions if that hasn't been done
      if (!isset($_SESSION)) {
         session_start();
      }

      // check for our namespaced variables
      if (isset($_SESSION['_statamic'])) {
         return;
      }

      // start up our namespaced session
      $_SESSION['_statamic'] = array(
         'plugins' => array()
         );
   }
}