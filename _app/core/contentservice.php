<?php
/**
 * ContentService
 * An intermediary between the content cache and the system
 *
 * @package statamic
 */
class ContentService
{
    public static $cache;
    public static $cache_loaded = false;


    /**
     * Loads the content cache into the local cache file if not done yet
     *
     * @return void
     */
    public static function loadCache()
    {
        if (self::$cache_loaded) {
            return;
        }

        self::$cache_loaded = true;
        self::$cache = unserialize(File::get(Path::tidy(BASE_PATH . "/_cache/_app/content/content.php")));

        if (!is_array(self::$cache)) {
            // something has gone wrong, log a message and set to an empty array
            self::$cache = array();
            Log::fatal('Could not find or access your cache.', 'core', 'ContentService');
        }
    }



    // content checking
    // ------------------------------------------------------------------------

    /**
     * Is a given URL content that exists?
     *
     * @param string  $url  URL to check
     * @return bool
     */
    public static function isContent($url)
    {
        self::loadCache();
        return isset(self::$cache['urls'][$url]) && isset(self::$cache['content'][self::$cache['urls'][$url]]);
    }


    // single content entry
    // ------------------------------------------------------------------------

    /**
     * Gets cached content for one page based on a given URL
     *
     * @param string  $url  URL of content to load
     * @return array
     */
    public static function getContent($url)
    {
        self::loadCache();
        if (!self::isContent($url)) {
            return array();
        }

        return self::$cache["content"][self::$cache['urls'][$url]];
    }



    // taxonomies
    // ------------------------------------------------------------------------

    /**
     * Gets a list of taxonomy values by type
     *
     * @param string  $type  Taxonomy type to retrieve
     * @return TaxonomySet
     */
    public static function getTaxonomiesByType($type)
    {
        self::loadCache();
        $data = array();

        // taxonomy type doesn't exist, return empty
        if (!isset(self::$cache['taxonomies'][$type])) {
            return new TaxonomySet(array());
        }


        $url_root  = Config::getSiteRoot() . $type;
        $values    = self::$cache['taxonomies'][$type];
        $slugify   = Config::getTaxonomySlugify();

        // what we need
        // - name
        // - count of related content
        // - related

        foreach ($values as $key => $parts) {
            $set = array();

            $prepared_key = ($slugify) ? Slug::make($key) : rawurlencode($key);

            foreach ($parts['files'] as $url) {
                if (!isset(self::$cache['urls'][$url])) {
                    continue;
                }

                $set[$url] = self::$cache['content'][self::$cache['urls'][$url]];
            }

            $data[$key] = array(
                'content' => new ContentSet($set),
                'name'    => $parts['name'],
                'url'     => $url_root . '/' . $prepared_key,
                'slug'    => $type . '/' . $prepared_key
            );
            $data[$key]['count'] = $data[$key]['content']->count();
        }

        return new TaxonomySet($data);
    }


    /**
     * Returns a taxonomy slug's name if stored in cache
     *
     * @param string  $taxonomy  Taxonomy to use
     * @param string  $taxonomy_slug  Taxonomy slug to use
     * @return mixed
     */
    public static function getTaxonomyName($taxonomy, $taxonomy_slug)
    {
        self::loadCache();

        if (!isset(self::$cache['taxonomies'][$taxonomy]) || !isset(self::$cache['taxonomies'][$taxonomy][$taxonomy_slug])) {
            return null;
        }

        return self::$cache['taxonomies'][$taxonomy][$taxonomy_slug]['name'];
    }


    // content
    // ------------------------------------------------------------------------

    /**
     * Gets cached content by URL
     *
     * @param string  $url  URL to use
     * @return ContentSet
     */
    public static function getContentByURL($url)
    {
        $content = ContentService::getContent($url);
        $content = (count($content)) ? array($content) : $content;
        return new ContentSet($content);
    }


    /**
     * Gets cached content for pages for a certain taxonomy type and value
     *
     * @param string  $taxonomy  Taxonomy to use
     * @param string  $values  Values to match (single or array)
     * @param mixed  $folders  Optionally, folders to filter down by
     * @return ContentSet
     */
    public static function getContentByTaxonomyValue($taxonomy, $values, $folders=null)
    {
        self::loadCache();
        $case_sensitive = Config::getTaxonomyCaseSensitive();

        if ($folders) {
            $folders = Helper::parseForFolders($folders);
        }

        // if an array was sent
        if (is_array($values)) {
            $files = array();

            if (!$case_sensitive) {
                $values = array_map('strtolower', $values);
            }

            // loop through each of the values looking for files
            foreach ($values as $value) {
                if (!isset(self::$cache["taxonomies"][$taxonomy][$value])) {
                    continue;
                }

                // add these file names to the big file list
                $files = array_merge($files, self::$cache["taxonomies"][$taxonomy][$value]['files']);
            }

            // get unique list of files
            $files = array_unique($files);

            // if a single value was sent
        } else {
            if (!$case_sensitive) {
                $values = strtolower($values);
            }

            if (!isset(self::$cache["taxonomies"][$taxonomy][$values])) {
                $files = array();
            } else {
                $files = self::$cache["taxonomies"][$taxonomy][$values]['files'];
            }
        }

        // if no files, abort
        if (!count($files)) {
            return new ContentSet(array());
        }

        // still here? grab data from cache
        $data = array();
        foreach ($files as $file) {
            $data[] = ContentService::getContent($file);
        }

        $content_set = new ContentSet($data);

        if ($folders) {
            $content_set->filter(array("folders" => $folders));
        }

        return $content_set;
    }


    /**
     * Gets cached content for pages from given folders
     *
     * @param array  $folders  Folders to grab from
     * @return ContentSet
     */
    public static function getContentByFolders($folders)
    {
        self::loadCache();
        
        $content_set = new ContentSet(array_values(self::$cache['content']));
        $content_set->filter(array("folders" => Helper::parseForFolders($folders)));
        return $content_set;
    }
}