<?php

use Stringy\StaticStringy as Stringy;

/**
 * Modifier_ends_with
 * 
 * Returns true if the string ends with a given substring, false otherwise.
 * the comparison is case-insensitive, but can be made sensitive by setting $params[1] to true.
 */
class Modifier_ends_with extends Modifier
{
    public function index($value, $params=array()) {
        return Stringy::endsWith($value, array_get($params, 0), array_get($params, 1, false));
    }
}