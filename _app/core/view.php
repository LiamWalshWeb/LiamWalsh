<?php
/**
 * Statamic_View
 * Manages display rendering within Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2012 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Statamic_View extends \Slim\View
{
  protected static $_layout     = NULL;
  protected static $_templates  = NULL;
  public static $_dataStore  = array();

  /**
   * __construct
   * Starts up Statamic_View
   *
   * @return void
   */
  public function __construct()
  {
    $this->parser = new Lex\Parser();
    $this->parser->cumulativeNoparse(true);
  }

  /**
   * set_templates
   * Interface for setting templates
   *
   * @param mixed  $list  Template (or array of templates, in order of preference) to use for page render
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
   * @param string  $layout  Layout to use for page render
   * @return void
   */
  public static function set_layout($layout=NULL)
  {
    self::$_layout = $layout;
  }

  /**
   * render
   * Finds and chooses the correct template, then renders the page
   *
   * @param string  $template  Template (or array of templates, in order of preference) to render the page with
   * @return string
   */
  public function render($template)
  {
    $html = '<p style="text-align:center; font-size:28px; font-style:italic; padding-top:50px;">No template found.</p>';

    $list = $template ?  $list = array($template) : self::$_templates;

    $allow_php = Config::get('_allow_php', false);

    foreach ($list as $template) {
      $template_path = $this->getTemplatesDirectory() . '/templates/' . ltrim($template, '/');
      $template_type = 'html';

      if (file_exists($template_path.'.html') || file_exists($template_path.'.php')) {

        # standard lex-parsed template
        if (file_exists($template_path.'.html')) {

          Statamic_View::$_dataStore = array_merge(Statamic_View::$_dataStore, $this->data);
          $html = $this->parser->parse(Theme::getTemplate($template), Statamic_View::$_dataStore, array($this, 'callback'), $allow_php);
          break;

        # lets forge into raw data
        } elseif (file_exists($template_path.'.php')) {

          $template_type = 'php';
          extract($this->data);
          ob_start();
          require $template_path.".php";
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
   * @param string  $_html  HTML of the template to use
   * @param string  $template_type  Content type of the template
   * @return string
   */
  public function _render_layout($_html, $template_type='html')
  {
      if (self::$_layout <> '') {

        $this->data['layout_content'] = $_html;
        $layout_path = $this->getTemplatesDirectory() . '/' . ltrim(self::$_layout, '/');

        if ($template_type == 'html') {

          if ( ! file_exists($layout_path.".html")) {
            Log::fatal("Can't find the specified theme", 'template');
            return '<p style="text-align:center; font-size:28px; font-style:italic; padding-top:50px;">We can\'t find your theme files. Please check your settings.';
          }

          Statamic_View::$_dataStore = array_merge(Statamic_View::$_dataStore, $this->data);
          $html = $this->parser->parse(file_get_contents($layout_path.".html"), Statamic_View::$_dataStore, array($this, 'callback'), true);
          $html = Lex\Parser::injectNoparse($html);

        } else {

          extract($this->data);
          ob_start();
          require $layout_path.".php";
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
   * @param string  $name
   * @param array  $attributes
   * @param string  $content
   * @return string
   */
  public static function callback($name, $attributes, $content)
  {
    $parser = new Lex\Parser();
    $parser->cumulativeNoparse(true);

    $output = null;

    # single function plugins
    if (strpos($name, ':') === FALSE) {

      $plugin = $name;
      $call   = "index";

    } else {

      $pieces = explode(':', $name, 2);

      # no function exists
      if (count($pieces) != 2) return NULL;

      $plugin = $pieces[0];
      $call   = $pieces[1];
    }

    # check the plugin directories
    $plugin_folders = array('_add-ons/', '_app/core/tags/');
    foreach ($plugin_folders as $folder) {

      if (is_dir($folder.$plugin) && is_file($folder.$plugin.'/pi.'.$plugin.'.php')) {

        $file = $folder.$plugin.'/pi.'.$plugin.'.php';
        break;

      } elseif (is_file($folder.'/pi.'.$plugin.'.php')) {

        $file = $folder.'/pi.'.$plugin.'.php';
        break;
      }
    }

    # plugin exists
    if (isset($file)) {

      require_once($file);
      $class = 'Plugin_'.$plugin;

      #formatted properly
      if (class_exists($class)) {
        $plug = new $class();
      }

      $output = false;

      # function exists
      if (method_exists($plug, $call)) {
        $plug->attributes = $attributes;
        $plug->content    = $content;

        $output = $plug->$call();
      } elseif (class_exists($class) && ! method_exists($plug, $call)) {
        $output = $class::$call();
      }

      if (is_array($output)) {
        $output = $parser->parse($content, $output, array('Statamic_View', 'callback'));
      }
    }

    return $output;

  }
}
