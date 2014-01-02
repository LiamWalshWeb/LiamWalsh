<?php

require_once('lib/simplepie_1.3.mini.php');

class Plugin_simplepie extends Plugin {

  var $meta = array(
  'name'       => 'Simple Pie',
  'version'    => '1.0.1',
  'author'     => 'Statamic',
  'author_url' => 'http://statamic.com'
  );

  public function index()
  {
    $url            = $this->fetch_param('url', null);
    $order_by_date  = $this->fetch_param('order_by_date', true, false, true);
    $offset         = $this->fetch_param('offset', 0);
    $cache          = $this->fetch_param('cache', false, false, true);
    $timeout        = $this->fetch_param('timeout', 10);

    $count          = $this->fetch_param('count', false);
    $limit          = $this->fetch_param('limit', 10);
    $limit = $limit == 'no' ? 0 : $limit;

    if ($count && $this->fetch_param('limit', false) === false) {
      $limit = $count; # backwards compatibility
    }

    $feed = new SimplePie();

    $feed->set_cache_location("_cache");
    $feed->enable_cache($cache);

    $feed->set_feed_url($url);
    $feed->enable_order_by_date($order_by_date);

    $success = $feed->init();
    $feed->handle_content_type();

    if ( ! $feed->error() && $success) {

      $loop_count = 0;
      $output = '';

      foreach($feed->get_items($offset) as $item) {
        $data = array();
        $data['title']        = $item->get_title();
        $data['permalink']    = $item->get_permalink();
        $data['date']         = $item->get_date(Statamic::get_date_format());
        $data['updated_date'] = $item->get_updated_date(Statamic::get_date_format());
        $data['author']       = $item->get_author();
        $data['category']     = $item->get_category();
        $data['description']  = $item->get_description();
        $data['content']      = $item->get_content();

        $loop_count ++;
        $output .= Parse::template($this->content, $data);

        if ($loop_count >= $limit) break;
      }

      return $output;
    }

    return '';
  }

  public function feed()
  {
    return $this->index();
  }

}