<?php

use Stringy\StaticStringy as Stringy;

/**
 * Modifier_contains
 * 
 * Returns true if the string contains $needle, false otherwise. By default,
 * the comparison is case-insensitive, but can be made sensitive by setting $params[1] to true.
 */
class Modifier_contains extends Modifier
{
    public function index($value, $params=array()) {
        return Stringy::contains($value, array_get($params, 0), array_get($params, 1, false));
    }
}