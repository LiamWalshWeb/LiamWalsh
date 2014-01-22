<?php
/**
 * Statamic
 *
 * @author      Jack McDade
 * @author      Mubashar Iqbal
 * @author      Fred LeBlanc
 * @copyright   2012 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 *
 */

use Symfony\Component\Finder\Finder as Finder;

class Statamic
{
  protected static $_yaml_cache = array();
  public static $folder_list = array();

  public static $publication_states = array('live' => 'Live', 'hidden' => 'Hidden', 'draft' => 'Draft');

  public static function loadYamlCached($content)
  {
    $hash = md5($content);

    if (isset(self::$_yaml_cache[$hash])) {
      $yaml = self::$_yaml_cache[$hash];
    } else {
      $yaml = YAML::parse($content);
      self::$_yaml_cache[$hash] = $yaml;
    }

    return $yaml;
  }

  /**
   * Load the config (yaml) files in a specified order:
   *
   * 1. Loose per-site configs
   * 2. Routes
   * 3. Settings
   * 4. Theme overrides
   */
  public static function loadAllConfigs($admin = FALSE)
  {
    /*
    |--------------------------------------------------------------------------
    | YAML Mode
    |--------------------------------------------------------------------------
    |
    | We need to know the YAML mode first (loose, strict, transitional),
    | so we parse the settings file once to check before doing anything else.
    |
    */

    $preload_config = YAML::parse(Config::getConfigPath() . '/settings.yaml');
    $yaml_mode = array_get($preload_config, '_yaml_mode', 'loose');

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | We keep a set of default options that the user config overrides, allowing
    | us to always have clean defaults.
    |
    */

    $default_config = YAML::parse(Config::getAppConfigPath() . '/default.settings.yaml');

    /*
    |--------------------------------------------------------------------------
    | User Site Settings
    |--------------------------------------------------------------------------
    |
    | Next we parse and override the user's settings.
    |
    */

    $user_config = YAML::parse(Config::getConfigPath() . '/settings.yaml', $yaml_mode);

    $config = array_merge($default_config, $user_config);

    /*
    |--------------------------------------------------------------------------
    | Routes and vanity URLs
    |--------------------------------------------------------------------------
    |
    | Any URL can be manipulated by routes or vanity urls. We need this info
    | early on, before content parsing begins.
    |
    */

    $routes = array();
    if (File::exists(Config::getConfigPath() . '/routes.yaml')) {
      $routes['_routes'] = YAML::parse('_config/routes.yaml', $yaml_mode);
    }

    // check for vanity URLs first, we may need to redirect
    $vanity = array();
    if (File::exists(Config::getConfigPath() . '/vanity.yaml')) {
      $vanity['_vanity_urls'] = YAML::parse(Config::getConfigPath() . '/vanity.yaml', $yaml_mode);
    }

    $config = array_merge($config, $routes, $vanity);

    /*
    |--------------------------------------------------------------------------
    | Global Variables
    |--------------------------------------------------------------------------
    |
    | We parse all the yaml files in the root (except settings and routes) of
    | the config folder and make them available as global template variables.
    |
    */

    if (Folder::exists($config_files_location = Config::getConfigPath())) {
      $finder = new Finder();

      $files = $finder->files()
        ->in($config_files_location)
        ->name('*.yaml')
        ->notName('routes.yaml')
        ->notName('vanity.yaml')
        ->notName('settings.yaml')
        ->depth(0)
        ->followLinks();

      if (iterator_count($files) > 0) {
        foreach ($files as $file) {
          $config = array_merge($config, YAML::parse($file->getRealPath(), $yaml_mode));
        }
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Theme Variables
    |--------------------------------------------------------------------------
    |
    | Theme variables need to specifically parsed later so they can override
    | any site/global defaults.
    |
    */

    $themes_path = array_get($config, '_themes_path', '_themes');
    $theme_name = array_get($config, '_theme', 'denali');

    if (Folder::exists($theme_files_location = URL::assemble(BASE_PATH, $themes_path, $theme_name))) {

      $finder = new Finder(); // clear previous Finder interator results

      $theme_files = $finder->files()
        ->in($theme_files_location)
        ->name('*.yaml')
        ->depth(0)
        ->followLinks();

      if (iterator_count($theme_files) > 0) {
        foreach ($theme_files as $file) {
          $config = array_merge($config, YAML::parse($file->getRealPath(), $yaml_mode));
        }
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Load Environment Configs and Variables
    |--------------------------------------------------------------------------
    |
    | Environments settings explicitly overwrite any existing settings, and
    | therefore must be loaded late. We also set a few helper variables
    | to make working with environments even easier.
    |
    */

    Environment::establish($config);

    /*
    |--------------------------------------------------------------------------
    | MIME Types
    |--------------------------------------------------------------------------
    */

    $config = array_merge($config, array('_mimes' => require Config::getAppConfigPath() . '/mimes.php'));

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | We load up English by default. We're American after all. Doesn't the
    | world revolve around us? Hello? Bueller? More hamburgers please.
    |
    */

    $config['_translations'] = array();
    $config['_translations']['en'] = YAML::parse(Config::getAppConfigPath() . '/default.en.yaml');

    if ($lang = array_get($config, '_language', false)) {
      if (File::exists(Config::getTranslation($lang))) {
        $config['_translations'][$lang] = YAML::parse(Config::getTranslation($lang));
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Set Slim Config
    |--------------------------------------------------------------------------
    |
    | Slim needs to be initialized with a set of config options, so these
    | need to be set earlier than the set_default_tags() method.
    |
    */

    // $config['view'] = new Statamic_View();
    $config['cookies.lifetime'] = $config['_cookies.lifetime'];

    if ($admin) {
      $admin_theme = array_get($config, '_admin_theme', 'ascent');

      if ( ! Folder::exists(Path::tidy('/'.$config['_admin_path'].'/'.'themes/'.$admin_theme))) {
        $admin_theme = 'ascent';
      }

      $theme_path = Path::tidy('/' . $config['_admin_path'] . '/' . 'themes/' . $admin_theme . '/');

      $config['_admin_path']    = $config['_admin_path'];
      $config['theme_path']     = $theme_path;
      $config['templates.path'] = '.'.$theme_path;

    } else {
      $public_path = isset($config['_public_path']) ? $config['_public_path'] : '';

      $config['theme_path'] = $themes_path.'/'.$config['_theme'].'/';
      $config['templates.path'] = Path::tidy($public_path.$themes_path.'/'.$config['_theme'].'/');
    }

    return $config;
  }


  /**
   * If the given redirect conditions are met, redirects the site to the given URL
   *
   * @param array  $config  Configuration of the site
   * @return bool
   */
  public static function processVanityURLs($config)
  {
      // if no array or an empty one, we're done here
      if ( ! isset($config['_vanity_urls']) || empty($config['_vanity_urls'])) {
          return FALSE;
      }

      // current path
      // note: not using API because it's not ready yet
      $uri     = $_SERVER['REQUEST_URI'];
      $query   = $_SERVER['QUERY_STRING'];
      $current = ($query) ? str_replace("?" . $query, "", $uri) : $uri;

      // loop through configured vanity URLs
      foreach ($config['_vanity_urls'] as $url => $redirect) {
          $redirect_forward = FALSE;
          $redirect_url     = NULL;

          // if this wasn't a match, move on
          if (Path::tidy("/" . $url) != $current) {
              continue;
          }

          // we have a match
          // now check to see how this redirect was set up
          if (is_array($redirect)) {
              // this was an array
              if ( ! isset($redirect['url'])) {
                  Log::warn("Vanity URL `" . $url . "` matched, but no redirect URL was configred.", "core", "vanity");
                  continue;
              }

              $redirect_start   = Helper::choose($redirect, 'start', NULL);
              $redirect_until   = Helper::choose($redirect, 'until', NULL);
              $redirect_forward = Helper::choose($redirect, 'forward_query_string', FALSE);
              $redirect_url     = Helper::choose($redirect, 'url', $redirect);
              $redirect_type    = (int) Helper::choose($redirect, 'type', 302);

              // if start date is set and it's before that date
              if ($redirect_start && time() < Date::resolve($redirect_start)) {
                  Log::info("Vanity URL `" . $url . "` matched, but scheduling does not allowed redirecting yet.", "core", "vanity");
                  continue;
              }

              // if until date is set and it's after after that date
              if ($redirect_until && time() > Date::resolve($redirect_until)) {
                  Log::info("Vanity URL `" . $url . "` matched, but scheduling for this redirect has expired.", "core", "vanity");
                  continue;
              }
          } else {
              // this was a string
              $redirect_url = $redirect;
              $redirect_type = 302;
          }

          // optionally forward any query string variables
          if ($query && $redirect_forward) {
              $redirect_url .= (strstr($redirect_url, "?") !== FALSE) ? "&" : "?";
              $redirect_url .= $query;
          }

          // ensure a complete URL
          if (!substr($redirect_url, 0, 4) == "http") {
              $redirect_url = Path::tidy(Config::getSiteRoot() . "/" . $redirect_url);
          }

          // ensure a valid redirect type
          if (!in_array($redirect_type, array(301, 302))) {
              $redirect_type = 302;
          }

          // redirect
          header("Location: " . $redirect_url, TRUE, $redirect_type);
          exit();
      }
  }


  /**
   * Set up any and all global vars, tags, and other defaults
   *
   * @return void
   */
  public static function setDefaultTags()
  {
    $app = \Slim\Slim::getInstance();

    /*
    |--------------------------------------------------------------------------
    | User & Session Authentication
    |--------------------------------------------------------------------------
    |
    | This may be overwritten later, but let's go ahead and set the default
    | layout file to start assembling our front-end view.
    |
    */

    $current_user = Statamic_Auth::get_current_user();
    $app->config['logged_in'] = $current_user !== FALSE;
    $app->config['username']  = $current_user ? $current_user->get_name() : FALSE;
    $app->config['is_admin']  = $current_user ? $current_user->has_role('admin') : FALSE;


    /*
    |--------------------------------------------------------------------------
    | GET and POST global vars
    |--------------------------------------------------------------------------
    |
    | Use these at your own risk, of course. Don't be stupid.
    |
    */

      $app->config['get'] = $_GET;
      $app->config['post'] = $_POST;
      $app->config['get_post'] = array_merge($_GET, $_POST);

    /**
     * @deprecated
     * The {{ user }} tag has been replaced by {{ member:profile }} and
     * will be removed in v1.6
     */

    $app->config['user'] = $current_user ? array(
      array('first_name' => $current_user->get_first_name()),
      array('last_name' => $current_user->get_last_name()),
      array('bio' => $current_user->get_biography())
    ) : FALSE;

    $app->config['homepage'] = Config::getSiteRoot();
  }

  /**
   * A cache-friendly wrapper for the get_content_list() method
   *
   * @return array
   **/
  public static function get_folder_list($folder,$future=FALSE,$past=TRUE)
  {
    if (isset(self::$folder_list[$folder])) {
      $folder_list = self::$folder_list[$folder];
    } else {
      $folder_list = Statamic::get_content_list($folder, NULL, 0, $future, $past, 'date', 'desc', NULL, NULL, FALSE, FALSE, NULL, NULL);
      self::$folder_list[$folder] = $folder_list;
    }

    return $folder_list;
  }

  public static function get_site_root()
  {
      Log::warn("Use of Statamic::get_site_root() is deprecated. Use Config::getSiteRoot() instead.", "core", "Statamic");
      return Config::getSiteRoot();
  }

  public static function get_site_url()
  {
      Log::warn("Use of Statamic::get_site_url() is deprecated. Use Config::getSiteURL() instead.", "core", "Statamic");
      return Config::getSiteURL();
  }

  public static function get_site_name()
  {
      Log::warn("Use of Statamic::get_site_name() is deprecated. Use Config::getSiteName() instead.", "core", "Statamic");
      return Config::getSiteName();
  }

  public static function get_license_key()
  {
      Log::warn("Use of Statamic::get_license_key() is deprecated. Use Config::getLicenseKey() instead.", "core", "Statamic");
      return Config::getLicenseKey();
  }

  public static function get_theme_name()
  {
      Log::warn("Use of Statamic::get_theme_name() is deprecated. Use Config::getTheme() instead.", "core", "Statamic");
      return Config::getTheme();
  }

  public static function get_theme()
  {
      Log::warn("Use of Statamic::get_theme() is deprecated. Use Config::getTheme() instead.", "core", "Statamic");
      return Config::getTheme();
  }

  public static function get_theme_assets_path()
  {
      Log::warn("Use of Statamic::get_theme_assets_path() is deprecated. Use Config::getThemeAssetsPath() instead.", "core", "Statamic");
      return Config::getThemeAssetsPath();
  }

  public static function get_theme_path()
  {
      Log::warn("Use of Statamic::get_theme_path() is deprecated. Use Config::getCurrentThemePath() instead.", "core", "Statamic");
      return Config::getCurrentThemePath();
  }

  public static function get_templates_path()
  {
      Log::warn("Use of Statamic::get_templates_path() is deprecated. Use Config::getTemplatesPath() instead.", "core", "Statamic");
      return Config::getTemplatesPath();
  }

  public static function get_admin_path()
  {
      Log::warn("Use of Statamic::get_admin_path() is deprecated. Use Config::getAdminPath() instead.", "core", "Statamic");
      return Config::getAdminPath();
  }

  public static function get_addon_path($addon=NULL)
  {
      Log::warn("Use of Statamic::get_addon_path() is deprecated. Use Config::getAddOnPath() instead.", "core", "Statamic");
      return Config::getAddOnPath($addon);
  }

  public static function get_content_root()
  {
      Log::warn("Use of Statamic::get_content_root() is deprecated. Use Config::getContentRoot() instead.", "core", "Statamic");
      return Config::getContentRoot();
  }

  public static function get_content_type()
  {
      Log::warn("Use of Statamic::get_content_type() is deprecated. Use Config::getContentType() instead.", "core", "Statamic");
      return Config::getContentType();
  }

  public static function get_date_format()
  {
      Log::warn("Use of Statamic::get_date_format() is deprecated. Use Config::getDateFormat() instead.", "core", "Statamic");
      return Config::getDateFormat();
  }

  public static function get_time_format()
  {
      Log::warn("Use of Statamic::get_time_format() is deprecated. Use Config::getTimeFormat() instead.", "core", "Statamic");
      return Config::getTimeFormat();
  }

  public static function get_entry_timestamps()
  {
      Log::warn("Use of Statamic::get_entry_timestamps() is deprecated. Use Config::getEntryTimestamps() instead.", "core", "Statamic");
      return Config::getEntryTimestamps();
  }

  public static function get_setting($setting, $default = FALSE)
  {
      Log::warn("Use of Statamic::get_setting() is deprecated. Use Config::get() instead.", "core", "Statamic");
      return Config::get($setting, $default);
  }

  public static function get_entry_type($path)
  {
    $type = 'none';

    $content_root = Config::getContentRoot();
    if (File::exists("{$content_root}/{$path}/fields.yaml")) {

      $fields_raw = File::get("{$content_root}/{$path}/fields.yaml");
      $fields_data = YAML::parse($fields_raw);

      if (isset($fields_data['type']) && ! is_array($fields_data['type'])) {
        $type = $fields_data['type']; # simplify, no "prefix" necessary
      } elseif (isset($fields_data['type']['prefix'])) {
        $type = $fields_data['type']['prefix'];
      }
    }

    return $type;
  }

  public static function get_templates_list()
  {
      Log::warn("Use of Statamic::get_templates_list() is deprecated. Use Theme::getTemplates() instead.", "core", "Statamic");
      return Theme::getTemplates();
  }

  public static function get_layouts_list()
  {
      Log::warn("Use of Statamic::get_templates_list() is deprecated. Use Theme::getLayouts() instead.", "core", "Statamic");
      return Theme::getLayouts();
  }

  public static function get_pagination_variable()
  {
      Log::warn("Use of Statamic::get_pagination_variable() is deprecated. Use Config::getPaginationVariable() instead.", "core", "Statamic");
      return Config::getPaginationVariable();
  }

  public static function get_pagination_style()
  {
      Log::warn("Use of Statamic::get_pagination_style() is deprecated. Use Config::getPaginationStyle() instead.", "core", "Statamic");
      return Config::getPaginationStyle();
  }

  public static function get_parse_order()
  {
        Log::warn("Use of Statamic::get_parse_order() is deprecated. Use Config::getParseOrder() instead.", "core", "Statamic");
        return Config::getParseOrder();
  }

  public static function is_content_writable()
  {
      return Folder::isWritable(Config::getContentRoot());
  }

  public static function are_users_writable()
  {
      return Folder::isWritable('_config/users/');
  }

  public static function get_content_meta($slug, $folder=NULL, $raw=FALSE, $parse=TRUE)
  {
    $app = \Slim\Slim::getInstance();

    $site_root    = Config::getSiteRoot();
    $content_root = Config::getContentRoot();
    $content_type = Config::getContentType();

    $file = $folder ? "{$content_root}/{$folder}/{$slug}.{$content_type}" : "{$content_root}/{$slug}.{$content_type}";
    $file = Path::tidy($file);

    $meta_raw = File::exists($file) ? file_get_contents($file) : '';

    if (Pattern::endsWith($meta_raw, "---")) {
      $meta_raw .= "\n"; # prevent parse failure
    }
    # Parse YAML Front Matter
    if (strpos($meta_raw, "---") === FALSE) {

      $meta = self::loadYamlCached($meta_raw);

      if (is_array($meta)) {
        $meta = array_merge($meta, $app->config);
      }

      $meta['content'] = "";
      if ($raw) {
        $meta['content_raw'] = "";
      }

    } else {
      list($yaml, $content) = preg_split("/---/", $meta_raw, 2, PREG_SPLIT_NO_EMPTY);
      $meta = self::loadYamlCached($yaml);

      if ($raw) {
        $meta['content_raw'] = $content;
      }

      // Parse the content if necessary
      //$meta['content'] = $parse ? Content::parse($content, $meta) : $content;
      $meta['content'] = $content;
    }
    if (File::exists($file)) {
      $meta['last_modified'] = filemtime($file);
    }

    if (! $raw) {
      $meta['homepage'] = Config::getSiteRoot();
      $meta['raw_url']  = Request::getResourceURI();
      $meta['page_url'] = Request::getResourceURI();

      # Is date formatted correctly?
      if (Config::getEntryTimestamps() && Slug::isDateTime($slug)) {
        $datetimestamp = Slug::getTimestamp($slug);
        $datestamp = Slug::getTimestamp($slug);

        $meta['datetimestamp'] = $datetimestamp;
        $meta['datestamp'] = $datestamp;
        $meta['date']      = Date::format(Config::getDateFormat(), $datestamp);
        $meta['time']      = Date::format(Config::getTimeFormat(), $datetimestamp);
        $meta['page_url']  = preg_replace(Pattern::DATETIME, '', $meta['page_url']); # clean url override

      } elseif (Slug::isDate($slug)) {
        $datestamp = Slug::getTimestamp($slug);

        $meta['datestamp'] = $datestamp;
        $meta['date']      = Date::format(Config::getDateFormat(), $datestamp);
        $meta['page_url']  = preg_replace(Pattern::DATE, '', $meta['page_url']); # clean url override

      } elseif (Slug::isNumeric($slug)) {
        $meta['numeric']   = Slug::getOrderNumber($slug);
      }

      $meta['permalink'] = Path::tidy(Config::getSiteURL().'/'.$meta['page_url']);
      $taxonomy_slugify  = (isset($app->config['_taxonomy_slugify']) && $app->config['_taxonomy_slugify']);

      # Jam it all together, brother.
      # @todo: functionize/abstract this method for more flexibility and readability
      foreach ($meta as $key => $value) {

        if (! is_array($value) && Taxonomy::isTaxonomy($key)) {
          $value = array($value);
          $meta[$key] = $value;
        }

        if (is_array($value)) {
          $list = array();
          $url_list = array();

          $i = 1;
          $total_results = count($meta[$key]);
          foreach ($meta[$key] as $k => $v) {

            $url = NULL;
            if (Taxonomy::isTaxonomy($key)) {

              // DO NOT DO numerical regex replace on the actual taxonomy item
              $url = Path::tidy(strtolower($site_root.'/'.$folder.'/'.$key));
              $url = preg_replace(Pattern::NUMERIC, '', $url);
              if ($taxonomy_slugify) {
                $url .= "/".(strtolower(Slug::make($v)));
              } else {
                $url .= "/".(strtolower($v));
              }


              $list[] = array(
                'name'  => $v,
                'count' => $i,
                'url'   => $url,
                'total_results' => $total_results,
                'first' => $i == 1 ? TRUE : FALSE,
                'last' => $i == $total_results ? TRUE : FALSE
              );

              $url_list[] = '<a href="'.$url.'">'.$v.'</a>';

            } elseif ( ! is_array($v)) {

              $list[] = array(
                'name'  => $v,
                'count' => $i,
                'url'   => $url,
                'total_results' => $total_results,
                'first' => $i == 1 ? TRUE : FALSE,
                'last' => $i == $total_results ? TRUE : FALSE
              );
            }

            // account for known structure
            // -
            //   name: something
            //   url: http://example.com
            if (is_array($v) && isset($v['name']) && isset($v['url'])) {
              $url_list[] = '<a href="'.$v['url'].'">'.$v['name'].'</a>';
            }

            $i++;

          }

          if (isset($url) || count($url_list)) {
            $meta[$key.'_url_list']                     = implode(', ', $url_list);
            $meta[$key.'_spaced_url_list']              = join(" ", $url_list);
            $meta[$key.'_ordered_url_list']             = "<ol><li>" . join("</li><li>", $url_list) . "</li></ol>";
            $meta[$key.'_unordered_url_list']           = "<ul><li>" . join("</li><li>", $url_list) . "</li></ul>";
            $meta[$key.'_sentence_url_list']            = Helper::makeSentenceList($url_list);
            $meta[$key.'_ampersand_sentence_url_list']  = Helper::makeSentenceList($url_list, "&", false);
          }

          if (isset($meta[$key][0]) && ! is_array($meta[$key][0])) {
            $meta[$key.'_list']                     = implode(', ', $meta[$key]);
            $meta[$key.'_option_list']              = implode('|', $meta[$key]);
            $meta[$key.'_spaced_list']              = implode(' ', $meta[$key]);
            $meta[$key.'_ordered_list']             = "<ol><li>" . join("</li><li>", $meta[$key]) . "</li></ol>";
            $meta[$key.'_unordered_list']           = "<ul><li>" . join("</li><li>", $meta[$key]) . "</li></ul>";
            $meta[$key.'_sentence_list']            = Helper::makeSentenceList($meta[$key]);
            $meta[$key.'_ampersand_sentence_list']  = Helper::makeSentenceList($meta[$key], "&", false);
            $meta[$key] = $list;
          }
        }
      }
    }

    return $meta;
  }

  public static function get_content_list($folder=NULL,$limit=NULL,$offset=0,$future=FALSE,$past=TRUE,$sort_by='date',$sort_dir='desc',$conditions=NULL,$switch=NULL,$skip_status=FALSE,$parse=TRUE,$since=NULL,$until=NULL,$location=NULL,$distance_from=NULL)
  {
    $folder_list = Helper::explodeOptions($folder);

    $list = array();
    foreach ($folder_list as $list_item) {
      $results = self::get_content_all($list_item, $future, $past, $conditions, $skip_status, $parse, $since, $until, $location, $distance_from);

      // if $location was set, filter out results that don't work
      if (!is_null($location)) {
        foreach ($results as $result => $variables) {
          try {
              foreach ($variables as $key => $value) {
                  // checks for $location variables, and that it has a latitude and longitude within it
                  if (strtolower($location) == strtolower($key)) {
                      if (!is_array($value) || !isset($value['latitude']) || !$value['latitude'] || !isset($value['longitude']) || !$value['longitude']) {
                          throw new Exception("nope");
                      }
                  }
              }
          } catch (Exception $e) {
              unset($results[$result]);
          }
        }
      }

      $list = $list+$results;
    }

    // default sort is by date
    if ($sort_by == 'date') {
      uasort($list, 'statamic_sort_by_datetime');
    } elseif ($sort_by == 'title') {
      uasort($list, "statamic_sort_by_title");
    } elseif ($sort_by == 'random') {
      shuffle($list);
    } elseif ($sort_by == 'numeric' || $sort_by == 'number') {
      ksort($list);
    } elseif ($sort_by == 'distance' && !is_null($location) && !is_null($distance_from) && preg_match(Pattern::COORDINATES, trim($distance_from))) {
      uasort($list, "statamic_sort_by_distance");
    } elseif ($sort_by != 'date') {
      # sort by any other field
      uasort($list, function($a, $b) use ($sort_by) {
        if (isset($a[$sort_by]) && isset($b[$sort_by])) {
          return strcmp($b[$sort_by], $a[$sort_by]);
        }
      });
    }

    // default sort is asc
    if ($sort_dir == 'desc') {
      $list = array_reverse($list);
    }

    // handle offset/limit
    if ($offset > 0) {
      $list = array_splice($list, $offset);
    }

    if ($limit) {
      $list = array_splice($list, 0, $limit);
    }

    if ($switch) {
      $switch_vars = explode('|',$switch);
      $switch_count = count($switch_vars);

      $count = 1;
      foreach ($list as $key => $post) {
        $list[$key]['switch'] = $switch_vars[($count -1) % $switch_count];
        $count++;
      }
    }

    return $list;
  }

  public static function fetch_content_by_url($path)
  {
      $data = NULL;
      $content_root = Config::getContentRoot();
      $content_type = Config::getContentType();

      if (File::exists("{$content_root}/{$path}.{$content_type}") || is_dir("{$content_root}/{$path}")) {
        // endpoint or folder exists!
      } else {
        $path = Path::resolve($path);
      }

      if (File::exists("{$content_root}/{$path}.{$content_type}")) {

        $page     = basename($path);
        $folder   = substr($path, 0, (-1*strlen($page))-1);

        $data = Statamic::get_content_meta($page, $folder);
      } elseif (Folder::exists("{$content_root}/{$path}")) {
        $data = Statamic::get_content_meta("page", $path);
      }

      return $data;
  }

  public static function get_next_numeric($folder=NULL)
  {
    $next = '01';

    $list = self::get_content_list($folder, NULL, 0, TRUE, TRUE, 'numeric', 'asc');

    if (sizeof($list) > 0) {

      $item = array_pop($list);
      $current = $item['numeric'];

      if ($current <> '') {
        $next = $current + 1;
        $format= '%1$0'.strlen($current).'d';
        $next = sprintf($format, $next);
      }
    }

    return $next;
  }

  public static function get_next_numeric_folder($folder=NULL)
  {
    $next = '01';

    $list = self::get_content_tree($folder,1,1,TRUE,FALSE,TRUE);
    if (sizeof($list) > 0) {
      $item = array_pop($list);
      if (isset($item['numeric'])) {
        $current = $item['numeric'];
        if ($current <> '') {
          $next = $current + 1;
          $format= '%1$0'.strlen($current).'d';
          $next = sprintf($format, $next);
        }
      }
    }

    return $next;
  }

  public static function get_content_count($folder=NULL,$future=FALSE,$past=TRUE,$conditions=NULL,$since=NULL,$until=NULL)
  {
      Log::warn("Use of Statamic::get_content_count() is deprecated. Use Content::count() instead.", "core", "Statamic");
      return Content::count($folder, $future, $past, $conditions, $since, $until);

  }

  public static function get_content_all($folder=NULL,$future=FALSE,$past=TRUE,$conditions=NULL,$skip_status=FALSE,$parse=TRUE,$since=NULL,$until=NULL,$location=NULL,$distance_from=NULL)
  {
    $content_type = Config::getContentType();
    $site_root = Config::getSiteRoot();

    $absolute_folder = Path::resolve($folder);

    $posts = self::get_file_list($absolute_folder);
    $list = array();

    // should we factor in location and distance?
    $measure_distance = (!is_null($location) && !is_null($distance_from) && preg_match(Pattern::COORDINATES, $distance_from, $matches));
    if ($measure_distance) {
        $center_point = array($matches[1], $matches[2]);
    }

    foreach ($posts as $key => $post) {
      // starts with numeric value
      unset($list[$key]);

      if ((preg_match(Pattern::DATE, $key) || preg_match(Pattern::NUMERIC, $key)) && File::exists($post.".{$content_type}")) {

        $data = Statamic::get_content_meta($key, $absolute_folder, FALSE, $parse);

        $list[$key] = $data;
        $list[$key]['url']     = $folder ? $site_root.$folder."/".$key : $site_root.$key;
        $list[$key]['raw_url'] = $list[$key]['url'];

        // Clean the folder numbers out
        $list[$key]['url'] =  Path::clean($list[$key]['url']);

        # Set status and "raw" slug
        if (substr($key, 0, 2) === "__") {
            $list[$key]['status'] = 'draft';
            $list[$key]['slug'] = substr($key, 2);
        } elseif (substr($key, 0, 1) === "_") {
            $list[$key]['status'] = 'hidden';
            $list[$key]['slug'] = substr($key, 1);
        } else {
            $list[$key]['slug'] = $key;
        }

        $slug = $list[$key]['slug'];

        $date_entry = FALSE;
        if (Config::getEntryTimestamps() && Slug::isDateTime($slug)) {
          $datestamp = Slug::getTimestamp($key);
          $date_entry = TRUE;

          # strip the date

          $list[$key]['slug'] = preg_replace(Pattern::DATETIME, '', $slug);
          $list[$key]['url']  = preg_replace(Pattern::DATETIME, '', $list[$key]['url']); #override

          $list[$key]['datestamp'] = $data['datestamp'];
          $list[$key]['date'] = $data['date'];

        } elseif (Slug::isDate($slug)) {
          $datestamp = Slug::getTimestamp($slug);
          $date_entry = TRUE;

          # strip the date
          // $list[$key]['slug'] = substr($key, 11);
          $list[$key]['slug'] = preg_replace(Pattern::DATE, '', $slug);

          $list[$key]['url']  = preg_replace(Pattern::DATE, '', $list[$key]['url']); #override

          $list[$key]['datestamp'] = $data['datestamp'];
          $list[$key]['date'] = $data['date'];

        } else {
          $list[$key]['slug'] = preg_replace(Pattern::NUMERIC, '', $slug);
          $list[$key]['url']  = preg_replace(Pattern::NUMERIC, '', $list[$key]['url'], 1); #override
        }

        $list[$key]['url'] = Path::tidy('/'.$list[$key]['url']);

        # fully qualified url
        $list[$key]['permalink'] = Path::tidy(Config::getSiteURL().'/'.$list[$key]['url']);

        /* $content  = preg_replace('/<img(.*)src="(.*?)"(.*)\/?>/', '<img \/1 src="'.Statamic::get_asset_path(null).'/\2" /\3 />', $data['content']); */
        //$list[$key]['content'] = Statamic::transform_content($data['content']);

        // distance
        if (isset($list[$key][$location]['latitude']) && $list[$key][$location]['latitude'] && isset($list[$key][$location]['longitude']) && $list[$key][$location]['longitude']) {
            $list[$key]['coordinates'] = $list[$key][$location]['latitude'] . "," . $list[$key][$location]['longitude'];
        }

        if ($measure_distance && is_array($center_point)) {
            if (!isset($list[$key][$location]) || !is_array($list[$key][$location])) {
                unset($list[$key]);
            }

            if (isset($list[$key][$location]['latitude']) && $list[$key][$location]['latitude'] && isset($list[$key][$location]['longitude']) && $list[$key][$location]['longitude']) {
                $list[$key]['distance_km'] = Statamic_Helper::get_distance_in_km($center_point, array($list[$key][$location]['latitude'], $list[$key][$location]['longitude']));
                $list[$key]['distance_mi'] = Statamic_Helper::convert_km_to_miles($list[$key]['distance_km']);
            } else {
                unset($list[$key]);
            }
        }


        if (! $skip_status) {
          if (isset($data['status']) && $data['status'] != 'live') {
            unset($list[$key]);
          }
        }

        // Remove future entries
        if ($date_entry && $future === FALSE && $datestamp > time()) {
          unset($list[$key]);
        }

        // Remove past entries
        if ($date_entry && $past === FALSE && $datestamp < time()) {
          unset($list[$key]);
        }

        // Remove entries before $since
        if ($date_entry && !is_null($since) && $datestamp < strtotime($since)) {
          unset($list[$key]);
        }

        // Remove entries after $until
        if ($date_entry && !is_null($until) && $datestamp > strtotime($until)) {
          unset($list[$key]);
        }

        if ($conditions) {
          $keepers = array();
          $conditions_array = explode(",", $conditions);
          foreach ($conditions_array as $condition) {
            $condition = trim($condition);
            $inclusive = TRUE;

            list($condition_key, $condition_values) = explode(":", $condition);

            # yay php!
            $pos = strpos($condition_values, 'not ');
            if ($pos === FALSE) {
            } else {
              if ($pos == 0) {
                $inclusive = FALSE;
                $condition_values = substr($condition_values, 4);
              }
            }

            $condition_values = explode("|", $condition_values);

            foreach ($condition_values as $k => $condition_value) {
              $keep = FALSE;
              if (isset($list[$key][$condition_key])) {
                if (is_array($list[$key][$condition_key])) {
                  foreach ($list[$key][$condition_key] as $key2 => $value2) {
                    #todo add regex driven taxonomy matching here

                    if ($inclusive) {

                      if (strtolower($value2['name']) == strtolower($condition_value)) {
                        $keepers[$key] = $key;
                        break;
                      }
                    } else {

                      if (strtolower($value2['name']) != strtolower($condition_value)) {
                        $keepers[$key] = $key;
                      } else {
                        // EXCLUDE!
                        unset($keepers[$key]);
                        break;
                      }
                    }
                  }
                } else {
                  if ($list[$key][$condition_key] == $condition_value) {
                    if ($inclusive) {
                      $keepers[$key] = $key;
                    } else {
                      unset($keepers[$key]);
                    }

                 } else {
                    if (! $inclusive) {
                      $keepers[$key] = $key;
                    }
                  }
                }
              } else {
                $keep = FALSE;
              }
            }
            if ( ! $keep && ! in_array($key, $keepers)) {
              unset($list[$key]);
            }
          }
        }
      }
    }

    return $list;
  }


  public static function get_content_tree($directory='/',$depth=1,$max_depth=5,$folders_only=FALSE,$include_entries=FALSE,$hide_hidden=TRUE,$include_content=FALSE,$site_root=FALSE)
  {
    // $folders_only = true only page.md
    // folders_only = false includes any numbered or non-numbered page (excluding anything with a fields.yaml file)
    // if include_entries is true then any numbered files are included

    $content_root = Config::getContentRoot();
    $content_type = Config::getContentType();
    $site_root = $site_root ? $site_root : Config::getSiteRoot();

    $current_url = Path::tidy($site_root.'/'.Request::getResourceURI());

    $taxonomy_url = FALSE;
    if (Taxonomy::isTaxonomyURL($current_url)) {
      list($taxonomy_type, $taxonomy_name) = Taxonomy::getCriteria($current_url);
      $taxonomy_url = self::remove_taxonomy_from_path($current_url, $taxonomy_type, $taxonomy_name);
    }

    $directory = '/'.$directory.'/'; #ensure proper slashing

    if ($directory <> '/') {
      $base = Path::tidy("{$content_root}/{$directory}");
    } elseif ($directory == '/') {
      $base = "{$content_root}";
    } else {
      $base = "{$content_root}";
    }

    $files = glob("{$base}/*");


    $data = array();
    if ($files) {
      foreach ($files as $path) {
        $current_name = basename($path);

        if (!Pattern::endsWith($current_name, '.yaml')) {

          // Hidden page that should be removed
          if ($hide_hidden && Pattern::startsWith($current_name, '_')) continue;

          $node = array();
          $file = substr($path, strlen($base)+1, strlen($path)-strlen($base)-strlen($content_type)-2);

          if (is_dir($path)) {
            $folder = substr($path, strlen($base)+1);
            $node['type']     = 'folder';
            $node['slug']     = basename($folder);
            $node['title']    = ucwords(basename($folder));

            $node['numeric']  = Slug::getOrderNumber($folder);

            $node['file_path'] = Path::tidy($site_root.'/'.$directory.'/'.$folder.'/page');

            if (Slug::isNumeric($folder)) {
              $pos = strpos($folder, ".");
              if ($pos !== FALSE) {
                $node['raw_url'] = Path::tidy(Path::clean($site_root.'/'.$directory.'/'.$folder));
                $node['url']     = Path::clean($node['raw_url']);
                $node['title']   = ucwords(basename(substr($folder, $pos+1)));
              } else {
                $node['title']   = ucwords(basename($folder));
                $node['raw_url'] = Path::tidy($site_root.'/'.$directory.'/'.$folder);
                $node['url']     = Path::clean($node['raw_url']);
              }
            } else {
              $node['title']   = ucwords(basename($folder));
              $node['raw_url'] = Path::tidy($site_root.'/'.$directory.'/'.$folder);
              $node['url']     = Path::clean($node['raw_url']);
            }

            $node['depth']    = $depth;
            $node['children'] = $depth < $max_depth ? self::get_content_tree($directory.$folder.'/', $depth+1, $max_depth, $folders_only, $include_entries, $hide_hidden, $include_content, $site_root) : NULL;
            $node['is_current'] = $node['raw_url'] == $current_url || $node['url'] == $current_url ? TRUE : FALSE;

            $node['is_parent'] = FALSE;
            if ($node['url'] == URL::popLastSegment($current_url) || ($taxonomy_url && $node['url'] == $taxonomy_url)) {
              $node['is_parent'] = TRUE;
            }

            $node['has_children'] = $node['children'] ? TRUE : FALSE;

            // has entries?
            if (File::exists(Path::tidy($path."/fields.yaml"))) {
              $node['has_entries'] = TRUE;
            } else {
              $node['has_entries'] = FALSE;
            }

            $meta = self::get_content_meta("page", Path::tidy($directory."/".$folder), FALSE, TRUE);
            //$meta = self::get_content_meta("page", Statamic_Helper::reduce_double_slashes($directory."/".$folder));

            if (isset($meta['title'])) {
              $node['title'] = $meta['title'];
            }

            if (isset($meta['last_modified'])) {
              $node['last_modified'] = $meta['last_modified'];
            }

            if ($hide_hidden === TRUE && (isset($meta['status']) && (($meta['status'] == 'hidden' || $meta['status'] == 'draft')))) {
              // placeholder condition
            } else {
              $data[] = $include_content ? array_merge($meta, $node) : $node;
              // print_r($data);
            }

          } else {
            if (Pattern::endsWith($path, $content_type)) {
              if ($folders_only == FALSE) {
                if ($file == 'page' || $file == 'feed' || $file == '404') {
                  // $node['url'] = $directory;
                  // $node['title'] = basename($directory);

                  // $meta = self::get_content_meta('page', substr($directory, 1));
                  // $node['depth'] = $depth;
                } else {
                  $include = TRUE;

                  // date based is never included
                  if (Config::getEntryTimestamps() && Slug::isDateTime(basename($path))) {
                    $include = FALSE;
                  } elseif (Slug::isDate(basename($path))) {
                      $include = FALSE;
                  } elseif (Slug::isNumeric(basename($path))) {
                    if ($include_entries == FALSE) {
                      if (File::exists(Path::tidy(dirname($path)."/fields.yaml"))) {
                        $include = FALSE;
                      }
                    }
                  }

                  if ($include) {
                    $node['type'] = 'file';
                    $node['raw_url'] = Path::tidy($directory).basename($path);

                    $pretty_url = Path::clean($node['raw_url']);
                    $node['url'] = substr($pretty_url, 0, -1*(strlen($content_type)+1));
                    $node['is_current'] = $node['url'] == $current_url || $node['url'] == $current_url ? TRUE : FALSE;

                    $node['slug'] = substr(basename($path), 0, -1*(strlen($content_type)+1));

                    $meta = self::get_content_meta(substr(basename($path), 0, -1*(strlen($content_type)+1)), substr($directory, 1), FALSE, TRUE);

                    //$node['meta'] = $meta;

                    if (isset($meta['title'])) $node['title'] = $meta['title'];
                    $node['depth'] = $depth;

                    if ($hide_hidden === TRUE && (isset($meta['status']) && (($meta['status'] == 'hidden' || $meta['status'] == 'draft')))) {
                    } else {
                      $data[] = $include_content ? array_merge($meta, $node) : $node;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    return $data;
  }


  public static function get_listings()
  {
    $listings = array();
    $finder = new Finder();
    $files  = $finder->files()
      ->in(Config::getContentRoot())
      ->name('fields.yaml')
      ->followLinks();

    foreach ($files as $file) {
      $slug = str_replace(Config::getContentRoot().'/', '', $file->getPath());

      $meta = array(
        'slug' => $slug,
        'title' => ucwords(Slug::humanize(Path::clean($slug)))
      );

      $item = self::yamlize_content(BASE_PATH . '/' . $file->getPath().'/page.' . Config::getContentType());

      // if (is_array($item)) {
        $listings[] = array_merge($meta, $item);
      // }
    }

    // Sort by Title
    uasort($listings, function($a, $b) {
      return strcmp($a['title'], $b['title']);
    });

    return $listings;
  }


  public static function get_file_list($directory=NULL)
  {
    $content_root = Config::getContentRoot();
    $content_type = Config::getContentType();

    if ($directory) {
      $files = glob("{$content_root}{$directory}/*.{$content_type}");
    } else {
      $files = glob('{$content_root}*.{$content_type}');
    }
    $posts = array();

    if ($files) {
      foreach ($files as $file) {
        $len = strlen($content_type);
        $len = $len + 1;
        $len = $len * -1;

        $key = substr(basename($file), 0, $len);
        // Statamic_helper::reduce_double_slashes($key = '/'.$key);
        $posts[$key] = substr($file, 0, $len);
      }
    }

    return $posts;
  }

  public static function find_relative($current, $folder=null, $future=false, $past=true, $show_hidden=false)
  {
      $content_set = ContentService::getContentByFolders($folder);
      $content_set->filter(array(
          'show_hidden'  => $show_hidden,
          'show_drafts'  => false,
          'show_future'  => $future,
          'show_past'    => $past,
          'type'         => 'entries'
      ));

      $content_set->sort();
      $content = $content_set->get(false, false);

      $relative = array(
          'prev' => null,
          'next' => null
      );

      $use_next = false;
      $prev = false;
      foreach ($content as $data) {
          // find previous
          if (!$prev && $current != $data['url']) {
              $relative['prev'] = $data['url'];
              continue;
          }

          // we have found the current url
          // set the currently-set previous url to be `prev`
          // and mark the next iteration to use its value as `next`
          if ($current == $data['url']) {
              $prev = true;
              $use_next = true;
              continue;
          }

          // we should use this url as `next`
          if ($use_next) {
              $relative['next'] = $data['url'];
              break;
          }
      }

      return $relative;
  }

  public static function get_asset_path($asset)
  {
    $content_root = Config::getContentRoot();

    return "{$content_root}".Request::getResourceURI().''.$asset;
  }

  public static function parse_content($template_data, $data, $type=NULL)
  {
      Log::warn("Use of Statamic::parse_content() is deprecated. Use Content::parse() instead.", "core", "Statamic");
      return Content::parse($template_data, $data, $type);
  }

  public static function yamlize_content($meta_raw, $content_key = 'content')
  {
    if (File::exists($meta_raw)) {
      $meta_raw = File::get($meta_raw);
    }

    if (Pattern::endsWith($meta_raw, "---")) {
      $meta_raw .= "\n"; # prevent parse failure
    }

    // Parse YAML Front Matter
    if (strpos($meta_raw, "---") === FALSE) {
      $meta = YAML::parse($meta_raw);
      $meta['content'] = "";
    } else {

      list($yaml, $content) = preg_split("/---/", $meta_raw, 2, PREG_SPLIT_NO_EMPTY);
      $meta = YAML::parse($yaml);
      $meta[$content_key.'_raw'] = trim($content);
      $meta[$content_key] = Content::transform($content);

      return $meta;
    }
  }

  public static function transform_content($content, $content_type=NULL)
  {
      Log::warn("Use of Statamic::transform_content() is deprecated. Use Content::render() instead.", "core", "Statamic");
      return Content::transform($content, $content_type);
  }

  public static function is_taxonomy($tax)
  {
      Log::warn("Use of Statamic::is_taxonomy() is deprecated. Use Taxonomy::isTaxonomy() instead.", "core", "Statamic");
      return Taxonomy::isTaxonomy($tax);
  }

  public static function is_taxonomy_url($path)
  {
      Log::warn("Use of Statamic::is_taxonomy_url() is deprecated. Use Taxonomy::isTaxonomyURL() instead.", "core", "Statamic");
      return Taxonomy::isTaxonomyURL($path);
  }

  public static function get_taxonomy_criteria($path)
  {
      Log::warn("Use of Statamic::get_taxonomy_criteria() is deprecated. Use Taxonomy::getCriteria() instead.", "core", "Statamic_Helper");
      return Taxonomy::getCriteria($path);
  }

  public static function remove_taxonomy_from_path($path, $type, $slug)
  {
    return substr($path, 0, -1 * strlen("/{$type}/{$slug}"));
  }

  public static function detect_environment(array $environments, $uri)
  {
      Log::warn("Use of Statamic::detect_environment() is deprecated. Use Environment::detect() instead.", "core", "Statamic_Helper");
      return Environment::detect();
  }
}

function statamic_sort_by_title($a, $b)
{
  return strcmp($a['title'], $b['title']);
}

function statamic_sort_by_field($field, $a, $b)
{
  if (isset($a[$field]) && isset($b[$field])) {
    return strcmp($a[$field], $b[$field]);
  } else {
    return strcmp($a['title'], $b['title']);
  }
}

function statamic_sort_by_datetime($a, $b)
{
  if (isset($a['datetimestamp']) && isset($b['datetimestamp'])) {
    return $a['datetimestamp'] - $b['datetimestamp'];
  } elseif (isset($a['datestamp']) && isset($b['datestamp'])) {
    return $a['datestamp'] - $b['datestamp'];
  }
}

function statamic_sort_by_distance($a, $b) {
  if (isset($a['distance_km']) && isset($b['distance_km'])) {
      if ($a['distance_km'] < $b['distance_km']) {
          return -1;
      } elseif ($a['distance_km'] > $b['distance_km']) {
          return 1;
      }

      return 0;
  }
}