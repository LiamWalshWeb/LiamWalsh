<?php
/**
 * Path
 * API for manipulating and working with paths
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Path
{
    /**
     * Finds a given path on the server, adding in any ordering elements missing
     *
     * @param string  $path  Path to resolve
     * @return string
     */
    public static function resolve($path)
    {
        $content_root = Config::getContentRoot();
        $content_type = Config::getContentType();

        if (strpos($path, "/") === 0) {
            $parts = explode("/", substr($path, 1));
        } else {
            $parts = explode("/", $path);
        }

        $fixedpath = "/";
        foreach ($parts as $part) {
            if (! File::exists(URL::assemble($content_root,$path . '.' . $content_type))
                && ! is_dir(URL::assemble($content_root, $part))) {

                // check folders
                $list = Statamic::get_content_tree($fixedpath, 1, 1, FALSE, TRUE, FALSE);
                foreach ($list as $item) {
                    $t = basename($item['slug']);
                    if (Slug::isNumeric($t)) {
                        $nl = strlen(Slug::getOrderNumber($t)) + 1;
                        if (strlen($part) >= (strlen($item['slug']) - $nl) && Pattern::endsWith($item['slug'], $part)) {
                            $part = $item['slug'];
                            break;
                        }
                    } else {
                        if (Pattern::endsWith($item['slug'], $part)) {
                            if (strlen($part) >= strlen($t)) {
                                $part = $item['slug'];
                                break;
                            }
                        }
                    }
                }

                // check files

                $list = Statamic::get_file_list($fixedpath);

                foreach ($list as $key => $item) {
                    if (Pattern::endsWith($key, $part)) {
                        $t = basename($item);

                        $offset = 0;
                        if (Pattern::startsWith($key, '__')) {
                            $offset = 2;
                        } elseif (Pattern::startsWith($key, '_')) {
                            $offset = 1;
                        }

                        if (Config::getEntryTimestamps() && Slug::isDateTime($t)) {
                            if (strlen($part) >= (strlen($key) - 16 - $offset)) {
                                $part = $key;
                                break;
                            }
                        } elseif (Slug::isDate($t)) {
                            if (strlen($part) >= (strlen($key) - 12 - $offset)) {
                                $part = $key;
                                break;
                            }
                        } elseif (Slug::isNumeric($t)) {
                            $nl = strlen(Slug::getOrderNumber($key)) + 1;
                            if (strlen($part) >= (strlen($key) - $nl - $offset)) {
                                $part = $key;
                                break;
                            }
                        } else {
                            $t = basename($item);
                            if (strlen($part) >= strlen($t) - $offset) {
                                $part = $key;
                                break;
                            }
                        }
                    }
                }
            }

            if ($fixedpath != '/') {
                $fixedpath .= '/';
            }

            $fixedpath .= $part;
        }

        // /2-blog/hidden

        return $fixedpath;
    }


    /**
     * Removes occurrences of "//" in a $path (except when part of a protocol)
     *
     * @param string  $path  Path to remove "//" from
     * @return string
     */
    public static function tidy($path)
    {
        return preg_replace("#(^|[^:])//+#", "\\1/", $path);
    }


    /**
     * Trim slashes from either end of a given $path
     *
     * @param string  $path  Path to trim slashes from
     * @return string
     */
    public static function trimSlashes($path)
    {
        return trim($path, '/');
    }


    /**
     * Cleans up a given $path, removing any order keys (date-based or number-based)
     *
     * @param string  $path  Path to clean
     * @return string
     */
    public static function clean($path)
    {
        // remove draft and hidden flags
        $path = preg_replace("#/_[_]?#", "/", $path);

        // if we don't want entry timestamps, handle things manually
        if (!Config::getEntryTimestamps()) {
            $file     = substr($path, strrpos($path, "/"));
            
            // trim path if needed
            if (-strlen($file) + 1 !== 0) {
                $path = substr($path, 0, -strlen($file) + 1);
            }
            
            $path     = preg_replace(Pattern::ORDER_KEY, "", $path);
            $pattern  = (preg_match(Pattern::DATE, $file)) ? Pattern::DATE : Pattern::ORDER_KEY;
            $file     = preg_replace($pattern, "", $file);

            return Path::tidy($path . $file);
        }
        
        // otherwise, just remove all order-keys
        return preg_replace(Pattern::ORDER_KEY, "", $path);
    }


    /**
     * Pretty, end user paths
     *
     * @param string  $path  Path to clean
     * @return string
     */
    public static function pretty($path)
    {
        return self::tidy(self::clean('/' . $path));
    }



    /**
     * Checks if a given path is non-public content
     *
     * @param string  $path  Path to check
     * @return boolean
     */
    public static function isNonPublic($path)
    {
        if (substr($path, 0, 1) !== "/") {
            $path = "/" . $path;
        }

        return (strpos($path, "/_") !== false);
    }


    /**
     * Removes any filesystem path outside of the site root
     *
     * @param string  $path  Path to trim
     * @return string
     */
    public static function trimFilesystem($path)
    {
        return str_replace(self::standardize(BASE_PATH) . "/" . Config::getContentRoot(), "", $path);
    }


    /**
     * Creates a URL-friendly path from webroot
     *
     * @param string  $path  Path to trim
     * @return string
     */
    public static function toAsset($path)
    {
        $asset_path = self::trimFilesystem($path);

        return self::standardize(self::tidy(Config::getSiteRoot().$asset_path));
    }


    /**
     * Creates a full system path from an asset URL
     *
     * @param string  $path  Path to start from
     * @return string
     */
    public static function fromAsset($path)
    {
        return BASE_PATH . str_replace(Config::getSiteRoot(), '/', $path);
    }


    /**
     * Standardizes a filesystem path between *nix and windows
     *
     * @param string  $path  Path to standardize
     * @return string
     */
    public static function standardize($path)
    {
        return str_replace('\\', '/', $path);
    }


    /**
     * Prepends a / to a given $path if it's not there
     *
     * @param string  $path  path to check
     * @return string
     */
    public static function addStartingSlash($path)
    {
        return (substr($path, 0, 1) !== '/') ? '/' . $path : $path;
    }


    /**
     * Removes the / from the beginning of a given $path if it's there
     *
     * @param string  $path  path to check
     * @return string
     */
    public static function removeStartingSlash($path)
    {
        return (substr($path, 0, 1) === '/') ? substr($path, 1) : $path;
    }


    /**
     * Checks to see if this path is a draft
     *
     * @param string  $path  Path to check
     * @return boolean
     */
    public static function isDraft($path)
    {
        return (strpos($path, '/__') !== false);
    }


    /**
     * Checks to see if this path is hidden
     *
     * @param string  $path  Path to check
     * @return boolean
     */
    public static function isHidden($path)
    {
        return (bool) (preg_match("#/_[^_]#", $path));
    }
}