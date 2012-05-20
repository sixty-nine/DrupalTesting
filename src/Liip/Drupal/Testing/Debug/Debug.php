<?php

namespace Liip\Drupal\Testing\Debug;

class Debug
{
    public static function dumpArray($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $res = '';

        foreach ($array as $item) {
            if ($res) {
                $res .= ', ';
            }
            if (!is_array($item)) {
                $res .= $item;
            } else {
                $res .= '[' . self::dumpArray($item) . ']';
            }
        }

        return '[' . $res . ']';
    }
}
