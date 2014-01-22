<?php
/**
 * Parse
 * API for parsing different types of content and templates
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Parse
{
    /**
     * Parse a block of YAML into PHP
     *
     * @param string  $yaml  YAML-formatted string to parse
     * @return array
     */
    public static function yaml($yaml)
    {
        return YAML::parse($yaml);
    }


    /**
     * Parses a template, replacing variables with their values
     *
     * @param string  $html  HTML template to parse
     * @param array  $variables  List of variables ($key => $value) to replace into template
     * @param string  $callback  Callback to call when done
     * @return string
     */
    public static function template($html, $variables, $callback = array('statamic_view', 'callback'))
    {
        $parser = new \Lex\Parser();
        $parser->cumulativeNoparse(TRUE);
        $allow_php = Config::get('_allow_php', false);

        return $parser->parse($html, $variables, $callback, $allow_php);
    }


    /**
     * Parses a tag loop, replacing template variables with each array in a list of arrays
     *
     * @param string  $content  Template for replacing
     * @param array  $data  Array of arrays containing values
     * @return string
     */
    public static function tagLoop($content, $data)
    {
        $output = "";

        // loop through each record of $data
        foreach ($data as $item) {
            $item_content = $content;

            // replace all inline instances of { variable } with the variable's value
            if (preg_match_all(Pattern::TAG, $item_content, $data_matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE)) {
                foreach ($data_matches as $match) {
                    $tag  = $match[0][0];
                    $name = $match[1][0];
                    if (isset($item[$name])) {
                        $item_content = str_replace($tag, $item[$name], $item_content);
                    }
                }
            }

            // add this record's parsed template to the output string
            $output .= Parse::template($item_content, $item);
        }

        // return what we've parsed
        return $output;
    }


    /**
     * Parses a conditions string
     *
     * @param string  $conditions  Conditions to parse
     * @return array
     */
    public static function conditions($conditions)
    {
        $conditions = explode(",", $conditions);
        $output = array();

        foreach ($conditions as $condition) {
            $result = Parse::condition($condition);
            $output[$result['key']] = $result['value'];
        }

        return $output;
    }


    /**
     * Recursively parses a condition (key:value), returning the key and value
     *
     * @param string  $condition  Condition to parse
     * @return array
     */
    public static function condition($condition)
    {
        // has a colon, is a comparison
        if (strstr($condition, ":") !== FALSE) {
            // breaks this into key => value
            $parts  = explode(":", $condition, 2);

            $condition_array = array(
                "key" => trim($parts[0]),
                "value" => Parse::conditionValue(trim($parts[1]))
            );

        // doesn't have a colon, looking for existence (or lack thereof)
        } else {
            $condition = trim($condition);
            $condition_array = array(
                "key" => $condition,
                "value" => array()
            );

            if (substr($condition, 0, 1) === "!") {
                $condition_array['key'] = substr($condition, 1);
                $condition_array['value'] = array(
                    "kind" => "existence",
                    "type" => "lacks"
                );
            } else {
                $condition_array['value'] = array(
                    "kind" => "existence",
                    "type" => "has"
                );
            }
        }

        // return the parsed array
        return $condition_array;
    }


    /**
     * Recursively parses a condition, returning the key and value
     *
     * @param string  $value  Condition to parse
     * @return array
     */
    public static function conditionValue($value)
    {
        // found a bar, split this
        if (strstr($value, "|")) {
            if (substr($value, 0, 4) == "not ") {
                $item = array(
                    "kind" => "comparison",
                    "type" => "not in",
                    "value" => explode("|", substr($value, 4))
                );
            } else {
                $item = array(
                    "kind" => "comparison",
                    "type" => "in",
                    "value" => explode("|", $value)
                );
            }
        } else {
            if (substr($value, 0, 4) == "not ") {
                $item = array(
                    "kind" => "comparison",
                    "type" => "not equal",
                    "value" => substr($value, 4)
                );
            } elseif (substr($value, 0, 2) == "<=") {
                // less than or equal to
                $item = array(
                    "kind" => "comparison",
                    "type" => "less than or equal to",
                    "value" => substr($value, 2)
                );
            } elseif (substr($value, 0, 3) == "<= ") {
                // less than or equal to
                $item = array(
                    "kind" => "comparison",
                    "type" => "less than or equal to",
                    "value" => substr($value, 3)
                );
            } elseif (substr($value, 0, 2) == ">=") {
                // greater than or equal to
                $item = array(
                    "kind" => "comparison",
                    "type" => "greater than or equal to",
                    "value" => substr($value, 2)
                );
            } elseif (substr($value, 0, 3) == ">= ") {
                // greater than or equal to
                $item = array(
                    "kind" => "comparison",
                    "type" => "greater than or equal to",
                    "value" => substr($value, 3)
                );
            } elseif (substr($value, 0, 1) == ">") {
                // greater than
                $item = array(
                    "kind" => "comparison",
                    "type" => "greater than",
                    "value" => substr($value, 1)
                );
            } elseif (substr($value, 0, 2) == "> ") {
                // greater than
                $item = array(
                    "kind" => "comparison",
                    "type" => "greater than",
                    "value" => substr($value, 2)
                );
            } elseif (substr($value, 0, 1) == "<") {
                // less than
                $item = array(
                    "kind" => "comparison",
                    "type" => "less than",
                    "value" => substr($value, 1)
                );
            } elseif (substr($value, 0, 2) == "< ") {
                // less than
                $item = array(
                    "kind" => "comparison",
                    "type" => "less than",
                    "value" => substr($value, 2)
                );
            } else {
                $item = array(
                    "kind" => "comparison",
                    "type" => "equal",
                    "value" => $value
                );
            }
        }

        return $item;
    }
}