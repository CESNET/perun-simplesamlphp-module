<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

class ListOfSps
{
    public static function sortByName($a, $b)
    {
        return strcmp(strtolower($a['name']['value']), strtolower($b['name']['value']));
    }

    public static function getClass($type)
    {
        switch ($type) {
            case 'java.lang.String':
            case 'java.lang.LargeString':
                return 'string';
            case 'java.lang.Integer':
                return 'integer';
            case 'java.lang.Boolean':
                return 'boolean';
            case 'java.util.ArrayList':
            case 'java.util.LargeArrayList':
                return 'array';
            case 'java.util.LinkedHashMap':
                return 'map';
            default:
                return '';
        }
    }

    public static function printServiceName($name, $loginURL = null)
    {
        if (empty($loginURL)) {
            return $name;
        }

        return "<a class='customLink' href='" . htmlspecialchars($loginURL) . "'>" . htmlspecialchars($name) . '</a>';
    }

    public static function printAttributeValue($type, $value)
    {
        if (empty($value) && $type !== 'java.lang.Boolean') {
            return "<td class='center'>&horbar;</td>";
        }

        switch ($type) {
            case 'java.lang.String':
            case 'java.lang.LargeString':
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    $string = '<a class="customLink" href="' . htmlspecialchars($value) . '">'
                        . htmlspecialchars($value) . '</a>';
                } else {
                    $string = htmlspecialchars($value);
                }
                break;
            case 'java.lang.Integer':
                $string = htmlspecialchars($value);
                break;
            case 'java.lang.Boolean':
                if ($value !== null && $value) {
                    $string = '&#x2714;';
                } else {
                    $string = '&#x2715;';
                }
                break;
            case 'java.util.ArrayList':
            case 'java.lang.LargeArrayList':
                $string = '<ul>';
                foreach ($value as $v) {
                    $string .= '<li>' . htmlspecialchars($v) . '</li>';
                }
                $string .= '</ul>';
                break;
            case 'java.util.LinkedHashMap':
                $string = '<ul>';
                foreach ($value as $k => $v) {
                    $string .= '<li>' . htmlspecialchars($k) . ' &rarr; ' . htmlspecialchars($v) . '</li>';
                }
                $string .= '</ul>';
                break;
            default:
                $string = '';
        }
        if (! empty($string)) {
            return '<td class="' . self::getClass($type) . '">' . $string . '</td>';
        }
        return '<td/>';
    }

    public static function getPreferredTranslation($translations, $language = 'en')
    {
        if (is_string($translations)) {
            return $translations;
        }

        if (isset($translations[$language])) {
            return $translations[$language];
        }

        if (isset($translations['en'])) {
            return $translations['en'];
        }

        if (count($translations) > 0) {
            $languages = array_keys($translations);
            return $translations[$languages[0]];
        }

        // we don't have anything to return
        throw new \Exception('Nothing to return from translation.');
    }
}
