<?php
/**
 * Resource
 * Interact with resources
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Resource
{
    const PLUGIN     = 1;
    const FIELDTYPE  = 2;
    const HOOKS      = 4;
    const TASKS      = 8;
    const MODIFIER   = 16;
    const API        = 32;
    
    
    /**
     * Attempts to load a modifier
     * 
     * @param string  $name  Name of modifier to load
     * @return Modifier
     * @throws Exception
     */
    public static function loadModifier($name)
    {        
        return self::loadAddonResource(self::MODIFIER, $name);
    }
    
    
    /**
     * Attempts to load a plugin
     * 
     * @param string  $name  Name of plugin to load
     * @return Plugin
     * @throws Exception
     */
    public static function loadPlugin($name)
    {        
        return self::loadAddonResource(self::PLUGIN, $name);
    }
    
    
    /**
     * Attempts to load a fieldtype
     * 
     * @param string  $name  Name of fieldtype to load
     * @return Plugin
     * @throws Exception
     */
    public static function loadFieldtype($name)
    {        
        return self::loadAddonResource(self::FIELDTYPE, $name);
    }
    
    
    /**
     * Attempts to load hooks
     * 
     * @param string  $name  Name of hooks to load
     * @return Hooks
     * @throws Exception
     */
    public static function loadHooks($name)
    {        
        return self::loadAddonResource(self::HOOKS, $name);
    }
    
    
    /**
     * Attempts to load tasks
     * 
     * @param string  $name  Name of tasks to load
     * @return Tasks
     * @throws Exception
     */
    public static function loadTasks($name)
    {        
        return self::loadAddonResource(self::TASKS, $name);
    }
    
    
    /**
     * Attempts to load API
     * 
     * @param string  $name  Name of API to load
     * @return Tasks
     * @throws Exception
     */
    public static function loadAPI($name)
    {        
        return self::loadAddonResource(self::API, $name);
    }
    
    
    /**
     * Attempts to load an add-on file
     * 
     * @param integer  $type  Type of add-on file to load
     * @param string  $addon  Add-on to load
     * @return Addon
     * @throws Exception
     */
    public static function loadAddonResource($type, $addon)
    {
        $folders  = Config::getAddOnLocations();
        $file     = null;
        $type_map = array(
            self::PLUGIN => array(
                'abbreviation' => 'pi',
                'name' => 'plugin'
            ),
            self::FIELDTYPE => array(
                'abbreviation' => 'ft',
                'name' => 'fieldtype'
            ),
            self::HOOKS => array(
                'abbreviation' => 'hooks',
                'name' => 'hooks'
            ),
            self::TASKS => array(
                'abbreviation' => 'tasks',
                'name' => 'tasks'
            ),
            self::MODIFIER => array(
                'abbreviation' => 'mod',
                'name' => 'modifier'
            ),
            self::API => array(
                'abbreviation' => 'api',
                'name' => 'API'
            )
        );
        
        if (!isset($type_map[$type])) {
            Log::error("Unknown add-on type.", "API", "Resource");
            throw new Exception("Unknown add-on type.");
        }
        
        // grab the abbreviation and name
        $addon_details = $type_map[$type];
        $abbr = $addon_details['abbreviation'];
        $name = $addon_details['name'];

        // loop through folders looking for addon
        foreach ($folders as $folder) {
            if (Folder::exists($folder.$addon) && File::exists($folder.$addon.'/'.$abbr.'.'.$addon.'.php')) {
                $file = $folder.$addon.'/'.$abbr.'.'.$addon.'.php';
                break;
            }
        }

        if (!$file) {
            Log::error("Could not find files to load the `{$addon}` {$name}.", "API", "Resource");
            throw new Exception("Could not find files to load the `{$addon}` {$name}.");
        }

        $class = ucwords($name) . "_" . $addon;

        if (!class_exists($class)) {
            throw new Exception("Improperly formatted {$name} object.");
        }

        return new $class();
    }
}