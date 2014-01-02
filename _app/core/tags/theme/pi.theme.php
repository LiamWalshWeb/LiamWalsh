<?php
class Plugin_theme extends Plugin
{
  public function __construct()
  {
    parent::__construct();

    $this->theme_assets_path = Config::getThemeAssetsPath();
    $this->theme_path = Config::getCurrentthemePath();
    $this->theme_root = Config::getTemplatesPath();
    $this->site_root  = Config::getSiteRoot();
  }

  # Usage example: {{ theme:partial src="sidebar" }}
  public function partial()
  {
    $src = $this->fetchParam('src', null, null, false, false);

    if ($src) {
      $src .= ".html";

      $partial_path = $this->theme_root . 'partials/' . ltrim($src, '/');
      if (File::exists($partial_path)) {
        Statamic_View::$_dataStore = array_merge(Statamic_View::$_dataStore, $this->attributes);

        return Parse::template(file_get_contents($partial_path), Statamic_View::$_dataStore, 'Statamic_View::callback');
      }
    }

    return null;
  }

  # Usage example: {{ theme:asset src="file.ext" }}
  public function asset()
  {
    $src = $this->fetchParam('src', Config::getTheme().'.js', null, false, false);
    $file = $this->theme_path.$this->theme_assets_path.$src;

    return $this->site_root.$file;
  }

  # Usage example: {{ theme:js src="jquery" }}
  public function js()
  {
    $src = $this->fetchParam('src', Config::getTheme().'.js', null, false, false);
    $file = $this->theme_path.$this->theme_assets_path.'js/'.$src;
    $cache_bust = $this->fetchParam('cache_bust', Config::get('theme_cache_bust', false), false, true, true);

    # Add '.js' to the end if not present.
    if ( ! preg_match("(\.js)", $file)) {
      $file .= '.js';
    }

    if ($cache_bust && File::exists($file)) {
      $file .= '?v='.$last_modified = filemtime($file);
    }

    return $this->site_root.$file;
  }

  # Usage example: {{ theme:css src="primary" }}
  public function css()
  {
    $src        = $this->fetchParam('src', Config::getTheme().'.css', null, false, false);
    $file       = $this->theme_path.$this->theme_assets_path.'css/'.$src;
    $cache_bust = $this->fetchParam('cache_bust', Config::get('theme_cache_bust', false), false, true, true);

    # Add '.css' to the end if not present.
    if (! preg_match("(\.css)", $file)) {
      $file .= '.css';
    }

    // Add cache busting query string
    if ($cache_bust && File::exists($file)) {
      $file .= '?v='.$last_modified = filemtime($file);
    }

    return $this->site_root.$file;
  }

  # Usage example: {{ theme:img src="logo.png" }}
  public function img()
  {
    $src  = $this->fetchParam('src', null, null, false, false);
    $file = $this->theme_path.$this->theme_assets_path.'img/'.$src;
    $cache_bust = $this->fetchParam('cache_bust', Config::get('theme_cache_bust', false), false, true, true);

    if ($cache_bust && File::exists($file)) {
      $file .= '?v='.$last_modified = filemtime($file);
    }

    return $this->site_root.$file;
  }
}
