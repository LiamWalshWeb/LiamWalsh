<?php
/**
 * Plugin
 * Abstract implementation for creating new tags for Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 *
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
abstract class Plugin extends Addon
{
    /**
     * An array of parameters passed by the user
     * @public array
     */
    public $attributes;

    /**
     * Content between the opening and closing tags of this plugin
     * @public string
     */
    public $content;


    /**
     * Explodes options into an array
     *
     * @deprecated  Use $this->explodeOptions instead
     *
     * @param string  $string  String to explode
     * @param bool $keyed  Are options keyed?
     * @return array
     */
    public function explode_options($string, $keyed = FALSE)
    {
        $this->log->warn('Use of $this->explode_options() is deprecated. Use Helper::explodeOptions() instead.');
        return Helper::explodeOptions($string, $keyed);
    }


    /**
     * Fetch Parameter
     *
     * This method fetches tag parameters if they exist, and returns their value
     * or a given default if not found
     *
     * @deprecated  Use $this->fetchParam() instead
     *
     * @author  Jack McDade
     * @param  string  $param       Parameter to be checked
     * @param  string  $default     Default value
     * @param  boolean $is_valid    Allows a boolean callback function to validate parameter
     * @param  boolean $is_boolean  Indicates parameter is boolean (yes/no)
     * @param  boolean $force_lower Force the parameter's value to be lowercase?
     * @return mixed   Returns the parameter's value if found, default if not, or boolean if yes/no style
     */
    public function fetch_param($param, $default = NULL, $is_valid = FALSE, $is_boolean = FALSE, $force_lower = TRUE)
    {
        $this->log->warn('Use of $this->fetch_param() is deprecated. Use $this->fetchParam() instead.');
        return $this->fetchParam($param, $default, $is_valid, $is_boolean, $force_lower);
    }


    /**
     * Parses through this plugin's content, replacing variables with variables from $data
     *
     * @deprecated  Use Parse::tagLoop() instead
     *
     * @param string  $content  Content template to replace variables in
     * @param array  $data  Associated array of data to replace
     * @return string
     */
    public function parse_loop($content, Array $data)
    {
        $this->log->warn('Use of $this->parse_loop() is deprecated. Use Parse::tagLoop() instead.');
        return Parse::tagLoop($content, $data);
    }


    /**
     * Fetches a value from the user-passed parameters
     *
     * @param string  $key  Key of value to retrieve
     * @param mixed  $default  Default value if no value is found
     * @param string  $validity_check  Allows a boolean callback function to validate parameter
     * @param boolean  $is_boolean  Indicates parameter is boolean
     * @param boolean  $force_lower  Force the parameter's value to be lowercase?
     * @return mixed
     */
    protected function fetchParam($key, $default=NULL, $validity_check=NULL, $is_boolean=FALSE, $force_lower=TRUE)
    {
        if (isset($this->attributes[$key])) {
            $value = ($force_lower) ? strtolower($this->attributes[$key]) : $this->attributes[$key];

            if (!$validity_check || ($validity_check && function_exists($validity_check) && $validity_check($value) === TRUE)) {
                // account for yes/no parameters
                if ($is_boolean === TRUE) {
                    return !in_array(strtolower($value), array("no", "false", "0", "", "-1"));
                }

                // otherwise, standard return
                return $value;
            }
        }

        return $default;
    }


    /**
     * Attempts to fetch a given $key's value from (in order): user-passed parameters, config file, default value
     *
     * @param string  $key  Key of value to retrieve
     * @param mixed  $default  Default value if no value is found
     * @param string  $validity_check  Allows a boolean callback function to validate parameter
     * @param boolean  $is_boolean  Indicates parameter is boolean
     * @param boolean  $force_lower  Force the parameter's value to be lowercase?
     * @return mixed
     */
    protected function fetch($key, $default=NULL, $validity_check=NULL, $is_boolean=FALSE, $force_lower=TRUE)
    {
        return Helper::pick(
            $this->fetchParam($key, NULL, $validity_check, $is_boolean, $force_lower),   // check for user-defined parameters first
            $this->fetchConfig($key, NULL, $validity_check, $is_boolean, $force_lower),  // then config-file definitions section
            $default                                                                     // and finally, the passed default value if none found
        );
    }
}