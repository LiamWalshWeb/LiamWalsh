<?php
use Symfony\Component\Finder\Finder as Finder;

/**
 * Hook
 * API for hooking into events triggered by the site
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Hook
{
    private static $hooks = null;
    private static $hooks_loaded = false;
    
    /**
     * Run the instance of a given hook
     *
     * @param string  $namespace  The namespace (addon/aspect) calling the hook
     * @param string  $hook       Name of hook
     * @param string  $type       Cumulative/replace/call
     * @param mixed   $return     Pass-through values
     * @param mixed   $data       Data to pass to hooked method
     * @return mixed
     */
    public static function run($namespace, $hook, $type = NULL, $return = NULL, $data = NULL)
    {
        // check to see if this hook is enabled
        if (!Hook::isEnabled($namespace, $hook)) {
            return $return;
        }
        
        // @Todo: Clean this up globally
        $addons_path = BASE_PATH.Config::getAddOnsPath();

        if (Folder::exists($addons_path) && Folder::exists(APP_PATH . '/core/bundles')) {

            $finder = new Finder();

            $files = $finder->files()
                ->in($addons_path)
                ->in(APP_PATH . '/core/bundles')
                ->depth('<3')
                ->name("hooks.*.php")
                ->followLinks();

            foreach ($files as $file) {
                if (!is_callable(array('Hooks_' . $file->getRelativePath(), $namespace . '__' . $hook), false)) {
                    continue;
                }

                $class_name = 'Hooks_' . $file->getRelativePath();
                $hook_class = new $class_name();

                $method = $namespace . '__' . $hook;

                if ($type == 'cumulative') {
                    $response = $hook_class->$method($data);
                    if (is_array($response)) {
                        $return = is_array($return) ? $return + $response : $response;
                    } else {
                        $return .= $response;
                    }
                } elseif ($type == 'replace') {
                    $return = $hook_class->$method($data);
                } else {
                    $hook_class->$method($data);
                }
            }
        } else {
            Log::error('Add-ons path not found', 'hooks');
        }

        return $return;
    }
    
    
    /**
     * Checks to see if a given hook is enabled
     * 
     * @param string  $namespace  Namespace that hook exists in
     * @param string  $hook  Hook to check
     * @return bool
     */
    public static function isEnabled($namespace, $hook)
    {
        if (!self::$hooks_loaded) {
            self::$hooks_loaded = true;
            self::$hooks = Config::get('_enable_hooks');
        }
        
        // always allow control panel hooks
        if (substr($namespace, 0, 1) !== "_") {
            return true;
        }
        
        if (isset(self::$hooks[$namespace]) && is_array(self::$hooks[$namespace]) && isset(self::$hooks[$namespace][$hook])) {
            return (bool) self::$hooks[$namespace][$hook];
        }
        
        return false;
    }
}
