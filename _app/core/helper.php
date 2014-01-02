<?php
/**
 * Statamic_Helper
 * Provides utility functionality for Statamic
 *
 * @deprecated  Note that all functionality in this file has been deprecated, use _app/core/api instead.
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2012 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class statamic_helper
{
    /**
     * starts_with
     * Determines if a given $haystack starts with $needle
     *
     * @deprecated  Use Pattern::startsWith() instead
     *
     * @param string  $haystack  String to inspect
     * @param string  $needle  Character to look for
     * @return boolean
     */
    public static function starts_with($haystack, $needle)
    {
        Log::warn("Use of Statamic_Helper::starts_with() is deprecated. Use Pattern::startsWith() instead.", "core", "Statamic_Helper");
        return Pattern::startsWith($haystack, $needle);
    }


    /**
     * ends_with
     * Determines if a given $haystack ends with $needle
     *
     * @deprecated  Use Pattern::endsWith() instead
     *
     * @param string  $haystack  String to inspect
     * @param string  $needle  Character to look for
     * @param boolean  $case  Perform a case-sensitive search?
     * @return boolean
     */
    public static function ends_with($haystack, $needle, $case = TRUE)
    {
        Log::warn("Use of Statamic_Helper::ends_with() is deprecated. Use Pattern::endsWith() instead.", "core", "Statamic_Helper");
        return Pattern::endsWith($haystack, $needle, $case);
    }


    /**
     * is_date_slug
     * Determines if a given $slug is date-based and is valid or not
     *
     * @deprecated  Use Slug::isDate() instead
     *
     * @param string  $slug  Slug to inspect
     * @return boolean
     */
    public static function is_date_slug($slug)
    {
        Log::warn("Use of Statamic_Helper::is_date_slug() is deprecated. Use Slug::isDate() instead.", "core", "Statamic_Helper");
        return Slug::isDate($slug);
    }


    /**
     * is_datetime_slug
     * Determines if a given $slug is datetime-based and is valid or not
     *
     * @deprecated  Use Slug::isDateTime() instead
     *
     * @param string  $slug  Slug to inspect
     * @return boolean
     */
    public static function is_datetime_slug($slug)
    {
        Log::warn("Use of Statamic_Helper::is_datetime_slug() is deprecated. Use Slug::isDateTime() instead.", "core", "Statamic_Helper");
        return Slug::isDateTime($slug);
    }


    /**
     * is_valid_date
     * Determines if a given date or datetime represents a real date
     *
     * @deprecated  Use Pattern::isValidDate() instead
     *
     * @param string  $date  A yyyy-mm-dd(-hhii)[-ish] formatted string for checking
     * @return boolean
     */
    public static function is_valid_date($date)
    {
        Log::warn("Use of Statamic_Helper::is_valid_date() is deprecated. Use Pattern::isValidDate() instead.", "core", "Statamic_Helper");
        return Pattern::isValidDate($date);
    }


    /**
     * is_numeric_slug
     * Determines if a given $slug is numeric-based or not
     *
     * @deprecated  Use Slug::isNumeric() instead
     *
     * @param string  $slug  Slug to inspect
     * @return boolean
     */

    public static function is_numeric_slug($slug)
    {
        Log::warn("Use of Statamic_Helper::is_numeric_slug() is deprecated. Use Slug::isNumeric() instead.", "core", "Statamic_Helper");
        return Slug::isNumeric($slug);
    }


    /**
     * get_datestamp
     * Gets a timestamp from a given $date_slug
     *
     * @deprecated  Use Slug::getTimestamp instead
     *
     * @param string  $date_slug  Date slug to inspect
     * @return integer
     */
    public static function get_datestamp($date_slug)
    {
        Log::warn("Use of Statamic_Helper::get_datestamp() is deprecated. Use Slug::getTimestamp() instead.", "core", "Statamic_Helper");
        return Slug::getTimestamp($date_slug);
    }


    /**
     * get_datetimestamp
     * Gets a timestamp from a given $date_slug
     *
     * @deprecated  Use Slug::getTimestamp() instead
     *
     * @param string  $date_slug  Date (or datetime) slug to inspect
     * @return integer
     */
    public static function get_datetimestamp($date_slug)
    {
        Log::warn("Use of Statamic_Helper::get_datetimestamp() is deprecated. Use Slug::getTimestamp() instead.", "core", "Statamic_Helper");
        return Slug::getTimestamp($date_slug);
    }


    /**
     * get_numeric
     * Gets the numeric value of the $numeric_slug
     *
     * @deprecated  Use Slug::getOrderNumber() instead
     *
     * @param string  $numeric_slug  Numeric slug to inspect
     * @return integer
     */
    public static function get_numeric($numeric_slug)
    {
        Log::warn("Use of Statamic_Helper::get_datetimestamp() is deprecated. Use Slug::getOrderNumber() instead.", "core", "Statamic_Helper");
        return Slug::getOrderNumber($numeric_slug);
    }


    /**
     * get_distance
     * Gets the distance between $point_1 and $point_2
     *
     * @param array  $point_1  Point 1 (an array of latitude and longitude)
     * @param array  $point_2  Point 2 (an array of latitude and longitude)
     * @return float
     */
    public static function get_distance_in_km($point_1, $point_2)
    {
        Log::warn("Use of Statamic_Helper::get_distance_in_km() is deprecated. Use Math::getDistanceInKilometers() instead.", "core", "Statamic_Helper");
        return Math::getDistanceInKilometers($point_1, $point_2);
    }

    /**
     * Convert kilometers to miles
     *
     * @param float  $kilometers  Kilometers to convert
     * @return float
     */
    public static function convert_km_to_miles($kilometers)
    {
        Log::warn("Use of Statamic_Helper::convert_km_to_miles() is deprecated. Use Math::convertKilometersToMiles() instead.", "core", "Statamic_Helper");
        return Math::convertKilometersToMiles($kilometers);
    }


    /**
     * reduce_double_slashes
     * Removes instances of "//" from a given $string except for URL protocols
     *
     * @deprecated  Use Path::reduceDoubleSlashes() instead
     *
     * @param string  $string  String to reduce
     * @return string
     */
    public static function reduce_double_slashes($string)
    {
        Log::warn("Use of Statamic_Helper::reduce_double_slashes() is deprecated. Use Path::tidy() instead.", "core", "Statamic_Helper");
        return Path::tidy($string);
    }


    /**
     * trim_slashes
     * Removes any extra "/" at the beginning or end of a given $string
     *
     * @deprecated  Use Path::trimSlashes() instead
     *
     * @param string  $string  String to trim
     * @return string
     */
    public static function trim_slashes($string)
    {
        Log::warn("Use of Statamic_Helper::trim_slashes() is deprecated. Use Path::trimSlashes() instead.", "core", "Statamic_Helper");
        return Path::trimSlashes($string);
    }


    /**
     * remove_numerics_from_path
     * Strips out any instances of a numeric ordering from a given $path
     *
     * @deprecated  Use Path::clean() instead
     *
     * @param string  $path  String to strip out numerics from
     * @return string
     */
    public static function remove_numerics_from_path($path)
    {
        Log::warn("Use of Statamic_Helper::remove_numerics_from_path() is deprecated. Use Path::clean() instead.", "core", "Statamic_Helper");
        return Path::clean($path);
    }


    /**
     * pop_last_segment
     * Pops the last segment off of a given $url and returns the appropriate array.
     *
     * @param string  $url  URL to derive segments from
     * @return string
     */
    public static function pop_last_segment($url)
    {
        Log::warn("Use of Statamic_Helper::pop_last_segment() is deprecated. Use URL::popLastSegment() instead.", "core", "Statamic_Helper");
        return URL::popLastSegment($url);
    }


    /**
     * resolve_path
     * Finds the actual path from a URL-friendly $path
     *
     * @deprecated As of v1.5
     * @param string  $path  Path to resolve
     * @return string
     */
    public static function resolve_path($path)
    {
        Log::warn("Use of Statamic_Helper::resolve_path() is deprecated. Use Path::resolve() instead.", "core", "Statamic_Helper");
        return Path::resolve($path);
    }


    /**
     * is_file_newer
     * Checks to see if $file is newer than $compare_to_this_one
     *
     * @deprecated  Use File::isNewer() instead
     *
     * @param string  $file  File for comparing
     * @param string  $compare_to_this_one  Path and name of file to compare against $file
     * @return boolean
     */
    public static function is_file_newer($file, $compare_to_this_one)
    {
        Log::warn("Use of Statamic_Helper::is_file_newer() is deprecated. Use File::isNewer() instead.", "core", "Statamic_Helper");
        return File::isNewer($file, $compare_to_this_one);
    }


    /**
     * is_valid
     * Determines if the given $uuid is valid
     *
     * @deprecated  Use Pattern::isValidUUID() instead
     *
     * @param string  $uuid  UUID to validate
     * @return boolean
     */
    public static function is_valid($uuid)
    {
        Log::warn("Use of Statamic_Helper::is_valid() is deprecated. Use Pattern::isValidUUID() instead.", "core", "Statamic_Helper");
        return Pattern::isValidUUID($uuid);
    }


    /**
     * random_string
     * Returns a random string $length characters long
     *
     * @deprecated  Use Helper::getRandomString() instead
     *
     * @param string  $length  Length of random string to return
     * @return string
     */
    public static function random_string($length=NULL)
    {
        Log::warn("Use of Statamic_Helper::random_string() is deprecated. Use Helper::getRandomString() instead.", "core", "Statamic_Helper");
        return Helper::getRandomString($length);
    }


    /**
     * build_file_content
     * Creates a file content from $data_array and $content
     *
     * @deprecated  Use File::buildContent() instead
     *
     * @param array  $data_array  Data to load into the file's front-matter
     * @param string  $content  Content to append to the file
     * @return string
     */
    public static function build_file_content($data_array, $content)
    {
        Log::warn("Use of Statamic_Helper::build_file_content() is deprecated. Use File::buildContent() instead.", "core", "Statamic_Helper");
        return File::buildContent($data_array, $content);
    }


    /**
     * get_template
     * Get a fully-parsed HTML template
     *
     * @deprecated  Use Theme::getParsedTemplate() instead
     *
     * @param string  $template  Template name to use
     * @param array  $data  Option array of data to incorporate into the template
     * @return string
     */
    public static function get_template($template, $data = array())
    {
        Log::warn("Use of Statamic_Helper::get_template() is deprecated. Use Theme::getParsedTemplate() instead.", "core", "Statamic_Helper");
        return Theme::getParsedTemplate($template, $data);
    }


    /**
     * is
     * Determine if a given string matches a given pattern
     *
     * @deprecated  Use Pattern::matches() instead
     *
     * @param string  $pattern  Pattern to look for in $value
     * @param string  $value  String to look through
     * @return boolean
     */
    public static function is($pattern, $value)
    {
        Log::warn("Use of Statamic_Helper::is() is deprecated. Use Pattern::matches() instead.", "core", "Statamic_Helper");
        return Pattern::matches($pattern, $value);
    }


    /**
     * prettify
     * Converts a string from underscore-slug-format to normal-format
     *
     * @deprecated  Use Slug::prettify() instead
     *
     * @param string  $string  String to convert
     * @return string
     */
    public static function prettify($string)
    {
        Log::warn("Use of Statamic_Helper::prettify() is deprecated. Use Slug::prettify() instead.", "core", "Statamic_Helper");
        return Slug::prettify($string);
    }


    /**
     * slugify
     * Converts a string from normal-format to slug-format
     *
     * credit: http://sourcecookbook.com/en/recipes/8/function-to-slugify-strings-in-php
     *
     * @deprecated  Use Slug::make() instead
     *
     * @param string  $text  String to convert
     * @return string
     */
    public static function slugify($text)
    {
        Log::warn("Use of Statamic_Helper::slugify() is deprecated. Use Slug::make() instead.", "core", "Statamic_Helper");
        return Slug::make($text);
    }


    /**
     * deslugify
     * Converts a string from slug-format to normal-format
     *
     * @deprecated  Use Slug::humanize() instead
     *
     * @param string  $text  String to convert
     * @return string
     */
    public static function deslugify($text)
    {
        Log::warn("Use of Statamic_Helper::deslugify() is deprecated. Use Slug::humanize() instead.", "core", "Statamic_Helper");
        return Slug::humanize($text);
    }


    public static function explode_options($string, $keyed = FALSE)
    {
        Log::warn("Use of Statamic_Helper::explode_options() is deprecated. Use Helper::explodeOptions() instead.", "core", "Statamic_Helper");
        return Helper::explodeOptions($string, $keyed);
    }


    /**
     * array_empty
     * Determines if a given $mixed value is an empty array or not
     *
     * @param mixed  $mixed  Value to check for an empty array
     * @return boolean
     */
    public static function array_empty($mixed)
    {
        Log::warn("Use of Statamic_Helper::array_empty() is deprecated. Use Helper::isEmptyArray() instead.", "core", "Statamic_Helper");
        return Helper::isEmptyArray($mixed);
    }
}
