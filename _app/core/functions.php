<?php
/**
 * @todo merge this file with Helper
 */
/**
 * Get an item from an array using "colon" notation.
 *
 * <code>
 *    // Get the $array['user']['name'] value from the array
 *    $name = array_get($array, 'user:name');
 *
 *    // Return a default from if the specified item doesn't exist
 *    $name = array_get($array, 'user:name', 'Batman');
 * 
 *    // Return the first found key, otherwise a default
 *    $name = array_get($array, array('user:name', 'user:first_name'), 'Bruce');
 * </code>
 *
 * @param  array   $array
 * @param  string|array  $key
 * @param  mixed   $default
 * @return mixed
 */
function array_get($array, $key, $default = null) {
    if (is_null($key)) return $array;
    $keys = Helper::ensureArray($key);
    
    // short-circuit
    if (!is_array($array)) {
        return Helper::resolveValue($default);
    }
    
    // a flag to remember whether something has been found or not
    $found = false;

    // To retrieve the array item using dot syntax, we'll iterate through
    // each segment in the key and look for that value. If it exists, we
    // will return it, otherwise we will set the depth of the array and
    // look for the next segment.
    foreach ($keys as $key) {
        foreach (explode(':', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                // did we not find something? mark `found` as `false`
                $found = false;
                break;
            }

            // we found something, although not sure if this is the last thing,
            // mark `found` as `true` and let the outer loop handle it if this
            // *is* the last thing in the list
            $found = true;
            $array = $array[$segment];
        }
        
        // if `found` is `true`, the inner loop found something worth returning,
        // which means that we're done here
        if ($found) {
            break;
        }
    }
    
    if ($found) {
        // `found` is `true`, we found something, return that
        return $array;
    } else {
        // `found` isn't `true`, return the default
        return Helper::resolveValue($default);
    }
}


/**
 * Set an array item to a given value using "colon" notation.
 *
 * If no key is given to the method, the entire array will be replaced.
 *
 * <code>
 *    // Set the $array['user']['name'] value on the array
 *    array_set($array, 'user:name', 'Batman');
 *
 *    // Set the $array['user']['name']['first'] value on the array
 *    array_set($array, 'user:name:first', 'Bruce');
 * </code>
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $value
 * @return void
 */
function array_set(&$array, $key, $value)
{
  if (is_null($key)) return $array = $value;

  $keys = explode(':', $key);

  // This loop allows us to dig down into the array to a dynamic depth by
  // setting the array value for each level that we dig into. Once there
  // is one key left, we can fall out of the loop and set the value as
  // we should be at the proper depth.
  while (count($keys) > 1)
  {
    $key = array_shift($keys);

    // If the key doesn't exist at this depth, we will just create an
    // empty array to hold the next value, allowing us to create the
    // arrays to hold the final value.
    if ( ! isset($array[$key]) or ! is_array($array[$key]))
    {
      $array[$key] = array();
    }

    $array =& $array[$key];
  }

  $array[array_shift($keys)] = $value;
}

/**
 * Dump the given value and kill the script.
 *
 * @param  mixed  $value
 * @return void
 */
function dd($value)
{
  d($value);
  die;
}

/**
 * Print_r the given value and kill the script.
 *
 * @param  mixed  $value
 * @return void
 */
function rd($value)
{
  r($value);
  die;
}

/**
 * Print_r with pre tags
 * 
 * @param mixed $value
 * @return void
 */
function r($value){
    echo "<pre>";
    print_r($value);
    echo "</pre>";
}

/**
 * Dump with pre tags
 * 
 * @param mixed $value
 * @return void
 */
function d($value){
    echo "<pre>";
    var_dump($value);
    echo "</pre>";
}


/**
 * Autoload core classes from Statamic
 * 
 * @param string  $class  Name of the class to load  
 * @return bool
 */
function autoload_statamic($class) {
    // define a list of possible locations
    $locations = array(
        APP_PATH . '/core/api/',
        APP_PATH . '/core/extend/',
        APP_PATH . '/core/content/',
        APP_PATH . '/core/members/',
        APP_PATH . '/core/',
        APP_PATH . '/core/bundles/',
        BASE_PATH . '/_add-ons/'
    );
    
    $addon_types = array(
        'Plugin'    => 'pi',
        'Fieldtype' => 'ft',
        'Modifier'  => 'mod',
        'Hooks'     => 'hooks',
        'Tasks'     => 'tasks',
        'API'       => 'api'
    );
    
    // loop through known locations looking for files
    foreach ($locations as $location) {
        if (file_exists($location . strtolower($class) . ".php")) {
            require_once $location . strtolower($class) . ".php";
            return true;
        } elseif (file_exists($location . ucwords($class) . ".php")) {
            require_once $location . "Statamic_" . $class . ".php";
            return true;
        } elseif (file_exists($location . strtolower(str_replace("Statamic_", "", $class)) . ".php")) {
            require_once $location . strtolower(str_replace("Statamic_", "", $class)) . ".php";
            return true;
        } elseif (strpos($class, "_") !== false) {
            list($addon_type, $addon_name) = explode("_", $class, 2);
            
            if (!isset($addon_types[$addon_type])) {
                continue;
            } elseif (file_exists($location . $addon_name . "/" . $addon_types[$addon_type] . "." . $addon_name . ".php")) {
                require_once $location . $addon_name . "/" . $addon_types[$addon_type] . "." . $addon_name . ".php";
                return true;
            }
        }
    }
}
