<?php
/**
 * Theme
 * API for interacting with the site's themes
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Theme
{
    // theme
    // ------------------------------------------------------------------------

    /**
     * Returns the current theme folder name
     *
     * @return string
     */
    public static function getName()
    {
        return Config::getTheme();
    }


    /**
     * Returns the path to the current theme
     *
     * @return string
     */
    public static function getPath()
    {
        return Config::getCurrentThemePath();
    }



    // templates
    // ------------------------------------------------------------------------

    /**
     * Fetches the contents of a template
     *
     * @param string  $template  Name of template to retrieve
     * @return string
     */
    public static function getTemplate($template)
    {
        return File::get(self::getTemplatePath() . $template . '.html');
    }


    /**
     * Returns a given $template parsed with given $data
     *
     * @param string  $template  Template to parse
     * @param array  $data  Associative array of data to fill into template
     * @return string
     */
    public static function getParsedTemplate($template, Array $data=array())
    {
        $parser         = new Lex\Parser();
        $template_path  = Config::getTemplatesPath() . '/templates/' . ltrim($template, '/') . '.html';

        return $parser->parse(File::get($template_path, ""), $data, array('statamic_view', 'callback'));
    }



    /**
     * Returns a list of templates for this theme
     *
     * @param string  $theme  Optional theme to list from, otherwise, current theme
     * @return array
     */
    public static function getTemplates($theme=NULL)
    {
        $templates = array();
        $list = glob("_themes/" . Helper::pick($theme, Config::getTheme()) . "/templates/*");
        if ($list) {
            foreach ($list as $name) {
                if (is_dir($name)) {
                    $folder_array = explode('/',rtrim($name,'/'));
                    $folder_name = end($folder_array);

                    $sub_list = glob($name.'/*');

                    foreach ($sub_list as $sub_name) {
                        $start = strrpos($sub_name, "/")+1;
                        $end = strrpos($sub_name, ".");
                        $templates[] = $folder_name.'/'.substr($sub_name, $start, $end-$start);
                    }
                } else {
                    $start = strrpos($name, "/")+1;
                    $end = strrpos($name, ".");
                    $templates[] = substr($name, $start, $end-$start);
                }
            }

            return $templates;
        }

        return array();
    }


    /**
     * Returns the path to the current theme's template directory
     *
     * @return string
     */
    public static function getTemplatePath()
    {
        return self::getPath() . 'templates/';
    }



    // layouts
    // ------------------------------------------------------------------------

    /**
     * Returns a list of layouts for a given $theme, or current theme if no $theme passed
     *
     * @param mixed  $theme  Theme to list layouts from, or current if none is passed
     * @return array
     */
    public static function getLayouts($theme=NULL)
    {
        $layouts = array();
        $list = glob("_themes/" . Helper::pick($theme, Config::getTheme()) . "/layouts/*");

        if ($list) {
            foreach ($list as $name) {
                $start = strrpos($name, "/")+1;
                $end = strrpos($name, ".");
                $layouts[] = substr($name, $start, $end-$start);
            }
        }

        return $layouts;
    }
}