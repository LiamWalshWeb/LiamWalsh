<?php
use Symfony\Component\Finder\Finder as Finder;

/**
 * Cache
 * API for caching content
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @package     API
 * @copyright   2013 Statamic
 */
class Cache
{
    /**
     * Updates the internal content cache
     *
     * @return boolean
     */
    public static function update()
    {
        // track if any files have changed
        $files_changed     = false;
        $settings_changed  = false;

        // grab length of content type extension
        $content_type         = Config::getContentType();
        $full_content_root    = rtrim(Path::tidy(BASE_PATH . "/" . Config::getContentRoot()), "/");
        $content_type_length  = strlen($content_type) + 1;

        // the cache files we'll use
        $cache_file      = BASE_PATH . '/_cache/_app/content/content.php';
        $settings_file   = BASE_PATH . '/_cache/_app/content/settings.php';
        $structure_file  = BASE_PATH . '/_cache/_app/content/structure.php';
        $time_file       = BASE_PATH . '/_cache/_app/content/last.php';
        $members_file    = BASE_PATH . '/_cache/_app/members/members.php';
        $now             = time();

        // grab the existing cache
        $cache = unserialize(File::get($cache_file));
        if (!is_array($cache)) {
            $cache = self::getCleanCacheArray();
        }
        $last = File::get($time_file);
        
        // check for current and new settings
        $settings = unserialize(File::get($settings_file));
        if (!is_array($settings)) {
            $settings = array(
                'site_root' => '',
                'site_url'  => '',
                'timezone'  => '',
                'taxonomy'  => '',
                'taxonomy_case_sensitive' => '',
                'taxonomy_force_lowercase' => '',
                'entry_timestamps' => '',
                'base_path' => '',
                'app_version' => ''
            );
        }
        
        // look up current settings
        $current_settings = array(
            'site_root' => Config::getSiteRoot(),
            'site_url'  => Config::getSiteURL(),
            'timezone'  => Config::get('timezone'),
            'taxonomy'  => Config::getTaxonomies(),
            'taxonomy_case_sensitive' => Config::getTaxonomyCaseSensitive(),
            'taxonomy_force_lowercase' => Config::getTaxonomyForceLowercase(),
            'entry_timestamps' => Config::getEntryTimestamps(),
            'base_path' => BASE_PATH,
            'app_version' => STATAMIC_VERSION
        );
        
        // have cache-altering settings changed?        
        if ($settings !== $current_settings) {
            // settings have changed
            $settings_changed = true;
            
            // clear the cache and set current settings
            $cache     = self::getCleanCacheArray();
            $settings  = $current_settings;
            $last      = null;
        }

        // grab a list of all content files
        $finder = new Finder();
        $files = $finder
            ->files()
            ->name('*.' . Config::getContentType())
            ->in(Config::getContentRoot());

        // grab a separate list of files that have changed since last check
        $updated_files = clone $files;
        $updated = array();

        if ($last) {
            $updated_files->date('>= ' . Date::format('Y-m-d H:i:s', $last));

            foreach ($updated_files as $file) {
                // we don't want directories, they may show up as being modified
                // if a file inside them has changed or been renamed
                if (is_dir($file)) {
                    continue;
                }

                // this isn't a directory, add it to the list
                $updated[] = Path::trimFilesystemFromContent(Path::standardize($file->getRealPath()));
            }
        }

        // loop over current files
        $current_files = array();
        foreach ($files as $file) {
            $current_files[] = Path::trimFilesystemFromContent(Path::standardize($file->getRealPath()));
        }
        
        // get a diff of files we know about and files currently existing
        $known_files = array();
        foreach ($cache['urls'] as $url_data) {
            array_push($known_files, $url_data['path']);
        }
        $new_files = array_diff($current_files, $known_files);

        // create a master list of files that need updating
        $changed_files = array_unique(array_merge($new_files, $updated));

        // add to the cache if files have been updated
        if (count($changed_files)) {
            $files_changed = true;

            // build content cache
            foreach ($changed_files as $file) {
                $file           = $full_content_root . $file;
                $local_path     = Path::trimFilesystemFromContent($file);
                
                // before cleaning anything, check for hidden or draft content
                $is_hidden      = Path::isHidden($local_path);
                $is_draft       = Path::isDraft($local_path);
                
                // now clean up the path
                $local_filename = Path::clean($local_path);

                // file parsing
                $content       = substr(File::get($file), 3);
                $divide        = strpos($content, "\n---");
                $front_matter  = trim(substr($content, 0, $divide));
                $content_raw   = trim(substr($content, $divide + 4));

                // parse data
                $data = YAML::parse($front_matter);
                
                if ($content_raw) {
                    $data['content']      = 'true';
                    $data['content_raw']  = 'true';
                }

                // set additional information
                $data['_file']          = $file;
                $data['_local_path']    = $local_path;

                $data['_order_key']     = null;
                $data['datetimestamp']  = null;  // legacy
                $data['datestamp']      = null;
                $data['date']           = null;
                $data['time']           = null;
                $data['numeric']        = null;
                $data['last_modified']  = filemtime($file);
                $data['_is_hidden']     = $is_hidden;
                $data['_is_draft']      = $is_draft;

                // get initial slug (may be changed below)
                $data['slug'] = ltrim(basename($file, "." . $content_type), "_");

                // folder
                $instance = ($data['slug'] == 'page') ? 1 : 0;
                $data['_folder'] = Path::clean($data['_local_path']);
                $slash = Helper::strrpos_count($data['_folder'], '/', $instance);
                $data['_folder'] = (!$slash) ? '' : substr($data['_folder'], 1, $slash - 1);
                $data['_folder'] = (!strlen($data['_folder'])) ? "/" : $data['_folder'];

                $data['_basename'] = $data['slug'] . '.' . $content_type;
                $data['_filename'] = $data['slug'];
                $data['_is_entry'] = preg_match(Pattern::ENTRY_FILEPATH, $data['_basename']);
                $data['_is_page']  = preg_match(Pattern::PAGE_FILEPATH,  $data['_basename']);

                // 404 is special
                if ($data['_local_path'] === "/404.{$content_type}") {
                    $local_filename = $local_path;

                // order key: date or datetime                
                } elseif (preg_match(Pattern::DATE_OR_DATETIME, $data['_basename'], $matches)) {
                    $date = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                    $time = null;

                    if (Config::getEntryTimestamps() && isset($matches[4])) {
                        $time = substr($matches[4], 0, 2) . ":" . substr($matches[4], 2);
                        $date = $date . " " . $time;

                        $data['slug']           = substr($data['slug'], 16);
                        $data['datetimestamp']  = $data['_order_key'];
                    } else {
                        $data['slug']           = substr($data['slug'], 11);
                    }

                    $data['_order_key'] = strtotime($date);
                    $data['datestamp']  = $data['_order_key'];
                    $data['date']       = Date::format(Config::getDateFormat(), $data['_order_key']);
                    $data['time']       = ($time) ? Date::format(Config::getTimeFormat(), $data['_order_key']) : null;

                // order key: slug is page, back up a level
                } elseif ($data['slug'] == 'page' && preg_match(Pattern::NUMERIC, substr($data['_local_path'], Helper::strrpos_count($data['_local_path'], '/', 1)), $matches)) {
                    $data['_order_key'] = $matches[1];
                    $data['numeric']    = $data['_order_key'];

                // order key: numeric
                } elseif (preg_match(Pattern::NUMERIC, $data['_basename'], $matches)) {
                    $data['_order_key'] = $matches[1];
                    $data['numeric']    = $data['_order_key'];
                    $data['slug']       = substr($data['slug'], strlen($matches[1]) + 1);

                // order key: other
                } else {
                    $data['_order_key'] = $data['_basename'];
                }

                // determine url
                $data['url'] = preg_replace('#/__?#', '/', $local_filename);

                // remove any content type extensions from the end of filename
                if (substr($data['url'], -$content_type_length) === '.' . $content_type) {
                    $data['url'] = substr($data['url'], 0, strlen($data['url']) - $content_type_length);
                }

                // remove any base pages from filename
                if (substr($data['url'], -5) == '/page') {
                    $data['url'] = substr($data['url'], 0, strlen($data['url']) - 5);
                }

                // add the site root
                $data['url'] = Path::tidy(Config::getSiteRoot() . $data['url']);

                // add the site URL to get the permalink
                $data['permalink'] = Path::tidy(Config::getSiteURL() . $data['url']);
                
                // new content
                if (!isset($cache['content'][$data['_folder']]) || !is_array($cache['content'][$data['_folder']])) {
                    $cache['content'][$data['_folder']] = array();
                }

                $slug_with_extension = ($data['_filename'] == 'page') ? substr($data['url'], strrpos($data['url'], '/') + 1) . '/' . $data['_filename'] . "." . $content_type : $data['_filename'] . "." . $content_type;
                $cache['content'][$data['_folder']][$slug_with_extension] = array(
                    'folder' => $data['_folder'],
                    'path'   => $local_path,
                    'file'   => $slug_with_extension,
                    'url'    => $data['url'],
                    'data'   => $data
                );

                $cache['urls'][$data['url']] = array(
                    'folder' => $data['_folder'],
                    'path'   => $local_path,
                    'file'   => $slug_with_extension
                );
            }
        }


        // loop through all cached content for deleted files
        // this isn't as expensive as you'd think in real-world situations
        foreach ($cache['content'] as $folder => $folder_contents) {
            foreach ($folder_contents as $path => $data) {
                if (File::exists($full_content_root . $data['path'])) {
                    // still here, keep it
                    continue;
                }
                
                $files_changed = true;
                
                // remove from url cache
                $url = (isset($cache['content'][$folder][$path]['url'])) ? $cache['content'][$folder][$path]['url'] : null;
                if (!is_null($url)) {
                    unset($cache['urls'][$url]);
                }
                
                // remove from content cache
                unset($cache['content'][$folder][$path]);
            }
        }

        // build taxonomy cache
        // only happens if files were added, updated, or deleted above
        if ($files_changed) {
            $taxonomies           = Config::getTaxonomies();
            $force_lowercase      = Config::getTaxonomyForceLowercase();
            $case_sensitive       = Config::getTaxonomyCaseSensitive();
            $cache['taxonomies']  = array();

            // rebuild taxonomies
            if (count($taxonomies)) {
                // set up taxonomy array
                foreach ($taxonomies as $taxonomy) {
                    $cache['taxonomies'][$taxonomy] = array();
                }

                // loop through content to build cached array
                foreach ($cache['content'] as $folder => $pages) {
                    foreach ($pages as $file => $item) {
                        $data = $item['data'];
    
                        // loop through the types of taxonomies
                        foreach ($taxonomies as $taxonomy) {
                            // if this file contains this type of taxonomy
                            if (isset($data[$taxonomy])) {
                                $values = Helper::ensureArray($data[$taxonomy]);
    
                                // add the file name to the list of found files for a given taxonomy value
                                foreach ($values as $value) {
                                    if (!$value) {
                                        continue;
                                    }
    
                                    $key = (!$case_sensitive) ? strtolower($value) : $value;
    
                                    if (!isset($cache['taxonomies'][$taxonomy][$key])) {
                                        $cache['taxonomies'][$taxonomy][$key] = array(
                                            'name' => ($force_lowercase) ? strtolower($value) : $value,
                                            'files' => array()
                                        );
                                    }
    
                                    array_push($cache['taxonomies'][$taxonomy][$key]['files'], $data['url']);
                                }
                            }
                        }
                    }
                }
            }
            
            // build structure cache
            $structure = array();
            $home = Path::tidy('/' . Config::getSiteRoot() . '/');
            
            foreach ($cache['content'] as $folder => $pages) {
                foreach ($pages as $file => $item) {
                    // set up base variables
                    $parent = null;
                    
                    $order_key = ltrim($item['path'], $home);
                    $sub_order_key = $item['data']['_order_key'];
                    
                    // does this have a parent (and if so, what is it?)
                    if ($item['url'] !== $home) {
                        $parent = $home;
                        $depth = substr_count(str_replace($home, '/', $item['url']), '/');
                        $last_slash = strrpos($item['url'], '/', 1);
                        $last_order_slash = strrpos($order_key, '/', 0);
                        
                        if ($last_slash !== false) {
                            $parent = substr($item['url'], 0, $last_slash);
                        }

                        if ($last_order_slash !== false) {
                            $order_key = substr($order_key, 0, $last_order_slash);
                        }
                        
                        if ($item['data']['_is_page']) {
                            $type = ($item['data']['slug'] == 'page') ? 'folder' : 'page';
                        } else {
                            $type = 'entry';
                        }
                    } else {
                        $depth = 0;
                        $type = 'folder';
                        $order_key = $home;
                    }
                    
                    $structure[$item['url']] = array(
                        'parent' => $parent,
                        'is_entry' => $item['data']['_is_entry'],
                        'is_page' => $item['data']['_is_page'],
                        'is_hidden' => $item['data']['_is_hidden'],
                        'is_draft' => $item['data']['_is_draft'],
                        'depth' => $depth,
                        'order_key' => ($order_key) ? $order_key : $sub_order_key,
                        'sub_order_key' => $sub_order_key,
                        'type' => $type
                    );
                }
            }
        }

        // build member cache
        // ----------------------------------------------------------------

        // have members changed?
        $members_changed = false;

        // grab a list of existing members
        $finder = new Finder();
        $users = $finder
            ->files()
            ->name('*.yaml')
            ->in(Config::getConfigPath() . '/users/');

        // clone for reuse, set up our list of updated users
        $updated_users = clone $users;
        $updated = array();

        // get users from the file
        $members = unserialize(File::get($members_file));

        // has this been run before?
        if ($last) {
            // it has, check for updated members on our cloned list
            $updated_users->date('>= ' . Date::format('Y-m-d H:i:s', $last));

            // loop through
            foreach ($updated_users as $user) {
                // add it to the list
                $updated[] = Path::trimFilesystemFromContent(Path::standardize($user->getRealPath()));
            }
        } else {
            $members_changed = true;
        }

        // loop over current users
        $current_users = array();
        foreach ($users as $user) {
            $current_users[] = Path::trimFilesystemFromContent(Path::standardize($user->getRealPath()));
        }

        // get a diff of users we know about and files currently existing
        $known_users = array();
        if ($members) {
            foreach ($members as $username => $member_data) {
                $known_users[$username] = $member_data['_path'];
            }
        }
        
        $new_users = array_diff($current_users, $known_users);

        // create a master list of users that need updating
        $changed_users = array_unique(array_merge($new_users, $updated));

        if (count($changed_users)) {
            $members_changed = true;

            foreach ($changed_users as $user_file) {
                // file parsing
                $last_slash  = strrpos($user_file, '/') + 1;
                $last_dot    = strrpos($user_file, '.');
                $username    = substr($user_file, $last_slash, $last_dot - $last_slash);
                $content     = substr(File::get($user_file), 3);
                $divide      = strpos($content, "\n---");
                $data        = YAML::parse(trim(substr($content, 0, $divide)));
                $bio_raw     = trim(substr($content, $divide + 4));
                
                $data['_path'] = $user_file;
                
                if ($bio_raw) {
                    $data['biography'] = 'true';
                    $data['biography_raw'] = 'true';
                }

                $members[$username] = $data;
            }
        }

        // loop through all cached content for deleted files
        // this isn't as expensive as you'd think in real-world situations
        if (is_array($members)) {
            foreach ($members as $username => $data) {
                if (File::exists(Config::getConfigPath() . '/users/' . $username . '.yaml')) {
                    // still here, keep it
                    continue;
                }

                // mark that members have changed
                $members_changed = true;

                // remove from member cache
                unset($members[$username]);
            }
        }

        
        // write to caches
        // --------------------------------------------------------------------
        
        if ($files_changed) {
            // store the content cache
            if (File::put($cache_file, serialize($cache)) === false) {
                if (!File::isWritable($cache_file)) {
                    Log::fatal('Cache folder is not writable.', 'core', 'content-cache');
                }

                Log::fatal('Could not write to the cache.', 'core', 'content-cache');
                return false;
            }

            // store the structure cache
            if (File::put($structure_file, serialize($structure)) === false) {
                if (!File::isWritable($structure_file)) {
                    Log::fatal('Structure cache file is not writable.', 'core', 'structure-cache');
                }

                Log::fatal('Could not write to the structure cache.', 'core', 'structure-cache');
                return false;
            }
        }

        // store the settings cache
        if ($settings_changed) {
            if (File::put($settings_file, serialize($settings)) === false) {
                if (!File::isWritable($settings_file)) {
                    Log::fatal('Settings cache file is not writable.', 'core', 'settings-cache');
                }

                Log::fatal('Could not write to the settings cache file.', 'core', 'settings-cache');
                return false;
            }
        }
        
        // store the members cache
        if ($members_changed) {
            if (File::put($members_file, serialize($members)) === false) {
                if (!File::isWritable($members_file)) {
                    Log::fatal('Member cache file is not writable.', 'core', 'member-cache');
                }
                
                Log::fatal('Could not write to the member cache file.', 'core', 'member-cache');
                return false;
            }
        }
        
        File::put($time_file, $now);
        return true;
    }


    /**
     * Get last cache update time
     * 
     * @return int
     */
    public static function getLastCacheUpdate()
    {
        return filemtime(BASE_PATH . '/_cache/_app/content/content.php');
    }


    /**
     * Dumps the current content of the content cache to the screen
     * 
     * @return void
     */
    public static function dump()
    {
        $cache_file = BASE_PATH . '/_cache/_app/content/content.php';
        rd(unserialize(File::get($cache_file)));
    }
    
    
    /**
     * Returns a clean cache array for filling
     * 
     * @return array
     */
    public static function getCleanCacheArray()
    {
        return array(
            'urls' => array(),
            'content' => array(),
            'taxonomies' => array(),
            'structure' => array()
        );
    }
}