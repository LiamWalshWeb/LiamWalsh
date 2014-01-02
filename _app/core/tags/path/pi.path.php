<?php
class Plugin_path extends Plugin
{
  public function index()
  {
    $src = $this->fetchParam('src', NULL, NULL, FALSE, FALSE);

    return $src ? Path::tidy(Config::getSiteRoot().'/'.$src) : null;
  }

}
