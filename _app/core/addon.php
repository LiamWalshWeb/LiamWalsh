<?php
/**
 * Addon
 * Abstract implementation for extending Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 *
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
use Symfony\Component\Finder\Finder as Finder;

abstract class Addon
{
    /**
     * Contextual log object for this add-on
     * @public ContextualLog
     */
    public $log;

    /**
     * Contextual session object for this add-on
     * @public ContextualSession
     */
    public $session;

    /**
     * Contextual cache object for this add-on
     * @public ContextualCache
     */
    public $cache;

    /**
     * Related tasks object if it exists
     * @public Tasks
     */
    public $tasks;

    /**
     * Name of Addon
     * @public string
     */
    public $addon_name;

    /**
     * Type of Addon
     * @public string
     */
    public $addon_type;

    /**
     * Path to Addon in filesystem
     * @public string
     */
    public $addon_path;

    /**
     * Array of settings from this add-ons config
     * @public array
     */
    public $config;

    /**
     * Should we skip loading tasks? Only for the Tasks object
     * @public boolean
     */
    public $skip_tasks = false;


    /**
     * Initializes object
     *
     * @return Addon
     */
    public function __construct()
    {
        $this->addon_name = $this->getAddonName();
        $this->addon_type = $this->getAddonType();
        $this->addon_path = Path::tidy(Config::getSiteRoot() . Config::getAddonPath($this->addon_name));
        $this->config     = $this->getConfig();
        $this->tasks      = (!$this->skip_tasks) ? $this->getTasks() : null;

        // contextual objects
        $this->log        = ContextualLog::create($this);
        $this->session    = ContextualSession::create($this);
        $this->cache      = ContextualCache::create($this);
        $this->css        = ContextualCSS::create($this);
        $this->js         = ContextualJS::create($this);
        $this->assets     = ContextualAssets::create($this);
    }


    /**
     * Retrieves the name of this Add-on
     *
     * @return string
     */
    private function getAddonName()
    {
        return ltrim(strstr(get_called_class(), '_'), '_');
    }


    /**
     * Retrieves the type of this Add-on
     *
     * @return string
     */
    private function getAddonType()
    {
        return strtolower(substr(get_called_class(), 0, strpos(get_called_class(), '_')));
    }


    /**
     * Retrieves the Task object for this add-on
     *
     * @return Tasks|null
     */
    private function getTasks()
    {
        // only do this for non-Tasks objects
        if ($this->addon_type == "Tasks") {
            return null;
        }

        // check that a task file exists
        if (!File::exists(BASE_PATH . Config::getAddOnPath($this->addon_name) . "/tasks." . $this->addon_name . ".php")) {
            return null;
        }

        // make sure that the tasks file is loaded
        require_once BASE_PATH . Config::getAddOnPath($this->addon_name) . "/tasks." . $this->addon_name . ".php";

        $class_name = "Tasks_" . $this->addon_name;
        return new $class_name();
    }


    /**
     * Retrieves the config file for this Add-on
     *
     * @return mixed
     */
    public function getConfig()
    {
        if (File::exists($file = Config::getConfigPath() . '/add-ons/' . $this->addon_name . '/' . $this->addon_name . '.yaml')) {
            return YAML::parseFile($file);
        } elseif (File::exists($file = Config::getConfigPath() . '/add-ons/' . $this->addon_name . '.yaml')) {
            return YAML::parseFile($file);
        }

        return null;
    }


    /**
     * Fetches a value from the configuration
     *
     * @param string  $key  Key of value to retrieve
     * @param mixed  $default  Default value if no value is found
     * @param string  $validity_check  Allows a boolean callback function to validate parameter
     * @param boolean  $is_boolean  Indicates parameter is boolean
     * @param boolean  $force_lower  Force the parameter's value to be lowercase?
     * @return mixed
     */
    protected function fetchConfig($key, $default=null, $validity_check=null, $is_boolean=false, $force_lower=true)
    {
        if (isset($this->config[$key])) {
            $value = ($force_lower) ? strtolower($this->config[$key]) : $this->config[$key];

            if (is_null($validity_check) || (!is_null($validity_check) && function_exists($validity_check) && $validity_check($value) === true)) {
                // account for yes/no parameters
                if ($is_boolean === true) {
                    return !in_array(strtolower($value), array("no", "false", "0", "", "-1"));
                }

                // otherwise, standard return
                return $value;
            }
        }

        return $default;
    }


    /**
     * Gets the full absolute path to a given CSS $file
     *
     * @param string  $file  CSS file to find
     * @return string
     */
    public function getCSS($file)
    {
        $this->log->warn('Use of $this->getCSS() is deprecated. Use $this->css->get() instead.');
        return $this->css->get($file);
    }


    /**
     * Gets the full absolute path to a given JavaScript $file
     *
     * @param string  $file  JavaScript file to find
     * @return string
     */
    public function getJS($file)
    {
        $this->log->warn('Use of $this->getJS() is deprecated. Use $this->js->get() instead.');
        return $this->js->get($file);
    }


    /**
     * Gets the full absolute path to a given asset $file
     *
     * @param string  $file  Asset file to find
     * @return string
     */
    public function getAsset($file)
    {
        $this->log->warn('Use of $this->getAsset() is deprecated. Use $this->assets->get() instead.');
        return $this->assets->get($file);
    }


    /**
     * Creates calls to a list of given stylesheets
     *
     * @param mixed  $stylesheet  Single or multiple stylesheets
     * @return string
     */
    public function includeCSS($stylesheet)
    {
        $this->log->warn('Use of $this->includeCSS() is deprecated. Use $this->css->link() instead.');
        return $this->css->link($stylesheet);
    }


    /**
     * Creates calls to a list of given javascript scripts
     *
     * @param mixed  $script  Single or multiple scripts
     * @return string
     */
    public function includeJS($script)
    {
        $this->log->warn('Use of $this->includeJS() is deprecated. Use $this->js->link() instead.');
        return $this->css->link($script);
    }


    /**
     * Creates an inline JavaScript block
     *
     * @param mixed  $javascript  JavaScript to put within block
     * @return string
     */
    public function inlineJS($javascript)
    {
        $this->log->warn('Use of $this->inlineJS() is deprecated. Use $this->js->inline() instead.');
        return $this->css->inline($javascript);
    }


    /**
     * Creates an inline style block
     *
     * @param mixed  $style  CSS to put within block
     * @return string
     */
    public function inlineCSS($style)
    {
        $this->log->warn('Use of $this->inlineCSS() is deprecated. Use $this->css->inline() instead.');
        return $this->css->inline($style);
    }


    /**
     * Runs a hook for this add-on
     *
     * @param string  $hook  Hook to run
     * @param string  $type  Type of hook to run (cumulative|replace|call)
     * @param mixed  $return  Pass-through values
     * @param mixed  $data  Data to pass to hook method
     * @return mixed
     */
    public function runHook($hook, $type=null, $return=null, $data=null)
    {
        return Hook::run($this->addon_name, $hook, $type, $return, $data);
    }
}


/**
 * ContextualObject
 * An object with the context of a given AddOn
 */
class ContextualObject
{
    /**
     * Context
     * @private Addon
     */
    protected $context;


    /**
     * Initialized object
     *
     * @param Addon  $context  Contact object
     * @return ContextualObject
     */
    public function __construct(Addon $context)
    {
        $this->context = $context;
    }
}


/**
 * ContextualLog
 * Supports logging via an Addon context
 */
class ContextualLog extends ContextualObject
{
    /**
     * Logs a debug message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function debug($message)
    {
        $this->log(Log::DEBUG, $message);
    }


    /**
     * Logs a info message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function info($message)
    {
        $this->log(Log::INFO, $message);
    }


    /**
     * Logs a warn message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function warn($message)
    {
        $this->log(Log::WARN, $message);
    }


    /**
     * Logs a error message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function error($message)
    {
        $this->log(Log::ERROR, $message);
    }


    /**
     * Logs a fatal message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function fatal($message)
    {
        $this->log(Log::FATAL, $message);
    }


    /**
     * Logs a message to the logger with context
     *
     * @param int  $level  Level of message to log
     * @param string  $message  Message to log
     * @return void
     */
    private function log($level, $message)
    {
        switch ($level) {
            case Log::DEBUG:
                Log::debug($message, $this->context->addon_type, $this->context->addon_name);
                break;

            case Log::INFO:
                Log::info($message, $this->context->addon_type, $this->context->addon_name);
                break;

            case Log::WARN:
                Log::warn($message, $this->context->addon_type, $this->context->addon_name);
                break;

            case Log::ERROR:
                Log::error($message, $this->context->addon_type, $this->context->addon_name);
                break;

            default:
                Log::fatal($message, $this->context->addon_type, $this->context->addon_name);
                break;
        }
    }


    /**
     * Creates a new ContextualLog
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualLog
     */
    public static function create(Addon $context)
    {
        return new ContextualLog($context);
    }
}


/**
 * ContextualSession
 * Supports session variables management via a Addon context
 */
class ContextualSession extends ContextualObject
{
    /**
     * Gets the value of a given $key for this plugin's namespace
     *
     * @param string  $key  Key to retrieve
     * @param boolean  $strict  Throw error if doesn't exist?
     * @return mixed
     */
    public function get($key, $strict = false)
    {
        return Session::get($this->context->addon_name, $key, $strict);
    }


    /**
     * Sets the value of a given $key for this plugin's namespace
     *
     * @param string  $key  Key to set
     * @param mixed  $value  Value to set
     * @return void
     */
    public function set($key, $value)
    {
        Session::set($this->context->addon_name, $key, $value);
    }


    /**
     * Unsets all variables in the session within this plugin's namespace
     *
     * @return void
     */
    public function destroy()
    {
        Session::destroy($this->context->addon_name);
    }


    /**
     * Checks to see if a given key exists in the session within this plugin's namespace
     *
     * @param string  $key  Key to check
     * @return boolean
     */
    public function exists($key)
    {
        return Session::isKey($this->context->addon_name, $key);
    }


    /**
     * delete
     * Unsets a given key from this plugin's namespace
     *
     * @param string  $key  Key to unset
     * @return void
     */
    public function delete($key)
    {
        Session::unsetKey($this->context->addon_name, $key);
    }


    /**
     * Creates a new ContextualSession
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualSession
     */
    public static function create(Addon $context)
    {
        return new ContextualSession($context);
    }
}


/**
 * ContextualCache
 * Supports cache file manipulation and maintenance via an Addon context
 */
class ContextualCache extends ContextualObject
{
    /**
     * Contextual path to cache folder
     * @private string
     */
    private $path;


    /**
     * Initializes object
     *
     * @param Addon  $context  Context object
     * @return ContextualCache
     */
    public function __construct(Addon $context)
    {
        $this->path = BASE_PATH . "/_cache/_add-ons/{$context->addon_name}/";
        parent::__construct($context);
    }


    /**
     * Checks to see if a given $filename exists within this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to check for
     * @return boolean
     */
    public function exists($filename)
    {
        $this->isValidFilename($filename);
        return File::exists($this->contextualize($filename));
    }


    /**
     * Gets a file from this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to get
     * @param mixed  $default  Default value to return if no file is found
     * @return mixed
     */
    public function get($filename, $default=null)
    {
        $this->isValidFilename($filename);
        return File::get($this->contextualize($filename), $default);
    }


    /**
     * Gets a file from this plugin's namespaced cache and parses it as YAML
     *
     * @param string  $filename  Name of file to get
     * @param mixed  $default  Default value to return if no file is found, or file is not YAML-parsable
     * @return mixed
     */
    public function getYAML($filename, $default=null)
    {
        $data = $this->get($filename);

        if (!is_null($data)) {
            return YAML::parse($data);
        }

        return $default;
    }


    /**
     * Puts a file to this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to put
     * @param mixed  $content  Content to write to file
     * @return int
     */
    public function put($filename, $content)
    {
        $this->isValidFilename($filename);
        return File::put($this->contextualize($filename), $content);
    }


    /**
     * Parses the given $content array and puts a file to this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to put
     * @param array  $content  Array to parse and write to file
     * @return int
     */
    public function putYAML($filename, Array $content)
    {
        return $this->put($filename, YAML::dump($content));
    }


    /**
     * Appends content to the bottom of a file in this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to use
     * @param mixed  $content  Content to append to file
     * @return boolean
     */
    public function append($filename, $content)
    {
        $this->isValidFilename($filename);
        return File::append($this->contextualize($filename), $content);
    }


    /**
     * Prepends content to the start of a file in this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to use
     * @param mixed  $content  Content to prepend to file
     * @return boolean
     */
    public function prepend($filename, $content)
    {
        $this->isValidFilename($filename);
        return File::prepend($this->contextualize($filename), $content);
    }


    /**
     * Moves a file from one location to another within this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to move
     * @param string  $new_filename  New file name to move it to
     * @return boolean
     */
    public function move($filename, $new_filename)
    {
        $this->isValidFilename($filename);
        $this->isValidFilename($new_filename);
        return File::move($this->contextualize($filename), $this->contextualize($new_filename));
    }


    /**
     * Copies a file from one location to another within this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to copy
     * @param string  $new_filename  New file name to copy it to
     * @return boolean
     */
    public function copy($filename, $new_filename)
    {
        $this->isValidFilename($filename);
        $this->isValidFilename($new_filename);
        return File::copy($this->contextualize($filename), $this->contextualize($new_filename));
    }


    /**
     * Deletes a file from this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to delete
     * @return boolean
     */
    public function delete($filename)
    {
        $this->isValidFilename($filename);
        return File::delete($this->contextualize($filename));
    }


    /**
     * Destroys all content within this plugin's namespaced cache
     *
     * @param string  $folder  Folder within cache to destroy
     * @return void
     */
    public function destroy($folder="")
    {
        $this->isValidFilename($folder);
        Folder::wipe($this->contextualize($folder . "/"));
    }


    /**
     * Retrieves and array of all files within this plugin's namespaced cache
     *
     * @param string  $folder  Folder within cache to limit to
     * @return array
     */
    public function listAll($folder="")
    {
        $path   = $this->contextualize($folder . "/");
        $finder = new Finder();
        $files  = $finder->files()->in($path);
        $output = array();

        foreach ($files as $file) {
            array_push($output, str_replace($path, "", $file));
        }

        return $output;
    }


    /**
     * Gets the age of a given file within this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to check
     * @return mixed
     */
    public function getAge($filename)
    {
        $this->isValidFilename($filename);
        $file = $this->contextualize($filename);
        return ($this->exists($filename)) ? time() - File::getLastModified($file) : false;
    }


    /**
     * Removes all cache files older than a given age in seconds
     *
     * @param int  $seconds  Threshold of seconds for wiping
     * @param string  $folder  Folder to apply wipe to with namespaced cache
     * @return void
     */
    public function purgeOlderThan($seconds, $folder="")
    {
        $this->isValidFilename($folder);

        $path   = $this->contextualize($folder . "/");
        $finder = new Finder();
        $files  = $finder->files()->in($path)->date("<= " . Date::format("F j, Y H:i:s", time() - $seconds));

        foreach ($files as $file) {
            File::delete($file);
        }
    }


    /**
     * Removes all cache files last modified before a given $date
     *
     * @param mixed  $date  Date to use as threshold for deletion
     * @param string  $folder  Folder to apply wipe to with namespaced cache
     * @return void
     */
    public function purgeFromBefore($date, $folder="")
    {
        $this->isValidFilename($folder);

        $path   = $this->contextualize($folder . "/");
        $finder = new Finder();
        $files  = $finder->files()->in($path)->date("< " . Date::format("F j, Y H:i:s", $date));

        foreach ($files as $file) {
            File::delete($file);
        }
    }


    /**
     * Returns the filepath for a given $filename for this plugin's namespaced cache
     *
     * @param string  $filename  File name to use
     * @return string
     */
    private function contextualize($filename)
    {
        return Path::tidy($this->path . $filename);
    }


    /**
     * Checks for a valid filename string
     *
     * @throws Exception
     *
     * @param string  $filename  File name to check
     * @return boolean
     */
    private function isValidFilename($filename)
    {
        if (strpos($filename, "..") !== false) {
            $this->context->log->error("Cannot use cache with path containing two consecutive dots (..).");

            // throw an exception to prevent whatever is happening from happening
            throw new Exception("Cannot use cache with path containing two consecutive dots (..).");
        }

        return true;
    }


    /**
     * Creates a new ContextualCache object
     *
     * @param Addon  $context  Context object
     * @return ContextualCache
     */
    public static function create(Addon $context)
    {
        return new ContextualCache($context);
    }
}



/**
 * ContextualCSS
 * Access CSS via an Addon context
 */
class ContextualCSS extends ContextualObject
{
    /**
     * Returns HTML to include one or more given $stylesheets
     *
     * @param mixed  $stylesheets  Stylesheet(s) to create HTML for
     * @return string
     */
    public function link($stylesheets)
    {


        $files = Helper::ensureArray($stylesheets);
        $html  = '';

        foreach ($files as $file) {
            $html .= HTML::includeStylesheet($this->get($file));
        }

        return $html;
    }


    /**
     * Returns HTML for inline CSS
     *
     * @param string  $css  CSS to return
     * @return string
     */
    public function inline($css)
    {
        return '<style>' . $css . '</style>';
    }


    /**
     * Returns the full path of a given stylesheet
     *
     * @param string  $file  Stylesheet file to find
     * @return string
     */
    public function get($file)
    {
        $file_location = Config::getAddOnPath($this->context->addon_name) . '/';

        if (File::exists(BASE_PATH . $file_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location . $file);
        } elseif (File::exists(BASE_PATH . $file_location . 'css/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'css', $file);
        } elseif ( ! Pattern::endsWith($file, ".css", false)) {
            return $this->get($file . ".css");
        }

        $this->context->log->error("CSS file `" . $file . "` doesn't exist.");
        return "";
    }


    /**
     * Creates a new ContextualCSS
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualCSS
     */
    public static function create(Addon $context)
    {
        return new ContextualCSS($context);
    }
}



/**
 * ContextualJS
 * Access JavaScript via an Addon context
 */
class ContextualJS extends ContextualObject
{
    /**
     * Returns HTML to include one or more given $scripts
     *
     * @param mixed  $scripts  Script(s) to create HTML for
     * @return string
     */
    public function link($scripts)
    {
        $files = Helper::ensureArray($scripts);
        $html  = '';

        foreach ($files as $file) {
            $html .= HTML::includeScript($this->get($file));
        }

        return $html;
    }


    /**
     * Returns HTML for inline JavaScript
     *
     * @param string  $js  JavaScript to return
     * @return string
     */
    public function inline($js)
    {
        return '<script type="text/javascript">' . $js . '</script>';
    }


    /**
     * Returns the full path of a given script
     *
     * @param string  $file  Script file to find
     * @return string
     */
    public function get($file)
    {
        $file_location = Config::getAddOnPath($this->context->addon_name) . '/';

        if (File::exists(BASE_PATH . $file_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location . $file);
        } elseif (File::exists(BASE_PATH . $file_location . 'js/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'js', $file);
        } elseif ( ! Pattern::endsWith($file, ".js", false)) {
            return $this->get($file . ".js");
        }

        $this->context->log->error("JavaScript file `" . $file . "` doesn't exist.");
        return "";
    }


    /**
     * Creates a new ContextualJS
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualJS
     */
    public static function create(Addon $context)
    {
        return new ContextualJS($context);
    }
}



/**
 * ContextualAssets
 * Access assets via an Addon context
 */
class ContextualAssets extends ContextualObject
{
    /**
     * Returns the full path of a given script
     *
     * @param string  $file  Script file to find
     * @return string
     */
    public function get($file)
    {
        $file_location = Config::getAddOnPath($this->context->addon_name) . '/';

        if (File::exists(BASE_PATH . $file_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, $file);
        } elseif (File::exists(BASE_PATH . $file_location . 'assets/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'assets', $file);
        }

        $this->context->log->error("Asset file `" . $file . "` doesn't exist.");
        return "";
    }


    /**
     * Creates a new ContextualAssets
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualAssets
     */
    public static function create(Addon $context)
    {
        return new ContextualAssets($context);
    }
}