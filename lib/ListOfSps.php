<?php
namespace SimpleSAML\Module\perun;

class ListOfSps
{
    public static function sortByName($a, $b)
    {
        return strcmp(strtolower($a['facility']->getName()), strtolower($b['facility']->getName()));
    }

    public static function getClass($attribute)
    {
        if ($attribute['type'] === 'java.lang.String' || $attribute['type'] === 'java.lang.LargeString') {
            return 'string';
        } elseif ($attribute['type'] === 'java.lang.Integer') {
            return 'integer';
        } elseif ($attribute['type'] === 'java.lang.Boolean') {
            return 'boolean';
        } elseif ($attribute['type'] === 'java.util.ArrayList' || $attribute['type'] === 'java.util.LargeArrayList') {
            return 'array';
        } elseif ($attribute['type'] === 'java.util.LinkedHashMap') {
            return 'map';
        } else {
            return '';
        }
    }

    public static function printServiceName($name, $loginURL = null)
    {
        if (empty($loginURL))
        ) {
            return $name;
        }

        return "<a class='customLink' href='" . htmlspecialchars($loginURL) . "'>" . htmlspecialchars($name) . "</a>";
    }

    public static function printAttributeValue($attribute, $service, $attr)
    {
        $value = $attribute['value'];
        if (empty($value) && $attribute['type'] !== 'java.lang.Boolean') {
            return "<td class='center'>&horbar;</td>";
        }
        $string = '';
        if ($attribute['type'] === 'java.lang.String' || $attribute['type'] === 'java.lang.LargeString') {
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $string = '<a class="customLink" href="' . $value . '">' . $value . '</a>';
            } else {
                $string = $value;
            }
        } elseif ($attribute['type'] === 'java.lang.Integer') {
            $string = $value;
        } elseif ($attribute['type'] === 'java.lang.Boolean') {
            if ($value !== null && $value) {
                $string = '&#x2714;';
            } else {
                $string = '&#x2715;';
            }
        } elseif ($attribute['type'] === 'java.util.ArrayList' || $attribute['type'] === 'java.lang.LargeArrayList') {
            $string = '<ul>';
            foreach ($value as $v) {
                $string .= '<li>' . $v . '</li>';
            }
            $string .= '</ul>';
        } elseif ($attribute['type'] === 'java.util.LinkedHashMap') {
            $string = '<ul>';
            foreach ($value as $k => $v) {
                $string .= '<li>' . $k . ' &rarr; ' . $v . '</li>';
            }
            $string .= '</ul>';
        }
        if (!empty($string)) {
            return '<td class="' . self::getClass($service['facilityAttributes'][$attr]) . '">' . $string . '</td>';
        } else {
            return '<td/>';
        }
    }
}
