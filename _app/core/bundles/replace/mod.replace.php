<?php

use Stringy\StaticStringy as Stringy;

/**
 * Modifier_replace
 * 
 * Replaces all occurrences of a search in $params[0] by $params[1].
 */
class Modifier_replace extends Modifier
{
    public function index($value, $params=array()) {
        return Stringy::replace($value, array_get($params, 0), array_get($params, 1));
    }
}