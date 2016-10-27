<?php
/**
 * Date
 * API for managing and manipulating dates
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */

use \Carbon\Carbon;

class Date
{
    /**
     * Resolves a given date string or integer into timestamp
     *
     * @param mixed  $date  Date to resolve
     * @return int
     */
    public static function resolve($date)
    {
        if (! is_numeric($date)) {
            if (strpos($date, '-')) {
                $date = Carbon::parse(date($date))->timestamp;
            } else {
                $date = strtotime($date);
            }
        }

        return $date;
    }


    /**
     * Formats a given $date (automatically resolving timestamps) to a given $format
     *
     * @param string  $format  Format to use (based on PHP's date formatting)
     * @param mixed  $date  Date string or timestamp (if blank, now)
     * @return mixed
     */
    public static function format($format, $date=NULL)
    {
        return date($format, self::resolve(Helper::pick($date, time())));
    }
}
