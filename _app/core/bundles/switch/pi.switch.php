<?php
class Plugin_switch extends Plugin
{
  public static $container = array();

  public function index()
  {
    $between = $this->fetchParam('between', false, false, false, false);

    if ($between) {

      # create unique instance key
      # using all parameters to allow a workaround
      # for duplicate switch tags
      $hash = md5(implode(",",$this->attributes));

      if ( ! isset(self::$container[$hash])) {
        # setup unique, per-instance counter
        self::$container[$hash] = 0;
      }

      $switch_vars = Helper::explodeOptions($between);
      $switch_count = count($switch_vars);

      $switch = $switch_vars[(self::$container[$hash]) % $switch_count];

      self::$container[$hash]++;

      return $switch;
    }

    return null;

  }

}
