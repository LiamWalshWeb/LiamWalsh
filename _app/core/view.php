<?php
/**
 * Statamic_View
 * Manages display rendering within Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Statamic_View extends \Slim\View
{
    protected static $_layout = null;
    protected static $_templates = null;
    public static $_dataStore = array();


    /**
     * set_templates
     * Interface for setting templates
     *
     * @param mixed $list Template (or array of templates, in order of preference) to use for page render
     * @return void
     */
    public static function set_templates($list)
    {
        self::$_templates = $list;
    }

    /**
     * set_layout
     * Interface for setting page layout
     *
     * @param string $layout Layout to use for page render
     * @return void
     */
    public static function set_layout($layout = null)
    {
        self::$_layout = $layout;
    }

    /**
     * render
     * Finds and chooses the correct template, then renders the page
     *
     * @param string $template Template (or array of templates, in order of preference) to render the page with
     * @return string
     */
    public function render($template)
    {
        $html = '<p style="text-align:center; font-size:28px; font-style:italic; padding-top:50px;">No template found.</p>';

        $list = $template ? $list = array($template) : self::$_templates;
        $template_type = 'html';

        foreach ($list as $template) {
            $template_path = Path::assemble(BASE_PATH, Config::getTemplatesPath(), 'templates', $template);

            if (File::exists($template_path . '.html') || file_exists($template_path . '.php')) {
                # standard lex-parsed template
                if (File::exists($template_path . '.html')) {
                    $template_type = 'html';

                    $this->mergeNewData($this->data);

                    $html = Parse::template(Theme::getTemplate($template), Statamic_View::$_dataStore, array($this, 'callback'));
                    break;

                # lets forge into raw data
                } elseif (File::exists($template_path . '.php')) {

                    $template_type = 'php';
                    extract($this->data);
                    ob_start();

                    require $template_path . ".php";
                    $html = ob_get_clean();
                    break;

                } else {
                    Log::error("Template does not exist: '${template_path}'", 'core');
                }
            }
        }

        return $this->_render_layout($html, $template_type);
    }

    /**
     * _render_layout
     * Renders the page
     *
     * @param string $_html HTML of the template to use
     * @param string $template_type Content type of the template
     * @return string
     */
    public function _render_layout($_html, $template_type = 'html')
    {

        if (self::$_layout != '') {

            $this->data['layout_content'] = $_html;
            $layout_path = Path::assemble(BASE_PATH, Config::getTemplatesPath(), self::$_layout);

            if ($template_type == 'html') {

                if ( ! File::exists($layout_path . ".html")) {
                    Log::fatal("Can't find the specified theme.", 'core', 'template');

                    return '<p style="text-align:center; font-size:28px; font-style:italic; padding-top:50px;">We can\'t find your theme files. Please check your settings.';
                }

                $this->mergeNewData($this->data);
                $html = Parse::template(File::get($layout_path . ".html"), Statamic_View::$_dataStore, array($this, 'callback'));
                $html = Lex\Parser::injectNoparse($html);

            } else {
                extract($this->data);
                ob_start();
                require $layout_path . ".php";
                $html = ob_get_clean();
            }

            return $html;

        }

        return $_html;
    }
      
      
    

    /**
     * callback
     * Attempts to load a plugin?
     *
     * @param string $name
     * @param array $attributes
     * @param string $content
     * @param array $context
     * @return string
     */
    public static function callback($name, $attributes, $content, $context=array())
    {
        $output = null;
        $file   = null;
        $pos    = strpos($name, ':');

        # single function plugins
        if ($pos === false) {
            $plugin = $name;
            $call   = "index";
        } else {
            $plugin = substr($name, 0, $pos);
            $call   = substr($name, $pos + 1);
            
            if (!$call) {
                return null;
            }
        }

        # check the plugin directories
        $plugin_folders = Config::getAddOnLocations();
        foreach ($plugin_folders as $folder) {
            if (Folder::exists($folder . $plugin) && File::exists($folder . $plugin . '/pi.' . $plugin . '.php')) {

                $file = $folder . $plugin . '/pi.' . $plugin . '.php';
                break;

            } elseif (File::exists($folder . '/pi.' . $plugin . '.php')) {

                $file = $folder . '/pi.' . $plugin . '.php';
                break;
            }
        }

        # no file? return
        if (!$file) {
            return null;
        }
        
        # file exists, it's plugin time
        $class  = 'Plugin_' . $plugin;
        $output = false;
        
        if (is_callable(array(new $class, $call), false)) {
            $plug = new $class();

            $plug->attributes = $attributes;
            $plug->content    = $content;
            $plug->context    = $context;
            
            $output = $plug->$call();
        }

        if (is_array($output)) {
            $output = Parse::template($content, $output);
        }

        return $output;
    }


    /**
     * Merges any new data into this view's data store
     * 
     * @param $data  array  Array of data to merge
     * @return void
     */
    function mergeNewData($data)
    {
        foreach ($data as $key => $item) {
            if (is_object($item)) {
                unset($data[$key]);
            }
        }

        Statamic_View::$_dataStore = $data + Statamic_View::$_dataStore;
    }
}
