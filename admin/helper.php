<?php
class CP_Helper
{
  public static function show_page($page, $default = true)
  {
    $admin_nav = Config::get('_admin_nav');

    return array_get($admin_nav, $page, $default);
  }

  public static function nav_count()
  {
    $default_config = YAML::parse(Config::getAppConfigPath() . '/default.settings.yaml');
    $admin_nav = array_merge($default_config['_admin_nav'], Config::get('_admin_nav', array()));

    return count(array_filter($admin_nav, 'strlen'));

  }
}
