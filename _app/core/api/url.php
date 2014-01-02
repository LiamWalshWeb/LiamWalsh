<?php
/**
 * URL
 * API for inspecting and manipulating URLs
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class URL
{
    /**
     * Format a given URI.
     *
     * @param  string  $uri
     * @return string
     */
    public static function format($uri)
    {
        return rtrim(self::tidy('/' . $uri), '/') ?: '/';
    }


    /**
     * Determine if the given URL is valid.
     *
     * @param  string  $url
     * @return bool
     */
    public static function isValid($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
    }


    /**
     * Get the full URL for the current request.
     *
     * @param boolean  $include_root  Should this include the site root itself?
     * @return string
     */
    public static function getCurrent($include_root = true)
    {
        $url = Request::getResourceURI();

        if ($include_root) {
            $url = Config::getSiteRoot() . '/' . $url;
        }

        return self::format($url);
    }


    /**
     * Redirect visitor to a specified URL
     *
     * @param string  $url  URL to redirect to
     * @param int  $status  Status code to use
     * @return void
     **/
    public static function redirect($url, $status = 302)
    {
        $app = \Slim\Slim::getInstance();

        $app->redirect($url, $status);
    }


    /**
     * Assembles a URL from an ordered list of segments
     *
     * @param string  Open ended number of arguments
     * @return string
     */
    public static function assemble()
    {
        $args = func_get_args();

        if (!is_array($args) || !count($args)) {
            return NULL;
        }

        return self::tidy('/' . join($args, '/'));
    }


    /**
     * Gets the value of pagination in the current URL
     *
     * @return int
     */
    public static function getCurrentPaginationPage()
    {
        return Helper::pick(Request::get(Config::getPaginationVariable()), 1);
    }


    /**
     * Pops off the last segment of a given URL
     *
     * @param string  $url  URL to pop
     * @return string
     */
    public static function popLastSegment($url)
    {
        $url_array = explode('/', $url);
        array_pop($url_array);

        return (is_array($url_array)) ? implode('/', $url_array) : $url_array;
    }

    /**
     * Removes occurrences of "//" in a $path (except when part of a protocol)
     * Alias of Path::tidy()
     *
     * @param string  $url  URL to remove "//" from
     * @return string
     */
    public static function tidy($url)
    {
        return Path::tidy($url);
    }
}
