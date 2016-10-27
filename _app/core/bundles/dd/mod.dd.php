<?php

class Modifier_dd extends Modifier
{
    public function index($value, $parameters=array())
    {
        dd($value);
    }
}
