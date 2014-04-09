<?php
/**
 * Modifier_relative
 * Relative Date Strings
 *
 * @author Statamic
 */
use \Carbon\Carbon;

class Modifier_relative extends Modifier
{
    public function index($value, $parameters = array())
    {
        if (is_int($value)) {
            // this is a timestamp
            return Carbon::createFromTimestamp($value)->diffForHumans();
        } else {
            // this is a string
            return Carbon::parse($value)->diffForHumans();
        }
    }
}