<?php
class Plugin_lorem extends Plugin {

  var $meta = array(
    'name'       => 'Lorem Ipsum Generator',
    'version'    => '0.9',
    'author'     => 'Jack McDade',
    'author_url' => 'http://jackmcdade.com'
  );

  public function index()
  {
    $params = array(
      'paragraphs'   => $this->fetchParam('paragraphs', '3', 'is_numeric'),
      'length'       => $this->fetchParam('length', 'short'),
      'decorate'     => $this->fetchParam('decorate', false),
      'links'        => $this->fetchParam('links', false),
      'ul'           => $this->fetchParam('ul', false),
      'ol'           => $this->fetchParam('ol', false),
      'dl'           => $this->fetchParam('dl', false),
      'bq'           => $this->fetchParam('bq', false),
      'code'         => $this->fetchParam('code', false),
      'headers'      => $this->fetchParam('headers', false),
      'allcaps'      => $this->fetchParam('allcaps', false)
    );

    $request_url = 'http://loripsum.net/api';
   
    foreach ($params as $key => $value)
    {
      if ($key == 'paragraphs' || $key == 'length')
        $request_url .= '/'.$value;
      elseif ($value == "yes")
        $request_url .= '/'.$key;
    }
    
    return file_get_contents($request_url);
  }
}