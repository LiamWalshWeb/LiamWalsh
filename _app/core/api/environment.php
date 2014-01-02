<?php
/**
 * Environment
 * API to inspect and set environments
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Environment
{
    /**
     * Detects the current environment
     *
     * @return mixed
     */
    public static function detect()
    {
        $uri  = Request::getURL();

        // get configured environments
        $environments = Config::get("_environments");

        if (is_array($environments)) {
            foreach ($environments as $environment => $patterns) {
                foreach ($patterns as $pattern) {
                    if (Pattern::matches($pattern, $uri)) {
                        return $environment;
                    }
                }
            }
        }

        return NULL;
    }


    /**
     * Sets the current environment to the given $environment
     *
     * @param string  $environment  Environment to set
     * @return void
     */
    public static function set($environment)
    {
        $app = \Slim\Slim::getInstance();

        $app->config['environment'] = $environment;
        $app->config['is_'.$environment] = TRUE;
        $environment_config = YAML::parse("_config/environments/{$environment}.yaml");

        if (is_array($environment_config)) {
            $app->config = array_merge($app->config, $environment_config);
        }
    }


    /**
     * Detects and sets the current environment in one call
     *
     * @return void
     */
    public static function establish()
    {
        $environment = self::detect();

        if ($environment) {
            self::set($environment);
        }
    }
}