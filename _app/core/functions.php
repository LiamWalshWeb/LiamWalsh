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
 * </code>
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function array_get($array, $key, $default = null)
{
  if (is_null($key)) return $array;

  // To retrieve the array item using dot syntax, we'll iterate through
  // each segment in the key and look for that value. If it exists, we
  // will return it, otherwise we will set the depth of the array and
  // look for the next segment.
  foreach (explode(':', $key) as $segment)
  {
    if ( ! is_array($array) or ! array_key_exists($segment, $array))
    {
      return Helper::resolveValue($default);
    }

    $array = $array[$segment];
  }

  return $array;
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
 * Return the value of the given item.
 *
 * If the given item is a Closure the result of the Closure will be returned.
 *
 * @deprecated  Use Helper::resolveValue() instead
 *
 * @param  mixed  $value
 * @return mixed
 */
function value($value)
{
    Log::warn("Use of value() is deprecated. Use Helper::resolveValue() instead.", "core", "Statamic_Helper");
    return Helper::resolveValue($value);
}