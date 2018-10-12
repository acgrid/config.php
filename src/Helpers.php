<?php


namespace ACGrid\Config;


class Helpers
{
    public static function typedHash(array $types = [])
    {
        return function ($value, array $default) use ($types) {
            $tmp = [];
            foreach($types as $key => $type) {
                if(is_callable($type)) {
                    $tmp[$key] = call_user_func($type, $value[$key], $default[$key] ?? null);
                }else{
                    $tmp[$key] = $value[$key] ?? ($default[$key] ?? null);
                }
            }
            return $tmp;
        };
    }

    public static function typedArray(callable $fixedType)
    {
        return function ($value, array $default) use ($fixedType) {
            $tmp = [];
            foreach($value as $key => $val) {
                $tmp[$key] = call_user_func($fixedType, $val, $default[$key] ?? null);
            }
            return $tmp;
        };
    }

    public static function float($minValue = null)
    {
        return function ($value, float $default) use ($minValue) {
            return is_numeric($value) ? (isset($minValue) && $value >= $minValue ? floatval($value) : $default) : $default;
        };
    }

    public static function integer($minValue = null)
    {
        return function($value, int $default) use ($minValue) {
            return is_numeric($value) ? (isset($minValue) && $value >= $minValue ? intval($value) : $default) : $default;
        };
    }

    public static function rangedInt(int $minValue, int $maxValue)
    {
        return function ($value, int $default) use ($minValue, $maxValue) {
            return is_numeric($value) ? ($value >= $minValue && $value <= $maxValue ? intval($value) : $default) : $default;
        };
    }

    public static function unsignedInteger()
    {
        static $reader;
        return $reader ?? ($reader = static::integer(0));
    }

    public static function unsignedFloat()
    {
        static $reader;
        return $reader ?? ($reader = static::float(0));
    }

    public static function json()
    {
        static $reader;
        return $reader ?? ($reader = function ($value, $default){
                return json_decode($value, false) ?? $default;
            });
    }

    public static function asJson()
    {
        static $writer;
        return $writer ?? ($writer = function ($newValue){
                return json_encode($newValue);
            });
    }

    public static function boolean()
    {
        static $reader;
        return $reader ?? ($reader = function ($value, bool $default){
                return isset($value) ? (is_string($value) ? ($value === '1' || $value === 'yes' || $value === 'enable') : boolval($value)) : $default;
            });
    }

    public static function enum(array $allowed)
    {
        return function ($value, $default) use ($allowed){
            return in_array($value, $allowed) ? $value : $default;
        };
    }

    public static function csv()
    {
        static $reader;
        return $reader ?? ($reader = function ($value, array $default){
                return is_string($value) ? (strpos($value, ',') === false ? [$value] : explode(',', $value)) : $default;
            });
    }

    public static function asCsv()
    {
        static $writer;
        return $writer ?? ($writer = function ($newValue){
                return is_array($newValue) ? implode(',', $newValue) : $newValue;
            });
    }

}