<?php
class Plugin_truncate extends Plugin
{

    var $meta = array(
        'name'       => 'Truncate',
        'version'    => '1.1',
        'author'     => 'Jack McDade',
        'author_url' => 'http://jackmcdade.com'
    );

    public function marker()
    {
        $marker = $this->fetchParam('marker', '<!--more-->');
        $ending = $this->fetchParam('ending', FALSE);
        $url    = $this->fetchParam('url', NULL);

        $this->content = Parse::template($this->content, Statamic_View::$_dataStore, 'Statamic_View::callback');
        $pos = stripos($this->content, $marker);
        
        if ($pos !== FALSE) {
            $this->content = trim(substr($this->content, 0, $pos));
            if ($ending) {
                $this->content .= "<p><a href='" . $url . "'>" . $ending . "</a><p>";
            }
        }

        return $this->content;
    }

    public function characters()
    {
        $limit  = $this->fetchParam('limit', NULL);
        $ending = $this->fetchParam('ending', '...');

        $this->content = Parse::template($this->content, Statamic_View::$_dataStore, 'Statamic_View::callback');

        if ($limit && strlen($this->content) > $limit) {
            $endpos = strpos(str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $this->content), ' ', $limit);
            if ($endpos !== FALSE) {
                $this->content = trim(substr($this->content, 0, $endpos)) . $ending;
            }
        }

        return $this->content;
    }

    public function words()
    {
        $limit  = $this->fetchParam('limit', NULL);
        $ending = $this->fetchParam('ending', '...');

        $this->content = Parse::template($this->content, Statamic_View::$_dataStore, 'Statamic_View::callback');

        $words = preg_split("/[\n\r\t ]+/", $this->content, $limit + 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
        if (count($words) > $limit) {
            end($words);
            $last_word = prev($words);

            $this->content = substr($this->content, 0, $last_word[1] + strlen($last_word[0])) . $ending;
        }

        return $this->content;
    }

}