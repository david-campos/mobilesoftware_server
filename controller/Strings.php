<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 22/01/2017
 * Time: 17:08
 */

namespace controller;

require_once dirname(__FILE__) . '/controller_exceptions/RequiredParameterException.php';

use exceptions\RequiredParameterException;
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

    public static function getReqIdentifier(string $name): string {
        return static::getConstants()['requests'][$name]['name'];
    }

    /**
     * Gets the key of the indicated parameter for the indicated request
     * @param string $name name of the request
     * @param string $param name of the parameter
     * @return string the key of the parameter
     */
    public static function getReqParam(string $name, string $param): string {
        return static::getConstants()['requests'][$name][$param];
    }

    /**
     * @param string $identifier identifier of the request
     * @return null|string the name of the class that should process the request or null if the identifier is not found
     */
    public static function getReqProcessorClass(string $identifier) {
        foreach (static::getConstants()['requests'] as $request) {
            if ($request['name'] === $identifier) {
                return $request['processor_class'];
            }
        }
        return null;
    }

    /**
     * Gets the key of the indicated general parameter
     * @param string $param the name of the parameter
     * @return string the key
     */
    public static function getGenParam(string $param): string {
        return static::getConstants()['general_params'][$param];
    }

    /**
     * Finds in the given array the value for the indicated parameter in the indicated request,
     * throws an exception if the param value is not in the array.
     * @param string $request name of the request the param belongs to
     * @param string $param_name name of the parameter whose value we want to obtain
     * @param array $vars array in which looking for the value for the parameter
     * @return string the value for the parameter
     * @throws RequiredParameterException if the key for the indicated param is not found in the array
     */
    public static function getParamValueIn(string $request, string $param_name, array $vars): string {
        $key = static::getReqParam($request, $param_name);
        if (!array_key_exists($key, $vars))
            throw new RequiredParameterException("Required parameter '$key' not found.");
        return $vars[$key];
    }

    public static function getGenParamValueIn(string $param_name, array $vars): string {
        $key = static::getGenParam($param_name);
        if (!array_key_exists($key, $vars))
            throw new RequiredParameterException("Required parameter '$key' not found.");
        return $vars[$key];
    }
}