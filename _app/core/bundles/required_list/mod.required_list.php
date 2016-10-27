<?php
/**
 * Modifier_required_list
 * Implodes an array into an ampersand delimited string
 *
 * @author Statamic
 */

class Modifier_required_list extends Modifier
{
    public function index($value, $parameters=array())
    {
        return join('&', $value);
    }
}