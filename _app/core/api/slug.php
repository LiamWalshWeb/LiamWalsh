<?php
/**
 * Slug
 * API for interacting and manipulating slugs
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Slug
{
    /**
     * Creates a slug from a given $string
     *
     * @param string  $string  Value to make slug from
     * @param array  $options  Array of options for how slugs are created
     * @return string
     */
    public static function make($string, $options = array())
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $string = mb_convert_encoding((string)$string, 'UTF-8', mb_list_encodings());

        $defaults = array(
            'delimiter' => '-',
            'limit' => null,
            'lowercase' => false,
            'replacements' => array(),
            'transliterate' => Config::get('transliterate', false),
        );

        // Merge options
        $options = array_merge($defaults, $options);

        $char_map = array(
            // Latin
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y',

            // Latin symbols
            '©' => '(c)',

            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
            'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
            'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
            'Ϋ' => 'Y',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',

            // Turkish
            'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
            'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',

            // Russian
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',

            // Ukrainian
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

            // Czech
            'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
            'Ž' => 'Z',
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z',

            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
            'Ż' => 'Z',
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',

            // Latvian
            'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
            'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
            'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'š' => 's', 'ū' => 'u', 'ž' => 'z'
        );

        // Make custom replacements
        $string = preg_replace(array_keys($options['replacements']), $options['replacements'], $string);

        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $string = str_replace(array_keys($char_map), $char_map, $string);
        }

        // Replace non-alphanumeric characters with our delimiter
        $string = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $string);

        // Remove duplicate delimiters
        $string = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $string);

        // Truncate slug to max. characters
        $string = mb_substr($string, 0, ($options['limit'] ? $options['limit'] : mb_strlen($string, 'UTF-8')), 'UTF-8');

        // Remove delimiter from ends
        $string = trim($string, $options['delimiter']);

        return $options['lowercase'] ? mb_strtolower($string, 'UTF-8') : $string;
    }


    /**
     * Humanizes a slug, converting delimiters to spaces
     *
     * @param string  $value  Value to humanize from slug form
     * @return string
     */
    public static function humanize($value)
    {
        return trim(preg_replace('~[-_]~', ' ', $value), " ");
    }


    /**
     * Pretties up a slug, making it title case
     *
     * @param string  $value  Value to pretty
     * @return string
     */
    public static function prettify($value)
    {
        return ucwords(self::humanize($value));
    }


    /**
     * Checks to see whether a given $slug matches the DATE pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDate($slug)
    {
        if (!preg_match(Pattern::DATE, $slug, $matches)) {
            return FALSE;
        }

        return Pattern::isValidDate($matches[0]);
    }


    /**
     * Checks to see whether a given $slug matches the DATETIME pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDateTime($slug)
    {
        if (!preg_match(Pattern::DATETIME, $slug, $matches)) {
            return FALSE;
        }

        return Pattern::isValidDate($matches[0]);
    }


    /**
     * Checks to see whether a given $slug matches the NUMERIC pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isNumeric($slug)
    {
        return (bool) preg_match(Pattern::NUMERIC, $slug);
    }


    /**
     * Checks the slug for status indicators
     *
     * @param string  $slug  Slug to check
     * @return string
     */
    public static function getStatus($slug)
    {
        $slugParts = explode('/', $slug);
        $slug = end($slugParts);

        if (substr($slug, 0, 2) === "__") {
            return 'draft';
        } elseif (substr($slug, 0, 1) === "_") {
            return 'hidden';
        }

        return 'live';
    }


    /**
     * Returns the proper status prefix
     *
     * @param string  $status  Status to check
     * @return string
     */
    public static function getStatusPrefix($status)
    {
        if ($status === 'draft') {
            return '__';
        } elseif ($status === 'hidden') {
            return '_';
        }

        return '';
    }

    /**
     * Checks if the slug has a draft indicator
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDraft($slug)
    {
        return self::getStatus($slug) === 'draft';
    }


    /**
     * Checks if the slug has a hidden indicator
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isHidden($slug)
    {
        return self::getStatus($slug) === 'hidden';
    }


    /**
     * Checks if the slug has a no status indicators (thus, live)
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isLive($slug)
    {
        return self::getStatus($slug) === 'live';
    }


    /**
     * Gets the date and time from a given $slug
     *
     * @param string  $slug  Slug to parse
     * @return int
     */
    public static function getTimestamp($slug)
    {
        if (!preg_match(Pattern::DATE_OR_DATETIME, $slug, $matches) || !Pattern::isValidDate($matches[0])) {
            return FALSE;
        }

        $date_string = substr($matches[0], 0, 10);
        $delimiter   = substr($date_string, 4, 1);
        $date_array  = explode($delimiter, $date_string);

        // check to see if this is a full date and time
        $time_string = (strlen($matches[0]) > 11) ? substr($matches[0], 11, 4) : '0000';

        // construct the stringed time
        $date = $date_array[2] . '-' . $date_array[1] . '-' . $date_array[0];
        $time = substr($time_string, 0, 2) . ":" . substr($time_string, 2);

        return strtotime("{$date} {$time}");
    }


    /**
     * Gets the order number from a given $slug
     *
     * @param string  $slug  Slug to parse
     * @return int
     */
    public static function getOrderNumber($slug)
    {
        if (!preg_match(Pattern::NUMERIC, $slug, $matches)) {
            return FALSE;
        }

        return $matches[1];
    }
}
