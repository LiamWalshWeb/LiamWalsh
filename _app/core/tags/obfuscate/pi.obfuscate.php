<?php

class Plugin_Obfuscate extends Plugin
{
    public function index()
    {
        return Parse::template(HTML::obfuscateEmail($this->content), array());
    }
}