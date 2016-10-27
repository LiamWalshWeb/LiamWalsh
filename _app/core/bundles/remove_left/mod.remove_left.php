<?php

use Stringy\StaticStringy as Stringy;

/**
 * Modifier_remove_left
 * 
 * Returns a new string with the prefix $params[0] removed, if present.
 */
class Modifier_remove_left extends Modifier
{
    public function index($value, $params=array()) {
        return Stringy::removeLeft($value, array_get($params, 0));
    }
}