<?php
/**
 * Statamic_Auth
 * Handles user authentication within Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2012 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class statamic_auth
{
  /**
   * login
   * Attempts to log in a user
   *
   * @param string  $username  Username of the user
   * @param string  $password  Password of the user
   * @param boolean  $remember  Remember this user later?
   * @return boolean
   */
  public static function login($username, $password, $remember=false)
  {
    $u = self::get_user($username);

    if ($u && $u->correct_password($password)) {
      $app = \Slim\Slim::getInstance();
      $hash = $username.":".md5($u->get_encrypted_password().$app->config['_cookies.secret_key']);
      $expire = $app->config['_cookies.lifetime'];
      $app->setEncryptedCookie('stat_auth_cookie', $hash, $expire);

      return true;
    }

    return false;
  }

  /**
   * logout
   * Logs a user out
   *
   * @return void
   */
  public static function logout()
  {
    $app = \Slim\Slim::getInstance();
    $cookie = $app->deleteCookie('stat_auth_cookie');
  }

  /**
   * user_exists
   * Determines if a given $username exists
   *
   * @param string  $username  Username to check for existence
   * @return boolean
   */
  public static function user_exists($username)
  {
    return !(self::get_user($username) == null);
  }

  /**
   * is_logged_in
   * Checks to see if the current session is logged in
   *
   * @return mixed
   */
  public static function is_logged_in()
  {
    $user = null;

    $app = \Slim\Slim::getInstance();
    $cookie = $app->getEncryptedCookie('stat_auth_cookie');

    if ($cookie) {
      list($username, $hash) = explode(":", $cookie);
      $user = self::get_user($username);

      if ($user) {
        $hash = $username.":".md5($user->get_encrypted_password().$app->config['_cookies.secret_key']);

        if ($cookie === $hash) {
          # validated
          $expire = $app->config['_cookies.lifetime'];
          $app->setEncryptedCookie('stat_auth_cookie', $cookie, $expire);

          return $user;
        }
      }
    }

    return false;
  }

  /**
   * get_user
   * Gets complete information about a given $username
   *
   * @param string  $username  Username to look up
   * @return Statamic_User object
   */
  public static function get_user($username)
  {
    $u = Statamic_User::load($username);

    return $u;
  }

  /**
   * get_current_user
   * Gets complete information about the currently logged-in user
   *
   * @return Statamic_User object
   */
  public static function get_current_user()
  {
    $u = self::is_logged_in();

    return $u;
  }

  /**
   * get_user_list
   * Gets a full list of registered users
   *
   * @param boolean  $protected  Displaying information in a protected area?
   * @return array
   */
  public static function get_user_list($protected = true)
  {
    $users = array();
    $folder = "_config/users/*.yaml";
    $list = glob($folder);
    if ($list) {
      foreach ($list as $name) {
        $start = strrpos($name, "/")+1;
        $end = strrpos($name, ".");
        $username = substr($name, $start, $end-$start);
        if ($protected) {
          $users[$username] = self::get_user($username);
        } else {
          $users[$username] = Statamic_User::get_profile($username);
        }
      }
    }

    return $users;
  }
}
