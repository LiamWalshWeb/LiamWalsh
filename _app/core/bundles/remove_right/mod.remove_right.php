<?php

use Stringy\StaticStringy as Stringy;

/**
 * Modifier_remove_right
 * 
 * Returns a new string with the prefix $params[0] removed, if present.
 */
class Modifier_remove_right extends Modifier
{
    public function index($value, $params=array()) {
        return Stringy::removeRight($value, array_get($params, 0));
    }
}