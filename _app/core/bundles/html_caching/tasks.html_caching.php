<?php

class Tasks_html_caching extends Tasks
{
    /**
     * Is HTML-caching enabled (either globally or for $url)?
     * 
     * @param string  $url  URL to check specifically for
     * @return bool
     */
    public function isEnabled($url)
    {
        // check to see if html-caching is on
        $global_enable = (bool) $this->fetchConfig('enable', false, null, true);
        
        if (!$global_enable) {
            return false;
        }

        // check that the URL being requested is a content file
        $bare_url  = (strpos($url, '?') !== false) ? substr($url, 0, strpos($url, '?')) : $url;
        $data      = ContentService::getContent($bare_url);

        // not a content file, not enabled
        if (!$data) {
            return false;
        }
        
        // check for exclude on the current page
        $exclude_raw = $this->fetchConfig('exclude');
        
        // if excludes were set
        if ($exclude_raw) {
            $excluded = Parse::pipeList($exclude_raw);
            
            // loop through excluded options
            foreach ($excluded as $exclude) {
                // account for non-/-starting locations
                $this_url = (substr($exclude, 0, 1) !== "/") ? ltrim($url, '/') : $url;
                
                if ($exclude === "*" || $exclude === "/*") {
                    // exclude all
                    return false;
                } elseif (substr($exclude, -1) === "*") {
                    // wildcard check
                    if (strpos($this_url, substr($exclude, 0, -1)) === 0) {
                        return false;
                    }
                } else {
                    // plain check
                    if ($exclude == $this_url) {
                        return false;
                    }
                }
            }
        }

        // all is well, return true
        return true;
    }


    /**
     * Is the $url in our cache and still valid?
     * 
     * @param string  $url  URL to check for cache
     * @return bool
     */
    public function isPageCached($url)
    {        
        $cache_length = trim($this->fetchConfig('cache_length', false));
        
        // if no cache-length is set, this feature is off
        if (!(bool) $cache_length) {
            return false;
        }

        // create the hash now so we don't have to do it many times below
        $url_hash = Helper::makeHash($url);
        
        // are we doing this on cache update?
        if ($cache_length == 'on cache update') {
            // purge any cache file from before the last cache update
            $this->cache->purgeFromBefore(Cache::getLastCacheUpdate());
            
            // return if the file still exists
            return $this->cache->exists($url_hash);
        } elseif ($cache_length == 'on last modified') {
            // ignore the cached version if the last modified time of this URL's
            // content file is newer than when the cached version was made

            // check that the URL being requested is a content file
            $bare_url  = (strpos($url, '?') !== false) ? substr($url, 0, strpos($url, '?')) : $url;
            $data      = ContentService::getContent($bare_url);
            $age       = time() - File::getLastModified($data['_file']);
            
            // return if the cache file exists and if it's new enough
            return ($this->cache->exists($url_hash) && $this->cache->getAge($url_hash) <= $age);
        } else {
            // purge any cache files older than the cache length
            $this->cache->purgeFromBefore('-' . $cache_length);
            
            // return if the file still exists
            return $this->cache->exists($url_hash);
        }
    }


    /**
     * Return the cached HTML for a $url
     * 
     * @param string  $url  URL to retrieve from cache
     * @return string
     */
    public function getCachedPage($url)
    {
        return $this->cache->get(Helper::makeHash($url), '');
    }


    /**
     * Store the $html into the cache for a $url
     * 
     * @param string  $url  URL to store HTML for
     * @param string  $html  Rendered HTML to store
     * @return void
     */
    public function putCachedPage($url, $html)
    {
        $this->cache->put(Helper::makeHash($url), $html);
    }
}