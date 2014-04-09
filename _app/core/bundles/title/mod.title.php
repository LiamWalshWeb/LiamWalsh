<?php
/**
 * Modifier_title
 * Converts a variable to title-case
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_title extends Modifier
{
    public function index($value, $parameters=array()) {
        return ucwords($value);
    }
}