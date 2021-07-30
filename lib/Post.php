<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

/**
 * Class Post
 *
 * @package SimpleSAML\Module\perun
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class Post
{
    /**
     * Write out one or more INPUT elements for the given name-value pair.
     *
     * If the value is a string, this function will write a single INPUT element. If the value is an array, it will
     * write multiple INPUT elements to recreate the array.
     *
     * @param string $name The name of the element.
     * @param string|array $value The value of the element.
     */
    public static function printItem($name, $value)
    {
        assert(is_string($name));
        assert(is_string($value) || is_array($value));

        if (is_string($value)) {
            echo '<input type="hidden" name="' . htmlspecialchars($name) .
                 '" value="' . htmlspecialchars($value) . '" />';
            return;
        }

        // This is an array...
        foreach ($value as $index => $item) {
            self::printItem($name . '[' . $index . ']', $item);
        }
    }
}
