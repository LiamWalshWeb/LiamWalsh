<?php
/**
 * Modifier_img
 * Converts a variable's value to an HTML `img` tag
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_img extends Modifier
{
    public function index($value, $parameters=array()) {
        return '<img src="' . Path::toAsset($value) . '">';
    }
}