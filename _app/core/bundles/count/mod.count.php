<?php

class Modifier_count extends Modifier
{
    public function index($value, $parameters=array())
    {
        return count($value);
    }
}