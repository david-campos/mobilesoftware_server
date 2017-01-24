<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 22/01/2017
 * Time: 17:08
 */

namespace controller;

const STRINGS_PATH = "/strings.json";

abstract class Strings
{
    private static $strings = null;

    public static function getConstants(): array {
        if (static::$strings === null) {
            static::$strings = json_decode(
                file_get_contents(dirname(__FILE__) . STRINGS_PATH),
                true);
        }
        return static::$strings;
    }

    public static function getReqName(string $name): string {
        return static::getConstants()['requests'][$name]['name'];
    }

    public static function getReqParam(string $name, string $param): string {
        return static::getConstants()['requests'][$name][$param];
    }

    public static function getGenParam(string $param): string {
        return static::getConstants()['general_params'][$param];
    }
}