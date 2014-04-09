<?php
/**
 * Modifier_slugify
 * Replaces non-letter-characters in a variable with hyphens
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_slugify extends Modifier
{
    public function index($value, $parameters=array()) {
        $delimiter = array_get($parameters, 0, '-');
        return Slug::make($value, array('delimiter' => $delimiter));
    }
}