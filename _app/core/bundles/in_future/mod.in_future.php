<?php
/**
 * Modifier_in_future
 * Checks to see if a variable is in the future
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_in_future extends Modifier
{
    public function index($value, $parameters=array()) {
        return (Date::resolve($value) > time()) ? "true" : "";
    }
}