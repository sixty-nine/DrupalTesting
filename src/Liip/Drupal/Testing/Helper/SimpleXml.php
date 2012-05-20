<?php

namespace Liip\Drupal\Testing\Helper;

class SimpleXml
{
    public static function getAttribute(\SimpleXMLElement $el, $name)
    {
        foreach ($el->attributes() as $attrName => $value) {
            if ($attrName === $name) {
                return (string)$value;
            }
        }
        return false;
    }

    public static function hasAttribute(\SimpleXMLElement $el, $name)
    {
        return self::getAttribute($el, $name) !== false;
    }

    public static function firstChild(\SimpleXMLElement $el)
    {
        foreach ($el->children() as $child) {
            return $child;
        }
        return false;
    }
}
