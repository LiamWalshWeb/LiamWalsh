<?php
/**
 * Modifier_in_past
 * Checks to see if a variable is in the past
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_in_past extends Modifier
{
    public function index($value, $parameters=array()) {
        return (Date::resolve($value) < time()) ? "true" : "";
    }
}